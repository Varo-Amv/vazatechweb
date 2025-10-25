<?php
// snk.php
require_once __DIR__ . '/../inc/fungsi.php'; // agar bisa pakai url() jika tersedia
$home = function_exists('url') ? url('/') : '/';
$privacyUrl = function_exists('url') ? url('kebijakan-privasi.php') : '/kebijakan-privasi.php';
$contactEmail = 'support@vazatech.store';
$brand = 'VAZATECH';
$domain = 'vazatech.store';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Syarat & Ketentuan • <?= htmlspecialchars($brand) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,follow"><!-- buka jadi index jika sudah final -->
  <link rel="icon" type="image/png" sizes="32x32" href="../image/logo_nocapt.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/snk.css">
</head>
<body>
  <div class="wrap">
    <div class="header">
      <div class="brand">
  <img
    class="logo-img"
    src="<?= htmlspecialchars(function_exists('url') ? url('image/logo_nocapt.png') : '/image/logo_nocapt.png') ?>"
    alt="VAZATECH"
    loading="lazy"
    onerror="this.onerror=null;this.src='<?= htmlspecialchars(function_exists('url') ? url('image/logo.png') : '/image/logo.png') ?>';"
  >
  <div>
    <h1>Syarat & Ketentuan</h1>
    <div class="meta">Terakhir diperbarui: 20 Oktober 2025 • Berlaku untuk <?= htmlspecialchars($domain) ?></div>
  </div>
