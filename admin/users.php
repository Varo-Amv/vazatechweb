<?php
require __DIR__.'/../inc/auth.php';
require_role(['admin','staff']); // hanya admin/staff
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>VAZATECH Admin · Users</title>

    <title>Users · Admin</title>


<?php include("../inc/hdradmin.php")?>

    <main class="container">
      <!-- Sidebar -->
      <aside class="sidebar">
        <a href="index"><i class="fas fa-home"></i>Dashboard</a>
        <a href="stocks"><i class="fas fa-box"></i>Stocks</a>
        <a href="#" class="active"><i class="fas fa-users"></i>Users</a>
        <a href="orders"><i class="fas fa-shopping-cart"></i>Orders</a>


        <a href="banners"><i class="fas fa-image"></i>Banners</a>

        <a href="blog-list"><i class="fas fa-newspaper"></i>Blog</a>
      </aside>

      <!-- Content -->
      <section class="content">
        <!-- Header + KPI -->
        <div class="dashboard-header">
          <h2>Users</h2>
<div class="cards-row">
  <div class="card kpi">
    <div class="kpi-title">Total Users</div>
    <div id="kpiTotal" class="kpi-value">0</div>
  </div>
  <div class="card kpi">
    <div class="kpi-title">Active</div>
    <div id="kpiActive" class="kpi-value">0</div>
  </div>
  <div class="card kpi">
    <div class="kpi-title">Suspended</div>
    <div id="kpiSuspended" class="kpi-value">0</div>
  </div>
</div>

        </div>

        <!-- Panel utama: Toolbar + Tabel Users -->
        <div class="card panel">
          <div class="panel-toolbar">
            <div class="scroll-inner">
              <div class="left">
                <div class="control">
                  <i class="fa fa-magnifying-glass"></i>
                 <input id="q" type="text" class="input" placeholder="Cari nama / email / phone" />
                </div>
<select id="roleSel" class="select">
  <option value="all">Semua Role</option>
  <option value="admin">admin</option>
  <option value="staff">staff</option>
  <option value="customer">customer</option>
  <option value="user">user</option>
</select>
<select id="statusSel" class="select">
  <option value="all">Semua Status</option>
  <option value="active">active</option>
  <option value="suspended">suspended</option>
</select>
              </div>
              <div class="right">
<button id="btnExport" class="btn btn-secondary">
  <i class="fa fa-file-export"></i> Export CSV
</button>
<button id="btnAdd" class="btn btn-primary">
  <i class="fa fa-user-plus"></i> Tambah User
</button>
              </div>
            </div>

            <div class="table-wrap">
              <div class="scroll-inner">
                <table class="users-table">
                  <thead>
                    <tr>
                      <th>Nama</th>
                      <th>Email</th>
                      <th>Phone</th>
                      <th>Role</th>
                      <th>Status</th>
                      <th>Last Login</th>
                      <th>Dibuat</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody id="userRows"></tbody>
                </table>
              </div>
            </div>

            <div class="legend">
              <span class="role-chip admin">admin</span>
              <span class="role-chip staff">staff</span>
              <span class="role-chip customer">customer</span>
              <span class="status-chip active">active</span>
              <span class="status-chip suspended">suspended</span>
            </div>
          </div>
        </div>
      </section>
    </main>
    <footer class="footer"></footer>
    <!-- Overlay + Modals -->
<div id="overlay" class="modal-overlay" hidden></div>

<!-- Modal Add/Edit -->
<div id="userModal" class="modal" hidden>
  <div class="modal-box">
    <h3 id="modalTitle">Tambah User</h3>
    <form id="userForm">
      <input type="hidden" name="id" id="f_id" />
      <div class="grid2">
        <label>Nama
          <input type="text" name="nama" id="f_nama" required />
        </label>
        <label>Email
          <input type="email" name="email" id="f_email" required />
        </label>
        <label>Phone
          <input type="text" name="no_telp" id="f_phone" />
        </label>
        <label>Role
          <select name="role" id="f_role">
            <option value="customer">customer</option>
            <option value="staff">staff</option>
            <option value="admin">admin</option>
          </select>
        </label>
        <label>Status
          <select name="status" id="f_status">
            <option value="active">active</option>
            <option value="suspended">suspended</option>
          </select>
        </label>
        <label>Password <small>(kosongkan jika tidak diubah)</small>
          <input type="password" name="password" id="f_password" />
        </label>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn" id="btnCancel">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Delete -->
<div id="delModal" class="modal" hidden>
  <div class="modal-box">
    <h3>Hapus User</h3>
    <p id="delText">Yakin ingin menghapus?</p>
    <div class="modal-actions">
      <button type="button" class="btn" id="btnDelCancel">Batal</button>
      <button type="button" class="btn btn-danger" id="btnDelYes">Hapus</button>
    </div>
  </div>
</div>
<script>
const API = '../assets/api/users.php'; // endpoint backend

// elemen2
const rowsEl = document.getElementById('userRows');
const qEl    = document.getElementById('q');
const roleEl = document.getElementById('roleSel');
const statEl = document.getElementById('statusSel');

const kTotal = document.getElementById('kpiTotal');
const kActive= document.getElementById('kpiActive');
const kSusp  = document.getElementById('kpiSuspended');

const btnAdd    = document.getElementById('btnAdd');
const btnExport = document.getElementById('btnExport');

const overlay = document.getElementById('overlay');
const userModal = document.getElementById('userModal');
const delModal  = document.getElementById('delModal');

