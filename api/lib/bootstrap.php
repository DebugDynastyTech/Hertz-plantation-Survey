<?php

// CORS (tighten in production by setting HERTZ_API_CORS_ORIGINS)
$originsEnv = getenv('HERTZ_API_CORS_ORIGINS') ?: '*';
header('Access-Control-Allow-Origin: ' . $originsEnv);
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Idempotency-Key');
header('Access-Control-Allow-Credentials: false');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/response.php';

// DB connection (reuse existing config)
require_once __DIR__ . '/../conn/connection.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

function api_jwt_secret(): string {
    $secret = getenv('HERTZ_API_JWT_SECRET');
    if ($secret && trim($secret) !== '') return $secret;
    // Dev fallback; set HERTZ_API_JWT_SECRET in production.
    return 'dev-insecure-change-me';
}

function api_bearer_token(): string {
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!$hdr) return '';
    if (stripos($hdr, 'Bearer ') !== 0) return '';
    return trim(substr($hdr, 7));
}

