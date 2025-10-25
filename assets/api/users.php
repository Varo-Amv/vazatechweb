<?php
// assets/api/users.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../inc/koneksi.php';

$isPDO    = isset($pdo) && $pdo instanceof PDO;
$isMySQLi = isset($koneksi) && $koneksi instanceof mysqli;

// ... di atas sudah ada require koneksi dan deteksi $pdo / $koneksi

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if ($action === 'list') {
  $q      = trim($_GET['q']      ?? '');
  $role   = trim($_GET['role']   ?? '');
  $status = trim($_GET['status'] ?? '');

  // >>> PERBAIKAN: abaikan 'all'
  if ($role === 'all')   $role   = '';
  if ($status === 'all') $status = '';

  // >>> (opsional) UI 'customer' = DB 'user'
  if ($role === 'customer') $role = 'user';

  $where  = [];
  $params = [];

  if ($q !== '') {
    $where[] = "(nama LIKE :q OR email LIKE :q OR no_telp LIKE :q)";
    $params[':q'] = "%{$q}%";
  }
  if ($role !== '') {
    $where[] = "role = :role";
    $params[':role'] = $role;
  }
  if ($status !== '') {
    $where[] = "status = :status";
    $params[':status'] = $status;
  }

  $sql = "SELECT id, nama, email, no_telp, role, status, tgl_login, tgl_dibuat
          FROM users";
  if ($where) $sql .= " WHERE " . implode(' AND ', $where);
  $sql .= " ORDER BY id DESC LIMIT 500";

  try {
    if (isset($pdo) && $pdo instanceof PDO) {
      $st = $pdo->prepare($sql);
      $st->execute($params);
      $rows = $st->fetchAll(PDO::FETCH_ASSOC);
      echo json_encode($rows ?: []);
      exit;
    } elseif (isset($koneksi) && $koneksi instanceof mysqli) {
      // Build query untuk mysqli (tanpa prepared parameter array)
      // tetap aman karena kita sudah filter sederhana & escape manual
      $qEsc = $koneksi->real_escape_string($q);
      $roleEsc = $koneksi->real_escape_string($role);
      $statusEsc = $koneksi->real_escape_string($status);

      $where2 = [];
      if ($q !== '')      $where2[] = "(nama LIKE '%$qEsc%' OR email LIKE '%$qEsc%' OR no_telp LIKE '%$qEsc%')";
      if ($role !== '')   $where2[] = "role = '$roleEsc'";
      if ($status !== '') $where2[] = "status = '$statusEsc'";

      $sql2 = "SELECT id, nama, email, no_telp, role, status, tgl_login, tgl_dibuat FROM users";
      if ($where2) $sql2 .= " WHERE " . implode(' AND ', $where2);
      $sql2 .= " ORDER BY id DESC LIMIT 500";

      $res = $koneksi->query($sql2);
      $out = [];
      if ($res) while ($r = $res->fetch_assoc()) $out[] = $r;
      echo json_encode($out);
      exit;
    } else {
      echo json_encode([]);
      exit;
    }
  } catch (Throwable $e) {
    echo json_encode([]);
    exit;
  }
}

/* --- opsional: handler stats juga aman dari 'all' (kalau sudah ada, biarkan) ---
if ($action === 'stats') {
  // SELECT COUNT(*), SUM(status='active'), SUM(status='suspended') FROM users
}
*/

