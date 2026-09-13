<?php

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
| The patient must be logged in via login.php (Patient ID + mobile
| number + date of birth). If there's no active session, send them
| back to the login page instead of showing any dashboard data.
*/

session_start();

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit;
}

$patient_id = (int) $_SESSION['patient_id'];


/*
|--------------------------------------------------------------------------
| GET PATIENT
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM patients
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$patient_id]);

$patient = $stmt->fetch();

if (!$patient) {
    // Session points to a patient that no longer exists — force re-login.
    session_destroy();
    header("Location: login.php");
    exit;
}



/* =========================================================
   PATIENT MEDICAL TIMELINE
   ========================================================= */

$timeline_patient_id25 = $patient_id;

$timeline_stmt25 = $pdo->prepare("
    SELECT
        pt.id,
        pt.patient_id,
        pt.doctor_id,
        pt.hospital_name,
        pt.visit_date,
        pt.diagnosis,
        pt.treatment,
        pt.medicines,
        pt.doctor_notes,
        d.full_name AS doctor_name25,
        d.specialization AS specialization25
    FROM patient_timeline pt
    LEFT JOIN doctors d
        ON pt.doctor_id = d.id
    WHERE pt.patient_id = ?
    ORDER BY pt.visit_date DESC, pt.id DESC
");

$timeline_stmt25->execute([
    $timeline_patient_id25
]);

$patient_timeline25 = $timeline_stmt25->fetchAll();


/*
|--------------------------------------------------------------------------
| GET PATIENT'S DOCTORS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT DISTINCT
        d.*
    FROM doctors d
    INNER JOIN appointments a
        ON a.doctor_id = d.id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC
");

$stmt->execute([$patient_id]);

$doctors = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| PRIMARY / MOST RECENT DOCTOR
|--------------------------------------------------------------------------
*/

$primary_doctor = null;

if (!empty($doctors)) {
    $primary_doctor = $doctors[0];
}


/*
|--------------------------------------------------------------------------
| GET ACTIVE MEDICINES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        m.*,
        d.full_name AS doctor_name,
        d.specialization
    FROM medicines m
    LEFT JOIN doctors d
        ON m.prescribed_by = d.id
    WHERE m.patient_id = ?
    AND m.status = 'active'
    ORDER BY m.start_date DESC, m.id DESC
");

$stmt->execute([$patient_id]);

$medicines = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| GET ALL MEDICINES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        m.*,
        d.full_name AS doctor_name
    FROM medicines m
    LEFT JOIN doctors d
        ON m.prescribed_by = d.id
    WHERE m.patient_id = ?
    ORDER BY m.start_date DESC, m.id DESC
");

$stmt->execute([$patient_id]);

$all_medicines = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| UPCOMING APPOINTMENTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        a.*,
        d.full_name AS doctor_name,
        d.specialization,
        d.hospital_name
    FROM appointments a
    INNER JOIN doctors d
        ON a.doctor_id = d.id
    WHERE a.patient_id = ?
    AND a.status = 'scheduled'
    AND a.appointment_date >= NOW()
    ORDER BY a.appointment_date ASC
    LIMIT 10
");

$stmt->execute([$patient_id]);

$upcoming_appointments = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| LAST / RECENT MEETINGS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        a.*,
        d.full_name AS doctor_name,
        d.specialization
    FROM appointments a
    INNER JOIN doctors d
        ON a.doctor_id = d.id
    WHERE a.patient_id = ?
    AND a.status = 'completed'
    ORDER BY a.appointment_date DESC
    LIMIT 10
");

$stmt->execute([$patient_id]);

$recent_meetings = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| TOTAL APPOINTMENTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM appointments
    WHERE patient_id = ?
");

$stmt->execute([$patient_id]);

$total_appointments = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| COMPLETED MEETINGS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM appointments
    WHERE patient_id = ?
    AND status = 'completed'
");

$stmt->execute([$patient_id]);

$completed_meetings = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function calculateAge($date_of_birth)
{
    if (!$date_of_birth) {
        return "—";
    }

    $dob = new DateTime($date_of_birth);
    $today = new DateTime();

    return $today->diff($dob)->y;
}


function formatDate($date)
{
    if (!$date) {
        return "—";
    }

    return date("d M Y", strtotime($date));
}


function formatDateTime($date)
{
    if (!$date) {
        return "—";
    }

    return date("d M Y, h:i A", strtotime($date));
}


function initials($name)
{
    $words = explode(" ", trim($name));

    $result = "";

    foreach ($words as $word) {

        if ($word !== "") {
            $result .= strtoupper(substr($word, 0, 1));
        }

        if (strlen($result) >= 2) {
            break;
        }
    }

    return $result;
}


function safe($value)
{
    return htmlspecialchars(
        $value ?? "—",
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Patient Dashboard | PatientCheck
    </title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        :root {

            --primary: #2563eb;
            --primary-dark: #1d4ed8;

            --navy: #172554;

            --background: #f5f7fb;

            --card: #ffffff;

            --text: #172033;

            --muted: #718096;

            --border: #e7ebf2;

            --success: #16a34a;
            --success-bg: #ecfdf3;

            --warning: #d97706;
            --warning-bg: #fff7ed;

            --danger: #dc2626;
            --danger-bg: #fef2f2;

            --info-bg: #eff6ff;

            --sidebar-width: 255px;
        }


        body {

            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;

            background: var(--background);

            color: var(--text);

            min-height: 100vh;
        }


        a {
            text-decoration: none;
            color: inherit;
        }


        button,
        input {
            font-family: inherit;
        }


        /* =====================================================
           SIDEBAR
        ===================================================== */

        .sidebar {

            position: fixed;

            top: 0;
            left: 0;
            bottom: 0;

            width: var(--sidebar-width);

            background: white;

            border-right: 1px solid var(--border);

            z-index: 100;

            display: flex;

            flex-direction: column;
        }


        .logo {

            height: 82px;

            display: flex;

            align-items: center;

            padding: 0 23px;

            border-bottom: 1px solid var(--border);
        }


        .logo-icon {

            width: 42px;
            height: 42px;

            background: var(--primary);

            color: white;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;

            font-weight: 800;

            margin-right: 11px;
        }


        .logo-text {

            font-size: 20px;

            font-weight: 800;

            letter-spacing: -.6px;
        }


        .logo-text span {
            color: var(--primary);
        }


        .portal-label {

            padding: 24px 23px 11px;

            font-size: 10px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 1.3px;

            color: #9aa3b2;
        }


        .nav {

            padding: 0 12px;

            flex: 1;
        }


/* =========================================================
   MEDICAL HISTORY TIMELINE - UNIQUE CSS
   ========================================================= */

.timeline-card25 {
    margin-top: 24px;
    width: 100%;
}


/* ---------------------------------------------------------
   TIMELINE HEADER
   --------------------------------------------------------- */

.timeline-card25 .card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 20px 22px;
    border-bottom: 1px solid #e5e7eb;
}

.timeline-card25 .card-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #172033;
}

.timeline-card25 .card-header span {
    font-size: 12px;
    color: #718096;
}


/* ---------------------------------------------------------
   TIMELINE MAIN AREA
   --------------------------------------------------------- */

.timeline25 {
    position: relative;
    padding: 28px 28px 28px 58px;
}


/* Vertical line */

.timeline25::before {
    content: "";
    position: absolute;

    left: 31px;
    top: 30px;
    bottom: 30px;

    width: 2px;

    background: #dbeafe;
}


/* ---------------------------------------------------------
   EACH TIMELINE ITEM
   --------------------------------------------------------- */

.timeline-item25 {
    position: relative;
    margin: 0;
    padding: 0 0 30px 0;
}

.timeline-item25:last-child {
    padding-bottom: 0;
}


/* ---------------------------------------------------------
   TIMELINE DOT
   --------------------------------------------------------- */

