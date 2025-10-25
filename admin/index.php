<?php 
include_once("../inc/koneksi.php");  
include_once("../inc/fungsi.php");
require __DIR__.'/../inc/koneksi.php'; // pastikan file ini membuat $pdo (PDO) atau $koneksi (mysqli)
require __DIR__.'/../inc/auth.php';
require_role(['admin','staff']); // hanya admin/staff
// Deteksi jenis koneksi
$isPDO    = isset($pdo) && $pdo instanceof PDO;
$isMySQLi = isset($koneksi) && $koneksi instanceof mysqli;

// Helper ambil 1 nilai (COUNT, SUM, dsb)
function db_scalar(string $sql): int {
  global $pdo, $koneksi, $isPDO, $isMySQLi;
  try {
    if ($isPDO) {
      $res = $pdo->query($sql);
      return (int)($res ? $res->fetchColumn() : 0);
    } elseif ($isMySQLi) {
      $res = $koneksi->query($sql);
      if (!$res) { error_log('mysqli query failed: '.$koneksi->error); return 0; }
      $row = $res->fetch_row();
      return (int)($row[0] ?? 0);
    } else {
      error_log('DB connection not found (define $pdo or $koneksi).');
      return 0;
    }
  } catch (Throwable $e) {
    error_log('db_scalar error: '.$e->getMessage());
    return 0;
  }
}

// VISITOR (unik harian = baris di access_logs untuk tanggal hari ini)
$visitors_today = db_scalar("SELECT COUNT(*) FROM access_logs WHERE visit_date = CURDATE()");

// USERS (jumlah akun dengan role 'user' — bukan admin)
$users_total = db_scalar("SELECT COUNT(*) FROM users WHERE role = 'user'");

/* 
   Jika yang kamu mau adalah:
   - total SEMUA akun:     SELECT COUNT(*) FROM users
   - hanya user AKTIF:     SELECT COUNT(*) FROM users WHERE role='user' AND status='active'
   - admin saja:           SELECT COUNT(*) FROM users WHERE role='admin'
*/

