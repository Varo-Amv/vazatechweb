<?php
// admin/banners.php
require __DIR__.'/../inc/auth.php';
require_role(['admin','staff']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Banners · Admin</title>
  <?php include("../inc/hdradmin.php")?>
</head>
<body>

<main class="container">
  <!-- Sidebar -->
  <aside class="sidebar">
    <a href="index"><i class="fas fa-home"></i>Dashboard</a>
    <a href="stocks"><i class="fas fa-box"></i>Stocks</a>
    <a href="users"><i class="fas fa-users"></i>Users</a>
    <a href="orders"><i class="fas fa-shopping-cart"></i>Orders</a>
    <a href="#" class="active"><i class="fas fa-image"></i>Banners</a>
    <a href="blog-list"><i class="fas fa-newspaper"></i>Blog</a>
  </aside>

  <!-- Content -->
  <section class="content">
    <div class="dashboard-header">
      <h2>Banners</h2>
      <div class="cards-row">
        <div class="card kpi">
          <div class="kpi-title">Aktif</div>
          <div class="kpi-value" id="kpiActive">0</div>
        </div>
        <div class="card kpi">
          <div class="kpi-title">Total</div>
          <div class="kpi-value" id="kpiTotal">0</div>
        </div>
      </div>
    </div>

    <div class="card panel">
      <div class="panel-toolbar">
        <div class="scroll-inner">
          <div class="left">
            <div class="control">
              <i class="fa fa-magnifying-glass"></i>
              <input type="text" class="input" id="q" placeholder="Cari judul / tautan">
            </div>
            <select class="select" id="filActive">
              <option value="">Semua</option>
              <option value="1">Aktif</option>
              <option value="0">Nonaktif</option>
            </select>
          </div>
          <div class="right">
            <button class="btn btn-primary" id="btnAdd"><i class="fa fa-plus"></i> Tambah Banner</button>
          </div>
        </div>

        <div class="table-wrap">
          <div class="scroll-inner">
            <table class="table">
              <thead>
                <tr>
                  <th>Preview</th>
                  <th>Judul</th>
                  <th>Tautan</th>
                  <th>Urutan</th>
                  <th>Status</th>
                  <th>Update</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="rows"></tbody>
            </table>
          </div>
        </div>

        <div class="legend">
          <span class="status-chip in">Aktif</span>
          <span class="status-chip out">Nonaktif</span>
        </div>
      </div>
    </div>
  </section>
</main>

<!-- Overlay -->
<div id="ovl" class="modal-overlay" hidden></div>

<!-- Modal Add/Edit -->
<div id="dlg" class="modal" hidden>
  <div class="modal-box">
    <h3 id="dlgTitle">Tambah Banner</h3>
    <form id="frm">
      <input type="hidden" name="id" id="f_id">
      <div class="grid2">
        <label>Judul
          <input type="text" name="title" id="f_title" required>
        </label>
        <label>Tautan (opsional)
          <input type="url" name="link_url" id="f_link" placeholder="https://...">
        </label>
        <label>Urutan
          <input type="number" name="sort" id="f_sort" value="0" required>
        </label>
        <label>Status
          <select name="is_active" id="f_active">
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
          </select>
        </label>
        <label>Image URL (opsional)
          <input type="url" name="image_url" id="f_imgurl" placeholder="https://...">
        </label>
        <label>Upload Gambar (opsional)
          <input type="file" name="image_file" id="f_imgfile" accept="image/*">
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
<div id="dlgDel" class="modal" hidden>
  <div class="modal-box">
    <h3>Hapus Banner</h3>
    <p id="delText">Yakin ingin menghapus?</p>
    <div class="modal-actions">
      <button type="button" class="btn" id="btnDelCancel">Batal</button>
      <button type="button" class="btn btn-danger" id="btnDelYes">Hapus</button>
    </div>
  </div>
</div>

<script>
const API = '../assets/api/banners.php';

const $ = sel => document.querySelector(sel);
const rows   = $('#rows');
const q      = $('#q');
const filAct = $('#filActive');
const btnAdd = $('#btnAdd');

const ovl = $('#ovl'), dlg = $('#dlg'), dlgTitle = $('#dlgTitle'), frm = $('#frm');
const f_id = $('#f_id'), f_title = $('#f_title'), f_link = $('#f_link'), f_sort = $('#f_sort'),
      f_active = $('#f_active'), f_imgurl = $('#f_imgurl'), f_imgfile = $('#f_imgfile');
const btnCancel = $('#btnCancel');

const dlgDel = $('#dlgDel'), delText = $('#delText'), btnDelYes = $('#btnDelYes'), btnDelCancel = $('#btnDelCancel');
let delId = null;

function escapeHtml(s){return String(s||'').replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));}
async function api(url,opt={}){ const r=await fetch(url,opt); return r.json(); }

