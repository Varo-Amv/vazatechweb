<?php
// assets/api/orders.php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../inc/koneksi.php';

/**
 * ==== UBAH DI SINI SAJA ====
 * $TABLE  : nama tabel barumu
 * $COL[...] : mapping kolom pada tabel barumu -> nama standar yang dipakai UI
 *
 * Contoh: kalau di tabel barumu kolomnya "order_kode", isi 'order_code' => 'order_kode'
 */
$TABLE = 'orders'; // <-- ganti: mis. 'revenue' atau 'tbl_orders_baru'
$COL = [
  'order_code'      => 'order_code',
  'buyer_name'      => 'buyer_name',
  'buyer_email'     => 'buyer_email',
  'product_name'    => 'product_name',
  'qty'             => 'qty',
  'unit_price'      => 'unit_price',
  'subtotal'        => 'subtotal',
  'payment_channel' => 'payment_channel', // QRIS/OVO/GoPay/DANA/BCA VA, dsb
  'status'          => 'status',          // pending/paid/processed/success/failed/expired/cancelled
  'created_at'      => 'created_at',
  'updated_at'      => 'updated_at',
];
// ==== sampai sini ====

$isPDO    = isset($pdo) && $pdo instanceof PDO;
$isMySQLi = isset($koneksi) && $koneksi instanceof mysqli;
function out($x){ echo json_encode($x, JSON_UNESCAPED_UNICODE); exit; }
function escLike($s){ return str_replace(['%','_'], ['\%','\_'], $s); }

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

function selectClause($C){
  // alias kolom ke nama standar yang dipakai UI
  return sprintf(
    "%s AS order_code, %s AS buyer_name, %s AS buyer_email, %s AS product_name,
     %s AS qty, %s AS unit_price, %s AS subtotal,
     %s AS payment_channel, %s AS status,
     %s AS created_at, %s AS updated_at",
    $C['order_code'], $C['buyer_name'], $C['buyer_email'], $C['product_name'],
    $C['qty'], $C['unit_price'], $C['subtotal'],
    $C['payment_channel'], $C['status'],
    $C['created_at'], $C['updated_at']
  );
}

if ($action === 'list') {
  $q       = trim($_GET['q'] ?? '');
  $from    = trim($_GET['from'] ?? '');
  $to      = trim($_GET['to'] ?? '');
  $status  = trim($_GET['status'] ?? '');
  $channel = trim($_GET['channel'] ?? '');

  if ($status  === 'all') $status  = '';
  if ($channel === 'all') $channel = '';

  $sel = selectClause($COL);

  // NOTE: kalau kolom tanggalmu berupa UNIX timestamp INT,
  // ganti DATE({$COL['created_at']}) menjadi DATE(FROM_UNIXTIME({$COL['created_at']}))
  $where = []; $params = [];
  if ($q !== '') {
    $where[] = "({$COL['order_code']} LIKE :q OR {$COL['buyer_name']} LIKE :q OR {$COL['buyer_email']} LIKE :q OR {$COL['product_name']} LIKE :q)";
    $params[':q'] = '%'.escLike($q).'%';
  }
  if ($from !== '') { $where[] = "DATE({$COL['created_at']}) >= :from"; $params[':from'] = $from; }
  if ($to   !== '') { $where[] = "DATE({$COL['created_at']}) <= :to";   $params[':to']   = $to;   }
  if ($status  !== '') { $where[] = "{$COL['status']} = :status"; $params[':status'] = $status; }
  if ($channel !== '') { $where[] = "{$COL['payment_channel']} = :ch"; $params[':ch'] = $channel; }

  $sql = "SELECT $sel FROM $TABLE";
  if ($where) $sql .= " WHERE ".implode(' AND ', $where);
  $sql .= " ORDER BY {$COL['created_at']} DESC LIMIT 500";

  try {
    if ($isPDO) {
      $st = $pdo->prepare($sql);
      $st->execute($params);
      out($st->fetchAll(PDO::FETCH_ASSOC) ?: []);
    } elseif ($isMySQLi) {
      // versi sederhana untuk mysqli (tanpa prepared)
      $w = [];
      if ($q !== '')      $w[] = "({$COL['order_code']} LIKE '%".$koneksi->real_escape_string($q)."%' OR {$COL['buyer_name']} LIKE '%".$koneksi->real_escape_string($q)."%' OR {$COL['buyer_email']} LIKE '%".$koneksi->real_escape_string($q)."%' OR {$COL['product_name']} LIKE '%".$koneksi->real_escape_string($q)."%')";
      if ($from !== '')   $w[] = "DATE({$COL['created_at']}) >= '".$koneksi->real_escape_string($from)."'";
      if ($to   !== '')   $w[] = "DATE({$COL['created_at']}) <= '".$koneksi->real_escape_string($to)."'";
      if ($status !== '') $w[] = "{$COL['status']} = '".$koneksi->real_escape_string($status)."'";
      if ($channel!=='')  $w[] = "{$COL['payment_channel']} = '".$koneksi->real_escape_string($channel)."'";

      $sql2 = "SELECT $sel FROM $TABLE";
      if ($w) $sql2 .= " WHERE ".implode(' AND ', $w);
      $sql2 .= " ORDER BY {$COL['created_at']} DESC LIMIT 500";
      $res = $koneksi->query($sql2);
      $rows = [];
      if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
      out($rows);
    } else out([]);
  } catch (Throwable $e) { out([]); }
}

