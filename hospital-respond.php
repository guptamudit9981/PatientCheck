<?php

require_once "config/database.php";
require_once "includes/helpers.php";

session_start();

if (!isset($_SESSION['hospital_id'])) {
    jsonResponse(["success" => false, "message" => "Not logged in."], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(["success" => false, "message" => "Invalid request method."], 405);
}

$hospital_id = (int) $_SESSION['hospital_id'];

$request_id = (int) ($_POST['id'] ?? 0);
$action     = $_POST['action'] ?? '';

if ($request_id <= 0 || !in_array($action, ['accept', 'reject'], true)) {
    jsonResponse(["success" => false, "message" => "Invalid request."], 422);
}


/*
|--------------------------------------------------------------------------
| LOOK UP THE ROW FIRST (need the emergency_code either way)
|--------------------------------------------------------------------------
*/

$lookupStmt = $pdo->prepare("
    SELECT emergency_code, status
    FROM emergency_requests
    WHERE id = ?
    AND hospital_id = ?
    LIMIT 1
");

$lookupStmt->execute([$request_id, $hospital_id]);
$row = $lookupStmt->fetch();

if (!$row) {
    jsonResponse(["success" => false, "message" => "Request not found."], 404);
}

if ($row['status'] !== 'pending') {
    jsonResponse([
        "success" => false,
        "message" => "This request has already been handled.",
        "current_status" => $row['status']
    ], 409);
}


if ($action === 'accept') {

    // Atomic: only succeeds if this row is still 'pending'.
    // If two hospitals click Accept at the same moment, only one wins.
    $acceptStmt = $pdo->prepare("
        UPDATE emergency_requests
        SET status = 'accepted',
            response_time = NOW(),
            hospital_response_note = 'Accepted by hospital.'
        WHERE id = ?
        AND hospital_id = ?
        AND status = 'pending'
    ");

    $acceptStmt->execute([$request_id, $hospital_id]);

    if ($acceptStmt->rowCount() === 0) {
        jsonResponse([
            "success" => false,
            "message" => "This request has just been handled."
        ], 409);
    }

    // Stop the broadcast to every other hospital for this emergency.
    $expireStmt = $pdo->prepare("
        UPDATE emergency_requests
        SET status = 'expired',
            response_time = NOW(),
            hospital_response_note = 'Auto-expired: another hospital accepted this emergency.'
        WHERE emergency_code = ?
        AND id != ?
        AND status = 'pending'
    ");

    $expireStmt->execute([$row['emergency_code'], $request_id]);

    jsonResponse(["success" => true, "status" => "accepted"]);
}


// action === 'reject'

$rejectStmt = $pdo->prepare("
    UPDATE emergency_requests
    SET status = 'rejected',
        response_time = NOW(),
        hospital_response_note = 'Rejected by hospital.'
    WHERE id = ?
    AND hospital_id = ?
    AND status = 'pending'
");

$rejectStmt->execute([$request_id, $hospital_id]);

if ($rejectStmt->rowCount() === 0) {
    jsonResponse([
        "success" => false,
        "message" => "This request has just been handled."
    ], 409);
}

jsonResponse(["success" => true, "status" => "rejected"]);
