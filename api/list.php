<?php
// Single serverless function for every list endpoint (Vercel Hobby: max 12
// functions per deployment). Dispatched by request path, e.g. /trending.
header("Content-Type: application/json");
require __DIR__ . "/../lib/helper.php";

$endpoint = trim(parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH), "/");

switch ($endpoint) {
    case "trending": {
        $query = $_GET['q'] ?? "";
        if (!$query) output_error("Query parameter 'q' is required, example: ?q=id");

        $page = get_page_param();
        list($with_duration, $min_duration, $max_duration) = get_duration_params();

        $html = fetch_html(append_page_param("https://www.myinstants.com/en/index/" . urlencode($query), $page));
        if (!$html) output_error("Page not found");

        $sounds = parse_sounds($html);
        $total_pages = parse_total_pages($html);
        $sounds = apply_duration_filter($sounds, $with_duration, $min_duration, $max_duration);
        output_json($sounds, "200", array_merge(["source" => fetch_source()], pagination_meta($page, $total_pages, count($sounds))));
        break;
    }

    case "search": {
        $query = $_GET['q'] ?? "";
        if (!$query) output_error("Query parameter 'q' is required, example: ?q=vine boom");

        $page = get_page_param();
        list($with_duration, $min_duration, $max_duration) = get_duration_params();

        $html = fetch_html(append_page_param("https://www.myinstants.com/en/search/?name=" . urlencode($query), $page));
        if (!$html) output_error("Page not found");

        $sounds = parse_sounds($html);
        $total_pages = parse_total_pages($html);
        $sounds = apply_duration_filter($sounds, $with_duration, $min_duration, $max_duration);
        output_json($sounds, "200", array_merge(["source" => fetch_source()], pagination_meta($page, $total_pages, count($sounds))));
        break;
    }

    case "recent": {
        $page = get_page_param();
        list($with_duration, $min_duration, $max_duration) = get_duration_params();

        $html = fetch_html(append_page_param("https://www.myinstants.com/en/recent", $page));
        if (!$html) output_error("Page not found");

        $sounds = parse_sounds($html);
        $total_pages = parse_total_pages($html);
        $sounds = apply_duration_filter($sounds, $with_duration, $min_duration, $max_duration);
        output_json($sounds, "200", array_merge(["source" => fetch_source()], pagination_meta($page, $total_pages, count($sounds))));
        break;
    }

    case "best": {
        $query = $_GET['q'] ?? "";
        if (!$query) output_error("Query parameter 'q' is required, example: ?q=id");

        $page = get_page_param();
        list($with_duration, $min_duration, $max_duration) = get_duration_params();

        $html = fetch_html(append_page_param("https://www.myinstants.com/en/best_of_all_time/" . urlencode($query), $page));
        if (!$html) output_error("Page not found");

        $sounds = parse_sounds($html);
        $total_pages = parse_total_pages($html);
        $sounds = apply_duration_filter($sounds, $with_duration, $min_duration, $max_duration);
        output_json($sounds, "200", array_merge(["source" => fetch_source()], pagination_meta($page, $total_pages, count($sounds))));
        break;
    }

    case "uploaded": {
        $username = $_GET['username'] ?? null;
        if (!$username) output_error("Query parameter 'username' is required, example: ?username=hellmouz", "400");

        $page = get_page_param();
        list($with_duration, $min_duration, $max_duration) = get_duration_params();

        $html = fetch_html(append_page_param("https://www.myinstants.com/en/profile/" . urlencode($username) . "/uploaded/", $page));
        if (!$html) output_error("Page not found");

        $sounds = parse_sounds($html);
        $total_pages = parse_total_pages($html);
        $sounds = apply_duration_filter($sounds, $with_duration, $min_duration, $max_duration);
        output_json($sounds, "200", array_merge(["source" => fetch_source()], pagination_meta($page, $total_pages, count($sounds))));
        break;
    }

    case "favorites": {
        $username = $_GET['username'] ?? null;
        if (!$username) output_error("Query parameter 'username' is required, example: ?username=hellmouz", "400");

        $page = get_page_param();
        list($with_duration, $min_duration, $max_duration) = get_duration_params();

        $html = fetch_html(append_page_param("https://www.myinstants.com/en/profile/" . urlencode($username), $page));
        if (!$html) output_error("Page not found");

        $sounds = parse_sounds($html);
        $total_pages = parse_total_pages($html);
        $sounds = apply_duration_filter($sounds, $with_duration, $min_duration, $max_duration);
        output_json($sounds, "200", array_merge(["source" => fetch_source()], pagination_meta($page, $total_pages, count($sounds))));
        break;
    }

    case "category": {
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
        break;
    }

    case "memesoundboard": {
        $query = $_GET['q'] ?? "";
        if (!$query) output_error("Query parameter 'q' is required, example: ?q=bruh");

        $page = get_page_param();
        list($with_duration, $min_duration, $max_duration) = get_duration_params();

        $apiUrl = "https://play-v1.soundboard.cloud/api/memesoundboard.io/sounds/search?name=" . urlencode($query) . "&page=" . $page . "&page_size=35";
        list($body, $code) = curl_fetch($apiUrl, 15);
        if ($code < 200 || $code >= 300 || !$body) output_error("Upstream memesoundboard search failed (HTTP $code). Please retry later.", "502");

        $data = json_decode($body, true);
        if (!is_array($data)) output_error("Upstream memesoundboard returned invalid data. Please retry later.", "502");

        list($sounds, $total_pages, $msb_has_next) = parse_msb_api($data, $query, $page, 35);
        $sounds = apply_duration_filter($sounds, $with_duration, $min_duration, $max_duration);
        $meta = array_merge(
            ["source" => "memesoundboard"],
            pagination_meta($page, $total_pages, count($sounds))
        );
        if ($msb_has_next !== null) $meta["has_next"] = $msb_has_next;
        output_json($sounds, "200", $meta);
        break;
    }

    case "101soundboards": {
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
        break;
    }

    case "freesound": {
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
        break;
    }

    case "search_all": {
        $query = $_GET['q'] ?? "";
        if (!$query) output_error("Query parameter 'q' is required, example: ?q=bruh");

        $page = get_page_param();
        list($with_duration, $min_duration, $max_duration) = get_duration_params();

        $targets = [
            "myinstants" => append_page_param("https://www.myinstants.com/en/search/?name=" . urlencode($query), $page),
            "memesoundboard" => "https://play-v1.soundboard.cloud/api/memesoundboard.io/sounds/search?name=" . urlencode($query) . "&page=" . $page . "&page_size=35",
            "101soundboards" => "https://www.101soundboards.com/search/" . rawurlencode($query) . ($page > 1 ? "?page=" . $page : "")
        ];

        // Freesound joins only when configured (free key via FREESOUND_API_KEY).
        $fsKey = freesound_key();
        $fsPageSize = 30;
        if ($fsKey) {
            $fsParams = [
                "query" => $query,
                "page" => $page,
                "page_size" => $fsPageSize,
                "sort" => "downloads_desc",
                "fields" => "id,name,previews,duration,license,username,images",
                "token" => $fsKey
            ];
            $targets["freesound"] = "https://freesound.org/apiv2/search/?" . http_build_query($fsParams);
        }

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
                unset($s);
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
                    unset($s);
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
        $msbHasNext = null;
        $msbOk = ($msb[1] >= 200 && $msb[1] < 300);
        if ($msb[1] >= 200 && $msb[1] < 300 && $msb[0]) {
            $msbData = json_decode($msb[0], true);
            if (is_array($msbData)) list($msbList, $msbTotal, $msbHasNext) = parse_msb_api($msbData, $query, $page, 35);
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

        // Freesound (official JSON API, skipped without key)
        $fsList = [];
        $fsTotal = null;
        $fsHasNext = null;
        $fsOk = false;
        if ($fsKey) {
            $fs = $bodyBySource["freesound"];
            $fsOk = ($fs[1] >= 200 && $fs[1] < 300);
            if ($fsOk && $fs[0]) {
                $fsData = json_decode($fs[0], true);
                if (is_array($fsData)) list($fsList, $fsTotal, $fsHasNext) = parse_freesound_api($fsData, $fsPageSize);
            }
            $sources["freesound"] = ["ok" => $fsOk, "count" => count($fsList), "total_pages" => $fsTotal];
            $lists[] = $fsList;
        } else {
            $sources["freesound"] = ["ok" => false, "count" => 0, "total_pages" => null, "reason" => "missing FREESOUND_API_KEY"];
        }

        if (!$miOk && !$msbOk && !$s101Ok && !$fsOk) {
            output_error("All upstream sources failed for this query. Please retry later.", "502");
        }

        $sounds = round_robin_merge($lists);
        $sounds = apply_duration_filter($sounds, $with_duration, $min_duration, $max_duration);

        $has_next = null;
        if ($msbHasNext === true || $fsHasNext === true) $has_next = true;
        foreach (["myinstants" => $miTotal, "memesoundboard" => $msbTotal, "freesound" => $fsTotal] as $src => $tp) {
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
        break;
    }

    default:
        output_error("Endpoint not found", "404");
}
?>
