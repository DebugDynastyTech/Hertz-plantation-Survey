<?php
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') api_error('Method not allowed.', 405);

// api_require_employee($conn);

function fetch_column(mysqli $conn, string $sql, string $col): array {
    $result = $conn->query($sql);
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $out = [];
    foreach ($rows as $r) {
        if (isset($r[$col])) $out[] = (string)$r[$col];
    }
    return $out;
}

api_ok([
    'villages' => fetch_column($conn, "SELECT village_name FROM village ORDER BY village_name ASC", 'village_name'),
    'species' => fetch_column($conn, "SELECT species_name FROM species ORDER BY species_name ASC", 'species_name'),
    'natural_species' => fetch_column($conn, "SELECT species_name FROM natural_species ORDER BY species_name ASC", 'species_name'),
    'protection_walls' => fetch_column($conn, "SELECT wall_name FROM protection_wall ORDER BY wall_name ASC", 'wall_name'),
    'reasons' => fetch_column($conn, "SELECT reason FROM reasons ORDER BY reason ASC", 'reason'),
]);

