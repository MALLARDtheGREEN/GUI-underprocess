<?php
declare(strict_types=1);
require_once __DIR__ . '/parameters.php';
require_once __DIR__ . '/detector.php';
require_once __DIR__ . '/privileged.php';

function respond(array $body, int $status = 200): never {
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body, JSON_THROW_ON_ERROR);
    exit;
}

function handle_api(string $path): never {
    $runtime = getenv('DETECTOR_RUNTIME_DIR') ?: dirname(__DIR__) . '/runtime';
    $configDir = $runtime . '/configurations';
    $sessions = $runtime . '/sessions';
    if (!is_dir($sessions) && !mkdir($sessions, 0700, true) && !is_dir($sessions)) {
        throw new RuntimeException('Cannot create local session storage.');
    }
    session_save_path($sessions);
    session_name('detector_session');
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict', 'use_strict_mode' => true]);
    if (!isset($_SESSION['token'])) $_SESSION['token'] = bin2hex(random_bytes(32));
    $token = $_SESSION['token'];
    $method = $_SERVER['REQUEST_METHOD'];

    if ($path === '/api/bootstrap' && $method === 'GET') {
        $parameters = null;
        $warning = null;
        $saved = $configDir . '/parameters.json';
        if (is_file($saved)) {
            try {
                $result = validate_parameters(json_decode(file_get_contents($saved), true, 512, JSON_THROW_ON_ERROR));
                if ($result['errors']) throw new RuntimeException('Invalid saved parameters.');
                $parameters = $result['values'];
            } catch (Throwable $error) {
                $warning = 'Saved configuration is invalid. Defaults are shown; Setup will replace the saved configuration.';
            }
        }
        respond(['token' => $token, 'schema' => parameter_schema(), 'parameters' => $parameters,
            'warning' => $warning, 'hardware_connected' => false, 'python_connected' => false,
            'mode' => detector_mode()]);
    }
    if (!in_array($path, ['/api/setup', '/api/acquire', '/api/stop', '/api/system/sudo'], true)) {
        respond(['message' => 'Unknown API route.'], 404);
    }
    if ($method !== 'POST') respond(['message' => 'Use POST for this operation.'], 405);
    if (!hash_equals($token, $_SERVER['HTTP_X_DETECTOR_TOKEN'] ?? '')) {
        respond(['message' => 'Session expired or invalid. Reload this page.'], 403);
    }
    if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') {
        respond(['message' => 'Expected application/json.'], 415);
    }
    $raw = file_get_contents('php://input', false, null, 0, 32769);
    if (strlen($raw) > 32768) respond(['message' => 'Request is too large.'], 413);
    try {
        $body = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
    } catch (JsonException $error) {
        respond(['message' => 'Invalid JSON request.'], 400);
    }
    if ($path === '/api/system/sudo') {
        if (!is_array($body) || array_keys($body) !== ['action'] || $body['action'] !== 'identity') {
            respond(['message' => 'Only the fixed identity action is allowed.'], 400);
        }
        respond(sudo_identity());
    }
    if ($path === '/api/stop') {
        if (detector_mode() !== 'preview') respond(['message' => 'No detector stop interface is configured.'], 501);
        $_SESSION['running'] = false;
        respond(['stopped' => true, 'mode' => 'preview', 'message' => 'Preview acquisition stopped.']);
    }
    if ($path === '/api/setup') unset($_SESSION['prepared_signature'], $_SESSION['ready_at']);
    $result = validate_parameters($body);
    if ($result['errors']) respond(['message' => 'Review the highlighted parameters before continuing.', 'errors' => $result['errors']], 422);
    if ($path === '/api/setup') {
        if (!empty($_SESSION['running'])) respond(['message' => 'Stop acquisition before changing the detector configuration.'], 409);
        save_parameters($result['values'], $configDir);
        $_SESSION['prepared_signature'] = detector_signature($result['values']);
        $_SESSION['ready_at'] = microtime(true) + 1;
        respond(['parameters' => $result['values'], 'ready_in_ms' => 1000,
            'message' => 'Configuration prepared. Acquire is ready.', 'mode' => detector_mode()]);
    }
    if (($_SESSION['prepared_signature'] ?? '') !== detector_signature($result['values'])
        || microtime(true) < ($_SESSION['ready_at'] ?? PHP_FLOAT_MAX)) {
        respond(['message' => 'Detectors are not ready. Configure the parameters and click Setup first.', 'started' => false], 409);
    }
    if (!empty($_SESSION['running'])) respond(['message' => 'Acquisition is already running.', 'started' => false], 409);
    $result = acquire_detector($result['values']);
    if ($result['started']) $_SESSION['running'] = true;
    respond($result, $result['started'] ? 202 : 501);
}
