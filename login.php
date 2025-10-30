<?php
include_once("inc/koneksi.php");
include_once("inc/fungsi.php"); 
require_once __DIR__ . '/inc/auth.php';

// ==== SESSION aman (SEBELUM apa pun yang butuh session) ====
if (session_status() === PHP_SESSION_NONE) {
  $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

// ==== AUTO-LOGIN dari cookie (SEBELUM redirect check) ====
// gunakan kunci yang konsisten: $_SESSION['user']['id']
if (empty($_SESSION['user']['id'])) {
  remember_login_from_cookie($koneksi); // jika valid akan set $_SESSION['user']
}

// ==== Redirect jika sudah login ====
redirect_if_logged_in( (function_exists('url') ? url() : '.') . 'index.php' );

// ==== CSRF helper ====
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function csrf_ok(?string $t): bool {
  return isset($_SESSION['csrf']) && is_string($t) && hash_equals($_SESSION['csrf'], $t);
}

// ==== Error handling aman (log ke file, tidak tampil) ====
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php-error.log');
mysqli_report(MYSQLI_REPORT_OFF);

// ==== Variabel FORM ====
$email    = "";
$password = "";
$err      = "";

// ==== Proses Login ====
if (isset($_POST['masuk'])) {
  $email    = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if (!csrf_ok($_POST['csrf'] ?? '')) {
    $err .= "<li>Sesi tidak valid. Silakan muat ulang halaman lalu coba lagi.</li>";
  }

  if ($email === '' || $password === '') {
    $err .= "<li>Email atau Password belum diisi.</li>";
  }

  if (empty($err)) {
    // cari user by email (prepared statement)
    $user = null;
    if ($stmt = $koneksi->prepare("SELECT id,nama,email,no_telp,password,role,status FROM users WHERE email = ? LIMIT 1")) {
      $stmt->bind_param('s', $email);
      $stmt->execute();
      $res  = $stmt->get_result();
      $user = $res->fetch_assoc() ?: null;
      $stmt->close();
    }

    if (!$user) {
      $err .= "<li>Akun tidak ditemukan.</li>";
    } else {
      // status akun
      if ($user['status'] === 'suspended') {
        $err .= "<li>Akun kamu kena suspend.</li>";
      } elseif ($user['status'] !== 'active') {
        $err .= "<li>Akun kamu belum aktif.</li>";
      }

      // cek password (dukung MD5 lama & Bcrypt baru)
      if (empty($err)) {
        $hash = $user['password'];

        $ok = false;
        if (preg_match('/^\$2y\$/', (string)$hash)) {
          // bcrypt
          $ok = password_verify($password, $hash);
          // rehash jika perlu
          if ($ok && password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $new = password_hash($password, PASSWORD_DEFAULT);
            if ($upd = $koneksi->prepare("UPDATE users SET password=? WHERE id=?")) {
              $upd->bind_param('si', $new, $user['id']);
              $upd->execute();
              $upd->close();
            }
          }
        } else {
          // anggap MD5 legacy
          $ok = (md5($password) === $hash);
          // upgrade ke bcrypt jika cocok
          if ($ok) {
            $new = password_hash($password, PASSWORD_DEFAULT);
            if ($upd = $koneksi->prepare("UPDATE users SET password=? WHERE id=?")) {
              $upd->bind_param('si', $new, $user['id']);
              $upd->execute();
              $upd->close();
            }
          }
        }

        if (!$ok) {
          $err .= "<li>Password tidak sesuai.</li>";
        } else {
          // ingat saya (remember me)
          if (!empty($_POST['remember'])) {
            remember_create($koneksi, (int)$user['id'], 30); // 30 hari
          }
        }
      }
    }
  }

  // Sukses: set session + redirect sesuai role
  if (empty($err) && !empty($user)) {
    session_regenerate_id(true);
    $_SESSION['user'] = [
      'id'    => (int)$user['id'],
      'nama'  => $user['nama'],
      'email' => $user['email'],
      'role'  => strtolower($user['role'] ?? 'user'), // admin|staff|user/customer
    ];

    // update tgl_login
    if ($upd = $koneksi->prepare("UPDATE users SET tgl_login = NOW() WHERE id = ?")) {
      $upd->bind_param('i', $user['id']);
      $upd->execute();
      $upd->close();
    }

    // admin/staff -> /admin/index.php, lainnya -> /index.php
    $role  = $_SESSION['user']['role'];
    $redir = ($role === 'admin' || $role === 'staff') ? 'index.php' : 'index.php';
    header("Location: {$redir}");
    exit;
  }
}
?>

<style>
  .error{padding:20px;background:#f44336;color:#fff;margin-bottom:15px}
  .sukses{padding:20px;background:#2196F3;color:#fff;margin-bottom:15px}
</style>

<?php if($err){ echo "<div class='error'><ul class='pesan'>$err</ul></div>"; } ?>
<?php if(!empty($err)): ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Ambil HTML error list dari PHP dengan aman
    var html = <?php echo json_encode("<ul class='pesan'>$err</ul>", JSON_UNESCAPED_UNICODE); ?>;

    // Ubah <li> jadi baris <br> agar ringkas di toast
    var tmp = document.createElement('div');
    tmp.innerHTML = html;
    var lines = Array.from(tmp.querySelectorAll('li'))
      .map(li => li.textContent.trim())
      .filter(Boolean);
    var msg = lines.length ? lines.join('<br>') : tmp.textContent.trim();

    // Tampilkan toast error 10 dtk close
    if (typeof notify === 'function') {
      notify('error', msg, { duration: 10000 });
      // Sembunyikan fallback agar tidak dobel tampil
      var fb = document.querySelector('.error');
      if (fb) fb.style.display = 'none';
    }
  });
</script>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/png" sizes="32x32" href="./image/logo_nocapt.png" />
    <title>Masuk · VAZATECH</title>
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
        <input type="hidden" name="csrf" value="<?=$_SESSION['csrf'] ?? ''?>">
        <h1>Masuk</h1>
        <div class="input-box">
          <input type="email" name="email" placeholder="Email" required />
          <i class="bx bxs-at"></i>
        </div>
        <div class="input-box">
          <input type="password" name="password" placeholder="Password" required />
          <i class="bx bxs-lock"></i>
        </div>
        <div class="remember-forgot">
          <label><input type="checkbox" name="remember" /> Ingat Saya</label>
          <a href="forgot_password">Lupa Password?</a>
        </div>

        <input type="submit" class="btn" value="Masuk" name="masuk">

        <div class="register-link">
          <p>Belum punya akun? <a href="daftar">Daftar</a></p>
        </div>
      </form>
    </div>
  </body>
</html>
