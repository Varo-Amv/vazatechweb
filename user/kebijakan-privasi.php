<?php
// kebijakan-privasi.php
require_once __DIR__ . '/../inc/fungsi.php'; // jika ada
$home       = function_exists('url') ? url('/') : '/';
$brand      = 'VAZATECH';
$domain     = 'vazatech.store';
$contact    = 'support@vazatech.store';
$updated_at = '20 Oktober 2025'; // ubah sesuai kebutuhan
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Kebijakan Privasi • <?= htmlspecialchars($brand) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,follow">
  <link rel="icon" type="image/png" sizes="32x32" href="../image/logo_nocapt.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/kbp.css">
</head>
<body>
  <div class="wrap">
    <div class="header">
      <div class="brand">
        <img class="logo-img" src="<?= htmlspecialchars(function_exists('url')?url('image/logo_nocapt.png'):'/image/logo_nocapt.png') ?>" alt="VAZATECH" loading="lazy">
        <div>
          <h1>Kebijakan Privasi</h1>
          <div class="meta">Terakhir diperbarui: <?= htmlspecialchars($updated_at) ?> • Berlaku untuk <?= htmlspecialchars($domain) ?></div>
        </div>
      </div>
    </div>

    <div class="card">
      <p>Kebijakan Privasi ini menjelaskan bagaimana <b><?= htmlspecialchars($brand) ?></b> (“kami”) mengumpulkan, menggunakan, dan melindungi data pribadi Anda saat menggunakan platform di <b><?= htmlspecialchars($domain) ?></b>.</p>

      <ul class="toc">
        <li><a href="#data-dikumpulkan">Data yang Kami Kumpulkan</a></li>
        <li><a href="#cara-penggunaan">Cara Kami Menggunakan Data</a></li>
        <li><a href="#penyimpanan">Penyimpanan & Keamanan</a></li>
        <li><a href="#pihak-ketiga">Berbagi ke Pihak Ketiga</a></li>
        <li><a href="#hak-anda">Hak Anda</a></li>
        <li><a href="#cookie">Cookie & Teknologi Serupa</a></li>
        <li><a href="#kontak">Kontak</a></li>
      </ul>
      <hr>

      <h2 id="data-dikumpulkan">Data yang Kami Kumpulkan</h2>
      <ul>
        <li>Data akun: nama, email, nomor telepon, avatar.</li>
        <li>Data transaksi: produk/top-up, nominal, metode pembayaran, riwayat.</li>
        <li>Data teknis: alamat IP, perangkat, browser, log akses.</li>
      </ul>

      <h2 id="cara-penggunaan">Cara Kami Menggunakan Data</h2>
      <ul>
        <li>Memproses pesanan dan pembayaran.</li>
        <li>Memberikan dukungan pelanggan dan notifikasi transaksi.</li>
        <li>Pencegahan penipuan, audit keamanan, dan peningkatan layanan.</li>
        <li>Pemasaran sah (dengan pilihan berhenti berlangganan).</li>
      </ul>

      <h2 id="penyimpanan">Penyimpanan & Keamanan</h2>
      <p>Data disimpan pada server/penyedia tepercaya dengan kontrol akses ketat. Kata sandi disimpan menggunakan algoritma hashing modern (mis. <code>password_hash</code>).</p>

      <h2 id="pihak-ketiga">Berbagi ke Pihak Ketiga</h2>
      <p>Kami dapat membagikan data yang diperlukan ke mitra pembayaran, penyedia top-up/publisher, layanan email, dan analitik — sebatas untuk menjalankan layanan.</p>

      <h2 id="hak-anda">Hak Anda</h2>
      <ul>
        <li>Mengakses dan memperbarui data akun.</li>
        <li>Meminta penghapusan atau penonaktifan akun sesuai ketentuan.</li>
        <li>Menarik persetujuan komunikasi pemasaran kapan saja.</li>
      </ul>

      <h2 id="cookie">Cookie & Teknologi Serupa</h2>
      <p>Kami menggunakan cookie untuk sesi login, preferensi, dan statistik. Anda dapat mengatur cookie lewat pengaturan browser, namun beberapa fitur mungkin tidak berfungsi.</p>

      <h2 id="kontak">Kontak</h2>
      <p>Pertanyaan terkait privasi: <a href="mailto:<?= htmlspecialchars($contact) ?>"><?= htmlspecialchars($contact) ?></a>.</p>

      <hr>
      <p class="note"><b>Catatan:</b> Ini template umum. Sesuaikan dengan praktik aktual (retensi data, DPO, lokasi server, dasar pemrosesan, dsb.).</p>
    </div>

    <p style="text-align:center;margin-top:16px;">
      <a class="btn" href="<?= htmlspecialchars($home) ?>">Kembali ke Beranda</a>
    </p>
  </div>
</body>
</html>
