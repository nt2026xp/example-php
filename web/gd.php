<?php
error_reporting(0);
$n = [
    'gdws' => 43, //广东卫视
    'dwqws' => 51, //大湾区卫视
    'dwqws2' => 46, //大湾区卫视(海外版)
    'gdzj' => 44, //广东珠江
    'gdxw' => 45, //广东新闻
    'gdms' => 48, //广东民生
    'gdty' => 47, //广东体育
    'gdys' => 53, //广东影视
    'gd4k' => 16, //广东4K超高清
    'gdse' => 54, //广东少儿
    'jjkt' => 66, //嘉佳卡通
    'nfgw' => 42, //南方购物
    'lnxq' => 15, //岭南戏曲
    'gdyd' => 74, //广东移动
    'xdjy' => 111, //现代教育
    'gdjdj' => 100, //广东台经典剧
    'gdjlp' => 94, //纪录片
    'gdjk' => 99, //GRTN健康频道
    'gdwh' => 75, //GRTN文化频道
    'gdsh' => 102, //GRTN生活频道
    ];
$id = isset($_GET['id'])?$_GET['id']:'gdws';
//获取初始node
$nurl = "https://tcdn-api.itouchtv.cn/getParam";
$data = request($nurl);
$json = json_decode($data);
$node = $json->node;

//wss取串
$context = stream_context_create();
$sock = stream_socket_client("ssl://tcdn-ws.itouchtv.cn:3800",$errno,$errstr,5,STREAM_CLIENT_CONNECT,$context);
$key = '';
for ($i = 0; $i < 16; $i++) {
        $key .= chr(rand(33, 126));
    }
$key = base64_encode($key);
$header = '';
$header .= "GET /connect HTTP/1.1\r\n";
$header .= "Host: tcdn-ws.itouchtv.cn:3800\r\n";
$header .= "Upgrade: websocket\r\n";
$header .= "Sec-WebSocket-Key: $key\r\n";
fwrite($sock,$header."\r\n");
$handshake = fread($sock, 4096);

$wssData = json_encode(['route' => 'getwsparam','message' => $node]);
$encoded_data = encode($wssData);
fwrite($sock, $encoded_data);
$param = fread($sock, 4096);
$param = substr($param,4);
$json = json_decode($param);
$wsnode = $json->wsnode;

//获取播放线路.
$purl = "https://gdtv-api.gdtv.cn/api/tv/v2/tvChannel/{$n[$id]}?node=".base64_encode($wsnode);
request($purl, false, "OPTIONS");

$data = request($purl);
$json = json_decode($data);
$playURL = json_decode($json->playUrl)->hd;
header("location:".$playURL);
//echo $playURL;

function request($url, $header = true,$method = null) {
    $ch = curl_init();
    $o = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        ];
    $t = round(microtime(true) * 1000);
    $k = 'dfkcY1c3sfuw1Cii9DWj8UO3iQy2hqlDxyvDXd1oVMxwYVDSgeB6phO9eW1dfuwX';
    $sign = base64_encode(hash_hmac("SHA256","GET\n$url\n$t\n",$k,true));
    $header = [
            "Referer: https://www.gdtv.cn/",
            "Origin: https://www.gdtv.cn",
            "User-Agent: Mozilla/5.0 (Linux; U; Android 9)",
            "X-Itouchtv-Ca-Key: 89541943007407288657755311868534",
            "X-Itouchtv-Ca-Signature: $sign",
            "X-Itouchtv-Ca-Timestamp: $t",
            "X-Itouchtv-Client: WEB_M",
            "X-Itouchtv-Device-Id: WEBM_0",
            ];
    $o[CURLOPT_HTTPHEADER] = $header;
    if ($method !== null)
        $o[CURLOPT_CUSTOMREQUEST] = $method;
    curl_setopt_array($ch, $o);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function encode($data){
    $len = strlen($data);
    $head[0] = 129;
    $mask = [];
    for ($j = 0; $j < 4; $j ++) {
         $mask[] = mt_rand(1, 128);
         }
    $split = str_split(sprintf('%016b', $len), 8);
    $head[1] = 254;
    $head[2] = bindec($split[0]);
    $head[3] = bindec($split[1]);
    $head = array_merge($head, $mask);
    foreach ($head as $k => $v) {
             $head[$k] = chr($v);
             }
    $mask_data = '';
    for ($j = 0; $j < $len; $j ++) {
         $mask_data .= chr(ord($data[$j]) ^ $mask[$j % 4]);
         }
    return implode('', $head).$mask_data;
}
?>