.timeline-dot25 {
    position: absolute;

    left: -34px;
    top: 3px;

    width: 15px;
    height: 15px;

    background: #2563eb;

    border: 3px solid #ffffff;

    border-radius: 50%;

    box-shadow: 0 0 0 3px #dbeafe;

    z-index: 2;
}


/* ---------------------------------------------------------
   DATE
   --------------------------------------------------------- */

.timeline-date25 {
    margin-bottom: 8px;

    font-size: 11px;
    font-weight: 700;

    color: #2563eb;

    letter-spacing: 0.3px;
}


/* ---------------------------------------------------------
   CONTENT CARD
   --------------------------------------------------------- */

.timeline-content25 {
    width: 100%;

    padding: 18px;

    background: #f8fafc;

    border: 1px solid #e5e7eb;

    border-radius: 12px;

    box-sizing: border-box;

    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease,
        border-color 0.2s ease;
}


/* Small hover effect */

.timeline-content25:hover {
    transform: translateY(-1px);

    border-color: #bfdbfe;

    box-shadow: 0 5px 18px rgba(15, 23, 42, 0.06);
}


/* ---------------------------------------------------------
   DOCTOR NAME
   --------------------------------------------------------- */

.timeline-doctor25 {
    margin: 0;

    font-size: 15px;
    font-weight: 700;

    color: #172033;

    line-height: 1.4;
}


/* ---------------------------------------------------------
   SPECIALIZATION
   --------------------------------------------------------- */

.timeline-specialization25 {
    display: inline-block;

    margin-top: 5px;

    padding: 4px 8px;

    font-size: 10px;
    font-weight: 700;

    color: #2563eb;

    background: #eff6ff;

    border-radius: 5px;

    text-transform: uppercase;

    letter-spacing: 0.3px;
}


/* ---------------------------------------------------------
   HOSPITAL
   --------------------------------------------------------- */

.timeline-hospital25 {
    margin-top: 9px;

    font-size: 12px;

    color: #64748b;

    line-height: 1.5;
}


/* ---------------------------------------------------------
   INFORMATION SECTIONS
   --------------------------------------------------------- */

.timeline-section25 {
    margin-top: 15px;

    padding-top: 12px;

    border-top: 1px solid #e5e7eb;
}


/* ---------------------------------------------------------
   LABEL
   --------------------------------------------------------- */

.timeline-label25 {
    display: block;

    margin-bottom: 5px;

    font-size: 10px;
    font-weight: 700;

    color: #64748b;

    text-transform: uppercase;

    letter-spacing: 0.5px;
}


/* ---------------------------------------------------------
   NORMAL TEXT
   --------------------------------------------------------- */

.timeline-text25 {
    font-size: 13px;

    line-height: 1.6;

    color: #374151;

    word-wrap: break-word;

    overflow-wrap: anywhere;
}


/* ---------------------------------------------------------
   MEDICINES
   --------------------------------------------------------- */

.timeline-medicine25 {
    color: #166534;

    font-weight: 500;
}


/* ---------------------------------------------------------
   DOCTOR NOTES
   --------------------------------------------------------- */

.timeline-notes25 {
    color: #475569;

    font-style: italic;
}


/* ---------------------------------------------------------
   EMPTY TIMELINE
   --------------------------------------------------------- */

.timeline-empty25 {
    padding: 40px 20px;

    text-align: center;

    font-size: 13px;

    color: #718096;
}


/* ---------------------------------------------------------
   MOBILE RESPONSIVE
   --------------------------------------------------------- */

@media (max-width: 760px) {

    .timeline-card25 {
        margin-top: 18px;
    }

    .timeline-card25 .card-header {
        align-items: flex-start;

        flex-direction: column;

        padding: 17px;
    }

    .timeline25 {
        padding: 24px 15px 24px 45px;
    }

    .timeline25::before {
        left: 22px;

        top: 25px;
        bottom: 25px;
    }

    .timeline-dot25 {
        left: -30px;

        width: 14px;
        height: 14px;
    }

    .timeline-content25 {
        padding: 15px;
    }

    .timeline-doctor25 {
        font-size: 14px;
    }

    .timeline-text25 {
        font-size: 12px;
    }

}


/* ---------------------------------------------------------
   VERY SMALL MOBILE SCREENS
   --------------------------------------------------------- */

