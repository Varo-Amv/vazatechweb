<?php
// /blog/index.php — Tema putih + tombol cantik + gambar sedikit lebih kecil
require_once __DIR__ . '/../inc/koneksi.php';
require_once __DIR__ . '/../inc/fungsi.php';

$home     = function_exists('url') ? url('/')   : '/';
$blogList = function_exists('url') ? url('blog') : '/blog';
$slug = trim($_GET['slug'] ?? '');

/* ==================== DETAIL ==================== */
if ($slug !== '') {
  $sql  = "SELECT id,title,slug,excerpt,content,cover_url,published,created_at,updated_at
           FROM blog_posts WHERE slug=? AND published=1 LIMIT 1";
  $stmt = $koneksi->prepare($sql);
  $stmt->bind_param("s", $slug);
  $stmt->execute();
  $post = $stmt->get_result()->fetch_assoc();

  if (!$post) { header("HTTP/1.1 404 Not Found"); }

  $title   = $post ? $post['title'] : 'Tidak ditemukan';
  $desc    = $post['excerpt'] ?: mb_substr(strip_tags($post['content'] ?? ''), 0, 160);
  $cover   = $post['cover_url'] ?: (function_exists('url') ? url('image/logo_nocapt.png') : '/image/logo_nocapt.png');
  $pubDate = $post ? date('d M Y', strtotime($post['created_at'])) : '';
  $brand   = 'VAZATECH';
  $domain  = 'vazatech.store';
  ?>
  <!doctype html>
  <html lang="id">
  <head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title) ?> • Blog <?= htmlspecialchars($brand) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= htmlspecialchars($desc) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">

    <meta property="og:title" content="<?= htmlspecialchars($title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($desc) ?>">
    <meta property="og:type" content="article">
    <meta property="og:image" content="<?= htmlspecialchars($cover) ?>">
    <meta property="og:url" content="<?= htmlspecialchars((function_exists('url')?url('blog/'.$slug):('/blog/'.$slug))) ?>">
    <meta name="twitter:card" content="summary_large_image">

    <style>
      :root{
        --bg:#ffffff; --surface:#ffffff; --line:#e5e7eb;
        --text:#0f172a; --muted:#64748b; --accent:#2563eb;
        --shadow:0 10px 26px rgba(15,23,42,.08);
      }
      *{box-sizing:border-box}
      html,body{margin:0;background:var(--bg);color:var(--text);font-family:Inter,system-ui,Segoe UI,Roboto,Arial,sans-serif}
      a{text-decoration:none}
      .wrap{max-width:960px;margin:28px auto;padding:0 18px}
      .header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px}
      .brand{display:flex;align-items:center;gap:12px}
      .logo{width:42px;height:42px;object-fit:contain;border-radius:10px;background:#eef2ff;padding:6px;box-shadow:var(--shadow)}
      h1{font-size:32px;line-height:1.2;margin:0 0 4px}
      .meta{color:var(--muted);font-size:14px}

      /* Tombol cantik */
      .btn{
        display:inline-flex;align-items:center;gap:10px;
        padding:12px 18px;border-radius:14px;border:0;cursor:pointer;
        background:linear-gradient(135deg,#3b82f6 0%,#1d4ed8 100%);
        color:#fff;font-weight:800;box-shadow:0 12px 24px rgba(37,99,235,.25);
        transition:transform .12s ease, filter .12s ease, box-shadow .12s ease;
      }
      .btn:hover{transform:translateY(-1px);filter:brightness(1.05);box-shadow:0 16px 30px rgba(37,99,235,.28)}
      .btn:active{transform:translateY(0)}
      .btn--light{
        background:#fff;color:var(--text);border:1px solid var(--line);box-shadow:var(--shadow)
      }
      .btn--light:hover{border-color:#cfd8e3;filter:none;box-shadow:0 12px 26px rgba(15,23,42,.12)}

      .card{background:var(--surface);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);padding:18px}
      .cover{width:100%;max-height:360px;object-fit:cover;border-radius:14px;border:1px solid var(--line)} /* lebih kecil */
      .content{line-height:1.4;margin-top:14px;font-size:17px;color:#111827;white-space: pre-wrap;word-break: break-word}
      .content img{max-width:100%;height:auto;border-radius:10px}
      .content pre{background:#0f172a;color:#e5e7eb;padding:14px;border-radius:12px;overflow:auto}
      .back{margin-top:18px;display:flex;gap:10px;flex-wrap:wrap}
      @media (max-width:640px){ h1{font-size:26px} .content{font-size:16.5px} }
    </style>
  </head>
  <body>
    <div class="wrap">
      <div class="header">
        <div class="brand">
          <img class="logo" src="<?= htmlspecialchars(function_exists('url')?url('image/logo_nocapt.png'):'/image/logo_nocapt.png') ?>" alt="VAZATECH" loading="lazy">
          <div>
            <div class="meta">Blog • <?= htmlspecialchars($domain) ?></div>
            <h1><?= htmlspecialchars($title) ?></h1>
            <div class="meta">Diterbitkan: <?= htmlspecialchars($pubDate) ?></div>
          </div>
        </div>
        <a class="btn btn--light" href="<?= htmlspecialchars($blogList) ?>">Semua Artikel</a>
      </div>

      <?php if(!$post): ?>
        <div class="card">Artikel tidak ditemukan atau belum diterbitkan.</div>
      <?php else: ?>
        <div class="card">
          <?php if ($cover): ?>
            <img class="cover" src="<?= htmlspecialchars($cover) ?>" alt="<?= htmlspecialchars($title) ?>">
          <?php endif; ?>
          <div class="content">
            <?php echo $post['content']; ?>
          </div>
          <div class="back">
            <a class="btn btn--light" href="<?= htmlspecialchars($blogList) ?>">Kembali ke Blog</a>
            <a class="btn btn--light" href="<?= htmlspecialchars($home) ?>">Beranda</a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </body>
  </html>
  <?php
  exit;
}

/* ==================== LIST ==================== */
$stmt = $koneksi->prepare("SELECT title,slug,excerpt,cover_url,created_at FROM blog_posts WHERE published=1 ORDER BY created_at DESC");
$stmt->execute();
$rows   = $stmt->get_result();
$brand  = 'VAZATECH';
$domain = 'vazatech.store';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Blog • <?= htmlspecialchars($brand) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
  <style>
    :root{
      --bg:#ffffff; --text:#0f172a; --muted:#64748b; --line:#e5e7eb;
      --accent:#2563eb; --surface:#ffffff; --shadow:0 10px 26px rgba(15,23,42,.08);
      --thumb:#f3f4f6;
    }
    *{box-sizing:border-box}
    html,body{margin:0;background:var(--bg);color:var(--text);font-family:Inter,system-ui,Segoe UI,Roboto,Arial,sans-serif}
    .wrap{max-width:1080px;margin:28px auto;padding:0 18px}
    .header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px}
    .brand{display:flex;align-items:center;gap:12px}
    .logo{width:42px;height:42px;object-fit:contain;border-radius:10px;background:#eef2ff;padding:6px;box-shadow:var(--shadow)}
    h1{font-size:28px;margin:0}
    .meta{color:var(--muted);font-size:14px}
    .btn{
      display:inline-flex;align-items:center;gap:10px;padding:12px 18px;border-radius:14px;border:0;cursor:pointer;
      background:linear-gradient(135deg,#3b82f6 0%,#1d4ed8 100%);color:#fff;font-weight:800;box-shadow:0 12px 24px rgba(37,99,235,.25);
      transition:transform .12s ease, filter .12s ease, box-shadow .12s ease;
    }
    .btn:hover{transform:translateY(-1px);filter:brightness(1.05);box-shadow:0 16px 30px rgba(37,99,235,.28)}

    .tiles{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
    @media (max-width:980px){ .tiles{grid-template-columns:repeat(2,1fr)} }
    @media (max-width:560px){ .tiles{grid-template-columns:1fr} }

    .tile{
      display:block;background:var(--surface);border:1px solid var(--line);border-radius:18px;
      padding:14px;box-shadow:var(--shadow);
      transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease;
    }
    .tile:hover{ transform:translateY(-2px); box-shadow:0 14px 32px rgba(15,23,42,.12); border-color:#d8dee9; }

    .thumb{
      width:100%; height:140px; /* lebih kecil */
      object-fit:cover;border-radius:14px;border:1px solid var(--line);background:var(--thumb);
    }
    .title{margin:10px 2px 4px;font-size:18px;font-weight:800;line-height:1.35}
    .excerpt{margin:2px 2px 6px;color:#334155;font-size:14.5px;line-height:1.55}
    .tile-meta{margin-top:4px;font-size:12.5px;color:var(--muted)}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="header">
      <div class="brand">
        <img class="logo" src="<?= htmlspecialchars(function_exists('url')?url('image/logo_nocapt.png'):'/image/logo_nocapt.png') ?>" alt="VAZATECH" loading="lazy">
        <div>
          <h1>Blog</h1>
          <div class="meta">Berita & artikel terbaru • <?= htmlspecialchars($domain) ?></div>
        </div>
      </div>
      <a class="btn" href="<?= htmlspecialchars($home) ?>">Beranda</a>
    </div>

    <div class="tiles">
      <?php if ($rows->num_rows === 0): ?>
        <div class="tile" style="text-align:center">
          <div class="title">Belum ada artikel</div>
          <div class="excerpt">Tunggu update terbaru dari kami. Konten menarik akan segera hadir.</div>
          <div class="tile-meta">VAZATECH</div>
        </div>
      <?php endif; ?>

      <?php while($r = $rows->fetch_assoc()):
        $title = $r['title'];
        $slug  = $r['slug'];
        $href  = function_exists('url') ? url('blog/'.$slug) : ('/blog/'.$slug);
        $date  = date('d M Y', strtotime($r['created_at']));
        $cover = $r['cover_url'] ?: (function_exists('url')?url('image/logo_nocapt.png'):'/image/logo_nocapt.png');
        $excerpt = $r['excerpt'] ?: mb_substr(strip_tags($r['content'] ?? ''), 0, 100).'…';
      ?>
        <a class="tile" href="<?= htmlspecialchars($href) ?>">
          <img class="thumb" src="<?= htmlspecialchars($cover) ?>" alt="<?= htmlspecialchars($title) ?>"
               loading="lazy"
               onerror="this.onerror=null;this.src='<?= htmlspecialchars(function_exists('url')?url('image/logo_nocapt.png'):'/image/logo_nocapt.png') ?>'">
          <div class="title"><?= htmlspecialchars($title) ?></div>
          <div class="excerpt"><?= htmlspecialchars($excerpt) ?></div>
          <div class="tile-meta"><?= $date ?> • <?= htmlspecialchars($domain) ?></div>
        </a>
      <?php endwhile; ?>
    </div>
  </div>
</body>
</html>
