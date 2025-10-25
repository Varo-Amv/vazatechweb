<?php
// refund.php (Kebijakan Pengembalian Dana)
require_once __DIR__ . '/../inc/fungsi.php'; // agar bisa pakai url() jika tersedia

$home        = function_exists('url') ? url('/') : '/';

$privacyUrl  = function_exists('url') ? url('kebijakan-privasi.php') : '/kebijakan-privasi.php';
$tncUrl      = function_exists('url') ? url('snk.php') : '/snk.php';

$privacyUrl  = function_exists('url') ? url('user/kebijakan-privasi') : '/user/kebijakan-privasi';
$tncUrl      = function_exists('url') ? url('user/snk') : '/user/snk';

$contactEmail= 'support@vazatech.store';
$brand       = 'VAZATECH';
$domain      = 'vazatech.store';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Kebijakan Pengembalian Dana • <?= htmlspecialchars($brand) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,follow"><!-- ubah jadi index jika sudah final -->
  <link rel="icon" type="image/png" sizes="32x32" href="../image/logo_nocapt.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/refund.css">
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
          <h1>Kebijakan Pengembalian Dana</h1>
          <div class="meta">Terakhir diperbarui: 22 Oktober 2025 • Berlaku untuk <?= htmlspecialchars($domain) ?></div>
        </div>
      </div>
    </div>

    <div class="card">
      <p>Kebijakan ini menjelaskan ketentuan <b>pengembalian dana</b> (“<b>Refund</b>”) untuk transaksi di platform
        <b><?= htmlspecialchars($brand) ?></b> (<b><?= htmlspecialchars($domain) ?></b>). Dengan melakukan transaksi,
        Anda dianggap telah membaca dan menyetujui kebijakan ini serta <a href="<?= htmlspecialchars($tncUrl) ?>">Syarat &amp; Ketentuan</a> dan
        <a href="<?= htmlspecialchars($privacyUrl) ?>">Kebijakan Privasi</a>.</p>

      <ul class="toc">
        <li><a href="#cakupan">Cakupan</a></li>
        <li><a href="#kriteria">Kriteria Kelayakan</a></li>
        <li><a href="#proses">Proses Pengajuan</a></li>
        <li><a href="#bukti">Bukti yang Diperlukan</a></li>
        <li><a href="#waktu">Waktu Proses & SLA</a></li>
        <li><a href="#metode">Metode Pengembalian</a></li>
        <li><a href="#pengecualian">Pengecualian</a></li>
        <li><a href="#biaya">Biaya & Penyesuaian</a></li>
        <li><a href="#penyalahgunaan">Penyalahgunaan Kebijakan</a></li>
        <li><a href="#perubahan">Perubahan Kebijakan</a></li>
        <li><a href="#kontak">Kontak</a></li>
      </ul>

      <hr>

      <h2 id="cakupan">1. Cakupan</h2>
      <p>Kebijakan ini berlaku untuk pembelian produk/layanan digital (mis. top-up, voucher) yang diproses melalui platform kami.</p>

      <h2 id="kriteria">2. Kriteria Kelayakan</h2>
      <ul>
        <li><b>Pembayaran sukses</b> tetapi <b>pengiriman gagal</b> karena kendala dari sistem kami.</li>
        <li><b>Double charge</b> (terdapat dua kali penagihan untuk transaksi yang sama).</li>
        <li>Kesalahan teknis dari pihak kami yang menyebabkan layanan tidak sesuai pesanan.</li>
        <li>Permohonan diajukan dalam waktu wajar, maksimal <b>3×24 jam</b> sejak transaksi.</li>
      </ul>
      <div class="note">Pengajuan <b>bukan</b> berasal dari kesalahan input pengguna (mis. user ID/server salah) atau gangguan pihak ketiga di luar kendali kami.</div>

      <h2 id="proses">3. Proses Pengajuan</h2>
      <ol>
        <li>Kirimkan pengajuan ke email <a href="mailto:<?= htmlspecialchars($contactEmail) ?>"><?= htmlspecialchars($contactEmail) ?></a> atau melalui menu bantuan pada akun.</li>
        <li>Sertakan: nomor pesanan, tanggal/jam transaksi, metode pembayaran, kronologi singkat, dan bukti pendukung (lihat bagian <a href="#bukti">Bukti</a>).</li>
        <li>Tim kami akan melakukan verifikasi dan menginformasikan hasil (disetujui/ditolak/perlu data tambahan).</li>
      </ol>

      <h2 id="bukti">4. Bukti yang Diperlukan</h2>
      <ul>
        <li>Struk/riwayat transaksi dari penyedia pembayaran (screenshot/email notifikasi).</li>
        <li>Bukti potongan saldo/limit (mutasi rekening/e-wallet).</li>
        <li>Tangkapan layar halaman pesanan di platform kami.</li>
        <li>Dokumen pendukung lain bila diperlukan untuk investigasi.</li>
      </ul>

      <h2 id="waktu">5. Waktu Proses & SLA</h2>
      <ul>
        <li>Peninjauan awal: ± <b>1–3 hari kerja</b> sejak data lengkap diterima.</li>
        <li>Proses pengembalian (jika disetujui): mengikuti kebijakan mitra pembayaran, umumnya <b>3–10 hari kerja</b>.</li>
        <li>Waktu dapat berubah bila diperlukan verifikasi tambahan dari pihak ketiga.</li>
      </ul>

      <h2 id="metode">6. Metode Pengembalian</h2>
      <ul>
        <li>Ke <b>metode pembayaran awal</b> (kartu/e-wallet/transfer) sesuai jalur transaksi.</li>
        <li>Alternatif: <b>saldo akun</b> atau voucher (jika disepakati pengguna dan tersedia).</li>
      </ul>

      <h2 id="pengecualian">7. Pengecualian</h2>
      <ul>
        <li>Kesalahan input dari pengguna (ID/Server/nominal/saldo tidak cukup).</li>
        <li>Layanan sudah berhasil diberikan/ditukarkan.</li>
        <li>Gangguan/maintenance dari pihak ketiga (publisher/game/gateway pembayaran) di luar kendali kami.</li>
        <li>Indikasi penipuan, penyalahgunaan, atau pelanggaran S&amp;K.</li>
      </ul>

      <h2 id="biaya">8. Biaya & Penyesuaian</h2>
      <ul>
        <li>Biaya admin/fee dari penyedia pembayaran dapat <b>tidak dikembalikan</b> sesuai kebijakan masing-masing penyedia.</li>
        <li>Apabila terjadi <i>chargeback</i> dari bank/penyedia pembayaran, kami berhak melakukan penyesuaian saldo/akun.</li>
      </ul>

      <h2 id="penyalahgunaan">9. Penyalahgunaan Kebijakan</h2>
      <p>Kami berhak menolak, menangguhkan akun, dan/atau mengambil tindakan lain yang diperlukan bila ditemukan pola pengajuan yang tidak wajar, manipulatif, atau bertujuan merugikan.</p>

      <h2 id="perubahan">10. Perubahan Kebijakan</h2>
      <p>Kami dapat memperbarui kebijakan ini sewaktu-waktu. Versi terbaru akan ditampilkan di halaman ini beserta tanggal pembaruan.</p>

      <h2 id="kontak">11. Kontak</h2>
      <p>Untuk pengajuan/pertanyaan terkait pengembalian dana, hubungi kami di: <a href="mailto:<?= htmlspecialchars($contactEmail) ?>"><?= htmlspecialchars($contactEmail) ?></a>.</p>

      <hr>
      <p class="muted"><b>Catatan:</b> Kebijakan ini bersifat umum dan mungkin perlu disesuaikan dengan ketentuan operasional & mitra pembayaran.</p>
    </div>

    <p style="text-align:center; margin-top:16px;">
      <a class="btn" href="<?= htmlspecialchars($home) ?>">Kembali ke Beranda</a>
    </p>
  </div>
</body>
</html>
