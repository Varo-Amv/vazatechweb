<?php
include_once("inc/koneksi.php");
include_once("inc/fungsi.php");
require_once __DIR__ . '/inc/auth.php';
redirect_if_logged_in( (function_exists('url') ? url() : '.') . 'index.php' );
?>
<style>
  .error {
    padding: 20px;
    background-color: #f44336;
    color: #FFFFFF;
    margin-bottom: 15px;
  }

    .sukses {
    padding: 20px;
    background-color: #2196F3;
    color: #FFFFFF;
    margin-bottom: 15px;
  }
</style>

<?php
$email      = "";
$nama       = "";
$no_telp    = "";
$err        = "";
$sukses     = "";

function add_err(&$err, $msg){
  // pastikan selalu berformat <li>...</li>
  $err .= "<li>".htmlspecialchars($msg, ENT_QUOTES, 'UTF-8')."</li>";
}

if(isset($_POST['simpan'])){
  $email                 = $_POST['email'];
  $nama                  = $_POST['nama'];
  $no_telp               = $_POST['no_telp'];
  $password              = $_POST['password'];
  $password_confirmation = $_POST['password_confirmation'];

  if($email == '' or $nama == '' or $no_telp == '' or $password == '' or $password_confirmation == ''){
    add_err($err, "Silahkan masukkan semua isian.");
  }

  if($email !=''){
    // (opsional) gunakan prepared statement agar aman dari SQL injection
    $stmt = $koneksi->prepare("SELECT email FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if($stmt->num_rows > 0){
      add_err($err, "Email yang kamu masukkan sudah terdaftar.");
    }
    $stmt->close();
  }

  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    add_err($err, "Email tidak valid.");
  }
  if($password != $password_confirmation){
    add_err($err, "Password dan Konfirmasi Password tidak sesuai!");
  }
  if(strlen($password) < 8) {
    add_err($err, "Password harus lebih dari 8 karakter.");
  }
  if ($no_telp === '' || !ctype_digit($no_telp)) {
    add_err($err, "Nomor telepon harus berisi angka saja.");
  }
  if (empty($_POST['agree'])) {
    add_err($err, "Anda harus menyetujui Syarat & Ketentuan.");
  }

  if(empty($err)){
    $status = bin2hex(random_bytes(16));

    // Kirim email verifikasi
    $judul_email = "Verifikasi Email • VAZATECH";
    $verifLink   = url("/verifikasi.php?email=" . urlencode($email) . "&kode=" . urlencode($status));
    $isi_email   = "
      Hai <b>" . htmlspecialchars($nama) . "</b>,<br><br>
      Akun kamu dengan email <b>" . htmlspecialchars($email) . "</b> hampir siap digunakan.<br>
      Silakan verifikasi email kamu lewat tautan berikut:<br><br>
      <a href='{$verifLink}' target='_blank' style='display:inline-block;padding:10px 14px;background:#1a73e8;color:#fff;border-radius:8px;text-decoration:none;'>Verifikasi Sekarang</a><br><br>
      Atau salin URL ini ke browser:<br>
      {$verifLink}<br>
      Abaikan email ini jika kamu tidak melakukan pendaftaran.<br><br>
      Terima kasih,<br>VAZATECH
    ";

    $send = kirim_email($email, $nama, $isi_email);
    if (!$send['ok']) {
      // boleh lanjut simpan user, tapi beri tahu bahwa email gagal terkirim
       $err .= "<li>Gagal mengirim email verifikasi: ".htmlspecialchars($send['err'])."</li>";
       return;
    }
    //simpan data ke database
      $hash = md5($password);
      $role = 'user';

      $ins = $koneksi->prepare("INSERT INTO users (nama,email,no_telp,password,role,status) VALUES (?,?,?,?,?,?)");
      $ins->bind_param("ssssss", $nama, $email, $no_telp, $hash, $role, $status);
      if ($ins->execute()) {
        $sukses = "Daftar berhasil. Silakan cek email kamu untuk verifikasi.";
        $email = $nama = $no_telp = "";
      } else {
        $err .= "<li>Gagal menyimpan data. Coba lagi.</li>";
      }
}
}
?>
<link rel="stylesheet" href="/assets/css/notify.css">
<script src="/assets/js/notify.js" defer></script>

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
    <title>Daftar · VAZATECH</title>
    <link rel="icon" type="image/png" sizes="32x32" href="./image/logo_nocapt.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
      href="https://cdn.boxicons.com/fonts/basic/boxicons.min.css"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="./assets/css/log.css" />
    <style>
      body {
  margin: 0;
  padding: 0;
  background: #06011cff;
}
      .auth-card.outlined {
        border: 3px solid #2e6bff;
      }
      .btn.block {
        width: 100%;
      }
      .link.strong {
  font-weight: 700;
  color: #2563eb;
}
.wrapper {
    width: min(96vw, 520px);
    padding: 28px 30px 26px;
}
.wrapper .input-box {
    margin: 8px 0;
    border-radius: 15px;
}
@media (max-width: 680px) {
  .wrapper .input-box {
    margin: 18px 0;
  }
}
    </style>
  </head>
  <body>
<div class="wrapper">
      <form action="#" class="" method="post" novalidate>
        <h1>Daftar</h1>
        <div class="input-box">
          <input type="name" name="nama" placeholder="Nama" value="<?php echo $nama?>" required />
          <i class="bx bxs-user"></i>
        </div>
        <div class="input-box">
          <input type="email" name="email" placeholder="Email" value="<?php echo $email?>" required />
          <i class="bx bxs-at"></i>
        </div>
        <div class="input-box">
          <input type="tel" name="no_telp" placeholder="Nomor Telepon" pattern="[0-9]{3}-[0-9]{2}-[0-9]{3}" value="<?php echo $no_telp?>" required />
          <i class="bx bxs-phone"></i>
        </div>
        <div class="input-box">
          <input type="password" name="password" placeholder="Password" required minlength="8" />
          <i class="bx bxs-lock"></i>
        </div>
        <div class="input-box">
          <input type="password" name="password_confirmation" placeholder="Konfirmasi Password" required minlength="8" />
          <i class="bx bxs-lock"></i>
        </div>
<label class="field" style="display:flex; gap:8px; align-items:flex-start ; margin-bottom:15px;">
  <input type="checkbox" name="agree" required style="margin-top:5px">
  <span>Saya telah membaca dan menyetujui <a href="/snk" target="_blank" class="link strong">Syarat & Ketentuan</a> dan
    <a href="/kebijakan-privasi" target="_blank" class="link strong">Kebijakan Privasi</a>.</span>
</label>

        <input class="btn" type="submit" value="Daftar" name="simpan">

        <div class="register-link">
          <p>Sudah punya akun? <a href="login">Masuk</a></p>
        </div>
      </form>
    </div>
  </body>
</html>
