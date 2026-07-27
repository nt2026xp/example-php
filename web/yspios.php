<?php
// ===================== 生成播放列表（不影响正常播放） =====================
if (isset($_GET['generate']) && $_GET['generate'] == 1) {
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

    $domain = 'http://127.0.0.1:8901/wwwroot';
    $days = 7;
    $today = new DateTime();
    $format = isset($_GET['format']) ? strtolower($_GET['format']) : 'txt';

    if ($format === 'm3u') {
        // ===================== M3U格式 =====================
        header('Content-Type: audio/x-mpegurl; charset=utf-8');
        header('Content-Disposition: attachment; filename="playlist.m3u"');
        
        echo "#EXTM3U\n";
        echo "# 央视直播+回看列表\n\n";
        
        // 直播分组
        echo "# 直播源\n";
        foreach ($channels as $id => $name) {
            $url = "{$domain}/yspios.php?id={$id}";
            echo "#EXTINF:-1 group-title=\"直播源\",{$name}\n";
            echo "{$url}\n";
        }
        echo "\n";
        
        // 回看分组（7天）
        for ($d = 0; $d < $days; $d++) {
            $date = clone $today;
            $date->modify("-$d days");
            $dateStr = $date->format('Ymd');
            $dateDisplay = $date->format('Y-m-d');
            
            $groupName = "回看{$dateDisplay}";
            foreach ($channels as $id => $name) {
                $url = "{$domain}/yspios.php?id={$id}&playseek={$dateStr}000000-{$dateStr}235959";
                echo "#EXTINF:-1 group-title=\"{$groupName}\",{$name}\n";
                echo "{$url}\n";
            }
            echo "\n";
        }
        
    } else {
        // ===================== TXT格式 =====================
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="playlist.txt"');
        
        $output = '';
        
        // 直播分组
        $output .= "直播源,#genre#\n";
        foreach ($channels as $id => $name) {
            $output .= "{$name},{$domain}/yspios.php?id={$id}\n";
        }
        $output .= "\n";
        
        // 回看分组（7天）
        for ($d = 0; $d < $days; $d++) {
            $date = clone $today;
            $date->modify("-$d days");
            $dateStr = $date->format('Ymd');
            $dateDisplay = $date->format('Y-m-d');
            
            $output .= "回看{$dateDisplay},#genre#\n";
            foreach ($channels as $id => $name) {
                $output .= "{$name},{$domain}/yspios.php?id={$id}&playseek={$dateStr}000000-{$dateStr}235959\n";
            }
            $output .= "\n";
        }
        
        echo $output;
    }
    die();
}

// ========== 以下是正常播放逻辑，不要改动 ==========
date_default_timezone_set('Asia/Shanghai');
$id = isset($_GET['id']) ? $_GET['id'] : 'cctv1';
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
    'cctvgeqwq' => ['2026874703', '600099659', 'shd'],
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
    'cetv1' => ['2022823801', '600171827', 'fhd'],
    'gxpd' => ['2029360403', '600213139', 'fhd'],
    'xjws' => ['2019927403', '600152138', 'fhd']
];

// ========== 以下是 CKeyManager 类 ==========
class CKeyManager
{
    // 常量定义
    const DELTA = 0x9e3779b9;
    const ROUNDS = 16;
    const LOG_ROUNDS = 4;
    const SALT_LEN = 2;
    const ZERO_LEN = 7;
    const TEA_CKEY = '59b2f7cf725ef43c34fdd7c123411ed3';
    const GUARD_TEA_KEY = '110DBEC10C23E7D2E56A1CAD6914EF1B';
    
    private $xorKey = [0x84, 0x2E, 0xED, 0x08, 0xF0, 0x66, 0xE6, 0xEA, 0x48, 0xB4, 0xCA, 0xA9, 0x91, 0xED, 0x6F, 0xF3];
    private $guardXorKey = [0xB3, 0xC9, 0x53, 0xA0, 0x69, 0x13, 0xAD, 0x4D];
    private $standardAlphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
    private $customAlphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-=';
    
    private $guid = '';
    
