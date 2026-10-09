<?php
// router.php - Vercel routing simulation on local development

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

if ($path === '/') {
    require __DIR__ . '/api/index.php';
    return true;
}

$endpoint = trim(rtrim($path, '/'), '/');

$listEndpoints = ['101board', '101categories', '101category', '101soundboards', 'best', 'category', 'favorites', 'memesoundboard', 'recent', 'search', 'search_all', 'trending', 'uploaded'];

if ($endpoint === 'detail') {
    require __DIR__ . '/api/detail.php';
    return true;
}

if (in_array($endpoint, $listEndpoints, true)) {
    require __DIR__ . '/api/list.php';
    return true;
}

if (file_exists(__DIR__ . $path) && !is_dir(__DIR__ . $path)) {
    return false;
}
require __DIR__ . '/api/404.php';
return true;
