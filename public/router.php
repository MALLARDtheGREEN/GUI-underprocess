<?php
declare(strict_types=1);

header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'");
require_once dirname(__DIR__) . '/backend/api.php';

$port = getenv('DETECTOR_PORT') ?: '8765';
$hosts = ["127.0.0.1:$port", "localhost:$port"];
$origin = $_SERVER['HTTP_ORIGIN'] ?? null;
if (!in_array($_SERVER['HTTP_HOST'] ?? '', $hosts, true)
    || ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site'
    || ($origin !== null && !in_array($origin, array_map(fn($host) => 'http://' . $host, $hosts), true))) {
    respond(['message' => 'Only same-origin local requests are allowed.'], 403);
}
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
try {
    if (str_starts_with($path, '/api/')) handle_api($path);
    $assets = ['/' => ['index.html', 'text/html'], '/app.js' => ['app.js', 'text/javascript'],
        '/validation.js' => ['validation.js', 'text/javascript'], '/workflow.js' => ['workflow.js', 'text/javascript'],
        '/styles.css' => ['styles.css', 'text/css'],
        '/vendor/quarcBaseLayout.css' => ['vendor/quarcBaseLayout.css', 'text/css'],
        '/vendor/quarcGUI.css' => ['vendor/quarcGUI.css', 'text/css']];
    if (!isset($assets[$path])) respond(['message' => 'Not found.'], 404);
    if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) respond(['message' => 'Method not allowed.'], 405);
    [$file, $type] = $assets[$path];
    header('Content-Type: ' . $type . '; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') readfile(__DIR__ . '/' . $file);
} catch (Throwable $error) {
    error_log((string)$error);
    respond(['message' => 'The service encountered an internal error. Check the server log.'], 500);
}
