<?php
// assets/api/banners.php
require_once __DIR__ . '/../../inc/koneksi.php';
require_once __DIR__ . '/../../inc/fungsi.php';
header('Content-Type: application/json; charset=utf-8');

function out($arr){ echo json_encode($arr); exit; }

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

/** Konfigurasi upload lokal */
$LOCAL_DIR = __DIR__ . '/../../assets/uploads/banners';
$LOCAL_URL = (function_exists('url') ? rtrim(url('assets/uploads/banners'), '/') : '/assets/uploads/banners');

/** (Opsional) Upload ke imgbb */
$USE_IMGBB = true; // ubah ke true jika ingin push ke imgbb
$IMGBB_KEY = $KeyGBB;    // isi API key imgbb

function ensure_dir($dir){ if(!is_dir($dir)) @mkdir($dir,0775,true); }
function clean_str($s){ return trim($s??''); }

/** LIST */
if ($action === 'list') {
  $q = trim($_GET['q'] ?? '');
  $active = $_GET['active'] ?? '';
  $where = " WHERE 1 ";
  $params = [];

  if ($q !== '') {
    $where .= " AND (title LIKE ? OR link_url LIKE ?) ";
    $like = "%$q%"; $params[]=$like; $params[]=$like;
  }
  if ($active !== '' && ($active==='0' || $active==='1')) {
    $where .= " AND is_active=? "; $params[] = (int)$active;
  }

  $sql = "SELECT id,title,image_url,link_url,sort,is_active,
                 DATE_FORMAT(updated_at,'%Y-%m-%d %H:%i:%s') AS updated_at
          FROM home_banners {$where}
          ORDER BY sort ASC, id ASC";

  if (isset($pdo)) {
    $st=$pdo->prepare($sql); $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    // KPI
    $kActive = $pdo->query("SELECT COUNT(*) FROM home_banners WHERE is_active=1")->fetchColumn();
    $kTotal  = $pdo->query("SELECT COUNT(*) FROM home_banners")->fetchColumn();
  } else {
    global $koneksi;
    $st=$koneksi->prepare($sql);
    if ($params) { $types=str_repeat('s',count($params)); $st->bind_param($types, ...$params); }
    $st->execute(); $res=$st->get_result(); $rows=[];
    while($r=$res->fetch_assoc()){ $rows[]=$r; }
    $kActive = $koneksi->query("SELECT COUNT(*) AS c FROM home_banners WHERE is_active=1")->fetch_assoc()['c'] ?? 0;
    $kTotal  = $koneksi->query("SELECT COUNT(*) AS c FROM home_banners")->fetch_assoc()['c'] ?? 0;
  }
  out(['rows'=>$rows, 'kpi'=>['active'=>(int)$kActive, 'total'=>(int)$kTotal]]);
}

/** SHOW */
if ($action === 'show') {
  $id = (int)($_GET['id'] ?? 0);
  if (!$id) out([]);
  $sql = "SELECT id,title,image_url,link_url,sort,is_active,
                 DATE_FORMAT(updated_at,'%Y-%m-%d %H:%i:%s') AS updated_at
          FROM home_banners WHERE id=?";
  if (isset($pdo)) {
    $st=$pdo->prepare($sql); $st->execute([$id]); out($st->fetch(PDO::FETCH_ASSOC) ?: []);
  } else {
    global $koneksi; $st=$koneksi->prepare($sql); $st->bind_param('i',$id); $st->execute();
    $res=$st->get_result(); out($res->fetch_assoc() ?: []);
  }
}

/** helper upload ke imgbb */
function upload_to_imgbb_php($path, $filename, $key){
  $image = base64_encode(file_get_contents($path));
  $ch = curl_init('https://api.imgbb.com/1/upload?key='.urlencode($key));
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => ['image' => $image, 'name' => $filename]
  ]);
  $resp = curl_exec($ch); $err = curl_error($ch); curl_close($ch);
  if ($err) return [false, $err];
  $json = json_decode($resp, true);
  if (!($json['success'] ?? false)) return [false, 'Upload failed'];
  return [true, $json['data']['url'] ?? ''];
}

/** CREATE / UPDATE */
if ($action==='create' || $action==='update') {
  $id        = (int)($_POST['id'] ?? 0);
  $title     = clean_str($_POST['title'] ?? '');
  $link_url  = clean_str($_POST['link_url'] ?? '');
  $sort      = (int)($_POST['sort'] ?? 0);
  $is_active = (int)($_POST['is_active'] ?? 1);

  $image_url = clean_str($_POST['image_url'] ?? '');

  // Jika ada file diunggah -> simpan lokal / imgbb
  if (!empty($_FILES['image_file']['tmp_name'])) {
    if ($USE_IMGBB && !empty($IMGBB_KEY)) {
      [$ok, $url] = upload_to_imgbb_php($_FILES['image_file']['tmp_name'], $_FILES['image_file']['name'], $IMGBB_KEY);
      if ($ok) $image_url = $url;
    } else {
      ensure_dir($LOCAL_DIR);
      $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION)) ?: 'jpg';
      $fname = date('YmdHis').'_'.bin2hex(random_bytes(4)).'.'.$ext;
      $dest = rtrim($LOCAL_DIR,'/').'/'.$fname;
      if (move_uploaded_file($_FILES['image_file']['tmp_name'], $dest)) {
        $image_url = rtrim($LOCAL_URL,'/').'/'.$fname;
      }
    }
  }

  if ($action==='create') {
    $sql = "INSERT INTO home_banners (title,image_url,link_url,sort,is_active,created_at,updated_at)
            VALUES (?,?,?,?,?,NOW(),NOW())";
    $params = [$title,$image_url,$link_url,$sort,$is_active];
  } else {
    if (!$id) out(['ok'=>false,'err'=>'id required']);
    $sql = "UPDATE home_banners
            SET title=?, image_url=?, link_url=?, sort=?, is_active=?, updated_at=NOW()
            WHERE id=?";
    $params = [$title,$image_url,$link_url,$sort,$is_active,$id];
  }

  if (isset($pdo)) {
    $st=$pdo->prepare($sql); $ok=$st->execute($params); out(['ok'=>$ok]);
  } else {
    global $koneksi; $st=$koneksi->prepare($sql);
    if ($action==='create') { $st->bind_param('sssii', $title,$image_url,$link_url,$sort,$is_active); }
    else { $st->bind_param('sssiii', $title,$image_url,$link_url,$sort,$is_active,$id); }
    $ok=$st->execute(); out(['ok'=>$ok]);
  }
}

/** DELETE */
if ($action==='delete') {
  $id = (int)($_POST['id'] ?? 0);
  if (!$id) out(['ok'=>false,'err'=>'id required']);
  if (isset($pdo)) {
    $st=$pdo->prepare("DELETE FROM home_banners WHERE id=?"); $ok=$st->execute([$id]); out(['ok'=>$ok]);
  } else {
    global $koneksi; $st=$koneksi->prepare("DELETE FROM home_banners WHERE id=?"); $st->bind_param('i',$id); $ok=$st->execute(); out(['ok'=>$ok]);
  }
}

out(['ok'=>false,'err'=>'unknown action']);
