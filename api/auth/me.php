<?php
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') api_error('Method not allowed.', 405);

$employee = api_require_employee($conn);

api_ok([
    'employee' => [
        'id' => (int)$employee['id'],
        'name' => (string)$employee['name'],
        'mobile_number' => (string)$employee['mobile_number'],
    ],
]);

