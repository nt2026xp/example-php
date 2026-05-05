<?php
/**
 * 央視新聞直播源動態 M3U 生成器
 */

// 設置 Header，讓播放器辨認為 M3U 播放列表
header('Content-Type: application/vnd.apple.mpegurl');
header('Content-Disposition: inline; filename="cctv_live.m3u"');

/**
 * 從央視新聞頁面提取 m3u8 地址
 */
function fetch_m3u8($id) {
    $url = "https://cctv.com" . $id;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    // 必須模擬手機端
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1');
    
    $html = curl_exec($ch);
    curl_close($ch);

    // 正則尋找 m3u8 連結
    if (preg_match('/"(https:[^"]+?\.m3u8[^"]*?)"/', $html, $matches)) {
        return stripslashes($matches[1]);
    }
    return "";
}

// 頻道清單數據
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

// 開始輸出 M3U 內容
echo "#EXTM3U x-tvg-url=\"http://51zmt.top\"\n";

foreach ($channels as $item) {
    $m3u8 = fetch_m3u8($item['id']);
    
    if ($m3u8) {
        // tvg-name 用於匹配 EPG 節目表，group-title 用於頻道分類
        echo "#EXTINF:-1 tvg-name=\"{$item['name']}\" group-title=\"央視新聞\",{$item['name']}\n";
        echo $m3u8 . "\n";
    }
}
