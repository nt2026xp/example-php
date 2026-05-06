<?php
// --- 1. TS 檔案代理轉發部分 (必須放在最前面並 exit) ---
if (isset($_GET['ts'])) {
    $ts_url = $_GET['ts'];
    $headers = [
        "User-Agent: cctv_app_tv",
        "Referer: api.cctv.cn",
    ];

    $ch = curl_init($ts_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 追蹤 TS 可能發生的重導向
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $tsData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        header("Content-Type: video/MP2T");
        header("Content-Length: " . strlen($tsData));
        echo $tsData;
    } else {
        http_response_code($httpCode);
    }
    exit; // 務必退出，否則會輸出下方的 m3u8 內容
}

// --- 2. 常數與配置 ---
const APP_ID = '5f39826474a524f95d5f436eacfacfb67457c4a7';
const APP_VERSION = '1.3.4';
const UA = 'cctv_app_tv';
const REFERER = 'api.cctv.cn';
const PUB_KEY = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC/ZeLwTPPLSU7QGwv6tVgdawz9n7S2CxboIEVQlQ1USAHvBRlWBsU2l7+HuUVMJ5blqGc/5y3AoaUzPGoXPfIm0GnBdFL+iLeRDwOS1KgcQ0fIquvr/2Xzj3fVA1o4Y81wJK5BP8bDTBFYMVOlOoCc1ZzWwdZBYpb4FNxt//5dAwIDAQAB';
const URL_CLOUDWS_REGISTER = 'https://ytpcloudws.cctv.cn/cloudps/wssapi/device/v1/register';
const URL_GET_BASE = 'https://ytpaddr.cctv.cn/gsnw/live';
const URL_GET_APP_SECRET = 'https://ytpaddr.cctv.cn/gsnw/tpa/sk/obtain';
const URL_GET_STREAM = 'https://ytpvdn.cctv.cn/cctvmobileinf/rest/cctv/videoliveUrl/getstream';

$cctvList = [
    'cctv1'    => 'Live1717729995180256', 'cctv2'    => 'Live1718261577870260',
    'cctv3'    => 'Live1718261955077261', 'cctv4'    => 'Live1718276148119264',
    'cctv5'    => 'Live1719474204987287', 'cctv5p'   => 'Live1719473996025286',
    'cctv7'    => 'Live1718276412224269', 'cctv8'    => 'Live1718276458899270',
    'cctv9'    => 'Live1718276503187272', 'cctv10'   => 'Live1718276550002273',
    'cctv11'   => 'Live1718276603690275', 'cctv12'   => 'Live1718276623932276',
    'cctv13'   => 'Live1718276575708274', 'cctv14'   => 'Live1718276498748271',
    'cctv15'   => 'Live1718276319614267', 'cctv16'   => 'Live1718276256572265',
    'cctv17'   => 'Live1718276138318263', 'cgtnen'   => 'Live1719392219423280',
    'cgtnfr'   => 'Live1719392670442283', 'cgtnru'   => 'Live1719392779653284',
    'cgtnar'   => 'Live1719392885692285', 'cgtnes'   => 'Live1719392560433282',
    'cgtndoc'  => 'Live1719392360336281', 'cctv4k16' => 'Live1704966749996185',
    'cctv4k'   => 'Live1704872878572161', 'cctv8k'   => 'Live1688400593818102',
];

// --- 3. 工具函數 ---
function generateAndroidID() {
    return bin2hex(random_bytes(8));
}

function encryptByPublicKey($data, $pubKeyStr) {
    $pubKey = openssl_pkey_get_public("-----BEGIN PUBLIC KEY-----\n" . chunk_split($pubKeyStr, 64, "\n") . "-----END PUBLIC KEY-----");
    openssl_public_encrypt($data, $encrypted, $pubKey);
    return base64_encode($encrypted);
}

function decryptByPublicKey($encryptedStr, $pubKeyStr) {
    $pubKey = openssl_pkey_get_public("-----BEGIN PUBLIC KEY-----\n" . chunk_split($pubKeyStr, 64, "\n") . "-----END PUBLIC KEY-----");
    $encrypted = base64_decode($encryptedStr);
    openssl_public_decrypt($encrypted, $decrypted, $pubKey);
    return $decrypted;
}

