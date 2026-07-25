<?php
/**
 * 江蘇衛視 (荔枝網) 直播源動態解析腳本
 * 功能：自動獲取帶有最新 Token 鑑權的動態 m3u8 連結並跳轉播放
 */

// 1. 設定標頭，指定輸出格式與編碼
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *"); // 允許跨域

// 2. 荔枝網江蘇衛視官方直播的 API 請求資訊
// 備註：網頁端背後請求的動態金鑰 API
$apiUrl = "https://jstv.com"; 
$channelId = "1"; // 江蘇衛視的頻道 ID 通常為 1

// 3. 建立請求後台 API 的參數
$postData = json_encode([
    'channel_id' => $channelId,
    'client_type' => 'pc', // 模擬 PC 網頁端
    'timestamp' => time()
]);

// 4. 使用 cURL 模擬瀏覽器發送 POST 請求
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Referer: https://jstv.com', // 極為重要：防盜鏈檢驗來源網址
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
]);

$response = curl_exec($ch);
curl_close($ch);

// 5. 解析 API 回傳的 JSON 數據
if ($response) {
    $data = json_decode($response, true);
    
    // 判斷是否成功取得動態直播位址
    if (isset($data['code']) && $data['code'] == 200 && !empty($data['data']['m3u8_url'])) {
        $liveM3u8Url = $data['data']['m3u8_url'];
        
        // 核心步驟：直接 302 重導向到最新帶 Token 的 m3u8 直播源
        header("Location: " . $liveM3u8Url, true, 302);
        exit;
    }
}

// 6. 若無回應或解析失敗，回傳錯誤代碼
http_response_code(404);
echo json_encode(["error" => "無法獲取直播源，請檢查接口或防盜鏈機制是否變更。"]);
?>