</div>
    </div>

    <div class="card">
      <p>Dokumen Syarat &amp; Ketentuan (“<b>S&amp;K</b>”) ini mengatur tata cara penggunaan layanan situs/web
        <b><?= htmlspecialchars($brand) ?></b> di <b><?= htmlspecialchars($domain) ?></b> (selanjutnya disebut “<b>Platform</b>”).
        Dengan membuat akun, mengakses, atau menggunakan layanan di Platform, Anda menyatakan telah membaca, memahami, dan menyetujui S&amp;K ini.</p>

      <ul class="toc">
        <li><a href="#definisi">1. Definisi</a></li>
        <li><a href="#akun">2. Akun & Keamanan</a></li>
        <li><a href="#layanan">3. Layanan & Transaksi</a></li>
        <li><a href="#pembayaran">4. Pembayaran & Harga</a></li>
        <li><a href="#pengiriman">5. Pengiriman Top-Up</a></li>
        <li><a href="#refund">6. Pengembalian Dana</a></li>
        <li><a href="#larangan">7. Aktivitas Terlarang</a></li>
        <li><a href="#ip">8. Kekayaan Intelektual</a></li>
        <li><a href="#privasi">9. Privasi Data</a></li>
        <li><a href="#tanggung">10. Penafian & Batas Tanggung Jawab</a></li>
        <li><a href="#force">11. Keadaan Kahar</a></li>
        <li><a href="#perubahan">12. Perubahan S&K</a></li>
        <li><a href="#hukum">13. Hukum yang Berlaku & Sengketa</a></li>
        <li><a href="#kontak">14. Kontak</a></li>
      </ul>

      <hr>

      <h2 id="definisi">1. Definisi</h2>
      <p>“<b>Pengguna</b>” adalah individu/badan yang mengakses atau menggunakan Platform. “<b>Transaksi</b>” adalah proses pembelian produk/layanan digital (mis. top-up game, voucher). “<b>Kami</b>” merujuk pada pengelola Platform, yaitu <?= htmlspecialchars($brand) ?>.</p>

      <h2 id="akun">2. Akun & Keamanan</h2>
      <ul>
        <li>Pengguna wajib memberikan data yang benar, akurat, dan terbaru saat registrasi.</li>
        <li>Keamanan kredensial (email, kata sandi, OTP) menjadi tanggung jawab Pengguna. Segera hubungi kami jika terindikasi penyalahgunaan akun.</li>
        <li>Kami berhak membekukan/menutup akun jika terjadi pelanggaran S&amp;K atau aktivitas mencurigakan.</li>
      </ul>

      <h2 id="layanan">3. Layanan & Transaksi</h2>
      <ul>
        <li>Produk yang tersedia (contoh: top-up game, voucher) tercantum pada Platform beserta deskripsi dan ketentuannya.</li>
        <li>Dengan melakukan pemesanan, Pengguna menyetujui harga, biaya, syarat khusus, dan ketersediaan stok/layanan.</li>
        <li>Kami dapat menolak/membatalkan transaksi jika terindikasi fraud, kesalahan harga yang jelas, atau tidak memenuhi ketentuan.</li>
      </ul>

      <h2 id="pembayaran">4. Pembayaran & Harga</h2>
      <ul>
        <li>Metode pembayaran yang didukung tercantum di halaman checkout. Biaya admin (jika ada) akan diinformasikan sebelum pembayaran.</li>
        <li>Harga dapat berubah sewaktu-waktu. Perubahan tidak memengaruhi transaksi yang sudah dibayar.</li>
        <li>Pengguna wajib memastikan saldo/limit mencukupi. Kegagalan pembayaran mengakibatkan pesanan tidak diproses.</li>
      </ul>

      <h2 id="pengiriman">5. Pengiriman Top-Up</h2>
      <ul>
        <li>Top-up/voucher digital diproses setelah pembayaran terkonfirmasi. Estimasi waktu dapat berbeda tergantung sistem pihak ketiga (publisher/game).</li>
        <li>Pengguna wajib mengisi <i>user ID/server</i> atau data lain dengan benar. Kesalahan input dari Pengguna berada di luar tanggung jawab kami.</li>
        <li>Bukti transaksi/riwayat akan tersedia di akun atau dikirim via email.</li>
      </ul>

      <h2 id="refund">6. Pengembalian Dana</h2>
      <div class="note">
        Produk digital umumnya <b>tidak dapat dikembalikan</b>. Namun, kami akan melakukan peninjauan jika: (a) pembayaran sukses tetapi pengiriman gagal karena sistem kami, (b) terjadi pemotongan ganda, (c) terjadi kesalahan dari pihak kami. Proses dapat memerlukan bukti dan waktu verifikasi.
      </div>
      <ul>
        <li>Jika disetujui, pengembalian dana dilakukan ke metode pembayaran awal atau saldo akun (sesuai kebijakan operasional).</li>
        <li>Pengajuan harus dilakukan dalam jangka waktu wajar (mis. 3×24 jam) sejak transaksi dengan melampirkan bukti.</li>
      </ul>

      <h2 id="larangan">7. Aktivitas Terlarang</h2>
      <ul>
        <li>Penggunaan Platform untuk tindakan melanggar hukum, penipuan, pencucian uang, atau pelanggaran hak pihak ketiga dilarang.</li>
        <li>Dilarang melakukan <i>reverse engineering</i>, scraping berlebihan, atau gangguan terhadap infrastruktur Platform.</li>
        <li>Kami berhak mengambil tindakan (pemblokiran akun/transaksi) jika ditemukan pelanggaran.</li>
      </ul>

      <h2 id="ip">8. Kekayaan Intelektual</h2>
      <p>Seluruh logo, merek, antarmuka, kode, dan konten di Platform dilindungi hukum. Anda memperoleh lisensi terbatas untuk menggunakan Platform sesuai S&amp;K, tanpa hak memperbanyak/menjual/memodifikasi di luar izin tertulis.</p>

      <h2 id="privasi">9. Privasi Data</h2>
      <p>Kami memproses data pribadi sesuai Kebijakan Privasi. Silakan baca <a href="<?= htmlspecialchars($privacyUrl) ?>">Kebijakan Privasi</a> untuk detail pengumpulan, penggunaan, dan perlindungan data.</p>

      <h2 id="tanggung">10. Penafian & Batas Tanggung Jawab</h2>
      <ul>
        <li>Platform disediakan “sebagaimana adanya”. Kami tidak menjamin bebas error, bebas gangguan, atau kompatibilitas tertentu.</li>
        <li>Tanggung jawab kami terbatas sebesar nilai transaksi terkait yang benar-benar dibayarkan Pengguna.</li>
        <li>Kami tidak bertanggung jawab atas gangguan layanan dari pihak ketiga (mis. maintenans publisher/game, gateway pembayaran) di luar kendali wajar kami.</li>
      </ul>

      <h2 id="force">11. Keadaan Kahar (<i>Force Majeure</i>)</h2>
      <p>Kami dibebaskan dari kewajiban jika kegagalan/keterlambatan disebabkan kejadian di luar kendali wajar (bencana, kebijakan pemerintah, gangguan jaringan luas, perang, huru-hara, dll.).</p>

      <h2 id="perubahan">12. Perubahan S&K</h2>
      <p>Kami dapat memperbarui S&amp;K ini sewaktu-waktu. Versi terbaru akan ditampilkan di halaman ini dengan tanggal berlaku yang diperbarui. Penggunaan berkelanjutan berarti Anda menyetujui perubahan tersebut.</p>

      <h2 id="hukum">13. Hukum yang Berlaku & Sengketa</h2>
      <p>S&amp;K ini diatur oleh hukum Republik Indonesia. Sengketa yang timbul terlebih dahulu diupayakan penyelesaian secara musyawarah. Jika tidak tercapai, maka diselesaikan sesuai mekanisme penyelesaian sengketa yang berlaku (mis. mediasi/BANI/pengadilan setempat).</p>

      <h2 id="kontak">14. Kontak</h2>
      <p>Untuk pertanyaan atau pengajuan terkait S&amp;K, hubungi kami di: <a href="mailto:<?= htmlspecialchars($contactEmail) ?>"><?= htmlspecialchars($contactEmail) ?></a>.</p>

      <hr>
      <p class="muted"><b>Penafian:</b> Dokumen ini bersifat umum dan mungkin perlu disesuaikan. Pertimbangkan untuk mengkonsultasikan dengan penasihat hukum.</p>
    </div>

    <p style="text-align:center; margin-top:16px;">
      <a class="btn" href="<?= htmlspecialchars($home) ?>">Kembali ke Beranda</a>
    </p>
  </div>
</body>
</html>