    public function __construct()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        date_default_timezone_set('Asia/Shanghai');
        $this->generateGuid();
    }
    
    private function generateGuid()
    {
        $this->guid = sprintf('%08s%04s%04s%04s%12s',
            dechex(mt_rand(0, 0xffffffff)),
            dechex(mt_rand(0, 0xffff)),
            dechex(mt_rand(0, 0xffff)),
            dechex(mt_rand(0, 0xffff)),
            dechex(mt_rand(0, 0xffffffffffff))
        );
        
        if (strlen($this->guid) !== 32) {
            $this->guid = str_pad($this->guid, 32, '0', STR_PAD_LEFT);
        }
        return $this->guid;
    }
    
    public function getGuid()
    {
        return $this->guid;
    }
    
    public function setGuid($guid)
    {
        $this->guid = $guid;
    }
    
    public function resetGuid()
    {
        return $this->generateGuid();
    }
    
    private function randomHexStr($length)
    {
        $hex = '';
        for ($i = 0; $i < $length; $i++) {
            $hex .= dechex(mt_rand(0, 15));
        }
        return strtoupper($hex);
    }

    private function spvcode($defn) {
        $height = 1080;
        if (preg_match('/(4k|8k|hdr)/i', $defn)) {
            $height = 2160;
        }
        $frame_rates = [30, 60, 90, 120];
        $h264_parts = [];
        $h265_parts = [];
        foreach ($frame_rates as $fps) {
            $h264_parts[] = "{$fps}:{$height}";
            $h265_parts[] = "{$fps}:{$height}";
        }
        $h264_str = implode(',', $h264_parts);
        $h265_str = implode(',', $h265_parts);
        $spvcode_raw = "H({$h264_str}|{$h264_str});2({$h265_str}|{$h265_str})";
        return base64_encode($spvcode_raw);
    }

    private function calcSignature($buffer)
    {
        $signature = 0;
        foreach ($buffer as $byte) {
            $signature = (0x83 * $signature + ($byte & 0xFF)) & 0x7FFFFFFF;
        }
        return $signature;
    }
    
    private function customDecode($text)
    {
        if (empty($text)) return '';
        $text = rtrim($text, '=');
        if (strlen($text) % 4 != 0) {
            $text .= str_repeat('=', 4 - (strlen($text) % 4));
        }
        
        $translationTable = [];
        $len = min(strlen($this->customAlphabet), strlen($this->standardAlphabet));
        for ($i = 0; $i < $len; $i++) {
            $translationTable[$this->customAlphabet[$i]] = $this->standardAlphabet[$i];
        }
        
        $translatedStr = strtr($text, $translationTable);
        return base64_decode($translatedStr);
    }
    
    private function customEncode($text)
    {
        $encoded = base64_encode($text);
        
        $translationTable = [];
        $len = min(strlen($this->standardAlphabet), strlen($this->customAlphabet));
        for ($i = 0; $i < $len; $i++) {
            $translationTable[$this->standardAlphabet[$i]] = $this->customAlphabet[$i];
        }
        
        return rtrim(strtr($encoded, $translationTable), '=');
    }
    
    private function xorArray($byteArray)
    {
        $retArray = [];
        $len = count($byteArray);
        for ($i = 0; $i < $len; $i++) {
            $retArray[] = $byteArray[$i] ^ $this->xorKey[$i & 0xF];
        }
        return $retArray;
    }
    
    private function teaEncryptECB($pInBuf, $pKey)
    {
        if (strlen($pInBuf) < 8) {
            $pInBuf = str_pad($pInBuf, 8, "\0");
        }
        
        $unpacked = unpack('N2', $pInBuf);
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
            $sum = ($sum + self::DELTA) & 0xFFFFFFFF;
            $y = ($y + ((($z << 4) + $k[0]) ^ ($z + $sum) ^ (($z >> 5) + $k[1]))) & 0xFFFFFFFF;
            $z = ($z + ((($y << 4) + $k[2]) ^ ($y + $sum) ^ (($y >> 5) + $k[3]))) & 0xFFFFFFFF;
        }
        
        return pack('N2', $y, $z);
    }
    
    private function teaDecryptECB($pInBuf, $pKey)
    {
        $unpacked = unpack('N2', $pInBuf);
        $y = $unpacked[1];
        $z = $unpacked[2];
        
        $k = [
            unpack('N', substr($pKey, 0, 4))[1],
            unpack('N', substr($pKey, 4, 4))[1],
            unpack('N', substr($pKey, 8, 4))[1],
            unpack('N', substr($pKey, 12, 4))[1]
        ];
        
        $sum = (self::DELTA << self::LOG_ROUNDS) & 0xFFFFFFFF;
        
        for ($i = 0; $i < self::ROUNDS; $i++) {
            $z = ($z - ((($y << 4) + $k[2]) ^ ($y + $sum) ^ (($y >> 5) + $k[3]))) & 0xFFFFFFFF;
            $y = ($y - ((($z << 4) + $k[0]) ^ ($z + $sum) ^ (($z >> 5) + $k[1]))) & 0xFFFFFFFF;
            $sum = ($sum - self::DELTA) & 0xFFFFFFFF;
        }
        
        return pack('N2', $y, $z);
    }
    
    private function oiSymmetryEncrypt2($pInBuf, $nInBufLen, $pKey)
    {
        $nPadSaltBodyZeroLen = $nInBufLen + 1 + self::SALT_LEN + self::ZERO_LEN;
        $nPadlen = $nPadSaltBodyZeroLen % 8;
        if ($nPadlen) {
            $nPadlen = 8 - $nPadlen;
        }
        
        $pOutBuf = '';
        
        $src_buf = array_fill(0, 8, 0);
        $src_buf[0] = (mt_rand(0, 255) & 0xF8) | $nPadlen;
        $src_i = 1;
        
        while ($nPadlen) {
            $src_buf[$src_i] = mt_rand(0, 255);
            $src_i++;
            $nPadlen--;
        }
        
        $iv_plain = array_fill(0, 8, 0);
        $iv_crypt = $iv_plain;
        
        $i = 0;
        while ($i < self::SALT_LEN) {
            if ($src_i < 8) {
                $src_buf[$src_i] = mt_rand(0, 255);
                $src_i++;
                $i++;
            }
            
            if ($src_i == 8) {
                for ($j = 0; $j < 8; $j++) {
                    $src_buf[$j] ^= $iv_crypt[$j];
                }
                
                $temp_out = $this->teaEncryptECB(pack('C*', ...$src_buf), $pKey);
                $temp_bytes = array_values(unpack('C*', $temp_out));
                
                for ($j = 0; $j < 8; $j++) {
                    $temp_bytes[$j] ^= $iv_plain[$j];
                }
                
                $iv_plain = $src_buf;
                $iv_crypt = $temp_bytes;
                $pOutBuf .= pack('C*', ...$temp_bytes);
                $src_i = 0;
            }
        }
        
        $pInBufIndex = 0;
        while ($nInBufLen) {
            if ($src_i < 8) {
                $src_buf[$src_i] = ord($pInBuf[$pInBufIndex]);
                $pInBufIndex++;
                $src_i++;
                $nInBufLen--;
            }
            
            if ($src_i == 8) {
                for ($j = 0; $j < 8; $j++) {
                    $src_buf[$j] ^= $iv_crypt[$j];
                }
                
                $temp_out = $this->teaEncryptECB(pack('C*', ...$src_buf), $pKey);
                $temp_bytes = array_values(unpack('C*', $temp_out));
                
                for ($j = 0; $j < 8; $j++) {
                    $temp_bytes[$j] ^= $iv_plain[$j];
                }
                
                $iv_plain = $src_buf;
                $iv_crypt = $temp_bytes;
                $pOutBuf .= pack('C*', ...$temp_bytes);
                $src_i = 0;
            }
        }
        
        $i = 0;
        while ($i < self::ZERO_LEN) {
            if ($src_i < 8) {
                $src_buf[$src_i] = 0;
                $src_i++;
                $i++;
            }
            
            if ($src_i == 8) {
                for ($j = 0; $j < 8; $j++) {
                    $src_buf[$j] ^= $iv_crypt[$j];
                }
                
                $temp_out = $this->teaEncryptECB(pack('C*', ...$src_buf), $pKey);
                $temp_bytes = array_values(unpack('C*', $temp_out));
                
                for ($j = 0; $j < 8; $j++) {
                    $temp_bytes[$j] ^= $iv_plain[$j];
                }
                
                $iv_plain = $src_buf;
                $iv_crypt = $temp_bytes;
                $pOutBuf .= pack('C*', ...$temp_bytes);
                $src_i = 0;
            }
        }
        
        if ($src_i > 0) {
            for ($j = $src_i; $j < 8; $j++) {
                $src_buf[$j] = 0;
            }
            
            for ($j = 0; $j < 8; $j++) {
                $src_buf[$j] ^= $iv_crypt[$j];
            }
            
            $temp_out = $this->teaEncryptECB(pack('C*', ...$src_buf), $pKey);
            $temp_bytes = array_values(unpack('C*', $temp_out));
            
            for ($j = 0; $j < 8; $j++) {
                $temp_bytes[$j] ^= $iv_plain[$j];
            }
            
            $pOutBuf .= pack('C*', ...$temp_bytes);
        }
        
        return $pOutBuf;
    }
    
    private function oiSymmetryDecrypt2($pInBuf, $nInBufLen, $pKey)
    {
        if (($nInBufLen % 8) != 0 || $nInBufLen < 16) {
            return false;
        }
        
        $dest_buf_str = $this->teaDecryptECB(substr($pInBuf, 0, 8), $pKey);
        $dest_buf = array_values(unpack('C*', $dest_buf_str));
        
        $nPadLen = $dest_buf[0] & 0x07;
        
        $i = $nInBufLen - 1;
        $i = $i - $nPadLen - self::SALT_LEN - self::ZERO_LEN;
        
        if ($i < 0) {
            return false;
        }
        
        $pOutBufLen = $i;
        
        $iv_pre_crypt = array_fill(0, 8, 0);
        $iv_cur_crypt = array_values(unpack('C*', substr($pInBuf, 0, 8)));
        
        $pInBufOffset = 8;
        $dest_i = 1;
        
        $dest_i += $nPadLen;
        
        $salt_count = 1;
        while ($salt_count <= self::SALT_LEN) {
            if ($dest_i < 8) {
                $dest_i++;
                $salt_count++;
            } elseif ($dest_i == 8) {
                $iv_pre_crypt = $iv_cur_crypt;
                $iv_cur_crypt = array_values(unpack('C*', substr($pInBuf, $pInBufOffset, 8)));
                
                for ($j = 0; $j < 8; $j++) {
                    if ($pInBufOffset + $j >= $nInBufLen) {
                        return false;
                    }
                    $dest_buf[$j] ^= $iv_cur_crypt[$j];
                }
                
                $temp_buf = $this->teaDecryptECB(pack('C*', ...$dest_buf), $pKey);
                $dest_buf = array_values(unpack('C*', $temp_buf));
                
                $pInBufOffset += 8;
                $dest_i = 0;
            }
        }
        
        $nPlainLen = $pOutBufLen;
        $plain_bytes = [];
        
        while ($nPlainLen > 0) {
            if ($dest_i < 8) {
                $plain_bytes[] = $dest_buf[$dest_i] ^ $iv_pre_crypt[$dest_i];
                $dest_i++;
                $nPlainLen--;
            } elseif ($dest_i == 8) {
                $iv_pre_crypt = $iv_cur_crypt;
                $iv_cur_crypt = array_values(unpack('C*', substr($pInBuf, $pInBufOffset, 8)));
                
                for ($j = 0; $j < 8; $j++) {
                    if ($pInBufOffset + $j >= $nInBufLen) {
                        return false;
                    }
                    $dest_buf[$j] ^= $iv_cur_crypt[$j];
                }
                
                $temp_buf = $this->teaDecryptECB(pack('C*', ...$dest_buf), $pKey);
                $dest_buf = array_values(unpack('C*', $temp_buf));
                
                $pInBufOffset += 8;
                $dest_i = 0;
            }
        }
        
        return pack('C*', ...$plain_bytes);
    }

    private function generateCkGuardTime($timestamp, $guid, $guardData = '-1', $packageName = 'null', $processName = 'null')
    {
        $body = pack('N', $timestamp);
        foreach ([
            $this->guardLastFive($guid),
            $this->guardLastFive($packageName),
            $this->guardLastFive($processName),
            $guardData
        ] as $part) {
            $body .= pack('n', strlen($part)) . $part;
        }

        $plain = pack('n', strlen($body)) . $body;
        $checksum = $this->calcSignature(array_values(unpack('C*', $plain)));

        $encrypted = $this->oiSymmetryEncrypt2($plain, strlen($plain), hex2bin(self::GUARD_TEA_KEY));
        $encrypted .= pack('N', $checksum);

        $bytes = array_values(unpack('C*', $encrypted));
        $len = count($bytes);
        for ($i = 0; $i < $len; $i++) {
            $bytes[$i] ^= $this->guardXorKey[$i & 7];
        }

        return strtoupper(bin2hex(pack('C*', ...$bytes)));
    }

    private function guardLastFive($value)
    {
        $value = (string)$value;
        return strlen($value) >= 5 ? substr($value, -5) : '';
    }
    
    public function encryptDataToCKey($data)
    {
        $teaCkey = hex2bin(self::TEA_CKEY);
        
        $data_len = strlen($data);
        
        $data_array = array_values(unpack('C*', $data));
        $checksum = $this->calcSignature($data_array);
        
        $encrypted = $this->oiSymmetryEncrypt2($data, $data_len, $teaCkey);
        
        $encrypted .= pack('N', $checksum);
        
        $encrypted_array = array_values(unpack('C*', $encrypted));
        $xor_array = $this->xorArray($encrypted_array);
        $xor_encrypted = pack('C*', ...$xor_array);
        
        $base64_encoded = $this->customEncode($xor_encrypted);
        
        return "--01" . $base64_encoded;
    }
    
    public function decryptCKeyToData($ckey)
    {
        $teaCkey = hex2bin(self::TEA_CKEY);
        
        $ckey_without_prefix = substr($ckey, 4);
        
        $base64_decoded = $this->customDecode($ckey_without_prefix);
        if (!$base64_decoded) {
            return false;
        }
        
        $xor_array = array_values(unpack('C*', $base64_decoded));
        $xor_decrypted_array = $this->xorArray($xor_array);
        $xor_decrypted = pack('C*', ...$xor_decrypted_array);
        
        $data_len = strlen($xor_decrypted) - 4;
        $encrypted_data = substr($xor_decrypted, 0, $data_len);
        $checksum_bytes = substr($xor_decrypted, $data_len);
        $checksum = unpack('N', $checksum_bytes)[1];
        
        $decrypted = $this->oiSymmetryDecrypt2($encrypted_data, $data_len, $teaCkey);
        
        return [
            'data' => $decrypted,
            'checksum' => $checksum
        ];
    }
    
    public function buildPacket($params)
    {
        $data = '';
        
        $data .= hex2bin('0000004200000004000004d2');
        $data .= pack('N', $params['Platform']);
        $data .= pack('N', 0);
        $data .= pack('N', $params['Timestamp']);
        
        $sdtfrom = $params['Sdtfrom'];
        $data .= pack('n', strlen($sdtfrom)) . $sdtfrom;
        
        $randFlag = $params['randFlag'];
        $data .= pack('n', strlen($randFlag)) . $randFlag;
        
        $appVer = $params['appVer'];
        $data .= pack('n', strlen($appVer)) . $appVer;
        
        $vid = $params['vid'];
        $data .= pack('n', strlen($vid)) . $vid;
        
        $guid = $params['guid'];
        $data .= pack('n', strlen($guid)) . $guid;
        
        $data .= pack('N', 1);
        $data .= pack('N', 1);
        
        $uid = "2622783A";
        $data .= pack('n', strlen($uid)) . $uid;
        
        $bundleID = "nil";
        $data .= pack('n', strlen($bundleID)) . $bundleID;
        
        $uuid4 = $params['uuid4'];
        $data .= pack('n', strlen($uuid4)) . $uuid4;
        
        $data .= pack('n', strlen($bundleID)) . $bundleID;
        
        $ckeyVersion = "v0.1.000";
        $data .= pack('n', strlen($ckeyVersion)) . $ckeyVersion;
        
        $packageName = "com.cctv.yangshipin.app.iphone";
        $data .= pack('n', strlen($packageName)) . $packageName;
        
        $platform_str = "4330403";
        $data .= pack('n', strlen($platform_str)) . $platform_str;
        
        $ex_json_bus = "ex_json_bus";
        $data .= pack('n', strlen($ex_json_bus)) . $ex_json_bus;
        
        $ex_json_vs = "ex_json_vs";
        $data .= pack('n', strlen($ex_json_vs)) . $ex_json_vs;
        
        $ck_guard_time = $params['ck_guard_time'];
        $data .= pack('n', strlen($ck_guard_time)) . $ck_guard_time;
        
        $body_length = strlen($data);
        
        $buffer = pack('n', $body_length) . $data;
        
        $buffer_array = array_values(unpack('C*', $buffer));
        $signature = $this->calcSignature($buffer_array);
        
        $buffer = substr($buffer, 0, 18) . pack('N', $signature) . substr($buffer, 22);
        
        return $buffer;
    }
    
    public function generateCKey($cnlid, $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }
        
        $randFlag = base64_encode(random_bytes(18));
        $uuid4 = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        $ck_guard_time = $this->generateCkGuardTime($timestamp, $this->guid);
        $randFlag='_zj1A5Gh6QYcxWjIUGos2w==';
        $params = [
            'Platform' => 4330403,
            'Timestamp' => $timestamp,
            'Sdtfrom' => 'dcgh',
            'vid' => $cnlid,
            'guid' => $this->guid,
            'appVer' => 'V8.22.1035.3031',
            'randFlag' => $randFlag,
            'uuid4' => '57eab0c4-2c58-44c6-8ae9-dd2757525dc5',
            'ck_guard_time' => $ck_guard_time
        ];
        
        $buffer = $this->buildPacket($params);
        $ckey = $this->encryptDataToCKey($buffer);
        
        return [
            'ckey' => $ckey,
            'params' => $params,
            'buffer' => $buffer
        ];
    }
    
    public function makeLiveRequest($cnlid, $livepid = '600001859', $defn = 'fhd', $playseek = null)
    {
        $this->generateGuid();
        
        $ckeyResult = $this->generateCKey($cnlid);
        $ckey = $ckeyResult['ckey'];
        $params = $ckeyResult['params'];
        
        $flowid = sprintf('%s_%d', 
            sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            ),
            4330403
        );
        
        $isPlayback = !empty($playseek);
        $playbackTimestamp = null;
        
        if ($isPlayback) {
            try {
                $parts = explode('-', $playseek);
                $startTimeStr = $parts[0];
                $dateTime = DateTime::createFromFormat('YmdHis', $startTimeStr, new DateTimeZone('Asia/Shanghai'));
                if ($dateTime === false) {
                    throw new Exception("回看时间格式错误: " . $startTimeStr);
                }
                $playbackTimestamp = $dateTime->getTimestamp();
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'error' => '回看时间处理失败: ' . $e->getMessage(),
                    'playseek' => $playseek
                ];
            }
        }
        
        $spvcode = $this->spvcode($defn);
        $request_params = [
            "atime" => "120",
            "livepid" => $livepid,
            "cnlid" => $cnlid,
            "appVer" => "V8.22.1035.3031",
            "app_version" => "300090",
            "caplv" => "1",
            "cmd" => "2",
            "defn" => $defn,
            "device" => "iPhone",
            "encryptVer" => "4.2",
            "getpreviewinfo" => "0",
            "hevclv" => "33",
            "lang" => "zh-Hans_JP",
            "livequeue" => "0",
            "logintype" => "1",
            "nettype" => "1",
            "newnettype" => "1",
            "newplatform" => "4330403",
            "platform" => "4330403",
            "sdtfrom" => "v3021",
            "spacode" => "23",
            "spaudio" => "1",
            "spdemuxer" => "6",
            "spdrm" => "2",
            "spdynamicrange" => "7",
            "spflv" => "1",
            "spflvaudio" => "1",
            "sphdrfps" => "60",
            "sphttps" => "0",
            "spvcode" => "MSgzMDoyMTYwLDYwOjIxNjB8MzA6MjE2MCw2MDoyMTYwKTsyKDMwOjIxNjAsNjA6MjE2MHwzMDoyMTYwLDYwOjIxNjAp",
            "spvideo" => "4",
            "stream" => "1",
            "system" => "1",
            "sysver" => "ios18.2.1",
            "uhd_flag" => "4",
            "cKey" => $ckey,
            "guid" => $this->guid,
            "fntick" => $params['Timestamp'],
            "flowid" => $flowid,
        ];
        
        if ($isPlayback) {
            $request_params['playbacktime'] = $playbackTimestamp;
            
            $response = $this->sendHttpRequest($request_params);
            
            if ($response['success'] && isset($response['response']['playurl'])) {
                return $response;
            } else {
                unset($request_params['playbacktime']);
                
                $response = $this->sendHttpRequest($request_params);
                
                if ($response['success'] && isset($response['response']['playurl'])) {
                    $playurl = $response['response']['playurl'];
                    $playurl = $this->processPlaybackUrl($playurl, $playbackTimestamp);
                    $response['response']['playurl'] = $playurl;
                    return $response;
                } else {
                    return [
                        'success' => false,
                        'error' => '无法获取回看地址',
                        'playseek' => $playseek,
                        'response' => $response['response'] ?? null
                    ];
                }
            }
        } else {
            $request_params['playbacktime'] = "0";
            return $this->sendHttpRequest($request_params);
        }
    }
    
    private function processPlaybackUrl($playurl, $playbackTimestamp)
    {
        $urlParts = explode('/', $playurl);
        if (count($urlParts) >= 3) {
            $urlParts[2] = 'tlivecloud-playback-cdn.ysp.cctv.cn/tcloud.cctv.com';
            $playurl = implode('/', $urlParts);
            
            if (strpos($playurl, '?') !== false) {
                $playurl .= '&starttime=' . $playbackTimestamp;
            } else {
                $playurl .= '?starttime=' . $playbackTimestamp;
            }
        }
        return $playurl;
    }
    
    private function sendHttpRequest($params)
    {
        $url = "https://bkliveinfo.ysp.cctv.cn";
        $query_string = http_build_query($params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . $query_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: qqlive',
            'Connection: Keep-Alive',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'cURL错误: ' . $error,
                'http_code' => $http_code
            ];
        }
        
        $data = json_decode($response, true);
        if ($data) {
            if (isset($data['iretcode'])) {
                $result = [
                    'success' => $data['iretcode'] == 0,
                    'iretcode' => $data['iretcode'],
                    'http_code' => $http_code,
                    'response' => $data
                ];
                
                if ($data['iretcode'] == 0) {
                    $result['playurl'] = $data['playurl'] ?? null;
                } else {
                    $result['error'] = $data['errinfo'] ?? '未知错误';
                }
                
                return $result;
            }
        }
        
        return [
            'success' => false,
            'error' => '无效的JSON响应',
            'http_code' => $http_code,
            'raw_response' => substr($response, 0, 500)
        ];
    }
    
    public function getPlayUrl($cnlid, $livepid = '600001859', $defn = 'fhd', $playseek = null)
    {
        $result = $this->makeLiveRequest($cnlid, $livepid, $defn, $playseek);
        if ($result['success'] && isset($result['playurl'])) {
            return $result['playurl'];
        }
        return null;
    }
    
    public function parsePacket($data)
    {
        if (strlen($data) < 2) {
            return false;
        }
        
        $pos = 0;
        $result = [];
        
        $result['packet_len'] = unpack('n', substr($data, $pos, 2))[1];
        $pos += 2;
        $result['header'] = substr($data, $pos, 12);
        $pos += 12;
        $result['platform'] = unpack('N', substr($data, $pos, 4))[1];
        $pos += 4;
        $result['signature'] = unpack('N', substr($data, $pos, 4))[1];
        $pos += 4;
        $result['timestamp'] = unpack('N', substr($data, $pos, 4))[1];
        $pos += 4;
        
        $sdtfrom_len = unpack('n', substr($data, $pos, 2))[1];
        $pos += 2;
        $result['sdtfrom'] = substr($data, $pos, $sdtfrom_len);
        $pos += $sdtfrom_len;
        
        $randFlag_len = unpack('n', substr($data, $pos, 2))[1];
        $pos += 2;
        $result['randFlag'] = substr($data, $pos, $randFlag_len);
        $pos += $randFlag_len;
        
        $appVer_len = unpack('n', substr($data, $pos, 2))[1];
        $pos += 2;
        $result['appVer'] = substr($data, $pos, $appVer_len);
        $pos += $appVer_len;
        
        $vid_len = unpack('n', substr($data, $pos, 2))[1];
        $pos += 2;
        $result['vid'] = substr($data, $pos, $vid_len);
        $pos += $vid_len;
        
        $guid_len = unpack('n', substr($data, $pos, 2))[1];
        $pos += 2;
        $result['guid'] = substr($data, $pos, $guid_len);
        $pos += $guid_len;
        
        $result['part1'] = unpack('N', substr($data, $pos, 4))[1];
        $pos += 4;
        $result['isDlna'] = unpack('N', substr($data, $pos, 4))[1];
        $pos += 4;
        
        $uid_len = unpack('n', substr($data, $pos, 2))[1];
        $pos += 2;
        $result['uid'] = substr($data, $pos, $uid_len);
        $pos += $uid_len;
        
        $bundleID_len = unpack('n', substr($data, $pos, 2))[1];
        $pos += 2;
        $result['bundleID'] = substr($data, $pos, $bundleID_len);
        $pos += $bundleID_len;
        
        $uuid4_len = unpack('n', substr($data, $pos, 2))[1];
        $pos += 2;
        $result['uuid4'] = substr($data, $pos, $uuid4_len);
        $pos += $uuid4_len;
        
        $bundleID1_len = unpack('n', substr($data, $pos, 2))[1];
        $pos += 2;
        $result['bundleID1'] = substr($data, $pos, $bundleID1_len);
        $pos += $bundleID1_len;
        
        $ckeyVersion_len = unpack('n', substr($data, $pos, 2))[1];
        $pos += 2;
        $result['ckeyVersion'] = substr($data, $pos, $ckeyVersion_len);
        $pos += $ckeyVersion_len;
        
        $packageName_len = unpack('n', substr($data, $pos, 2))[1];
        $pos += 2;
        $result['packageName'] = substr($data, $pos, $packageName_len);
        $pos += $packageName_len;
        
        $platform_str_len = unpack('n', substr($data, $pos, 2))[1];
        $pos += 2;
        $result['platform_str'] = substr($data, $pos, $platform_str_len);
        $pos += $platform_str_len;
        
        $ex_json_bus_len = unpack('n', substr($data, $pos, 2))[1];
        $pos += 2;
        $result['ex_json_bus'] = substr($data, $pos, $ex_json_bus_len);
        $pos += $ex_json_bus_len;
        
        $ex_json_vs_len = unpack('n', substr($data, $pos, 2))[1];
        $pos += 2;
        $result['ex_json_vs'] = substr($data, $pos, $ex_json_vs_len);
        $pos += $ex_json_vs_len;
        
        $ck_guard_time_len = unpack('n', substr($data, $pos, 2))[1];
        $pos += 2;
        $result['ck_guard_time'] = substr($data, $pos, $ck_guard_time_len);
        $pos += $ck_guard_time_len;
        
        $result['total_size'] = strlen($data);
        $result['parsed_size'] = $pos;
        $result['remaining'] = substr($data, $pos);
        
        return $result;
    }
    
    public function verifyCKey($ckey)
    {
        $decrypt_result = $this->decryptCKeyToData($ckey);
        if (!$decrypt_result) {
            return false;
        }
        
        $calculated_checksum = $this->calcSignature(array_values(unpack('C*', $decrypt_result['data'])));
        return $decrypt_result['checksum'] == $calculated_checksum;
    }
    
    public function parsePlayseek($playseek)
    {
        $parts = explode('-', $playseek);
        if (count($parts) !== 2) {
            throw new Exception("回看时间格式错误，应为: YYYYMMDDHHMMSS-YYYYMMDDHHMMSS");
        }
        
        $startTimeStr = $parts[0];
        $endTimeStr = $parts[1];
        
        $startTime = DateTime::createFromFormat('YmdHis', $startTimeStr, new DateTimeZone('Asia/Shanghai'));
        $endTime = DateTime::createFromFormat('YmdHis', $endTimeStr, new DateTimeZone('Asia/Shanghai'));
        
        if ($startTime === false || $endTime === false) {
            throw new Exception("回看时间解析失败");
        }
        
        return [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'start_timestamp' => $startTime->getTimestamp(),
            'end_timestamp' => $endTime->getTimestamp(),
            'start_str' => $startTime->format('Y-m-d H:i:s'),
            'end_str' => $endTime->format('Y-m-d H:i:s'),
            'duration' => $endTime->getTimestamp() - $startTime->getTimestamp()
        ];
    }
    
    public function generatePlayseek($startDateTime, $endDateTime)
    {
        $startTime = DateTime::createFromFormat('Y-m-d H:i:s', $startDateTime, new DateTimeZone('Asia/Shanghai'));
        $endTime = DateTime::createFromFormat('Y-m-d H:i:s', $endDateTime, new DateTimeZone('Asia/Shanghai'));
        
        if ($startTime === false || $endTime === false) {
            throw new Exception("时间格式错误，应为: Y-m-d H:i:s");
        }
        
        return $startTime->format('YmdHis') . '-' . $endTime->format('YmdHis');
    }
}

