<?php
// assets/api/stocks_kpi.php
header('Content-Type: application/json; charset=utf-8');

try {
  // koneksi (sesuaikan path ke koneksi.php)
  require __DIR__ . '/../../inc/koneksi.php';

  // deteksi koneksi yang tersedia
  $pdo_ok    = isset($pdo) && $pdo instanceof PDO;
  $mysqli_ok = isset($koneksi) && $koneksi instanceof mysqli;

  $total = $low = $out = 0;

  if ($pdo_ok) {
    $total = (int)$pdo->query("SELECT COUNT(*) FROM stocks")->fetchColumn();
    $low   = (int)$pdo->query("SELECT COUNT(*) FROM stocks WHERE stock > 0 AND stock <= min_stock")->fetchColumn();
    $out   = (int)$pdo->query("SELECT COUNT(*) FROM stocks WHERE stock <= 0")->fetchColumn();
  } elseif ($mysqli_ok) {
    $r = $koneksi->query("SELECT COUNT(*) FROM stocks");            $total = (int)($r ? $r->fetch_row()[0] : 0);
    $r = $koneksi->query("SELECT COUNT(*) FROM stocks WHERE stock > 0 AND stock <= min_stock");
    $low = (int)($r ? $r->fetch_row()[0] : 0);
    $r = $koneksi->query("SELECT COUNT(*) FROM stocks WHERE stock <= 0");
    $out = (int)($r ? $r->fetch_row()[0] : 0);
  } else {
    throw new Exception('DB connection not found');
  }

  echo json_encode(['total'=>$total, 'low'=>$low, 'out'=>$out], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>'DB error','detail'=>$e->getMessage()]);
}
