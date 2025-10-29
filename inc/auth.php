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
  return !empty($_SESSION['user']['id']);
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
// ==== REMEMBER ME (MYSQLI) ====
// Pastikan $koneksi adalah instance mysqli yang sudah terkoneksi.

function remember_create(mysqli $koneksi, int $user_id, int $days = 30): void {
  $selector  = substr(random_str(24), 0, 12);
  $validator = random_str(64);
  $hash      = hash('sha256', $validator);
  $expires   = (new DateTime("+{$days} days"))->format('Y-m-d H:i:s');

  $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
  $ip = inet_pton($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

  $sql = "INSERT INTO remember_tokens (user_id, selector, hashed_validator, expires_at, user_agent, ip_addr)
          VALUES (?, ?, ?, ?, ?, ?)";
  if ($stmt = $koneksi->prepare($sql)) {
    // ip_addr (VARBINARY) tetap di-bind sebagai string
    $stmt->bind_param('isssss', $user_id, $selector, $hash, $expires, $ua, $ip);
    $stmt->execute();
    $stmt->close();
  }

  set_remember_cookie($selector, $validator, new DateTime($expires));
}

function remember_purge_expired(mysqli $koneksi): void {
  $koneksi->query("DELETE FROM remember_tokens WHERE expires_at < NOW()");
}

/**
 * Coba login dari cookie remember; return array user jika sukses, selain itu null.
 * Jika sukses → set session & ROTASI validator.
 */
function remember_login_from_cookie(mysqli $koneksi): ?array {
  if (empty($_COOKIE['remember'])) return null;

  $parts = explode(':', $_COOKIE['remember'], 2);
  if (count($parts) !== 2) { clear_remember_cookie(); return null; }
  [$selector, $validator] = $parts;

  if (!preg_match('/^[a-f0-9]{12}$/', $selector) || !preg_match('/^[a-f0-9]{64}$/', $validator)) {
    clear_remember_cookie(); return null;
  }

  // Ambil token berdasar selector
  $sql = "SELECT * FROM remember_tokens WHERE selector = ? LIMIT 1";
  if (!($st = $koneksi->prepare($sql))) { return null; }
  $st->bind_param('s', $selector);
  $st->execute();
  $res = $st->get_result();
  $tok = $res->fetch_assoc();
  $st->close();

  if (!$tok) { clear_remember_cookie(); return null; }

  // Cek kadaluarsa
  if (new DateTime($tok['expires_at']) < new DateTime()) {
    if ($del = $koneksi->prepare("DELETE FROM remember_tokens WHERE id = ?")) {
      $del->bind_param('i', $tok['id']);
      $del->execute();
      $del->close();
    }
    clear_remember_cookie(); return null;
  }

  // Verifikasi validator
  if (!hash_equals($tok['hashed_validator'], hash('sha256', $validator))) {
    // kemungkinan token reuse/curian → hapus
    if ($del = $koneksi->prepare("DELETE FROM remember_tokens WHERE id = ?")) {
      $del->bind_param('i', $tok['id']);
      $del->execute();
      $del->close();
    }
    clear_remember_cookie(); return null;
  }

  // Ambil user
  $uid = (int)$tok['user_id'];
  if (!($us = $koneksi->prepare("SELECT * FROM users WHERE id = ? LIMIT 1"))) {
    return null;
  }
  $us->bind_param('i', $uid);
  $us->execute();
  $ures = $us->get_result();
  $user = $ures->fetch_assoc();
  $us->close();

  if (!$user) {
    if ($del = $koneksi->prepare("DELETE FROM remember_tokens WHERE id = ?")) {
      $del->bind_param('i', $tok['id']);
      $del->execute();
      $del->close();
    }
    clear_remember_cookie(); return null;
  }

  // === ROTASI validator ===
  $newValidator = random_str(64);
  $newHash      = hash('sha256', $newValidator);
  $newExpDT     = new DateTime('+30 days');
  $newExp       = $newExpDT->format('Y-m-d H:i:s');
  $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
  $ip = inet_pton($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

  $up = $koneksi->prepare("UPDATE remember_tokens
                           SET hashed_validator = ?, expires_at = ?, last_used_at = NOW(),
                               user_agent = ?, ip_addr = ?
                           WHERE id = ?");
  if ($up) {
    $id = (int)$tok['id'];
    $up->bind_param('ssssi', $newHash, $newExp, $ua, $ip, $id);
    $up->execute();
    $up->close();
  }

  set_remember_cookie($tok['selector'], $newValidator, $newExpDT);

  // Set session login (sesuaikan field yang kamu pakai)
// Set session login (SAMAKAN dengan auth_login)
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
session_regenerate_id(true);

$_SESSION['user'] = [
  'id'      => (int)$user['id'],
  'nama'    => $user['nama'] ?? null,
  'email'   => $user['email'] ?? null,
  'no_telp' => $user['no_telp'] ?? null,
  'role'    => strtolower($user['role'] ?? 'user'),
  'status'  => strtolower($user['status'] ?? 'active'),
];

// (opsional) update tgl_login
if ($up = $koneksi->prepare("UPDATE users SET tgl_login = NOW() WHERE id = ?")) {
  $up->bind_param('i', $user['id']);
  $up->execute();
  $up->close();
}

return $_SESSION['user'];

}

function remember_clear_all_for_user(mysqli $koneksi, int $user_id): void {
  if ($st = $koneksi->prepare("DELETE FROM remember_tokens WHERE user_id = ?")) {
    $st->bind_param('i', $user_id);
    $st->execute();
    $st->close();
  }
}

function remember_logout(mysqli $koneksi): void {
  if (!empty($_COOKIE['remember'])) {
    $parts = explode(':', $_COOKIE['remember'], 2);
    if (count($parts) === 2) {
      $selector = $parts[0];
      if ($st = $koneksi->prepare("DELETE FROM remember_tokens WHERE selector = ?")) {
        $st->bind_param('s', $selector);
        $st->execute();
        $st->close();
      }
    }
  }
  clear_remember_cookie();

  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'],
      $params['secure'], $params['httponly']);
  }
  session_destroy();
}