// ACTIVE (pengunjung online 5 menit terakhir; distinct IP+UA)
$active_now = db_scalar("
  SELECT COUNT(DISTINCT CONCAT(ip,'|',user_agent))
  FROM access_logs
  WHERE last_seen >= NOW() - INTERVAL 5 MINUTE
");

function db_rows_rev(string $sql): array {
  global $pdo,$koneksi,$isPDO,$isMySQLi;
  try {
    if ($isPDO) {
      $st = $pdo->query($sql);
      return $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
    } elseif ($isMySQLi) {
      $out = [];
      if ($r = $koneksi->query($sql)) {
        while ($row = $r->fetch_assoc()) $out[] = $row;
      }
      return $out;
    }
  } catch (Throwable $e) { error_log($e->getMessage()); }
  return [];
}
function db_count_rev(string $sql): int {
  global $pdo,$koneksi,$isPDO,$isMySQLi;
  try {
    if ($isPDO)    { return (int)($pdo->query($sql)->fetchColumn() ?? 0); }
    if ($isMySQLi) { $r=$koneksi->query($sql); $row=$r?$r->fetch_row():[0]; return (int)($row[0]??0); }
  } catch (Throwable $e) { error_log($e->getMessage()); }
  return 0;
}

// data untuk tabel
$total_rows = db_count_rev("SELECT COUNT(*) FROM revenue");
$rows = db_rows_rev("
  SELECT order_code, buyer_name, product_name, qty, unit_price, subtotal, payment_channel, status, created_at
  FROM revenue
  ORDER BY created_at DESC
  LIMIT 20
");
?>

<!DOCTYPE html>
<html lang="en">
  <head>
        <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>VAZATECH · Admin · Dashboard</title>

    <title>Dashboard · Admin</title>

<?php include("../inc/hdradmin.php")?>
    <main class="container">
      <div class="sidebar">
        <a href="#" class="active"><i class="fas fa-home"></i>Dashboard</a>
        <a href="stocks"><i class="fas fa-box"></i>Stocks</a>
        <a href="users"><i class="fas fa-users"></i>Users</a>
        <a href="orders"><i class="fas fa-shopping-cart"></i>Orders</a>


        <a href="banners"><i class="fas fa-image"></i>Banners</a>

        <a href="blog-list"><i class="fas fa-newspaper"></i>Blog</a>
      </div>
      <div class="content">
        <!-- Header Dashboard -->
        <div class="dashboard-header">
          <h2>Dashboard</h2>
          <div class="cards-row">
            <div class="card kpi">
              <div class="kpi-title">Visitor</div>
              <div class="kpi-value"><?= number_format($visitors_today) ?></div>
            </div>
            <div class="card kpi">
              <div class="kpi-title">Users</div>
              <div class="kpi-value"><?= number_format($users_total) ?></div>
            </div>
            <div class="card kpi">
              <div class="kpi-title">Active</div>
              <div class="kpi-value"><?= number_format($active_now) ?></div>
            </div>
          </div>
        </div>

        <!-- Charts -->
<div class="charts-grid">
  <div class="card chart">
    <div class="kpi-title">Transaksi / Hari</div>
    <div class="chart-box"><canvas id="chartTx"></canvas></div>
  </div>

  <div class="card chart">
    <div class="kpi-title">Pendapatan / Hari</div>
    <div class="chart-box"><canvas id="chartRevenue"></canvas></div>
  </div>

  <div class="card chart">
    <div class="kpi-title">Qty Terjual / Hari</div>
    <div class="chart-box"><canvas id="chartQty"></canvas></div>
  </div>
</div>

      </div>
          <!-- Script: Chart.js + file JS kamu -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3"></script>

<script>
  window.REVENUE_API_URL   = '/topupweb/assets/api/revenue_series.php?days=30';
  window.REVENUE_KEEP_DAYS = 14;   // tampilkan 14 hari terakhir
  window.REVENUE_REFRESH_MS = 10000;
</script>
<script src="../assets/js/revenue-charts.js"></script>

<script>
/* /assets/js/revenue-charts.js — BAR + DEBUG, DOM-ready */
document.addEventListener('DOMContentLoaded', () => {
  const API =
    (typeof window !== 'undefined' && window.REVENUE_API_URL) ||
    document.currentScript?.dataset?.api ||
    '../assets/api/revenue_series.php?days=30';

  const REFRESH_MS =
    (typeof window !== 'undefined' && window.REVENUE_REFRESH_MS) || 10000;

  const elTx  = document.getElementById('chartTx');
  const elRev = document.getElementById('chartRevenue');
  const elQty = document.getElementById('chartQty');

  if (!elTx || !elRev || !elQty) {
    console.warn('[charts] canvas tidak ditemukan');
    return;
  }

  const fmtIdr = v => 'Rp ' + (Number(v) || 0).toLocaleString('id-ID');

  const makeOpts = (isMoney, suggestedMax) => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 0 },
    plugins: {
      legend: { display: false },
      tooltip: {
        mode: 'index',
        intersect: false,
        callbacks: isMoney ? { label: ctx => fmtIdr(ctx.parsed.y) } : undefined,
      },
    },
    interaction: { mode: 'index', intersect: false },
    scales: {
      x: { grid: { display: false }, ticks: { maxRotation: 55 } },
      y: {
        beginAtZero: true,
        suggestedMax,
        ticks: isMoney ? { callback: v => fmtIdr(v) } : undefined,
      },
    },
  });

  const txChart = new Chart(elTx, {
    type: 'bar',
    data: { labels: [], datasets: [{
      label: 'Transaksi',
      data: [],
      backgroundColor: '#2563eb',
      borderWidth: 0,
      borderRadius: 6,
      maxBarThickness: 28,
      categoryPercentage: 0.7,
      barPercentage: 0.9,
    }]},
    options: makeOpts(false, 5),
  });

  const revenueChart = new Chart(elRev, {
    type: 'bar',
    data: { labels: [], datasets: [{
      label: 'Pendapatan',
      data: [],
      backgroundColor: '#10b981',
      borderWidth: 0,
      borderRadius: 6,
      maxBarThickness: 28,
      categoryPercentage: 0.7,
      barPercentage: 0.9,
    }]},
    options: makeOpts(true, 100000),
  });

  const qtyChart = new Chart(elQty, {
    type: 'bar',
    data: { labels: [], datasets: [{
      label: 'Qty',
      data: [],
      backgroundColor: '#f59e0b',
      borderWidth: 0,
      borderRadius: 6,
      maxBarThickness: 28,
      categoryPercentage: 0.7,
      barPercentage: 0.9,
    }]},
    options: makeOpts(false, 5),
  });

  function overlayMsg(el, msg) {
    const p = el.parentElement; if (!p) return;
    p.style.position = 'relative';
    const note = document.createElement('div');
    note.textContent = msg;
    note.style.cssText =
      'position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#ef4444;font-weight:700;background:transparent';
    p.appendChild(note);
  }

  function applyData(json) {
    console.log('[charts] data:', json); // <— lihat di DevTools
    const labels = (json.labels || []);
    const tx  = (json.tx || []).map(Number);
    const rev = (json.revenue || []).map(Number);
    const qty = (json.qty || []).map(Number);

    // cek data kosong
    const hasAny =
      labels.length &&
      (tx.some(v => v > 0) || rev.some(v => v > 0) || qty.some(v => v > 0));

    if (!labels.length) {
      overlayMsg(elTx,  'Tidak ada label dari API');
      overlayMsg(elRev, 'Tidak ada label dari API');
      overlayMsg(elQty, 'Tidak ada label dari API');
      return;
    }
    if (!hasAny) {
      overlayMsg(elTx,  'Tidak Ada Data');
      overlayMsg(elRev, 'Tidak Ada Data');
      overlayMsg(elQty, 'Tidak Ada Data');
    }

    const maxTx  = Math.max(1, ...tx);
    const maxRev = Math.max(1, ...rev);
    const maxQty = Math.max(1, ...qty);

    txChart.options.scales.y.suggestedMax      = Math.ceil(maxTx  * 1.25);
    revenueChart.options.scales.y.suggestedMax = Math.ceil(maxRev * 1.25);
    qtyChart.options.scales.y.suggestedMax     = Math.ceil(maxQty * 1.25);

    txChart.data.labels = revenueChart.data.labels = qtyChart.data.labels = labels;
    txChart.data.datasets[0].data  = tx;
    revenueChart.data.datasets[0].data = rev;
    qtyChart.data.datasets[0].data = qty;

    txChart.update();
    revenueChart.update();
    qtyChart.update();
  }

  async function loadAndRender() {
    try {
      const res = await fetch(API, { cache: 'no-store' });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const json = await res.json();
      applyData(json);
    } catch (e) {
      console.error('[charts] fetch error:', e);
      overlayMsg(elTx,  'Gagal memuat data');
      overlayMsg(elRev, 'Gagal memuat data');
      overlayMsg(elQty, 'Gagal memuat data');
    }
  }

  loadAndRender();
  if (REFRESH_MS > 0) setInterval(loadAndRender, REFRESH_MS);
});
</script>

    </main>
    <footer></footer>
  </body>
</html>
