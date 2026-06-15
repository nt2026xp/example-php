<?php
/**
 * Usage:
 *   http://host/camelscore.php
 *   http://host/camelscore.php?id=list
 *   http://host/camelscore.php?id=sd-xxxxx
 *   http://host/camelscore.php?stream=sd-xxxxx
 *   http://host/camelscore.php?debug=1
 */

date_default_timezone_set('Asia/Shanghai');

const APP_CODE = 'D04B29D6B957CD44DC5F9894189380B8';
const API_BASE = 'https://api.cameltv.live';
const LIVE_BASE = 'https://liveplay1.camel4.live/live/';
const REFERER_URL = 'https://www.camelscore.live/';
const ORIGIN_URL = 'https://www.camelscore.live';
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0';
const CACHE_TTL = 0.3;

const LAYOUT_MANAGER_AES_KEY_HEX = 'b5521549a83a1f005ada2c5d889872a151afd95851ff600b47cccdaedfa38dc5';

function fail_text($message, $status = 500)
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
}

function cache_path()
{
    return sys_get_temp_dir() . '/camelscore_php_cache.json';
}

function read_cache()
{
    $path = cache_path();
    if (!is_file($path)) {
        return array();
    }
    $data = json_decode((string)file_get_contents($path), true);
    return is_array($data) ? $data : array();
}

function write_cache($cache)
{
    file_put_contents(cache_path(), json_encode($cache, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function uuid_v4()
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}

function http_headers_common($session = array(), $content_type = '')
{
    $headers = array(
        'User-Agent: ' . UA,
        'Accept: application/json, text/plain, */*',
        'Accept-Language: en,zh-CN;q=0.9,zh;q=0.8',
        'Referer: ' . REFERER_URL,
        'Origin: ' . ORIGIN_URL,
        'sec-ch-ua: "Google Chrome";v="147", "Not.A/Brand";v="8", "Chromium";v="147"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "Windows"',
        'sec-fetch-dest: empty',
        'sec-fetch-mode: cors',
        'sec-fetch-site: cross-site',
    );
    if ($content_type !== '') {
        $headers[] = 'Content-Type: ' . $content_type;
    }
    if (!empty($session['device_id'])) {
        $headers[] = 'deviceid: ' . $session['device_id'];
    }
    $headers[] = 'device: WEB';
    $headers[] = 'appversion: 20.0.0.0';
    $headers[] = 'node: camel1_g2';
    $headers[] = 'region: ' . (!empty($session['region']) ? $session['region'] : 'XM');
    if (!empty($session['uid'])) {
        $headers[] = 'uid: ' . $session['uid'];
    }
    return $headers;
}

function http_headers_stream()
{
    return array(
        'User-Agent: ' . UA,
        'Referer: ' . REFERER_URL,
        'Origin: ' . ORIGIN_URL,
        'Accept: */*',
    );
}

function http_request($url, $method = 'GET', $headers = array(), $body = null, $timeout = 12)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(5, $timeout));
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $data = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($errno) {
        throw new RuntimeException('curl error: ' . $error);
    }
    return array($status, $data);
}

function json_request($url, $method = 'GET', $headers = array(), $body = null, $timeout = 12)
{
    list($status, $data) = http_request($url, $method, $headers, $body, $timeout);
    $json = json_decode((string)$data, true);
    if (!is_array($json)) {
        throw new RuntimeException('invalid json from ' . $url . ': HTTP ' . $status . ' ' . substr((string)$data, 0, 160));
    }
    return array($status, $json);
}

function multi_http_get($items, $headers = array(), $timeout = 10)
{
    $mh = curl_multi_init();
    $handles = array();
    foreach ($items as $key => $url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(5, $timeout));
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_multi_add_handle($mh, $ch);
        $handles[$key] = $ch;
    }

    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) {
            curl_multi_select($mh, 0.5);
        }
    } while ($running && $status == CURLM_OK);

    $results = array();
    foreach ($handles as $key => $ch) {
        $results[$key] = array(
            'status' => (int)curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'body' => (string)curl_multi_getcontent($ch),
            'error' => curl_error($ch),
        );
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    return $results;
}

function current_script_url()
{
    $scheme = 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
    } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $scheme = 'https';
    }
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '127.0.0.1';
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/camelscore.php';
    return $scheme . '://' . $host . $script;
}

function play_link_for_stream($stream)
{
    $params = array('id' => $stream);
    if (isset($_GET['region']) && $_GET['region'] !== '') {
        $params['region'] = trim($_GET['region']);
    }
    return current_script_url() . '?' . http_build_query($params);
}

