<?php
// 停用廢棄警告輸出，避免干擾 Header 或 M3U8 格式
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);

// --- 1. TS 代理 ---
if (isset($_GET['ts'])) {
    $ts_url = $_GET['ts'];
    $ch = curl_init($ts_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, "cctv_app_tv");
    $tsData = curl_exec($ch);
    // PHP 8+ 不再需要 curl_close
    header("Content-Type: video/MP2T");
    echo $tsData;
    exit;
}

// --- 2. 配置 ---
const UA = 'cctv_app_tv';
const REFERER = 'api.cctv.cn';
const PUB_KEY = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC/ZeLwTPPLSU7QGwv6tVgdawz9n7S2CxboIEVQlQ1USAHvBRlWBsU2l7+HuUVMJ5blqGc/5y3AoaUzPGoXPfIm0GnBdFL+iLeRDwOS1KgcQ0fIquvr/2Xzj3fVA1o4Y81wJK5BP8bDTBFYMVOlOoCc1ZzWwdZBYpb4FNxt//5dAwIDAQAB';

$cctvList = [
    'cctv1' => 'Live1717729995180256', 'cctv2' => 'Live1718261577870260',
    'cctv3' => 'Live1718261955077261', 'cctv4' => 'Live1718276148119264',
    'cctv5' => 'Live1719474204987287', 'cctv5p' => 'Live1719473996025286',
    'cctv7' => 'Live1718276412224269', 'cctv8' => 'Live1718276458899270',
    'cctv9' => 'Live1718276503187272', 'cctv10' => 'Live1718276550002273',
    'cctv11' => 'Live1718276603690275', 'cctv12' => 'Live1718276623932276',
    'cctv13' => 'Live1718276575708274', 'cctv14' => 'Live1718276498748271',
    'cctv15' => 'Live1718276319614267', 'cctv16' => 'Live1718276256572265',
    'cctv17' => 'Live1718276138318263', 'cctv4k' => 'Live1704872878572161'
];

// --- 3. 核心請求函數 ---
function apiRequest($url, $payload, $uid) {
    $headers = [
        'Content-Type: application/json',
        'UID: ' . $uid,
        'Referer: ' . REFERER,
        'User-Agent: ' . UA
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    return json_decode($res, true);
}

// --- 4. 取得 M3U8 內容 ---
function getM3uContent($channelId, $uid) {
    global $cctvList;
    if (!isset($cctvList[$channelId])) return "Error: Channel Not Found";
    $liveID = $cctvList[$channelId];

    $pubKey = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(PUB_KEY, 64, "\n") . "-----END PUBLIC KEY-----";
    
    // Step 1: Register
    openssl_public_encrypt($uid, $encUid, $pubKey);
    $reg = apiRequest('https://cctv.cn', [
        'device_name' => '央視頻電視投屏助手',
        'device_id' => base64_encode($encUid)
    ], $uid);
    $guid = $reg['data']['guid'] ?? '';

    // Step 2: Secret
    openssl_public_encrypt($guid, $encGuid, $pubKey);
    $sec = apiRequest('https://cctv.cn', ['guid' => base64_encode($encGuid)], $uid);
    $encSecret = $sec['data']['appSecret'] ?? '';
    openssl_public_decrypt(base64_decode($encSecret), $appSecret, $pubKey);

    // Step 3: Base URL
    $base = apiRequest('https://cctv.cn', [
        'id' => $liveID,
        'systemType' => 'android',
        'deviceId' => ['android_id' => $uid]
    ], $uid);
    
    // 強化解析：嘗試從 videoList[0] 或直接從 videoList 獲取 url
    $videoData = $base['data']['videoList'] ?? [];
    $baseUrl = $videoData[0]['url'] ?? ($videoData['url'] ?? '');

    if (!$baseUrl) return "Error: Failed to get Base URL. API Response: " . json_encode($base);

    // Step 4: Stream URL
    $appRandomStr = uniqid();
    $appSign = md5('5f39826474a524f95d5f436eacfacfb67457c4a7' . $appSecret . $appRandomStr);
    
    $ch = curl_init('https://cctv.cn');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'appcommon' => json_encode(["adid"=>$uid, "av"=>"1.3.4", "an"=>"央視視頻電視投屏助手", "ap"=>"cctv_app_tv"]),
        'url' => $baseUrl
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "UID: $uid", "APPID: 5f39826474a524f95d5f436eacfacfb67457c4a7",
        "APPSIGN: $appSign", "APPRANDOMSTR: $appRandomStr", "User-Agent: ".UA
    ]);
    $res = json_decode(curl_exec($ch), true);
    $streamUrl = $res['url'] ?? '';

    if (!$streamUrl) return "Error: Stream URL empty.";

    // Step 5: Content
    $content = file_get_contents($streamUrl);
    if (!$content) return "Error: Cannot fetch stream content.";
    
    $path = substr($streamUrl, 0, strrpos($streamUrl, '/') + 1);
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $self = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];

    return preg_replace_callback('/^[^#\s]+\.ts(\?.*)?$/m', function($m) use ($path, $self, $uid) {
        $tsUrl = (stripos($m[0], 'http') === 0) ? $m[0] : $path . trim($m[0]);
        return $self . "?ts=" . urlencode($tsUrl) . "&uid=" . $uid;
    }, $content);
}

// --- 5. 輸出 ---
$id = $_GET['id'] ?? 'cctv4k';
$uid = $_GET['uid'] ?? bin2hex(random_bytes(8));

$result = getM3uContent($id, $uid);

if (strpos($result, 'Error') === 0) {
    header("Content-Type: text/plain; charset=utf-8");
    echo $result;
} else {
    header("Content-Type: application/x-mpegURL");
    echo $result;
}