// ========== 主逻辑 ==========
$ckeyManager = new CKeyManager();
$playseek = $_GET['playseek'] ?? null;
$cookieKey = 'playurl_cache';
$cacheTimeoutLive = 80;
$cookieExpire = time() + 3600;

$cacheJson = $_COOKIE[$cookieKey] ?? '{}';
$cache = json_decode($cacheJson, true) ?: [];

$now = time();
$isLive = ($playseek === null || $playseek === '');

$playUrl = null;
$m3u8Content = false;
$maxAttempts = 2;

for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    $needRefresh = true;

    if ($attempt == 1 && $isLive) {
        if (isset($cache[$id]) && is_array($cache[$id])) {
            $entry = $cache[$id];
            if (($now - $entry['time']) <= $cacheTimeoutLive) {
                $needRefresh = false;
                $playUrl = $entry['url'];
            }
        }
    }

    if ($needRefresh) {
        $playUrl = $ckeyManager->getPlayUrl($n[$id][0], $n[$id][1], $n[$id][2], $playseek);
        if (!$playUrl) {
            die("获取播放地址失败\n");
        }

        if ($isLive) {
            $cache[$id] = [
                'url' => $playUrl,
                'time' => $now
            ];
            setcookie($cookieKey, json_encode($cache), $cookieExpire, '/');
        } else {
            header("Location: " . $playUrl);
            exit();
        }
    }

    $m3u8Content = @file_get_contents($playUrl);
    if ($m3u8Content === false) {
        $m3u8Content = false;
    }

    if ($m3u8Content !== false) {
        break;
    }

    if ($attempt == 1 && $isLive && !$needRefresh) {
        unset($cache[$id]);
        setcookie($cookieKey, json_encode($cache), $cookieExpire, '/');
    }
}

if ($m3u8Content === false) {
    die("无法获取 M3U8 内容，请稍后重试\n");
}

$baseUrl = substr($playUrl, 0, strrpos($playUrl, '/') + 1);
header('Content-Type: application/vnd.apple.mpegurl');
print_r(preg_replace("/(.*?.ts)/i", $baseUrl."$1", $m3u8Content));
exit();
