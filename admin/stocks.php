<?php
require __DIR__.'/../inc/auth.php';
require_role(['admin','staff']); // hanya admin/staff
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Stocks 路 Admin</title>
<?php include("../inc/hdradmin.php")?>

    <main class="container">
      <!-- Sidebar -->
      <aside class="sidebar">
        <a href="index"><i class="fas fa-home"></i>Dashboard</a>
        <a href="#" class="active"><i class="fas fa-box"></i>Stocks</a>
        <a href="users"><i class="fas fa-users"></i>Users</a>
        <a href="orders"><i class="fas fa-shopping-cart"></i>Orders</a>
        <a href="banners"><i class="fas fa-image"></i>Banners</a>
        <a href="blog-list"><i class="fas fa-newspaper"></i>Blog</a>
      </aside>

      <!-- Content -->
      <section class="content">
        <!-- Header + KPI -->
        <div class="dashboard-header">
          <h2>Stocks</h2>
          <div class="cards-row">
  <div class="card kpi">
    <div class="kpi-title">Total SKU</div>
    <div class="kpi-value" id="kpiTotal">0</div>
  </div>
  <div class="card kpi">
    <div class="kpi-title">Low Stock</div>
    <div class="kpi-value" id="kpiLow">0</div>
  </div>
  <div class="card kpi">
    <div class="kpi-title">Out of Stock</div>
    <div class="kpi-value" id="kpiOut">0</div>
  </div>
</div>

        </div>

        <!-- Panel utama: Toolbar + Tabel -->
        <div class="card panel">
          <div class="panel-toolbar">
            <div class="scroll-inner">
              <div class="left">
                <div class="control">
                  <i class="fa fa-magnifying-glass"></i>
                  <input
                    type="text"
                    class="input"
                    placeholder="Cari produk / game / kode"
                  />
                </div>
                <select class="select">
                  <option value="">Semua Game</option>
                  <option>Mobile Legends</option>
                  <option>Free Fire</option>
                  <option>Genshin Impact</option>
                  <option>Valorant</option>
                </select>
                <select class="select">
                  <option value="">Semua Status</option>
                  <option value="in">In Stock</option>
                  <option>Low</option>
                  <option>Out</option>
                </select>
              </div>
              <div class="right">
                <button class="btn btn-secondary">
                  <i class="fa fa-file-import"></i> Import CSV
                </button>
                <button class="btn btn-primary">
                  <i class="fa fa-plus"></i> Tambah Stock
                </button>
              </div>
            </div>

            <div class="table-wrap">
              <div class="scroll-inner">
                <table class="table">
                  <thead>
                    <tr>
                      <th>Produk</th>
                      <th>Game</th>
                      <th>Gambar</th>
                      <th>Kategori</th>
                      <th>Mata Uang</th>
                      <th>Harga</th>
                      <th>Stock</th>
                      <th>Min Stock</th>
                      <th>Status</th>
                      <th>Terakhir Update</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody id="stockRows"></tbody>
                </table>
              </div>
            </div>

            <div class="legend">
              <span class="status-chip in">In Stock</span>
              <span class="status-chip low">Low</span>
              <span class="status-chip out">Out</span>
            </div>
          </div>
        </div>
      </section>
    </main>
    <footer class="footer"></footer>
    <!-- Modal Overlay -->
<div id="modalOverlay" class="modal-overlay" hidden></div>

<!-- Modal Form Add/Edit -->
<div id="stockModal" class="modal" hidden>
  <div class="modal-box">
    <h3 id="modalTitle">Tambah Stock</h3>
    <form id="stockForm">
      <input type="hidden" name="id" id="f_id">

      <div class="grid2">
        <label>Produk
          <input type="text" name="product_name" id="f_product" required>
        </label>
        <label>Game
          <input type="text" name="game" id="f_game" required>
        </label>
        <label>Image URL
        <input type="url" name="image_url" id="f_image" placeholder="https://... (imgbb, dll)">
        </label>
        <label>Kategori
        <input type="text" name="category" id="f_category" placeholder="mis. terpopuler / promo / terbaru">
        </label>
        <label>Mata Uang
          <input type="text" name="currency" id="f_currency" placeholder="DM / VP / GC / IDR" required>
        </label>
        <label>Harga (Rp)
          <input type="number" min="0" name="price" id="f_price" required>
        </label>
        <label>Stock
          <input type="number" min="0" name="stock" id="f_stock" required>
        </label>
        <label>Min Stock
          <input type="number" min="0" name="min_stock" id="f_min" required>
        </label>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn" id="btnCancel">Batal</button>
        <button type="submit" class="btn btn-primary" id="btnSave">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Delete -->