const f_id   = document.getElementById('f_id');
const f_nama = document.getElementById('f_nama');
const f_email= document.getElementById('f_email');
const f_phone= document.getElementById('f_phone');
const f_role = document.getElementById('f_role');
const f_status=document.getElementById('f_status');
const f_pass = document.getElementById('f_password');
const modalTitle = document.getElementById('modalTitle');

const delText = document.getElementById('delText');
let delId = null;

// helpers
function show(el){ overlay.hidden=false; el.hidden=false; }
function hide(el){ overlay.hidden=true; el.hidden=true; }
function escapeHtml(s){return String(s||'').replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));}
function fmt(s){ return s ? s.replace('T',' ') : '—'; }

function avatarHtml(name, email) {
  const init = (name||'?').trim().split(/\s+/).slice(0,2).map(x=>x[0]).join('').toUpperCase();
  return `
    <div class="user-cell">
      <div class="avatar">${init}</div>
      <div class="meta">
        <div class="name">${escapeHtml(name||'')}</div>
        <div class="sub">${escapeHtml(email||'')}</div>
      </div>
    </div>`;
}
function roleChip(r){ const cls=(r==='admin')?'admin':(r==='staff')?'staff':'customer'; const txt=(r==='user')?'customer':r; return `<span class="role-chip ${cls}">${escapeHtml(txt)}</span>`; }
function statusChip(s){ const cls=(s==='suspended')?'suspended':'active'; return `<span class="status-chip ${cls}">${escapeHtml(s)}</span>`; }

async function api(url, opt={}) {
  const r = await fetch(url, {headers:{'X-Requested-With':'fetch'}, ...opt});
  const ct = r.headers.get('Content-Type')||'';
  return ct.includes('application/json') ? r.json() : r.text();
}

function rowTpl(r){
  return `<tr>
    <td>${avatarHtml(r.nama, r.email)}</td>
    <td>${escapeHtml(r.email||'')}</td>
    <td>${escapeHtml(r.no_telp||'')}</td>
    <td>${roleChip(r.role||'customer')}</td>
    <td>${statusChip(r.status||'active')}</td>
    <td>${fmt(r.tgl_login)}</td>
    <td>${fmt(r.tgl_dibuat)}</td>
    <td>
      <button class="btn btn-ghost" title="Edit" onclick="openEdit(${r.id})"><i class="fa fa-pen"></i></button>
      <button class="btn btn-ghost" title="Hapus" onclick="openDelete(${r.id}, '${escapeHtml(r.nama)}')"><i class="fa fa-ellipsis-v"></i></button>
    </td>
  </tr>`;
}

async function loadList(){
  const q = encodeURIComponent(qEl.value.trim());
  const role = encodeURIComponent(roleEl.value);
  const status = encodeURIComponent(statEl.value);
  const data = await api(`${API}?action=list&q=${q}&role=${role}&status=${status}`);
  rowsEl.innerHTML = (data && data.length)
    ? data.map(rowTpl).join('')
    : `<tr><td colspan="8" style="text-align:center; color:#6b7280; padding:16px;">Tidak ada data</td></tr>`;
}

async function loadKPI(){
  try{
    const s = await api(`${API}?action=stats`);
    kTotal.textContent = (s.total||0).toLocaleString('id-ID');
    kActive.textContent = (s.active||0).toLocaleString('id-ID');
    kSusp.textContent = (s.suspended||0).toLocaleString('id-ID');
  }catch(_){}
}

// Search debounce
let _t; qEl.addEventListener('input', ()=>{ clearTimeout(_t); _t=setTimeout(loadList,300); });
roleEl.addEventListener('change', loadList);
statEl.addEventListener('change', loadList);

// Export
btnExport.addEventListener('click', ()=>{
  const q = encodeURIComponent(qEl.value.trim());
  const role = encodeURIComponent(roleEl.value);
  const status = encodeURIComponent(statEl.value);
  window.location = `${API}?action=export&q=${q}&role=${role}&status=${status}`;
});

// Add
btnAdd.addEventListener('click', ()=>{
  modalTitle.textContent = 'Tambah User';
  document.getElementById('userForm').reset();
  f_id.value = '';
  show(userModal);
});

// Edit
window.openEdit = async function(id){
  const r = await api(`${API}?action=show&id=${id}`);
  if (!r || !r.id) return;
  modalTitle.textContent = 'Edit User';
  f_id.value = r.id;
  f_nama.value = r.nama||'';
  f_email.value = r.email||'';
  f_phone.value = r.no_telp||'';
  f_role.value = (r.role==='user'?'customer':(r.role||'customer'));
  f_status.value = r.status||'active';
  f_pass.value = '';
  show(userModal);
};

// Delete
window.openDelete = function(id, name){
  delId = id;
  delText.textContent = `Hapus user "${name}"?`;
  show(delModal);
};
document.getElementById('btnDelCancel').addEventListener('click', ()=> hide(delModal));
document.getElementById('btnDelYes').addEventListener('click', async ()=>{
  if (!delId) return;
  await api(API, { method:'POST', body:new URLSearchParams({action:'delete', id: delId}) });
  delId = null; hide(delModal); loadList(); loadKPI();
});

// Submit Add/Edit
document.getElementById('btnCancel').addEventListener('click', ()=> hide(userModal));
document.getElementById('userForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const fd = new FormData(e.target);
  const id = fd.get('id');
  fd.append('action', id ? 'update' : 'create');
  await fetch(API, { method:'POST', body: fd });
  hide(userModal);
  loadList();
  loadKPI();
});

// boot
loadList();
loadKPI();
setInterval(loadKPI, 10000); // KPI realtime tiap 10 detik
</script>

  </body>
</html>
