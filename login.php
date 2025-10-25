<?php
include_once("inc/koneksi.php");
include_once("inc/fungsi.php"); 
require_once __DIR__ . '/inc/auth.php';
redirect_if_logged_in( (function_exists('url') ? url() : '.') . 'index.php' );
// ==== SESSION aman ====
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

<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/png" sizes="32x32" href="./image/logo_nocapt.png" />
    <title>Masuk · VAZATECH</title>
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

        <h1 class="title">MASUK</h1>

        <form class="auth-form" action="#" method="post" novalidate>
  <input type="hidden" name="csrf" value="<?=$_SESSION['csrf'] ?? ''?>">
  <!-- ... input email & password ... -->
          <label class="field">
            <input
              type="email"
              name="email"
              class="input"
              placeholder="Email"
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
            />
            <button
              type="button"
              class="toggle"
              aria-label="Tampilkan password"
              onclick="togglePwd(this)"
            >
              <svg
                viewBox="0 0 24 24"
                width="20"
                height="20"
                fill="none"
                stroke="currentColor"
                stroke-width="1.6"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
            </button>
          </label>

          <div class="row between">
            <a href="forgot_password" class="link small">Lupa Password?</a>
          </div>

          <input class="btn primary" type="submit" value="Masuk" name="masuk">

          <div class="divider"><span>Atau masuk dengan</span></div>

          <div class="oauth">
            <button class="btn ghost">
              <img src="./image/google.png" height="30px" width="30px" />
            </button>
            <button class="btn ghost">
              <img src="./image/facebook.png" height="35px" width="35px" />
            </button>
          </div>

          <p class="foot">
            Belum punya akun?
            <a href="./daftar" class="link strong">Buat Disini</a>
          </p>
        </form>
      </div>
    </div>

    <script>
      function togglePwd(btn) {
        const input = btn.parentElement.querySelector("input");
        const is = input.type === "password";
        input.type = is ? "text" : "password";
        btn.setAttribute(
          "aria-label",
          is ? "Sembunyikan password" : "Tampilkan password"
        );
      }
    </script>
  </body>
</html>
