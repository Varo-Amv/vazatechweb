<?php
$ROOT = dirname(__DIR__, 2);
require $ROOT . '/inc/session.php';
require $ROOT . '/inc/koneksi.php';
require $ROOT . '/inc/auth.php';
require $ROOT . '/inc/fungsi.php';
require $ROOT . '/inc/env.php';
require_login();

function back_with($q){ header("Location: ../../profile.php?$q"); exit; }

// Pastikan ada file
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) back_with("upload=none");
if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) back_with("upload=err");

// (opsional) validasi ukuran 3MB
if ($_FILES['avatar']['size'] > 3*1024*1024) back_with("upload=toolarge");

// Ambil user
$stmt = $koneksi->prepare("SELECT id, avatar_path FROM users WHERE email=?");
$stmt->bind_param("s", $_SESSION['user']['email']);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
if (!$me) { header("Location: ../../logout.php"); exit; }

// ==== Upload ke ImgBB ====
$IMGBB_KEY = getenv('IMGBB_KEY') ?: $KeyGBB; // <- taruh di env jika bisa
$res = upload_to_imgbb($_FILES['avatar']['tmp_name'], $_FILES['avatar']['name'], $IMGBB_KEY);

if (!$res['ok']) {
  back_with("upload=remote_fail&err=".rawurlencode($res['err']));
}

$remoteUrl = $res['url']; // URL gambar publik

// (opsional) hapus avatar lama kalau URL lama bukan default
$old = $me['avatar_path'] ?? '';
if ($old && stripos($old, 'uploads/avatars/') === 0) {
  @unlink($ROOT . '/' . $old); // hanya jika dulu simpan lokal
}

// Update DB dengan URL
$upd = $koneksi->prepare("UPDATE users SET avatar_path=? WHERE id=?");
$upd->bind_param("si", $remoteUrl, $me['id']);
$ok = $upd->execute();

// Update session
$_SESSION['user']['avatar_path'] = $remoteUrl;

back_with($ok ? "upload=ok" : "upload=dberr");
