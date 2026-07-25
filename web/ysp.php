<?php
// 在文件最开头添加输出缓冲和错误控制
ob_start();
error_reporting(E_ALL ^ E_DEPRECATED);
ini_set('display_errors', 0);

date_default_timezone_set('Asia/Shanghai');

// 获取参数，若不传参默认播放 cctv1
$id = isset($_GET['id']) ? $_GET['id'] : 'cctv1';
$playseek = isset($_GET['playseek']) ? $_GET['playseek'] : null;

$n = [
    'cctv1' => ['2024078201', '600001859', 'fhd'], //CCTV-1高清
    'cctv2' => ['2024075401', '600001800', 'fhd'], //CCTV-2高清
    'cctv3' => ['2024068501', '600001801', 'fhd'], //CCTV-3高清
    'cctv4' => ['2029797101', '600001814', 'fhd'], //CCTV-4高清
    'cctv5' => ['2024078401', '600001818', 'fhd'], //CCTV-5高清
    'cctv5p' => ['2024078001', '600001817', 'fhd'], //CCTV-5+高清
    'cctv6' => ['2013693901', '600108442', 'fhd'], //CCTV-6高清
    'cctv7' => ['2024072001', '600004092', 'fhd'], //CCTV-7高清
    'cctv8' => ['2029793001', '600001803', 'fhd'], //CCTV-8高清
    'cctv9' => ['2024078601', '600004078', 'fhd'], //CCTV-9高清
    'cctv10' => ['2024078701', '600001805', 'fhd'], //CCTV-10高清
    'cctv11' => ['2027248701', '600001806', 'fhd'], //CCTV-11高清
    'cctv12' => ['2027248801', '600001807', 'fhd'], //CCTV-12高清
    'cctv13' => ['2029797201', '600001811', 'fhd'], //CCTV-13高清
    'cctv14' => ['2027248901', '600001809', 'fhd'], //CCTV-14高清
    'cctv15' => ['2027249001', '600001815', 'fhd'], //CCTV-15高清
    'cctv16' => ['2027249101', '600098637', 'fhd'], //CCTV-16高清
    'cctv164k' => ['2027249301', '600099502', 'fhd'], //CCTV-16(4K)
    'cctv17' => ['2027249401', '600001810', 'fhd'], //CCTV-17高清
    'cctv4k' => ['2029810301', '600002264', 'fhd'], //CCTV-4K
    'cctv8k' => ['2026774101', '600156816', 'fhd'], //CCTV-8K
    'cgtn' => ['2024181701', '600014550', 'fhd'], //CGTN
    'cgtnfy' => ['2024181801', '600084704', 'fhd'], //CGTN法语频道
    'cgtney' => ['2024181901', '600084758', 'fhd'], //CGTN俄语频道
    'cgtnalby' => ['2024182001', '600084782', 'fhd'], //CGTN阿拉伯语频道
    'cgtnxby' => ['2024182101', '600084744', 'fhd'], //CGTN西班牙语频道
    'cgtnwyjl' => ['2024182301', '600084781', 'fhd'], //CGTN外语纪录频道
    'cctvfyjc' => ['2025637103', '600099658', 'shd'], //CCTV风云剧场频道
    'cctvdyjc' => ['2026874203', '600099655', 'shd'], //CCTV第一剧场频道
    'cctvhjjc' => ['2026874303', '600099620', 'shd'], //CCTV怀旧剧场频道
    'cctvsjdl' => ['2026874403', '600099637', 'shd'], //CCTV世界地理频道
    'cctvfyyy' => ['2026874503', '600099660', 'shd'], //CCTV风云音乐频道
    'cctvbqkj' => ['2026874603', '600099649', 'shd'], //CCTV兵器科技频道
    'cctvfyzq' => ['2026966203', '600099636', 'shd'], //CCTV风云足球频道
    'cctvgeqwq' => ['2026874703', '600099659', 'shd'], //CCTV高尔夫·网球频道
    'cctvnxss' => ['2026874803', '600099650', 'shd'], //CCTV女性时尚频道
    'cctvyswhjp' => ['2026874903', '600099653', 'shd'], //CCTV央视文化精品频道
    'cctvystq' => ['2026875003', '600099652', 'shd'], //CCTV央视台球频道
    'cctvdszn' => ['2026875103', '600099656', 'shd'], //CCTV电视指南频道
    'cctvwsjk' => ['2025637003', '600099651', 'shd'], //CCTV卫生健康频道
    'bjws' => ['2024052703', '600002309', 'fhd'], //北京卫视
    'jsws' => ['2024171103', '600002521', 'fhd'], //江苏卫视
    'dfws' => ['2024054503', '600002483', 'fhd'], //东方卫视
    'zjws' => ['2024054703', '600002520', 'fhd'], //浙江卫视
    'hnws' => ['2024054803', '600002475', 'fhd'], //湖南卫视
    'hbws' => ['2024171203', '600002508', 'fhd'], //湖北卫视
    'gdws' => ['2024060903', '600002485', 'fhd'], //广东卫视
    'gxws' => ['2024060703', '600002509', 'fhd'], //广西卫视
    'hljws' => ['2029797003', '600002498', 'fhd'], //黑龙江卫视
    'hnws2' => ['2024055603', '600002506', 'fhd'], //海南卫视
    'cqws' => ['2024061103', '600002531', 'fhd'], //重庆卫视
    'szws' => ['2024061303', '600002481', 'fhd'], //深圳卫视
    'scws' => ['2024061403', '600002516', 'fhd'], //四川卫视
    'henanws' => ['2029797303', '600002525', 'fhd'], //河南卫视
    'fjdnhz' => ['2024061503', '600002484', 'fhd'], //福建东南卫视
    'gzhws' => ['2024061603', '600002490', 'fhd'], //贵州卫视
    'jxws' => ['2024061703', '600002503', 'fhd'], //江西卫视
    'lnws' => ['2024171303', '600002505', 'fhd'], //辽宁卫视
    'ahws' => ['2024171403', '600002532', 'fhd'], //安徽卫视
    'hbws2' => ['2024171503', '600002493', 'fhd'], //河北卫视
    'sdws' => ['2029787903', '600002513', 'fhd'], //山东卫视
    'tjws' => ['2019927003', '600152137', 'fhd'], //天津卫视
    'jlws' => ['2025561503', '600190405', 'fhd'], //吉林卫视
    'shanxiws' => ['2029795103', '600190400', 'fhd'], //陕西卫视
    'nxws' => ['2025608503', '600190737', 'fhd'], //宁夏卫视
    'nmgws' => ['2025561203', '600190401', 'fhd'], //内蒙古卫视
    'ynws' => ['2025561303', '600190402', 'fhd'], //云南卫视
    'shanxiws2' => ['2025560803', '600190407', 'fhd'], //山西卫视
    'qhws' => ['2025559103', '600190406', 'fhd'], //青海卫视
    'xzws' => ['2025558003', '600190403', 'fhd'], //西藏卫视
    'cetv1' => ['2022823801', '600171827', 'fhd'], //中国教育电视台1频道
    'gxpd' => ['2029360403', '600213139', 'fhd'], //国学频道
    'xjws' => ['2019927403', '600152138', 'fhd'] //新疆卫视
];

