<?php
session_start();
include_once(__DIR__ . "/inc/koneksi.php");
include_once(__DIR__ . "/inc/fungsi.php");

// Optional: amanin error
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php-error.log');

$sukses = "";
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
        $emailhost = "mail.vazatech.store";
        $emailsender = "no-reply@vazatech.store";
        $sendername = "VAZATECH";
        $base  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $host  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $link  = $host . $base . "/reset_password.php?token=" . urlencode($rawToken) . "&email=" . urlencode($email);

        // kirim email (sederhana). Di lokal/XAMPP mungkin tidak terkirim—tampilkan link-nya di layar juga.
        $judul_email = "Reset Password";
    $isi_email   = "
      Hai <b>" . htmlspecialchars($user['nama']) . "</b>,<br><br>
      Seseorang mencoba untuk mengganti password. Jika orang tersebut adalah Anda, silakan gunakan link verifikasi di bawah untuk mengonfirmasi identitas Anda. Link verifikasi ini berlaku selama 1 Jam.<br><br>
      <a href='{$link}' target='_blank' style='display:inline-block;padding:10px 14px;background:#1a73e8;color:#fff;border-radius:8px;text-decoration:none;'>Verifikasi Sekarang</a><br><br>
      Atau salin URL ini ke browser:<br>
      {$link}<br><br>
      <a style='color: #f90000ff'>Peringatan:<br>Tidak ada yang dapat menyelesaikan proses ini tanpa Mail ini. Jika ini bukan Anda, mohon abaikan Mail ini untuk mengamankan akun Anda.</a><br><br>
      Terima kasih,<br>VAZATECH
    ";

    kirim_email($emailhost,$emailsender, $sendername, $judul_email, $email, $user['nama'], $isi_email);
        $sukses = "Link reset password berhasil dikirim. Silahkan cek email kamu untuk mengganti password. (Link berlaku hingga 1 jam)<br/>";
      } else {
        $err = "Terjadi kesalahan saat membuat token. Coba lagi.";
      }
    }
  }
}
?>
<?php if($err){echo "<div id='php-error-block' class='error'><ul>$err</ul></div>";} ?>
<?php if($sukses){echo "<div id='php-success-block' class='sukses'>".htmlspecialchars($sukses, ENT_QUOTES, 'UTF-8')."</div>";} ?>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    // ERROR → kumpulkan <li> lalu jadikan <br>
    <?php if(!empty($err)): ?>
      (function(){
        var html = <?php echo json_encode("<ul>$err</ul>", JSON_UNESCAPED_UNICODE); ?>;
        var tmp = document.createElement('div'); tmp.innerHTML = html;
        var lines = Array.from(tmp.querySelectorAll('li')).map(li => li.textContent.trim()).filter(Boolean);
        var msg = lines.length ? lines.join('<br>') : tmp.textContent.trim();
        notify('error', msg, { duration: 10000 });
        var fb = document.getElementById('php-error-block'); if (fb) fb.style.display = 'none';
      })();
    <?php endif; ?>

    // SUKSES → tampilkan 10 detik
    <?php if(!empty($sukses)): ?>
      (function(){
        var msg = <?php echo json_encode($sukses, JSON_UNESCAPED_UNICODE); ?>;
        notify('success', msg, { duration: 10000 });
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
  <title>Lupa Password · VAZATECH</title>
  <link rel="icon" type="image/png" sizes="32x32" href="./image/logo_nocapt.png" />
  <link rel="stylesheet" href="./assets/css/log.css" />
  <link rel="stylesheet" href="/assets/css/notify.css">
  <script src="/assets/js/notify.js" defer></script>
</head>
<body>
      <div class="wrapper">
      <form action="#" class="" method="post" novalidate>
        <input type="hidden" name="csrf" value="<?=$_SESSION['csrf'] ?? ''?>">
        <h1>Lupa Password</h1>
        <p>Masukkan email Anda untuk meminta link reset password.</p>
        <div class="input-box">
          <input type="email" name="email" placeholder="Email" required />
          <i class="bx bxs-at"></i>
        </div>

            <button class="btn" type="submit">Kirim Link Reset</button>

        <div class="register-link">
          <p>Belum punya akun? <a href="daftar">Daftar</a></p>
        </div>
      </form>
    </div>
</body>
</html>