@media (max-width: 480px) {

    .timeline25 {
        padding-left: 38px;
        padding-right: 10px;
    }

    .timeline25::before {
        left: 18px;
    }

    .timeline-dot25 {
        left: -26px;

        width: 13px;
        height: 13px;
    }

    .timeline-date25 {
        font-size: 10px;
    }

    .timeline-content25 {
        padding: 13px;
        border-radius: 10px;
    }

    .timeline-specialization25 {
        font-size: 9px;
    }

    .timeline-hospital25 {
        font-size: 11px;
    }

    .timeline-label25 {
        font-size: 9px;
    }

    .timeline-text25 {
        font-size: 12px;
        line-height: 1.55;
    }

}

        .nav-item {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 13px;

            margin-bottom: 4px;

            border-radius: 10px;

            color: #667085;

            font-size: 13px;

            font-weight: 600;

            transition: .2s;
        }


        .nav-item:hover {

            color: var(--primary);

            background: #eff6ff;
        }


        .nav-item.active {

            color: var(--primary);

            background: #eff6ff;
        }


        .nav-item.logout-item {

            color: var(--danger);
        }


        .nav-item.logout-item:hover {

            color: var(--danger);

            background: var(--danger-bg);
        }


        .nav-icon {

            width: 22px;

            text-align: center;

            font-size: 17px;
        }


        .sidebar-profile {

            padding: 18px;

            border-top: 1px solid var(--border);
        }


        .mini-profile {

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .avatar {

            width: 42px;
            height: 42px;

            border-radius: 12px;

            background: #dbeafe;

            color: var(--primary);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 13px;

            font-weight: 800;

            flex-shrink: 0;
        }


        .mini-profile strong {

            display: block;

            font-size: 12px;

            margin-bottom: 3px;
        }


        .mini-profile span {

            color: var(--muted);

            font-size: 10px;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .main {

            margin-left: var(--sidebar-width);

            min-height: 100vh;
        }


        .topbar {

            height: 82px;

            background: white;

            border-bottom: 1px solid var(--border);

            padding: 0 32px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            position: sticky;

            top: 0;

            z-index: 50;
        }


        .topbar h1 {

            font-size: 20px;

            letter-spacing: -.5px;

            margin-bottom: 4px;
        }


        .topbar p {

            color: var(--muted);

            font-size: 12px;
        }


        .top-actions {

            display: flex;

            align-items: center;

            gap: 11px;
        }


        .icon-button {

            width: 40px;
            height: 40px;

            background: white;

            border: 1px solid var(--border);

            border-radius: 10px;

            font-size: 17px;

            cursor: pointer;
        }


        .top-profile {

            display: flex;

            align-items: center;

            gap: 9px;

            padding-left: 8px;
        }


        .top-profile .avatar {

            width: 38px;
            height: 38px;

            border-radius: 11px;
        }


        .top-profile-text strong {

            display: block;

            font-size: 12px;
        }


        .top-profile-text span {

            display: block;

            color: var(--muted);

            font-size: 10px;

            margin-top: 2px;
        }


        .content {

            padding: 28px 32px 55px;
        }


        /* =====================================================
           HERO
        ===================================================== */

        .hero {

            background: linear-gradient(
                135deg,
                #172554,
                #2563eb
            );

            color: white;

            border-radius: 18px;

            padding: 27px 29px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            position: relative;

            overflow: hidden;

            margin-bottom: 22px;
        }


        .hero::after {

            content: "";

            position: absolute;

            width: 250px;
            height: 250px;

            border-radius: 50%;

            border: 45px solid rgba(
                255,
                255,
                255,
                .05
            );

            right: -80px;

            top: -100px;
        }


        .hero-content {

            position: relative;

            z-index: 2;
        }


        .hero small {

            display: block;

            color: #bfdbfe;

            font-size: 11px;

            margin-bottom: 8px;
        }


        .hero h2 {

            font-size: 25px;

            letter-spacing: -.7px;

            margin-bottom: 7px;
        }


        .hero p {

            color: #dbeafe;

            font-size: 12px;
        }


        .patient-id {

            position: relative;

            z-index: 2;

            background: rgba(
                255,
                255,
                255,
                .12
            );

            border: 1px solid rgba(
                255,
                255,
                255,
                .16
            );

            padding: 13px 17px;

            border-radius: 11px;

            text-align: right;
        }


        .patient-id span {

            display: block;

            font-size: 10px;

            color: #bfdbfe;

            margin-bottom: 4px;
        }


        .patient-id strong {

            font-size: 13px;
        }


        /* =====================================================
           STATS
        ===================================================== */

        .stats {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 16px;

            margin-bottom: 23px;
        }


        .stat-card {

            background: white;

            border: 1px solid var(--border);

            border-radius: 14px;

            padding: 19px;

            display: flex;

            align-items: flex-start;

            justify-content: space-between;
        }


        .stat-card small {

            color: var(--muted);

            font-size: 11px;

            font-weight: 600;
        }


        .stat-card h3 {

            font-size: 25px;

            margin-top: 8px;

            letter-spacing: -1px;
        }


        .stat-icon {

            width: 41px;
            height: 41px;

            border-radius: 11px;

            background: var(--info-bg);

            color: var(--primary);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 17px;
        }


        /* =====================================================
           GRID
        ===================================================== */

        .dashboard-grid {

            display: grid;

            grid-template-columns:
                minmax(0, 1.55fr)
                minmax(280px, .85fr);

            gap: 20px;

            margin-bottom: 25px;
        }


        .card {

            background: white;

            border: 1px solid var(--border);

            border-radius: 15px;

            overflow: hidden;
        }


        .card-header {

            padding: 18px 20px;

            border-bottom: 1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;
        }


        .card-header h3 {

            font-size: 14px;
        }


        .card-header span {

            color: var(--muted);

            font-size: 10px;
        }


        .sos-button {
    width: 220px;
    height: 220px;
    border-radius: 50%;
    border: 10px solid rgba(255,255,255,.25);
    background: #dc2626;
    color: white;
    font-size: 22px;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 0 0 15px rgba(220,38,38,.12),
                0 20px 50px rgba(0,0,0,.25);
    transition: .2s;
}

.sos-button:hover {
    transform: scale(1.04);
}

.sos-button:active {
    transform: scale(.97);
}

#sosStatus {
    margin-top: 20px;
    font-weight: 700;
}

        /* =====================================================
           APPOINTMENTS
        ===================================================== */

        .appointment {

            padding: 15px 20px;

            display: flex;

            align-items: center;

            gap: 12px;

            border-bottom: 1px solid #f0f2f6;
        }


        .appointment:last-child {
            border-bottom: none;
        }


        .appointment-avatar {

            width: 41px;
            height: 41px;

            border-radius: 11px;

            background: #eff6ff;

            color: var(--primary);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 12px;

            font-weight: 800;

            flex-shrink: 0;
        }


        .appointment-main {

            flex: 1;

            min-width: 0;
        }


        .appointment-main strong {

            display: block;

            font-size: 12px;

            margin-bottom: 4px;
        }


        .appointment-main span {

            color: var(--muted);

            font-size: 10px;
        }


        .appointment-date {

            text-align: right;

            flex-shrink: 0;
        }


        .appointment-date strong {

            display: block;

            font-size: 11px;
        }


        .appointment-date span {

            color: var(--muted);

            font-size: 9px;

            display: block;

            margin-top: 2px;
        }


        .status {

            display: inline-flex;

            padding: 4px 7px;

            border-radius: 6px;

            font-size: 9px;

            font-weight: 700;

            margin-left: 5px;
        }


        .status.scheduled {

            background: var(--info-bg);

            color: var(--primary);
        }


        .status.completed {

            background: var(--success-bg);

            color: var(--success);
        }


        .status.cancelled {

            background: var(--danger-bg);

            color: var(--danger);
        }


        /* =====================================================
           HEALTH CARD
        ===================================================== */

        .health-card {

            padding: 20px;
        }


        .health-profile {

            display: flex;

            align-items: center;

            gap: 13px;

            margin-bottom: 20px;
        }


        .health-profile .avatar {

            width: 54px;
            height: 54px;

            border-radius: 15px;

            font-size: 16px;
        }


        .health-profile h3 {

            font-size: 15px;

            margin-bottom: 4px;
        }


        .health-profile p {

            color: var(--muted);

            font-size: 10px;
        }


        .health-list {

            display: grid;

            gap: 12px;
        }


        .health-row {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding-bottom: 11px;

            border-bottom: 1px solid #f0f2f6;
        }


        .health-row:last-child {
            border-bottom: none;
        }


        .health-row span {

            color: var(--muted);

            font-size: 11px;
        }


        .health-row strong {

            font-size: 11px;

            text-align: right;
        }


        .blood {

            color: var(--danger);

            font-weight: 800;
        }


        /* =====================================================
           MEDICINES
        ===================================================== */

        .medicine-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 14px;

            padding: 18px;
        }


        .medicine-card {

            border: 1px solid var(--border);

            border-radius: 12px;

            padding: 16px;

            position: relative;
        }


        .medicine-icon {

            width: 34px;
            height: 34px;

            background: #eff6ff;

            color: var(--primary);

            border-radius: 9px;

            display: flex;

            align-items: center;

            justify-content: center;

            margin-bottom: 12px;
        }


        .medicine-card h4 {

            font-size: 12px;

            margin-bottom: 7px;
        }


        .medicine-card p {

            color: var(--muted);

            font-size: 10px;

            line-height: 1.6;

            margin-bottom: 9px;
        }


        .medicine-time {

            font-size: 10px;

            color: var(--primary);

            font-weight: 700;
        }


        .medicine-status {

            position: absolute;

            top: 14px;

            right: 14px;

            font-size: 8px;

            color: var(--success);

            background: var(--success-bg);

            padding: 4px 6px;

            border-radius: 5px;

            font-weight: 700;
        }
/* =========================================
   PREMIUM FLOATING SOS BUTTON
   Existing ID/Class preserved
   ========================================= */

.sos-floating-button {
    position: fixed;
    right: 28px;
    top: 50%;
    transform: translateY(-50%);

    width: 175px;
    min-height: 68px;

    display: flex;
    align-items: center;
    gap: 12px;

    padding: 10px 18px 10px 10px;

    border: 0;
    border-radius: 22px;

    cursor: pointer;
    z-index: 99999;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #ff1515 0%,
            #d90000 50%,
            #9f0000 100%
        );

    box-shadow:
        0 12px 35px rgba(190, 0, 0, 0.38),
        0 5px 12px rgba(0, 0, 0, 0.18),
        inset 0 1px 1px rgba(255, 255, 255, 0.35);

    overflow: hidden;

    transition:
        transform 0.3s ease,
        box-shadow 0.3s ease,
        width 0.3s ease;

    font-family:
        Inter,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
}


/* =========================================
   HOVER EFFECT
   ========================================= */

.sos-floating-button:hover {
    transform: translateY(-50%) translateX(-6px);

    box-shadow:
        0 18px 45px rgba(190, 0, 0, 0.48),
        0 8px 18px rgba(0, 0, 0, 0.2),
        inset 0 1px 1px rgba(255, 255, 255, 0.4);
}


/* =========================================
   ACTIVE / CLICK EFFECT
   ========================================= */

.sos-floating-button:active {
    transform: translateY(-50%) scale(0.95);
}


/* =========================================
   SOS ICON CONTAINER
   ========================================= */

