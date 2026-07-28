<?php
/**
 * yspios.php
 *
 * 功能：
 * 1. CCTV / 卫视直播
 * 2. 回看
 * 3. M3U / TXT 播放列表生成
 * 4. M3U8 代理
 *
 * PHP >= 7.4
 */

date_default_timezone_set('Asia/Shanghai');


// ============================================================
// 播放列表
// ============================================================

if (isset($_GET['generate']) && (string)$_GET['generate'] === '1') {

    $channels = [
        'cctv1' => 'CCTV-1高清',
        'cctv2' => 'CCTV-2高清',
        'cctv3' => 'CCTV-3高清',
        'cctv4' => 'CCTV-4高清',
        'cctv5' => 'CCTV-5高清',
        'cctv5p' => 'CCTV-5+高清',
        'cctv6' => 'CCTV-6高清',
        'cctv7' => 'CCTV-7高清',
        'cctv8' => 'CCTV-8高清',
        'cctv9' => 'CCTV-9高清',
        'cctv10' => 'CCTV-10高清',
        'cctv11' => 'CCTV-11高清',
        'cctv12' => 'CCTV-12高清',
        'cctv13' => 'CCTV-13高清',
        'cctv14' => 'CCTV-14高清',
        'cctv15' => 'CCTV-15高清',
        'cctv16' => 'CCTV-16高清',
        'cctv164k' => 'CCTV-16(4K)',
        'cctv17' => 'CCTV-17高清',
        'cctv4k' => 'CCTV-4K',
        'cctv8k' => 'CCTV-8K',
        'cgtn' => 'CGTN',
        'cgtnfy' => 'CGTN法语频道',
        'cgtney' => 'CGTN俄语频道',
        'cgtnalby' => 'CGTN阿拉伯语频道',
        'cgtnxby' => 'CGTN西班牙语频道',
        'cgtnwyjl' => 'CGTN外语纪录频道',
        'cctvfyjc' => 'CCTV风云剧场',
        'cctvdyjc' => 'CCTV第一剧场',
        'cctvhjjc' => 'CCTV怀旧剧场',
        'cctvsjdl' => 'CCTV世界地理',
        'cctvfyyy' => 'CCTV风云音乐',
        'cctvbqkj' => 'CCTV兵器科技',
        'cctvfyzq' => 'CCTV风云足球',
        'cctvgeqwq' => 'CCTV高尔夫·网球',
        'cctvnxss' => 'CCTV女性时尚',
        'cctvyswhjp' => 'CCTV央视文化精品',
        'cctvystq' => 'CCTV央视台球',
        'cctvdszn' => 'CCTV电视指南',
        'cctvwsjk' => 'CCTV卫生健康',
        'bjws' => '北京卫视',
        'jsws' => '江苏卫视',
        'dfws' => '东方卫视',
        'zjws' => '浙江卫视',
        'hnws' => '湖南卫视',
        'hbws' => '湖北卫视',
        'gdws' => '广东卫视',
        'gxws' => '广西卫视',
        'hljws' => '黑龙江卫视',
        'hnws2' => '海南卫视',
        'cqws' => '重庆卫视',
        'szws' => '深圳卫视',
        'scws' => '四川卫视',
        'henanws' => '河南卫视',
        'fjdnhz' => '东南卫视',
        'gzhws' => '贵州卫视',
        'jxws' => '江西卫视',
        'lnws' => '辽宁卫视',
        'ahws' => '安徽卫视',
        'hbws2' => '河北卫视',
        'sdws' => '山东卫视',
        'tjws' => '天津卫视',
        'jlws' => '吉林卫视',
        'shanxiws' => '陕西卫视',
        'nxws' => '宁夏卫视',
        'nmgws' => '内蒙古卫视',
        'ynws' => '云南卫视',
        'shanxiws2' => '山西卫视',
        'qhws' => '青海卫视',
        'xzws' => '西藏卫视',
        'xjws' => '新疆卫视',
        'cetv1' => '中国教育电视台1',
        'gxpd' => '国学频道'
    ];

    $days = 7;
    $format = isset($_GET['format'])
        ? strtolower(trim((string)$_GET['format']))
        : 'txt';


    // --------------------------------------------------------
    // 自动取得当前 PHP 文件的访问地址
    //
    // 例如：
    // http://192.168.1.100:8901/wwwroot/yspios.php
    //
    // 自动得到：
    // http://192.168.1.100:8901/wwwroot
    //
    // 不再使用 127.0.0.1
    // --------------------------------------------------------

    $https = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    );

    $scheme = $https ? 'https' : 'http';

    $host = isset($_SERVER['HTTP_HOST'])
        ? $_SERVER['HTTP_HOST']
        : '127.0.0.1';

    $scriptName = isset($_SERVER['SCRIPT_NAME'])
        ? $_SERVER['SCRIPT_NAME']
        : '/yspios.php';

    $scriptDir = str_replace('\\', '/', dirname($scriptName));

    if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
        $scriptDir = '';
    }

    $domain = $scheme . '://' . $host . rtrim($scriptDir, '/');


    $today = new DateTime('now', new DateTimeZone('Asia/Shanghai'));


    // --------------------------------------------------------
    // M3U
    // --------------------------------------------------------

    if ($format === 'm3u') {

        header('Content-Type: audio/x-mpegurl; charset=utf-8');
        header('Content-Disposition: attachment; filename="playlist.m3u"');
        header('Cache-Control: no-cache');

        echo "#EXTM3U\n";
        echo "# 央视直播+回看列表\n\n";

        echo "# 直播源\n";

        foreach ($channels as $channelId => $channelName) {

            $url = $domain . '/yspios.php?id=' . rawurlencode($channelId);

            echo '#EXTINF:-1 group-title="直播源",' . $channelName . "\n";
            echo $url . "\n";
        }

        echo "\n";


        // 7 天回看
        for ($d = 0; $d < $days; $d++) {

            $date = clone $today;
            $date->modify("-{$d} days");

            $dateStr = $date->format('Ymd');
            $dateDisplay = $date->format('Y-m-d');

            $groupName = '回看' . $dateDisplay;

            foreach ($channels as $channelId => $channelName) {

                $playseek =
                    $dateStr . '000000-' .
                    $dateStr . '235959';

                $url =
                    $domain .
                    '/yspios.php?id=' . rawurlencode($channelId) .
                    '&playseek=' . rawurlencode($playseek);

                echo '#EXTINF:-1 group-title="' .
                    $groupName .
                    '",' .
                    $channelName .
                    "\n";

                echo $url . "\n";
            }

            echo "\n";
        }

        exit;
    }


    // --------------------------------------------------------
    // TXT
    // --------------------------------------------------------

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="playlist.txt"');
    header('Cache-Control: no-cache');

    $output = '';

    $output .= "直播源,#genre#\n";

    foreach ($channels as $channelId => $channelName) {

        $url =
            $domain .
            '/yspios.php?id=' .
            rawurlencode($channelId);

        $output .=
            $channelName .
            ',' .
            $url .
            "\n";
    }

    $output .= "\n";


    // 7 天回看
    for ($d = 0; $d < $days; $d++) {

        $date = clone $today;
        $date->modify("-{$d} days");

        $dateStr = $date->format('Ymd');
        $dateDisplay = $date->format('Y-m-d');

        $output .=
            '回看' .
            $dateDisplay .
            ',#genre#' .
            "\n";

        foreach ($channels as $channelId => $channelName) {

            $playseek =
                $dateStr . '000000-' .
                $dateStr . '235959';

            $url =
                $domain .
                '/yspios.php?id=' .
                rawurlencode($channelId) .
                '&playseek=' .
                rawurlencode($playseek);

            $output .=
                $channelName .
                ',' .
                $url .
                "\n";
        }

        $output .= "\n";
    }

    echo $output;
    exit;
}


