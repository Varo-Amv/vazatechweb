<?php
// admin/blog_edit.php (tema putih)
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/koneksi.php';
require_once __DIR__ . '/../inc/fungsi.php';
require_once __DIR__ . '/../inc/auth.php';
require_role(['admin','staff']); // hanya admin/staff

// CSRF
if (empty($_SESSION['csrf_blog'])) { $_SESSION['csrf_blog'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf_blog'];

// Konfigurasi upload (sama seperti create)
$USE_IMGBB = false;  $IMGBB_KEY = '';
$LOCAL_DIR = __DIR__ . '/../assets/uploads/blog';
$LOCAL_URL = (function_exists('url') ? url('assets/uploads/blog') : '/assets/uploads/blog');

// Ambil artikel by slug atau id
$slug = trim($_GET['slug'] ?? '');
$id   = (int)($_GET['id'] ?? 0);

if ($slug !== '') {
  $stmt = $koneksi->prepare("SELECT * FROM blog_posts WHERE slug=? LIMIT 1");
  $stmt->bind_param("s", $slug);
} else {
  $stmt = $koneksi->prepare("SELECT * FROM blog_posts WHERE id=? LIMIT 1");
  $stmt->bind_param("i", $id);
}
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
if (!$post) { header('HTTP/1.1 404 Not Found'); exit('Artikel tidak ditemukan.'); }

$title     = $post['title'];
$excerpt   = $post['excerpt'];
$content   = $post['content'];
$published = (int)$post['published'];
$cover_url = $post['cover_url'];

$msg=''; $ok=false;

if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!hash_equals($_SESSION['csrf_blog'], $_POST['csrf'] ?? '')) {
    $msg = 'Sesi berakhir. Muat ulang halaman.';
  } else {
    $title       = trim($_POST['title'] ?? '');
    $excerpt     = trim($_POST['excerpt'] ?? '');
    $content     = trim($_POST['content'] ?? '');
    $published   = isset($_POST['published']) ? 0 : 1;
    $remove_cover= isset($_POST['remove_cover']);

    if ($title==='')              $msg = "Judul wajib diisi.";
    elseif (strlen($content)<20)  $msg = "Konten terlalu pendek (min 20 karakter).";

    // handle cover
    $new_cover = $cover_url;
// HANYA MySQL <-> imgbb (tanpa local file)
if ($msg === '') {
  // Hapus cover: cukup null-kan URL (tidak perlu unlink lokal)
  if ($remove_cover) {
    $new_cover = null;
  }

  // Upload cover baru -> WAJIB imgbb
  if (!empty($_FILES['cover']['tmp_name'])) {
    if ($USE_IMGBB && function_exists('upload_to_imgbb') && !empty($IMGBB_KEY)) {
      // (opsional) validasi basic mime/ekstensi
      $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
        $ext = 'jpg';
      }

      $res = upload_to_imgbb($_FILES['cover']['tmp_name'], $_FILES['cover']['name'], $IMGBB_KEY);
      if (!empty($res['ok'])) {
        // simpan URL imgbb ke DB
        $new_cover = $res['url'];
        // kalau kamu menyimpan delete_url dari imgbb, simpan juga di DB:
        // $new_delete_url = $res['delete_url'] ?? null;
      } else {
        $msg = "Upload cover ke imgbb gagal: " . ($res['err'] ?? 'unknown error');
      }
    } else {
      $msg = 'Konfigurasi imgbb belum aktif (cek $USE_IMGBB, $IMGBB_KEY, dan fungsi upload_to_imgbb).';
    }
  }
}


    if ($msg==='') {
      $sql = "UPDATE blog_posts SET title=?, excerpt=?, content=?, cover_url=?, published=?, updated_at=NOW() WHERE id=?";
      $u = $koneksi->prepare($sql);
      $u->bind_param("ssssii", $title, $excerpt, $content, $new_cover, $published, $post['id']);
      $ok = $u->execute();
      $msg = $ok ? 'Perubahan disimpan.' : 'Gagal menyimpan perubahan.';
      if ($ok) { $cover_url = $new_cover; }
    }
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Edit Artikel • Admin • VAZATECH</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/blog-edit.css">
</head>
<body>
  <div class="wrap">
    <div class="page-head">
      <h1>Edit Artikel</h1>
      <span class="badge">ID #<?= (int)$post['id'] ?></span>
    </div>

    <?php if ($msg): ?>
      <div class="note <?= $ok?'ok':'err' ?>">
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-head">
        <div class="muted">Perbarui konten, cover, dan status publikasi.</div>
        <div class="chip"><?= $published ? 'Published' : 'Draft' ?></div>
      </div>

      <div class="card-body">
        <form method="post" enctype="multipart/form-data" autocomplete="off">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

          <div class="grid">
            <!-- KIRI: judul & konten -->
            <div>
              <label>Judul</label>
              <input class="input" type="text" name="title" value="<?= htmlspecialchars($title) ?>" required>

              <label>Ringkasan (opsional)</label>
              <textarea class="area" name="excerpt" placeholder="Teks pendek yang tampil sebagai cuplikan di kartu/blog list..."><?= htmlspecialchars($excerpt) ?></textarea>

              <label>Konten</label>
              <textarea class="area" name="content" required placeholder="Tulis konten artikel di sini..."><?= htmlspecialchars($content) ?></textarea>
              <div class="helper">Tip: kamu bisa menempel HTML, gambar dengan &lt;img&gt;, atau kode yang dibungkus &lt;pre&gt;.</div>
            </div>

            <!-- KANAN: cover & publish -->
            <div>
              <label>Cover</label>
              <input class="input" type="file" name="cover" accept="image/*">
              <div class="helper">PNG/JPG/WEBP disarankan ≤ 1MB.</div>

              <?php if ($cover_url): ?>
                <div class="cover-wrap">
                  <img class="cover-img" src="<?= htmlspecialchars($cover_url) ?>" alt="Cover">
                </div>
                <label class="switch" style="margin-top:8px">
                  <input type="checkbox" name="remove_cover"> Hapus cover
                </label>
              <?php endif; ?>

              <label class="switch" style="margin-top:12px">
                <input type="checkbox" name="published" <?= $published? '':'checked' ?>> Draft
              </label>

              <div class="actions" style="margin-top:14px">
                <button type="submit" class="btnsv">
  <span class="span-mother">
    <span>S</span>
    <span>i</span>
    <span>m</span>
    <span>p</span>
    <span>a</span>
    <span>n</span>
    <span>&nbsp;</span>
    <span>P</span>
    <span>e</span>
    <span>r</span>
    <span>u</span>
    <span>b</span>
    <span>a</span>
    <span>h</span>
    <span>a</span>
    <span>n</span>
  </span>
  <span class="span-mother2">
    <span>S</span>
    <span>i</span>
    <span>m</span>
    <span>p</span>
    <span>a</span>
    <span>n</span>
    <span>&nbsp;</span>
    <span>P</span>
    <span>e</span>
    <span>r</span>
    <span>u</span>
    <span>b</span>
    <span>a</span>
    <span>h</span>
    <span>a</span>
    <span>n</span>
  </span>
</button>
                <a class="btn btn-ghost" href="blog-list">Kembali ke Daftar</a>
                <a class="btn btn-ghost" target="_blank" href="<?= htmlspecialchars((function_exists('url')?url('blog/'.$post['slug']):('/blog/'.$post['slug']))) ?>">Lihat Publik</a>
              </div>
            </div>
          </div>

        </form>
      </div>
    </div>
  </div>
</body>
</html>
