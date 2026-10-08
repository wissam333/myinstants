<?php
header("Content-Type: application/json");
require "helper.php";

$query = $_GET['q'] ?? "";
if (!$query) output_error("Query parameter 'q' is required, example: ?q=airhorn");

$key = freesound_key();
if (!$key) output_error("Freesound source is not configured. Set FREESOUND_API_KEY (free key at https://freesound.org/apiv2/apply).", "503");

$page = get_page_param();
list($with_duration, $min_duration, $max_duration) = get_duration_params();
$page_size = 30;

$params = [
    "query" => $query,
    "page" => $page,
    "page_size" => $page_size,
    "sort" => "downloads_desc",
    "fields" => "id,name,previews,duration,license,username,images",
    "token" => $key
];
if ($min_duration !== null || $max_duration !== null) {
    $lo = ($min_duration !== null) ? $min_duration : "*";
    $hi = ($max_duration !== null) ? $max_duration : "*";
    $params["filter"] = "duration:[" . $lo . " TO " . $hi . "]";
}

$apiUrl = "https://freesound.org/apiv2/search/?" . http_build_query($params);
list($body, $code) = curl_fetch($apiUrl, 15);
if ($code < 200 || $code >= 300 || !$body) output_error("Upstream Freesound search failed (HTTP $code). Please retry later.", "502");

$data = json_decode($body, true);
if (!is_array($data)) output_error("Upstream Freesound returned invalid data. Please retry later.", "502");

list($sounds, $total_pages, $fs_has_next) = parse_freesound_api($data, $page_size);
// Durations come free from the API; only probe the rare gaps.
$sounds = apply_duration_filter($sounds, $with_duration, $min_duration, $max_duration);
$meta = array_merge(
    ["source" => "freesound"],
    pagination_meta($page, $total_pages, count($sounds))
);
if ($fs_has_next !== null) $meta["has_next"] = $fs_has_next;
output_json($sounds, "200", $meta);
?>
