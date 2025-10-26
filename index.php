<?php 
include("./inc/koneksi.php");
// Mulai session sekali untuk halaman ini
if (session_status() !== PHP_SESSION_ACTIVE) {
  // (opsional tapi bagus)
  session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
  session_start();
}

// BACA aman (tanpa warning)
$users_email = $_SESSION['users_email'] ?? null;
$users_name  = $_SESSION['users_name']  ?? null;
// index.php
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '');

// --- LOG KUNJUNGAN HALAMAN ---
require __DIR__.'/inc/koneksi.php';
date_default_timezone_set('Asia/Jakarta'); // sesuaikan

$ip    = $_SERVER['REMOTE_ADDR']            ?? '0.0.0.0';
$ua    = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255);
$path  = substr(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/', 0, 255);
$ref   = substr($_SERVER['HTTP_REFERER'] ?? '/', 0, 255);
$today = date('Y-m-d');
$now   = date('Y-m-d H:i:s');

/* (opsional) skip bot sederhana */
$skip  = false; // <-- inisialisasi agar tidak "Undefined variable $skip"
$ua_lc = strtolower($ua);
foreach (['bot','spider','crawler','preview','facebookexternalhit','pingdom','gtmetrix'] as $b) {
  if (strpos($ua_lc, $b) !== false) { $skip = true; break; }
}

if (!$skip) {
  try {
    if (isset($pdo) && $pdo instanceof PDO) {
      // --- versi PDO ---
      $sql = "INSERT INTO access_logs (visit_date, ip, user_agent, url_path, referrer, visited_at, last_seen, hits)
              VALUES (:d,:ip,:ua,:p,:r,:n,:n,1)
              ON DUPLICATE KEY UPDATE hits = hits + 1, last_seen = VALUES(last_seen)";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([
        ':d'  => $today,
        ':ip' => $ip,
        ':ua' => $ua,
        ':p'  => $path,
        ':r'  => $ref,
        ':n'  => $now,
      ]);

    } elseif (isset($koneksi) && $koneksi instanceof mysqli) {
      // --- versi MySQLi ---
      $sql = "INSERT INTO access_logs (visit_date, ip, user_agent, url_path, referrer, visited_at, last_seen, hits)
              VALUES (?,?,?,?,?,?,?,1)
              ON DUPLICATE KEY UPDATE hits = hits + 1, last_seen = VALUES(last_seen)";
      if ($stmt = $koneksi->prepare($sql)) {
        $stmt->bind_param('sssssss', $today, $ip, $ua, $path, $ref, $now, $now);
        $stmt->execute();
        $stmt->close();
      } else {
        error_log('mysqli prepare failed: '.$koneksi->error);
      }

    } else {
      // variabel koneksi tidak ditemukan
      error_log('DB connection not found: define $pdo (PDO) or $koneksi (mysqli) in inc/koneksi.php');
    }
  } catch (Throwable $e) {
    // supaya halaman tidak fatal jika DB error
    error_log('Log visit error: '.$e->getMessage());
  }
}
?>
<?php
$hasSearch = isset($_GET['q']) && trim($_GET['q']) !== '';
$results = [];

if ($hasSearch) {
  $q  = trim($_GET['q']);
  $kw = '%'.$q.'%';

  // contoh query – sesuaikan nama tabel/kolommu
  $sql = "SELECT id, product_name, game, category, image_url, price, status, updated_at
          FROM stocks
          WHERE COALESCE(status,'') <> 'out'
            AND (product_name LIKE ? OR game LIKE ? OR category LIKE ?)
          ORDER BY updated_at DESC
          LIMIT 50";

  if (isset($pdo) && $pdo instanceof PDO) {
    $st = $pdo->prepare($sql);
    $st->execute([$kw,$kw,$kw]);
    $results = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } elseif (isset($koneksi) && $koneksi instanceof mysqli) {
    if ($st = $koneksi->prepare($sql)) {
      $st->bind_param('sss',$kw,$kw,$kw);
      $st->execute();
      $res = $st->get_result();
      while ($r = $res->fetch_assoc()) { $results[] = $r; }
      $st->close();
    }
  }
}
?>