class CKeyManager
{
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
        
        $randFlag = '_zj1A5Gh6QYcxWjIUGos2w==';
        $uuid4 = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        $ck_guard_time = $this->generateCkGuardTime($timestamp, $this->guid);
        
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
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }
        
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
            
            if ($response['success'] && isset($response['playurl'])) {
                return $response;
            } else {
                unset($request_params['playbacktime']);
                $response = $this->sendHttpRequest($request_params);
                
                if ($response['success'] && isset($response['playurl'])) {
                    $playurl = $this->processPlaybackUrl($response['playurl'], $playbackTimestamp);
                    $response['playurl'] = $playurl;
                    return $response;
                }
            }
        } else {
            $request_params['playbacktime'] = "0";
            return $this->sendHttpRequest($request_params);
        }

        return ['success' => false, 'error' => '获取播放地址失败'];
    }

    private function processPlaybackUrl($playurl, $playbackTimestamp)
    {
        $urlParts = explode('/', $playurl);
        if (count($urlParts) >= 3) {
            $urlParts[2] = 'tlivecloud-playback-cdn.ysp.cctv.cn/tcloud.cctv.com';
            $playurl = implode('/', $urlParts);
            $playurl .= (strpos($playurl, '?') !== false ? '&' : '?') . 'starttime=' . $playbackTimestamp;
        }
        return $playurl;
    }

    /**
     * 补全的 HTTP 请求发送函数
     */
    private function sendHttpRequest($params)
    {
        $url = 'https://vv.Live.ysp.cctv.cn/vmind/getvinfo?' . http_build_query($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'YangShiPin/300090 CFNetwork/1410.0.3 Darwin/22.6.0');
        curl_setopt($ch, CURLOPT_HEADER, true); // 获取响应头以拦截可能的302地址
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'error' => 'Curl请求失败'];
        }

        $header = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        // 如果API直接302重定向到了直播流
        if ($httpCode == 301 || $httpCode == 302) {
            if (preg_match('/Location:\s*(https?:\/\/[^\r\n]+)/i', $header, $matches)) {
                return ['success' => true, 'playurl' => trim($matches[1])];
            }
        }

        // 解析 JSON 数据
        $data = json_decode($body, true);
        if (isset($data['s']) && $data['s'] == 'o' && !empty($data['playurl'])) {
            return ['success' => true, 'playurl' => $data['playurl']];
        }

        return ['success' => false, 'error' => '未获取到有效的PlayURL', 'raw' => $body];
    }
}

// ==================== 主逻辑与重定向处理 ====================

// 判断频道标识是否存在
if (!isset($n[$id])) {
    header("HTTP/1.1 404 Not Found");
    die("未找到指定的频道");
}

$channel = $n[$id];
$cnlid = $channel[0];
$livepid = $channel[1];
$defn = $channel[2];

// 实例化解析类
$manager = new CKeyManager();
$result = $manager->makeLiveRequest($cnlid, $livepid, $defn, $playseek);

if ($result['success'] && !empty($result['playurl'])) {
    // 关键修正：直接以 302 形式重定向到 m3u8，支持所有播放器直接加载
    ob_end_clean();
    header("Location: " . $result['playurl']);
    exit;
} else {
    header("HTTP/1.1 500 Internal Server Error");
    echo "获取直播源地址失败，原因: " . ($result['error'] ?? '未知错误');
}
