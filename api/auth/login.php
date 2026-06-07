<?php
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_error('Method not allowed.', 405);

$body = api_read_json_body();
$mobile = trim((string)($body['mobile_number'] ?? ''));
$password = (string)($body['password'] ?? '');

if ($mobile === '' || $password === '') {
    api_error('mobile_number and password are required.', 422);
}

$stmt = $conn->prepare("SELECT id, name, mobile_number, password, status FROM employees WHERE mobile_number = ? LIMIT 1");
$stmt->bind_param("s", $mobile);
$stmt->execute();
$res = $stmt->get_result();
$employee = $res->fetch_assoc();
$stmt->close();

if (!$employee || !password_verify($password, $employee['password'] ?? '')) {
    api_error('Invalid credentials.', 401);
}
if (($employee['status'] ?? '') !== 'active') {
    api_error('Account inactive.', 403);
}

$accessToken = jwt_sign([
    'sub' => (int)$employee['id'],
    'role' => 'employee',
    'name' => (string)$employee['name'],
], api_jwt_secret(), 60 * 60 * 24 * 365); // 90 days

api_ok([
    'access_token' => $accessToken,
    'token_type' => 'Bearer',
    'expires_in' => 60 * 60 * 24 * 365,
    'employee' => [
        'id' => (int)$employee['id'],
        'name' => (string)$employee['name'],
        'mobile_number' => (string)$employee['mobile_number'],
    ],
]);

