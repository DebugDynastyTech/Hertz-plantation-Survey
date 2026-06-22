<?php
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/auth.php';

$baseUrl = "https://hertzinsp.co.in/plantation_survey/api/";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed.', 405);
}

$employee = api_require_employee($conn);

$referenceId = $_POST['reference_id'] ?? '';

if (!$referenceId) {
    api_error('reference_id required', 422);
}

$latitudes = $_POST['latitudes'] ?? [];
$longitudes = $_POST['longitudes'] ?? [];

$imagesData = [];

$uploadDir = __DIR__ . '/../uploads/' . $referenceId . '/';

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!empty($_FILES['images'])) {

    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {

        if (!is_uploaded_file($tmpName)) continue;

        $originalName = $_FILES['images']['name'][$key];
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);

        $fileName = uniqid("img_", true) . "." . $extension;

        $destination = $uploadDir . $fileName;

        if (move_uploaded_file($tmpName, $destination)) {

            $relativePath = $baseUrl . "/uploads/" . $referenceId . "/" . $fileName;

            $lat = isset($latitudes[$key]) ? (float)$latitudes[$key] : 0;
            $lng = isset($longitudes[$key]) ? (float)$longitudes[$key] : 0;

            $imagesData[] = [
                "image_path" => $relativePath,
                "lat" => $lat,
                "long" => $lng
            ];
        }
    }
}

if (!empty($imagesData)) {

    $imagesJson = json_encode($imagesData);

    $stmt = $conn->prepare("UPDATE data_entry SET images = ? WHERE reference_id = ?");
    $stmt->bind_param("ss", $imagesJson, $referenceId);
    $stmt->execute();
    $stmt->close();
}

api_ok([
    "uploaded" => true,
    "images" => $imagesData
]);