// ============================================================
// 频道数据
// ============================================================

$id = isset($_GET['id'])
    ? trim((string)$_GET['id'])
    : 'cctv1';

$n = [

    'cctv1' => ['2024078201', '600001859', 'fhd'],
    'cctv2' => ['2024075401', '600001800', 'fhd'],
    'cctv3' => ['2024068501', '600001801', 'fhd'],
    'cctv4' => ['2029797101', '600001814', 'fhd'],
    'cctv5' => ['2024078401', '600001818', 'fhd'],
    'cctv5p' => ['2024078001', '600001817', 'fhd'],
    'cctv6' => ['2013693901', '600108442', 'fhd'],
    'cctv7' => ['2024072001', '600004092', 'fhd'],
    'cctv8' => ['2029793001', '600001803', 'fhd'],
    'cctv9' => ['2024078601', '600004078', 'fhd'],
    'cctv10' => ['2024078701', '600001805', 'fhd'],
    'cctv11' => ['2027248701', '600001806', 'fhd'],
    'cctv12' => ['2027248801', '600001807', 'fhd'],
    'cctv13' => ['2029797201', '600001811', 'fhd'],
    'cctv14' => ['2027248901', '600001809', 'fhd'],
    'cctv15' => ['2027249001', '600001815', 'fhd'],
    'cctv16' => ['2027249101', '600098637', 'fhd'],
    'cctv164k' => ['2027249301', '600099502', 'fhd'],
    'cctv17' => ['2027249401', '600001810', 'fhd'],
    'cctv4k' => ['2029810301', '600002264', 'fhd'],
    'cctv8k' => ['2026774101', '600156816', 'fhd'],

    'cgtn' => ['2024181701', '600014550', 'fhd'],
    'cgtnfy' => ['2024181801', '600084704', 'fhd'],
    'cgtney' => ['2024181901', '600084758', 'fhd'],
    'cgtnalby' => ['2024182001', '600084782', 'fhd'],
    'cgtnxby' => ['2024182101', '600084744', 'fhd'],
    'cgtnwyjl' => ['2024182301', '600084781', 'fhd'],

    'cctvfyjc' => ['2025637103', '600099658', 'shd'],
    'cctvdyjc' => ['2026874203', '600099655', 'shd'],
    'cctvhjjc' => ['2026874303', '600099620', 'shd'],
    'cctvsjdl' => ['2026874403', '600099637', 'shd'],
    'cctvfyyy' => ['2026874503', '600099660', 'shd'],
    'cctvbqkj' => ['2026874603', '600099649', 'shd'],
    'cctvfyzq' => ['2026966203', '600099636', 'shd'],
    'cctvgeqwq' => ['2026874703', '600099659', 'fhd'],
    'cctvnxss' => ['2026874803', '600099650', 'shd'],
    'cctvyswhjp' => ['2026874903', '600099653', 'shd'],
    'cctvystq' => ['2026875003', '600099652', 'shd'],
    'cctvdszn' => ['2026875103', '600099656', 'shd'],
    'cctvwsjk' => ['2025637003', '600099651', 'shd'],

    'bjws' => ['2024052703', '600002309', 'fhd'],
    'jsws' => ['2024171103', '600002521', 'fhd'],
    'dfws' => ['2024054503', '600002483', 'fhd'],
    'zjws' => ['2024054703', '600002520', 'fhd'],
    'hnws' => ['2024054803', '600002475', 'fhd'],
    'hbws' => ['2024171203', '600002508', 'fhd'],
    'gdws' => ['2024060903', '600002485', 'fhd'],
    'gxws' => ['2024060703', '600002509', 'fhd'],
    'hljws' => ['2029797003', '600002498', 'fhd'],
    'hnws2' => ['2024055603', '600002506', 'fhd'],
    'cqws' => ['2024061103', '600002531', 'fhd'],
    'szws' => ['2024061303', '600002481', 'fhd'],
    'scws' => ['2024061403', '600002516', 'fhd'],
    'henanws' => ['2029797303', '600002525', 'fhd'],
    'fjdnhz' => ['2024061503', '600002484', 'fhd'],
    'gzhws' => ['2024061603', '600002490', 'fhd'],
    'jxws' => ['2024061703', '600002503', 'fhd'],
    'lnws' => ['2024171303', '600002505', 'fhd'],
    'ahws' => ['2024171403', '600002532', 'fhd'],
    'hbws2' => ['2024171503', '600002493', 'fhd'],
    'sdws' => ['2029787903', '600002513', 'fhd'],
    'tjws' => ['2019927003', '600152137', 'fhd'],
    'jlws' => ['2025561503', '600190405', 'fhd'],
    'shanxiws' => ['2029795103', '600190400', 'fhd'],
    'nxws' => ['2025608503', '600190737', 'fhd'],
    'nmgws' => ['2025561203', '600190401', 'fhd'],
    'ynws' => ['2025561303', '600190402', 'fhd'],
    'shanxiws2' => ['2025560803', '600190407', 'fhd'],
    'qhws' => ['2025559103', '600190406', 'fhd'],
    'xzws' => ['2025558003', '600190403', 'fhd'],
    'xjws' => ['2019927403', '600152138', 'fhd'],

    'cetv1' => ['2022823801', '600171827', 'fhd'],
    'gxpd' => ['2029360403', '600213139', 'fhd']
];


// ============================================================
// 检查频道
// ============================================================

if (!isset($n[$id])) {

    http_response_code(400);

    header('Content-Type: text/plain; charset=utf-8');

    exit(
        "错误：不存在的频道 ID：{$id}\n" .
        "例如：?id=cctv1\n"
    );
}


// ============================================================
// CKeyManager
// ============================================================

