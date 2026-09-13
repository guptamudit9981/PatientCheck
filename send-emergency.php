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

$latitude  = $_POST['latitude']  ?? null;
$longitude = $_POST['longitude'] ?? null;
$accuracy  = $_POST['accuracy']  ?? null;

if (
    $latitude === null ||
    $longitude === null ||
    !is_numeric($latitude) ||
    !is_numeric($longitude)
) {
    jsonResponse(["success" => false, "message" => "Location was not provided."], 422);
}

$latitude  = (float) $latitude;
$longitude = (float) $longitude;
$accuracy  = is_numeric($accuracy) ? (float) $accuracy : null;


/*
|--------------------------------------------------------------------------
| IF THIS PATIENT ALREADY HAS AN ACTIVE (PENDING/ACCEPTED) EMERGENCY,
| JUST RETURN THAT ONE INSTEAD OF CREATING A DUPLICATE BROADCAST
|--------------------------------------------------------------------------
*/

$activeStmt = $pdo->prepare("
    SELECT emergency_code
    FROM emergency_requests
    WHERE patient_id = ?
    AND status IN ('pending', 'accepted')
    ORDER BY created_at DESC
    LIMIT 1
");

$activeStmt->execute([$patient_id]);
$active = $activeStmt->fetch();

if ($active) {

    // Still update the location so the already-open request stays fresh
    $updateStmt = $pdo->prepare("
        UPDATE emergency_requests
        SET latitude = ?,
            longitude = ?,
            location_accuracy = ?,
            location_updated_at = NOW()
        WHERE emergency_code = ?
        AND patient_id = ?
    ");

    $updateStmt->execute([
        $latitude,
        $longitude,
        $accuracy,
        $active['emergency_code'],
        $patient_id
    ]);

    jsonResponse([
        "success"        => true,
        "emergency_code" => $active['emergency_code'],
        "reused"         => true
    ]);
}


/*
|--------------------------------------------------------------------------
| GET PATIENT (for the location text snapshot)
|--------------------------------------------------------------------------
*/

$patientStmt = $pdo->prepare("SELECT full_name FROM patients WHERE id = ? LIMIT 1");
$patientStmt->execute([$patient_id]);
$patient = $patientStmt->fetch();

if (!$patient) {
    jsonResponse(["success" => false, "message" => "Patient not found."], 404);
}


/*
|--------------------------------------------------------------------------
| GET ALL ACTIVE / EMERGENCY-READY HOSPITALS
|--------------------------------------------------------------------------
*/

$hospitalsStmt = $pdo->prepare("
    SELECT id
    FROM hospitals
    WHERE status = 'active'
    AND emergency_available = 1
");

$hospitalsStmt->execute();
$hospitals = $hospitalsStmt->fetchAll();

if (empty($hospitals)) {
    jsonResponse([
        "success" => false,
        "message" => "No hospitals are currently available to receive emergency requests. Please call your local emergency number immediately."
    ], 503);
}


/*
|--------------------------------------------------------------------------
| CREATE THE BROADCAST
|--------------------------------------------------------------------------
*/

$emergency_code  = generateEmergencyCode();
$patient_location = sprintf("Lat %.6f, Lng %.6f", $latitude, $longitude);

$insertStmt = $pdo->prepare("
    INSERT INTO emergency_requests (
        emergency_code,
        hospital_request_code,
        request_code,
        patient_id,
        hospital_id,
        emergency_type,
        emergency_description,
        patient_location,
        latitude,
        longitude,
        location_accuracy,
        location_updated_at,
        request_time,
        status,
        created_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 'pending', NOW()
    )
");

foreach ($hospitals as $hospital) {

    $hospital_id = (int) $hospital['id'];
    $hospital_request_code = $emergency_code . "-H" . $hospital_id;

    $insertStmt->execute([
        $emergency_code,
        $hospital_request_code,
        $hospital_request_code,
        $patient_id,
        $hospital_id,
        "Medical Emergency",
        "Emergency SOS request from patient.",
        $patient_location,
        $latitude,
        $longitude,
        $accuracy
    ]);
}

jsonResponse([
    "success"        => true,
    "emergency_code" => $emergency_code,
    "hospital_count" => count($hospitals)
]);