.sos-icon-wrap {
    position: relative;

    width: 48px;
    height: 48px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 15px;

    background: rgba(255, 255, 255, 0.18);

    border: 1px solid rgba(255, 255, 255, 0.28);

    box-shadow:
        inset 0 1px 2px rgba(255, 255, 255, 0.3),
        0 4px 12px rgba(90, 0, 0, 0.2);

    backdrop-filter: blur(8px);

    z-index: 3;

    animation: sosIconFloat 2s ease-in-out infinite;
}


/* =========================================
   SOS ICON
   ========================================= */

.sos-icon {
    font-size: 25px;

    display: flex;
    align-items: center;
    justify-content: center;

    filter:
        drop-shadow(0 2px 3px rgba(0, 0, 0, 0.25));
}


/* =========================================
   TEXT
   ========================================= */

.sos-text {
    position: relative;
    z-index: 3;

    display: flex;
    flex-direction: column;

    align-items: flex-start;
    justify-content: center;

    line-height: 1.05;
}


.sos-text strong {
    font-size: 14px;
    font-weight: 900;

    letter-spacing: 0.8px;

    white-space: nowrap;

    text-shadow:
        0 1px 2px rgba(0, 0, 0, 0.25);
}


.sos-text small {
    margin-top: 5px;

    font-size: 11px;
    font-weight: 700;

    letter-spacing: 2.5px;

    opacity: 0.88;

    white-space: nowrap;
}


/* =========================================
   OUTER PULSE
   ========================================= */

.sos-pulse {
    position: absolute;

    width: 58px;
    height: 58px;

    left: 5px;
    top: 5px;

    border-radius: 50%;

    border: 2px solid rgba(255, 255, 255, 0.6);

    pointer-events: none;

    animation: sosPulse 2s ease-out infinite;
}


.sos-pulse-delay {
    animation-delay: 1s;
}


/* =========================================
   SHINE ANIMATION
   ========================================= */

.sos-shine {
    position: absolute;

    top: 0;
    left: -100%;

    width: 70%;
    height: 100%;

    background:
        linear-gradient(
            90deg,
            transparent,
            rgba(255, 255, 255, 0.25),
            transparent
        );

    transform: skewX(-20deg);

    pointer-events: none;

    animation: sosShine 3.5s ease-in-out infinite;
}


/* =========================================
   ANIMATIONS
   ========================================= */

@keyframes sosPulse {

    0% {
        transform: scale(0.7);
        opacity: 0.8;
    }

    70% {
        transform: scale(1.8);
        opacity: 0;
    }

    100% {
        transform: scale(1.8);
        opacity: 0;
    }
}


@keyframes sosIconFloat {

    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-3px);
    }
}


@keyframes sosShine {

    0% {
        left: -100%;
    }

    25% {
        left: 130%;
    }

    100% {
        left: 130%;
    }
}


/* =========================================
   GLOW EFFECT
   ========================================= */

.sos-premium::after {
    content: "";

    position: absolute;

    inset: 0;

    border-radius: inherit;

    pointer-events: none;

    box-shadow:
        inset 0 0 0 1px rgba(255, 255, 255, 0.12);

    animation: sosGlow 2.5s ease-in-out infinite;
}


@keyframes sosGlow {

    0%,
    100% {
        opacity: 0.5;
    }

    50% {
        opacity: 1;
    }
}


/* =========================================
   MOBILE
   ========================================= */

@media (max-width: 768px) {

    .sos-floating-button {
        right: 16px;

        width: 64px;
        height: 64px;
        min-height: 64px;

        padding: 8px;

        border-radius: 20px;

        justify-content: center;
    }

    .sos-icon-wrap {
        width: 48px;
        height: 48px;
    }

    .sos-text {
        display: none;
    }

    .sos-floating-button:hover {
        transform: translateY(-50%);
    }
}


/* =========================================
   SMALL MOBILE
   ========================================= */

