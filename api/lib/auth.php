<?php

require_once __DIR__ . '/jwt.php';

function api_require_employee(mysqli $conn): array {
    $token = api_bearer_token();
    if ($token === '') api_error('Missing Authorization Bearer token.', 401);

    $payload = jwt_verify($token, api_jwt_secret());
    if (!$payload) api_error('Invalid or expired token.', 401);
    if (($payload['role'] ?? '') !== 'employee') api_error('Forbidden.', 403);

    $employeeId = (int)($payload['sub'] ?? 0);
    if ($employeeId <= 0) api_error('Invalid token subject.', 401);

    $stmt = $conn->prepare("SELECT id, name, mobile_number, status FROM employees WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $res = $stmt->get_result();
    $employee = $res->fetch_assoc();
    $stmt->close();

    if (!$employee || ($employee['status'] ?? '') !== 'active') {
        api_error('Employee not found or inactive.', 401);
    }

    return $employee;
}