function httpPost($url, $data, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// --- 4. 業務邏輯 ---
function getGUID($uid) {
    $encryptedUID = encryptByPublicKey($uid, PUB_KEY);
    $requestBody = json_encode(['device_name' => '央視頻電視投屏助手', 'device_id' => $encryptedUID]);
    $headers = ['Accept: application/json', "UID: $uid", "Referer: " . REFERER, "User-Agent: " . UA, 'Content-Type: application/json'];
    $result = json_decode(httpPost(URL_CLOUDWS_REGISTER, $requestBody, $headers), true);
    return $result['data']['guid'] ?? '';
}

function getAppSecret($guid, $uid) {
    $encryptedGUID = encryptByPublicKey($guid, PUB_KEY);
    $requestBody = json_encode(['guid' => $encryptedGUID]);
    $headers = ['Accept: application/json', "UID: $uid", "Referer: " . REFERER, "User-Agent: " . UA, 'Content-Type: application/json'];
    $result = json_decode(httpPost(URL_GET_APP_SECRET, $requestBody, $headers), true);
    return decryptByPublicKey($result['data']['appSecret'], PUB_KEY);
}

function getBaseM3uUrl($liveID, $uid) {
    $requestBody = json_encode([
        'rate' => '', 'systemType' => 'android', 'model' => '', 'id' => $liveID,
        'userId' => '', 'clientSign' => 'cctvVideo',
        'deviceId' => ['serial' => '', 'imei' => '', 'android_id' => $uid]
    ]);
    $headers = ['Accept: application/json', "UID: $uid", "Referer: " . REFERER, "User-Agent: " . UA, 'Content-Type: application/json'];
    $result = json_decode(httpPost(URL_GET_BASE, $requestBody, $headers), true);
    return $result['data']['videoList'][0]['url'] ?? '';
}

function getM3uUrl($channelLiveID, $uid) {
    $guid = getGUID($uid);
    $appSecret = getAppSecret($guid, $uid);
    $baseUrl = getBaseM3uUrl($channelLiveID, $uid);
    
    $appRandomStr = uniqid();
    $appSign = md5(APP_ID . $appSecret . $appRandomStr);

    $postData = [
        'appcommon' => json_encode(["adid" => $uid, "av" => APP_VERSION, "an" => "央視視頻電視投屏助手", "ap" => "cctv_app_tv"]),
        'url' => $baseUrl,
    ];
    $headers = ["User-Agent: " . UA, "Referer: " . REFERER, "UID: $uid", "APPID: " . APP_ID, "APPSIGN: $appSign", "APPRANDOMSTR: $appRandomStr"];
    
    $result = json_decode(httpPost(URL_GET_STREAM, $postData, $headers), true);
    $streamUrl = $result['url'] ?? '';

    if (!$streamUrl) return "Error: Cannot get Stream URL";

    // 遞歸獲取最終 M3U8 (處理 Master Playlist)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: " . UA, "Referer: " . REFERER, "UID: $uid"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    while (true) {
        curl_setopt($ch, CURLOPT_URL, $streamUrl);
        $data = curl_exec($ch);
        // 如果內容包含新的 m3u8 連結，則繼續抓取
        if (preg_match('/(.*\.m3u8(\?.*)?)/', $data, $matches)) {
            $path = substr($streamUrl, 0, strrpos($streamUrl, '/') + 1);
            $nextFile = trim($matches[1]);
            $streamUrl = (stripos($nextFile, 'http') === 0) ? $nextFile : $path . $nextFile;
        } else {
            break;
        }
    }
    curl_close($ch);

    // 準備代理路徑
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $proxyUrl = $protocol . "://" . $host . $_SERVER['SCRIPT_NAME'];
    $finalPath = substr($streamUrl, 0, strrpos($streamUrl, '/') + 1);

    // 取代 TS 連結為本地代理連結
    return preg_replace_callback('/^[^#\s]+\.ts(\?.*)?$/m', function($matches) use ($proxyUrl, $uid, $finalPath) {
        $ts_file = trim($matches[0]);
        $ts_full_url = (stripos($ts_file, 'http') === 0) ? $ts_file : $finalPath . $ts_file;
        return $proxyUrl . "?ts=" . urlencode($ts_full_url) . "&uid=" . urlencode($uid);
    }, $data);
}

// --- 5. 主程式入口 ---
$uid = $_GET['uid'] ?? generateAndroidID();
$id = $_GET['id'] ?? 'cctv4k';

if (!isset($cctvList[$id])) {
    die("Invalid Channel ID");
}

header("Content-Type: application/x-mpegURL");
header("Content-Disposition: inline; filename=\"$id.m3u8\"");
echo getM3uUrl($cctvList[$id], $uid);
