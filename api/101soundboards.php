<?php
header("Content-Type: application/json");
require "helper.php";

$query = $_GET['q'] ?? "";
if (!$query) output_error("Query parameter 'q' is required, example: ?q=bruh");

$page = get_page_param();
list($with_duration, $min_duration, $max_duration) = get_duration_params();

$base = "https://www.101soundboards.com/search/" . rawurlencode($query);
if ($page > 1) $base .= "?page=" . $page;

$html = fetch_html($base);
if (!$html) output_error("Page not found");

$sounds = parse_101_sounds($html);
// 101soundboards serves ~100 sounds per page with no total count:
// a full page likely means more pages exist.
$count = count($sounds);
$has_next = ($count >= 100);
$sounds = apply_duration_filter($sounds, $with_duration, $min_duration, $max_duration);
$meta = ["source" => fetch_source(), "page" => $page, "count" => count($sounds), "total_pages" => null, "has_next" => $has_next];
output_json($sounds, "200", $meta);
?>
