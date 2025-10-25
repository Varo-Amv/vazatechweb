<?php
// topupweb/logout.php
// Bersihkan sesi & cookie, lalu redirect

// Pastikan session aktif
require __DIR__ . '/inc/session.php';
// Jika kamu butuh helper url_dasar():
require __DIR__ . '/inc/fungsi.php';

// (opsional) mulai output buffering untuk mencegah "headers already sent"
if (!ob_get_level()) { ob_start(); }

// Hapus semua data sesi
$_SESSION = [];

// Hapus cookie sesi di browser (jika ada)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, 
        $params["path"], $params["domain"], 
        $params["secure"], $params["httponly"]
    );
    // Jika kamu memakai cookie lain seperti "remember_me", hapus juga di sini:
    // setcookie('remember_me', '', time() - 42000, '/');
}

// Hancurkan sesi
session_destroy();

// Regenerasi ID sesi baru yang bersih (opsional, untuk hardening)
session_start();
session_regenerate_id(true);

// Tentukan tujuan redirect
$redirectTo = function_exists('url_dasar') ? url_dasar() . '' : 'index.php';
// Jika tidak punya halaman login, bisa ganti ke beranda:
// $redirectTo = function_exists('url_dasar') ? url_dasar() . '/' : 'index.php';

// Redirect
if (!headers_sent()) {
    header("Location: {$redirectTo}");
    exit;
}

// Fallback jika header sudah terkirim
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta http-equiv="refresh" content="0;url=<?= htmlspecialchars($redirectTo) ?>">
  <title>Logout</title>
</head>
<body>
  <p>Berhasil logout. Jika tidak otomatis pindah, klik <a href="<?= htmlspecialchars($redirectTo) ?>">di sini</a>.</p>
</body>
</html>
