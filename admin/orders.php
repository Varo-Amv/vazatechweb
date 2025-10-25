<?php
require __DIR__.'/../inc/auth.php';
require_role(['admin','staff']); // hanya admin/staff
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Orders · Admin</title>

   <?php include("../inc/hdradmin.php")?>

    <main class="container">
      <!-- Sidebar -->
      <aside class="sidebar">
        <a href="index"><i class="fas fa-home"></i>Dashboard</a>
        <a href="stocks"><i class="fas fa-box"></i>Stocks</a>
        <a href="users"><i class="fas fa-users"></i>Users</a>
        <a href="#" class="active"
          ><i class="fas fa-shopping-cart"></i>Orders</a
        >
        <a href="banners"><i class="fas fa-image"></i>Banners</a>
        <a href="blog-list"><i class="fas fa-newspaper"></i>Blog</a>
      </aside>

      <!-- Content -->
      <section class="content">
        <!-- Header + KPI -->
        <div class="dashboard-header">
          <h2>Orders</h2>
          <div class="cards-row">
            <div class="card kpi">
              <div class="kpi-title">Orders Today</div>
              <div class="kpi-value">27</div>
            </div>
            <div class="card kpi">
              <div class="card kpi kpi--revenue">
                <div class="kpi-title">Revenue Today</div>
                <div class="kpi-value">
                  <span class="currency">Rp</span
                  ><span class="amount">3,420,000</span>
                </div>
              </div>
            </div>
            <div class="card kpi">
              <div class="kpi-title">Pending Payments</div>
              <div class="kpi-value">4</div>
            </div>
          </div>
        </div>

        <!-- Panel utama: Toolbar + Tabel Orders -->
        <div class="card panel">
          <div class="panel-toolbar">
            <div class="left">
              <div class="control">
                <i class="fa fa-magnifying-glass"></i>
                <input
                  type="text"
                  class="input"
                  placeholder="Cari kode / nama / email / game / produk"
                />
              </div>
              <input type="date" class="input input-date" value="2025-08-24" />
              <span class="sep">—</span>
              <input type="date" class="input input-date" value="2025-08-28" />
              <select class="select">
                <option value="">Semua Status</option>
                <option>pending</option>
                <option>paid</option>
                <option>processed</option>
                <option>success</option>
                <option>failed</option>
                <option>expired</option>
                <option>cancelled</option>
              </select>
              <select class="select">
                <option value="">Semua Channel</option>
                <option>QRIS</option>
                <option>OVO</option>
                <option>GoPay</option>
                <option>DANA</option>
                <option>BCA VA</option>
              </select>
            </div>
            <div class="right">
              <button class="btn btn-secondary">
                <i class="fa fa-file-export"></i> Export CSV
              </button>
              <button class="btn btn-primary">
                <i class="fa fa-plus"></i> Buat Order
              </button>
            </div>
          </div>

          <div class="table-wrap">
            <div class="scroll-inner">
              <table class="orders-table">
                <thead>
                  <tr>
                    <th>Kode</th>
                    <th>Pembeli</th>
                    <th>Game / Produk</th>
                    <th>Qty</th>
                    <th>Amount</th>
                    <th>Channel</th>
                    <th>Status</th>
                    <th>Dibuat</th>
                    <th>Update</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><code class="mono">ABC12345</code></td>
                    <td>
                      <div class="user-cell">
                        <div class="avatar">RP</div>
                        <div class="meta">
                          <div class="name">Raka Pratama</div>
                          <div class="sub">raka@example.com</div>
                        </div>
                      </div>
                    </td>
                    <td>Mobile Legends — 172 Diamonds</td>
                    <td>1</td>
                    <td><span class="amount">Rp 40.000</span></td>
                    <td><span class="pay-chip qris">QRIS</span></td>
                    <td><span class="status-chip success">success</span></td>
                    <td>2025-08-28 10:10</td>
                    <td>2025-08-28 10:15</td>
                    <td>
                      <button class="btn btn-ghost" title="Lihat">
                        <i class="fa fa-eye"></i>
                      </button>
                      <button class="btn btn-ghost" title="Edit">
                        <i class="fa fa-pen"></i>
                      </button>
                      <button class="btn btn-ghost" title="More">
                        <i class="fa fa-ellipsis-v"></i>
                      </button>
                    </td>
                  </tr>

                  <tr>
                    <td><code class="mono">XYZ99001</code></td>
                    <td>
                      <div class="user-cell">
                        <div class="avatar">SN</div>
                        <div class="meta">
                          <div class="name">Sinta Nabila</div>
                          <div class="sub">sinta@example.com</div>
                        </div>
                      </div>
                    </td>
                    <td>Free Fire — 140 Diamonds</td>
                    <td>1</td>
                    <td><span class="amount">Rp 27.000</span></td>
                    <td><span class="pay-chip ovo">OVO</span></td>
                    <td><span class="status-chip pending">pending</span></td>
                    <td>2025-08-28 09:30</td>
                    <td>2025-08-28 09:41</td>
                    <td>
                      <button class="btn btn-ghost" title="Lihat">
                        <i class="fa fa-eye"></i>
                      </button>
                      <button class="btn btn-ghost" title="Edit">
                        <i class="fa fa-pen"></i>
                      </button>
                      <button class="btn btn-ghost" title="More">
                        <i class="fa fa-ellipsis-v"></i>
                      </button>
                    </td>
                  </tr>

                  <tr>
                    <td><code class="mono">GP778899</code></td>
                    <td>
                      <div class="user-cell">
                        <div class="avatar">AL</div>
                        <div class="meta">
                          <div class="name">Andi Lazuardi</div>
                          <div class="sub">andi@example.com</div>
                        </div>
                      </div>
                    </td>
                    <td>Genshin Impact — 300 Crystals</td>
                    <td>1</td>
                    <td><span class="amount">Rp 75.000</span></td>
                    <td><span class="pay-chip gopay">GoPay</span></td>
                    <td>
                      <span class="status-chip processed">processed</span>
                    </td>
                    <td>2025-08-27 17:55</td>
                    <td>2025-08-27 18:03</td>
                    <td>
                      <button class="btn btn-ghost" title="Lihat">
                        <i class="fa fa-eye"></i>
                      </button>
                      <button class="btn btn-ghost" title="Edit">
                        <i class="fa fa-pen"></i>
                      </button>
                      <button class="btn btn-ghost" title="More">
                        <i class="fa fa-ellipsis-v"></i>
                      </button>
                    </td>
                  </tr>

                  <tr>
                    <td><code class="mono">VR112233</code></td>
                    <td>
                      <div class="user-cell">
                        <div class="avatar">RP</div>
                        <div class="meta">
                          <div class="name">Raka Pratama</div>
                          <div class="sub">raka@example.com</div>
                        </div>
                      </div>
                    </td>
                    <td>Valorant — 700 VP</td>
                    <td>1</td>
                    <td><span class="amount">Rp 80.000</span></td>
                    <td><span class="pay-chip bcava">BCA VA</span></td>
                    <td><span class="status-chip failed">failed</span></td>
                    <td>2025-08-27 12:01</td>
                    <td>2025-08-27 12:10</td>
                    <td>
                      <button class="btn btn-ghost" title="Lihat">
                        <i class="fa fa-eye"></i>
                      </button>
                      <button class="btn btn-ghost" title="Edit">
                        <i class="fa fa-pen"></i>
                      </button>
                      <button class="btn btn-ghost" title="More">
                        <i class="fa fa-ellipsis-v"></i>
                      </button>
                    </td>
                  </tr>

                  <tr>
                    <td><code class="mono">SW551122</code></td>
                    <td>
                      <div class="user-cell">
                        <div class="avatar">SN</div>
                        <div class="meta">
                          <div class="name">Sinta Nabila</div>
                          <div class="sub">sinta@example.com</div>
                        </div>
                      </div>
                    </td>
                    <td>Steam Wallet (IDR) — IDR 120.000</td>
                    <td>1</td>
                    <td><span class="amount">Rp 120.000</span></td>
                    <td><span class="pay-chip dana">DANA</span></td>
                    <td><span class="status-chip expired">expired</span></td>
                    <td>2025-08-25 08:00</td>
                    <td>2025-08-25 08:02</td>
                    <td>
                      <button class="btn btn-ghost" title="Lihat">
                        <i class="fa fa-eye"></i>
                      </button>
                      <button class="btn btn-ghost" title="Edit">
                        <i class="fa fa-pen"></i>
                      </button>
                      <button class="btn btn-ghost" title="More">
                        <i class="fa fa-ellipsis-v"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="legend">
            <span class="status-chip pending">pending</span>
            <span class="status-chip paid">paid</span>
            <span class="status-chip processed">processed</span>
            <span class="status-chip success">success</span>
            <span class="status-chip failed">failed</span>
            <span class="status-chip expired">expired</span>
            <span class="status-chip cancelled">cancelled</span>
          </div>
        </div>
      </section>
    </main>
    <footer class="footer"></footer>
    <script>
