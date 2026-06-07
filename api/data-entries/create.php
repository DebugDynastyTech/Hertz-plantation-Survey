<?php
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_error('Method not allowed.', 405);

$employee = api_require_employee($conn);
$employeeId = (int)$employee['id'];

$idempotencyKey = trim($_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? '');
$body = api_read_json_body();

$referenceId = trim((string)($body['reference_id'] ?? ''));
$monitoringDate = trim((string)($body['monitoring_date'] ?? ''));
$villageName = trim((string)($body['village_name'] ?? ''));
$noOfRows = (int)($body['no_of_rows'] ?? 0);
$plantsInOneRow = (int)($body['plants_in_one_row'] ?? 0);
$waterFacility = trim((string)($body['water_facility'] ?? ''));
$reason = trim((string)($body['reason'] ?? ''));

$speciesData = $body['species'] ?? [];
$naturalSpeciesData = $body['natural_species'] ?? [];
$protectionWallData = $body['protection_wall'] ?? [];
$locationData = $body['locations'] ?? [];

if ($referenceId === '' || $monitoringDate === '' || $villageName === '') {
    api_error('reference_id, monitoring_date, village_name are required.', 422);
}

// Check duplicate reference_id
$stmt = $conn->prepare("SELECT id FROM data_entry WHERE reference_id = ? LIMIT 1");
$stmt->bind_param("s", $referenceId);
$stmt->execute();
$result = $stmt->get_result();
$existing = $result->fetch_assoc();
$stmt->close();

if ($existing) {
    api_error("Reference ID already exists. Please edit the reference ID and try again.", 409);
}

// Optional idempotency: if client retries, return existing entry id
if ($idempotencyKey !== '') {
    $stmt = $conn->prepare("SELECT id FROM data_entry WHERE employee_id = ? AND reference_id = ? AND monitoring_date = ? LIMIT 1");
    $stmt->bind_param("iss", $employeeId, $referenceId, $monitoringDate);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if ($row && isset($row['id'])) {
        api_ok(['data_entry_id' => (int)$row['id'], 'deduplicated' => true], 200);
    }
}

// Normalize arrays similarly to existing web form
function normalize_species($arr): array {
    if (!is_array($arr)) return [];
    $out = [];
    foreach ($arr as $item) {
        if (!is_array($item)) continue;
        $name = trim((string)($item['name'] ?? ''));
        if ($name === '') continue;
        $out[] = [
            'name' => $name,
            'planted' => (int)($item['planted'] ?? 0),
            'survival' => (int)($item['survival'] ?? 0),
            'height' => (float)($item['height'] ?? 0),
        ];
    }
    return $out;
}

function normalize_walls($arr): array {
    if (!is_array($arr)) return [];
    $out = [];
    foreach ($arr as $item) {
        if (!is_array($item)) continue;
        $name = trim((string)($item['name'] ?? ''));
        if ($name === '') continue;
        $out[] = [
            'name' => $name,
            'rmt' => (float)($item['rmt'] ?? 0),
        ];
    }
    return $out;
}

function normalize_locations($arr): array {
    if (!is_array($arr)) return [];
    $out = [];
    foreach ($arr as $item) {
        if (!is_array($item)) continue;
        $lat = (float)($item['lat'] ?? 0);
        $lng = (float)($item['long'] ?? 0);
        if ($lat == 0 || $lng == 0) continue;
        $out[] = ['lat' => $lat, 'long' => $lng];
    }
    return $out;
}

$speciesData = normalize_species($speciesData);
$naturalSpeciesData = normalize_species($naturalSpeciesData);
$protectionWallData = normalize_walls($protectionWallData);
$locationData = normalize_locations($locationData);

$sql = "INSERT INTO data_entry (
    employee_id, reference_id, monitoring_date, village_name,
    no_of_rows, plants_in_one_row,
    species_data, protection_wall_data, water_facility,
    natural_species_data, location_data, reason
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$speciesJson = json_encode($speciesData);
$wallsJson = json_encode($protectionWallData);
$naturalJson = json_encode($naturalSpeciesData);
$locJson = json_encode($locationData);

$stmt->bind_param(
    "isssiissssss",
    $employeeId,
    $referenceId,
    $monitoringDate,
    $villageName,
    $noOfRows,
    $plantsInOneRow,
    $speciesJson,
    $wallsJson,
    $waterFacility,
    $naturalJson,
    $locJson,
    $reason
);

$stmt->execute();
$newId = $stmt->insert_id;
$stmt->close();

api_ok(['data_entry_id' => (int)$newId], 201);