<?php include("./inc/header.php"); ?>
<link rel="stylesheet" href="<?= htmlspecialchars('/assets/css/home.css') ?>">
<!DOCTYPE html>
    <?php
// helper kecil
function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function rupiah($n){ return 'Rp '.number_format((float)$n,0,',','.'); }

// ---- ambil banner ----
$banners = [];
if (isset($koneksi) && $koneksi instanceof mysqli) {
  $q = $koneksi->query("SELECT id,image_url,title,link_url FROM home_banners WHERE is_active=1 ORDER BY sort ASC, id ASC");
  if ($q) { while($r=$q->fetch_assoc()){ $banners[]=$r; } $q->free(); }
} elseif (isset($pdo) && $pdo instanceof PDO) {
  $stmt = $pdo->query("SELECT id,image_url,title,link_url FROM home_banners WHERE is_active=1 ORDER BY sort ASC, id ASC");
  $banners = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

// ---- ambil list game (untuk pills) ----
$games = [];
if (isset($koneksi) && $koneksi instanceof mysqli) {
  $q = $koneksi->query("SELECT DISTINCT game FROM stocks ORDER BY game ASC");
  if ($q){ while($r=$q->fetch_assoc()){ $games[]=$r['game']; } $q->free(); }
} elseif (isset($pdo) && $pdo instanceof PDO) {
  $stmt = $pdo->query("SELECT DISTINCT game FROM stocks ORDER BY game ASC");
  $games = $stmt ? array_column($stmt->fetchAll(PDO::FETCH_ASSOC),'game') : [];
}

// ---- ambil produk terpopuler ----
// contoh: stok terbanyak dulu; sesuaikan logika "populer" versi kamu
$allCategories = [];
$sqlCat = "SELECT DISTINCT LOWER(TRIM(COALESCE(category,''))) AS cat
           FROM stocks
           WHERE COALESCE(category,'') <> ''
             AND LOWER(COALESCE(status,'')) <> 'out'
           ORDER BY cat ASC";

if (isset($koneksi) && $koneksi instanceof mysqli) {
  if ($res = $koneksi->query($sqlCat)) {
    while ($r = $res->fetch_assoc()) { $allCategories[] = $r['cat']; }
    $res->close();
  }
} elseif (isset($pdo) && $pdo instanceof PDO) {
  $stmt = $pdo->query($sqlCat);
  $allCategories = $stmt ? array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'cat') : [];
}

// Kalau user pakai ?cat=promo, kita render hanya kategori itu.
// Kalau tidak ada parameter, render semua kategori.
$requested = strtolower(trim($_GET['cat'] ?? ''));
$categoriesToShow = $requested && in_array($requested, $allCategories, true)
  ? [$requested]
  : $allCategories;

// Ambil produk per kategori (status != out)
$productsByCat = [];
$sqlItems = "SELECT id, product_name, game, image_url, currency, price, stock, status, updated_at
             FROM stocks
             WHERE LOWER(COALESCE(status,'')) <> 'out'
               AND LOWER(COALESCE(category,'')) = ?
             ORDER BY stock DESC, updated_at DESC
             LIMIT 12";