class CKeyManager
{
    const DELTA = 0x9e3779b9;
    const ROUNDS = 16;
    const LOG_ROUNDS = 4;
    const SALT_LEN = 2;
    const ZERO_LEN = 7;

    const TEA_CKEY =
        '59b2f7cf725ef43c34fdd7c123411ed3';

    const GUARD_TEA_KEY =
        '110DBEC10C23E7D2E56A1CAD6914EF1B';


    private $xorKey = [
        0x84, 0x2E, 0xED, 0x08,
        0xF0, 0x66, 0xE6, 0xEA,
        0x48, 0xB4, 0xCA, 0xA9,
        0x91, 0xED, 0x6F, 0xF3
    ];


    private $guardXorKey = [
        0xB3, 0xC9, 0x53, 0xA0,
        0x69, 0x13, 0xAD, 0x4D
    ];


    private $standardAlphabet =
        'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';

    private $customAlphabet =
        'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-=';


    private $guid = '';


    public function __construct()
    {
        $this->generateGuid();
    }


    private function generateGuid()
    {
        $this->guid = sprintf(
            '%08x%04x%04x%04x%012x',
            mt_rand(0, 0xffffffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffffffffffff)
        );

        $this->guid = substr(
            str_pad($this->guid, 32, '0', STR_PAD_LEFT),
            0,
            32
        );

        return $this->guid;
    }


    public function getGuid()
    {
        return $this->guid;
    }


    public function setGuid($guid)
    {
        $this->guid = (string)$guid;
    }


    public function resetGuid()
    {
        return $this->generateGuid();
    }


    private function spvcode($defn)
    {
        $height = 1080;

        if (preg_match('/(4k|8k|hdr)/i', $defn)) {
            $height = 2160;
        }

        $frameRates = [30, 60, 90, 120];

        $h264 = [];
        $h265 = [];

        foreach ($frameRates as $fps) {
            $h264[] = "{$fps}:{$height}";
            $h265[] = "{$fps}:{$height}";
        }

        $h264Str = implode(',', $h264);
        $h265Str = implode(',', $h265);

        $raw =
            "H({$h264Str}|{$h264Str});" .
            "2({$h265Str}|{$h265Str})";

        return base64_encode($raw);
    }


    private function calcSignature($buffer)
    {
        $signature = 0;

        foreach ($buffer as $byte) {
            $signature =
                (0x83 * $signature + ($byte & 0xFF))
                & 0x7FFFFFFF;
        }

        return $signature;
    }


    private function customDecode($text)
    {
        if ($text === '' || $text === null) {
            return '';
        }

        $text = rtrim($text, '=');

        $mod = strlen($text) % 4;

        if ($mod !== 0) {
            $text .= str_repeat('=', 4 - $mod);
        }

        $translationTable = [];

        $len = min(
            strlen($this->customAlphabet),
            strlen($this->standardAlphabet)
        );

        for ($i = 0; $i < $len; $i++) {
            $translationTable[
                $this->customAlphabet[$i]
            ] = $this->standardAlphabet[$i];
        }

        $translated = strtr(
            $text,
            $translationTable
        );

        return base64_decode($translated, true);
    }


    private function customEncode($text)
    {
        $encoded = base64_encode($text);

        $translationTable = [];

        $len = min(
            strlen($this->standardAlphabet),
            strlen($this->customAlphabet)
        );

        for ($i = 0; $i < $len; $i++) {
            $translationTable[
                $this->standardAlphabet[$i]
            ] = $this->customAlphabet[$i];
        }

        return rtrim(
            strtr($encoded, $translationTable),
            '='
        );
    }


    private function xorArray($byteArray)
    {
        $ret = [];
        $len = count($byteArray);

        for ($i = 0; $i < $len; $i++) {
            $ret[] =
                $byteArray[$i] ^
                $this->xorKey[$i & 0x0F];
        }

        return $ret;
    }


    private function teaEncryptECB($pInBuf, $pKey)
    {
        if (strlen($pInBuf) < 8) {
            $pInBuf = str_pad($pInBuf, 8, "\0");
        }

        $unpacked = unpack('N2', substr($pInBuf, 0, 8));

        $y = $unpacked[1];
        $z = $unpacked[2];

        $k = [
            unpack('N', substr($pKey, 0, 4))[1],
            unpack('N', substr($pKey, 4, 4))[1],
            unpack('N', substr($pKey, 8, 4))[1],
            unpack('N', substr($pKey, 12, 4))[1]
        ];

        $sum = 0;

        for ($i = 0; $i < self::ROUNDS; $i++) {

            $sum =
                ($sum + self::DELTA)
                & 0xFFFFFFFF;

            $y =
                (
                    $y +
                    (
                        (
                            (($z << 4) + $k[0]) ^
                            ($z + $sum) ^
                            (($z >> 5) + $k[1])
                        )
                    )
                ) & 0xFFFFFFFF;

            $z =
                (
                    $z +
                    (
                        (
                            (($y << 4) + $k[2]) ^
                            ($y + $sum) ^
                            (($y >> 5) + $k[3])
                        )
                    )
                ) & 0xFFFFFFFF;
        }

        return pack('N2', $y, $z);
    }


    private function teaDecryptECB($pInBuf, $pKey)
    {
        if (strlen($pInBuf) < 8) {
            return false;
        }

        $unpacked = unpack('N2', substr($pInBuf, 0, 8));

        $y = $unpacked[1];
        $z = $unpacked[2];

        $k = [
            unpack('N', substr($pKey, 0, 4))[1],
            unpack('N', substr($pKey, 4, 4))[1],
            unpack('N', substr($pKey, 8, 4))[1],
            unpack('N', substr($pKey, 12, 4))[1]
        ];

        $sum =
            (self::DELTA << self::LOG_ROUNDS)
            & 0xFFFFFFFF;

        for ($i = 0; $i < self::ROUNDS; $i++) {

            $z =
                (
                    $z -
                    (
                        (($y << 4) + $k[2]) ^
                        ($y + $sum) ^
                        (($y >> 5) + $k[3])
                    )
                ) & 0xFFFFFFFF;

            $y =
                (
                    $y -
                    (
                        (($z << 4) + $k[0]) ^
                        ($z + $sum) ^
                        (($z >> 5) + $k[1])
                    )
                ) & 0xFFFFFFFF;

            $sum =
                ($sum - self::DELTA)
                & 0xFFFFFFFF;
        }

        return pack('N2', $y, $z);
    }


