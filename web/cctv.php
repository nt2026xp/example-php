<?php
/**
 * 央視新聞直播源 M3U 生成器 (帶緩存功能)
 */

// 設置緩存文件路徑與過期時間（3600秒 = 1小時）
$cache_file = 'cctv_cache.m3u';
$cache_time = 3600;

// 檢查緩存是否有效
if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
    header('Content-Type: application/vnd.apple.mpegurl');
    header('Content-Disposition: inline; filename="cctv_live.m3u"');
    readfile($cache_file);
    exit;
}

// 如果緩存失效，執行抓取邏輯
function fetch_m3u8($id) {
    $url = "https://cctv.com" . $id;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1');
    $html = curl_exec($ch);
    curl_close($ch);

    if (preg_match('/"(https:[^"]+?\.m3u8[^"]*?)"/', $html, $matches)) {
        return stripslashes($matches[1]);
    }
    return "";
}

$channels = [
    ["name" => "CCTV4K超高清", "id" => "2127841942201075403"],
    ["name" => "CCTV1綜合", "id" => "11200132825562653886"],
    ["name" => "CCTV2財經", "id" => "12030532124776958103"],
    ["name" => "CCTV4中文國際", "id" => "10620168294224708952"],
    ["name" => "CCTV7國防軍事", "id" => "8516529981177953694"],
    ["name" => "CCTV9紀錄", "id" => "7252237247689203957"],
    ["name" => "CCTV10科教", "id" => "14589146016461298119"],
    ["name" => "CCTV12社會與法", "id" => "13180385922471124325"],
    ["name" => "CCTV13新聞", "id" => "16265686808730585228"],
    ["name" => "CCTV17農業農村", "id" => "4496917190172866934"]
];

$output = "#EXTM3U x-tvg-url=\"http://51zmt.top\"\n";

foreach ($channels as $item) {
    $m3u8 = fetch_m3u8($item['id']);
    if ($m3u8) {
        $output .= "#EXTINF:-1 tvg-name=\"{$item['name']}\" group-title=\"央視新聞\",{$item['name']}\n";
        $output .= $m3u8 . "\n";
    }
}

// 將結果存入緩存文件
file_put_contents($cache_file, $output);

// 輸出給客戶端
header('Content-Type: application/vnd.apple.mpegurl');
header('Content-Disposition: inline; filename="cctv_live.m3u"');
echo $output;