function ensure_session(&$cache)
{
    if (empty($cache['session']) || empty($cache['session']['uid']) || empty($cache['session']['device_id'])) {
        $cache['session'] = array(
            'uid' => '',
            'user_sig' => '',
            'device_id' => uuid_v4(),
            'region' => isset($_GET['region']) ? trim($_GET['region']) : 'XM',
        );
    }
    if (!empty($cache['session']['uid'])) {
        return $cache['session'];
    }

    $url = API_BASE . '/account-service/login/anonymous/web?appCode=' . rawurlencode(APP_CODE);
    $headers = http_headers_common($cache['session'], 'application/x-www-form-urlencoded');
    list($status, $json) = json_request($url, 'POST', $headers, json_encode(array('appCode' => APP_CODE)), 12);
    if (!empty($json['success']) && !empty($json['detail']['key'])) {
        $cache['session']['uid'] = $json['detail']['key'];
        $cache['session']['user_sig'] = isset($json['detail']['value']) ? $json['detail']['value'] : '';
        write_cache($cache);
        return $cache['session'];
    }
    throw new RuntimeException('anonymous login failed: HTTP ' . $status . ' ' . json_encode($json, JSON_UNESCAPED_UNICODE));
}

function stream_name_from_url($url)
{
    $path = parse_url($url, PHP_URL_PATH);
    $name = basename((string)$path);
    if (substr($name, -5) === '.m3u8') {
        $name = substr($name, 0, -5);
    }
    return strpos($name, 'sd-') === 0 ? $name : '';
}

function discover_stream_name(&$cache)
{
    if (!empty($_GET['id']) && $_GET['id'] !== 'list') {
        return trim($_GET['id']);
    }
    if (!empty($_GET['stream'])) {
        return trim($_GET['stream']);
    }
    if (!empty($cache['stream_name']) && empty($_GET['refresh_stream'])) {
        return $cache['stream_name'];
    }

    $session = ensure_session($cache);
    $headers = http_headers_common($session);
    list($status, $home) = json_request(API_BASE . '/camel-service/ee/sports_live/home?page=1&size=20', 'GET', $headers, null, 12);
    $matches = isset($home['data']['results']) && is_array($home['data']['results']) ? $home['data']['results'] : array();
    if (!$matches) {
        throw new RuntimeException('no matches found: HTTP ' . $status);
    }

    usort($matches, function ($a, $b) {
        $as = !empty($a['coverage']['has_stream']) ? 1 : 0;
        $bs = !empty($b['coverage']['has_stream']) ? 1 : 0;
        return $bs - $as;
    });

    $matches = array_slice($matches, 0, 12);
    foreach ($matches as $match) {
        $match_id = isset($match['id']) ? $match['id'] : (isset($match['matchId']) ? $match['matchId'] : '');
        if ($match_id === '') {
            continue;
        }
        try {
            $url = API_BASE . '/camel-service/ee/sports_live/loadAnchorsByMatchId?matchId=' . rawurlencode($match_id);
            list(, $data) = json_request($url, 'GET', $headers, null, 8);
            $streams = array();
            if (!empty($data['detail']['streams']) && is_array($data['detail']['streams'])) {
                $streams = $data['detail']['streams'];
            } elseif (!empty($data['data']) && is_array($data['data'])) {
                $streams = $data['data'];
            }
            foreach ($streams as $item) {
                $stream = isset($item['streamName']) ? $item['streamName'] : '';
                if ($stream === '') {
                    $stream = stream_name_from_url(isset($item['streamUrlM3u8']) ? $item['streamUrlM3u8'] : (isset($item['streamUrl']) ? $item['streamUrl'] : ''));
                }
                if ($stream !== '') {
                    $cache['stream_name'] = $stream;
                    write_cache($cache);
                    return $stream;
                }
            }
        } catch (Throwable $e) {
            continue;
        }
    }

    throw new RuntimeException('no playable stream found');
}

