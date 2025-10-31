<?php
// /assets/api/search_stocks.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../inc/koneksi.php';

$q = trim($_GET['q'] ?? '');
$limit = 10;

$out = [];
if ($q !== '') {
  $like = '%' . $q . '%';
  $sql = "SELECT id, product_name, game, image_url, price, status
          FROM stocks
          WHERE COALESCE(status,'') <> 'out'
            AND (product_name LIKE ? OR game LIKE ? OR category LIKE ?)
          ORDER BY updated_at DESC
          LIMIT ?";

  if (isset($pdo) && $pdo instanceof PDO) {
    $st = $pdo->prepare($sql);
    $st->execute([$like,$like,$like,$limit]);
    $out = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } elseif (isset($koneksi) && $koneksi instanceof mysqli) {
    $st = $koneksi->prepare($sql);
    $st->bind_param('sssi', $like,$like,$like,$limit);
    $st->execute();
    $res = $st->get_result();
    while ($r = $res->fetch_assoc()) { $out[] = $r; }
    $st->close();
  }
}

echo json_encode(['items' => $out], JSON_UNESCAPED_UNICODE);
