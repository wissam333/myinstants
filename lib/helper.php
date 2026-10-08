<?php
require_once __DIR__ . "/simple_html_dom.php";

// Never leak warnings/deprecations into JSON responses.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '0');

function fetch_source() {
    global $fetch_source;
    return $fetch_source ?? 'live';
}

function wayback_url($url) {
    return 'https://web.archive.org/web/' . date('Y') . 'id_/' . $url;
}

function curl_fetch($url, $timeout = 15) {
    static $cookieFile = null;
    if ($cookieFile === null) $cookieFile = sys_get_temp_dir() . '/myinstants_cookies.txt';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9',
        'Referer: https://www.myinstants.com/',
        'Upgrade-Insecure-Requests: 1'
    ]);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    // Note: curl_close() intentionally omitted — deprecated since PHP 8.5.
    return [$body, $code, $error];
}

function proxy_templates() {
    // Plural wins; singular kept for backward compatibility.
    // Comma-separated, tried in order until one returns valid HTML, e.g.:
    //   UPSTREAM_PROXY_TEMPLATES="https://api.scraperapi.com?api_key=K1&url={url},https://app.scrapingbee.com/api/v1/?api_key=K2&url={url}"
    $raw = getenv('UPSTREAM_PROXY_TEMPLATES');
    if (!$raw) {
        $single = getenv('UPSTREAM_PROXY_TEMPLATE');
        if ($single) $raw = $single;
    }
    $out = [];
    if ($raw) {
        foreach (explode(',', $raw) as $t) {
            $t = trim($t);
            if ($t !== '' && strpos($t, '{url}') !== false) $out[] = $t;
        }
    }
    return $out;
}

function cache_smaxage($source) {
    $env = ['live' => 'CACHE_SMAXAGE_LIVE', 'proxy' => 'CACHE_SMAXAGE_PROXY', 'archive' => 'CACHE_SMAXAGE_ARCHIVE'];
    $defaults = ['live' => 3600, 'proxy' => 3600, 'archive' => 86400];
    $key = $env[$source] ?? $env['live'];
    $v = getenv($key);
    if ($v !== false && preg_match('/^\d+$/', (string)$v)) return (int)$v;
    return $defaults[$source] ?? 3600;
}

function valid_scrape_html($body) {
    if (!$body) return null;
    $html = str_get_html($body);
    if ($html && (count($html->find('div.instant')) > 0 || $html->find('h1#instant-page-title', 0))) {
        return $html;
    }
    return null;
}

function fetch_html($url, $retry = true) {
    global $fetch_source;
    $fetch_source = 'live';
    list($htmlString, $httpCode, $error) = curl_fetch($url);
    if (($httpCode == 403 || $httpCode == 429) && $retry) {
        sleep(1);
        return fetch_html($url, false);
    }
    if ($httpCode == 403 || $httpCode == 429 || !$htmlString) {
    // Escape hatch: paid/free-tier scraper proxies (real browsers that pass
    // Cloudflare). Set UPSTREAM_PROXY_TEMPLATES env var (comma-separated,
    // tried in order), e.g.:
    //   https://api.scraperapi.com?api_key=KEY&url={url}
    //   https://app.scrapingbee.com/api/v1/?api_key=KEY&url={url}
    //   https://api.zenrows.com/v1/?apikey=KEY&url={url}
    foreach (proxy_templates() as $tpl) {
        list($pBody, $pCode) = curl_fetch(str_replace('{url}', urlencode($url), $tpl), 15);
        if ($pCode >= 200 && $pCode < 300) {
            $pHtml = valid_scrape_html($pBody);
            if ($pHtml) {
                $fetch_source = 'proxy';
                return $pHtml;
            }
        }
    }
        // Fallback: latest Wayback Machine snapshot (raw markup via id_ suffix).
        list($aBody, $aCode) = curl_fetch(wayback_url($url), 12);
        if ($aCode >= 200 && $aCode < 300) {
            $aHtml = valid_scrape_html($aBody);
            if ($aHtml) {
                $fetch_source = 'archive';
                return $aHtml;
            }
        }
    }
    if ($httpCode >= 400 || !$htmlString) {
        if ($httpCode == 403 || $httpCode == 429) {
            output_error("Upstream myinstants.com refused this request (HTTP $httpCode, anti-bot protection) and no fallback source worked. Retry later or set UPSTREAM_PROXY_TEMPLATE.", "502");
        }
        output_error("Fetch failed: HTTP $httpCode, cURL Error: $error");
    }
    return str_get_html($htmlString);
}

