<?php

require_once "config/database.php";
require_once "includes/helpers.php";

session_start();

if (!isset($_SESSION['hospital_id'])) {
    jsonResponse(["success" => false, "message" => "Not logged in."], 401);
}

$hospital_id = (int) $_SESSION['hospital_id'];


/*
|--------------------------------------------------------------------------
| PENDING REQUESTS (awaiting this hospital's response)
|--------------------------------------------------------------------------
*/

$pendingStmt = $pdo->prepare("
    SELECT
        er.id,
        er.emergency_code,
        er.emergency_type,
        er.emergency_description,
        er.patient_location,
        er.latitude,
        er.longitude,
        er.location_accuracy,
        er.location_updated_at,
        er.request_time,
        p.id AS patient_id,
        p.full_name,
        p.patient_code,
        p.date_of_birth,
        p.gender,
        p.blood_group,
        p.phone,
        p.allergies,
        p.medical_conditions,
        p.emergency_contact_name,
        p.emergency_contact_phone
    FROM emergency_requests er
    INNER JOIN patients p
        ON p.id = er.patient_id
    WHERE er.hospital_id = ?
    AND er.status = 'pending'
    ORDER BY er.request_time ASC
");

$pendingStmt->execute([$hospital_id]);
$pendingRows = $pendingStmt->fetchAll();


/*
|--------------------------------------------------------------------------
| REQUESTS THIS HOSPITAL HAS ACCEPTED AND NOT YET COMPLETED
|--------------------------------------------------------------------------
*/

$acceptedStmt = $pdo->prepare("
    SELECT
        er.id,
        er.emergency_code,
        er.emergency_type,
        er.emergency_description,
        er.patient_location,
        er.latitude,
        er.longitude,
        er.location_accuracy,
        er.location_updated_at,
        er.request_time,
        er.response_time,
        p.id AS patient_id,
        p.full_name,
        p.patient_code,
        p.date_of_birth,
        p.gender,
        p.blood_group,
        p.phone,
        p.allergies,
        p.medical_conditions,
        p.emergency_contact_name,
        p.emergency_contact_phone
    FROM emergency_requests er
    INNER JOIN patients p
        ON p.id = er.patient_id
    WHERE er.hospital_id = ?
    AND er.status = 'accepted'
    ORDER BY er.response_time DESC
");

$acceptedStmt->execute([$hospital_id]);
$acceptedRows = $acceptedStmt->fetchAll();


function formatRequest($row)
{
    return [
        "id"                     => (int) $row['id'],
        "emergency_code"         => $row['emergency_code'],
        "emergency_type"         => $row['emergency_type'],
        "emergency_description"  => $row['emergency_description'],
        "latitude"               => $row['latitude'] !== null ? (float) $row['latitude'] : null,
        "longitude"              => $row['longitude'] !== null ? (float) $row['longitude'] : null,
        "location_accuracy"      => $row['location_accuracy'] !== null ? (float) $row['location_accuracy'] : null,
        "location_updated_ago"   => timeAgo($row['location_updated_at']),
        "requested_ago"          => timeAgo($row['request_time']),
        "patient_id"             => (int) $row['patient_id'],
        "patient_name"           => $row['full_name'],
        "patient_code"           => $row['patient_code'],
        "patient_age"            => calculateAge($row['date_of_birth']),
        "patient_gender"         => $row['gender'] ? ucfirst($row['gender']) : "—",
        "blood_group"            => $row['blood_group'] ?: "—",
        "phone"                  => $row['phone'] ?: "—",
        "allergies"              => $row['allergies'] ?: "No known allergies.",
        "medical_conditions"     => $row['medical_conditions'] ?: "None recorded.",
        "emergency_contact_name"  => $row['emergency_contact_name'] ?: "—",
        "emergency_contact_phone" => $row['emergency_contact_phone'] ?: "",
        "initials"               => initials($row['full_name'])
    ];
}

jsonResponse([
    "success"  => true,
    "pending"  => array_map('formatRequest', $pendingRows),
    "accepted" => array_map('formatRequest', $acceptedRows)
]);
