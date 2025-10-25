<?php
/**
 * footer.php — VAZATECH Footer Component
 * Include di akhir halaman:  <?php include __DIR__ . '/footer.php'; ?>
 * Catatan:
 * - Logo & ikon pakai path contoh (./assets/img/...). Silakan ganti sesuai file di proyekmu.
 * - CSS disuntikkan sekali (guard VZ_FOOTER_CSS) agar tidak dobel saat banyak include.
 */

if (!defined('VZ_FOOTER_CSS')):
  define('VZ_FOOTER_CSS', true);
?>
<style>
  .vz-footer{background:#fff;color:#0b0f14;border-top:1px solid #e5e7eb;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}
  .vz-footer .container{max-width:1200px;margin:0 auto;padding:22px 16px}
  .vz-footer__grid{display:grid;grid-template-columns:1.2fr 1fr 1.2fr 1fr;gap:28px;align-items:flex-start}
  .vz-brand{display:flex;align-items:center;gap:10px;font-weight:800;letter-spacing:.4px}
  .vz-brand img{height:28px;width:auto;display:block}
  .vz-brand .name{font-size:20px}
  .vz-col h4{margin:0 0 12px;font-size:18px;color:#1e63e9}
  .vz-links, .vz-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px}
  .vz-links a{color:#0b0f14;text-decoration:none}
  .vz-links a:hover{text-decoration:underline}
  .vz-social{display:flex;gap:12px;align-items:center}
  .vz-social a{width:30px;height:30px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;border:1px solid #d1d5db;background:#fff}
  .vz-social img{width:18px;height:18px;display:block}
  .vz-pay{display:flex;flex-wrap:wrap;gap:14px 22px;align-items:center}
  .vz-pay img{height:28px;width:auto;display:block;filter:none}
  .vz-footer__bottom{margin-top:16px;padding-top:10px;font-size:12px;color:#64748b}
  @media (max-width: 980px){ .vz-footer__grid{grid-template-columns:1fr 1fr} }
  @media (max-width: 560px){ .vz-footer__grid{grid-template-columns:1fr} .vz-footer .container{padding:20px 14px} }
</style>
<link rel="stylesheet" href="./assets/css/user.css" />
<?php endif; ?>

<footer class="vz-footer">
  <div class="container">
    <div class="vz-footer__grid">
      <!-- Brand -->
      <div class="vz-col">
        <div class="logo">
          <img src="./image/logo_nocapt.png" alt="VAZATECH">
          <span class="name">VAZATECH</span>
        </div>
      </div>

      <!-- Kontak Kami -->
      <div class="vz-col">
        <h4>Kontak Kami</h4>
        <div class="vz-social">
          <a href="https://instagram.com/vazatech" aria-label="Instagram" target="_blank"><img src="./image/instagram.png" alt="Instagram"></a>
          <a href="https://www.tiktok.com/@vazatech" aria-label="TikTok" target="_blank"><img src="./image/tiktok.png" alt="TikTok"></a>
          <a href="mailto:support@vazatech.store" aria-label="Email" target="_blank"><img src="./image/gmail.png" alt="Email"></a>
          <a href="https://wa.me/62895402427731" aria-label="WhatsApp" target="_blank"><img src="./image/whatsapp.png" alt="WhatsApp"></a>
        </div>

        <div style="height:14px"></div>

        <h4>Tentang</h4>
        <ul class="vz-links">
          <li><a href="./snk">Syarat &amp; Ketentuan</a></li>
          <li><a href="./kebijakan-privasi">Kebijakan Privasi</a></li>
          <li><a href="./refund-policy">Kebijakan Pengembalian Dana</a></li>
          <li><a href="./blog">Blog</a></li>
        </ul>
      </div>

      <!-- Metode Pembayaran -->
      <div class="vz-col">
        <h4>Metode Pembayaran</h4>
        <div class="vz-pay">
          <img src="./image/payments/qris.png" alt="QRIS">
          <img src="./image/payments/bca.png" alt="BCA">
          <img src="./image/payments/dana.png" alt="DANA">
          <img src="./image/payments/sopay.png" alt="ShopeePay">
          <img src="./image/payments/gopay.png" alt="GoPay">
          <img src="./image/payments/jago.png" alt="Jago">
          <img src="./image/payments/ovo.png" alt="OVO">
          <img src="./image/payments/seabank.png" alt="SeaBank">
        </div>
      </div>

      <!-- Pergi Ke -->
      <div class="vz-col">
        <h4>Pergi Ke</h4>
        <ul class="vz-links">
          <li><a href="./index.php">Home</a></li>
          <li><a href="./promo">Promo</a></li>
          <li><a href="./cart">Keranjang</a></li>
          <li><a href="./transaksi">Transaksi</a></li>
          <li><a href="./notifikasi">Notifikasi</a></li>
        </ul>
      </div>
    </div>

    <div class="vz-footer__bottom">
      Copyright &copy; <?php echo date('Y'); ?> VAZATECH. All Rights Reserved.
    </div>
  </div>
</footer>