function output_stream_list(&$cache)
{
    set_time_limit(0);
    $session = ensure_session($cache);
    $headers = http_headers_common($session);
    $size = isset($_GET['size']) ? intval($_GET['size']) : 50;
    if ($size < 1) {
        $size = 50;
    }
    if ($size > 200) {
        $size = 200;
    }

    list($status, $home) = json_request(API_BASE . '/camel-service/ee/sports_live/home?page=1&size=' . $size, 'GET', $headers, null, 12);
    $matches = isset($home['data']['results']) && is_array($home['data']['results']) ? $home['data']['results'] : array();
    if (!$matches) {
        fail_text("# 无法获取比赛列表 HTTP {$status}", 502);
    }

    $live_matches = array();
    foreach ($matches as $m) {
        $status_id = isset($m['status_id']) ? intval($m['status_id']) : 0;
        $has_stream = !empty($m['coverage']['has_stream']);
        if ($has_stream && (isset($_GET['all']) || in_array($status_id, array(2, 4), true))) {
            $match_id = isset($m['id']) ? $m['id'] : (isset($m['matchId']) ? $m['matchId'] : '');
            if ($match_id !== '') {
                $live_matches[$match_id] = $m;
            }
        }
    }

    if (!$live_matches) {
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-cache');
        echo "# 没有找到正在直播且带直播源的赛事\n";
        exit;
    }

    $urls = array();
    foreach ($live_matches as $match_id => $m) {
        $urls[$match_id] = API_BASE . '/camel-service/ee/sports_live/loadAnchorsByMatchId?matchId=' . rawurlencode($match_id);
    }
    $anchor_results = multi_http_get($urls, $headers, 10);

    $rows = array();
    foreach ($anchor_results as $match_id => $res) {
        $data = json_decode($res['body'], true);
        if (!$data) {
            continue;
        }
        $streams = array();
        if (!empty($data['detail']['streams']) && is_array($data['detail']['streams'])) {
            $streams = $data['detail']['streams'];
        } elseif (!empty($data['data']) && is_array($data['data'])) {
            $streams = $data['data'];
        }
        if (!$streams) {
            continue;
        }

        $stream = '';
        $raw_url = '';
        foreach ($streams as $item) {
            $raw_url = isset($item['streamUrl']) ? $item['streamUrl'] : (isset($item['streamUrlM3u8']) ? $item['streamUrlM3u8'] : '');
            $stream = isset($item['streamName']) ? $item['streamName'] : stream_name_from_url($raw_url);
            if ($stream !== '') {
                break;
            }
        }
        if ($stream === '') {
            continue;
        }

        $info = $live_matches[$match_id];
        $home_name = isset($info['home_team']['name']) ? $info['home_team']['name'] : '';
        $away_name = isset($info['away_team']['name']) ? $info['away_team']['name'] : '';
        $home_score = isset($info['home_scores'][0]) ? $info['home_scores'][0] : 0;
        $away_score = isset($info['away_scores'][0]) ? $info['away_scores'][0] : 0;
        $competition = isset($info['competition']['name']) ? $info['competition']['name'] : '';
        $title = trim($competition . ' ' . $home_name . ' ' . $home_score . '-' . $away_score . ' ' . $away_name);

        $rows[$stream] = array(
            'title' => $title !== '' ? $title : $stream,
            'competition' => $competition,
            'home' => $home_name,
            'away' => $away_name,
            'homeScore' => $home_score,
            'awayScore' => $away_score,
            'link' => play_link_for_stream($stream),
        );
    }

    if (isset($_GET['format']) && $_GET['format'] === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache');
        echo json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo "\n";
        exit;
    }

    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-cache');
    header('Content-Disposition: inline');

    echo "#EXTM3U\n";
    foreach ($rows as $stream => $row) {
        $title = str_replace(array("\r", "\n", ","), array(' ', ' ', ' '), $row['title']);
        echo '#EXTINF:-1 tvg-id="' . $stream . '" group-title="' . str_replace('"', "'", $row['competition']) . '",' . $title . "\n";
        echo $row['link'] . "\n";
    }
    if (!$rows) {
        echo "# 没有解析到可播放 stream\n";
    }
    exit;
}

function b64_decode_unpadded($value)
{
    $pad = strlen($value) % 4;
    if ($pad) {
        $value .= str_repeat('=', 4 - $pad);
    }
    $decoded = base64_decode($value, true);
    if ($decoded === false) {
        throw new RuntimeException('base64 decode failed');
    }
    return $decoded;
}

function decrypt_tx_secret($encrypted)
{
    $value = trim($encrypted);
    if (strlen($value) <= 16) {
        throw new RuntimeException('encrypted txSecret is too short');
    }
    $iv = b64_decode_unpadded(substr($value, 0, 16));
    $cipher_and_tag = b64_decode_unpadded(substr($value, 16));
    if (strlen($iv) !== 12 || strlen($cipher_and_tag) <= 16) {
        throw new RuntimeException('invalid txSecret layout');
    }
    $tag = substr($cipher_and_tag, -16);
    $ciphertext = substr($cipher_and_tag, 0, -16);
    $plain = openssl_decrypt($ciphertext, 'aes-256-gcm', hex2bin(LAYOUT_MANAGER_AES_KEY_HEX), OPENSSL_RAW_DATA, $iv, $tag);
    if ($plain === false || strlen($plain) !== 32) {
        throw new RuntimeException('txSecret AES-GCM decrypt failed');
    }
    return $plain;
}

