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

if(isset($_POST['simpan'])){
  $email                 = $_POST['email'];
  $nama                  = $_POST['nama'];
  $no_telp               = $_POST['no_telp'];
  $password              = $_POST['password'];
  $password_confirmation = $_POST['password_confirmation'];

  if($email == '' or $nama == '' or $no_telp == '' or $password == '' or $password_confirmation == ''){
    $err .= "<li>Silahkan masukkan semua isian.</li>";
  }

  //cek di bagian db, apakah email sudah ada atau belum
  if($email !=''){
    $sql1   = "select email from users where email = '$email'";
    $q1     = mysqli_query($koneksi,$sql1);
    $n1     = mysqli_num_rows($q1);
    if($n1 > 0){
      $err .= "<li>Email yang kamu masukkan sudah terdaftar.</li>";
    }
  }
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err .= 'Email tidak valid.';
}
  if($password != $password_confirmation){
    $err .= "<li>Password dan Konfirmasi Password tidak sesuai!</li>";
  }
  if(strlen($password) < 8) {
    $err .= "Password harus lebih dari 8 karakter.";
  }
if ($no_telp === '' || !ctype_digit($no_telp)) {
    $err .= 'Nomor telepon harus berisi angka saja.';
}
if (empty($_POST['agree'])) {
  $err .= "<li>Anda harus menyetujui Syarat & Ketentuan.</li>";
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
<?php if($err){echo "<div class='error'><ul>$err</ul></div>";} ?>
<?php if($sukses) {echo "<div class='sukses'>$sukses</div>";} ?>
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
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="./assets/css/login.css" />
    <style>
      .auth-card.outlined {
        border: 3px solid #2e6bff;
      }
      .btn.block {
        width: 100%;
      }
    </style>
  </head>
  <body>
    <div class="auth-wrap">
      <div class="auth-card outlined">
        <div class="brand">
          <img
            src="./image/logo_nocapt.png"
            alt="VAZATECH"
            class="brand-logo"
          />
          <span class="logo">V A Z A T E C H</span>
        </div>

        <h1 class="title">DAFTAR</h1>

        <form class="auth-form" action="#" method="post" novalidate>
          <label class="field">
            <input
              type="name"
              name="nama"
              class="input"
              placeholder="Nama"
              value="<?php echo $nama?>"
              required
            />
          </label>
          <label class="field">
            <input
              type="email"
              name="email"
              class="input"
              placeholder="Email"
              value="<?php echo $email?>"
              required
            />
          </label>

          <label class="field">
            <input
              type="tel"
              name="no_telp"
              class="input"
              placeholder="Nomor telepon"
              pattern="[0-9]{3}-[0-9]{2}-[0-9]{3}"
              value="<?php echo $no_telp?>"
              required
            />
          </label>

          <label class="field">
            <input
              type="password"
              name="password"
              class="input"
              placeholder="Password"
              required
              minlength="8"
            />
          </label>

          <label class="field">
            <input
              type="password"
              name="password_confirmation"
              class="input"
              placeholder="Confirm password"
              required
              minlength="8"
            />
          </label>
<label class="field" style="display:flex; gap:8px; align-items:flex-start">
  <input type="checkbox" name="agree" required style="margin-top:5px">
  <span>Saya telah membaca dan menyetujui <a href="/snk" target="_blank" class="link strong">Syarat & Ketentuan</a> dan
    <a href="/kebijakan-privasi" target="_blank" class="link strong">Kebijakan Privasi</a>.</span>
</label>

          <input class="btn primary block" type="submit" value="Daftar" name="simpan">
          <p class="foot">
            Sudah punya akun?
            <a href="./login" class="link strong">Masuk Disini</a>
          </p>
        </form>
      </div>
    </div>
  </body>
</html>
