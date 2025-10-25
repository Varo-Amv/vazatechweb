<?php
// 404.php
http_response_code(404);
require_once __DIR__ . '/inc/fungsi.php';     // agar bisa pakai url()
$home = function_exists('url') ? url('/') : '/';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>404 • Halaman Tidak Ditemukan</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@600;800;900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="./assets/css/404.css">

  <style>
    :root{
      --bg:#0b0614;
      --panel:#0d0a1a;
      --primary:#2e6bff;
      --primary2:#1a73e8;
      --text:#eaf0ff;
      --muted:#a9b8ff;
    }
    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0; font-family:Inter,system-ui,Segoe UI,Roboto,Arial,sans-serif; color:var(--text);
      background:
        radial-gradient(1100px 520px at 10% -10%, rgba(46,107,255,.18),transparent 60%),
        radial-gradient(900px 480px at 100% 0%, rgba(26,115,232,.22),transparent 55%),
        var(--bg);
      display:grid; place-items:center;
    }
    .card{
      width:min(820px,92vw);
      background:var(--panel);
      border:1px solid rgba(255,255,255,.08);
      border-radius:22px;
      padding:36px 28px;
      box-shadow:0 20px 60px rgba(0,0,0,.45);
      position:relative; overflow:hidden;
    }
    .bar{position:absolute; inset:0 auto auto 0; height:6px; width:100%;
      background:linear-gradient(90deg,var(--primary),var(--primary2),#0f3bd9);}
    .hero{
      text-align:center; margin:8px 0 14px;
      font-weight:900; letter-spacing:4px; line-height:1;
      font-size:128px; color:#fff;
      text-shadow:
        0 8px 0 rgba(46,107,255,.18),
        0 24px 60px rgba(0,0,0,.45);
    }
    .title{font-size:28px; font-weight:900; text-align:center; margin-top:6px}
    .caption{color:var(--muted); text-align:center; margin-top:8px}
    .actions{display:flex; justify-content:center; margin-top:22px}
    .btn{
      appearance:none; border:0; border-radius:12px;
      padding:12px 16px; font-weight:800; cursor:pointer;
      display:inline-flex; align-items:center; gap:10px;
      box-shadow:0 10px 24px rgba(46,107,255,.35); transition:.15s ease;
      background:#1733ff; color:#fff; text-decoration:none;
    }
    .btn:hover{filter:brightness(1.07); transform:translateY(-1px)}
    .hint{font-size:13px; color:#9fb2ff; text-align:center; margin-top:10px}
  </style>

</head>
<body>
  <div class="card">
    <div class="bar"></div>

    <div class="hero">404</div>
    <div class="title">Oops! Halaman tidak ditemukan</div>
    <div class="caption">URL yang kamu akses tidak tersedia atau sudah dipindahkan.</div>

    <div class="actions">
      <a class="btn" href="<?= htmlspecialchars($home) ?>">
        Kembali ke Beranda
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </a>
    </div>

    <div class="hint">Kode kesalahan: 404 · <?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES) ?></div>
  </div>
</body>
</html>
