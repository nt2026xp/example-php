<?php
#!/usr/bin/env php
/**
 * 4GTV API Client PHP Version
 * 支援 PHP 8.0+
 */

// 確保以 CLI 模式執行時能正確解析參數（模擬 Python argparse 最簡單的方式）
$options = getopt("", [
    "apk:", "device-uuid:", "locale::", "api-user-agent::", "webview-ua::",
    "adid::", "uid2::", "account::", "password::", "token::", "link-id::",
    "guest-pv1::", "guest-pv2::", "qr-login", "qr-out::", "qr-timeout::",
    "channel-id::", "asset-id::", "channel-name::", "url-index::",
    "list-channels", "json-out::"
]);

// 常量定義
const HOST = "api2.4gtv.tv";
const APP_VERSION = "1.5.4";
const ANDROID_UA = "Dalvik/2.1.0 (Linux; U; Android 13; Android TV Build/TP1A.220624.014)";
const WEBVIEW_UA = "Mozilla/5.0 (Linux; Android TV 13; XIAOMI 17 Pro Build/TP1A.220624.014; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/148.0.7778.120 Mobile Safari/537.36";
const APP_NAME = "四季線上電視版";
const APP_BUNDLE = "tv.fourgtv.video";
const DEFAULT_SYSTEM_PROPERTIES = [
    "api_domain"   => "https://api2.4gtv.tv/",
    "key"          => "bD5tN0VpW3pCjXhCIf9MhuuB2A39cCk5",
    "iv"           => "CaIiNVDSAPKfraXs",
    "bandott_key"  => "ghweDj351kj-rdam",
    "bandott_iv"   => "nsd3fgl83jws2wem",
];

// 生成 UUID v4 輔助函式
function uuid4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function parse_properties($text) {
    $props = [];
    foreach (explode("\n", $text) as $line) {
        $line = trim($line);
        if (!$line || str_starts_with($line, "#") || !str_contains($line, "=")) {
            continue;
        }
        list($key, $value) = explode("=", $line, 2);
        $props[trim($key)] = trim($value);
    }
    return $props;
}

function pkcs7_unpad($data) {
    $len = strlen($data);
    $size = ord($data[$len - 1]);
    if ($size < 1 || $size > 16) {
        throw new Exception("bad pkcs7 padding");
    }
    for ($i = $len - $size; $i < $len; $i++) {
        if (ord($data[$i]) !== $size) {
            throw new Exception("bad pkcs7 padding");
        }
    }
    return substr($data, 0, $len - $size);
}