if (isset($koneksi) && $koneksi instanceof mysqli) {
  if ($stmt = $koneksi->prepare($sqlItems)) {
    foreach ($categoriesToShow as $cat) {
      $stmt->bind_param('s', $cat);
      $stmt->execute();
      $res = $stmt->get_result();
      $rows = [];
      while ($row = $res->fetch_assoc()) { $rows[] = $row; }
      $productsByCat[$cat] = $rows;
    }
    $stmt->close();
  }
} elseif (isset($pdo) && $pdo instanceof PDO) {
  $stmt = $pdo->prepare($sqlItems);
  foreach ($categoriesToShow as $cat) {
    $stmt->execute([$cat]);
    $productsByCat[$cat] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }
}
?>
<main>
  <div class="wrap">

    <!-- ====== HERO / BANNER ====== -->
    <section class="hero" id="hero">
      <div class="hero__track" id="heroTrack" style="transform:translateX(0)">
        <?php if (count($banners) === 0): ?>
          <!-- fallback jika belum ada banner -->
          <div class="hero__slide"><img src="https://i.ibb.co/Kh9LHFk/hero-all.jpg" alt="Banner"></div>
        <?php else: ?>
          <?php foreach($banners as $b): ?>
            <a class="hero__slide" href="<?= e($b['link_url'] ?: '#') ?>">
              <img src="<?= e($b['image_url']) ?>" alt="<?= e($b['title'] ?: 'Banner') ?>">
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <?php if (count($banners) > 1): ?>
        <div class="hero__nav" id="heroNav">
          <?php foreach($banners as $i=>$b): ?>
            <div class="hero__dot <?= $i===0?'is-active':'' ?>" data-idx="<?= (int)$i ?>"></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- ====== PILLS (kategori game) ====== -->
    <?php if ($games): ?>
    <div class="pills-scroll" id="gamePills">
      <button type="button" class="pill is-active" data-game="__all">Semua</button>
      <?php foreach($games as $g): ?>
        <button class="pill" data-game="<?= e($g) ?>"><?= e($g) ?></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ====== KATEGORI GAME ====== -->
<?php if ($hasSearch): ?>
  <section class="section">
    <h3 class="section-title">
      Hasil pencarian: “<?= htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8') ?>”
    </h3>

    <?php if (!$results): ?>
      <p>Tidak ada produk yang cocok.</p>
    <?php else: ?>
      <div class="cards-row" id="search-results">
        <?php foreach ($results as $p): ?>
          <article class="card" data-id="<?= (int)$p['id'] ?>">
   <div class="card__shine"></div>
            <div class="card__glow"></div>
            <div class="card__content">
              <?php if (($p['status'] ?? '') === 'in'): ?>
                <div class="card__badge">Ready</div>
              <?php elseif (($p['status'] ?? '') === 'low'): ?>
                <div class="card__badge" style="background:#f59e0b">Low</div>
              <?php else: ?>
                <div class="card__badge" style="background:#ef4444">Out</div>
              <?php endif; ?>

              <div class="card__image">
                <img
                  src="<?= e($p['image_url'] ?: 'https://i.ibb.co/Kh9LHFk/hero-all.jpg') ?>"
                  alt="<?= e($p['product_name']) ?>"
                  loading="lazy"
                  onerror="this.onerror=null;this.src='<?= e('https://i.ibb.co/Kh9LHFk/hero-all.jpg') ?>';"
                >
              </div>

              <div class="card__text">
                <p class="card__title"><?= e($p['product_name']) ?></p>
                <p class="card__description"><?= e($p['game']) ?></p>
              </div>
              <div class="card__footer">
                <div class="card__price"><?= rupiah($p['price']) ?></div>
                <div class="card__button" title="Tambah">
                  <svg height="16" width="16" viewBox="0 0 24 24">
                    <path stroke-width="2" stroke="currentColor" d="M4 12H20M12 4V20" fill="currentColor"></path>
                  </svg>
                </div>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
<?php endif; ?>


    <?php if (empty($categoriesToShow)): ?>
  <h3 class="section-title">PRODUK</h3>
  <div style="color:var(--muted)">Belum ada kategori/produk.</div>