function first_media_line($playlist, $base_url)
{
    foreach (preg_split('/\r\n|\r|\n/', $playlist) as $line) {
        $line = trim($line);
        if ($line !== '' && strpos($line, '#') !== 0) {
            return absolutize_url($line, $base_url);
        }
    }
    throw new RuntimeException('playlist has no media url');
}

function absolutize_url($url, $base)
{
    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }
    if (strpos($url, '//') === 0) {
        return 'https:' . $url;
    }
    $p = parse_url($base);
    $scheme = isset($p['scheme']) ? $p['scheme'] : 'https';
    $host = isset($p['host']) ? $p['host'] : '';
    if (strpos($url, '/') === 0) {
        return $scheme . '://' . $host . $url;
    }
    $path = isset($p['path']) ? $p['path'] : '/';
    if (substr($path, -1) !== '/') {
        $path = dirname($path) . '/';
    }
    return $scheme . '://' . $host . $path . $url;
}

function rewrite_m3u8_absolute_ts($playlist, $base_url)
{
    $out = array();
    foreach (preg_split('/\r\n|\r|\n/', $playlist) as $line) {
        $trim = trim($line);
        if ($trim === '' || strpos($trim, '#') === 0) {
            $out[] = $line;
        } else {
            $out[] = absolutize_url($trim, $base_url);
        }
    }
    return implode("\n", $out) . "\n";
}

function cached_m3u8_url(&$cache, $stream, $now)
{
    if (!empty($_GET['nocache'])) {
        return '';
    }
    if (!empty($cache['m3u8_links'][$stream]) && !empty($cache['m3u8_expires'][$stream]) && $now < intval($cache['m3u8_expires'][$stream]) - 45) {
        return $cache['m3u8_links'][$stream];
    }

    $legacy_key = 'auth_' . $stream;
    if (!empty($cache[$legacy_key]['auth_url']) && !empty($cache[$legacy_key]['expire_time']) && $now < intval($cache[$legacy_key]['expire_time']) - 45) {
        if (empty($cache['m3u8_links']) || !is_array($cache['m3u8_links'])) {
            $cache['m3u8_links'] = array();
        }
        if (empty($cache['m3u8_expires']) || !is_array($cache['m3u8_expires'])) {
            $cache['m3u8_expires'] = array();
        }
        $cache['m3u8_links'][$stream] = $cache[$legacy_key]['auth_url'];
        $cache['m3u8_expires'][$stream] = intval($cache[$legacy_key]['expire_time']);
        write_cache($cache);
        return $cache['m3u8_links'][$stream];
    }

    return '';
}

function remember_m3u8_url(&$cache, $stream, $url, $expire_time)
{
    if (empty($cache['m3u8_links']) || !is_array($cache['m3u8_links'])) {
        $cache['m3u8_links'] = array();
    }
    if (empty($cache['m3u8_expires']) || !is_array($cache['m3u8_expires'])) {
        $cache['m3u8_expires'] = array();
    }
    if (empty($cache['m3u8_updated_at']) || !is_array($cache['m3u8_updated_at'])) {
        $cache['m3u8_updated_at'] = array();
    }

    $cache['m3u8_links'][$stream] = $url;
    $cache['m3u8_expires'][$stream] = intval($expire_time);
    $cache['m3u8_updated_at'][$stream] = time();
    unset($cache['auth_' . $stream]);
    write_cache($cache);
}

function forget_m3u8_url(&$cache, $stream)
{
    if (!empty($cache['m3u8_links']) && is_array($cache['m3u8_links'])) {
        unset($cache['m3u8_links'][$stream]);
    }
    if (!empty($cache['m3u8_expires']) && is_array($cache['m3u8_expires'])) {
        unset($cache['m3u8_expires'][$stream]);
    }
    if (!empty($cache['m3u8_updated_at']) && is_array($cache['m3u8_updated_at'])) {
        unset($cache['m3u8_updated_at'][$stream]);
    }
    unset($cache['auth_' . $stream]);
    write_cache($cache);
}