@media (max-width: 400px) {

    .sos-floating-button {
        right: 12px;

        width: 58px;
        height: 58px;
        min-height: 58px;

        border-radius: 18px;
    }

    .sos-icon-wrap {
        width: 44px;
        height: 44px;
    }

    .sos-icon {
        font-size: 22px;
    }
}

        /* =====================================================
           SOS STATUS PANEL
        ===================================================== */

        .sos-status-panel {

            position: fixed;

            right: 28px;

            bottom: 28px;

            width: 320px;

            max-width: calc(100vw - 40px);

            background: white;

            border-radius: 16px;

            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);

            border: 1px solid var(--border);

            z-index: 99998;

            overflow: hidden;

            display: none;
        }


        .sos-status-panel.visible {
            display: block;
        }


        .sos-status-header {

            padding: 14px 16px;

            display: flex;

            align-items: center;

            gap: 10px;

            color: white;

            font-weight: 800;

            font-size: 12px;

            letter-spacing: .3px;

            text-transform: uppercase;
        }


        .sos-status-header.pending {
            background: linear-gradient(135deg, #b91c1c, #dc2626);
        }


        .sos-status-header.accepted {
            background: linear-gradient(135deg, #15803d, #16a34a);
        }


        .sos-status-header.all_rejected {
            background: linear-gradient(135deg, #92400e, #d97706);
        }


        .sos-status-dot {

            width: 9px;
            height: 9px;

            border-radius: 50%;

            background: white;

            animation: pulseDot2 1.4s ease-in-out infinite;

            flex-shrink: 0;
        }


        @keyframes pulseDot2 {
            0%, 100% { opacity: 1; }
            50% { opacity: .35; }
        }


        .sos-status-body {

            padding: 16px;

            font-size: 12px;

            color: var(--text);

            line-height: 1.6;
        }


        .sos-status-body strong {
            display: block;
            font-size: 13px;
            margin-bottom: 4px;
        }


        .sos-status-actions {

            display: flex;

            gap: 8px;

            padding: 0 16px 16px;
        }


        .sos-status-actions a,
        .sos-status-actions button {

            flex: 1;

            border: none;

            border-radius: 9px;

            padding: 10px;

            font-size: 11px;

            font-weight: 700;

            cursor: pointer;

            text-align: center;
        }


        .sos-btn-call {
            background: var(--primary);
            color: white;
        }


        .sos-btn-cancel {
            background: #f1f5f9;
            color: #475569;
        }


        /* =====================================================
           MEETINGS
        ===================================================== */

        .meeting {

            padding: 16px 20px;

            display: flex;

            align-items: flex-start;

            gap: 14px;

            border-bottom: 1px solid #f0f2f6;
        }


        .meeting:last-child {
            border-bottom: none;
        }


        .meeting-date {

            min-width: 50px;

            text-align: center;

            background: #f8fafc;

            border-radius: 9px;

            padding: 8px 5px;
        }


        .meeting-date strong {

            display: block;

            font-size: 15px;
        }


        .meeting-date span {

            font-size: 8px;

            color: var(--muted);

            text-transform: uppercase;
        }


        .meeting-info {

            flex: 1;
        }


        .meeting-info h4 {

            font-size: 12px;

            margin-bottom: 4px;
        }


        .meeting-info p {

            color: var(--muted);

            font-size: 10px;

            line-height: 1.5;
        }


        /* =====================================================
           MEDICAL INFORMATION
        ===================================================== */

        .medical-grid {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 15px;

            padding: 18px;
        }


        .medical-box {

            border: 1px solid var(--border);

            border-radius: 11px;

            padding: 17px;
        }


        .medical-box h4 {

            font-size: 11px;

            margin-bottom: 9px;
        }


        .medical-box p {

            font-size: 11px;

            color: #5e697a;

            line-height: 1.7;
        }


        .danger-box {

            background: #fffafa;

            border-color: #fee2e2;
        }


        .danger-box h4 {

            color: var(--danger);
        }


        /* =====================================================
           CONTACT / DOCTOR
        ===================================================== */

        .bottom-grid {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 20px;

            margin-top: 22px;
        }


        .doctor-card {

            padding: 20px;
        }


        .doctor-info {

            display: flex;

            align-items: center;

            gap: 13px;

            margin-bottom: 18px;
        }


        .doctor-avatar {

            width: 50px;
            height: 50px;

            border-radius: 14px;

            background: #dbeafe;

            color: var(--primary);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 15px;

            font-weight: 800;
        }


        .doctor-info h4 {

            font-size: 13px;

            margin-bottom: 4px;
        }


        .doctor-info p {

            color: var(--muted);

            font-size: 10px;
        }


        .doctor-details {

            display: grid;

            gap: 11px;
        }


        .doctor-detail {

            display: flex;

            justify-content: space-between;

            gap: 10px;

            font-size: 10px;
        }


        .doctor-detail span:first-child {

            color: var(--muted);
        }


        .doctor-detail span:last-child {

            font-weight: 600;

            text-align: right;
        }


        .contact-card {

            padding: 20px;
        }


        .emergency-contact {

            display: flex;

            align-items: center;

            gap: 13px;

            margin-bottom: 17px;
        }


        .contact-icon {

            width: 46px;
            height: 46px;

            background: #fef2f2;

            color: var(--danger);

            border-radius: 13px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;
        }


        .emergency-contact h4 {

            font-size: 13px;

            margin-bottom: 4px;
        }


        .emergency-contact p {

            color: var(--muted);

            font-size: 10px;
        }


        .call-button {

            display: block;

            width: 100%;

            background: var(--danger);

            color: white;

            text-align: center;

            padding: 11px;

            border-radius: 9px;

            font-size: 11px;

            font-weight: 700;
        }


        /* =====================================================
           EMPTY
        ===================================================== */

        .empty {

            padding: 28px 20px;

            text-align: center;

            color: var(--muted);

            font-size: 11px;
        }


        /* =====================================================
           MOBILE NAV
        ===================================================== */

        .mobile-nav {

            display: none;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media(max-width:1100px) {

            .stats {

                grid-template-columns:
                    repeat(2, 1fr);
            }


            .dashboard-grid {

                grid-template-columns: 1fr;
            }


            .medicine-grid {

                grid-template-columns:
                    repeat(2, 1fr);
            }

        }


        @media(max-width:760px) {

            :root {
                --sidebar-width: 0px;
            }


            .sidebar {
                display: none;
            }


            .main {
                margin-left: 0;
            }


            .topbar {

                height: 70px;

                padding: 0 16px;
            }


            .topbar h1 {
                font-size: 17px;
            }


            .topbar p {
                display: none;
            }


            .top-profile-text {
                display: none;
            }


            .content {

                padding: 18px 14px 90px;
            }


            .hero {

                display: block;

                padding: 22px;
            }


            .hero h2 {
                font-size: 21px;
            }


            .patient-id {

                display: inline-block;

                margin-top: 17px;

                text-align: left;
            }


            .stats {

                grid-template-columns:
                    1fr 1fr;

                gap: 10px;
            }


            .stat-card {

                padding: 15px;
            }


            .stat-card h3 {
                font-size: 21px;
            }


            .stat-icon {

                width: 34px;
                height: 34px;

                font-size: 14px;
            }


            .medicine-grid {

                grid-template-columns: 1fr;
            }


            .medical-grid {

                grid-template-columns: 1fr;
            }


            .bottom-grid {

                grid-template-columns: 1fr;
            }


            .appointment {

                padding: 13px 15px;
            }


            .appointment-date {
                display: none;
            }


            .mobile-nav {

                display: flex;

                position: fixed;

                left: 10px;
                right: 10px;
                bottom: 10px;

                height: 63px;

                background: white;

                border: 1px solid var(--border);

                border-radius: 16px;

                z-index: 200;

                align-items: center;

                justify-content: space-around;

                box-shadow:
                    0 8px 30px rgba(
                        15,
                        23,
                        42,
                        .12
                    );
            }


            .mobile-nav a {

                color: var(--muted);

                font-size: 9px;

                font-weight: 600;

                text-align: center;
            }


            .mobile-nav a.active {
                color: var(--primary);
            }


            .mobile-nav i {

                display: block;

                font-style: normal;

                font-size: 17px;

                margin-bottom: 3px;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar">


    <div class="logo">

        <div class="logo-icon">
            P
        </div>

        <div class="logo-text">
            Patient<span>Check</span>
        </div>

    </div>


    <div class="portal-label">
        Patient Portal
    </div>


    <nav class="nav">


        <a
            href="patient-dashboard.php"
            class="nav-item active"
        >

            <span class="nav-icon">
                ⌂
            </span>

            Dashboard

        </a>


        <a
            href="#health"
            class="nav-item"
        >

            <span class="nav-icon">
                ♡
            </span>

            My Health

        </a>


        <a
            href="#medicines"
            class="nav-item"
        >

            <span class="nav-icon">
                ✚
            </span>

            Medicines

        </a>


        <a
            href="#appointments"
            class="nav-item"
        >

            <span class="nav-icon">
                ◷
            </span>

            Appointments

        </a>


        <a
            href="#meetings"
            class="nav-item"
        >

            <span class="nav-icon">
                ▣
            </span>

            Last Meetings

        </a>


        <a
            href="#doctor"
            class="nav-item"
        >

            <span class="nav-icon">
                ♙
            </span>

            My Doctor

        </a>


        <a
            href="logout.php"
            class="nav-item logout-item"
        >

            <span class="nav-icon">
                ⏻
            </span>

            Logout

        </a>


    </nav>


    <div class="sidebar-profile">

        <div class="mini-profile">

            <div class="avatar">

                <?= safe(
                    initials(
                        $patient['full_name']
                    )
                ) ?>

            </div>


            <div>

                <strong>
                    <?= safe(
                        $patient['full_name']
                    ) ?>
                </strong>

                <span>
                    <?= safe(
                        $patient['patient_code']
                    ) ?>
                </span>

            </div>

        </div>

    </div>


</aside>


<!-- =========================================================
     MAIN
========================================================= -->

<main class="main">

    <button type="button" id="sosButton" class="sos-floating-button sos-premium">
    <span class="sos-pulse"></span>
    <span class="sos-pulse sos-pulse-delay"></span>

    <span class="sos-icon-wrap">
        <span class="sos-icon">🚨</span>
    </span>

    <span class="sos-text">
        <strong>EMERGENCY</strong>
        <small>SOS</small>
    </span>

    <span class="sos-shine"></span>
</button>


<div class="sos-status-panel" id="sosStatusPanel">
    <div class="sos-status-header pending" id="sosStatusHeader">
        <span class="sos-status-dot"></span>
        <span id="sosStatusTitle">Sending SOS…</span>
    </div>
    <div class="sos-status-body" id="sosStatusBody">
        Getting your location and alerting nearby hospitals.
    </div>
    <div class="sos-status-actions" id="sosStatusActions"></div>
</div>


    <!-- TOPBAR -->

    <header class="topbar">


        <div>

            <h1>
                My Health Dashboard
            </h1>

            <p>
                Your health information at a glance.
            </p>

        </div>


        <div class="top-actions">


            <button
                class="icon-button"
                title="Notifications"
            >
                🔔
            </button>


            <div class="top-profile">

                <div class="avatar">

                    <?= safe(
                        initials(
                            $patient['full_name']
                        )
                    ) ?>

                </div>


                <div class="top-profile-text">

                    <strong>
                        <?= safe(
                            $patient['full_name']
                        ) ?>
                    </strong>

                    <span>
                        Patient
                    </span>

                </div>

            </div>


        </div>


    </header>


    <div class="content">


        <!-- =================================================
             HERO
        ================================================= -->

        <section class="hero">


            <div class="hero-content">

                <small>
                    PatientCheck • Personal Health Portal
                </small>


                <h2>

                    Welcome back,
                    <?= safe(
                        explode(
                            " ",
                            trim(
                                $patient['full_name']
                            )
                        )[0]
                    ) ?>.

                </h2>


                <p>
                    Keep track of your medicines,
                    appointments and health information.
                </p>

            </div>


            <div class="patient-id">

                <span>
                    Patient ID
                </span>

                <strong>
                    <?= safe(
                        $patient['patient_code']
                    ) ?>
                </strong>

            </div>


        </section>


        <!-- =================================================
             STATS
        ================================================= -->

        <section class="stats">




            <div class="stat-card">

                <div>

                    <small>
                        Active Medicines
                    </small>

                    <h3>
                        <?= count(
                            $medicines
                        ) ?>
                    </h3>

                </div>


                <div class="stat-icon">
                    ✚
                </div>

            </div>


            <div class="stat-card">

                <div>

                    <small>
                        Upcoming Visits
                    </small>

                    <h3>
                        <?= count(
                            $upcoming_appointments
                        ) ?>
                    </h3>

                </div>


                <div class="stat-icon">
                    ◷
                </div>

            </div>


            <div class="stat-card">

                <div>

                    <small>
                        Total Appointments
                    </small>

                    <h3>
                        <?= (int)$total_appointments ?>
                    </h3>

                </div>


                <div class="stat-icon">
                    ▣
                </div>

            </div>


            <div class="stat-card">

                <div>

                    <small>
                        Completed Meetings
                    </small>

                    <h3>
                        <?= (int)$completed_meetings ?>
                    </h3>

                </div>


                <div class="stat-icon">
                    ✓
                </div>

            </div>


        </section>


        <!-- =================================================
             APPOINTMENTS + HEALTH
        ================================================= -->

        <section
            class="dashboard-grid"
            id="appointments"
        >


            <!-- UPCOMING APPOINTMENTS -->

            <div class="card">


                <div class="card-header">

                    <h3>
                        Upcoming Appointments
                    </h3>

                    <span>
                        Scheduled visits
                    </span>

                </div>


                <?php if (
                    count(
                        $upcoming_appointments
                    ) > 0
                ): ?>


                    <?php foreach (
                        $upcoming_appointments
                        as $appointment
                    ): ?>


                        <div class="appointment">


                            <div class="appointment-avatar">

                                <?= safe(
                                    initials(
                                        $appointment[
                                            'doctor_name'
                                        ]
                                    )
                                ) ?>

                            </div>


                            <div class="appointment-main">

                                <strong>

                                    <?= safe(
                                        $appointment[
                                            'doctor_name'
                                        ]
                                    ) ?>

                                    <span
                                        class="status scheduled"
                                    >
                                        Scheduled
                                    </span>

                                </strong>


                                <span>

                                    <?= safe(
                                        $appointment[
                                            'specialization'
                                        ]
                                    ) ?>

                                    •
                                    <?= safe(
                                        $appointment[
                                            'reason'
                                        ]
                                    ) ?>

                                </span>

                            </div>


                            <div class="appointment-date">

                                <strong>

                                    <?= date(
                                        "h:i A",
                                        strtotime(
                                            $appointment[
                                                'appointment_date'
                                            ]
                                        )
                                    ) ?>

                                </strong>


                                <span>

                                    <?= date(
                                        "d M Y",
                                        strtotime(
                                            $appointment[
                                                'appointment_date'
                                            ]
                                        )
                                    ) ?>

                                </span>

                            </div>


                        </div>


                    <?php endforeach; ?>


                <?php else: ?>


                    <div class="empty">

                        You don't have any upcoming
                        appointments.

                    </div>


                <?php endif; ?>


            </div>


            <!-- HEALTH OVERVIEW -->

            <div
                class="card"
                id="health"
            >


                <div class="card-header">

                    <h3>
                        Health Overview
                    </h3>

                    <span>
                        Personal information
                    </span>

                </div>


                <div class="health-card">


                    <div class="health-profile">

                        <div class="avatar">

                            <?= safe(
                                initials(
                                    $patient[
                                        'full_name'
                                    ]
                                )
                            ) ?>

                        </div>


                        <div>

                            <h3>
                                <?= safe(
                                    $patient[
                                        'full_name'
                                    ]
                                ) ?>
                            </h3>

                            <p>
                                <?= calculateAge(
                                    $patient[
                                        'date_of_birth'
                                    ]
                                ) ?> years old
                            </p>

                        </div>

                    </div>


                    <div class="health-list">


                        <div class="health-row">

                            <span>
                                Blood Group
                            </span>

                            <strong class="blood">

                                <?= safe(
                                    $patient[
                                        'blood_group'
                                    ]
                                ) ?>

                            </strong>

                        </div>


                        <div class="health-row">

                            <span>
                                Height
                            </span>

                            <strong>

                                <?= $patient[
                                    'height_cm'
                                ]
                                    ? safe(
                                        $patient[
                                            'height_cm'
                                        ]
                                    ) . " cm"
                                    : "—"
                                ?>

                            </strong>

                        </div>


                        <div class="health-row">

                            <span>
                                Weight
                            </span>

                            <strong>

                                <?= $patient[
                                    'weight_kg'
                                ]
                                    ? safe(
                                        $patient[
                                            'weight_kg'
                                        ]
                                    ) . " kg"
                                    : "—"
                                ?>

                            </strong>

                        </div>


                        <div class="health-row">

                            <span>
                                Gender
                            </span>

                            <strong>

                                <?= safe(
                                    ucfirst(
                                        $patient[
                                            'gender'
                                        ] ?: "—"
                                    )
                                ) ?>

                            </strong>

                        </div>


                    </div>


                </div>


            </div>


        </section>


        <!-- =================================================
             MEDICINES
        ================================================= -->

        <section
            class="card"
            id="medicines"
        >


            <div class="card-header">

                <h3>
                    My Medicines
                </h3>

                <span>
                    <?= count(
                        $medicines
                    ) ?> active medicines
                </span>

            </div>


            <?php if (
                count($medicines) > 0
            ): ?>


                <div class="medicine-grid">


                    <?php foreach (
                        $medicines
                        as $medicine
                    ): ?>


                        <div class="medicine-card">


                            <div class="medicine-icon">
                                ✚
                            </div>


                            <span
                                class="medicine-status"
                            >
                                ACTIVE
                            </span>


                            <h4>

                                <?= safe(
                                    $medicine[
                                        'medicine_name'
                                    ]
                                ) ?>

                            </h4>


                            <p>

                                <strong>
                                    <?= safe(
                                        $medicine[
                                            'dosage'
                                        ]
                                    ) ?>
                                </strong>

                                <br>

                                <?= safe(
                                    $medicine[
                                        'instructions'
                                    ]
                                ) ?>

                            </p>


                            <div class="medicine-time">

                                <?= safe(
                                    $medicine[
                                        'frequency'
                                    ]
                                ) ?>

                                •
                                <?= safe(
                                    $medicine[
                                        'timing'
                                    ]
                                ) ?>

                            </div>


                        </div>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <div class="empty">

                    No active medicines found.

                </div>


            <?php endif; ?>


        </section>


        <!-- =================================================
             MEDICAL INFORMATION
        ================================================= -->

        <section
            class="card"
            style="margin-top:22px;"
        >


            <div class="card-header">

                <h3>
                    Important Medical Information
                </h3>

                <span>
                    Keep this information updated
                </span>

            </div>


            <div class="medical-grid">


                <div class="medical-box danger-box">

                    <h4>
                        Allergies
                    </h4>

                    <p>

                        <?= safe(
                            $patient[
                                'allergies'
                            ] ?: 'No known allergies.'
                        ) ?>

                    </p>

                </div>


                <div class="medical-box">

                    <h4>
                        Medical Conditions
                    </h4>

                    <p>

                        <?= safe(
                            $patient[
                                'medical_conditions'
                            ] ?: 'No medical conditions recorded.'
                        ) ?>

                    </p>

                </div>


            </div>


        </section>


        <!-- =================================================
             LAST MEETINGS
        ================================================= -->

        <section
            class="card"
            id="meetings"
            style="margin-top:22px;"
        >


            <div class="card-header">

                <h3>
                    Last Meetings
                </h3>

                <span>
                    Recent completed appointments
                </span>

            </div>


            <?php if (
                count($recent_meetings) > 0
            ): ?>


                <?php foreach (
                    $recent_meetings
                    as $meeting
                ): ?>


                    <div class="meeting">


                        <div class="meeting-date">

                            <strong>

                                <?= date(
                                    "d",
                                    strtotime(
                                        $meeting[
                                            'appointment_date'
                                        ]
                                    )
                                ) ?>

                            </strong>


                            <span>

                                <?= date(
                                    "M",
                                    strtotime(
                                        $meeting[
                                            'appointment_date'
                                        ]
                                    )
                                ) ?>

                            </span>

                        </div>


                        <div class="meeting-info">

                            <h4>

                                <?= safe(
                                    $meeting[
                                        'doctor_name'
                                    ]
                                ) ?>


                                <span
                                    class="status completed"
                                >
                                    Completed
                                </span>

                            </h4>


                            <p>

                                <?= safe(
                                    $meeting[
                                        'reason'
                                    ] ?: 'Medical consultation'
                                ) ?>

                                <?php if (
                                    !empty(
                                        $meeting[
                                            'diagnosis'
                                        ]
                                    )
                                ): ?>

                                    <br>

                                    <strong>
                                        Diagnosis:
                                    </strong>

                                    <?= safe(
                                        $meeting[
                                            'diagnosis'
                                        ]
                                    ) ?>

                                <?php endif; ?>


                                <?php if (
                                    !empty(
                                        $meeting[
                                            'doctor_notes'
                                        ]
                                    )
                                ): ?>

                                    <br>

                                    <?= safe(
                                        $meeting[
                                            'doctor_notes'
                                        ]
                                    ) ?>

                                <?php endif; ?>

                            </p>

                        </div>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="empty">

                    No previous meetings found.

                </div>


            <?php endif; ?>


        </section>


        <!-- =================================================
             DOCTOR + EMERGENCY CONTACT
        ================================================= -->

        <section class="bottom-grid">


            <!-- DOCTOR -->

            <div
                class="card"
                id="doctor"
            >


                <div class="card-header">

                    <h3>
                        My Doctor
                    </h3>

                    <span>
                        Recent doctor
                    </span>

                </div>


                <?php if (
                    $primary_doctor
                ): ?>


                    <div class="doctor-card">


                        <div class="doctor-info">


                            <div class="doctor-avatar">

                                <?= safe(
                                    initials(
                                        $primary_doctor[
                                            'full_name'
                                        ]
                                    )
                                ) ?>

                            </div>


                            <div>

                                <h4>

                                    <?= safe(
                                        $primary_doctor[
                                            'full_name'
                                        ]
                                    ) ?>

                                </h4>


                                <p>

                                    <?= safe(
                                        $primary_doctor[
                                            'specialization'
                                        ]
                                    ) ?>

                                </p>

                            </div>


                        </div>


                        <div class="doctor-details">


                            <div class="doctor-detail">

                                <span>
                                    Qualification
                                </span>

                                <span>

                                    <?= safe(
                                        $primary_doctor[
                                            'qualification'
                                        ]
                                    ) ?>

                                </span>

                            </div>


                            <div class="doctor-detail">

                                <span>
                                    Hospital
                                </span>

                                <span>

                                    <?= safe(
                                        $primary_doctor[
                                            'hospital_name'
                                        ]
                                    ) ?>

                                </span>

                            </div>


                            <div class="doctor-detail">

                                <span>
                                    Experience
                                </span>

                                <span>

                                    <?= safe(
                                        $primary_doctor[
                                            'experience_years'
                                        ]
                                    ) ?>
                                    years

                                </span>

                            </div>


                            <div class="doctor-detail">

                                <span>
                                    Phone
                                </span>

                                <span>

                                    <?= safe(
                                        $primary_doctor[
                                            'phone'
                                        ]
                                    ) ?>

                                </span>

                            </div>


                        </div>


                    </div>


                <?php else: ?>


                    <div class="empty">

                        No doctor information available.

                    </div>


                <?php endif; ?>


            </div>


            <!-- EMERGENCY CONTACT -->

            <div class="card">


                <div class="card-header">

                    <h3>
                        Emergency Contact
                    </h3>

                    <span>
                        Quick access
                    </span>

                </div>


                <div class="contact-card">


                    <div class="emergency-contact">


                        <div class="contact-icon">
                            ☎
                        </div>


                        <div>

                            <h4>

                                <?= safe(
                                    $patient[
                                        'emergency_contact_name'
                                    ]
                                ) ?>

                            </h4>


                            <p>
                                Emergency Contact
                            </p>

                        </div>


                    </div>


                    <?php if (
                        !empty(
                            $patient[
                                'emergency_contact_phone'
                            ]
                        )
                    ): ?>


                        <a
                            href="tel:<?= safe(
                                $patient[
                                    'emergency_contact_phone'
                                ]
                            ) ?>"
                            class="call-button"
                        >

                            Call Emergency Contact

                        </a>


                    <?php else: ?>


                        <div class="empty">
                            No emergency contact number available.
                        </div>


                    <?php endif; ?>


                </div>


            </div>


        </section>


    </div>
    
    
            <?php if (!empty($patient_timeline25)): ?>

    <div class="timeline25">

        <?php foreach ($patient_timeline25 as $timeline_record25): ?>

            <div class="timeline-item25">

                <div class="timeline-dot25"></div>

                <div class="timeline-date25">

                    <?= formatDateTime(
                        $timeline_record25['visit_date']
                    ) ?>

                </div>

                <div class="timeline-content25">

                    <div class="timeline-doctor25">

                        <?= safe(
                            $timeline_record25['doctor_name25']
                            ?: 'Doctor'
                        ) ?>

                    </div>

                    <?php if (!empty($timeline_record25['specialization25'])): ?>

                        <span class="timeline-specialization25">

                            <?= safe(
                                $timeline_record25['specialization25']
                            ) ?>

                        </span>

                    <?php endif; ?>

                    <?php if (!empty($timeline_record25['hospital_name'])): ?>

                        <div class="timeline-hospital25">

                            🏥
                            <?= safe(
                                $timeline_record25['hospital_name']
                            ) ?>

                        </div>

                    <?php endif; ?>

                    <?php if (!empty($timeline_record25['diagnosis'])): ?>

                        <div class="timeline-section25">

                            <span class="timeline-label25">
                                Diagnosis
                            </span>

                            <div class="timeline-text25">
                                <?= safe(
                                    $timeline_record25['diagnosis']
                                ) ?>
                            </div>

                        </div>

                    <?php endif; ?>

                    <?php if (!empty($timeline_record25['treatment'])): ?>

                        <div class="timeline-section25">

                            <span class="timeline-label25">
                                Treatment
                            </span>

                            <div class="timeline-text25">
                                <?= safe(
                                    $timeline_record25['treatment']
                                ) ?>
                            </div>

                        </div>

                    <?php endif; ?>

                    <?php if (!empty($timeline_record25['medicines'])): ?>

                        <div class="timeline-section25">

                            <span class="timeline-label25">
                                Medicines
                            </span>

                            <div class="timeline-text25 timeline-medicine25">

                                <?= safe(
                                    $timeline_record25['medicines']
                                ) ?>

                            </div>

                        </div>

                    <?php endif; ?>

                    <?php if (!empty($timeline_record25['doctor_notes'])): ?>

                        <div class="timeline-section25">

                            <span class="timeline-label25">
                                Doctor's Notes
                            </span>

                            <div class="timeline-text25 timeline-notes25">

                                <?= safe(
                                    $timeline_record25['doctor_notes']
                                ) ?>

                            </div>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php else: ?>

    <div class="timeline-empty25">
        No medical history available yet.
    </div>

<?php endif; ?>



</main>


<!-- =========================================================
     MOBILE NAVIGATION
========================================================= -->

<nav class="mobile-nav">


    <a
        href="patient-dashboard.php"
        class="active"
    >

        <i>⌂</i>

        Home

    </a>


    <a href="#health">

        <i>♡</i>

        Health

    </a>


    <a href="#medicines">

        <i>✚</i>

        Medicines

    </a>


    <a href="#appointments">

        <i>◷</i>

        Visits

    </a>


    <a href="#doctor">

        <i>♙</i>

        Doctor

    </a>


</nav>

</body>


<script>

const LOCATION_UPDATE_INTERVAL_MS = 18000;  // 15-20s live monitoring updates
const STATUS_POLL_INTERVAL_MS     = 4000;

const sosButton        = document.getElementById("sosButton");
const sosButtonDefault = sosButton.innerHTML;

const panel        = document.getElementById("sosStatusPanel");
const panelHeader  = document.getElementById("sosStatusHeader");
const panelTitle   = document.getElementById("sosStatusTitle");
const panelBody    = document.getElementById("sosStatusBody");
const panelActions = document.getElementById("sosStatusActions");

let activeEmergencyCode = null;
let statusPollTimer     = null;
let locationUpdateTimer = null;


function getCurrentPosition() {

    return new Promise((resolve, reject) => {

        if (!navigator.geolocation) {
            reject(new Error("Your device does not support location services."));
            return;
        }

        navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        });
    });
}


function setButtonLocating(isLocating) {

    sosButton.disabled = isLocating;

    sosButton.innerHTML = isLocating
        ? `<span class="sos-icon">📍</span>
           <span class="sos-text">
                <strong>LOCATING</strong>
                <small>PLEASE WAIT</small>
           </span>`
        : sosButtonDefault;
}


function showPanel(state, titleText, bodyHtml, actionsHtml) {

    panel.classList.add("visible");

    panelHeader.className = "sos-status-header " + state;
    panelTitle.textContent = titleText;
    panelBody.innerHTML = bodyHtml;
    panelActions.innerHTML = actionsHtml || "";
}


function hidePanel() {
    panel.classList.remove("visible");
    panelActions.innerHTML = "";
}


function stopAllTimers() {

    if (statusPollTimer) {
        clearInterval(statusPollTimer);
        statusPollTimer = null;
    }

    if (locationUpdateTimer) {
        clearInterval(locationUpdateTimer);
        locationUpdateTimer = null;
    }
}


async function sendLocationUpdate() {

    if (!activeEmergencyCode) {
        return;
    }

    try {

        const position = await getCurrentPosition();

        const formData = new FormData();
        formData.append("emergency_code", activeEmergencyCode);
        formData.append("latitude", position.coords.latitude);
        formData.append("longitude", position.coords.longitude);
        formData.append("accuracy", position.coords.accuracy);

        await fetch("update-location.php", { method: "POST", body: formData });

    } catch (err) {
        // If GPS briefly fails, just skip this update cycle silently.
        console.warn("Location update skipped:", err);
    }
}


async function pollStatus() {

    if (!activeEmergencyCode) {
        return;
    }

    try {

        const res = await fetch(
            "check-emergency-status.php?code=" + encodeURIComponent(activeEmergencyCode),
            { cache: "no-store" }
        );

        const data = await res.json();

        if (!data.success) {
            return;
        }

        renderStatus(data);

    } catch (err) {
        console.warn("Status check failed:", err);
    }
}


function renderStatus(data) {

    if (data.overall_status === "pending") {

        showPanel(
            "pending",
            "SOS Active",
            `Alerting nearby hospitals… <strong>${data.pending_count} of ${data.total_count}</strong> still reviewing your request.<br>Your live location is being shared.`,
            `<button class="sos-btn-cancel" onclick="cancelEmergency()">Cancel SOS</button>`
        );
        return;
    }

    if (data.overall_status === "accepted") {

        showPanel(
            "accepted",
            "Hospital On The Way",
            `<strong>${data.hospital_name}</strong>
             has accepted your emergency request and has been sent your live location.
             ${data.hospital_address ? "<br>" + data.hospital_address : ""}`,
            `${data.hospital_phone
                ? `<a class="sos-btn-call" href="tel:${data.hospital_phone}">Call Hospital</a>`
                : ""}
             <button class="sos-btn-cancel" onclick="cancelEmergency()">I'm Safe / End</button>`
        );

        // Keep sending live location so the hospital can keep tracking en route.
        return;
    }

    if (data.overall_status === "all_rejected") {

        showPanel(
            "all_rejected",
            "No Hospital Available",
            `All nearby hospitals were unable to respond. Please call your local emergency number directly if this is urgent.`,
            `<button class="sos-btn-cancel" onclick="resetSos()">Close</button>
             <button class="sos-btn-call" onclick="retrySos()">Try Again</button>`
        );

        stopAllTimers();
        return;
    }

    if (data.overall_status === "cancelled" || data.overall_status === "completed") {
        resetSos();
    }
}


function resetSos() {

    stopAllTimers();
    activeEmergencyCode = null;
    hidePanel();
    setButtonLocating(false);
}


async function cancelEmergency() {

    if (!activeEmergencyCode) {
        resetSos();
        return;
    }

    try {

        const formData = new FormData();
        formData.append("emergency_code", activeEmergencyCode);

        await fetch("cancel-emergency.php", { method: "POST", body: formData });

    } catch (err) {
        console.warn("Cancel failed:", err);
    }

    resetSos();
}


function retrySos() {
    hidePanel();
    startSos();
}


async function startSos() {

    setButtonLocating(true);

    showPanel(
        "pending",
        "Sending SOS…",
        "Getting your location and alerting nearby hospitals.",
        ""
    );

    try {

        const position = await getCurrentPosition();

        const formData = new FormData();
        formData.append("latitude", position.coords.latitude);
        formData.append("longitude", position.coords.longitude);
        formData.append("accuracy", position.coords.accuracy);

        const res = await fetch("send-emergency.php", { method: "POST", body: formData });
        const data = await res.json();

        setButtonLocating(false);

        if (!data.success) {

            showPanel(
                "all_rejected",
                "Could Not Send SOS",
                data.message || "Something went wrong. Please try again or call your local emergency number.",
                `<button class="sos-btn-cancel" onclick="hidePanelAndReset()">Close</button>`
            );
            return;
        }

        activeEmergencyCode = data.emergency_code;

        showPanel(
            "pending",
            "SOS Active",
            `Your emergency has been sent to <strong>${data.hospital_count || ""} nearby hospitals</strong>. Waiting for a response…`,
            `<button class="sos-btn-cancel" onclick="cancelEmergency()">Cancel SOS</button>`
        );

        stopAllTimers();
        statusPollTimer = setInterval(pollStatus, STATUS_POLL_INTERVAL_MS);
        locationUpdateTimer = setInterval(sendLocationUpdate, LOCATION_UPDATE_INTERVAL_MS);

        pollStatus();

    } catch (error) {

        setButtonLocating(false);

        let message = "Unable to get your location.";

        if (error.code === 1) {
            message = "Please allow location access to send the emergency request.";
        } else if (error.code === 2) {
            message = "Unable to determine your location. Please try again.";
        } else if (error.code === 3) {
            message = "Location request timed out. Please try again.";
        } else if (error.message) {
            message = error.message;
        }

        showPanel(
            "all_rejected",
            "Location Needed",
            message,
            `<button class="sos-btn-cancel" onclick="hidePanelAndReset()">Close</button>
             <button class="sos-btn-call" onclick="retrySos()">Try Again</button>`
        );
    }
}


function hidePanelAndReset() {
    hidePanel();
    resetSos();
}


sosButton.addEventListener("click", startSos);

</script>

</html>
