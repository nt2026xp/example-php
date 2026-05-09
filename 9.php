<?php
// get_stream.php
header('Content-Type: application/json');

function getCctvM3u8($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // 模擬手機瀏覽器，避免被阻擋
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.3 Mobile/15E148 Safari/104.1");
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) return null;

    // 尋找 HTML 中的 m3u8 連結
    // CCTV 通常在 window.__INITIAL_STATE__ 或類似的 JSON 變數中存放網址
    preg_match('/https?:\/\/[^"\']+\.m3u8[^"\']*/', $html, $matches);
    
    return $matches[0] ?? null;
}

$targetUrl = "https://m-live.cctvnews.cctv.com/live/landscape.html?liveRoomNumber=7252237247689203957";
$m3u8 = getCctvM3u8($targetUrl);

echo json_encode(['url' => $m3u8]);