<?php else: ?>
  <?php foreach ($categoriesToShow as $cat): 
        $rows = $productsByCat[$cat] ?? [];
        $title = strtoupper($cat);
  ?>
    <h3 class="section-title"><?= htmlspecialchars($title) ?></h3>
    <div class="cards-row" id="row-<?= e($cat) ?>">
      <?php if ($rows): ?>
        <?php foreach($rows as $p): ?>
          <article class="card" data-game="<?= e($p['game']) ?>">
            <div class="card__shine"></div>
            <div class="card__glow"></div>
            <div class="card__content">
              <?php if (($p['status'] ?? '') === 'in'): ?>
                <div class="card__badge">Ready</div>
              <?php elseif (($p['status'] ?? '') === 'low'): ?>
                <div class="card__badge" style="background:#f59e0b">Low</div>
              <?php else: ?>
                <div class="card__badge" style="background:#ef4444">Out</div>
              <?php endif; ?>

              <div class="card__image">
                <img
                  src="<?= e($p['image_url'] ?: 'https://i.ibb.co/Kh9LHFk/hero-all.jpg') ?>"
                  alt="<?= e($p['product_name']) ?>"
                  loading="lazy"
                  onerror="this.onerror=null;this.src='<?= e('https://i.ibb.co/Kh9LHFk/hero-all.jpg') ?>';"
                >
              </div>

              <div class="card__text">
                <p class="card__title"><?= e($p['product_name']) ?></p>
                <p class="card__description"><?= e($p['game']) ?></p>
              </div>
              <div class="card__footer">
                <div class="card__price"><?= rupiah($p['price']) ?></div>
                <div class="card__button" title="Tambah">
                  <svg height="16" width="16" viewBox="0 0 24 24">
                    <path stroke-width="2" stroke="currentColor" d="M4 12H20M12 4V20" fill="currentColor"></path>
                  </svg>
                </div>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php else: ?>
        <div style="color:var(--muted);padding:12px 0">Belum ada produk di kategori ini.</div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

    </div>
  </div>
</main>

<!-- JS kecil: carousel + filter pills -->
<script>
(function(){
  // Carousel (biarkan seperti punyamu)
  const track = document.getElementById('heroTrack');
  const nav = document.getElementById('heroNav');
  if (track && nav){
    const dots = Array.from(nav.querySelectorAll('.hero__dot'));
    const total = dots.length;
    let idx = 0, timer = null;

    const go = (to) => {
      idx = (to + total) % total;
      track.style.transform = 'translateX(' + (-idx*100) + '%)';
      dots.forEach((d,i)=>d.classList.toggle('is-active', i===idx));
    };
    dots.forEach((d,i)=> d.addEventListener('click', ()=>{ go(i); restart(); }));
    const restart = () => { if (timer) clearInterval(timer); timer=setInterval(()=>go(idx+1), 5000); };
    restart();
  }

  // ===== FIX: Filter pills bekerja untuk banyak .cards-row =====
  const pills = document.getElementById('gamePills');
  const rows  = Array.from(document.querySelectorAll('.cards-row')); // <-- ambil semua baris kategori

  if (pills && rows.length){
    pills.addEventListener('click', (e)=>{
      const btn = e.target.closest('.pill');
      if (!btn) return;

      // toggle aktif
      pills.querySelectorAll('.pill').forEach(p=>p.classList.remove('is-active'));
      btn.classList.add('is-active');

      const targetGame = (btn.dataset.game || '').toLowerCase(); // "__all" atau nama game

      // filter di setiap baris kategori
      rows.forEach(row=>{
        const cards = Array.from(row.querySelectorAll('.card'));
        let anyVisible = false;

        cards.forEach(card=>{
          const g = (card.dataset.game || '').toLowerCase();
          const show = (targetGame === '__all') || (g === targetGame);
          card.style.display = show ? '' : 'none';
          if (show) anyVisible = true;
        });

        // kalau semua kartu di baris ini tersembunyi, boleh sembunyikan barisnya (opsional)
        row.style.display = anyVisible ? '' : 'none';
        // kalau kamu juga ingin menyembunyikan judul section saat kosong:
        const titleEl = row.previousElementSibling;
        if (titleEl && titleEl.classList.contains('section-title')) {
          titleEl.style.display = anyVisible ? '' : 'none';
        }
      });
    });
  }
})();
</script>

  </body>
</html>
<?php include("./inc/footer.php")?>