const API = '../assets/api/orders.php';

// ambil referensi kontrol filter dari markup kamu
const elQ       = document.querySelector('.panel .control .input');
const elFrom    = document.querySelectorAll('.panel .input-date')[0];
const elTo      = document.querySelectorAll('.panel .input-date')[1];
const elStatus  = document.querySelectorAll('.panel .select')[0];
const elChannel = document.querySelectorAll('.panel .select')[1];
const tbody     = document.querySelector('.orders-table tbody');

// KPI (yang di header)
const kOrders  = document.querySelector('.cards-row .card.kpi .kpi-value');            // pertama
const kRevenue = document.querySelector('.kpi--revenue .amount');                      // angka saja
const kPend    = document.querySelectorAll('.cards-row .card.kpi .kpi-value')[2];      // ketiga

function rupiah(x){ x=Number(x||0); return 'Rp ' + x.toLocaleString('id-ID'); }
function chipStatus(s){ return `<span class="status-chip ${s}">${s}</span>`; }
function chipPay(c){
  const cls = (c||'').toLowerCase().replace(/\s+/g,'');
  return `<span class="pay-chip ${cls}">${c||''}</span>`;
}
function avatarInit(name){
  const init = (name||'?').trim().split(/\s+/).slice(0,2).map(w=>w[0]).join('').toUpperCase();
  return `<div class="avatar">${init}</div>`;
}
function rowTpl(r){
  return `<tr>
    <td><code class="mono">${r.order_code||''}</code></td>
    <td>
      <div class="user-cell">
        ${avatarInit(r.buyer_name)}
        <div class="meta">
          <div class="name">${escapeHtml(r.buyer_name||'')}</div>
          <div class="sub">${escapeHtml(r.buyer_email||'')}</div>
        </div>
      </div>
    </td>
    <td>${escapeHtml(r.product_name||'')}</td>
    <td>${r.qty||0}</td>
    <td><span class="amount">${rupiah(r.subtotal||0)}</span></td>
    <td>${chipPay(r.payment_channel||'')}</td>
    <td>${chipStatus(r.status||'pending')}</td>
    <td>${fmt(r.created_at)}</td>
    <td>${fmt(r.updated_at)}</td>
    <td>
      <button class="btn btn-ghost" title="Lihat"><i class="fa fa-eye"></i></button>
      <button class="btn btn-ghost" title="Edit"><i class="fa fa-pen"></i></button>
      <button class="btn btn-ghost" title="More"><i class="fa fa-ellipsis-v"></i></button>
    </td>
  </tr>`;
}
function escapeHtml(s){return String(s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));}
function fmt(s){ return s ? String(s).replace('T',' ') : '—'; }

