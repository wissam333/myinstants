<?php
// Streams an audio file from a referer-protected CDN (e.g. 101soundboards)
// so cross-origin players and direct downloads work. Hosts are whitelisted.
header("Access-Control-Allow-Origin: *");
require __DIR__ . "/../lib/helper.php";

$url = $_GET['url'] ?? "";
if ($url === "") {
    output_error("Query parameter 'url' is required (audio file URL)", "400");
}
$host = (string)parse_url($url, PHP_URL_HOST);
if (!is_allowed_audio_host($host)) {
    output_error("Host '" . $host . "' is not allowed on /stream", "403");
}

$referer = (strpos($host, 'soundboard.cloud') !== false)
    ? "https://play-v1.soundboard.cloud/"
    : "https://www.101soundboards.com/";
$ua = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36";

// --- Debug mode: report what the CDN actually returns (temporary) ---
if (isset($_GET['debug'])) {
    $probe = function ($ref, $withReferer) use ($url, $ua) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "User-Agent: $ua",
            "Range: bytes=0-2047",
            $withReferer ? "Referer: $ref" : "Accept: */*"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = [];
        foreach (explode("\r\n", substr($resp, 0, $headerSize)) as $line) {
            if (isset($line[0]) && $line[0] !== "\r" && strpos($line, ':') !== false) {
                $parts = explode(':', $line, 2);
                $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
        }
        return [
            "http_code" => $code,
            "curl_error" => $err,
            "content_type" => $headers['content-type'] ?? null,
            "content_length" => $headers['content-length'] ?? null,
            "content_range" => $headers['content-range'] ?? null,
            "body_bytes" => max(0, strlen($resp) - $headerSize)
        ];
    };
    output_json([
        "url" => $url,
        "host_allowed" => true,
        "without_referer" => $probe("", false),
        "with_referer" => $probe($referer, true)
    ], "200", ["debug" => true]);
}

$headers = [
    "User-Agent: $ua",
    "Referer: $referer",
    "Accept: */*"
];
$range = $_SERVER['HTTP_RANGE'] ?? "";
if ($range !== "") $headers[] = "Range: " . $range;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$out = fopen("php://output", "w");
curl_setopt($ch, CURLOPT_FILE, $out);
curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) {
    $len = strlen($header);
    $trimmed = trim($header);
    if ($trimmed === "") return $len;
    if (preg_match('#^HTTP/\S+\s+(\d{3})#', $trimmed, $m)) {
        http_response_code((int)$m[1]);
        return $len;
    }
    if (preg_match('#^([^:]+):\s*(.*)$#', $trimmed, $m)) {
        $h = strtolower($m[1]);
        if (in_array($h, ["content-type", "content-length", "accept-ranges", "content-range", "content-disposition", "cache-control"], true)) {
            header($trimmed);
        }
    }
    return $len;
});
curl_exec($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
fclose($out);