    private function oiSymmetryEncrypt2(
        $pInBuf,
        $nInBufLen,
        $pKey
    ) {

        $nPadSaltBodyZeroLen =
            $nInBufLen +
            1 +
            self::SALT_LEN +
            self::ZERO_LEN;

        $nPadlen =
            $nPadSaltBodyZeroLen % 8;

        if ($nPadlen) {
            $nPadlen = 8 - $nPadlen;
        }

        $pOutBuf = '';

        $src_buf = array_fill(0, 8, 0);

        $src_buf[0] =
            (mt_rand(0, 255) & 0xF8) |
            $nPadlen;

        $src_i = 1;

        while ($nPadlen) {

            $src_buf[$src_i] =
                mt_rand(0, 255);

            $src_i++;
            $nPadlen--;
        }

        $iv_plain = array_fill(0, 8, 0);
        $iv_crypt = array_fill(0, 8, 0);


        // SALT
        $i = 0;

        while ($i < self::SALT_LEN) {

            if ($src_i < 8) {

                $src_buf[$src_i] =
                    mt_rand(0, 255);

                $src_i++;
                $i++;
            }

            if ($src_i === 8) {

                for ($j = 0; $j < 8; $j++) {
                    $src_buf[$j] ^=
                        $iv_crypt[$j];
                }

                $tempOut =
                    $this->teaEncryptECB(
                        pack('C*', ...$src_buf),
                        $pKey
                    );

                $tempBytes =
                    array_values(
                        unpack('C*', $tempOut)
                    );

                for ($j = 0; $j < 8; $j++) {
                    $tempBytes[$j] ^=
                        $iv_plain[$j];
                }

                $iv_plain = $src_buf;
                $iv_crypt = $tempBytes;

                $pOutBuf .=
                    pack('C*', ...$tempBytes);

                $src_i = 0;
            }
        }


        // DATA
        $pInBufIndex = 0;

        while ($nInBufLen > 0) {

            if ($src_i < 8) {

                $src_buf[$src_i] =
                    ord($pInBuf[$pInBufIndex]);

                $pInBufIndex++;
                $src_i++;
                $nInBufLen--;
            }

            if ($src_i === 8) {

                for ($j = 0; $j < 8; $j++) {
                    $src_buf[$j] ^=
                        $iv_crypt[$j];
                }

                $tempOut =
                    $this->teaEncryptECB(
                        pack('C*', ...$src_buf),
                        $pKey
                    );

                $tempBytes =
                    array_values(
                        unpack('C*', $tempOut)
                    );

                for ($j = 0; $j < 8; $j++) {
                    $tempBytes[$j] ^=
                        $iv_plain[$j];
                }

                $iv_plain = $src_buf;
                $iv_crypt = $tempBytes;

                $pOutBuf .=
                    pack('C*', ...$tempBytes);

                $src_i = 0;
            }
        }


        // ZERO
        $i = 0;

        while ($i < self::ZERO_LEN) {

            if ($src_i < 8) {

                $src_buf[$src_i] = 0;

                $src_i++;
                $i++;
            }

            if ($src_i === 8) {

                for ($j = 0; $j < 8; $j++) {
                    $src_buf[$j] ^=
                        $iv_crypt[$j];
                }

                $tempOut =
                    $this->teaEncryptECB(
                        pack('C*', ...$src_buf),
                        $pKey
                    );

                $tempBytes =
                    array_values(
                        unpack('C*', $tempOut)
                    );

                for ($j = 0; $j < 8; $j++) {
                    $tempBytes[$j] ^=
                        $iv_plain[$j];
                }

                $iv_plain = $src_buf;
                $iv_crypt = $tempBytes;

                $pOutBuf .=
                    pack('C*', ...$tempBytes);

                $src_i = 0;
            }
        }


        // 最后一个不完整区块
        if ($src_i > 0) {

            for ($j = $src_i; $j < 8; $j++) {
                $src_buf[$j] = 0;
            }

            for ($j = 0; $j < 8; $j++) {
                $src_buf[$j] ^=
                    $iv_crypt[$j];
            }

            $tempOut =
                $this->teaEncryptECB(
                    pack('C*', ...$src_buf),
                    $pKey
                );

            $tempBytes =
                array_values(
                    unpack('C*', $tempOut)
                );

            for ($j = 0; $j < 8; $j++) {
                $tempBytes[$j] ^=
                    $iv_plain[$j];
            }

            $pOutBuf .=
                pack('C*', ...$tempBytes);
        }

        return $pOutBuf;
    }


    private function oiSymmetryDecrypt2(
        $pInBuf,
        $nInBufLen,
        $pKey
    ) {

        if (
            ($nInBufLen % 8) !== 0 ||
            $nInBufLen < 16
        ) {
            return false;
        }

        $destBufStr =
            $this->teaDecryptECB(
                substr($pInBuf, 0, 8),
                $pKey
            );

        if ($destBufStr === false) {
            return false;
        }

        $destBuf =
            array_values(
                unpack('C*', $destBufStr)
            );

        $nPadLen =
            $destBuf[0] & 0x07;

        $i =
            $nInBufLen -
            1 -
            $nPadLen -
            self::SALT_LEN -
            self::ZERO_LEN;

        if ($i < 0) {
            return false;
        }

        $pOutBufLen = $i;

        $ivPreCrypt =
            array_fill(0, 8, 0);

        $ivCurCrypt =
            array_values(
                unpack(
                    'C*',
                    substr($pInBuf, 0, 8)
                )
            );

        $pInBufOffset = 8;
        $destI = 1 + $nPadLen;

        $saltCount = 1;

        while ($saltCount <= self::SALT_LEN) {

            if ($destI < 8) {

                $destI++;
                $saltCount++;

            } elseif ($destI === 8) {

                $ivPreCrypt = $ivCurCrypt;

                if (
                    $pInBufOffset + 8 >
                    $nInBufLen
                ) {
                    return false;
                }

                $ivCurCrypt =
                    array_values(
                        unpack(
                            'C*',
                            substr(
                                $pInBuf,
                                $pInBufOffset,
                                8
                            )
                        )
                    );

                for ($j = 0; $j < 8; $j++) {
                    $destBuf[$j] ^=
                        $ivCurCrypt[$j];
                }

                $tempBuf =
                    $this->teaDecryptECB(
                        pack('C*', ...$destBuf),
                        $pKey
                    );

                if ($tempBuf === false) {
                    return false;
                }

                $destBuf =
                    array_values(
                        unpack('C*', $tempBuf)
                    );

                $pInBufOffset += 8;
                $destI = 0;
            }
        }


        $nPlainLen = $pOutBufLen;
        $plainBytes = [];

        while ($nPlainLen > 0) {

            if ($destI < 8) {

                $plainBytes[] =
                    $destBuf[$destI] ^
                    $ivPreCrypt[$destI];

                $destI++;
                $nPlainLen--;

            } elseif ($destI === 8) {

                $ivPreCrypt = $ivCurCrypt;

                if (
                    $pInBufOffset + 8 >
                    $nInBufLen
                ) {
                    return false;
                }

                $ivCurCrypt =
                    array_values(
                        unpack(
                            'C*',
                            substr(
                                $pInBuf,
                                $pInBufOffset,
                                8
                            )
                        )
                    );

                for ($j = 0; $j < 8; $j++) {
                    $destBuf[$j] ^=
                        $ivCurCrypt[$j];
                }

                $tempBuf =
                    $this->teaDecryptECB(
                        pack('C*', ...$destBuf),
                        $pKey
                    );

                if ($tempBuf === false) {
                    return false;
                }

                $destBuf =
                    array_values(
                        unpack('C*', $tempBuf)
                    );

                $pInBufOffset += 8;
                $destI = 0;
            }
        }

        return pack('C*', ...$plainBytes);
    }


