<?php
session_start();
include_once(__DIR__ . "/inc/koneksi.php");
include_once(__DIR__ . "/inc/fungsi.php");

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php-error.log');

$loginUrl = url('login');

$err = "";
$sukses = "";

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
        $sukses = "Password berhasil diubah. Mengalihkan anda ke halaman login...";
      } else {
        $err = "Gagal memperbarui password. Coba lagi.";
      }
    }
  }
}
?>
<?php if($err){echo "<div id='php-error-block' class='error'><ul>$err</ul></div>";} ?>
<?php if($sukses){echo "<div id='php-success-block' class='sukses'>".htmlspecialchars($sukses, ENT_QUOTES, 'UTF-8')."</div>";} ?>
<?php if (!empty($sukses)): ?>
<script>
  // Redirect ke halaman login setelah 2 detik
  setTimeout(function () {
    location.href = <?= json_encode($loginUrl) ?>;
  }, 2000);
</script>
<noscript>
  <!-- Fallback bila JS nonaktif -->
  <meta http-equiv="refresh" content="2;url=<?= htmlspecialchars($loginUrl, ENT_QUOTES) ?>">
</noscript>
<?php endif; ?>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    // ERROR → kumpulkan <li> lalu jadikan <br>
    <?php if(!empty($err)): ?>
      (function(){
        var html = <?php echo json_encode("<ul>$err</ul>", JSON_UNESCAPED_UNICODE); ?>;
        var tmp = document.createElement('div'); tmp.innerHTML = html;
        var lines = Array.from(tmp.querySelectorAll('li')).map(li => li.textContent.trim()).filter(Boolean);
        var msg = lines.length ? lines.join('<br>') : tmp.textContent.trim();
        notify('error', msg, { duration: 5000 });
        var fb = document.getElementById('php-error-block'); if (fb) fb.style.display = 'none';
      })();
    <?php endif; ?>

    // SUKSES → tampilkan 10 detik
    <?php if(!empty($sukses)): ?>
      (function(){
        var msg = <?php echo json_encode($sukses, JSON_UNESCAPED_UNICODE); ?>;
        notify('success', msg, { duration: 5000 });
        var fb = document.getElementById('php-success-block'); if (fb) fb.style.display = 'none';
      })();
    <?php endif; ?>
  });
</script>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/png" sizes="32x32" href="./image/logo_nocapt.png" />
  <title>Reset Password · VAZATECH</title>
  <link rel="stylesheet" href="./assets/css/login.css" />
  <link rel="stylesheet" href="./assets/css/log.css" />
    <link
      href="https://cdn.boxicons.com/fonts/basic/boxicons.min.css"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="/assets/css/notify.css">
<script src="/assets/js/notify.js" defer></script>
  </head>
  <body>
    <div class="wrapper">
      <form action="#" class="" method="post" novalidate>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
        <h1>Reset Password</h1>
        <div class="input-box">
          <input type="password" name="password" placeholder="Password Baru" required />
        </div>
        <div class="input-box">
          <input type="password" name="password2" placeholder="Konfirmasi Password" required />
        </div>

        <button class="btn" type="submit">Ubah Password</button>

        <div class="register-link">
          <p>Kembali ke <a href="daftar">Masuk</a></p>
        </div>
      </form>
    </div>
</body>
</html>
