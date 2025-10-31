<?php
session_start();
require_once __DIR__ . '/../inc/koneksi.php';
require_once __DIR__ . '/../inc/auth.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // pastikan login untuk semua aksi

  $pid = (int)($_POST['product_id'] ?? 0);
  $qty = max(1, (int)($_POST['qty'] ?? 1));

  if (isset($_POST['add_to_cart'])) {
    // keranjang sederhana berbasis session
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    // gabungkan qty kalau barang sudah ada
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
      if ($item['product_id'] === $pid) {
        $item['qty'] += $qty;
        $found = true;
        break;
      }
    }
    if (!$found) {
      $_SESSION['cart'][] = ['product_id' => $pid, 'qty' => $qty];
    }
    $_SESSION['flash_success'] = 'Produk ditambahkan ke keranjang.';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
  }

  if (isset($_POST['buy'])) {
      require_login();
    // di sini kamu bisa arahkan ke halaman checkout
    $_SESSION['flash_success'] = 'Lanjutkan ke checkout.';
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
  }
}
// Ambil data produk dari DB (tabel stocks)
$stmt = $koneksi->prepare("
    SELECT id, product_name, game, server, category, image_url, currency, price, stock, min_stock, status, updated_at
    FROM stocks
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param('i', $productId);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();
$stmt->close();

// Jika tidak ada id/produk, kamu bisa fallback id=1
if (!$product) {
    // fallback optional
    $fallbackId = 1;
    $stmt = $koneksi->prepare("
        SELECT id, product_name, game, server, category, image_url, currency, price, stock, min_stock, status, updated_at
        FROM stocks
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $fallbackId);
    $stmt->execute();
    $res = $stmt->get_result();
    $product = $res->fetch_assoc();
    $stmt->close();
}

// Helper format rupiah
function rupiah($angka) {
    return 'Rp' . number_format((int)$angka, 0, ',', '.');
}
?>
<?php include("../inc/header.php"); ?> <!-- sesuai instruksi -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Detail</title>

<link href="https://cdn.boxicons.com/fonts/basic/boxicons.min.css" rel="stylesheet" />
<link rel="stylesheet" href="/assets/css/notify.css">
<script src="/assets/js/notify.js" defer></script>
<script src="/assets/js/product-detail.js" defer></script>
<link rel="stylesheet" href="/assets/css/product-detail.css">
</head>
<body>
<div class="pd-wrap">
  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="pd-alert success"><i class='bx bx-check-circle'></i> <?= htmlspecialchars($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); ?>
  <?php endif; ?>

  <?php if (!$product): ?>
    <div class="pd-empty">
      <i class='bx bx-error-circle'></i> Produk tidak ditemukan.
    </div>
  <?php else: ?>
    <div class="pd-grid">
      <!-- Kiri: hero + judul -->
<section class="pd-card pd-left">
  <div class="pd-hero">
    <!-- kiri: gambar -->
    <div class="pd-hero-media">
      <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>">
    </div>

    <!-- kanan: info -->
    <div class="pd-hero-info">
      <h1 class="pd-name"><?= htmlspecialchars($product['product_name']) ?></h1>
      <div class="pd-game"><?= htmlspecialchars($product['game']) ?></div>
      <div class="pd-price-now"><?= rupiah($product['price']) ?></div>
      <!-- kalo mau ditambahin badge <div class="pd-badge"></div> -->
    </div>
  </div>

  <!-- HAPUS/GANTI blok .pd-title lama jika ada -->

  <!-- bawah: hanya game currency & server (biarkan seperti semula) -->
  <div class="pd-selects">
    <label class="pd-label">Game Currency</label>
    <div class="pd-select">
      <span><?= htmlspecialchars($product['currency']) ?></span>
    </div>

    <label class="pd-label">Server</label>
    <div class="pd-select">
      <span><?= htmlspecialchars($product['server']) ?></span>
    </div>
  </div>
</section>


      <!-- Kanan: Informasi Pesanan -->
      <aside class="pd-card pd-right">
        <h2>Informasi Pesanan</h2>

        <form method="post" class="pd-form" id="orderForm" novalidate>
          <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">

          <div class="pd-field-group">
            <label for="user_id">User ID <i class='bx bx-info-circle' title="Masukkan User ID game"></i></label>
            <input type="text" id="user_id" name="user_id" placeholder="Contoh : 123456789" required>
          </div>

          <div class="pd-field-group">
            <label for="zone_id">Zone ID <i class='bx bx-info-circle' title="Masukkan Zone ID game"></i></label>
            <input type="text" id="zone_id" name="zone_id" placeholder="Contoh : 2020" required>
          </div>

          <div class="pd-qty-stock">
            <div class="pd-qty">
              <button type="button" class="pd-qty-btn" data-act="dec"><i class='bx bx-minus'></i></button>
              <input type="number" min="1" value="1" id="qty" name="qty">
              <button type="button" class="pd-qty-btn" data-act="inc"><i class='bx bx-plus'></i></button>
            </div>
            <div class="pd-stock">
              Stok: <span id="stock"><?= (int)$product['stock'] ?></span>
            </div>
          </div>

<div class="pd-total">
  <span>Total</span>
  <strong id="total"><?= rupiah($product['price']) ?></strong>
</div>

<div class="pd-actions">
  <!-- ikon saja -->
  <button class="pd-cart-btn icon-only"
          name="add_to_cart"
          type="submit"
          aria-label="Tambah ke Keranjang"
          title="Tambah ke Keranjang">
    <i class='bx  bxs-cart-plus'  ></i> 
  </button>

  <!-- teks saja -->
  <button class="pd-buy-btn"
          name="buy"
          type="submit">
    Beli Sekarang
  </button>
</div>


<p class="pd-guard"><i class='bx bx-shield'></i> 100% Transaksi Aman</p>

        </form>
      </aside>
    </div>

    <!-- Tabs (tanpa “Informasi Penjual” sesuai permintaan) -->
    <section class="pd-tabs pd-card">
      <div class="pd-tab-headers">
        <button class="active" data-tab="desc">Deskripsi Produk</button>
        <button data-tab="guide">Panduan Aktivasi</button>
      </div>
      <div class="pd-tab-panels">
        <div class="pd-tab-panel active" id="desc">
          <p>Top up <strong><?= htmlspecialchars($product['game']) ?></strong> – paket <strong><?= htmlspecialchars($product['product_name']) ?></strong>.
             Pembelian diproses otomatis, mohon pastikan <em>User ID</em> & <em>Zone ID</em> benar.</p>
          <ul class="pd-bullets">
            <li><i class='bx bx-check'></i> Proses cepat & otomatis</li>
            <li><i class='bx bx-check'></i> Stok real-time</li>
            <li><i class='bx bx-check'></i> Harga transparan</li>
          </ul>
        </div>
        <div class="pd-tab-panel" id="guide">
          <ol>
            <li>Masukkan <strong>User ID</strong> dan <strong>Zone ID</strong>.</li>
            <li>Pilih jumlah dan klik <strong>Beli Sekarang</strong>.</li>
            <li>Selesaikan pembayaran, diamond akan masuk ke akun game kamu.</li>
          </ol>
        </div>
      </div>
    </section>
  <?php endif; ?>
</div>

<script>
  // Data awal untuk JS (harga & stok dari PHP)
  window.PD_DATA = {
    price: <?= (int)$product['price'] ?>,
    stock: <?= (int)$product['stock'] ?>
  };
</script>
<script src="/assets/js/product-detail.js"></script>
  
</body>
</html>
<?php include("../inc/footer.php"); ?> <!-- sesuai instruksi -->
