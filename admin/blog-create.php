<?php
// admin/blog_create.php — Tema putih
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/koneksi.php';
require_once __DIR__ . '/../inc/fungsi.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/env.php';
require_role(['admin','staff']); // hanya admin/staff

// ---- CSRF ----
if (empty($_SESSION['csrf_blog'])) {
  $_SESSION['csrf_blog'] = bin2hex(random_bytes(32));
}
function csrf_ok($t){ return isset($_SESSION['csrf_blog']) && hash_equals($_SESSION['csrf_blog'], $t ?? ''); }

// === Konfigurasi upload cover ===
$USE_IMGBB = true;                                    // true jika pakai ImgBB
$IMGBB_KEY = $KeyGBB;      // API key ImgBB kamu

// (Jika ingin fallback lokal, buka komentar ini)
// $LOCAL_DIR = __DIR__ . '/../assets/uploads/blog';
// $LOCAL_URL = (function_exists('url') ? url('assets/uploads/blog') : '/assets/uploads/blog');

// ---- helper slug ----
function slugify($text){
  $text = strtolower($text);
  $text = preg_replace('~[^\pL\d]+~u', '-', $text);
  $text = trim($text, '-');
  $text = preg_replace('~[^-\w]+~', '', $text);
  if (empty($text)) $text = 'post';
  return $text;
}

// ---- inisialisasi form ----
$title=''; $excerpt=''; $content=''; $published=1;
$msg=''; $ok=false;

if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!csrf_ok($_POST['csrf'] ?? '')) {
    $msg = "Sesi berakhir. Muat ulang halaman.";
  } else {
    $title     = trim($_POST['title'] ?? '');
    $excerpt   = trim($_POST['excerpt'] ?? '');
    $content   = trim($_POST['content'] ?? '');
    $published = isset($_POST['published']) ? 0 : 1;

    if ($title==='')              $msg = "Judul wajib diisi.";
    elseif (strlen($content)<20)  $msg = "Konten terlalu pendek (min 20 karakter).";
    else {
      // slug unik
      $slug = slugify($title);
      $cek  = $koneksi->prepare("SELECT COUNT(*) c FROM blog_posts WHERE slug=?");
      $slugTry = $slug; $i=2;
      while (true) {
        $cek->bind_param("s", $slugTry);
        $cek->execute();
        $c = $cek->get_result()->fetch_assoc()['c'] ?? 0;
        if ($c==0) { $slug = $slugTry; break; }
        $slugTry = $slug.'-'.$i++;
      }

      // cover (opsional)
      $cover_url = null;
      if (!empty($_FILES['cover']['tmp_name'])) {
        if ($USE_IMGBB && function_exists('upload_to_imgbb') && $IMGBB_KEY) {
          $res = upload_to_imgbb($_FILES['cover']['tmp_name'], $_FILES['cover']['name'], $IMGBB_KEY);
          if ($res['ok']) { $cover_url = $res['url']; }
          else { $msg = "Upload cover gagal: ".$res['err']; }
        } else {
          // --- fallback penyimpanan lokal (buka komentar jika dipakai) ---
          // if (!is_dir($LOCAL_DIR)) { @mkdir($LOCAL_DIR,0775,true); }
          // $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
          // if (!in_array($ext,['jpg','jpeg','png','webp'])) $ext = 'jpg';
          // $basename = date('YmdHis').'_'.bin2hex(random_bytes(4)).'.'.$ext;
          // $dest = rtrim($LOCAL_DIR,'/').'/'.$basename;
          // if (move_uploaded_file($_FILES['cover']['tmp_name'], $dest)) {
          //   $cover_url = rtrim($LOCAL_URL,'/').'/'.$basename;
          // } else {
          //   $msg = "Gagal menyimpan file cover.";
          // }
          $msg = "Upload cover dinonaktifkan (fallback lokal tidak diaktifkan).";
        }
      }

      if ($msg==='') {
        $sql = "INSERT INTO blog_posts (title, slug, excerpt, content, cover_url, published, author_id, created_at, updated_at)
                VALUES (?,?,?,?,?,?,?,NOW(),NOW())";
        $stmt = $koneksi->prepare($sql);
        $author = (int)($_SESSION['user']['id'] ?? 0);
        $stmt->bind_param("sssssis", $title, $slug, $excerpt, $content, $cover_url, $published, $author);
        if ($stmt->execute()) {
          $ok  = true;
          $msg = "Artikel berhasil dibuat.";
          header("Location: blog-edit?slug=".$slug);
          exit;
        } else {
          $msg = "Gagal menyimpan artikel. Coba lagi.";
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
  <title>Buat Artikel • Admin • VAZATECH</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/blog-create.css">
</head>
<body>
  <div class="wrap">
    <div class="page-head">
      <h1>Buat Artikel Blog</h1>
      <span class="badge">Draft Baru</span>
    </div>

    <?php if ($msg): ?>
      <div class="note <?= $ok?'ok':'err' ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="card">
      <div class="card-head">
        <div class="muted">Isi judul, konten, dan (opsional) cover. Centang “Terbitkan sekarang” jika ingin langsung live.</div>
      </div>

      <div class="card-body">
        <form method="post" enctype="multipart/form-data" autocomplete="off">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_blog']) ?>">

          <div class="grid">
            <!-- KIRI -->
            <div>
              <label>Judul</label>
              <input class="input" type="text" name="title" value="<?= htmlspecialchars($title) ?>" required>

              <label>Ringkasan (opsional)</label>
              <textarea class="area" name="excerpt" placeholder="Ringkasan singkat (ditampilkan di daftar blog)"><?= htmlspecialchars($excerpt) ?></textarea>

              <label>Konten</label>
              <textarea class="area" name="content" required placeholder="Tulis konten artikel di sini..."><?= htmlspecialchars($content) ?></textarea>
              <div class="helper">Tip: bisa menempel HTML/gambar. Gunakan &lt;pre&gt; untuk kode.</div>
            </div>

            <!-- KANAN -->
            <div>
              <label>Cover (opsional)</label>
              <input class="input" type="file" name="cover" accept="image/*">
              <div class="helper">PNG/JPG/WEBP disarankan ≤ 1MB. Saat ini upload via ImgBB.</div>

              <label style="display:flex;align-items:center;gap:10px;margin-top:12px">
                <input type="checkbox" name="published" <?= $published? '':'checked' ?>> Draft
              </label>

              <div class="actions" style="margin-top:14px">
                <button class="btn-up">
  <svg class="w-6 h-6" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" stroke-linejoin="round" stroke-linecap="round"></path>
  </svg>
  <span class="text">
    Upload
  </span>
</button>
                <a class="btn btn-ghost" href="blog-list">Kembali ke Daftar</a>
              </div>
            </div>
          </div>

        </form>
      </div>
    </div>
  </div>
</body>
</html>
