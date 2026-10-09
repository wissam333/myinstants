<?php
require __DIR__ . "/../lib/helper.php";

$raw = $_GET['url'] ?? '';
if ($raw === '') {
    output_error("Provide ?url= (comma-separated 101soundboards.com URLs)", "400");
}
$defaultNeedles = "v-bind:sound,<audio,data-sound,sound_transcript,board_index_title,ItemList,download_url,\"url\":";
$needles = explode(',', $_GET['needles'] ?? $defaultNeedles);

$out = [];
foreach (explode(',', $raw) as $url) {
    $url = trim($url);
    if ($url === '' || strpos($url, '101soundboards.com') === false) continue;
    list($body, $code, $err) = curl_fetch($url);
    $counts = [];
    $snippets = [];
    foreach ($needles as $n) {
        if ($n === '') continue;
        $counts[$n] = substr_count($body, $n);
        $p = strpos($body, $n);
        if ($p !== false) {
            $snippets[$n] = substr($body, max(0, $p - 300), 1200);
        }
    }
    $out[] = [
        "url" => $url,
        "http_code" => $code,
        "curl_error" => $err,
        "length" => strlen($body),
        "is_challenge" => is_challenge_page($body),
        "counts" => $counts,
        "snippets" => $snippets,
        "head" => substr($body, 0, 2500)
    ];
}
output_json($out, "200", ["count" => count($out)]);