function build_auth_url(&$cache, $stream)
{
    $now = time();
    $cached_url = cached_m3u8_url($cache, $stream, $now);
    if ($cached_url !== '') {
        return $cached_url;
    }

    $session = ensure_session($cache);
    $headers = http_headers_common($session);
    $token_url = API_BASE . '/camel-service/ee/sports_live/token?streamName=' . rawurlencode($stream);
    list(, $token_json) = json_request($token_url, 'GET', $headers, null, 12);
    if (empty($token_json['data']['txSecret']) || empty($token_json['data']['txTime'])) {
        throw new RuntimeException('token failed: ' . json_encode($token_json, JSON_UNESCAPED_UNICODE));
    }

    $tx_secret = decrypt_tx_secret($token_json['data']['txSecret']);
    $signed_url = LIVE_BASE . $stream . '.m3u8?' . http_build_query(array(
        'txSecret' => $tx_secret,
        'txTime' => $token_json['data']['txTime'],
        'lat' => '9000',
    ));

    list($status, $top_body) = http_request($signed_url, 'GET', http_headers_stream(), null, 12);
    if ($status !== 200) {
        throw new RuntimeException('top m3u8 failed HTTP ' . $status . ': ' . substr((string)$top_body, 0, 160));
    }
    $auth_url = first_media_line($top_body, dirname($signed_url) . '/');
    $expire_time = isset($token_json['data']['expireTime']) ? intval($token_json['data']['expireTime']) : ($now + 240);
    remember_m3u8_url($cache, $stream, $auth_url, $expire_time);
    return $auth_url;
}

function fetch_media_playlist(&$cache, $stream)
{
    $playlist_key = 'playlist_' . $stream;
    $now = microtime(true);
    if (empty($_GET['nocache']) && !empty($cache[$playlist_key]['body']) && !empty($cache[$playlist_key]['updated_at']) && ($now - floatval($cache[$playlist_key]['updated_at'])) < CACHE_TTL) {
        return $cache[$playlist_key]['body'];
    }

    $last_error = '';
    for ($i = 0; $i < 3; $i++) {
        $auth_url = build_auth_url($cache, $stream);
        list($status, $body) = http_request($auth_url, 'GET', http_headers_stream(), null, 12);
        if ($status === 200) {
            $rewritten = rewrite_m3u8_absolute_ts((string)$body, dirname($auth_url) . '/');
            $cache[$playlist_key] = array(
                'body' => $rewritten,
                'updated_at' => microtime(true),
            );
            write_cache($cache);
            return $rewritten;
        }
        $last_error = 'media m3u8 HTTP ' . $status . ': ' . substr((string)$body, 0, 160);
        forget_m3u8_url($cache, $stream);
        usleep(250000 * ($i + 1));
    }

    if (!empty($cache[$playlist_key]['body'])) {
        return $cache[$playlist_key]['body'];
    }
    throw new RuntimeException($last_error);
}

function selftest()
{
    $fixtures = array(
        array(
            'nXgAs2BH5lBhAgvbdRXHhGdr9VPLjMmAd+XwRMVQmmLuA0gjOMRDRi9iqxFIvSBpjamBdlg7i3YWJ0vr',
            '364b7ab9e8cbdc98aad593605baa07fa',
        ),
        array(
            '4jlVFz6GIvsurUgiecq4jfP2y4QlQjoyU0knWe4XghsNE4nqzfU2Oios2FRnTgrd6BDOabt6IGRLeebr',
            '2082c957937f6270994e5397f4414526',
        ),
    );
    foreach ($fixtures as $case) {
        $actual = decrypt_tx_secret($case[0]);
        if ($actual !== $case[1]) {
            fail_text('selftest failed: ' . $actual . ' != ' . $case[1], 500);
        }
    }
    header('Content-Type: text/plain; charset=utf-8');
    echo "ok\n";
    exit;
}

try {
    if (isset($_GET['selftest'])) {
        selftest();
    }

    $cache = read_cache();
    if (isset($_GET['id']) && $_GET['id'] === 'list') {
        output_stream_list($cache);
    }
    $stream = discover_stream_name($cache);
    $body = fetch_media_playlist($cache, $stream);

    if (isset($_GET['debug'])) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "stream={$stream}\n";
        echo $body;
        exit;
    }

    header('Content-Type: application/vnd.apple.mpegurl');
    header('Access-Control-Allow-Origin: *');
    header('Cache-Control: max-age=1, must-revalidate');
    echo $body;
} catch (Throwable $e) {
    fail_text($e->getMessage(), 502);
}