function decrypt_header_key($cipher_b64, $key, $iv) {
    $cipher_bytes = base64_decode($cipher_b64);
    // PHP openssl_decrypt 預設會自動處理 PKCS7 Unpad，但若是手動的話可用 OPENSSL_RAW_DATA
    $plain = openssl_decrypt($cipher_bytes, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
    if ($plain === false) {
        throw new Exception("Decryption failed");
    }
    return $plain; // openssl_decrypt 已經幫我們去除 Padding 了
}

function fourgtv_auth($header_key) {
    // 取得 UTC 當前日期 Ymd
    $gmt_date = gmdate("Ymd");
    $digest = hash("sha512", $gmt_date . $header_key, true);
    return base64_encode($digest);
}

class Tls13Client {
    public $device_uuid;
    public $device_mode;
    public $header_key;
    public $token;
    public $link_id;
    public $locale;
    public $api_user_agent;
    public $last_tls_version = "";
    public $last_cipher = "";
    public $last_request_headers = [];

    public function __construct($device_uuid, $device_mode="TV", $header_key=null, $token="", $link_id="", $locale="en-US", $api_user_agent=ANDROID_UA) {
        $this->device_uuid = $device_uuid;
        $this->device_mode = $device_mode;
        $this->header_key = $header_key;
        $this->token = $token ?: "";
        $this->link_id = $link_id ?: "";
        $this->locale = $locale;
        $this->api_user_agent = $api_user_agent ?: "";
    }

    public function request($method, $path, $body=null, $token=null, $link_id=null) {
        $body_bytes = "";
        if ($body !== null) {
            if (is_string($body)) {
                $body_bytes = $body;
            } else {
                $body_bytes = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        $token = $token === null ? $this->token : $token;
        $link_id = $link_id === null ? $this->link_id : $link_id;

        $headers = [
            "fsDEVICE" => $this->device_mode,
            "fsENC_KEY" => $this->device_uuid,
            "fsVERSION" => APP_VERSION,
        ];
        if ($method === "POST") {
            $headers["fsVALUE"] = $token ?: "";
            $headers["fsLINK_ID"] = $link_id ?: "";
        }
        $headers["Content-Type"] = "application/json";
        $headers["locale"] = $this->locale;

        if ($this->header_key) {
            $headers["4GTV_AUTH"] = fourgtv_auth($this->header_key);
        }
        if ($this->api_user_agent) {
            $headers["User-Agent"] = $this->api_user_agent;
        }
        $headers["Accept-Encoding"] = "gzip";
        $headers["Host"] = HOST;
        $headers["Connection"] = "Keep-Alive";

        if ($method === "POST") {
            $headers["Content-Length"] = strlen($body_bytes);
        }

        $this->last_request_headers = $headers;

        // 組裝原始 HTTP 請求字串
        $raw = "$method $path HTTP/1.1\r\n";
        foreach ($headers as $k => $v) {
            $raw .= "$k: $v\r\n";
        }
        $raw .= "\r\n" . $body_bytes;

        // 建立強制 TLS 1.3 的 Socket 上下文
        $context = stream_context_create([
            'ssl' => [
                'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT,
                'verify_peer'   => false,
                'verify_peer_name' => false,
                'alpn_protocols' => 'http/1.1'
            ]
        ]);

        $remote_socket = "tls://" . HOST . ":443";
        $sock = stream_socket_client($remote_socket, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
        if (!$sock) {
            throw new Exception("Socket connection failed: $errstr ($errno)");
        }
        stream_set_timeout($sock, 15);

        // 發送資料
        fwrite($sock, $raw);

        // 讀取響應
        $meta = stream_get_meta_data($sock);
        $this->last_tls_version = "TLSv1.3"; // 經由 context 強制指定
        $this->last_cipher = "UNKNOWN_AES"; 

        list($status, $response_headers, $response_body) = $this->_read_response($sock);
        fclose($sock);

        return [$status, $response_headers, $response_body];
    }

    public function post_json($path, $body, $token=null, $link_id=null) {
        list($status, $headers, $text) = $this->request("POST", path: $path, body: $body, token: $token, link_id: $link_id);
        $res = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("$path returned non-JSON body under $status: " . substr($text, 0, 500));
        }
        return [$status, $res];
    }

    public function get_json($path) {
        list($status, $headers, $text) = $this->request("GET", $path);
        $res = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("$path returned non-JSON body under $status: " . substr($text, 0, 500));
        }
        return [$status, $res];
    }

    private function _read_response($sock) {
        $buf = "";
        while (!str_contains($buf, "\r\n\r\n")) {
            $chunk = fread($sock, 4096);
            if ($chunk === false || $chunk === "") {
                break;
            }
            $buf .= $chunk;
        }

        list($header_bytes, $rest) = explode("\r\n\r\n", $buf, 2);
        $lower_headers = strtolower($header_bytes);
        
        $lines = explode("\r\n", $header_bytes);
        $status = $lines[0];

        // 1. Content-Length 處理
        if (preg_match('/content-length:\s*(\d+)/', $lower_headers, $matches)) {
            $want = (int)$matches[1];
            while (strlen($rest) < $want) {
                $rest .= fread($sock, 4096);
            }
            $body = substr($rest, 0, $want);
        } 
        // 2. Chunked 傳輸處理
        elseif (str_contains($lower_headers, "transfer-encoding: chunked")) {
            $body = "";
            $data = $rest;
            while (true) {
                while (!str_contains($data, "\r\n")) {
                    $data .= fread($sock, 4096);
                }
                list($line, $data) = explode("\r\n", $data, 2);
                // 提取 Hex 長度
                $parts = explode(";", $line, 2);
                $size = hexdec($parts[0]);
                if ($size == 0) {
                    break;
                }
                while (strlen($data) < $size + 2) {
                    $data .= fread($sock, 4096);
                }
                $body .= substr($data, 0, $size);
                $data = substr($data, $size + 2);
            }
        } 
        // 3. 其他情況直到斷開
        else {
            $body = $rest;
            while (!feof($sock)) {
                $body .= fread($sock, 4096);
            }
        }

        // Gzip 解壓
        if (str_contains($lower_headers, "content-encoding: gzip")) {
            $decompressed = @gzdecode($body);
            if ($decompressed !== false) {
                $body = $decompressed;
            }
        }

        return [$status, $header_bytes, $body];
    }
}

function load_system_properties($apk_path) {
    if (!$apk_path) {
        return [DEFAULT_SYSTEM_PROPERTIES, "builtin"];
    }
    if (!file_exists($apk_path)) {
        throw new Exception("APK not found: $apk_path");
    }
    $zip = new ZipArchive;
    if ($zip->open($apk_path) === TRUE) {
        $text = $zip->getFromName('assets/system.properties');
        $zip->close();
        if ($text === false) {
            throw new Exception("system.properties not found in APK");
        }
        return [parse_properties($text), $apk_path];
    } else {
        throw new Exception("Failed to open APK zip");
    }
}

function load_app_material($apk_path, $device_uuid) {
    list($props, $props_source) = load_system_properties($apk_path);
    $bootstrap = new Tls13Client($device_uuid);
    list($status, $config) = $bootstrap->post_json("/App/GetAPPConfig", ["fsDEVICE" => "TV", "fsVERSION" => APP_VERSION]);
    
    $header_key = decrypt_header_key($config["Data"]["header_key"], $props["key"], $props["iv"]);
    return [
        $props, $props_source, $header_key, $status, $config,
        $bootstrap->last_tls_version, $bootstrap->last_cipher
    ];
}

function extract_token($data) {
    if (!is_array($data)) return ["", ""];
    $token = $data["fsVALUE"] ?? $data["fsToken"] ?? $data["token"] ?? "";
    $link_id = $data["fsLINK_ID"] ?? $data["linkID"] ?? "";
    return [$token, $link_id];
}

function print_call($name, $client, $status) {
    echo "{$name}: {$status} tls={$client->last_tls_version} cipher={$client->last_cipher}\n";
}

function java_urlencode($value) {
    // 模擬 Java URLEncoder：把大部分特殊符號編碼，並強制將 '+' 改成 '%20'
    $encoded = urlencode((string)$value);
    // Java 在 `-_.*` 不會編碼，但 urlencode 會編碼某些，這裡做最核心的 %20 取代
    return str_replace('+', '%20', $encoded);
}

function encode_times($value, $times) {
    $value = (string)$value;
    for ($i = 0; $i < max($times, 0); $i++) {
        $value = java_urlencode($value);
    }
    return $value;
}

function tag_encode_times($tag, $default) {
    if (preg_match('/_e(\d+)/i', $tag, $matches)) {
        return (int)$matches[1];
    }
    return $default;
}

function tag_replace($media_url, $device_uuid, $adid, $webview_ua, $channel, $asset_id, $uid2="") {
    $media_title = $channel["fsNAME"] ?? $channel["fsCHANNEL_NAME"] ?? "";
    $values = [
        "appname"         => [APP_NAME, 4],
        "adid"            => [$adid, 0],
        "is-lat"          => ["0", 0],
        "user-agent"      => [$webview_ua, 4],
        "useragent"       => [$webview_ua, 4],
        "vtitle"          => [$media_title, 4],
        "assetid"         => [$asset_id, 0],
        "deviceid"        => [$device_uuid, 0],
        "timestamp"       => [(string)add_milliseconds(), 0],
        "app_bundle"      => [APP_BUNDLE, 0],
        "uid2"            => [$uid2, 4],
        "vkind"           => ["live", 0],
        "vtype"           => ["", 1],
        "referrer_url"    => ["https://www.4gtv.tv/", 1],
        "description_url" => ["https://www.4gtv.tv/", 1],
    ];

    // 毫秒時間戳模擬
    function add_milliseconds() {
        return round(microtime(true) * 1000);
    }

    return preg_replace_callback('/\[(.*?)]/', function($match) use ($values) {
        $tag = $match[0];
        $key = strtolower(substr($tag, 1, -1));
        $key = preg_replace('/_e\d+$/i', '', $key);
        
        if (!isset($values[$key])) {
            return "";
        }
        list($value, $default_times) = $values[$key];
        return encode_times($value, tag_encode_times($tag, $default_times));
    }, $media_url);
}

function select_channel($channels_payload, $channel_id=null, $asset_id="", $channel_name="") {
    $channels = $channels_payload["Data"] ?? [];
    if ($channel_id && $asset_id) {
        foreach ($channels as $channel) {
            if (($channel["fnID"] ?? null) == $channel_id && ($channel["fs4GTV_ID"] ?? null) == $asset_id) return $channel;
        }
    }
    if ($channel_id) {
        foreach ($channels as $channel) {
            if (($channel["fnID"] ?? null) == $channel_id) return $channel;
        }
    }
    if ($asset_id) {
        foreach ($channels as $channel) {
            if (($channel["fs4GTV_ID"] ?? null) == $asset_id) return $channel;
        }
    }
    if ($channel_name) {
        foreach ($channels as $channel) {
            if (str_contains($channel["fsNAME"] ?? "", $channel_name)) return $channel;
        }
    }
    return null;
}

function print_response_shape($name, $status, $payload) {
    echo "{$name}: {$status}\n";
    echo "  Success=" . ($payload['Success'] ? 'true' : 'false') . " Status=" . ($payload['Status'] ?? '') . " ErrMessage=" . ($payload['ErrMessage'] ?? '') . "\n";
    $data = $payload["Data"] ?? null;
    if (is_array($data)) {
        if (count($data) > 0 && array_keys($data) !== range(0, count($data) - 1)) {
            $keys = array_keys($data);
            sort($keys);
            echo "  Data keys=" . implode(', ', $keys) . "\n";
            if (isset($data["flstURLs"])) {
                echo "  flstURLs_count=" . count($data["flstURLs"]) . "\n";
            }
        } else {
            echo "  Data list count=" . count($data) . "\n";
            if (count($data) > 0 && is_array($data[0])) {
                $keys = array_keys($data[0]);
                sort($keys);
                echo "  first keys=" . implode(', ', $keys) . "\n";
            }
        }
    } else {
        echo "  Data type=" . gettype($data) . "\n";
    }
}

function qr_login($client, $qr_out, $timeout_sec) {
    list($status, $payload) = $client->post_json("/Tv/GetQRCode", "");
    print_response_shape("GetQRCode", $status, $payload);
    $data = $payload["Data"] ?? [];
    $session_id = $data["SessionID"] ?? "";
    $qr_b64 = $data["Base64"] ?? "";
    if ($qr_out && $qr_b64) {
        file_put_contents($qr_out, base64_decode($qr_b64));
        echo "  QR image written: {$qr_out}\n";
    }
    if (!$session_id) return ["", ""];

    $deadline = time() + $timeout_sec;
    while (time() < $deadline) {
        sleep(6);
        list($status, $payload) = $client->post_json("/Tv/CheakQRCodePolling", ["SessionID" => $session_id]);
        print_response_shape("CheakQRCodePolling", $status, $payload);
        if (($payload["Status"] ?? 0) === 200 || ($payload["Success"] ?? false)) {
            return extract_token($payload["Data"] ?? []);
        }
        if (!in_array($payload["Status"] ?? null, [6005, null])) {
            break;
        }
    }
    return ["", ""];
}

// ---- MAIN PROCESSING ----

$apk = $options["apk"] ?? "";
$device_uuid = $options["device-uuid"] ?? uuid4();
echo "device_uuid={$device_uuid}" . (isset($options["device-uuid"]) ? " (provided)\n" : " (random)\n");
$adid = $options["adid"] ?? uuid4();

$locale = $options["locale"] ?? "en-US";
$api_user_agent = $options["api-user-agent"] ?? ANDROID_UA;
$webview_ua = $options["webview-ua"] ?? WEBVIEW_UA;
$uid2 = $options["uid2"] ?? "";

list($props, $props_source, $header_key, $config_status, $config, $config_tls, $config_cipher) = load_app_material($apk, $device_uuid);

$props_keys = array_keys($props); sort($props_keys);
echo "system.properties source={$props_source} keys=" . implode(', ', $props_keys) . "\n";
echo "AppConfig: {$config_status} tls={$config_tls} cipher={$config_cipher} header_key_len=" . strlen($header_key) . "\n";

$client = new Tls13Client($device_uuid, "TV", $header_key, "", "", $locale, $api_user_agent);
list($status, $channels) = $client->get_json("/Channel/GetAllChannel2/TV");
print_call("GetAllChannel2", $client, $status);
print_response_shape("GetAllChannel2", $status, $channels);

if (isset($options["list-channels"])) {
    foreach (($channels["Data"] ?? []) as $ch) {
        echo "{$ch['fnID']}\t{$ch['fs4GTV_ID']}\t{$ch['fsNAME']}\tfree={$ch['fcFREE']}\n";
    }
    exit(0);
}

$channel_id = isset($options["channel-id"]) ? (int)$options["channel-id"] : 31;
$asset_id = $options["asset-id"] ?? "litv-ftv13";
$channel_name = $options["channel-name"] ?? "";

$channel = select_channel($channels, $channel_id, $asset_id, $channel_name);
if (!$channel) {
    echo "channel not found; use --list-channels to inspect available channels\n";
    exit(1);
}

$channel_id = $channel["fnID"];
$asset_id = $channel["fs4GTV_ID"] ?? $asset_id;
echo "Selected channel: fnID={$channel_id} fs4GTV_ID={$asset_id} fsNAME={$channel['fsNAME']} fcFREE={$channel['fcFREE']}\n";

$token = $options["token"] ?? "";
$link_id = $options["link-id"] ?? "";

if (isset($options["account"]) && isset($options["password"])) {
    list($status, $payload) = $client->post_json("/AppAccount/SignIn", [
        "fsUSER" => $options["account"],
        "fsPASSWORD" => $options["password"],
        "fsENC_KEY" => $device_uuid
    ]);
    print_response_shape("SignIn", $status, $payload);
    list($token, $link_id) = extract_token($payload["Data"] ?? []);
} elseif (isset($options["qr-login"])) {
    $qr_out = $options["qr-out"] ?? "/tmp/4gtv_qr.png";
    $qr_timeout = isset($options["qr-timeout"]) ? (int)$options["qr-timeout"] : 90;
    list($token, $link_id) = qr_login($client, $qr_out, $qr_timeout);
} elseif (isset($options["guest-pv1"]) || isset($options["guest-pv2"])) {
    list($status, $payload) = $client->post_json("/TV/GuestSignUp", [
        "fsFROM" => "TV",
        "fsPARTNER_VALUE1" => $options["guest-pv1"] ?? "",
        "fsPARTNER_VALUE2" => $options["guest-pv2"] ?? "",
        "fsENC_KEY" => $device_uuid
    ]);
    print_response_shape("GuestSignUp", $status, $payload);
    list($token, $link_id) = extract_token($payload["Data"] ?? []);
}

$client->token = $token;
$client->link_id = $link_id;

$request_body = [
    "fnCHANNEL_ID" => $channel_id,
    "fsASSET_ID" => $asset_id,
    "fsDEVICE_TYPE" => "tv",
    "clsAPP_IDENTITY_VALIDATE_ARUS" => [
        "fsVALUE" => $token,
        "fsENC_KEY" => $device_uuid
    ]
];

echo "GetChannelUrl request body:\n";
echo json_encode($request_body, JSON_UNESCAPED_UNICODE) . "\n";

list($status, $payload) = $client->post_json("/TV/GetChannelUrl", $request_body);
print_call("GetChannelUrl", $client, $status);
echo "GetChannelUrl request headers:\n";
foreach ($client->last_request_headers as $k => $v) {
    echo "  $k: $v\n";
}
print_response_shape("GetChannelUrl", $status, $payload);

if (!($payload["Success"] ?? false)) {
    echo "GetChannelUrl raw response:\n";
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    exit(2);
}

$data = $payload["Data"] ?? [];
$raw_urls = $data["flstURLs"] ?? [];
$final_urls = [];
foreach ($raw_urls as $url) {
    $final_urls[] = tag_replace($url, $device_uuid, $adid, $webview_ua, $channel, $asset_id, $uid2);
}

echo "Raw flstURLs:\n";
foreach ($raw_urls as $index => $url) {
    echo "[$index] $url\n";
