<?php

function api_json($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_error(string $message, int $status = 400, array $extra = []): void {
    api_json(array_merge([
        'ok' => false,
        'error' => $message,
    ], $extra), $status);
}

function api_ok(array $data = [], int $status = 200): void {
    api_json(array_merge(['ok' => true], $data), $status);
}

function api_read_json_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') return [];
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        api_error('Invalid JSON body.', 400);
    }
    return $decoded;
}

