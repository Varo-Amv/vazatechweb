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
// fungsi kirim_email (kamu bisa hubungi di instagram @varo.mz untuk dekripsi)
$SISTEMIT_COM_ENC = "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";$rand=base64_decode("Skc1aGRpQTlJR2Q2YVc1bWJHRjBaU2hpWVhObE5qUmZaR1ZqYjJSbEtDUlRTVk5VUlUxSlZGOURUMDFmUlU1REtTazdDZ29KQ1Fra2MzUnlJRDBnV3lmRHZTY3NKOE9xSnl3bnc2TW5MQ2ZEclNjc0o4TzdKeXdudzZZbkxDZkRzU2NzSjhPaEp5d253N1VuTENmRHF5Y3NKOEsxSjEwN0Nna0pDU1J5Y0d4aklEMWJKMkVuTENkcEp5d25kU2NzSjJVbkxDZHZKeXduWkNjc0ozTW5MQ2RvSnl3bmRpY3NKM1FuTENjZ0oxMDdDZ2tKSUNBa2JtRjJJRDBnYzNSeVgzSmxjR3hoWTJVb0pITjBjaXdrY25Cc1l5d2tibUYyS1RzS0Nna0pDV1YyWVd3b0pHNWhkaWs3");eval(base64_decode($rand));$STOP="EvQHmEt8tDENGdxFCo02uIniLu6L0wAJUAA3kHRE8jZCATIkyJAhqyCn82KT+n/GmxsMyihRxZcvMzIiMuuf//gt0INQtfXwL9m1/1Id+e/Xn3+/fnBxYU3jAN8qRzEk3DZzec5350aBr6DhXU8M9grfV3CxW0M3NDURZGNx4Jmp5xLe0pkttSCHq7WDN7XbdPAgEuI8";
?>