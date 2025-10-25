<?php
require __DIR__ . '/inc/session.php';
require __DIR__ . '/inc/koneksi.php';
require __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/fungsi.php';
require_login();

$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("s", $_SESSION['user']['email']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if (!$user) { header("Location: logout.php"); exit; }

function mask_password($len = 8) { return str_repeat('*', max(6, (int)$len)); }
$avatarPath = !empty($user['avatar_path']) ? htmlspecialchars($user['avatar_path']) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Profil</title>
  <link rel="stylesheet" href="assets/css/profile.css">
</head>
<body>
  <div class="bg-rails"><div class="spacer"></div></div>

  <div class="card">
    <div class="header" style="gap:10px; align-items:center;">
      <!-- Tombol kembali yang bagus & berfungsi -->
      <a class="btn btn-back" type="button" title="Kembali" href="<?= base_url() ?>">&larr;</a>
      <div class="title">Profile</div>
    </div>

    <!-- Avatar + Edit foto -->
    <form id="avatar-form" action= "/assets/api/profile_upload" method="post" enctype="multipart/form-data">
      <input id="avatar-file" type="file" name="avatar" accept="image/*">
      <div class="avatar" id="avatar">
        <?php if ($avatarPath): ?>
          <img src="<?= $avatarPath ?>" alt="Avatar">
        <?php else: ?>
          <span><?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?></span>
        <?php endif; ?>
       <span class="edit-dot" id="avatar-edit" title="Ganti foto" aria-label="Ganti foto">
  <!-- Ikon pensil (SVG, stroke putih) -->
  <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M12 20h9"/>
    <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
  </svg>
</span>

      </div>
    </form>

    <form onsubmit="return false;">
      <label>Nama</label>
      <input class="input" type="text" value="<?= htmlspecialchars($user['nama'] ?? '') ?>" readonly>

      <label>Alamat Email</label>
      <input class="input" type="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly>

      <label>Nomor Telepon</label>
      <input class="input" type="text" value="<?= htmlspecialchars($user['no_telp'] ?? '') ?>" readonly>

      <!-- Tombol Ganti yang rapi -->
<div class="actions">
  <a class="btn btn-primary" href="<?= base_url()."/profile_edit" ?>">Edit Profile</a>
  <a class="btn btn-danger" href="<?= base_url()."/logout.php" ?>" 
     onclick="return confirm('Yakin ingin logout?');">Logout</a>
</div>

    </form>
  </div>

  <script>
    function togglePw(){ const el = document.getElementById('pw'); el.type = (el.type === 'password') ? 'text' : 'password'; }

    // Kembali: prioritas history, fallback ke index.php
    function goBack(){
      if (document.referrer && document.referrer !== location.href) { history.back(); }
      else { window.location.href = 'index.php'; }
    }

    // Edit foto: klik ikon kamera => buka file picker, preview lalu upload
    const btnEdit = document.getElementById('avatar-edit');
    const fileInp = document.getElementById('avatar-file');
    const avatar = document.getElementById('avatar');
    const formAvatar = document.getElementById('avatar-form');

    btnEdit.addEventListener('click', () => fileInp.click());

    fileInp.addEventListener('change', () => {
      if (!fileInp.files.length) return;
      // Preview cepat
      const f = fileInp.files[0];
      const url = URL.createObjectURL(f);
      const img = document.createElement('img');
      img.src = url; img.alt = 'Preview';
      avatar.innerHTML = ''; avatar.appendChild(img);
      const dot = document.createElement('span'); dot.className='edit-dot'; dot.textContent=''; avatar.appendChild(dot);

      // Kirim ke server
      formAvatar.submit();
    });
  </script>
  <script>
  function togglePw(){ const el=document.getElementById('pw'); el.type=(el.type==='password')?'text':'password'; }

  function goBack(){
    if (document.referrer && document.referrer !== location.href) { history.back(); }
    else { window.location.href = 'index.php'; }
  }

  const fileInp   = document.getElementById('avatar-file');
  const avatarBox = document.getElementById('avatar');
  const formAvatar= document.getElementById('avatar-form');

  // Delegasi klik: apapun ikon edit-dot yang ada (sebelum/sesudah preview)
  avatarBox.addEventListener('click', (e)=>{
    if (e.target.classList.contains('edit-dot')) {
      fileInp.click();
    }
  });

  fileInp.addEventListener('change', ()=>{
    if (!fileInp.files.length) return;
    const f = fileInp.files[0];
    const url = URL.createObjectURL(f);

    // Render ulang isi avatar (img + edit-dot baru)
    avatarBox.innerHTML = '';
    const img = document.createElement('img');
    img.src = url; img.alt = 'Preview';
    avatarBox.appendChild(img);

    const dot = document.createElement('span');
    dot.className = 'edit-dot';
    dot.title = 'Ganti foto';
    dot.textContent = '.';
    avatarBox.appendChild(dot);

    formAvatar.submit(); // upload
  });
</script>

</body>
</html>