if ($action === 'stats') {
  // orders_today   = jumlah baris hari ini (semua status)
  // revenue_today  = SUM(subtotal) untuk status yang dianggap berhasil
  // pending_today  = jumlah pending hari ini
  $created = $COL['created_at'];
  $status  = $COL['status'];
  $subtot  = $COL['subtotal'];

  // kalau timestamppmu UNIX INT, gunakan DATE(FROM_UNIXTIME($created))
  $dateExpr = "DATE($created)";

  $sqlOrders  = "SELECT COUNT(*) FROM $TABLE WHERE $dateExpr = CURDATE()";
  $sqlRevenue = "SELECT COALESCE(SUM($subtot),0) FROM $TABLE WHERE $dateExpr = CURDATE() AND $status IN ('paid','processed','success')";
  $sqlPending = "SELECT COUNT(*) FROM $TABLE WHERE $dateExpr = CURDATE() AND $status='pending'";

  try {
    if ($isPDO) {
      $orders_today  = (int)$pdo->query($sqlOrders)->fetchColumn();
      $revenue_today = (float)$pdo->query($sqlRevenue)->fetchColumn();
      $pending_today = (int)$pdo->query($sqlPending)->fetchColumn();
      out(['orders_today'=>$orders_today,'revenue_today'=>$revenue_today,'pending_today'=>$pending_today]);
    } elseif ($isMySQLi) {
      $o = $koneksi->query($sqlOrders)->fetch_row();  $o=(int)($o[0]??0);
      $r = $koneksi->query($sqlRevenue)->fetch_row(); $r=(float)($r[0]??0);
      $p = $koneksi->query($sqlPending)->fetch_row(); $p=(int)($p[0]??0);
      out(['orders_today'=>$o,'revenue_today'=>$r,'pending_today'=>$p]);
    } else out(['orders_today'=>0,'revenue_today'=>0,'pending_today'=>0]);
  } catch (Throwable $e) {
    out(['orders_today'=>0,'revenue_today'=>0,'pending_today'=>0]);
  }
}

if ($action === 'export') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=orders.csv');

  $q       = trim($_GET['q'] ?? '');
  $from    = trim($_GET['from'] ?? '');
  $to      = trim($_GET['to'] ?? '');
  $statusF = trim($_GET['status'] ?? '');
  $channelF= trim($_GET['channel'] ?? '');
  if ($statusF  === 'all') $statusF  = '';
  if ($channelF === 'all') $channelF = '';
  $sel = selectClause($COL);

  $w=[]; 
  if ($q!==''){ $w[]="({$COL['order_code']} LIKE :q OR {$COL['buyer_name']} LIKE :q OR {$COL['buyer_email']} LIKE :q OR {$COL['product_name']} LIKE :q)"; $params[':q']='%'.escLike($q).'%'; }
  if ($from!==''){ $w[]="DATE({$COL['created_at']})>=:from"; $params[':from']=$from; }
  if ($to!==''){   $w[]="DATE({$COL['created_at']})<=:to";   $params[':to']=$to; }
  if ($statusF!==''){  $w[]="{$COL['status']}=:status"; $params[':status']=$statusF; }
  if ($channelF!==''){ $w[]="{$COL['payment_channel']}=:ch"; $params[':ch']=$channelF; }

  $sql="SELECT $sel FROM $TABLE";
  if ($w) $sql.=" WHERE ".implode(' AND ',$w);
  $sql.=" ORDER BY {$COL['created_at']} DESC LIMIT 2000";

  $out = fopen('php://output','w');
  fputcsv($out, ['order_code','buyer_name','buyer_email','product_name','qty','unit_price','subtotal','payment_channel','status','created_at','updated_at']);
  if ($isPDO) {
    $st=$pdo->prepare($sql); $st->execute($params ?? []);
    while($r=$st->fetch(PDO::FETCH_ASSOC)){ fputcsv($out,$r); }
  } elseif ($isMySQLi) {
    $res=$koneksi->query($sql);
    if ($res) while($r=$res->fetch_assoc()){ fputcsv($out,$r); }
  }
  fclose($out); exit;
}

out(['error'=>'Unknown action']);
