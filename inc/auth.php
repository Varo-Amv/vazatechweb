<?php
// inc/auth.php
require_once __DIR__.'/session.php';
require_once __DIR__.'/koneksi.php';

/** Login: return array user jika sukses, else null */
function auth_login(string $email, string $password): ?array {
  global $pdo, $koneksi;
  $user = null;

  if (isset($pdo) && $pdo instanceof PDO) {
    $st = $pdo->prepare("SELECT id,nama,email,no_telp,password,role,status FROM users WHERE email = ? LIMIT 1");
    $st->execute([$email]);
    $user = $st->fetch(PDO::FETCH_ASSOC) ?: null;
  } elseif (isset($koneksi) && $koneksi instanceof mysqli) {
    $st = $koneksi->prepare("SELECT id,nama,email,no_telp,password,role,status FROM users WHERE email = ? LIMIT 1");
    $st->bind_param('s', $email);
    $st->execute();
    $res = $st->get_result();
    $user = $res->fetch_assoc() ?: null;
  }

  if (!$user) return null;
  if (($user['status'] ?? 'active') !== 'active') return null;

  if (!password_verify($password, $user['password'])) return null;

  // sukses: simpan session (jangan simpan hash password)
  session_regenerate_id(true);
  $_SESSION['user'] = [
    'id'    => (int)$user['id'],
    'nama'  => $user['nama'],
    'email' => $user['email'],
    'no_telp' => $user['no_telp'],
    'password' => $user['password'],
    'role'  => strtolower($user['role'] ?? 'user'), // admin|staff|user/customer
    'status'=> strtolower($user['status'] ?? 'active'),
  ];

  // update tgl_login
  if (isset($pdo) && $pdo instanceof PDO) {
    $pdo->prepare("UPDATE users SET tgl_login = NOW() WHERE id = ?")->execute([$user['id']]);
  } elseif (isset($koneksi) && $koneksi instanceof mysqli) {
    $st = $koneksi->prepare("UPDATE users SET tgl_login = NOW() WHERE id = ?");
    $st->bind_param('i', $user['id']);
    $st->execute();
  }

  return $_SESSION['user'];
}

function auth_user(): ?array {
  return $_SESSION['user'] ?? null;
}
function auth_logged_in(): bool {
  return !empty($_SESSION['user']);
}
function auth_has_role($roles): bool {
  $u = auth_user();
  if (!$u) return false;
  $roles = is_array($roles) ? $roles : [$roles];
  return in_array($u['role'], $roles, true);
}

/** Guards */
// ... file lain di atas

function need_login() {
    if (empty($_SESSION['user']) || empty($_SESSION['user']['email'])) {
        header("Location: login");
        exit;
    }
}

/**
 * Jika sudah login, redirect dari halaman guest (login/register) ke tujuan.
 * @param string $to URL tujuan kalau sudah login.
 */
function redirect_if_logged_in(string $to = 'index.php'): void {
    if (!empty($_SESSION['user']) && !empty($_SESSION['user']['email'])) {
        header("Location: {$to}");
        exit;
    }
}

function require_login(): void {
  if (!auth_logged_in()) {
    header("Location: /login");
    exit;
  }
}
function sudah_login(): void {
  if (auth_logged_in()) {
    header("Location: index.php");
    exit;
  }
}
function require_role($roles): void {
  require_login();
  if (!auth_has_role($roles)) {
    http_response_code(403);
    echo "<h1>403 Forbidden</h1><p>Akses ditolak.</p>";
    exit;
  }
}
function wajib_login(string $loginPath = '/login'): void {
  require_login($loginPath);
}
function auth_logout(): void {
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
      $params["path"], $params["domain"], $params["secure"], $params["httponly"]
    );
  }
  session_destroy();
}
