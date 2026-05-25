<?php
/**
 * 四季線上電視版 API 客戶端 (PHP 8.1+)
 */

// 全域常數定義
define('HOST', 'api2.4gtv.tv');
define('APP_VERSION', '1.5.4');
define('ANDROID_UA', 'Dalvik/2.1.0 (Linux; U; Android 13; Android TV Build/TP1A.220624.014)');
define('WEBVIEW_UA', 'Mozilla/5.0 (Linux; Android TV 13; XIAOMI 17 Pro Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.120 Mobile Safari/537.36');
define('APP_NAME', '四季線上電視版');
define('APP_BUNDLE', 'tv.fourgtv.video');

define('DEFAULT_SYSTEM_PROPERTIES', [
    'api_domain'   => 'https://api2.4gtv.tv/',
    'key'          => 'bD5tN0VpW3pCjXhCIf9MhuuB2A39cCk5',
    'iv'           => 'CaIiNVDSAPKfraXs',
    'bandott_key'  => 'ghweDj351kj-rdam',
    'bandott_iv'   => 'nsd3fgl83jws2wem',
]);

/**
 * 解析 system.properties 文字內容
 */
function parse_properties(string $text): array {
    $props = [];
    $lines = explode("\n", $text);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $props[trim($key)] = trim($value);
    }
    return $props;
}

/**
 * 解密 Header Key (AES-128-CBC)
 */
function decrypt_header_key(string $cipher_b64, string $key, string $iv): string {
    $cipher_bytes = base64_decode($cipher_b64);
    // PHP openssl_decrypt 預設會自動處理 PKCS7 Unpadding
    $plain = openssl_decrypt($cipher_bytes, 'aes-128-cbc', $key, OPENSSL_RAW_DATA, $iv);
    if ($plain === false) {
        throw new \RuntimeException('AES decryption failed.');
    }
    return $plain;
}

/**
 * 產生 4GTV 驗證 Token
 */
function fourgtv_auth(string $header_key): string {
    // 取得 UTC 當天日期 (YYYYMMDD)
    $gmt_date = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Ymd');
    $digest = hash('sha512', $gmt_date . $header_key, true);
    return base64_encode($digest);
}

/**
 * 模擬強制使用 TLS 1.3 的 HTTP 客戶端
 */
class Tls13Client {
    public string $device_uuid;
    public string $device_mode;
    public ?string $header_key;
    public string $token;
    public string $link_id;
    public string $locale;
    public string $api_user_agent;
    
    public string $last_tls_version = '';
    public array $last_request_headers = [];

    public function __construct(
        string $device_uuid,
        string $device_mode = 'TV',
        ?string $header_key = null,
        string $token = '',
        string $link_id = '',
        string $locale = 'en-US',
        string $api_user_agent = ANDROID_UA
    ) {
        $this->device_uuid = $device_uuid;
        $this->device_mode = $device_mode;
        $this->header_key = $header_key;
        $this->token = $token;
        $this->link_id = $link_id;
        $this->locale = $locale;
        $this->api_user_agent = $api_user_agent;
    }