<div id="delModal" class="modal" hidden>
  <div class="modal-box">
    <h3>Hapus Stock</h3>
    <p id="delText">Yakin ingin menghapus?</p>
    <div class="modal-actions">
      <button type="button" class="btn" id="btnDelCancel">Batal</button>
      <button type="button" class="btn btn-danger" id="btnDelYes">Hapus</button>
    </div>
  </div>
</div>
<script>
const API = '../assets/api/stocks.php';

const elRows = document.getElementById('stockRows');
const elSearch = document.querySelector('.panel .control .input');
const elGame   = document.querySelectorAll('.panel .select')[0];
const elStatus = document.querySelectorAll('.panel .select')[1];

const btnAdd = document.querySelector('.btn.btn-primary');        // "Tambah Stock"
const btnImport = document.querySelector('.btn.btn-secondary');   // "Import CSV" (opsional)

const modalOverlay = document.getElementById('modalOverlay');
const stockModal   = document.getElementById('stockModal');
const delModal     = document.getElementById('delModal');

const f_id = document.getElementById('f_id');
const f_product = document.getElementById('f_product');
const f_game = document.getElementById('f_game');
const f_currency = document.getElementById('f_currency');
const f_price = document.getElementById('f_price');
const f_stock = document.getElementById('f_stock');
const f_min = document.getElementById('f_min');
const f_image = document.getElementById('f_image');
const f_category = document.getElementById('f_category');

const stockForm = document.getElementById('stockForm');
const btnCancel = document.getElementById('btnCancel');

const delText   = document.getElementById('delText');
const btnDelYes = document.getElementById('btnDelYes');
const btnDelCancel = document.getElementById('btnDelCancel');

let delId = null;

function rupiah(n){
  n = Number(n||0);
  return 'Rp ' + n.toLocaleString('id-ID');
}
function statusChip(s){
  const map = {in:'in', low:'low', out:'out'};
  const cls = map[s] || 'in';
  const text = s==='out'?'Out': (s==='low'?'Low':'In Stock');
  return `<span class="status-chip ${cls}">${text}</span>`;
}
function tr(r){
  const thumb = r.image_url
    ? `<img src="${escapeHtml(r.image_url)}" alt="" onerror="this.style.opacity=0.2"
             style="width:44px;height:28px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;">`
    : `<div style="width:44px;height:28px;border-radius:6px;background:#eef2ff;border:1px solid #e5e7eb"></div>`;

  return `<tr>
    <td>${escapeHtml(r.product_name)}</td>
    <td>${escapeHtml(r.game)}</td>
    <td>${thumb}</td>
    <td>${escapeHtml(r.category || '')}</td>
    <td>${escapeHtml(r.currency)}</td>
    <td>${rupiah(r.price)}</td>
    <td>${r.stock}</td>
    <td>${r.min_stock}</td>
    <td>${statusChip(r.status)}</td>
    <td>${(r.updated_at||'').replace('T',' ')}</td>
    <td>
      <button class="btn btn-ghost" title="Edit" onclick="openEdit(${r.id})"><i class="fa fa-pen"></i></button>
      <button class="btn btn-ghost" title="Hapus" onclick="openDelete(${r.id}, '${escapeHtml(r.product_name)}')"><i class="fa fa-ellipsis-v"></i></button>
    </td>
  </tr>`;
}
function escapeHtml(s){return String(s||'').replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));}

async function api(url, opt={}){
  const r = await fetch(url, {headers:{'X-Requested-With':'fetch'}, ...opt});
  return r.json();
}

