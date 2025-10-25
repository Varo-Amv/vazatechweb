<?php
session_start();
include_once(__DIR__ . "/inc/koneksi.php");
include_once(__DIR__ . "/inc/fungsi.php");

// Optional: amanin error
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php-error.log');

$msg = "";
$err = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err = "Alamat email tidak valid.";
  } else {
    // cek user ada atau tidak
    $stmt = $koneksi->prepare("SELECT id, nama FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    // Demi keamanan: tetap tampilkan pesan sukses meski email tidak terdaftar
    // agar tidak bocorkan eksistensi email.
    if (!$user) {
      $err = "Email tidak terdaftar.";
    } else {
      // buat token
      $rawToken   = bin2hex(random_bytes(32));      // token yang dikirim ke user
      $tokenHash  = hash('sha256', $rawToken);      // yang disimpan di DB
      $expiresAt  = date('Y-m-d H:i:s', time() + 3600); // 1 jam

      // hapus token lama yang belum dipakai (opsional)
      $del = $koneksi->prepare("DELETE FROM password_resets WHERE email = ? AND used = 0");
      $del->bind_param("s", $email);
      $del->execute();
      $del->close();

      // simpan token baru
      $ins = $koneksi->prepare("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)");
      $ins->bind_param("sss", $email, $tokenHash, $expiresAt);
      $ok = $ins->execute();
      $ins->close();

      if ($ok) {
        // susun reset link
        // SESUAIKAN BASE PATH kamu (contoh /topupweb/)
        $base  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $host  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $link  = $host . $base . "/reset_password.php?token=" . urlencode($rawToken) . "&email=" . urlencode($email);

        // kirim email (sederhana). Di lokal/XAMPP mungkin tidak terkirim—tampilkan link-nya di layar juga.
        $judul_email = "Reset Password • VAZATECH";
    $isi_email   = "
      Hai <b>" . htmlspecialchars($user['nama']) . "</b>,<br><br>
      Akun kamu dengan email <b>" . htmlspecialchars($user['email']) . "</b> meminta link reset password.<br>
      Silakan reset password kamu lewat tautan berikut:<br><br>
      <a href='{$link}' target='_blank' style='display:inline-block;padding:10px 14px;background:#1a73e8;color:#fff;border-radius:8px;text-decoration:none;'>Verifikasi Sekarang</a><br><br>
      Atau salin URL ini ke browser:<br>
      {$link}<br>
      Abaikan email ini jika kamu tidak melakukan reset password.<br><br>
      Terima kasih,<br>VAZATECH
    ";

    kirim_email($email, $user['nama'], $isi_email);
        $msg = "Link reset password berhasil dikirim. Silahkan cek email kamu untuk mengganti password. (Link berlaku hingga 1 jam)<br/>";
      } else {
        $err = "Terjadi kesalahan saat membuat token. Coba lagi.";
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Lupa Password · VAZATECH</title>
  <link rel="icon" type="image/png" sizes="32x32" href="./image/logo_nocapt.png" />
  <link rel="stylesheet" href="./assets/css/login.css" />
  <style>
    .auth-card.outlined{ border:3px solid #2e6bff; }
    .alert { padding:12px 14px; border-radius:10px; margin-top:10px; }
    .alert.ok { background:#0b5; color:#fff; }
    .alert.err{ background:#d33; color:#fff; }
    .btn.block{ width:100%; }
  </style>
</head>
<body>
  <div class="auth-wrap">
    <div class="auth-card outlined">
      <div class="brand">
        <img src="./image/logo_nocapt.png" alt="VAZATECH" class="brand-logo" />
        <span class="logo">V A Z A T E C H</span>
      </div>

      <h1 class="title">LUPA PASSWORD</h1>
      <p class="desc">Masukkan email akun kamu. Kami akan mengirimkan link untuk mengganti password.</p>

      <?php if ($msg): ?><div><?= $msg ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert err"><?= $err ?></div><?php endif; ?>

      <form class="auth-form" method="post" novalidate>
        <label class="field">
          <input type="email" name="email" class="input" placeholder="Email" required />
        </label>
        <button class="btn primary block" type="submit">Kirim Link Reset</button>
      </form>

      <p class="foot">
        Kembali ke <a class="link strong" href="./login">Masuk</a>
      </p>
    </div>
  </div>
</body>
<script>
  function PwRes(){
    window.location.href = $link;
  }
</script>
</html>