    private function guardLastFive($value)
    {
        $value = (string)$value;

        if (strlen($value) >= 5) {
            return substr($value, -5);
        }

        return '';
    }


    private function generateCkGuardTime(
        $timestamp,
        $guid,
        $guardData = '-1',
        $packageName = 'null',
        $processName = 'null'
    ) {

        $body = pack('N', $timestamp);

        $parts = [
            $this->guardLastFive($guid),
            $this->guardLastFive($packageName),
            $this->guardLastFive($processName),
            $guardData
        ];

        foreach ($parts as $part) {

            $body .=
                pack('n', strlen($part)) .
                $part;
        }

        $plain =
            pack('n', strlen($body)) .
            $body;

        $checksum =
            $this->calcSignature(
                array_values(
                    unpack('C*', $plain)
                )
            );

        $encrypted =
            $this->oiSymmetryEncrypt2(
                $plain,
                strlen($plain),
                hex2bin(self::GUARD_TEA_KEY)
            );

        $encrypted .=
            pack('N', $checksum);

        $bytes =
            array_values(
                unpack('C*', $encrypted)
            );

        $len = count($bytes);

        for ($i = 0; $i < $len; $i++) {
            $bytes[$i] ^=
                $this->guardXorKey[$i & 7];
        }

        return strtoupper(
            bin2hex(
                pack('C*', ...$bytes)
            )
        );
    }


    public function encryptDataToCKey($data)
    {
        $teaCkey =
            hex2bin(self::TEA_CKEY);

        $dataLen = strlen($data);

        $dataArray =
            array_values(
                unpack('C*', $data)
            );

        $checksum =
            $this->calcSignature($dataArray);

        $encrypted =
            $this->oiSymmetryEncrypt2(
                $data,
                $dataLen,
                $teaCkey
            );

        $encrypted .=
            pack('N', $checksum);

        $encryptedArray =
            array_values(
                unpack('C*', $encrypted)
            );

        $xorArray =
            $this->xorArray($encryptedArray);

        $xorEncrypted =
            pack('C*', ...$xorArray);

        $base64Encoded =
            $this->customEncode($xorEncrypted);

        return '--01' . $base64Encoded;
    }


    public function decryptCKeyToData($ckey)
    {
        if (
            !is_string($ckey) ||
            strpos($ckey, '--01') !== 0
        ) {
            return false;
        }

        $teaCkey =
            hex2bin(self::TEA_CKEY);

        $ckeyWithoutPrefix =
            substr($ckey, 4);

        $base64Decoded =
            $this->customDecode(
                $ckeyWithoutPrefix
            );

        if ($base64Decoded === false) {
            return false;
        }

        if (strlen($base64Decoded) < 12) {
            return false;
        }

        $xorArray =
            array_values(
                unpack('C*', $base64Decoded)
            );

        $xorDecryptedArray =
            $this->xorArray($xorArray);

        $xorDecrypted =
            pack(
                'C*',
                ...$xorDecryptedArray
            );

        $dataLen =
            strlen($xorDecrypted) - 4;

        if ($dataLen <= 0) {
            return false;
        }

        $encryptedData =
            substr(
                $xorDecrypted,
                0,
                $dataLen
            );

        $checksumBytes =
            substr(
                $xorDecrypted,
                $dataLen
            );

        $checksumData =
            unpack('N', $checksumBytes);

        if (!$checksumData) {
            return false;
        }

        $checksum =
            $checksumData[1];

        $decrypted =
            $this->oiSymmetryDecrypt2(
                $encryptedData,
                $dataLen,
                $teaCkey
            );

        if ($decrypted === false) {
            return false;
        }

        return [
            'data' => $decrypted,
            'checksum' => $checksum
        ];
    }


    public function buildPacket($params)
    {
        $data = '';

        $data .=
            hex2bin(
                '0000004200000004000004d2'
            );

        $data .=
            pack('N', $params['Platform']);

        $data .=
            pack('N', 0);

        $data .=
            pack('N', $params['Timestamp']);


        $fields = [
            $params['Sdtfrom'],
            $params['randFlag'],
            $params['appVer'],
            $params['vid'],
            $params['guid']
        ];

        foreach ($fields as $field) {

            $data .=
                pack('n', strlen($field)) .
                $field;
        }


        $data .= pack('N', 1);
        $data .= pack('N', 1);


        $uid = '2622783A';

        $data .=
            pack('n', strlen($uid)) .
            $uid;


        $bundleID = 'nil';

        $data .=
            pack('n', strlen($bundleID)) .
            $bundleID;


        $uuid4 = $params['uuid4'];

        $data .=
            pack('n', strlen($uuid4)) .
            $uuid4;


        $data .=
            pack('n', strlen($bundleID)) .
            $bundleID;


        $ckeyVersion = 'v0.1.000';

        $data .=
            pack('n', strlen($ckeyVersion)) .
            $ckeyVersion;


        $packageName =
            'com.cctv.yangshipin.app.iphone';

        $data .=
            pack('n', strlen($packageName)) .
            $packageName;


        $platformStr = '4330403';

        $data .=
            pack('n', strlen($platformStr)) .
            $platformStr;


        $exJsonBus = 'ex_json_bus';

        $data .=
            pack('n', strlen($exJsonBus)) .
            $exJsonBus;


        $exJsonVs = 'ex_json_vs';

        $data .=
            pack('n', strlen($exJsonVs)) .
            $exJsonVs;


        $ckGuardTime =
            $params['ck_guard_time'];

        $data .=
            pack('n', strlen($ckGuardTime)) .
            $ckGuardTime;


        $bodyLength = strlen($data);

        $buffer =
            pack('n', $bodyLength) .
            $data;


        $bufferArray =
            array_values(
                unpack('C*', $buffer)
            );

        $signature =
            $this->calcSignature(
                $bufferArray
            );


        $buffer =
            substr($buffer, 0, 18) .
            pack('N', $signature) .
            substr($buffer, 22);

        return $buffer;
    }


    public function generateCKey(
        $cnlid,
        $timestamp = null
    ) {

        if ($timestamp === null) {
            $timestamp = time();
        }


        $randFlag =
            base64_encode(
                random_bytes(18)
            );


        $uuid4 = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );


