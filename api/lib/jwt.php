<?php

function b64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function b64url_decode(string $data): string {
    $remainder = strlen($data) % 4;
    if ($remainder) $data .= str_repeat('=', 4 - $remainder);
    $decoded = base64_decode(strtr($data, '-_', '+/'), true);
    return $decoded === false ? '' : $decoded;
}

function jwt_sign(array $payload, string $secret, int $ttlSeconds = 3600): string {
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $now = time();
    $payload = array_merge($payload, [
        'iat' => $now,
        'exp' => $now + $ttlSeconds,
    ]);

    $h = b64url_encode(json_encode($header));
    $p = b64url_encode(json_encode($payload));
    $sig = b64url_encode(hash_hmac('sha256', $h . '.' . $p, $secret, true));
    return $h . '.' . $p . '.' . $sig;
}

function jwt_verify(string $token, string $secret): array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return [];
    [$h, $p, $s] = $parts;

    $expected = b64url_encode(hash_hmac('sha256', $h . '.' . $p, $secret, true));
    if (!hash_equals($expected, $s)) return [];

    $payloadJson = b64url_decode($p);
    $payload = json_decode($payloadJson, true);
    if (!is_array($payload)) return [];
    if (isset($payload['exp']) && time() > (int)$payload['exp']) return [];
    return $payload;
}

