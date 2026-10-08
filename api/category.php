<?php
header("Content-Type: application/json");
require "helper.php";

$raw_category = $_GET['category'] ?? "";
if (!$raw_category) output_error("Query parameter 'category' is required, example: ?category=music. Valid: " . implode(", ", valid_categories()), "400");

$category = resolve_category($raw_category);
if ($category === null) output_error("Invalid category '" . $raw_category . "'. Valid: " . implode(", ", valid_categories()), "400");

$region = trim($_GET['q'] ?? "");
$page = get_page_param();
list($with_duration, $min_duration, $max_duration) = get_duration_params();

$base = "https://www.myinstants.com/en/categories/" . category_slug($category) . "/";
if ($region !== "") $base .= urlencode($region) . "/";

$html = fetch_html(append_page_param($base, $page));
if (!$html) output_error("Page not found");

$sounds = parse_sounds($html);
$total_pages = parse_total_pages($html);
$sounds = apply_duration_filter($sounds, $with_duration, $min_duration, $max_duration);
$meta = array_merge(
    ["source" => fetch_source(), "category" => $category, "region" => ($region !== "" ? $region : null)],
    pagination_meta($page, $total_pages, count($sounds))
);
output_json($sounds, "200", $meta);
?>
