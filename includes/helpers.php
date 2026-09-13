<?php

/*
|--------------------------------------------------------------------------
| SHARED HELPER FUNCTIONS
|--------------------------------------------------------------------------
| Used by hospital-dashboard.php and the emergency AJAX endpoints.
| (patient-dashboard.php keeps its own copies of similar functions,
| so nothing here changes existing patient pages.)
*/

function safe($value)
{
    return htmlspecialchars(
        $value ?? "—",
        ENT_QUOTES,
        "UTF-8"
    );
}


function calculateAge($date_of_birth)
{
    if (!$date_of_birth) {
        return "—";
    }

    $dob = new DateTime($date_of_birth);
    $today = new DateTime();

    return $today->diff($dob)->y;
}


function initials($name)
{
    $words = explode(" ", trim((string) $name));

    $result = "";

    foreach ($words as $word) {

        if ($word !== "") {
            $result .= strtoupper(substr($word, 0, 1));
        }

        if (strlen($result) >= 2) {
            break;
        }
    }

    return $result ?: "?";
}


function formatDateTime($date)
{
    if (!$date) {
        return "—";
    }

    return date("d M Y, h:i A", strtotime($date));
}


/**
 * Human readable "x seconds/minutes ago" for a MySQL datetime string.
 */
function timeAgo($datetime)
{
    if (!$datetime) {
        return "—";
    }

    $diff = time() - strtotime($datetime);

    if ($diff < 5) {
        return "just now";
    }

    if ($diff < 60) {
        return $diff . "s ago";
    }

    if ($diff < 3600) {
        return floor($diff / 60) . "m ago";
    }

    if ($diff < 86400) {
        return floor($diff / 3600) . "h ago";
    }

    return floor($diff / 86400) . "d ago";
}


/**
 * Generate a unique emergency broadcast code, e.g. ER-20260913-142207-4F9A2C
 */
function generateEmergencyCode()
{
    return "ER-" .
        date("Ymd-His") .
        "-" .
        strtoupper(bin2hex(random_bytes(3)));
}


/**
 * Send a JSON response and stop execution.
 */
function jsonResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);
    header("Content-Type: application/json");
    echo json_encode($data);
    exit;
}
