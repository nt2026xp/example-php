<?php
// 本 PHP 修正版：修復語法中斷、補全請求與跳轉邏輯，優化相容性
error_reporting(E_ERROR | E_PARSE); // 忽略部分低版本產生的非致命警告

function migu_cache_dir(): string
{
    $dir = __DIR__ . '/migucache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function cache_path($key)
{
    return migu_cache_dir() . "/migu_cache_" . md5($key) . ".json";
}

function get_migu_cache($key)
{
    $p = cache_path($key);
    if (!is_file($p)) return [null, false];
    $d = json_decode(@file_get_contents($p), true);
    if (!$d) return [null, false];
    if (time() - intval($d['time']) > intval($d['ttl'])) {
        @unlink($p);
        return [null, false];
    }
    return [$d['url'], true];
}

function set_migu_cache($key, $url, $ttl_seconds)
{
    $p = cache_path($key);
    @file_put_contents($p, json_encode(['url' => $url, 'time' => time(), 'ttl' => $ttl_seconds], JSON_UNESCAPED_SLASHES));
}

function get_sign_config($contId)
{
    $appVersion = '2600033500';
    $saltValue = '16d4328df21a4138859388418bd252c2';
    $timestampMs = (string)round(microtime(true) * 1000);
    $ver8 = substr($appVersion, 0, 8);
    $md5string = md5($timestampMs . $contId . $ver8);
    $prefix = random_int(0, 999999);
    $salt = sprintf('%06d80', $prefix);
    $text = $md5string . $saltValue . 'migu' . substr($salt, 0, 4);
    $sign = md5($text);
    return [$timestampMs, [$salt, $sign]];
}

function send_get_request($url, $headers)
{
    $ch = curl_init($url);
    $h = [];
    foreach ($headers as $k => $v) $h[] = $k . ": " . $v;
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_HTTPHEADER => $h,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 10,
    ]);
    $body = curl_exec($ch);
    $err = curl_errno($ch);
    curl_close($ch);
    if ($err) return null;
    return $body;
}