        $ckGuardTime =
            $this->generateCkGuardTime(
                $timestamp,
                $this->guid
            );


        $params = [
            'Platform' => 4330403,
            'Timestamp' => $timestamp,
            'Sdtfrom' => 'dcgh',
            'vid' => $cnlid,
            'guid' => $this->guid,
            'appVer' => 'V8.22.1035.3031',
            'randFlag' => $randFlag,
            'uuid4' => $uuid4,
            'ck_guard_time' => $ckGuardTime
        ];


        $buffer =
            $this->buildPacket($params);

        $ckey =
            $this->encryptDataToCKey($buffer);


        return [
            'ckey' => $ckey,
            'params' => $params,
            'buffer' => $buffer
        ];
    }


    public function makeLiveRequest(
        $cnlid,
        $livepid = '600001859',
        $defn = 'fhd',
        $playseek = null
    ) {

        $this->generateGuid();


        $ckeyResult =
            $this->generateCKey($cnlid);

        $ckey =
            $ckeyResult['ckey'];

        $params =
            $ckeyResult['params'];


        $flowid = sprintf(
            '%s_%d',
            sprintf(
                '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff)
            ),
            4330403
        );


        $isPlayback =
            !empty($playseek);

        $playbackTimestamp = null;


        if ($isPlayback) {

            try {

                $parsed =
                    $this->parsePlayseek(
                        $playseek
                    );

                $playbackTimestamp =
                    $parsed['start_timestamp'];

            } catch (Exception $e) {

                return [
                    'success' => false,
                    'error' =>
                        '回看时间处理失败：' .
                        $e->getMessage(),
                    'playseek' => $playseek
                ];
            }
        }


        // 根据清晰度生成 spvcode
        $spvcode =
            $this->spvcode($defn);


        $requestParams = [

            'atime' => '120',

            'livepid' => $livepid,

            'cnlid' => $cnlid,

            'appVer' =>
                'V8.22.1035.3031',

            'app_version' =>
                '300090',

            'caplv' => '1',

            'cmd' => '2',

            'defn' => $defn,

            'device' => 'iPhone',

            'encryptVer' => '4.2',

            'getpreviewinfo' => '0',

            'hevclv' => '33',

            'lang' => 'zh-Hans_JP',

            'livequeue' => '0',

            'logintype' => '1',

            'nettype' => '1',

            'newnettype' => '1',

            'newplatform' => '4330403',

            'platform' => '4330403',

            'sdtfrom' => 'v3021',

            'spacode' => '23',

            'spaudio' => '1',

            'spdemuxer' => '6',

            'spdrm' => '2',

            'spdynamicrange' => '7',

            'spflv' => '1',

            'spflvaudio' => '1',

            'sphdrfps' => '60',

            'sphttps' => '0',

            'spvcode' => $spvcode,

            'spvideo' => '4',

            'stream' => '1',

            'system' => '1',

            'sysver' => 'ios18.2.1',

            'uhd_flag' => '4',

            'cKey' => $ckey,

            'guid' => $this->guid,

            'fntick' => $params['Timestamp'],

            'flowid' => $flowid
        ];


        // ----------------------------------------------------
        // 回看
        // ----------------------------------------------------

        if ($isPlayback) {

            $requestParams['playbacktime'] =
                (string)$playbackTimestamp;


            $response =
                $this->sendHttpRequest(
                    $requestParams
                );


            if (
                $response['success'] &&
                !empty(
                    $response['response']['playurl']
                )
            ) {

                return $response;
            }


            // 第二次尝试不带 playbacktime
            unset(
                $requestParams['playbacktime']
            );


            $response =
                $this->sendHttpRequest(
                    $requestParams
                );


            if (
                $response['success'] &&
                !empty(
                    $response['response']['playurl']
                )
            ) {

                $playurl =
                    $response['response']['playurl'];

                /*
                 * 不再强制修改 CDN 域名。
                 *
                 * 如果上游已经返回完整播放地址，
                 * 直接使用原地址。
                 */
                $response['response']['playurl'] =
                    $playurl;

                $response['playurl'] =
                    $playurl;

                return $response;
            }


            return [
                'success' => false,
                'error' => '无法获取回看地址',
                'playseek' => $playseek,
                'response' =>
                    isset($response['response'])
                        ? $response['response']
                        : null
            ];
        }


        // ----------------------------------------------------
        // 直播
        // ----------------------------------------------------

        $requestParams['playbacktime'] = '0';

        return $this->sendHttpRequest(
            $requestParams
        );
    }


    private function sendHttpRequest($params)
    {
        $url =
            'https://bkliveinfo.ysp.cctv.cn';


        $queryString =
            http_build_query(
                $params,
                '',
                '&',
                PHP_QUERY_RFC3986
            );


        $ch = curl_init();

        curl_setopt_array($ch, [

            CURLOPT_URL =>
                $url . '?' . $queryString,

            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_FOLLOWLOCATION => true,

            CURLOPT_CONNECTTIMEOUT => 10,

            CURLOPT_TIMEOUT => 20,

            CURLOPT_HTTPHEADER => [

                'User-Agent: qqlive',

                'Connection: Keep-Alive',

                'Accept: application/json',

                'Accept-Encoding: gzip, deflate'

            ],

            CURLOPT_ENCODING => '',

            CURLOPT_SSL_VERIFYPEER => false,

            CURLOPT_SSL_VERIFYHOST => false
        ]);


        $response =
            curl_exec($ch);

        $error =
            curl_error($ch);

        $httpCode =
            curl_getinfo(
                $ch,
                CURLINFO_HTTP_CODE
            );

        curl_close($ch);


        if ($response === false || $error) {

            return [
                'success' => false,
                'error' =>
                    'cURL错误：' .
                    ($error ?: '未知错误'),
                'http_code' => $httpCode
            ];
        }


        $data =
            json_decode(
                $response,
                true
            );


        if (!is_array($data)) {

            return [
                'success' => false,
                'error' => '无效的 JSON 响应',
                'http_code' => $httpCode,
                'raw_response' =>
                    substr($response, 0, 1000)
            ];
        }


        if (isset($data['iretcode'])) {

            $success =
                ((string)$data['iretcode'] === '0');


            $result = [

                'success' => $success,

                'iretcode' =>
                    $data['iretcode'],

                'http_code' =>
                    $httpCode,

                'response' =>
                    $data
            ];


            if ($success) {

                $result['playurl'] =
                    isset($data['playurl'])
                        ? $data['playurl']
                        : null;

            } else {

                $result['error'] =
                    isset($data['errinfo'])
                        ? $data['errinfo']
                        : '上游返回错误';
            }


            return $result;
        }


        return [
            'success' => false,
            'error' =>
                '上游返回数据中没有 iretcode',
            'http_code' => $httpCode,
            'response' => $data
        ];
    }


    public function getPlayUrl(
        $cnlid,
        $livepid = '600001859',
        $defn = 'fhd',
        $playseek = null
    ) {

        $result =
            $this->makeLiveRequest(
                $cnlid,
                $livepid,
                $defn,
                $playseek
            );


        if (
            !empty($result['success']) &&
            !empty($result['playurl'])
        ) {
            return $result['playurl'];
        }


        return null;
    }


    public function parsePlayseek($playseek)
    {
        $playseek =
            trim((string)$playseek);


        $parts =
            explode('-', $playseek);


        if (count($parts) !== 2) {

            throw new Exception(
                '回看时间格式错误，应为：' .
                'YYYYMMDDHHMMSS-YYYYMMDDHHMMSS'
            );
        }


        $startTimeStr =
            trim($parts[0]);

        $endTimeStr =
            trim($parts[1]);


        if (
            !preg_match(
                '/^\d{14}$/',
                $startTimeStr
            ) ||
            !preg_match(
                '/^\d{14}$/',
                $endTimeStr
            )
        ) {

            throw new Exception(
                '回看时间必须是 14 位数字'
            );
        }


        $timezone =
            new DateTimeZone(
                'Asia/Shanghai'
            );


        $startTime =
            DateTime::createFromFormat(
                '!YmdHis',
                $startTimeStr,
                $timezone
            );


        $endTime =
            DateTime::createFromFormat(
                '!YmdHis',
                $endTimeStr,
                $timezone
            );


        if (
            $startTime === false ||
            $endTime === false
        ) {

            throw new Exception(
                '回看时间解析失败'
            );
        }


        $startErrors =
            DateTime::getLastErrors();

        if (
            is_array($startErrors) &&
            (
                $startErrors['warning_count'] > 0 ||
                $startErrors['error_count'] > 0
            )
        ) {
            throw new Exception(
                '开始时间无效'
            );
        }


        $endErrors =
            DateTime::getLastErrors();

        if (
            is_array($endErrors) &&
            (
                $endErrors['warning_count'] > 0 ||
                $endErrors['error_count'] > 0
            )
        ) {
            throw new Exception(
                '结束时间无效'
            );
        }


        $startTimestamp =
            $startTime->getTimestamp();

        $endTimestamp =
            $endTime->getTimestamp();


        if ($endTimestamp <= $startTimestamp) {

            throw new Exception(
                '结束时间必须晚于开始时间'
            );
        }


        return [

            'start_time' =>
                $startTime,

            'end_time' =>
                $endTime,

            'start_timestamp' =>
                $startTimestamp,

            'end_timestamp' =>
                $endTimestamp,

            'start_str' =>
                $startTime->format(
                    'Y-m-d H:i:s'
                ),

            'end_str' =>
                $endTime->format(
                    'Y-m-d H:i:s'
                ),

            'duration' =>
                $endTimestamp -
                $startTimestamp
        ];
    }


    public function generatePlayseek(
        $startDateTime,
        $endDateTime
    ) {

        $timezone =
            new DateTimeZone(
                'Asia/Shanghai'
            );


        $startTime =
            DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $startDateTime,
                $timezone
            );


        $endTime =
            DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $endDateTime,
                $timezone
            );


        if (
            $startTime === false ||
            $endTime === false
        ) {

            throw new Exception(
                '时间格式错误，应为：Y-m-d H:i:s'
            );
        }


        return
            $startTime->format('YmdHis') .
            '-' .
            $endTime->format('YmdHis');
    }
}


