<?php
require __DIR__ . "/../lib/helper.php";

$raw = $_GET['url'] ?? '';
if ($raw === '') {
    output_error("Provide ?url= (comma-separated 101soundboards.com URLs)", "400");
}

$out = [];
foreach (explode(',', $raw) as $url) {
    $url = trim($url);
    if ($url === '' || strpos($url, '101soundboards.com') === false) continue;
    list($body, $code, $err) = curl_fetch($url);
    $markers = [];
    foreach (["soundPlayer", "data-sound", "board_sounds", "board_index_container", "board_title", "<audio", "/boards/", "/tags/", "Just a moment", "cf_chl", "challenges.cloudflare", "captcha", "Enable JavaScript"] as $m) {
        $markers[$m] = substr_count($body, $m);
    }
    $snippet = null;
    foreach (["soundPlayer", "board_index_container", "Just a moment", "cf_chl", "Enable JavaScript"] as $m) {
        $p = strpos($body, $m);
        if ($p !== false) { $snippet = substr($body, max(0, $p - 250), 700); break; }
    }
    $out[] = [
        "url" => $url,
        "http_code" => $code,
        "curl_error" => $err,
        "length" => strlen($body),
        "is_challenge" => is_challenge_page($body),
        "markers" => $markers,
        "snippet" => $snippet
    ];
}
output_json($out, "200", ["count" => count($out)]);
