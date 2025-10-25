<?php
// /assets/api/revenue_series.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../inc/koneksi.php'; // pastikan membuat $pdo (PDO) atau $koneksi (mysqli)
date_default_timezone_set('Asia/Jakarta');

$days = isset($_GET['days']) ? max(1, (int)$_GET['days']) : 14;

// Deteksi koneksi
$isPDO    = isset($pdo) && $pdo instanceof PDO;
$isMySQLi = isset($koneksi) && $koneksi instanceof mysqli;

function q_all(string $sql) {
  global $pdo, $koneksi, $isPDO, $isMySQLi;
  try {
    if ($isPDO) {
      $st = $pdo->query($sql);
      return $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
    } elseif ($isMySQLi) {
      $out = [];
      if ($res = $koneksi->query($sql)) {
        while ($row = $res->fetch_assoc()) $out[] = $row;
      }
      return $out;
    } else {
      throw new RuntimeException('DB connection not found');
    }
  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error', 'detail' => $e->getMessage()]);
    exit;
  }
}

// Ambil agregasi per tanggal (success saja)
$sql = "
  SELECT 
    DATE(created_at) AS d,
    COUNT(*)                     AS tx,
    COALESCE(SUM(subtotal),0)    AS revenue,
    COALESCE(SUM(qty),0)         AS qty
  FROM revenue
  WHERE created_at >= CURDATE() - INTERVAL ".($days-1)." DAY
    AND status = 'success'
  GROUP BY DATE(created_at)
  ORDER BY DATE(created_at)
";
$rows = q_all($sql);

// Siapkan tanggal lengkap (isi nol jika tidak ada data)
$labels  = [];
$map     = []; // 'YYYY-MM-DD' => row
foreach ($rows as $r) {
  $map[$r['d']] = [
    'tx' => (int)$r['tx'],
    'revenue' => (float)$r['revenue'],
    'qty' => (int)$r['qty'],
  ];
}

for ($i = $days - 1; $i >= 0; $i--) {
  $day = date('Y-m-d', strtotime("-{$i} day"));
  $labels[] = $day;
  if (!isset($map[$day])) {
    $map[$day] = ['tx' => 0, 'revenue' => 0.0, 'qty' => 0];
  }
}

$tx = $revenue = $qty = [];
foreach ($labels as $d) {
  $tx[]      = (int)$map[$d]['tx'];
  $revenue[] = (float)$map[$d]['revenue'];
  $qty[]     = (int)$map[$d]['qty'];
}

// Kembalikan JSON yang dipakai revenue-charts.js
echo json_encode([
  'labels'  => $labels,
  'tx'      => $tx,
  'revenue' => $revenue,
  'qty'     => $qty,
], JSON_UNESCAPED_SLASHES);
