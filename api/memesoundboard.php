<?php
header("Content-Type: application/json");
require "helper.php";

$query = $_GET['q'] ?? "";
if (!$query) output_error("Query parameter 'q' is required, example: ?q=bruh");

$page = get_page_param();
list($with_duration, $min_duration, $max_duration) = get_duration_params();

$apiUrl = "https://play-v1.soundboard.cloud/api/memesoundboard.io/sounds/search?name=" . urlencode($query) . "&page=" . $page . "&page_size=35";
list($body, $code) = curl_fetch($apiUrl, 15);
if ($code < 200 || $code >= 300 || !$body) output_error("Upstream memesoundboard search failed (HTTP $code). Please retry later.", "502");

$data = json_decode($body, true);
if (!is_array($data)) output_error("Upstream memesoundboard returned invalid data. Please retry later.", "502");

list($sounds, $total_pages) = parse_msb_api($data, $query);
$sounds = apply_duration_filter($sounds, $with_duration, $min_duration, $max_duration);
$meta = array_merge(
    ["source" => "memesoundboard"],
    pagination_meta($page, $total_pages, count($sounds))
);
output_json($sounds, "200", $meta);
?>