// ============================================================
// HTTP 获取 M3U8
// ============================================================

function fetchM3u8($url)
{
    if (
        !is_string($url) ||
        $url === ''
    ) {
        return false;
    }


    $ch = curl_init();


    curl_setopt_array($ch, [

        CURLOPT_URL => $url,

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_FOLLOWLOCATION => true,

        CURLOPT_MAXREDIRS => 5,

        CURLOPT_CONNECTTIMEOUT => 10,

        CURLOPT_TIMEOUT => 20,

        CURLOPT_HTTPHEADER => [

            'User-Agent: Mozilla/5.0',

            'Accept: application/vnd.apple.mpegurl,' .
            'application/x-mpegURL,*/*',

            'Connection: keep-alive'
        ],

        CURLOPT_ENCODING => '',

        CURLOPT_SSL_VERIFYPEER => false,

        CURLOPT_SSL_VERIFYHOST => false
    ]);


    $data =
        curl_exec($ch);

    $error =
        curl_error($ch);

    $httpCode =
        curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );


    curl_close($ch);


    if (
        $data === false ||
        $error !== '' ||
        $httpCode < 200 ||
        $httpCode >= 400
    ) {
        return false;
    }


    return $data;
}


// ============================================================
// M3U8 URL 转换
// ============================================================

function resolveM3u8Url(
    $baseUrl,
    $relativeUrl
) {

    $relativeUrl =
        trim($relativeUrl);


    if ($relativeUrl === '') {
        return $relativeUrl;
    }


    // 已经是完整 URL
    if (
        preg_match(
            '#^https?://#i',
            $relativeUrl
        )
    ) {
        return $relativeUrl;
    }


    // data: / blob: 等
    if (
        preg_match(
            '#^[a-z][a-z0-9+\-.]*:#i',
            $relativeUrl
        )
    ) {
        return $relativeUrl;
    }


    $base =
        parse_url($baseUrl);


    if (
        !$base ||
        empty($base['scheme']) ||
        empty($base['host'])
    ) {
        return $relativeUrl;
    }


    $scheme =
        $base['scheme'];

    $host =
        $base['host'];


    if (isset($base['port'])) {
        $host .= ':' . $base['port'];
    }


    $origin =
        $scheme . '://' . $host;


    // //example.com/path
    if (
        strpos(
            $relativeUrl,
            '//'
        ) === 0
    ) {

        return $scheme .
            ':' .
            $relativeUrl;
    }


    // 以 / 开头
    if (
        strpos(
            $relativeUrl,
            '/'
        ) === 0
    ) {

        return
            $origin .
            $relativeUrl;
    }


    $basePath =
        isset($base['path'])
            ? $base['path']
            : '/';


    $directory =
        dirname($basePath);


    if ($directory === '\\') {
        $directory = '/';
    }


    $directory =
        rtrim(
            str_replace(
                '\\',
                '/',
                $directory
            ),
            '/'
        );


    // 去掉 ./ 和 ../
    $relativeUrl =
        preg_replace(
            '#^\./#',
            '',
            $relativeUrl
        );


    while (
        strpos(
            $relativeUrl,
            '../'
        ) === 0
    ) {

        $directory =
            dirname($directory);

        $relativeUrl =
            substr(
                $relativeUrl,
                3
            );
    }


    if (
        $directory === '.' ||
        $directory === '\\'
    ) {
        $directory = '';
    }


    return
        $origin .
        ($directory
            ? '/' . ltrim($directory, '/')
            : '') .
        '/' .
        ltrim($relativeUrl, '/');
}


