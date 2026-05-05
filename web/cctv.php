<?php
/**
 * 央視新聞直播整合腳本 (M3U清單 + 單頻道跳轉 + 緩存)
 */

// 1. 頻道配置 (名稱 => 直播間ID)
$channel_map = [
    "CCTV1" => "11200132825562653886",
    "CCTV2" => "12030532124776958103",
    "CCTV4" => "10620168294224708952",
    "CCTV7" => "8516529981177953694",
    "CCTV9" => "7252237247689203957",
    "CCTV10" => "14589146016461298119",
    "CCTV12" => "13180385922471124325",
    "CCTV13" => "16265686808730585228",
    "CCTV17" => "4496917190172866934",
    "CCTV4K" => "2127841942201075403"
];

// 獲取當前腳本網址，用於生成 M3U 內的連結
$self_url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

// --- 模式 A：單頻道跳轉 ( php?id=CCTV13 ) ---
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    if (!isset($channel_map[$id])) {
        die("頻道不存在");
    }
    
    $m3u8 = get_live_url($id, $channel_map[$id]);
    if ($m3u8) {
        header("Location: $m3u8");
    } else {
        header("HTTP/1.1 404 Not Found");
        echo "抓取失敗";
    }
    exit;
}

// --- 模式 B：自動生成 M3U 清單 ( 直接訪問 php ) ---
header('Content-Type: application/vnd.apple.mpegurl');
header('Content-Disposition: inline; filename="cctv_all.m3u"');

echo "#EXTM3U x-tvg-url=\"http://51zmt.top\"\n";
foreach ($channel_map as $name => $room_id) {
    echo "#EXTINF:-1 tvg-name=\"$name\" group-title=\"央視新聞\",$name\n";
    echo $self_url . "?id=" . $name . "\n";
}


/**
 * 核心抓取與緩存函數
 */
function get_live_url($name, $room_id) {
    $cache_file = "cache_" . md5($name) . ".txt";
    $cache_time = 3600; // 1小時

    // 讀取緩存
    if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
        return file_get_contents($cache_file);
    }

    // 爬取官網
    $url = "https://cctv.com" . $room_id;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1');
    $html = curl_exec($ch);
    curl_close($ch);

    if (preg_match('/"(https:[^"]+?\.m3u8[^"]*?)"/', $html, $matches)) {
        $real_url = stripslashes($matches[1]);
        file_put_contents($cache_file, $real_url);
        return $real_url;
    }
    return false;
}