async function api(url){ const r = await fetch(url, {headers:{'X-Requested-With':'fetch'}}); return r.json(); }

async function loadList(){
  const q  = encodeURIComponent(elQ.value.trim());
  const df = encodeURIComponent(elFrom.value || '');
  const dt = encodeURIComponent(elTo.value   || '');
  const st = encodeURIComponent(elStatus.value || 'all');
  const ch = encodeURIComponent(elChannel.value || 'all');

  const rows = await api(`${API}?action=list&q=${q}&from=${df}&to=${dt}&status=${st}&channel=${ch}`);
  tbody.innerHTML = (rows && rows.length)
    ? rows.map(rowTpl).join('')
    : `<tr><td colspan="10" style="text-align:center; color:#6b7280; padding:16px;">Tidak ada data</td></tr>`;
}

async function loadKPI(){
  const s = await api(`${API}?action=stats`);
  if (kOrders)  kOrders.textContent  = (s.orders_today||0).toLocaleString('id-ID');
  if (kRevenue) kRevenue.textContent = (s.revenue_today||0).toLocaleString('id-ID');
  if (kPend)    kPend.textContent    = (s.pending_today||0).toLocaleString('id-ID');
}

// events
let t; elQ.addEventListener('input', ()=>{ clearTimeout(t); t=setTimeout(loadList, 350); });
[elFrom, elTo, elStatus, elChannel].forEach(el => el.addEventListener('change', loadList));

// boot
loadList();
loadKPI();
setInterval(loadKPI, 8000); // refresh KPI tiap 8 detik (realtime ringan)
</script>
  </body>
</html>
