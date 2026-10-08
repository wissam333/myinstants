<?php
header("Content-Type: application/json");
require "helper.php";

$query = $_GET['q'] ?? "";
if (!$query) output_error("Query parameter 'q' is required, example: ?q=bruh");

$page = get_page_param();
list($with_duration, $min_duration, $max_duration) = get_duration_params();

$targets = [
    "myinstants" => append_page_param("https://www.myinstants.com/en/search/?name=" . urlencode($query), $page),
    "memesoundboard" => "https://play-v1.soundboard.cloud/api/memesoundboard.io/sounds/search?name=" . urlencode($query) . "&page=" . $page . "&page_size=35",
    "101soundboards" => "https://www.101soundboards.com/search/" . rawurlencode($query) . ($page > 1 ? "?page=" . $page : "")
];

$responses = fetch_urls_parallel(array_values($targets));
$keys = array_keys($targets);
$bodyBySource = [];
$i = 0;
foreach ($keys as $k) $bodyBySource[$k] = $responses[array_values($targets)[$i++]];

$sources = [];
$lists = [];

// MyInstants (HTML)
$mi = $bodyBySource["myinstants"];
$miList = [];
$miTotal = null;
$miOk = ($mi[1] >= 200 && $mi[1] < 300);
if ($mi[1] >= 200 && $mi[1] < 300 && $mi[0]) {
    $miHtml = str_get_html($mi[0]);
    if ($miHtml && count($miHtml->find('div.instant')) > 0) {
        $miList = parse_sounds($miHtml);
        foreach ($miList as &$s) $s["source"] = "myinstants";
        $miTotal = parse_total_pages($miHtml);
    }
}
if ($miList === [] && ($mi[1] == 403 || $mi[1] == 429)) {
    // MyInstants blocked: one Wayback attempt for its share.
    list($aBody, $aCode) = curl_fetch(wayback_url($targets["myinstants"]), 12);
    $miOk = ($aCode >= 200 && $aCode < 300);
    if ($miOk && $aBody) {
        $aHtml = str_get_html($aBody);
        if ($aHtml && count($aHtml->find('div.instant')) > 0) {
            $miList = parse_sounds($aHtml);
            foreach ($miList as &$s) $s["source"] = "myinstants";
            $miTotal = parse_total_pages($aHtml);
        }
    }
}
$sources["myinstants"] = ["ok" => $miOk, "count" => count($miList), "total_pages" => $miTotal];
$lists[] = $miList;

// MemeSoundboard (JSON API)
$msb = $bodyBySource["memesoundboard"];
$msbList = [];
$msbTotal = null;
$msbOk = ($msb[1] >= 200 && $msb[1] < 300);
if ($msb[1] >= 200 && $msb[1] < 300 && $msb[0]) {
    $msbData = json_decode($msb[0], true);
    if (is_array($msbData)) list($msbList, $msbTotal) = parse_msb_api($msbData, $query);
}
$sources["memesoundboard"] = ["ok" => $msbOk, "count" => count($msbList), "total_pages" => $msbTotal];
$lists[] = $msbList;

// 101Soundboards (HTML + JSON-LD)
$s101 = $bodyBySource["101soundboards"];
$s101List = [];
$s101Ok = ($s101[1] >= 200 && $s101[1] < 300);
if ($s101[1] >= 200 && $s101[1] < 300 && $s101[0]) {
    $s101Html = str_get_html($s101[0]);
    if ($s101Html) $s101List = parse_101_sounds($s101Html);
}
$sources["101soundboards"] = ["ok" => $s101Ok, "count" => count($s101List), "total_pages" => null];
$lists[] = $s101List;

if (!$miOk && !$msbOk && !$s101Ok) {
    output_error("All upstream sources failed for this query. Please retry later.", "502");
}

$sounds = round_robin_merge($lists);
$sounds = apply_duration_filter($sounds, $with_duration, $min_duration, $max_duration);

$has_next = null;
foreach (["myinstants" => $miTotal, "memesoundboard" => $msbTotal] as $src => $tp) {
    if ($tp !== null && $page < $tp) { $has_next = true; break; }
}
if ($has_next === null && count($s101List) >= 100) $has_next = true;

$meta = [
    "source" => "mixed",
    "page" => $page,
    "count" => count($sounds),
    "total_pages" => null,
    "has_next" => $has_next,
    "sources" => $sources
];
output_json($sounds, "200", $meta);
?>
