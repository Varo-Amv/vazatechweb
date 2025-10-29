<?php
// user/promo.php
// Halaman "Daftar Promo" – ambil data dari tabel home_banners

// Header & koneksi
// Jika di proyekmu header sudah meng-include koneksi, baris require_once bisa dihapus.
include_once("../inc/header.php");
require_once("../inc/koneksi.php");

// Ambil data promo aktif
$sql = "
  SELECT id, image_url, title, link_url
  FROM home_banners
  WHERE COALESCE(is_active, 1) = 1
  ORDER BY 
    CASE WHEN sort IS NULL OR sort = 0 THEN 1 ELSE 0 END, 
    sort ASC,
    updated_at DESC,
    id DESC
";
$result = mysqli_query($koneksi, $sql);
if (!$result) {
  // fallback sederhana bila query gagal
  $error = "Gagal memuat data promo.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Daftar Promo</title>
  <link rel="stylesheet" href="../assets/css/promo.css?v=<?php echo time(); ?>">
</head>
<body>

<main class="promo-page container">
  <h1 class="promo-heading">DAFTAR PROMO</h1>

  <!-- Pills/Filter (placeholder; saat ini hanya 'Semua')
  <div class="pills-scroll promo-pills" role="tablist" aria-label="Filter promo">
    <button type="button" class="pill active" aria-selected="true">Semua</button>
    Tambahkan pill lain di sini kalau nanti dibutuhkan
  </div> -->

  <!-- Kartu promo -->
  <div class="cards-scroll promos-grid">
    <?php if (!empty($error)): ?>
      <div class="empty-state"><?php echo htmlspecialchars($error); ?></div>
    <?php else: ?>
      <?php if (mysqli_num_rows($result) === 0): ?>
        <div class="empty-state">Belum ada promo aktif.</div>
      <?php else: ?>
        <?php while ($row = mysqli_fetch_assoc($result)): 
          $title = htmlspecialchars($row['title'] ?? 'Promo');
          $img   = htmlspecialchars($row['image_url'] ?? '');
          $href  = trim((string)$row['link_url']) !== '' ? htmlspecialchars($row['link_url']) : '#';
          $hasLink = $href !== '#';
        ?>
          <?php if ($hasLink): ?>
            <a class="promo-card" href="<?php echo $href; ?>" target="_blank" rel="noopener">
          <?php else: ?>
            <div class="promo-card">
          <?php endif; ?>

              <div class="promo-image">
                <?php if ($img !== ''): ?>
                  <img src="<?php echo $img; ?>" alt="<?php echo $title; ?>">
                <?php else: ?>
                  <div class="img-placeholder" aria-hidden="true"></div>
                <?php endif; ?>
              </div>
              <div class="promo-title"><?php echo $title; ?></div>

          <?php if ($hasLink): ?>
            </a>
          <?php else: ?>
            </div>
          <?php endif; ?>
        <?php endwhile; ?>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</main>

<?php include_once("../inc/footer.php"); ?>
</body>
</html>
