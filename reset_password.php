<?php
session_start();
include_once(__DIR__ . "/inc/koneksi.php");
include_once(__DIR__ . "/inc/fungsi.php");

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php-error.log');

$err = "";
$msg = "";

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['token'] ?? '';
  $email = $_POST['email'] ?? '';
  $p1    = $_POST['password'] ?? '';
  $p2    = $_POST['password2'] ?? '';

  if (strlen($p1) < 6) {
    $err = "Password minimal 6 karakter.";
  } elseif ($p1 !== $p2) {
    $err = "Konfirmasi password tidak cocok.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err = "Data tidak valid.";
  } else {
    $tokenHash = hash('sha256', $token);

    // cari token valid
    $now = date('Y-m-d H:i:s');
    $sql = "SELECT id FROM password_resets
            WHERE email = ? AND token_hash = ? AND used = 0 AND expires_at > ?
            LIMIT 1";
    $st  = $koneksi->prepare($sql);
    $st->bind_param("sss", $email, $tokenHash, $now);
    $st->execute();
    $rs  = $st->get_result();
    $row = $rs->fetch_assoc();
    $st->close();

    if (!$row) {
      $err = "Token tidak valid atau sudah kadaluarsa.";
    } else {
      // update password users -> md5 untuk kompatibel dgn login.php kamu
      $newHash = md5($p1);

      $up = $koneksi->prepare("UPDATE users SET password = ? WHERE email = ? LIMIT 1");
      $up->bind_param("ss", $newHash, $email);
      $ok1 = $up->execute();
      $up->close();

      // tandaikan token used
      $up2 = $koneksi->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
      $up2->bind_param("i", $row['id']);
      $ok2 = $up2->execute();
      $up2->close();

      if ($ok1) {
        $msg = "Password berhasil diubah. Silakan masuk kembali.";
      } else {
        $err = "Gagal memperbarui password. Coba lagi.";
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
  <link rel="icon" type="image/png" sizes="32x32" href="./image/logo_nocapt.png" />
  <title>Reset Password · VAZATECH</title>
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

      <h1 class="title">RESET PASSWORD</h1>

      <?php if ($msg): ?>
        <div class="alert ok"><?= $msg ?></div>
        <p class="foot"><a href="./login" class="link strong">Ke halaman Masuk</a></p>
      <?php else: ?>
        <?php if ($err): ?><div class="alert err"><?= $err ?></div><?php endif; ?>

        <form class="auth-form" method="post" novalidate>
          <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
          <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

          <label class="field">
            <input type="password" name="password" class="input" placeholder="Password baru" required />
          </label>
          <label class="field">
            <input type="password" name="password2" class="input" placeholder="Ulangi password baru" required />
          </label>

          <button class="btn primary block" type="submit">Ubah Password</button>
        </form>

        <p class="foot">
          Kembali ke <a class="link strong" href="./login">Masuk</a>
        </p>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
