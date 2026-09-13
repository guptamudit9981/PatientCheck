<?php

require_once "config/database.php";
require_once "includes/helpers.php";

session_start();

if (!isset($_SESSION['patient_id'])) {
    jsonResponse(["success" => false, "message" => "Not logged in."], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(["success" => false, "message" => "Invalid request method."], 405);
}

$patient_id = (int) $_SESSION['patient_id'];

$emergency_code = trim($_POST['emergency_code'] ?? '');
$latitude       = $_POST['latitude']  ?? null;
$longitude      = $_POST['longitude'] ?? null;
$accuracy       = $_POST['accuracy']  ?? null;

if (
    $emergency_code === '' ||
    $latitude === null ||
    $longitude === null ||
    !is_numeric($latitude) ||
    !is_numeric($longitude)
) {
    jsonResponse(["success" => false, "message" => "Missing or invalid data."], 422);
}

$stmt = $pdo->prepare("
    UPDATE emergency_requests
    SET latitude = ?,
        longitude = ?,
        location_accuracy = ?,
        location_updated_at = NOW()
    WHERE emergency_code = ?
    AND patient_id = ?
    AND status IN ('pending', 'accepted')
");

$stmt->execute([
    (float) $latitude,
    (float) $longitude,
    is_numeric($accuracy) ? (float) $accuracy : null,
    $emergency_code,
    $patient_id
]);

jsonResponse([
    "success" => true,
    "updated" => $stmt->rowCount()
]);
