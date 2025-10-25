<?php
include_once("inc/koneksi.php");
include_once("inc/fungsi.php");
?>
<link rel="stylesheet" href="./assets/css/verifikasi.css">
<?php
$err = "";
$sukses = "";

$email = $_GET['email'] ?? '';
$kode  = $_GET['kode'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $kode === '') {
  $err = "Data yang diperlukan untuk verifikasi tidak tersedia.";
} else {
  // Ambil user sesuai email
  $stmt = $koneksi->prepare("SELECT id, status FROM users WHERE email = ? LIMIT 1");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();

  if (!$user) {
    $err = "Akun tidak ditemukan.";
  } else {
    // Sudah aktif?
    if ($user['status'] === '' || strcasecmp($user['status'], 'active') === 0) {
      $sukses = "Email kamu sudah terverifikasi. Kamu akan dialihkan ke halaman login...";
    } else if (hash_equals($user['status'], $kode)) {
      // Verifikasi: set 'active' atau kosongkan
      $upd = $koneksi->prepare("UPDATE users SET status='active' WHERE id=?");
      $upd->bind_param("i", $user['id']);
      if ($upd->execute()) {
        $sukses = "Verifikasi Berhasil! Kamu akan dialihkan ke halaman login...";
      } else {
        $err = "Gagal memproses verifikasi. Silakan coba lagi.";
      }
    } else {
      $err = "Kode tidak valid.";
    }
  }
}

// Siapkan URL tujuan
$loginUrl = url('login');

// Jika sukses, kirim header Refresh 3 detik (jika memungkinkan)
if ($sukses && !headers_sent()) {
  header("Refresh: 2; url={$loginUrl}");
}
?>

<?php if($err): ?>
  <div class="vz-alert vz-alert--center" role="alert" aria-live="polite">
    <span class="vz-alert__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 9v4m0 4h.01M12 5a7 7 0 1 0 0 14a7 7 0 0 0 0-14z"></path>
      </svg>
    </span>
    <span class="vz-alert__text"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></span>
    <span class="vz-alert__spacer"></span>
    <a href="<?= htmlspecialchars($loginUrl) ?>" class="vz-btn vz-btn--light">Login</a>
    <button class="vz-alert__close" onclick="this.parentElement.remove()" aria-label="Tutup">&times;</button>
  </div>
<?php endif; ?>

<?php if($sukses): ?>
  <div class="vz-alert vz-alert--center" role="alert" aria-live="polite">
    <span class="vz-alert__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 6L9 17l-5-5"></path>
      </svg>
    </span>
    <span class="vz-alert__text"><?= htmlspecialchars($sukses, ENT_QUOTES, 'UTF-8'); ?></span>
    <span class="vz-alert__spacer"></span>
    <a href="<?= htmlspecialchars($loginUrl) ?>" class="vz-btn vz-btn--light">Login</a>
    <button class="vz-alert__close" onclick="this.parentElement.remove()" aria-label="Tutup">&times;</button>
  </div>

  <!-- Fallback JS auto-redirect 3 detik -->
  <script>
    setTimeout(function(){ location.href = <?= json_encode($loginUrl) ?>; }, 3000);
  </script>
<?php endif; ?>