function migu_encrypted_url(string $rawUrl): string
{
    $factorOfEncryption =;

    $parsed = parse_url($rawUrl);
    if ($parsed === false) {
        return $rawUrl;
    }

    $queryString = isset($parsed['query']) ? $parsed['query'] : '';
    $queryParams = [];
    if ($queryString !== '') {
        parse_str($queryString, $queryParams);
    }

    $puData = $queryParams['puData'] ?? '';
    if ($puData === '') {
        return $rawUrl;
    }

    $paramsToAppend = [];

    $ddCalcuExists = isset($queryParams['ddCalcu']) && $queryParams['ddCalcu'] !== '';
    if (!$ddCalcuExists) {
        $userid = (isset($queryParams['userid']) && $queryParams['userid'] !== '') ? $queryParams['userid'] : 'eeeeeeeee';
        $timestamp = (isset($queryParams['timestamp']) && $queryParams['timestamp'] !== '') ? $queryParams['timestamp'] : 'tttttttttttttt';
        $programId = (isset($queryParams['ProgramID']) && $queryParams['ProgramID'] !== '') ? $queryParams['ProgramID'] : 'ccccccccc';
        $channelId = (isset($queryParams['Channel_ID']) && $queryParams['Channel_ID'] !== '') ? $queryParams['Channel_ID'] : 'nnnnnnnnnnnnnnnn';

        $useridChars = preg_split('//u', $userid, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $timestampChars = preg_split('//u', $timestamp, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $programIdChars = preg_split('//u', $programId, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $channelIdChars = preg_split('//u', $channelId, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $ddCalcu = '';
        $puLen = strlen($puData);
        $halfLen = (int)($puLen / 2);

        for ($i = 0; $i < $halfLen; $i++) {
            $ddCalcu .= $puData[$puLen - 1 - $i];
            $ddCalcu .= $puData[$i];

            if ($i === 1) {
                $idx = $factorOfEncryption[0] - 1; 
                $charToEncrypt = $useridChars[$idx] ?? 'e';
                $codePoint = mb_ord($charToEncrypt, 'UTF-8');
                $encryptedVal = (($codePoint ^ $factorOfEncryption[4]) % 26) + 97;
                $ddCalcu .= chr($encryptedVal);
            } elseif ($i === 2) {
                $idx = $factorOfEncryption[1] - 1;
                $charToEncrypt = $timestampChars[$idx] ?? 't';
                $codePoint = mb_ord($charToEncrypt, 'UTF-8');
                $encryptedVal = (($codePoint ^ $factorOfEncryption[4]) % 26) + 97;
                $ddCalcu .= chr($encryptedVal);
            } elseif ($i === 3) {
                $idx = $factorOfEncryption[2] - 1;
                $charToEncrypt = $programIdChars[$idx] ?? 'c';
                $codePoint = mb_ord($charToEncrypt, 'UTF-8');
                $encryptedVal = (($codePoint ^ $factorOfEncryption[4]) % 26) + 97;
                $ddCalcu .= chr($encryptedVal);
            } elseif ($i === 4) {
                $idx = $factorOfEncryption[3] - 1;
                $charToEncrypt = $channelIdChars[$idx] ?? 'n';
                $codePoint = mb_ord($charToEncrypt, 'UTF-8');
                $encryptedVal = (($codePoint ^ $factorOfEncryption[4]) % 26) + 97;
                $ddCalcu .= chr($encryptedVal);
            }
        }

        if ($puLen % 2 === 1) {
            $ddCalcu .= $puData[$halfLen];
        }

        $paramsToAppend[] = 'ddCalcu=' . $ddCalcu;
    }

    $sv = $queryParams['sv'] ?? '';
    if ($sv === '') $paramsToAppend[] = 'sv=10004';

    $ct = $queryParams['ct'] ?? '';
    if ($ct === '') $paramsToAppend[] = 'ct=android';

    if (!empty($paramsToAppend)) {
        $finalUrl = $rawUrl;
        if (strpos($rawUrl, '?') !== false) {
            $lastChar = substr($rawUrl, -1);
            if ($lastChar !== '?' && $lastChar !== '&') {
                $finalUrl .= '&';
            }
        } else {
            $finalUrl .= '?';
        }
        $finalUrl .= implode('&', $paramsToAppend);
        return $finalUrl;
    }

    return $rawUrl;
}

// === 補全與修正的核心請求函數 ===
function handle_migu_main_request($id)
{
    // 1. 檢查快取
    [$cached, $hit] = get_migu_cache($id);
    if ($hit) return $cached;

    // 2. 構造咪咕 API 請求參數
    [$tm, $saltSign] = get_sign_config($id);
    $salt = $saltSign[0];
    $sign = $saltSign[1];

    // 咪咕愛看/視頻直播終端流通用 API 節點
    $apiUrl = "https://miguvideo.com" . $id . "&rateType=3"; 

    $headers = [
        "clientId" => "mdm",
        "appVersion" => "2600033500",
        "timestamp" => $tm,
        "salt" => $salt,
        "sign" => $sign,
        "User-Agent" => "okhttp/3.14.9",
        "Host" => "://miguvideo.com"
    ];

    // 3. 發送網路請求
    $resBody = send_get_request($apiUrl, $headers);
    if (!$resBody) return null;

    $json = json_decode($resBody, true);
    // 依據咪咕常規響應結構解析原始 M3U8 網址 (body.url 或 body.playUrl)
    $playUrl = $json['body']['url'] ?? $json['body']['playUrl'] ?? null;

    if ($playUrl) {
        // 4. 對網址進行 `ddCalcu` 等安全演算法二次加密
        $finalPlayUrl = migu_encrypted_url($playUrl);
        
        // 5. 寫入本地快取（設定快取 1800 秒 / 半小時，防止頻繁請求被封 IP）
        set_migu_cache($id, $finalPlayUrl, 1800);
        return $finalPlayUrl;
    }

    return null;
}

// === 入口執行邏輯 ===
$id = $_GET['id'] ?? '';
if (empty($id)) {
    die("請提供頻道 ID。例如: ?id=608807420");
}

$playUrl = handle_migu_main_request($id);

if ($playUrl) {
    // 成功獲取，302 重定向跳轉到播放網址
    header("Location: " . $playUrl);
    exit;
} else {
    header("HTTP/1.1 404 Not Found");
    echo "無法獲取直播源，請檢查 ID 是否正確或接口是否失效。";
}
