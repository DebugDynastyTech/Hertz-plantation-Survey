<?php
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') api_error('Method not allowed.', 405);

$employee = api_require_employee($conn);
$employeeId = (int)$employee['id'];

$limit = (int)($_GET['limit'] ?? 20);
if ($limit <= 0) $limit = 20;
if ($limit > 100) $limit = 100;

$cursor = (int)($_GET['cursor'] ?? 0); // fetch entries with id < cursor (simple pagination)

if ($cursor > 0) {
    $stmt = $conn->prepare("
        SELECT id, reference_id, monitoring_date, village_name, no_of_rows, plants_in_one_row,
               species_data, protection_wall_data, water_facility, natural_species_data, location_data,
               reason, created_at
        FROM data_entry
        WHERE employee_id = ? AND id < ?
        ORDER BY id DESC
        LIMIT ?
    ");
    $stmt->bind_param("iii", $employeeId, $cursor, $limit);
} else {
    $stmt = $conn->prepare("
        SELECT id, reference_id, monitoring_date, village_name, no_of_rows, plants_in_one_row,
               species_data, protection_wall_data, water_facility, natural_species_data, location_data,
               reason, created_at
        FROM data_entry
        WHERE employee_id = ?
        ORDER BY id DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $employeeId, $limit);
}

$stmt->execute();
$res = $stmt->get_result();
$rows = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$items = [];
$nextCursor = null;
foreach ($rows as $r) {
    $id = (int)$r['id'];
    $items[] = [
        'id' => $id,
        'reference_id' => (string)$r['reference_id'],
        'monitoring_date' => (string)$r['monitoring_date'],
        'village_name' => (string)$r['village_name'],
        'no_of_rows' => (int)$r['no_of_rows'],
        'plants_in_one_row' => (int)$r['plants_in_one_row'],
        'species' => json_decode($r['species_data'] ?? '[]', true) ?: [],
        'protection_wall' => json_decode($r['protection_wall_data'] ?? '[]', true) ?: [],
        'water_facility' => (string)($r['water_facility'] ?? ''),
        'natural_species' => json_decode($r['natural_species_data'] ?? '[]', true) ?: [],
        'locations' => json_decode($r['location_data'] ?? '[]', true) ?: [],
        'reason' => (string)($r['reason'] ?? ''),
        'created_at' => (string)($r['created_at'] ?? ''),
    ];
    $nextCursor = $id;
}

api_ok([
    'items' => $items,
    'next_cursor' => $nextCursor,
]);