// ... setelah require koneksi & penentuan $isPDO/$isMySQLi
if (($action ?? '') === 'stats') {
  if ($isPDO) {
    $row = $pdo->query("
      SELECT
        COUNT(*) AS total,
        SUM(status='active') AS active,
        SUM(status='suspended') AS suspended
      FROM users
    ")->fetch(PDO::FETCH_ASSOC);
  } else {
    $res = $koneksi->query("
      SELECT
        COUNT(*) AS total,
        SUM(status='active') AS active,
        SUM(status='suspended') AS suspended
      FROM users
    ");
    $row = $res->fetch_assoc();
  }
  echo json_encode($row ?: ['total'=>0,'active'=>0,'suspended'=>0]);
  exit;
}

function jexit($data, $code = 200) {
  http_response_code($code);
  if (is_array($data) || is_object($data)) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
  } else {
    echo $data;
  }
  exit;
}

function esc_like($s) {
  return str_replace(['%', '_'], ['\%','\_'], $s ?? '');
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

try {

  /* ===== LIST ===== */
  if ($action === 'list') {
    $q     = trim($_GET['q'] ?? '');
    $role  = trim($_GET['role'] ?? '');
    $status= trim($_GET['status'] ?? '');

    $where = [];
    $params = [];

    if ($q !== '') {
      $where[] = "(nama LIKE :q OR email LIKE :q OR no_telp LIKE :q)";
      $params[':q'] = "%".esc_like($q)."%";
    }
    if ($role !== '' && $role !== 'all') {
      $where[] = "role = :role";
      $params[':role'] = $role;
    }
    if ($status !== '' && $status !== 'all') {
      $where[] = "status = :status";
      $params[':status'] = $status;
    }

    $sql = "SELECT id,nama,email,no_telp,role,status,tgl_dibuat,tgl_login
            FROM users";
    if ($where) $sql .= " WHERE ".implode(" AND ", $where);
    $sql .= " ORDER BY tgl_dibuat DESC, id DESC";

    if ($isPDO) {
      $st = $pdo->prepare($sql);
      $st->execute($params);
      $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    } else {
      // mysqli
      if ($params) {
        // build simple replacement (safe enough bcs only scalar binds and LIKE already escaped)
        foreach ($params as $k=>$v) {
          $v = $k==':q' ? '%'.$k.'%' : $v;
        }
      }
      // naive bind for mysqli
      if ($q !== '') { $qv = "%".esc_like($q)."%"; }
      $sql_m = $sql;
      if ($isMySQLi) {
        if ($q !== '' || $role !== '' || $status !== '') {
          $stmt = $koneksi->prepare(str_replace([':q',':role',':status'], ['?','?','?'], $sql));
          $binds = [];
          $types = '';
          if ($q !== '')     { $binds[] = $qv;   $types.='s'; }
          if ($role !== '' && $role !== 'all'){ $binds[] = $role; $types.='s'; }
          if ($status !== '' && $status !== 'all'){ $binds[] = $status; $types.='s'; }
          $stmt->bind_param($types, ...$binds);
          $stmt->execute();
          $res = $stmt->get_result();
          $rows = $res->fetch_all(MYSQLI_ASSOC);
        } else {
          $res = $koneksi->query($sql);
          $rows = $res->fetch_all(MYSQLI_ASSOC);
        }
      }
    }
    jexit($rows);
  }

  /* ===== SHOW ===== */
  if ($action === 'show') {
    $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($id <= 0) jexit(['error'=>'invalid id'], 400);

    $sql = "SELECT id,nama,email,no_telp,role,status,tgl_dibuat,tgl_login FROM users WHERE id = :id";
    if ($isPDO) {
      $st = $pdo->prepare($sql);
      $st->execute([':id'=>$id]);
      $row = $st->fetch(PDO::FETCH_ASSOC);
    } else {
      $stmt = $koneksi->prepare(str_replace(':id','?',$sql));
      $stmt->bind_param('i',$id);
      $stmt->execute();
      $row = $stmt->get_result()->fetch_assoc();
    }
    jexit($row ?: []);
  }

  /* ===== CREATE ===== */
  if ($action === 'create') {
    $nama    = trim($_POST['nama'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $no_telp = trim($_POST['no_telp'] ?? '');
    $role    = trim($_POST['role'] ?? 'customer');
    $status  = trim($_POST['status'] ?? 'active');
    $passRaw = trim($_POST['password'] ?? '');

    if ($nama==='' || $email==='' || $passRaw==='') jexit(['error'=>'nama/email/password wajib'], 422);

    $pass = password_hash($passRaw, PASSWORD_DEFAULT);

    if ($isPDO) {
      $st = $pdo->prepare("INSERT INTO users(nama,email,no_telp,password,role,status,tgl_dibuat) VALUES(:n,:e,:p,:pw,:r,:s,NOW())");
      $st->execute([':n'=>$nama, ':e'=>$email, ':p'=>$no_telp, ':pw'=>$pass, ':r'=>$role, ':s'=>$status]);
      jexit(['ok'=>true,'id'=>$pdo->lastInsertId()]);
    } else {
      $stmt = $koneksi->prepare("INSERT INTO users(nama,email,no_telp,password,role,status,tgl_dibuat) VALUES(?,?,?,?,?, ?, NOW())");
      $stmt->bind_param('ssssss',$nama,$email,$no_telp,$pass,$role,$status);
      $stmt->execute();
      jexit(['ok'=>true,'id'=>$stmt->insert_id]);
    }
  }

  /* ===== UPDATE ===== */
  if ($action === 'update') {
    $id      = intval($_POST['id'] ?? 0);
    $nama    = trim($_POST['nama'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $no_telp = trim($_POST['no_telp'] ?? '');
    $role    = trim($_POST['role'] ?? 'customer');
    $status  = trim($_POST['status'] ?? 'active');
    $passRaw = trim($_POST['password'] ?? '');

    if ($id<=0 || $nama==='' || $email==='') jexit(['error'=>'data kurang'], 422);

    if ($isPDO) {
      if ($passRaw!=='') {
        $pass = password_hash($passRaw, PASSWORD_DEFAULT);
        $st = $pdo->prepare("UPDATE users SET nama=:n,email=:e,no_telp=:p,role=:r,status=:s,password=:pw WHERE id=:id");
        $st->execute([':n'=>$nama, ':e'=>$email, ':p'=>$no_telp, ':r'=>$role, ':s'=>$status, ':pw'=>$pass, ':id'=>$id]);
      } else {
        $st = $pdo->prepare("UPDATE users SET nama=:n,email=:e,no_telp=:p,role=:r,status=:s WHERE id=:id");
        $st->execute([':n'=>$nama, ':e'=>$email, ':p'=>$no_telp, ':r'=>$role, ':s'=>$status, ':id'=>$id]);
      }
    } else {
      if ($passRaw!=='') {
        $pass = password_hash($passRaw, PASSWORD_DEFAULT);
        $stmt = $koneksi->prepare("UPDATE users SET nama=?,email=?,no_telp=?,role=?,status=?,password=? WHERE id=?");
        $stmt->bind_param('ssssssi',$nama,$email,$no_telp,$role,$status,$pass,$id);
        $stmt->execute();
      } else {
        $stmt = $koneksi->prepare("UPDATE users SET nama=?,email=?,no_telp=?,role=?,status=? WHERE id=?");
        $stmt->bind_param('sssssi',$nama,$email,$no_telp,$role,$status,$id);
        $stmt->execute();
      }
    }
    jexit(['ok'=>true]);
  }

  /* ===== DELETE ===== */
  if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id<=0) jexit(['error'=>'invalid id'], 422);
    if ($isPDO) {
      $st = $pdo->prepare("DELETE FROM users WHERE id=:id");
      $st->execute([':id'=>$id]);
    } else {
      $stmt = $koneksi->prepare("DELETE FROM users WHERE id=?");
      $stmt->bind_param('i',$id);
      $stmt->execute();
    }
    jexit(['ok'=>true]);
  }

  /* ===== EXPORT CSV ===== */
  if ($action === 'export') {
    // switch to CSV headers
    header_remove('Content-Type');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="users_export.csv"');

    $q     = trim($_GET['q'] ?? '');
    $role  = trim($_GET['role'] ?? '');
    $status= trim($_GET['status'] ?? '');

    $where = [];
    $params= [];
    if ($q !== '') { $where[]="(nama LIKE :q OR email LIKE :q OR no_telp LIKE :q)"; $params[':q']="%".esc_like($q)."%"; }
    if ($role !== '' && $role !== 'all') { $where[]="role=:role"; $params[':role']=$role; }
    if ($status!== '' && $status!=='all'){ $where[]="status=:status"; $params[':status']=$status; }

    $sql = "SELECT id,nama,email,no_telp,role,status,tgl_dibuat,tgl_login FROM users";
    if ($where) $sql .= " WHERE ".implode(" AND ", $where);
    $sql .= " ORDER BY id DESC";

    if ($isPDO) {
      $st = $pdo->prepare($sql);
      $st->execute($params);
      $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    } else {
      if ($q !== '' || ($role!=='' && $role!=='all') || ($status!=='' && $status!=='all')) {
        $stmt = $koneksi->prepare(str_replace([':q',':role',':status'],['?','?','?'],$sql));
        $binds=[]; $types='';
        if ($q!==''){ $binds[]="%".esc_like($q)."%"; $types.='s'; }
        if ($role!=='' && $role!=='all'){ $binds[]=$role; $types.='s'; }
        if ($status!=='' && $status!=='all'){ $binds[]=$status; $types.='s'; }
        $stmt->bind_param($types, ...$binds);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
      } else {
        $res = $koneksi->query($sql);
        $rows = $res->fetch_all(MYSQLI_ASSOC);
      }
    }

    $out = fopen('php://output','w');
    fputcsv($out, ['ID','Nama','Email','Phone','Role','Status','Dibuat','Last Login']);
    foreach ($rows as $r) {
      fputcsv($out, [$r['id'],$r['nama'],$r['email'],$r['no_telp'],$r['role'],$r['status'],$r['tgl_dibuat'],$r['tgl_login']]);
    }
    fclose($out);
    exit;
  }

  jexit(['error'=>'unknown action'], 400);

} catch (Throwable $e) {
  jexit(['error'=>'server','detail'=>$e->getMessage()], 500);
}
