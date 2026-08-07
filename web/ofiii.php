<?php
/**
 * ofiii.php  —  Ofiii 直播代理
 */

// ====================== 配置 ======================
define('PROXY_ENABLED', false);
define('PROXY_ADDR', '');
define('PROXY_AUTH', '');

// ====================== 輔助函數 ======================
function curl_get($url, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36');

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    if (PROXY_ENABLED) {
        curl_setopt($ch, CURLOPT_PROXY, PROXY_ADDR);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, PROXY_AUTH);
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME); 
    }

    $response = curl_exec($ch);
    
    // 增加错误排查提示
    if ($response === false) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        die("cURL 請求失敗。錯誤原因: " . $error_msg . " (請檢查 Socks5 代理是否在線或賬密是否正確)");
    }

    curl_close($ch);
    return $response;
}

function random_str($length = 8) {
    $strs = '0123456789abcdef';
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $strs[random_int(0, strlen($strs) - 1)];
    }
    return $result;
}

// ====================== 主流程 ======================
$id = $_GET['id'] ?? null;
if (!$id) {
    die('缺少 id 參數');
}

// 1. 獲取 device_id
$device_json = curl_get("https://www.ofiii.com/api/deviceId");
$device_data = json_decode($device_json, true);
$device_id   = $device_data['deviceId'] ?? $device_data['device_id'] ?? null;

// 生成隨機 device_id
if (empty($device_id)) {
    $device_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// 2. 生成 puid
$puid = random_str(8) . "-" . random_str(4) . "-" . random_str(4) . "-" . random_str(4) . "-" . random_str(12);

// 3. 請求視頻地址 API
$api_url = "https://cdi.ofiii.com/ofiii_cdi/video/urls?" . http_build_query([
    'device_type'   => 'pc',
    'device_id'     => $device_id,
    'media_type'    => 'channel',
    'asset_id'      => $id,
    'project_num'   => 'OFWEB00',
    'puid'          => $puid,
    '_t'            => time()
]);

$headers = [
    'Origin: https://www.ofiii.com',
    'Referer: https://www.ofiii.com/',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36'
];

$response = curl_get($api_url, $headers);

$data = json_decode($response, true);

if (empty($data['asset_urls'][0])) {
    die('未獲取到播放地址，可能被 Ofiii 官方阻擋（IP 非台灣或已被拉黑）');
}

// 4. 請求 master m3u8 並解析最高碼率
$master_url = $data['asset_urls'][0];

$m3u8_content = curl_get($master_url, $headers);

if (!$m3u8_content) {
    // 如果拿不到 m3u8，直接返回 master_url
    header("Location: " . $master_url);
    exit;
}

$best_url = parseHighestBitrate($m3u8_content, $master_url);

// 最終輸出播放地址
header("Location: " . $best_url);
exit;

// ====================== 解析最高碼率 ======================
function parseHighestBitrate($content, $masterUrl) {
    $lines = explode("\n", $content);
    $max_bandwidth = 0;
    $best_link = '';

    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#EXT-X-STREAM-INF') !== false) {
            if (preg_match('/BANDWIDTH=(\d+)/i', $line, $matches)) {
                $current = (int)$matches[1];
                if ($current > $max_bandwidth) {
                    $max_bandwidth = $current;
                }
            }
        } elseif ($line && $line[0] !== '#' && $max_bandwidth > 0) {
            $best_link = $line;
        }
    }

    if (!$best_link) {
        return $masterUrl;
    }

    // 補全相對路徑
    if (strpos($best_link, 'http') === false) {
        $base = substr($masterUrl, 0, strrpos($masterUrl, '/') + 1);
        $best_link = $base . $best_link;
    }

    return $best_link;
}
