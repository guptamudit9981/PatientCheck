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

$patient_id     = (int) $_SESSION['patient_id'];
$emergency_code = trim($_POST['emergency_code'] ?? '');

if ($emergency_code === '') {
    jsonResponse(["success" => false, "message" => "Missing emergency code."], 422);
}

// Any hospital that hasn't responded yet -> cancelled (no longer shown to them)
$cancelStmt = $pdo->prepare("
    UPDATE emergency_requests
    SET status = 'cancelled',
        response_time = NOW()
    WHERE emergency_code = ?
    AND patient_id = ?
    AND status = 'pending'
");

$cancelStmt->execute([$emergency_code, $patient_id]);

// A hospital that already accepted -> mark completed (patient says they're okay / handled)
$completeStmt = $pdo->prepare("
    UPDATE emergency_requests
    SET status = 'completed',
        response_time = COALESCE(response_time, NOW())
    WHERE emergency_code = ?
    AND patient_id = ?
    AND status = 'accepted'
");

$completeStmt->execute([$emergency_code, $patient_id]);

jsonResponse(["success" => true]);