// ============================================================
// 重写 M3U8 内媒体地址
// ============================================================

function rewriteM3u8Urls(
    $content,
    $playUrl
) {

    if (
        $content === false ||
        trim($content) === ''
    ) {
        return $content;
    }


    $lines =
        preg_split(
            "/\r\n|\n|\r/",
            $content
        );


    foreach ($lines as &$line) {

        $trimmed =
            trim($line);


        // 空行
        if ($trimmed === '') {
            continue;
        }


        // #EXTINF / #EXT-X 等
        if (
            strpos(
                $trimmed,
                '#'
            ) === 0
        ) {

            /*
             * 处理 EXT-X-MAP / KEY 等带 URI 的标签
             */
            if (
                preg_match(
                    '/URI="([^"]+)"/i',
                    $line,
                    $matches
                )
            ) {

                $oldUrl =
                    $matches[1];

                $newUrl =
                    resolveM3u8Url(
                        $playUrl,
                        $oldUrl
                    );

                $line =
                    str_replace(
                        $oldUrl,
                        $newUrl,
                        $line
                    );
            }

            continue;
        }


        $line =
            resolveM3u8Url(
                $playUrl,
                $trimmed
            );
    }


    unset($line);


    return implode(
        "\n",
        $lines
    );
}


// ============================================================
// 主逻辑
// ============================================================

$ckeyManager =
    new CKeyManager();


$playseek =
    isset($_GET['playseek'])
        ? trim((string)$_GET['playseek'])
        : null;


$cookieKey =
    'playurl_cache';


$cacheTimeoutLive =
    80;


$cookieExpire =
    time() + 3600;


$now =
    time();


$isLive =
    ($playseek === null ||
     $playseek === '');


$playUrl = null;

$m3u8Content = false;


// ============================================================
// 验证回看时间
// ============================================================

if (!$isLive) {

    try {

        $ckeyManager->parsePlayseek(
            $playseek
        );

    } catch (Exception $e) {

        http_response_code(400);

        header(
            'Content-Type: text/plain; charset=utf-8'
        );

        exit(
            "回看参数错误：\n" .
            $e->getMessage() .
            "\n"
        );
    }
}


// ============================================================
// Cookie 缓存
// ============================================================

$cache = [];


if (
    isset($_COOKIE[$cookieKey]) &&
    is_string($_COOKIE[$cookieKey])
) {

    $decoded =
        json_decode(
            $_COOKIE[$cookieKey],
            true
        );


    if (is_array($decoded)) {
        $cache = $decoded;
    }
}


// ============================================================
// 获取播放地址
// ============================================================

$maxAttempts = 2;


for (
    $attempt = 1;
    $attempt <= $maxAttempts;
    $attempt++
) {

    $needRefresh = true;


    // --------------------------------------------------------
    // 直播优先读取缓存
    // --------------------------------------------------------

    if (
        $attempt === 1 &&
        $isLive &&
        isset($cache[$id]) &&
        is_array($cache[$id])
    ) {

        $entry =
            $cache[$id];


        if (
            isset(
                $entry['url'],
                $entry['time']
            ) &&
            is_string($entry['url']) &&
            is_numeric($entry['time'])
        ) {

            if (
                ($now - (int)$entry['time'])
                <= $cacheTimeoutLive
            ) {

                $playUrl =
                    $entry['url'];

                $needRefresh = false;
            }
        }
    }


    // --------------------------------------------------------
    // 重新获取播放地址
    // --------------------------------------------------------

    if ($needRefresh) {

        $playUrl =
            $ckeyManager->getPlayUrl(
                $n[$id][0],
                $n[$id][1],
                $n[$id][2],
                $playseek
            );


        if (
            !is_string($playUrl) ||
            trim($playUrl) === ''
        ) {

            if (
                $attempt <
                $maxAttempts
            ) {
                continue;
            }


            http_response_code(502);

            header(
                'Content-Type: text/plain; charset=utf-8'
            );

            exit(
                "获取播放地址失败，请稍后重试\n"
            );
        }


        // ----------------------------------------------------
        // 回看：直接 302 到上游播放地址
        // ----------------------------------------------------

        if (!$isLive) {

            header(
                'Location: ' .
                $playUrl,
                true,
                302
            );

            exit;
        }


        // ----------------------------------------------------
        // 直播缓存
        // ----------------------------------------------------

        $cache[$id] = [
            'url' => $playUrl,
            'time' => $now
        ];


        /*
         * 只缓存当前频道。
         * 防止 Cookie 超过浏览器限制。
         */
        $cookieCache = [
            $id => $cache[$id]
        ];


        $cookieJson =
            json_encode(
                $cookieCache,
                JSON_UNESCAPED_SLASHES |
                JSON_UNESCAPED_UNICODE
            );


        if (
            $cookieJson !== false &&
            strlen($cookieJson) < 3500
        ) {

            setcookie(
                $cookieKey,
                $cookieJson,
                [
                    'expires' => $cookieExpire,
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
        }
    }


    // --------------------------------------------------------
    // 获取 M3U8
    // --------------------------------------------------------

    $m3u8Content =
        fetchM3u8(
            $playUrl
        );


    if (
        $m3u8Content !== false &&
        trim($m3u8Content) !== ''
    ) {
        break;
    }


    // --------------------------------------------------------
    // 第一次使用缓存失败
    // 清掉缓存，第二次重新获取
    // --------------------------------------------------------

    if (
        $attempt === 1 &&
        $isLive &&
        !$needRefresh
    ) {

        unset(
            $cache[$id]
        );


        setcookie(
            $cookieKey,
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }


    $playUrl = null;
}


// ============================================================
// M3U8 最终检查
// ============================================================

if (
    $m3u8Content === false ||
    trim($m3u8Content) === ''
) {

    http_response_code(502);

    header(
        'Content-Type: text/plain; charset=utf-8'
    );

    exit(
        "无法获取 M3U8 内容，请稍后重试\n"
    );
}


// ============================================================
// 修复 M3U8 中 TS / 子 M3U8 地址
// ============================================================

$m3u8Content =
    rewriteM3u8Urls(
        $m3u8Content,
        $playUrl
    );


// ============================================================
// 输出
// ============================================================

header(
    'Content-Type: application/vnd.apple.mpegurl; charset=utf-8'
);

header(
    'Cache-Control: no-cache, no-store, must-revalidate'
);

header(
    'Pragma: no-cache'
);

header(
    'Expires: 0'
);

echo $m3u8Content;

exit;
