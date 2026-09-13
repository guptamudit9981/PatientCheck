<?php

require_once "config/database.php";
require_once "includes/helpers.php";

session_start();

if (!isset($_SESSION['patient_id'])) {
    jsonResponse(["success" => false, "message" => "Not logged in."], 401);
}

$patient_id     = (int) $_SESSION['patient_id'];
$emergency_code = trim($_GET['code'] ?? '');

if ($emergency_code === '') {
    jsonResponse(["success" => false, "message" => "Missing emergency code."], 422);
}

$stmt = $pdo->prepare("
    SELECT
        er.id,
        er.hospital_id,
        er.status,
        er.response_time,
        er.hospital_response_note,
        h.hospital_name,
        h.phone AS hospital_phone,
        h.address AS hospital_address,
        h.city AS hospital_city
    FROM emergency_requests er
    INNER JOIN hospitals h
        ON h.id = er.hospital_id
    WHERE er.emergency_code = ?
    AND er.patient_id = ?
");

$stmt->execute([$emergency_code, $patient_id]);
$rows = $stmt->fetchAll();

if (empty($rows)) {
    jsonResponse(["success" => false, "message" => "Emergency request not found."], 404);
}

$accepted  = null;
$pending   = 0;
$rejected  = 0;
$cancelled = 0;
$completed = 0;

foreach ($rows as $row) {

    switch ($row['status']) {

        case 'accepted':
            $accepted = $row;
            break;

        case 'pending':
            $pending++;
            break;

        case 'rejected':
        case 'expired':
            $rejected++;
            break;

        case 'cancelled':
            $cancelled++;
            break;

        case 'completed':
            $completed++;
            break;
    }
}

if ($accepted) {

    jsonResponse([
        "success"         => true,
        "overall_status"  => "accepted",
        "hospital_name"   => $accepted['hospital_name'],
        "hospital_phone"  => $accepted['hospital_phone'],
        "hospital_address" => trim(
            ($accepted['hospital_address'] ?? '') .
            (
                $accepted['hospital_city']
                    ? ", " . $accepted['hospital_city']
                    : ""
            )
        ),
        "responded_at"    => $accepted['response_time']
    ]);
}

if ($completed > 0) {
    jsonResponse(["success" => true, "overall_status" => "completed"]);
}

if ($cancelled > 0) {
    jsonResponse(["success" => true, "overall_status" => "cancelled"]);
}

if ($pending > 0) {
    jsonResponse([
        "success"        => true,
        "overall_status" => "pending",
        "pending_count"  => $pending,
        "total_count"    => count($rows)
    ]);
}

// Nothing accepted, nothing pending -> everyone rejected/expired
jsonResponse(["success" => true, "overall_status" => "all_rejected"]);