function rowHtml(r){
  const prev = r.image_url
    ? `<img src="${escapeHtml(r.image_url)}" alt="" style="width:120px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb" onerror="this.style.opacity=.3">`
    : `<div style="width:120px;height:56px;border-radius:8px;background:#eef2ff;border:1px solid #e5e7eb"></div>`;
  const status = r.is_active == 1 ? '<span class="status-chip in">Aktif</span>' : '<span class="status-chip out">Nonaktif</span>';
  const updated = (r.updated_at||'').replace('T',' ');
  return `<tr>
    <td>${prev}</td>
    <td>${escapeHtml(r.title)}</td>
    <td>${escapeHtml(r.link_url||'')}</td>
    <td>${r.sort}</td>
    <td>${status}</td>
    <td>${updated}</td>
    <td>
      <button class="btn btn-ghost" title="Edit" onclick="openEdit(${r.id})"><i class="fa fa-pen"></i></button>
      <button class="btn btn-ghost" title="Hapus" onclick="openDelete(${r.id}, '${escapeHtml(r.title)}')"><i class="fa fa-ellipsis-v"></i></button>
    </td>
  </tr>`;
}

async function loadList(){
  const params = new URLSearchParams({ action:'list', q:q.value.trim(), active:filAct.value });
  const data = await api(`${API}?${params.toString()}`, {headers:{'X-Requested-With':'fetch'}});
  rows.innerHTML = (data.rows||[]).map(rowHtml).join('') || `<tr><td colspan="7" style="text-align:center;color:#6b7280;padding:16px">Tidak ada data</td></tr>`;
  $('#kpiActive').textContent = data.kpi?.active ?? 0;
  $('#kpiTotal').textContent  = data.kpi?.total  ?? 0;
}
let t; q.addEventListener('input', ()=>{clearTimeout(t); t=setTimeout(loadList,300);});
filAct.addEventListener('change', loadList);

function show(el){ el.hidden=false; ovl.hidden=false; }
function hide(el){ el.hidden=true;  ovl.hidden=true; }

btnAdd.addEventListener('click', ()=>{
  dlgTitle.textContent = 'Tambah Banner';
  frm.reset(); f_id.value='';
  show(dlg);
});
btnCancel.addEventListener('click', ()=> hide(dlg));

window.openEdit = async function(id){
  const r = await api(`${API}?action=show&id=${id}`, {headers:{'X-Requested-With':'fetch'}});
  if (!r || !r.id) return;
  dlgTitle.textContent = 'Edit Banner';
  f_id.value = r.id; f_title.value = r.title||''; f_link.value = r.link_url||'';
  f_sort.value = r.sort ?? 0; f_active.value = r.is_active ?? 1; f_imgurl.value = r.image_url||'';
  f_imgfile.value = ''; // reset file input
  show(dlg);
};

window.openDelete = function(id, name){
  delId = id; delText.textContent = `Hapus "${name}"? Tindakan ini tidak bisa dibatalkan.`; show(dlgDel);
};
btnDelCancel.addEventListener('click', ()=> hide(dlgDel));
btnDelYes.addEventListener('click', async ()=>{
  if (!delId) return;
  const form = new URLSearchParams({action:'delete', id: delId});
  await fetch(API, {method:'POST', body: form});
  delId = null; hide(dlgDel); loadList();
});

frm.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const fd = new FormData(frm);
  fd.append('action', f_id.value ? 'update' : 'create');
  const r = await fetch(API, { method:'POST', body: fd });
  hide(dlg); loadList();
});

loadList();
</script>
</body>
</html>