function parse_sounds($html) {
    $sounds = [];
    $web = "https://www.myinstants.com";
    foreach ($html->find("div.instant") as $instant) {
        $link = $instant->find("a.instant-link", 0);
        if (!$link) continue;
        
        $title = $link->plaintext;
        $url = $web . $link->href;
        $id = trim(str_replace("/en/instant/", "", $link->href), "/");
        
        $btn = $instant->find("button.small-button", 0);
        $soundmp3 = $btn ? $btn->onclick : "";
        if (preg_match("/play\\('(.*?)'/", $soundmp3, $matches)) {
            $sounds[] = [
                "id" => $id,
                "title" => $title,
                "url" => $url,
                "mp3" => $web . $matches[1]
            ];
        }
    }
    return $sounds;
}

function output_error($msg, $status = "404") {
    http_response_code((int)$status);
    header("Access-Control-Allow-Origin: *");
    echo json_encode(["status" => $status, "author" => "wissam333", "message" => $msg], JSON_PRETTY_PRINT);
    exit;
}

function output_json($data, $status = "200", $meta = []) {
    http_response_code((int)$status);
    header("Access-Control-Allow-Origin: *");
    // Archive snapshots are immutable: cache them much longer (configurable).
    $maxAge = cache_smaxage($meta["source"] ?? "live");
    header("Cache-Control: s-maxage=$maxAge, stale-while-revalidate");
    $response = array_merge(["status" => $status, "author" => "wissam333"], $meta, ["data" => $data]);
    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

// --- Pagination helpers (mirrors myinstants.com ?page=N) ---

function get_page_param() {
    if (!isset($_GET['page']) || $_GET['page'] === "") return 1;
    $raw = $_GET['page'];
    if (is_array($raw) || !preg_match('/^\d+$/', (string)$raw)) {
        output_error("Query parameter 'page' must be a positive integer, example: ?page=4", "400");
    }
    $page = (int)$raw;
    if ($page < 1) output_error("Query parameter 'page' must be >= 1, example: ?page=4", "400");
    return $page;
}

function append_page_param($url, $page) {
    if ($page <= 1) return $url;
    $sep = (strpos($url, '?') === false) ? '?' : '&';
    return $url . $sep . 'page=' . $page;
}

function parse_total_pages($html) {
    $texts = [];
    $titleEl = $html->find('title', 0);
    if ($titleEl) $texts[] = $titleEl->plaintext;
    foreach ($html->find('meta[name=description]') as $meta) {
        $c = $meta->getAttribute('content');
        if ($c) $texts[] = $c;
    }
    foreach ($texts as $t) {
        if (preg_match('/Page\s+\d+\s+of\s+(\d+)/i', $t, $m)) {
            return (int)$m[1];
        }
    }
    return null;
}

function pagination_meta($page, $total_pages, $count) {
    $meta = ["page" => $page, "count" => $count];
    $meta["total_pages"] = $total_pages;
    $meta["has_next"] = ($total_pages !== null) ? ($page < $total_pages) : null;
    return $meta;
}

// --- Category helpers (fixed list, mirrored from myinstants.com nav) ---

function valid_categories() {
    return [
        "anime & manga",
        "games",
        "memes",
        "movies",
        "music",
        "politics",
        "pranks",
        "reactions",
        "sound effects",
        "sports",
        "television",
        "tiktok trends",
        "viral",
        "whatsapp audios"
    ];
}

function resolve_category($input) {
    $name = strtolower(trim(rawurldecode((string)$input)));
    $name = preg_replace('/\s+/', ' ', $name);
    foreach (valid_categories() as $canonical) {
        if ($name === $canonical) return $canonical;
    }
    return null;
}

function category_slug($canonical) {
    // Match site convention: spaces as %20, "&" left raw (e.g. anime%20&%20manga).
    return str_replace('%26', '&', rawurlencode($canonical));
}

// --- Duration helpers (myinstants.com exposes no durations, so probe MP3s) ---

function get_duration_params() {
    $with_duration = isset($_GET['with_duration']) && in_array(strtolower((string)$_GET['with_duration']), ["1", "true", "yes"], true);
    $min = null;
    $max = null;
    if (isset($_GET['min_duration']) && $_GET['min_duration'] !== "") {
        if (!is_numeric($_GET['min_duration']) || (float)$_GET['min_duration'] < 0) {
            output_error("Query parameter 'min_duration' must be a number >= 0 (seconds), example: ?min_duration=2", "400");
        }
        $min = (float)$_GET['min_duration'];
        $with_duration = true;
    }
    if (isset($_GET['max_duration']) && $_GET['max_duration'] !== "") {
        if (!is_numeric($_GET['max_duration']) || (float)$_GET['max_duration'] < 0) {
            output_error("Query parameter 'max_duration' must be a number >= 0 (seconds), example: ?max_duration=6", "400");
        }
        $max = (float)$_GET['max_duration'];
        $with_duration = true;
    }
    if ($min !== null && $max !== null && $min > $max) {
        output_error("Query parameter 'min_duration' must be <= 'max_duration'", "400");
    }
    return [$with_duration, $min, $max];
}

function mp3_duration_from_data($data) {
    $len = strlen($data);
    if ($len < 5) return null;
    $pos = 0;
    // Skip ID3v2 header
    if ($len > 10 && substr($data, 0, 3) === "ID3") {
        $size = ((ord($data[6]) & 0x7F) << 21) | ((ord($data[7]) & 0x7F) << 14) | ((ord($data[8]) & 0x7F) << 7) | (ord($data[9]) & 0x7F);
        $pos = 10 + $size;
    }
    $bitrates_mpeg1 = [0, 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320, 0];
    $bitrates_mpeg2 = [0, 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160, 0];
    $samplerates = [
        [44100, 48000, 32000, 0],
        [22050, 24000, 16000, 0],
        [11025, 12000, 8000, 0]
    ];
    $duration = 0.0;
    $frames = 0;
    $guard = 0;
    while ($pos + 4 <= $len && $guard < 10000) {
        $guard++;
        if (ord($data[$pos]) !== 0xFF || (ord($data[$pos + 1]) & 0xE0) !== 0xE0) {
            $pos++;
            continue;
        }
        $b1 = ord($data[$pos + 1]);
        $b2 = ord($data[$pos + 2]);
        $b3 = ord($data[$pos + 3]);
        $verBits = ($b1 >> 3) & 0x03;
        $layerBits = ($b1 >> 1) & 0x03;
        $bitrateIdx = ($b2 >> 4) & 0x0F;
        $sampleIdx = ($b2 >> 2) & 0x03;
        $pad = ($b2 >> 1) & 0x01;
        // Only MPEG Layer III with valid indices
        if ($layerBits !== 1 || $bitrateIdx === 0 || $bitrateIdx === 15 || $sampleIdx === 3 || $verBits === 1) {
            $pos++;
            continue;
        }
        if ($verBits === 3) {
            $bitrate = $bitrates_mpeg1[$bitrateIdx] * 1000;
            $verIdx = 0;
        } elseif ($verBits === 2) {
            $bitrate = $bitrates_mpeg2[$bitrateIdx] * 1000;
            $verIdx = 1;
        } else {
            $bitrate = $bitrates_mpeg2[$bitrateIdx] * 1000;
            $verIdx = 2;
        }
        if ($bitrate <= 0) { $pos++; continue; }
        $samplerate = $samplerates[$verIdx][$sampleIdx];
        if ($samplerate <= 0) { $pos++; continue; }
        if ($verBits === 3) {
            $frameLen = (int)(144 * $bitrate / $samplerate + $pad);
            $samples = 1152;
        } else {
            $frameLen = (int)(72 * $bitrate / $samplerate + $pad);
            $samples = 576;
        }
        if ($frameLen < 1 || $pos + $frameLen > $len + 1) {
            // Last truncated frame: count it and stop
            if ($pos + $frameLen > $len && $frames > 0) {
                $duration += $samples / $samplerate;
                $frames++;
            } else {
                $pos++;
                continue;
            }
            break;
        }
        $duration += $samples / $samplerate;
        $frames++;
        $pos += $frameLen;
    }
    if ($frames === 0) return null;
    return $duration;
}

function fetch_urls_parallel($urls) {
    $out = [];
    $urls = array_values(array_unique($urls));
    if (empty($urls)) return $out;
    $mh = curl_multi_init();
    $handles = [];
    foreach ($urls as $i => $url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_multi_add_handle($mh, $ch);
        $handles[$i] = $ch;
    }
    $running = null;
    do {
        $mrc = curl_multi_exec($mh, $running);
        if ($running) curl_multi_select($mh, 1.0);
    } while ($running && $mrc == CURLM_OK);
    foreach ($urls as $i => $url) {
        $ch = $handles[$i];
        $data = curl_multi_getcontent($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $out[$url] = [($code >= 200 && $code < 300) ? $data : null, $code];
        curl_multi_remove_handle($mh, $ch);
        // Note: curl_close() / curl_multi_close() intentionally omitted —
        // deprecated since PHP 8.5 (no-ops since PHP 8.0, handles auto-freed).
    }
    return $out;
}

function mp3_duration_of($data) {
    if (!$data) return null;
    $d = mp3_duration_from_data($data);
    return ($d !== null) ? round($d, 2) : null;
}

function fetch_mp3_durations($urls) {
    $map = [];
    $responses = fetch_urls_parallel($urls);
    $needArchive = [];
    foreach ($responses as $url => $r) {
        list($data) = $r;
        $duration = mp3_duration_of($data);
        $map[$url] = $duration;
        if ($duration === null) $needArchive[] = $url;
    }
    // Second pass: undetermined files may still be measurable via archived copy.
    if (!empty($needArchive)) {
        $archived = [];
        foreach ($needArchive as $u) $archived[] = wayback_url($u);
        $aResponses = fetch_urls_parallel($archived);
        $i = 0;
        foreach ($needArchive as $u) {
            $r = $aResponses[$archived[$i++]];
            $d = mp3_duration_of($r[0]);
            if ($d !== null) $map[$u] = $d;
        }
    }
    return $map;
}

function apply_duration_filter($sounds, $with_duration, $min_duration, $max_duration) {
    if (!$with_duration && $min_duration === null && $max_duration === null) return $sounds;
    $urls = [];
    foreach ($sounds as $s) {
        // Sounds with a trusted pre-known duration (e.g. 101soundboards JSON-LD) skip probing.
        if (!empty($s['mp3']) && (!isset($s['duration']) || !is_numeric($s['duration']))) $urls[] = $s['mp3'];
    }
    $durations = fetch_mp3_durations($urls);
    $out = [];
    foreach ($sounds as $s) {
        if (isset($s['duration']) && is_numeric($s['duration'])) {
            $d = round((float)$s['duration'], 2);
        } else {
            $d = isset($s['mp3'], $durations[$s['mp3']]) ? $durations[$s['mp3']] : null;
        }
        if ($min_duration !== null && ($d === null || $d < $min_duration)) continue;
        if ($max_duration !== null && ($d === null || $d > $max_duration)) continue;
        $s['duration'] = $d;
        $out[] = $s;
    }
    return $out;
}

// --- Multi-source helpers (MemeSoundboard / 101Soundboards / Freesound) ---

function iso8601_to_seconds($s) {
    if ($s === null || $s === '') return null;
    if (is_numeric($s)) return (float)$s;
    if (!is_string($s)) return null;
    if (preg_match('/^P(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+(?:\.\d+)?)S)?)?$/', trim($s), $m)) {
        $d = isset($m[1]) && $m[1] !== '' ? (int)$m[1] : 0;
        $h = isset($m[2]) && $m[2] !== '' ? (int)$m[2] : 0;
        $i = isset($m[3]) && $m[3] !== '' ? (int)$m[3] : 0;
        $sec = isset($m[4]) && $m[4] !== '' ? (float)$m[4] : 0;
        if ($d == 0 && $h == 0 && $i == 0 && $sec == 0) return null;
        return $d * 86400 + $h * 3600 + $i * 60 + $sec;
    }
    return null;
}

function round_robin_merge($lists) {
    $out = [];
    $i = 0;
    while (true) {
        $added = false;
        foreach ($lists as $l) {
            if (isset($l[$i])) { $out[] = $l[$i]; $added = true; }
        }
        if (!$added) break;
        $i++;
    }
    return $out;
}

function collect_101_items($node, &$sounds, &$seen) {
    if (!is_array($node)) return;
    if (isset($node['itemListElement']) && is_array($node['itemListElement'])) {
        foreach ($node['itemListElement'] as $el) {
            $item = (is_array($el) && isset($el['item']) && is_array($el['item'])) ? $el['item'] : $el;
            if (!is_array($item)) continue;
            $name = $item['name'] ?? null;
            $mp3 = $item['contentUrl'] ?? $item['contentURL'] ?? null;
            if (!$name || !$mp3 || isset($seen[$mp3])) continue;
            $seen[$mp3] = true;
            $url = $item['url'] ?? null;
            $id = $url ? basename(parse_url($url, PHP_URL_PATH)) : md5($mp3);
            $sounds[] = [
                "id" => $id,
                "title" => html_entity_decode($name, ENT_QUOTES | ENT_HTML5),
                "url" => $url,
                "mp3" => $mp3,
                "thumbnail" => $item['thumbnailUrl'] ?? $item['thumbnail'] ?? null,
                "duration" => iso8601_to_seconds($item['duration'] ?? null),
                "source" => "101soundboards"
            ];
        }
        return;
    }
    if (isset($node['mainEntity'])) {
        $me = $node['mainEntity'];
        if (is_array($me)) {
            if (isset($me['itemListElement']) || isset($me['@type'])) collect_101_items($me, $sounds, $seen);
            else foreach ($me as $sub) collect_101_items($sub, $sounds, $seen);
        }
        return;
    }
    foreach ($node as $v) {
        if (is_array($v) && (isset($v['itemListElement']) || (($v['@type'] ?? null) === 'ItemList'))) {
            collect_101_items($v, $sounds, $seen);
        }
    }
}

function parse_101_sounds($html) {
    $sounds = [];
    $seen = [];
    foreach ($html->find('script') as $script) {
        if (strtolower(trim($script->getAttribute('type'))) !== 'application/ld+json') continue;
        $data = json_decode(trim($script->innertext), true);
        if (!is_array($data)) continue;
        if (isset($data['@graph']) && is_array($data['@graph'])) {
            foreach ($data['@graph'] as $node) collect_101_items($node, $sounds, $seen);
        } else {
            collect_101_items($data, $sounds, $seen);
        }
    }
    return $sounds;
}

function parse_msb_api($data, $query, $page = 1, $page_size = 35) {
    $sounds = [];
    $total_pages = null;
    $has_next = null;
    if (!is_array($data)) return [$sounds, $total_pages, $has_next];
    $list = $data['data']['sounds'] ?? $data['data']['results'] ?? $data['sounds'] ?? $data['results'] ?? null;
    if (!is_array($list)) {
        foreach (['data', null] as $wrap) {
            $cand = ($wrap === null) ? $data : ($data[$wrap] ?? null);
            if (is_array($cand) && $cand !== [] && array_keys($cand) === range(0, count($cand) - 1)) {
                $list = $cand;
                break;
            }
        }
        if (!is_array($list)) $list = [];
    }
    // DRF-style pagination: top-level count/next/previous.
    $next = $data['next'] ?? null;
    if (is_string($next) || $next === null) {
        if (array_key_exists('next', $data)) $has_next = ($next !== null && $next !== '');
    }
    $total = $data['count'] ?? null;
    if ($total === null) {
        $meta = (isset($data['meta']) && is_array($data['meta'])) ? $data['meta'] : [];
        if (isset($data['data']) && is_array($data['data'])) {
            foreach (['meta', 'initialMeta', 'pagination'] as $k) {
                if (isset($data['data'][$k]) && is_array($data['data'][$k])) { $meta = $data['data'][$k]; break; }
            }
        }
        foreach (['last_page', 'lastPage', 'total_pages', 'totalPages'] as $k) {
            if (isset($meta[$k]) && is_numeric($meta[$k])) { $total_pages = (int)$meta[$k]; break; }
        }
        if ($total_pages === null) {
            foreach (['total_items', 'totalItems', 'total', 'count'] as $k) {
                if (isset($meta[$k]) && is_numeric($meta[$k]) && (int)$meta[$k] > 0) {
                    $total = (int)$meta[$k];
                    break;
                }
            }
        }
    }
    if ($total_pages === null && is_numeric($total) && (int)$total > 0 && $page_size > 0) {
        $total_pages = (int)ceil((int)$total / $page_size);
    }
    if ($has_next === null && $total_pages !== null) $has_next = ($page < $total_pages);
    foreach ($list as $item) {
        if (!is_array($item)) continue;
        $title = $item['name'] ?? $item['title'] ?? null;
        $mp3 = $item['sound_file'] ?? $item['mp3'] ?? $item['audio'] ?? $item['audio_url'] ?? $item['file'] ?? null;
        if (!$title || !$mp3) continue;
        // sound_file is a relative path (sounds/xxx.mp3): resolve against media host.
        if (!preg_match('#^https?://#i', $mp3)) {
            $mp3 = 'https://play-v1.soundboard.cloud/media/' . ltrim($mp3, '/');
        }
        $id = isset($item['id']) ? (string)$item['id'] : null;
        $slug = $item['slug'] ?? null;
        if ($id !== null && $slug) $detail = "https://memesoundboard.io/" . $slug . "-" . $id;
        elseif ($id !== null) $detail = "https://memesoundboard.io/search/" . rawurlencode($query);
        else { $id = md5($mp3); $detail = "https://memesoundboard.io/search/" . rawurlencode($query); }
        $dur = $item['duration'] ?? $item['length'] ?? null;
        $sounds[] = [
            "id" => $id,
            "title" => $title,
            "url" => $detail,
            "mp3" => $mp3,
            "duration" => (is_numeric($dur) ? (float)$dur : null),
            "source" => "memesoundboard"
        ];
    }
    return [$sounds, $total_pages, $has_next];
}

function freesound_key() {
    $k = getenv('FREESOUND_API_KEY');
    return ($k !== false && trim((string)$k) !== '') ? trim((string)$k) : null;
}

function parse_freesound_api($data, $page_size = 30) {
    $sounds = [];
    $total_pages = null;
    $has_next = null;
    if (!is_array($data)) return [$sounds, $total_pages, $has_next];
    $list = $data['results'] ?? null;
    if (!is_array($list)) $list = [];
    $count = $data['count'] ?? null;
    if (is_numeric($count) && (int)$count > 0 && $page_size > 0) {
        $total_pages = (int)ceil((int)$count / $page_size);
    }
    if (array_key_exists('next', $data)) {
        $has_next = ($data['next'] !== null && $data['next'] !== '');
    }
    foreach ($list as $item) {
        if (!is_array($item)) continue;
        $id = $item['id'] ?? null;
        $name = $item['name'] ?? null;
        $previews = (isset($item['previews']) && is_array($item['previews'])) ? $item['previews'] : [];
        $mp3 = $previews['preview-hq-mp3'] ?? $previews['preview-lq-mp3'] ?? null;
        if ($id === null || !$name || !$mp3) continue;
        $username = $item['username'] ?? null;
        $url = $username ? "https://freesound.org/people/" . $username . "/sounds/" . $id . "/" : null;
        $dur = $item['duration'] ?? null;
        $images = (isset($item['images']) && is_array($item['images'])) ? $item['images'] : [];
        $sounds[] = [
            "id" => "freesound-" . $id,
            "title" => $name,
            "url" => $url,
            "mp3" => $mp3,
            "thumbnail" => $images['spectral_m'] ?? $images['waveform_m'] ?? null,
            "duration" => (is_numeric($dur) ? (float)$dur : null),
            "license" => $item['license'] ?? null,
            "source" => "freesound"
        ];
    }
    return [$sounds, $total_pages, $has_next];
}
