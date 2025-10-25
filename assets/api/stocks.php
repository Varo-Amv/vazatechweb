<?php
declare(strict_types=1);

// === Header JSON & jangan keluarkan warning ke output ===
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');  // sembunyikan warning/notice di output
error_reporting(E_ALL);

// === Koneksi DB ===
// Pastikan file ini membuat $pdo (PDO) atau $koneksi (mysqli)
require_once __DIR__ . '/../../inc/koneksi.php';

// Normalisasi handle + deteksi tipe koneksi
$pdo     = $pdo     ?? null;
$koneksi = $koneksi ?? null;

$isPDO    = ($pdo     instanceof PDO);
$isMySQLi = ($koneksi instanceof mysqli);

// === Helper DB generik (PDO / mysqli) ===
function db_all(string $sql, array $params = []): array {
  global $pdo, $koneksi, $isPDO, $isMySQLi;

  try {
    if ($isPDO) {
      if ($params) {
        $st = $pdo->prepare($sql);
        $st->execute($params);
      } else {
        $st = $pdo->query($sql);
      }
      return $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    if ($isMySQLi) {
      // simple param binding untuk mysqli: ganti :name di SQL dengan nilai yang sudah di-escape
      if ($params) {
        foreach ($params as $k => $v) {
          $v = $koneksi->real_escape_string((string)$v);
          $sql = str_replace($k, "'" . $v . "'", $sql);
        }
      }
      $res = $koneksi->query($sql);
      if (!$res) return [];
      $out = [];
      while ($row = $res->fetch_assoc()) $out[] = $row;
      return $out;
    }
  } catch (Throwable $e) {
    error_log('db_all: ' . $e->getMessage());
  }
  return [];
}

function db_row(string $sql, array $params = []): array {
  $rows = db_all($sql, $params);
  return $rows[0] ?? [];
}

function db_exec(string $sql, array $params = []): bool {
  global $pdo, $koneksi, $isPDO, $isMySQLi;

  try {
    if ($isPDO) {
      $st = $pdo->prepare($sql);
      return $st->execute($params);
    }

    if ($isMySQLi) {
      if ($params) {
        foreach ($params as $k => $v) {
          $v = $koneksi->real_escape_string((string)$v);
          $sql = str_replace($k, "'" . $v . "'", $sql);
        }
      }
      return (bool)$koneksi->query($sql);
    }
  } catch (Throwable $e) {
    error_log('db_exec: ' . $e->getMessage());
  }
  return false;
}

function json_ok($data) {
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}
function json_err(string $msg, int $code = 400) {
  http_response_code($code);
  echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

// === Util ===
function compute_status(int $stock, int $min): string {
  if ($stock <= 0)      return 'out';
  if ($stock <= $min)   return 'low';
  return 'in';
}



function jdie($ok, $data=null){ echo json_encode($data ?? ['ok'=>$ok]); exit; }
function str($k){ return trim($_POST[$k] ?? ''); }
function g($k){ return $_GET[$k] ?? ''; }

// === Router aksi ===
$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

if ($action === 'list') {
  $q      = trim((string)($_GET['q'] ?? ''));
  $game   = trim((string)($_GET['game'] ?? ''));
  $status = trim((string)($_GET['status'] ?? ''));


  $sql = "SELECT id, product_name, game, category, image_url, currency, price, stock, min_stock, status, updated_at
          FROM stocks
          WHERE 1";

  $sql = "SELECT id, product_name, game, category, image_url, currency, price, stock, min_stock, status,
                 DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i:%s') AS updated_at
          FROM stocks WHERE 1";

  $p = [];

  if ($q !== '') {
    $sql .= " AND (product_name LIKE :q OR game LIKE :q OR currency LIKE :q)";
    $p[':q'] = "%{$q}%";
  }
  if ($game !== '') {
    $sql .= " AND game = :game";
    $p[':game'] = $game;
  }
  if ($status !== '') {
    $sql .= " AND status = :status";
    $p[':status'] = $status;
  }
  $sql .= " ORDER BY updated_at DESC, product_name ASC LIMIT 500";

  json_ok(db_all($sql, $p));


    if (isset($pdo)) {
    $st = $pdo->prepare($sql); $st->execute($p); echo json_encode($st->fetchAll(PDO::FETCH_ASSOC)); exit;
  } else {
    $db = $koneksi; $st = $db->prepare($sql);
    if ($p) {
      $types = str_repeat('s', count($p));
      $st->bind_param($types, ...$p);
    }
    $st->execute(); $res = $st->get_result();
    $rows = []; while($r=$res->fetch_assoc()){ $rows[]=$r; }
    echo json_encode($rows); exit;
  }

}

if ($action === 'show') {
  $id = (int)($_GET['id'] ?? 0);

  if ($id <= 0) json_err('invalid id');

  $row = db_row("SELECT id, product_name, game, category, image_url, currency, price, stock, min_stock, status, updated_at
                 FROM stocks WHERE id = :id", [':id' => $id]);
  if (!$row) json_err('not found', 404);
  json_ok($row);
}

if ($action === 'create' || $action === 'update') {
  $id           = (int)($_POST['id'] ?? 0);
  $product_name = trim((string)($_POST['product_name'] ?? ''));
  $game         = trim((string)($_POST['game'] ?? ''));
  $category     = trim((string)($_POST['category'] ?? ''));
  $image_url    = trim((string)($_POST['image_url'] ?? ''));
  $currency     = trim((string)($_POST['currency'] ?? ''));
  $price        = (int)($_POST['price']  ?? 0);
  $stock        = (int)($_POST['stock']  ?? 0);
  $min_stock    = (int)($_POST['min_stock'] ?? 0);

  if ($product_name === '' || $game === '' || $currency === '') {
    json_err('Harap lengkapi field wajib.');
  }

  $status = compute_status($stock, $min_stock);

  if ($action === 'create') {
    $ok = db_exec(
      "INSERT INTO stocks (product_name, game, category, image_url, currency, price, stock, min_stock, status, updated_at)
       VALUES (:product_name, :game, :category, :image_url, :currency, :price, :stock, :min_stock, :status, NOW())",
      [
        ':product_name' => $product_name,
        ':game'         => $game,
        ':category'    => $category,
        ':image_url'   => $image_url,
        ':currency'     => $currency,
        ':price'        => $price,
        ':stock'        => $stock,
        ':min_stock'    => $min_stock,
        ':status'       => $status
      ]
    );
    if (!$ok) json_err('gagal insert', 500);
    json_ok(['ok' => true]);
  }

  // update
  if ($id <= 0) json_err('invalid id');
  $ok = db_exec(
    "UPDATE stocks
     SET product_name = :product_name,
         game         = :game,
         category     = :category,
         image_url    = :image_url,
         currency     = :currency,
         price        = :price,
         stock        = :stock,
         min_stock    = :min_stock,
         status       = :status,
         updated_at   = NOW()
     WHERE id = :id",
    [
      ':product_name' => $product_name,
      ':game'         => $game, 
      ':category'    => $category,
      ':image_url'   => $image_url,
      ':currency'     => $currency,
      ':price'        => $price,
      ':stock'        => $stock,
      ':min_stock'    => $min_stock,
      ':status'       => $status,
      ':id'           => $id
    ]
  );
  if (!$ok) json_err('gagal update', 500);
  json_ok(['ok' => true]);

  if (!$id) jdie(false, ['error'=>'id required']);
  $sql = "SELECT id, product_name, game, category, image_url, currency, price, stock, min_stock, status,
                 DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i:%s') AS updated_at
          FROM stocks WHERE id=?";
  if (isset($pdo)) {
    $st=$pdo->prepare($sql); $st->execute([$id]); echo json_encode($st->fetch(PDO::FETCH_ASSOC) ?: []); exit;
  } else {
    $st=$koneksi->prepare($sql); $st->bind_param('i',$id); $st->execute();
    $res=$st->get_result(); echo json_encode($res->fetch_assoc() ?: []); exit;
  }
}

if ($action === 'create' || $action === 'update') {
  $id          = (int)($_POST['id'] ?? 0);
  $product     = str('product_name');
  $game        = str('game');
  $category    = strtolower(trim(str('category'))); // distandarkan lowcase
  $image_url   = str('image_url');
  $currency    = strtoupper(trim(str('currency')));
  $price       = (int)str('price');
  $stock       = (int)str('stock');
  $min_stock   = (int)str('min_stock');

  // hitung status bila tidak diinput manual: in/low/out
  $status = 'in';
  if ($stock <= 0)            $status = 'out';
  else if ($min_stock > 0 && $stock < $min_stock) $status = 'low';

  if ($action === 'create') {
    $sql = "INSERT INTO stocks (product_name, game, category, image_url, currency, price, stock, min_stock, status, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,NOW())";
    $params = [$product,$game,$category,$image_url,$currency,$price,$stock,$min_stock,$status];
  } else {
    if (!$id) jdie(false, ['error'=>'id required']);
    $sql = "UPDATE stocks SET product_name=?, game=?, category=?, image_url=?, currency=?, price=?, stock=?, min_stock=?, status=?, updated_at=NOW()
            WHERE id=?";
    $params = [$product,$game,$category,$image_url,$currency,$price,$stock,$min_stock,$status,$id];
  }

  if (isset($pdo)) {
    $st=$pdo->prepare($sql); $ok=$st->execute($params); jdie($ok,['ok'=>$ok]);
  } else {
    $st=$koneksi->prepare($sql);
    if ($action==='create') { $st->bind_param('sssssiiss', $product,$game,$category,$image_url,$currency,$price,$stock,$min_stock,$status); }
    else { $st->bind_param('sssssiissi', $product,$game,$category,$image_url,$currency,$price,$stock,$min_stock,$status,$id); }
    $ok=$st->execute(); jdie($ok,['ok'=>$ok]);
  }

}

if ($action === 'delete') {
  $id = (int)($_POST['id'] ?? 0);

  if ($id <= 0) json_err('invalid id');
  $ok = db_exec("DELETE FROM stocks WHERE id = :id", [':id' => $id]);
  if (!$ok) json_err('gagal delete', 500);
  json_ok(['ok' => true]);
}

json_err('unknown action', 404); {

  if (!$id) jdie(false, ['error'=>'id required']);
  if (isset($pdo)) {
    $st=$pdo->prepare("DELETE FROM stocks WHERE id=?"); $ok=$st->execute([$id]); jdie($ok,['ok'=>$ok]);
  } else {
    $st=$koneksi->prepare("DELETE FROM stocks WHERE id=?"); $st->bind_param('i',$id); $ok=$st->execute(); jdie($ok,['ok'=>$ok]);
  }
}

jdie(false, ['error'=>'unknown action']);