    /**
     * 發送網路請求 (底層 Socket 實作，強制走 TLS 1.3)
     */
    public function request(string $method, string $path, mixed $body = null, ?string $token = null, ?string $link_id = null): array {
        $body_bytes = '';
        if ($body !== null) {
            if (is_string($body)) {
                $body_bytes = $body;
            } else {
                $body_bytes = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        $token = $token ?? $this->token;
        $link_id = $link_id ?? $this->link_id;

        $headers = [
            'fsDEVICE' => $this->device_mode,
            'fsENC_KEY' => $this->device_uuid,
            'fsVERSION' => APP_VERSION,
        ];

        if ($method === 'POST') {
            $headers['fsVALUE'] = $token;
            $headers['fsLINK_ID'] = $link_id;
        }

        $headers['Content-Type'] = 'application/json';
        $headers['locale'] = $this->locale;

        if ($this->header_key) {
            $headers['4GTV_AUTH'] = $fourgtv_auth($this->header_key);
        }
        if ($this->api_user_agent) {
            $headers['User-Agent'] = $this->api_user_agent;
        }

        $headers['Accept-Encoding'] = 'gzip';
        $headers['Host'] = HOST;
        $headers['Connection'] = 'Keep-Alive';

        if ($method === 'POST') {
            $headers['Content-Length'] = (string)strlen($body_bytes);
        }

        $this->last_request_headers = $headers;

        // 建立 HTTP 原始請求字串
        $raw = "{$method} {$path} HTTP/1.1\r\n";
        foreach ($headers as $k => $v) {
            $raw .= "{$k}: {$v}\r\n";
        }
        $raw .= "\r\n" . $body_bytes;

        // 建立安全連線上下文，限定 TLS 1.3
        $context = stream_context_create([
            'ssl' => [
                'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT,
                'verify_peer' => false,
                'verify_peer_name' => false,
                'check_hostname' => false,
            ]
        ]);

        // 發起連線 (預設 443 埠號)
        $remote_socket = 'ssl://' . HOST . ':443';
        $sock = stream_socket_client($remote_socket, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
        
        if (!$sock) {
            throw new \RuntimeException("Socket connection failed: $errstr ($errno)");
        }

        stream_set_timeout($sock, 15);
        fwrite($sock, $raw);

        [$status, $response_headers, $response_body] = $this->_read_response($sock);
        fclose($sock);

        return [$status, $response_headers, $response_body];
    }

    public function post_json(string $path, array $body, ?string $token = null, ?string $link_id = null): array {
        [$status, $headers, $text] = $this->request('POST', path: $path, body: $body, token: $token, link_id: $link_id);
        $data = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("{$path} returned non-JSON body under {$status}: " . substr($text, 0, 500));
        }
        return [$status, $data];
    }

    public function get_json(string $path): array {
        [$status, $headers, $text] = $this->request('GET', $path);
        $data = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("{$path} returned non-JSON body under {$status}: " . substr($text, 0, 500));
        }
        return [$status, $data];
    }

    /**
     * 讀取並解析 HTTP 響應結構
     */
    private function _read_response($sock): array {
        $buf = '';
        // 讀取標頭直到遇見空行
        while (!str_contains($buf, "\r\n\r\n")) {
            $chunk = fread($sock, 4096);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $buf .= $chunk;
        }

        [$header_bytes, $rest] = explode("\r\n\r\n", $buf, 2);
        $lower_headers = strtolower($header_bytes);
        
        // 取得狀態行
        $status = explode("\r\n", $header_bytes, 2)[0];
        $body = '';

        // 1. 處理 Content-Length 形式的內文
        if (preg_match('/content-length:\s*(\d+)/', $lower_headers, $matches)) {
            $want = (int)$matches[1];
            while (strlen($rest) < $want) {
                $chunk = fread($sock, 4096);
                if ($chunk === false || $chunk === '') break;
                $rest .= $chunk;
            }
            $body = substr($rest, 0, $want);
        } 
        // 2. 處理 Chunked 傳輸編碼
        elseif (str_contains($lower_headers, 'transfer-encoding: chunked')) {
            $data = $rest;
            while (true) {
                while (!str_contains($data, "\r\n")) {
                    $chunk = fread($sock, 4096);
                    if ($chunk === false || $chunk === '') break;
                    $data .= $chunk;
                }
                [$line, $data] = explode("\r\n", $data, 2);
                $size = hexdec(explode(';', $line, 2)[0]);
                if ($size === 0) {
                    break;
                }
                while (strlen($data) < $size + 2) {
                    $chunk = fread($sock, 4096);
                    if ($chunk === false || $chunk === '') break;
                    $data .= $chunk;
                }
                $body .= substr($data, 0, $size);
                $data = substr($data, $size + 2);
            }
        } 
        // 3. 常規串流形式
        else {
            $body = $rest;
            while (!feof($sock)) {
                $chunk = fread($sock, 4096);
                if ($chunk === false || $chunk === '') break;
                $body .= $chunk;
            }
        }

        // 如果伺服器回傳有經過 Gzip 壓縮，則解壓縮
        if (str_contains($lower_headers, 'content-encoding: gzip')) {
            $decompressed = @gzdecode($body);
            if ($decompressed !== false) {
                $body = $decompressed;
            }
        }

        return [$status, $header_bytes, $body];
    }
}

/**
 * 載入 system.properties 設定
 */
function load_system_properties(?string $apk_path): array {
    if (!$apk_path) {
        return [DEFAULT_SYSTEM_PROPERTIES, 'builtin'];
    }
    
    if (!file_exists($apk_path)) {
        throw new \InvalidArgumentException("APK not found: " . $apk_path);
    }

    $zip = new \ZipArchive();
    if ($zip->open($apk_path) === true) {
        $props_text = $zip->getFromName('assets/system.properties');
        $zip->close();
        if ($props_text === false) {
            throw new \RuntimeException("assets/system.properties not found in APK.");
        }
        return [parse_properties($props_text), $apk_path];
    } else {
        throw new \RuntimeException("Failed to open APK file.");
    }
}

/**
 * 被截斷函式的完整邏輯還原 (範例核心引導流程)
 */
function load_app_material(?string $apk_path, string $device_uuid): array {
    [$props, $props_source] = load_system_properties($apk_path);
    
    $bootstrap = new Tls13Client($device_uuid);
    [$status, $config] = $bootstrap->post_json('/App/GetAPPConfig', [
        'fsDEVICE' => 'TV',
        'fsVERSION' => APP_VERSION
    ]);

    // 接續並補完您被截斷的程式碼：
    // 從回傳的 config 取得金鑰加密字串，再進行解密
    if (!isset($config['fsKEY'])) {
