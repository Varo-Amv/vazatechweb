<?php
// /admin/blog-list.php
require_once __DIR__ . '/../inc/session.php';
require_once __DIR__ . '/../inc/koneksi.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/fungsi.php';
require_role(['admin','staff']); // hanya admin/staff

// CSRF
if (empty($_SESSION['csrf_blog'])) { $_SESSION['csrf_blog'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf_blog'];

$msg = ''; $ok = false;

/* ---------- Aksi (toggle publish / delete) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
    $msg = 'Sesi berakhir. Muat ulang halaman.';
  } else {
    $id  = (int)($_POST['id'] ?? 0);
    $act = $_POST['act'] ?? '';
    if ($id > 0) {
      if ($act === 'toggle') {
        $st = $koneksi->prepare("UPDATE blog_posts SET published=1-published, updated_at=NOW() WHERE id=?");
        $st->bind_param("i", $id);
        $ok = $st->execute();
        $msg = $ok ? 'Status artikel diperbarui.' : 'Gagal memperbarui status.';
      } elseif ($act === 'delete') {
        // ambil cover jika perlu dihapus file lokal (opsional)
        $get = $koneksi->prepare("SELECT cover_url FROM blog_posts WHERE id=?");
        $get->bind_param("i", $id);
        $get->execute();
        $cover = $get->get_result()->fetch_assoc()['cover_url'] ?? null;

        $del = $koneksi->prepare("DELETE FROM blog_posts WHERE id=?");
        $del->bind_param("i", $id);
        $ok = $del->execute();
        $msg = $ok ? 'Artikel dihapus.' : 'Gagal menghapus artikel.';

        // (opsional) hapus file lokal jika cover ada di /assets/uploads/blog
        if ($ok && $cover && str_starts_with($cover, (function_exists('url')?url('assets/uploads/blog'):'/assets/uploads/blog'))) {
          $local = __DIR__.'/..'.parse_url($cover, PHP_URL_PATH);
          if (is_file($local)) @unlink($local);
        }
      }
    }
  }
}

/* ---------- Query list ---------- */
$q = trim($_GET['q'] ?? '');
$sql = "SELECT id, title, slug, published, created_at, updated_at FROM blog_posts";
$where = '';
$params = [];
if ($q !== '') {
  $where = " WHERE title LIKE ? OR slug LIKE ? ";
  $like = "%{$q}%";
  $params = [$like, $like];
}
$sql .= $where . " ORDER BY created_at DESC";

$stmt = $koneksi->prepare($sql);
if ($q !== '') { $stmt->bind_param("ss", ...$params); }
$stmt->execute();
$res = $stmt->get_result();

$home = function_exists('url') ? url('/') : '/';
$blogPublic = function_exists('url') ? fn($slug)=>url('blog/'.$slug) : fn($slug)=>('/blog/'.$slug);
/* ---------- KPI cards ---------- */
$kpi_total     = (int)($koneksi->query("SELECT COUNT(*) FROM blog_posts")->fetch_row()[0] ?? 0);
$kpi_published = (int)($koneksi->query("SELECT COUNT(*) FROM blog_posts WHERE published=1")->fetch_row()[0] ?? 0);
$kpi_unpub     = (int)($koneksi->query("SELECT COUNT(*) FROM blog_posts WHERE published=0")->fetch_row()[0] ?? 0);

/* ---------- Query list (search + filter status) ---------- */
$q      = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');   // '', '1', '0'

$sql    = "SELECT id, title, slug, published, created_at, updated_at FROM blog_posts";
$where  = [];
$types  = '';
$args   = [];

/* search by title/slug */
if ($q !== '') {
  $where[] = "(title LIKE ? OR slug LIKE ?)";
  $like = "%{$q}%";
  $types .= 'ss';
  $args[] = $like; $args[] = $like;
}

/* filter status: 1=published, 0=not published */
if ($status === '1' || $status === '0') {
  $where[] = "published = ?";
  $types   .= 'i';
  $args[]  = (int)$status;
}

if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= " ORDER BY created_at DESC";

$stmt = $koneksi->prepare($sql);
if ($types !== '') {
  $stmt->bind_param($types, ...$args);
}
$stmt->execute();
$res = $stmt->get_result();

?>
<!doctype html>
<html lang="id">
<head>
<meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Blog · Admin</title>
<?php include("../inc/hdradmin.php")?>
    <main class="container">
      <div class="sidebar">
        <a href="index"><i class="fas fa-home"></i>Dashboard</a>
        <a href="stocks"><i class="fas fa-box"></i>Stocks</a>
        <a href="users"><i class="fas fa-users"></i>Users</a>
        <a href="orders"><i class="fas fa-shopping-cart"></i>Orders</a>
        <a href="banners"><i class="fas fa-image"></i>Banners</a>
        <a href="#" class="active"><i class="fas fa-newspaper"></i>Blog</a>
      </div>
    <link rel="stylesheet" href="../assets/css/blog-list.css">
</head>
<body>

    <!-- ===== CONTENT ===== -->
    <main class="content-blog">
            <div class="scroll-y scrollbar-none" style="max-height:510px;">
          <!-- KPI cards – gunakan style global dari hdradmin (card kpi) -->
          <div class="dashboard-header">
              <h2>Blog Stats</h2>
  <div class="cards-row">
    <div class="card kpi">
      <div class="kpi-title">Total Blog</div>
      <div class="kpi-value"><?= number_format($kpi_total) ?></div>
    </div>
    <div class="card kpi">
      <div class="kpi-title">Published</div>
      <div class="kpi-value"><?= number_format($kpi_published) ?></div>
    </div>
    <div class="card kpi">
      <div class="kpi-title">Not Published</div>
      <div class="kpi-value"><?= number_format($kpi_unpub) ?></div>
    </div>
  </div>
  </div>
  <!-- End KPI cards -->
      <div class="head">
        <h2 style="margin:0">Blog Posts</h2>
        <div class="actions">
          <a class="btn-blog primary" href="blog-create">+ Buat Artikel</a>
        </div>
      </div>
      <?php if ($msg): ?>
        <div class="card-blog" style="border-color:<?= $ok?'#b8f0d2':'#ffd4d4'?>;background:<?= $ok?'#e8fff2':'#fff0f0'?>;margin-bottom:10px">
          <?= htmlspecialchars($msg) ?>
        </div>
      <?php endif; ?>

      <div class="card-blog">
        <form class="search" method="get" id="searchForm">
          <input class="input-blog" type="search" name="q" placeholder="Cari judul atau slug..." value="<?= htmlspecialchars($q) ?>">
          <?php if ($q!==''): ?><a class="btn-blog outline" href="blog-list">Reset</a><?php endif; ?>
  <select class="input-blog" name="status" onchange="document.getElementById('searchForm').submit()">
    <option value=""  <?= $status===''  ? 'selected' : '' ?>>Semua Status</option>
    <option value="1" <?= $status==='1' ? 'selected' : '' ?>>Published</option>
    <option value="0" <?= $status==='0' ? 'selected' : '' ?>>Not Published</option>
  </select>
        </form>

        <div style="overflow:auto">
          <table>
            <thead>
              <tr>
                <th>Judul</th>
                <th>Slug</th>
                <th>Status</th>
                <th>Dibuat</th>
                <th style="width:300px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php while($row = $res->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($row['title']) ?></td>
                  <td class="muted"><?= htmlspecialchars($row['slug']) ?></td>
                  <td>
                    <?php if($row['published']): ?>
                      <span class="status on">Published</span>
                    <?php else: ?>
                      <span class="status off">Unpublished</span>
                    <?php endif; ?>
                  </td>
                  <td class="muted"><?= htmlspecialchars(date('d M Y H:i', strtotime($row['created_at']))) ?></td>
                  <td>
                    <div class="actions">
                      <a class="btn-blog outline" target="_blank" href="<?= htmlspecialchars($blogPublic($row['slug'])) ?>">Lihat</a>
                      <a class="btn-blog primary" href="blog-edit?slug=<?= urlencode($row['slug']) ?>">Edit</a>

                      <form method="post" onsubmit="return confirm('Ubah status terbit?')" style="display:inline">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="act" value="toggle">
                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                        <button class="btn-blog" type="submit"><?= $row['published'] ? 'Nonaktifkan' : 'Terbitkan' ?></button>
                      </form>

                      <form method="post" onsubmit="return confirm('Hapus artikel ini? Tindakan tidak bisa dibatalkan.')" style="display:inline">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="act" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                        <button class="btn-blog" type="submit">Hapus</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
              <?php if ($res->num_rows === 0): ?>
                <tr><td colspan="5" class="muted">Belum ada artikel.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      </div>
    </main>
  </div>
</body>
<script>
document.getElementById('searchForm').addEventListener('submit', (e) => {
  e.preventDefault();
  const q = encodeURIComponent(e.target.q.value); // %20
  location.href = `/admin/blog-list?q=${q}`;
});
  (function () {
    const form  = document.getElementById('searchForm');
    if (!form) return;

    let t = null;
    form.addEventListener('input', () => {
      clearTimeout(t);
      t = setTimeout(() => form.submit(), 1000);
    });
  })();
</script>
</html>
