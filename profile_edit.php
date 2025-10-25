<?php
require __DIR__ . '/inc/session.php';
require __DIR__ . '/inc/koneksi.php';
require __DIR__ . '/inc/fungsi.php';
require __DIR__ . '/inc/auth.php';
require_login();

// ---- CSRF helper ----
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function csrf_check($token) {
  return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

$msg = '';
$ok  = false;

// Ambil user saat ini
$stmt = $koneksi->prepare("SELECT id, nama, email, no_telp, password FROM users WHERE email = ?");
$stmt->bind_param("s", $_SESSION['user']['email']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) { header("Location: logout.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf_token'] ?? '')) {
    $msg = "Sesi kadaluarsa. Muat ulang halaman.";
  } else {
    // Input
    $nama      = trim($_POST['nama'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $no_telp   = trim($_POST['no_telp'] ?? '');
    $pwd_now   = $_POST['password_now'] ?? '';       // wajib utk otorisasi
    $pwd_new   = $_POST['password_new'] ?? '';       // opsional
    $pwd_conf  = $_POST['password_confirm'] ?? '';   // opsional

    // Validasi dasar
    if ($nama === '' || $email === '' || $no_telp === '' || $pwd_now === '') {
      $msg = "Nama, Email, Nomor Telepon, dan Password saat ini wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $msg = "Format email tidak valid.";
    } elseif ($pwd_new !== '' && $pwd_new !== $pwd_conf) {
      $msg = "Konfirmasi password baru tidak sama.";
    } else {
      // Verifikasi password saat ini (mendukung MD5 dan password_hash)
      $stored = $user['password'];
      $pass_ok = false;
      if (preg_match('/^[a-f0-9]{32}$/i', $stored)) {
        // MD5 lama
        $pass_ok = hash_equals(strtolower($stored), md5($pwd_now));
      } else {
        $pass_ok = password_verify($pwd_now, $stored);
      }

      if (!$pass_ok) {
        $msg = "Password saat ini salah.";
      } else {
    // Cek email unik bila berubah
$emailChanged = (strcasecmp($email, $user['email']) !== 0);
if ($emailChanged) {
  $cek = $koneksi->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
  $cek->bind_param("s", $email);
  $cek->execute();
  if ($cek->get_result()->fetch_row()) {
    $msg = "Email sudah digunakan.";
  }
}

if ($msg === '') {
  // Jika user mengganti email, siapkan status verifikasi + kirim email
  $statusCode = null;
 /* if ($emailChanged) {
    $statusCode = md5(random_int(0, 1000000)); // kode verifikasi
    $judul_email = "Verifikasi Email";
    $isi_email  = "Akun kamu dengan email <b>" . htmlspecialchars($email) . "</b> siap digunakan.<br>";
    $isi_email .= "Silakan aktivasi email melalui tautan di bawah ini:<br><br>";
    $isi_email .= base_url()."/verifikasi.php?email=" . urlencode($email) . "&kode=" . urlencode($statusCode);

    // kirim email (fungsi dari inc/fungsi.php)
    // kirim_email($email, $nama, $judul_email, $isi_email) harus sudah tersedia
    kirim_email($email, $nama, $judul_email, $isi_email);
  }*/

  // Siapkan UPDATE
  if ($pwd_new !== '') {
    $newHash = password_hash($pwd_new, PASSWORD_DEFAULT);
    if ($emailChanged) {
      $sql = "UPDATE users SET nama=?, email=?, no_telp=?, password=?, status=? WHERE id=?";
      $up  = $koneksi->prepare($sql);
      $up->bind_param("sssssi", $nama, $email, $no_telp, $newHash, $statusCode, $user['id']);
    } else {
      $sql = "UPDATE users SET nama=?, email=?, no_telp=?, password=? WHERE id=?";
      $up  = $koneksi->prepare($sql);
      $up->bind_param("ssssi", $nama, $email, $no_telp, $newHash, $user['id']);
    }
  } else {
    if ($emailChanged) {
      $sql = "UPDATE users SET nama=?, email=?, no_telp=?, status=? WHERE id=?";
      $up  = $koneksi->prepare($sql);
      $up->bind_param("ssssi", $nama, $email, $no_telp, $statusCode, $user['id']);
    } else {
      $sql = "UPDATE users SET nama=?, email=?, no_telp=? WHERE id=?";
      $up  = $koneksi->prepare($sql);
      $up->bind_param("sssi", $nama, $email, $no_telp, $user['id']);
    }
  }

  if ($up->execute()) {
    // Update session agar konsisten
    $_SESSION['user']['email']   = $email;
    $_SESSION['user']['nama']    = $nama;
    $_SESSION['user']['no_telp'] = $no_telp;

    $ok  = true;
    $msg = $emailChanged
      ? "Profil berhasil diubah. Email diperbarui."
      : "Profil berhasil diperbarui.";

    // Ambil ulang data terbaru untuk isi form
    $stmt = $koneksi->prepare("SELECT id, nama, email, no_telp, password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
  } else {
    $msg = "Gagal menyimpan perubahan. Coba lagi.";
  }
          } else {
            $msg = "Gagal menyimpan perubahan. Coba lagi.";
          }
        }
      }
    }
  }
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="icon" type="image/png" sizes="32x32" href="./image/logo_nocapt.png" />
  <title>Ganti Profil</title>
  <link rel="stylesheet" href="assets/css/profile.css">
</head>
<body>
  <div class="bg-rails"><div class="spacer"></div></div>
  <div class="card" style="max-width:560px;margin:24px auto;">
    <div class="header" style="gap:10px;align-items:center;">
      <a class="btn btn-back" type="button" href="<?= base_url(). "/profile" ?>" title="Kembali">&larr;</a>
      <div class="title">Ganti Profil</div>
    </div>

    <?php if ($msg): ?>
      <div style="background:<?= $ok ? '#e8fff1':'#fff3f3'?>;border:1px solid <?= $ok ? '#b7f0cb':'#ffd4d4'?>;padding:10px 12px;border-radius:8px;margin-bottom:12px;">
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

      <label>Nama</label>
      <input class="input" type="text" name="nama" value="<?= htmlspecialchars($user['nama'] ?? '') ?>" required>

      <label>Alamat Email</label>
      <input class="input" type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly>

      <label>Nomor Telepon</label>
      <input class="input" type="text" name="no_telp" value="<?= htmlspecialchars($user['no_telp'] ?? '') ?>" required>

      <hr style="border:none;height:1px;background:#eee;margin:18px 0;">
      <div style="font-weight:600; margin-bottom:6px;">Keamanan</div>

      <label>Password Saat Ini <small style="font-weight:400;color:#666">(wajib untuk mengubah data)</small></label>
      <input class="input" type="password" name="password_now" required>

      <label>Password Baru <small style="font-weight:400;color:#666">(opsional)</small></label>
      <input class="input" type="password" name="password_new" minlength="6">

      <label>Konfirmasi Password Baru</label>
      <input class="input" type="password" name="password_confirm" minlength="6">

      <div class="actions" style="margin-top:18px">
        <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
      </div>
    </form>
  </div>

  <script>
    function goBack(){
      if (document.referrer && document.referrer !== location.href) history.back();
      else window.location.href = '/profile';
    }
  </script>
</body>
</html>