async function loadList(){
  const q = encodeURIComponent(elSearch.value.trim());
  const game = encodeURIComponent(elGame.value || '');
  const status = encodeURIComponent(elStatus.value || '');
  const rows = await api(`${API}?action=list&q=${q}&game=${game}&status=${status}`);
  elRows.innerHTML = rows.map(tr).join('') || `<tr><td colspan="9" style="text-align:center; color:#6b7280; padding:16px;">Tidak ada data</td></tr>`;
}

let _t; elSearch.addEventListener('input', ()=>{ clearTimeout(_t); _t = setTimeout(loadList, 300); });
elGame.addEventListener('change', loadList);
elStatus.addEventListener('change', loadList);

/* ===== Modal helpers ===== */
function show(el){ el.hidden=false; modalOverlay.hidden=false; }
function hide(el){ el.hidden=true;  modalOverlay.hidden=true; }

/* ===== Add ===== */
btnAdd.addEventListener('click', ()=>{
  document.getElementById('modalTitle').textContent = 'Tambah Stock';
  stockForm.reset();
  f_id.value = '';
  show(stockModal);
});
btnCancel.addEventListener('click', ()=> hide(stockModal));

/* ===== Edit ===== */
window.openEdit = async function(id){
  const row = await api(`${API}?action=show&id=${id}`);
  if (!row || !row.id) return;
  document.getElementById('modalTitle').textContent = 'Edit Stock';
  f_id.value       = row.id;
  f_product.value  = row.product_name;
  f_game.value     = row.game;
  f_image.value    = row.image_url || '';
  f_category.value = row.category  || '';
  f_currency.value = row.currency;
  f_price.value    = row.price;
  f_stock.value    = row.stock;
  f_min.value      = row.min_stock;
  show(stockModal);
};


/* ===== Delete ===== */
window.openDelete = function(id, name){
  delId = id;
  delText.textContent = `Hapus "${name}"? Tindakan ini tidak bisa dibatalkan.`;
  show(delModal);
};
btnDelCancel.addEventListener('click', ()=> hide(delModal));
btnDelYes.addEventListener('click', async ()=>{
  if (!delId) return;
  await api(API, { method:'POST', body: new URLSearchParams({action:'delete', id: delId})});
  delId = null;
  hide(delModal);
  loadList();
});

/* ===== Submit Add/Edit ===== */
stockForm.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const form = new FormData(stockForm);
  const id = form.get('id');
  const action = id ? 'update' : 'create';
  form.append('action', action);
  await fetch(API, { method:'POST', body: form });
  hide(stockModal);
  loadList();
});

/* boot */
loadList();
</script>
<script>
  const KPI_API = '../assets/api/stocks_kpi.php';
  const KPI_POLL_MS = 7000; // refresh tiap 7 detik

  const elKpiTotal = document.getElementById('kpiTotal');
  const elKpiLow   = document.getElementById('kpiLow');
  const elKpiOut   = document.getElementById('kpiOut');

  async function loadKPI(){
    try {
      const r = await fetch(KPI_API, {cache:'no-store'});
      const j = await r.json();
      elKpiTotal.textContent = (j.total ?? 0).toLocaleString('id-ID');
      elKpiLow.textContent   = (j.low   ?? 0).toLocaleString('id-ID');
      elKpiOut.textContent   = (j.out   ?? 0).toLocaleString('id-ID');
    } catch(e) {
      // diamkan saja, agar tidak ganggu UI
      // console.error(e);
    }
  }

  // panggil saat halaman load & polling berkala
  loadKPI();
  setInterval(loadKPI, KPI_POLL_MS);

  // ==== Integrasi dengan CRUD yang sudah ada ====
  // panggil loadKPI() setelah create/update/delete agar langsung segar
  const _oldSubmit = stockForm.onsubmit;
  stockForm.addEventListener('submit', async (e)=>{
    // handler submit kamu sudah ada; setelah loadList(), panggil:
    setTimeout(loadKPI, 200); // beri jeda kecil
  });

  const _oldDelYes = btnDelYes.onclick;
  btnDelYes.addEventListener('click', ()=>{
    setTimeout(loadKPI, 200);
  });
</script>

  </body>
</html>
