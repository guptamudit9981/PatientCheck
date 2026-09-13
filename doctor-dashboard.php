<?php

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| DEMO DOCTOR
|--------------------------------------------------------------------------
| Later this will come from the logged-in doctor's session:
|
| $doctor_id = $_SESSION['doctor_id'];
|
*/

$doctor_id = 1;
$_SESSION['doctor_id'] = $doctor_id;

/*
|--------------------------------------------------------------------------
| GET DOCTOR INFORMATION
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM doctors
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$doctor_id]);

$doctor = $stmt->fetch();

if (!$doctor) {
    die("Doctor not found.");
}


/*
|--------------------------------------------------------------------------
| DASHBOARD STATISTICS
|--------------------------------------------------------------------------
*/

// Total patients seen by this doctor
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT patient_id)
    FROM appointments
    WHERE doctor_id = ?
");

$stmt->execute([$doctor_id]);

$total_patients = $stmt->fetchColumn();


// Today's appointments
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM appointments
    WHERE doctor_id = ?
    AND DATE(appointment_date) = CURDATE()
    AND status != 'cancelled'
");

$stmt->execute([$doctor_id]);

$today_appointments = $stmt->fetchColumn();


// Upcoming appointments
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM appointments
    WHERE doctor_id = ?
    AND appointment_date >= NOW()
    AND status = 'scheduled'
");

$stmt->execute([$doctor_id]);

$upcoming_appointments = $stmt->fetchColumn();


// Active medicines prescribed
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM medicines
    WHERE prescribed_by = ?
    AND status = 'active'
");

$stmt->execute([$doctor_id]);

$active_medicines = $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| UPCOMING APPOINTMENTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        a.*,
        p.patient_code,
        p.full_name,
        p.gender,
        p.date_of_birth,
        p.blood_group,
        p.phone
    FROM appointments a
    INNER JOIN patients p
        ON a.patient_id = p.id
    WHERE a.doctor_id = ?
    AND a.status = 'scheduled'
    AND a.appointment_date >= NOW()
    ORDER BY a.appointment_date ASC
    LIMIT 8
");

$stmt->execute([$doctor_id]);

$upcoming = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| RECENT PATIENT MEETINGS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        a.*,
        p.patient_code,
        p.full_name,
        p.gender,
        p.date_of_birth,
        p.blood_group
    FROM appointments a
    INNER JOIN patients p
        ON a.patient_id = p.id
    WHERE a.doctor_id = ?
    AND a.status = 'completed'
    ORDER BY a.appointment_date DESC
    LIMIT 8
");

$stmt->execute([$doctor_id]);

$recent_meetings = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| PATIENT SEARCH
|--------------------------------------------------------------------------
*/

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$patients = [];

if ($search !== '') {

    $stmt = $pdo->prepare("
        SELECT DISTINCT
            p.id,
            p.patient_code,
            p.full_name,
            p.gender,
            p.date_of_birth,
            p.blood_group,
            p.phone,
            p.medical_conditions,
            p.allergies
        FROM patients p
        INNER JOIN appointments a
            ON a.patient_id = p.id
        WHERE a.doctor_id = ?
        AND (
            p.full_name LIKE ?
            OR p.patient_code LIKE ?
            OR p.phone LIKE ?
        )
        ORDER BY p.full_name ASC
        LIMIT 20
    ");

    $searchTerm = "%" . $search . "%";

    $stmt->execute([
        $doctor_id,
        $searchTerm,
        $searchTerm,
        $searchTerm
    ]);

    $patients = $stmt->fetchAll();
}


/*
|--------------------------------------------------------------------------
| SELECTED PATIENT
|--------------------------------------------------------------------------
*/

$selected_patient = null;
$patient_medicines = [];
$patient_meetings = [];

if (isset($_GET['patient_id'])) {

    $patient_id = (int) $_GET['patient_id'];

    /*
    | Verify that this patient has an appointment with this doctor.
    */

    $stmt = $pdo->prepare("
        SELECT DISTINCT
            p.*
        FROM patients p
        INNER JOIN appointments a
            ON a.patient_id = p.id
        WHERE p.id = ?
        AND a.doctor_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $patient_id,
        $doctor_id
    ]);

    $selected_patient = $stmt->fetch();


    if ($selected_patient) {

        /*
        | Patient medicines
        */

        $stmt = $pdo->prepare("
            SELECT
                m.*,
                d.full_name AS doctor_name
            FROM medicines m
            LEFT JOIN doctors d
                ON m.prescribed_by = d.id
            WHERE m.patient_id = ?
            ORDER BY
                CASE
                    WHEN m.status = 'active' THEN 1
                    ELSE 2
                END,
                m.start_date DESC
        ");

        $stmt->execute([$patient_id]);

        $patient_medicines = $stmt->fetchAll();


        /*
        | Patient appointment history
        */

        $stmt = $pdo->prepare("
            SELECT
                a.*
            FROM appointments a
            WHERE a.patient_id = ?
            AND a.doctor_id = ?
            ORDER BY a.appointment_date DESC
        ");

        $stmt->execute([
            $patient_id,
            $doctor_id
        ]);

        $patient_meetings = $stmt->fetchAll();
    }
}


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


function formatDateTime($date)
{
    return date("d M Y, h:i A", strtotime($date));
}


function formatDate($date)
{
    return date("d M Y", strtotime($date));
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
        Doctor Dashboard | PatientCheck
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

            --secondary: #0f172a;

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

            --sidebar-width: 260px;
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


        button,
        input {

            font-family: inherit;
        }


        a {

            text-decoration: none;
            color: inherit;
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

            background: #ffffff;

            border-right: 1px solid var(--border);

            display: flex;

            flex-direction: column;

            z-index: 100;
        }


        .logo {

            height: 82px;

            display: flex;

            align-items: center;

            padding: 0 24px;

            border-bottom: 1px solid var(--border);
        }


        .logo-icon {

            width: 42px;
            height: 42px;

            background: var(--primary);

            border-radius: 12px;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 21px;

            font-weight: 800;

            margin-right: 11px;
        }


        .logo-text {

            font-size: 20px;

            font-weight: 800;

            letter-spacing: -0.5px;
        }


        .logo-text span {

            color: var(--primary);
        }


        .doctor-label {

            padding: 24px 24px 12px;

            font-size: 11px;

            text-transform: uppercase;

            letter-spacing: 1.2px;

            color: #9aa3b2;

            font-weight: 700;
        }


        .nav {

            padding: 0 13px;

            flex: 1;
        }


        .nav-item {

            display: flex;

            align-items: center;

            gap: 13px;

            padding: 13px 14px;

            margin-bottom: 5px;

            border-radius: 10px;

            color: #647084;

            font-size: 14px;

            font-weight: 600;

            transition: 0.2s;
        }


        .nav-item:hover {

            background: #f1f5ff;

            color: var(--primary);
        }


        .nav-item.active {

            background: #eff6ff;

            color: var(--primary);
        }


        .nav-icon {

            width: 21px;

            text-align: center;

            font-size: 17px;
        }


        .sidebar-bottom {

            padding: 18px;

            border-top: 1px solid var(--border);
        }


        .mini-doctor {

            display: flex;

            align-items: center;

            gap: 11px;
        }


        .avatar {

            width: 42px;
            height: 42px;

            border-radius: 50%;

            background: #dbeafe;

            color: var(--primary);

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: 800;

            font-size: 13px;

            flex-shrink: 0;
        }


        .mini-doctor strong {

            display: block;

            font-size: 13px;

            margin-bottom: 3px;
        }


        .mini-doctor small {

            color: var(--muted);

            font-size: 11px;
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

            background: #ffffff;

            border-bottom: 1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 34px;

            position: sticky;

            top: 0;

            z-index: 50;
        }

		.add-record-btn25 {
    display: inline-block;
    padding: 10px 16px;
    background: #2563eb;
    color: #ffffff;
    text-decoration: none;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 600;
}

.add-record-btn25:hover {
    background: #1d4ed8;
}
        .welcome h1 {

            font-size: 21px;

            letter-spacing: -0.4px;

            margin-bottom: 4px;
        }


        .welcome p {

            color: var(--muted);

            font-size: 13px;
        }


        .top-actions {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .icon-button {

            width: 42px;
            height: 42px;

            border: 1px solid var(--border);

            background: white;

            border-radius: 11px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;

            cursor: pointer;
        }


        .doctor-profile {

            display: flex;

            align-items: center;

            gap: 10px;

            padding-left: 10px;
        }


        .doctor-profile .avatar {

            width: 38px;
            height: 38px;
        }


        .doctor-profile-text strong {

            display: block;

            font-size: 13px;
        }


        .doctor-profile-text span {

            display: block;

            color: var(--muted);

            font-size: 11px;

            margin-top: 2px;
        }


        .content {

            padding: 30px 34px 50px;
        }


        /* =====================================================
           HERO
        ===================================================== */

        .hero {

            background: linear-gradient(
                135deg,
                #172554,
                #1d4ed8
            );

            border-radius: 18px;

            padding: 28px 30px;

            color: white;

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 24px;

            overflow: hidden;

            position: relative;
        }


        .hero::after {

            content: "";

            position: absolute;

            width: 230px;
            height: 230px;

            border-radius: 50%;

            border: 40px solid rgba(255,255,255,0.05);

            right: -50px;

            top: -70px;
        }


        .hero-content {

            position: relative;

            z-index: 2;
        }


        .hero small {

            display: block;

            color: #bfdbfe;

            font-size: 12px;

            margin-bottom: 8px;
        }


        .hero h2 {

            font-size: 26px;

            margin-bottom: 8px;

            letter-spacing: -0.7px;
        }


        .hero p {

            color: #dbeafe;

            font-size: 13px;

            max-width: 600px;
        }


        .hero-badge {

            position: relative;

            z-index: 2;

            background: rgba(255,255,255,0.12);

            border: 1px solid rgba(255,255,255,0.15);

            padding: 14px 18px;

            border-radius: 12px;

            text-align: right;
        }


        .hero-badge strong {

            display: block;

            font-size: 14px;

            margin-bottom: 4px;
        }


        .hero-badge span {

            font-size: 11px;

            color: #bfdbfe;
        }


        /* =====================================================
           STAT CARDS
        ===================================================== */

        .stats {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 25px;
        }


        .stat-card {

            background: white;

            border: 1px solid var(--border);

            border-radius: 15px;

            padding: 21px;

            display: flex;

            justify-content: space-between;

            align-items: flex-start;
        }


        .stat-card small {

            color: var(--muted);

            font-size: 12px;

            font-weight: 600;
        }


        .stat-card h3 {

            font-size: 28px;

            margin-top: 8px;

            letter-spacing: -1px;
        }


        .stat-icon {

            width: 43px;
            height: 43px;

            border-radius: 11px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;

            background: var(--info-bg);

            color: var(--primary);
        }


        /* =====================================================
           SECTION
        ===================================================== */

        .section-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 15px;
        }


        .section-header h2 {

            font-size: 17px;

            letter-spacing: -0.3px;
        }


        .section-header span {

            font-size: 12px;

            color: var(--muted);
        }


        /* =====================================================
           GRID
        ===================================================== */

        .dashboard-grid {

            display: grid;

            grid-template-columns:
                minmax(0, 1.65fr)
                minmax(280px, 0.8fr);

            gap: 22px;

            margin-bottom: 28px;
        }


        .card {

            background: white;

            border: 1px solid var(--border);

            border-radius: 15px;

            overflow: hidden;
        }


        .card-header {

            padding: 19px 21px;

            border-bottom: 1px solid var(--border);

            display: flex;

            justify-content: space-between;

            align-items: center;
        }


        .card-header h3 {

            font-size: 14px;
        }


        .card-header span {

            color: var(--muted);

            font-size: 11px;
        }


        /* =====================================================
           APPOINTMENTS
        ===================================================== */

        .appointment {

            display: flex;

            align-items: center;

            padding: 16px 21px;

            border-bottom: 1px solid #f0f2f6;

            gap: 13px;
        }


        .appointment:last-child {

            border-bottom: 0;
        }


        .appointment-avatar {

            width: 40px;
            height: 40px;

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

            font-size: 13px;

            margin-bottom: 4px;
        }


        .appointment-main span {

            display: block;

            color: var(--muted);

            font-size: 11px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .appointment-time {

            text-align: right;
        }


        .appointment-time strong {

            display: block;

            font-size: 12px;
        }


        .appointment-time span {

            font-size: 10px;

            color: var(--muted);
        }


        .status {

            display: inline-flex;

            align-items: center;

            padding: 5px 8px;

            border-radius: 6px;

            font-size: 10px;

            font-weight: 700;

            margin-left: 7px;
        }


        .status.scheduled {

            color: var(--primary);

            background: var(--info-bg);
        }


        .status.completed {

            color: var(--success);

            background: var(--success-bg);
        }


        .status.cancelled {

            color: var(--danger);

            background: var(--danger-bg);
        }


        /* =====================================================
           PROFILE CARD
        ===================================================== */

        .profile-card {

            padding: 23px;
        }


        .large-profile {

            display: flex;

            align-items: center;

            gap: 14px;

            margin-bottom: 22px;
        }


        .large-profile .avatar {

            width: 58px;
            height: 58px;

            border-radius: 16px;

            font-size: 17px;
        }


        .large-profile h3 {

            font-size: 16px;

            margin-bottom: 5px;
        }


        .large-profile p {

            font-size: 11px;

            color: var(--muted);
        }


        .profile-info {

            display: grid;

            gap: 13px;
        }


        .info-row {

            display: flex;

            justify-content: space-between;

            gap: 15px;

            font-size: 12px;
        }


        .info-row span:first-child {

            color: var(--muted);
        }


        .info-row span:last-child {

            text-align: right;

            font-weight: 600;
        }


        /* =====================================================
           SEARCH
        ===================================================== */

        .search-area {

            background: white;

            border: 1px solid var(--border);

            border-radius: 15px;

            padding: 20px;

            margin-bottom: 28px;
        }


        .search-form {

            display: flex;

            gap: 10px;
        }


        .search-input {

            flex: 1;

            height: 45px;

            border: 1px solid var(--border);

            border-radius: 10px;

            padding: 0 14px;

            outline: none;

            font-size: 13px;

            background: #fafbfc;
        }


        .search-input:focus {

            border-color: var(--primary);

            background: white;
        }


        .search-button {

            height: 45px;

            padding: 0 20px;

            border: none;

            border-radius: 10px;

            background: var(--primary);

            color: white;

            font-weight: 700;

            cursor: pointer;
        }


        .search-button:hover {

            background: var(--primary-dark);
        }


        /* =====================================================
           PATIENT SEARCH RESULTS
        ===================================================== */

        .patient-results {

            margin-top: 18px;

            border-top: 1px solid var(--border);

            padding-top: 16px;
        }


        .patient-result {

            display: flex;

            align-items: center;

            gap: 13px;

            padding: 13px 0;

            border-bottom: 1px solid #f0f2f6;
        }


        .patient-result:last-child {

            border-bottom: 0;
        }


        .patient-result .avatar {

            width: 42px;
            height: 42px;

            border-radius: 11px;
        }


        .patient-result-info {

            flex: 1;
        }


        .patient-result-info strong {

            display: block;

            font-size: 13px;

            margin-bottom: 4px;
        }


        .patient-result-info span {

            color: var(--muted);

            font-size: 11px;
        }


        .view-button {

            padding: 8px 12px;

            border-radius: 8px;

            background: var(--info-bg);

            color: var(--primary);

            font-size: 11px;

            font-weight: 700;
        }


        /* =====================================================
           PATIENT DETAIL
        ===================================================== */

        .patient-detail {

            margin-top: 28px;

            background: white;

            border: 1px solid var(--border);

            border-radius: 16px;

            overflow: hidden;
        }


        .patient-detail-header {

            padding: 24px;

            background: #f8fafc;

            border-bottom: 1px solid var(--border);

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;
        }


        .patient-heading {

            display: flex;

            align-items: center;

            gap: 15px;
        }


        .patient-heading .avatar {

            width: 56px;
            height: 56px;

            border-radius: 15px;
        }


        .patient-heading h2 {

            font-size: 19px;

            margin-bottom: 5px;
        }


        .patient-heading p {

            color: var(--muted);

            font-size: 11px;
        }


        .blood-badge {

            padding: 9px 13px;

            background: #fef2f2;

            color: var(--danger);

            border-radius: 9px;

            font-size: 12px;

            font-weight: 800;
        }


        .patient-content {

            padding: 22px;
        }


        .patient-info-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 13px;

            margin-bottom: 22px;
        }


        .patient-info-box {

            background: #f8fafc;

            border-radius: 10px;

            padding: 13px;
        }


        .patient-info-box span {

            display: block;

            color: var(--muted);

            font-size: 10px;

            margin-bottom: 6px;
        }


        .patient-info-box strong {

            font-size: 12px;
        }


        .medical-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 15px;

            margin-bottom: 22px;
        }


        .medical-box {

            border: 1px solid var(--border);

            border-radius: 11px;

            padding: 17px;
        }


        .medical-box h4 {

            font-size: 12px;

            margin-bottom: 9px;
        }


        .medical-box p {

            color: #5e697a;

            font-size: 12px;

            line-height: 1.6;
        }


        /* =====================================================
           TABLE
        ===================================================== */

        .data-table {

            width: 100%;

            border-collapse: collapse;
        }


        .data-table th {

            text-align: left;

            padding: 12px 15px;

            font-size: 10px;

            color: var(--muted);

            text-transform: uppercase;

            letter-spacing: .5px;

            background: #fafbfc;

            border-bottom: 1px solid var(--border);
        }


        .data-table td {

            padding: 14px 15px;

            font-size: 11px;

            border-bottom: 1px solid #f0f2f6;

            vertical-align: top;
        }


        .data-table tr:last-child td {

            border-bottom: none;
        }


        .medicine-name {

            font-weight: 700;

            font-size: 12px;
        }


        .medicine-detail {

            color: var(--muted);

            margin-top: 3px;
        }


        /* =====================================================
           EMPTY
        ===================================================== */

        .empty {

            padding: 35px 20px;

            text-align: center;

            color: var(--muted);

            font-size: 12px;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        .mobile-menu {

            display: none;
        }


        @media (max-width: 1100px) {

            .stats {

                grid-template-columns:
                    repeat(2, 1fr);
            }

            .dashboard-grid {

                grid-template-columns: 1fr;
            }

            .patient-info-grid {

                grid-template-columns:
                    repeat(2, 1fr);
            }
        }


        @media (max-width: 760px) {

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

                padding: 0 17px;

                height: 70px;
            }


            .welcome h1 {

                font-size: 17px;
            }


            .welcome p {

                display: none;
            }


            .doctor-profile-text {

                display: none;
            }


            .content {

                padding: 20px 15px 90px;
            }


            .hero {

                padding: 22px;

                display: block;
            }


            .hero h2 {

                font-size: 21px;
            }


            .hero-badge {

                display: inline-block;

                margin-top: 18px;

                text-align: left;
            }


            .stats {

                grid-template-columns: 1fr 1fr;

                gap: 10px;
            }


            .stat-card {

                padding: 16px;
            }


            .stat-card h3 {

                font-size: 22px;
            }


            .stat-icon {

                width: 35px;
                height: 35px;

                font-size: 14px;
            }


            .search-form {

                flex-direction: column;
            }


            .search-button {

                width: 100%;
            }


            .patient-detail-header {

                align-items: flex-start;

                flex-direction: column;
            }


            .patient-info-grid {

                grid-template-columns: 1fr 1fr;
            }


            .medical-grid {

                grid-template-columns: 1fr;
            }


            .table-wrap {

                overflow-x: auto;
            }


            .mobile-menu {

                display: flex;

                position: fixed;

                left: 10px;
                right: 10px;
                bottom: 10px;

                height: 64px;

                background: white;

                border: 1px solid var(--border);

                border-radius: 16px;

                z-index: 200;

                box-shadow:
                    0 8px 30px rgba(15,23,42,.12);

                justify-content: space-around;

                align-items: center;
            }


            .mobile-menu a {

                text-align: center;

                color: var(--muted);

                font-size: 10px;

                font-weight: 600;
            }


            .mobile-menu a.active {

                color: var(--primary);
            }


            .mobile-menu i {

                display: block;

                font-size: 18px;

                margin-bottom: 3px;

                font-style: normal;
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


    <div class="doctor-label">
        Doctor Portal
    </div>


    <nav class="nav">

        <a
            href="doctor-dashboard.php"
            class="nav-item active"
        >
            <span class="nav-icon">⌂</span>
            Dashboard
        </a>


        <a
            href="#appointments"
            class="nav-item"
        >
            <span class="nav-icon">◷</span>
            Appointments
        </a>


        <a
            href="#patients"
            class="nav-item"
        >
            <span class="nav-icon">♙</span>
            My Patients
        </a>


        <a
            href="#meetings"
            class="nav-item"
        >
            <span class="nav-icon">▣</span>
            Last Meetings
        </a>


        <a
            href="#medicines"
            class="nav-item"
        >
            <span class="nav-icon">✚</span>
            Medicines
        </a>


        <a
            href="#profile"
            class="nav-item"
        >
            <span class="nav-icon">⚙</span>
            My Profile
        </a>

    </nav>


    <div class="sidebar-bottom">

        <div class="mini-doctor">

            <div class="avatar">
                <?= htmlspecialchars(initials($doctor['full_name'])) ?>
            </div>

            <div>

                <strong>
                    <?= htmlspecialchars($doctor['full_name']) ?>
                </strong>

                <small>
                    <?= htmlspecialchars($doctor['specialization'] ?: 'Doctor') ?>
                </small>

            </div>

        </div>

    </div>

</aside>


<!-- =========================================================
     MAIN
========================================================= -->

<main class="main">

	<a href="add-medical-record.php" class="add-record-btn25">
    + Add Medical Record
		</a>
    <!-- TOPBAR -->

    <header class="topbar">

        <div class="welcome">

            <h1>
                Doctor Dashboard
            </h1>

            <p>
                Manage your patients, appointments and clinical information.
            </p>

        </div>


        <div class="top-actions">

            <button class="icon-button">
                🔔
            </button>


            <div class="doctor-profile">

                <div class="avatar">

                    <?= htmlspecialchars(
                        initials($doctor['full_name'])
                    ) ?>

                </div>


                <div class="doctor-profile-text">

                    <strong>
                        <?= htmlspecialchars($doctor['full_name']) ?>
                    </strong>

                    <span>
                        <?= htmlspecialchars(
                            $doctor['specialization']
                            ?: 'Medical Doctor'
                        ) ?>
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
                    PatientCheck • Doctor Portal
                </small>

                <h2>
                    Good to see you,
                    <?= htmlspecialchars(
                        str_replace(
                            'Dr. ',
                            '',
                            $doctor['full_name']
                        )
                    ) ?>.
                </h2>

                <p>
                    Here's an overview of your patients,
                    appointments and recent clinical activity.
                </p>

            </div>


            <div class="hero-badge">

                <strong>
                    <?= htmlspecialchars(
                        $doctor['specialization']
                        ?: 'Medical Doctor'
                    ) ?>
                </strong>

                <span>
                    <?= htmlspecialchars(
                        $doctor['hospital_name']
                        ?: 'PatientCheck Network'
                    ) ?>
                </span>

            </div>

        </section>


        <!-- =================================================
             STATISTICS
        ================================================= -->

        <section class="stats">


            <div class="stat-card">

                <div>

                    <small>
                        Total Patients
                    </small>

                    <h3>
                        <?= (int)$total_patients ?>
                    </h3>

                </div>

                <div class="stat-icon">
                    ♙
                </div>

            </div>


            <div class="stat-card">

                <div>

                    <small>
                        Today's Appointments
                    </small>

                    <h3>
                        <?= (int)$today_appointments ?>
                    </h3>

                </div>

                <div class="stat-icon">
                    ◷
                </div>

            </div>


            <div class="stat-card">

                <div>

                    <small>
                        Upcoming
                    </small>

                    <h3>
                        <?= (int)$upcoming_appointments ?>
                    </h3>

                </div>

                <div class="stat-icon">
                    ▣
                </div>

            </div>


            <div class="stat-card">

                <div>

                    <small>
                        Active Medicines
                    </small>

                    <h3>
                        <?= (int)$active_medicines ?>
                    </h3>

                </div>

                <div class="stat-icon">
                    ✚
                </div>

            </div>


        </section>


        <!-- =================================================
             APPOINTMENTS + DOCTOR PROFILE
        ================================================= -->

        <section class="dashboard-grid" id="appointments">


            <div class="card">

                <div class="card-header">

                    <h3>
                        Upcoming Appointments
                    </h3>

                    <span>
                        Next scheduled visits
                    </span>

                </div>


                <?php if (count($upcoming) > 0): ?>

                    <?php foreach ($upcoming as $appointment): ?>

                        <div class="appointment">


                            <div class="appointment-avatar">

                                <?= htmlspecialchars(
                                    initials(
                                        $appointment['full_name']
                                    )
                                ) ?>

                            </div>


                            <div class="appointment-main">

                                <strong>

                                    <?= htmlspecialchars(
                                        $appointment['full_name']
                                    ) ?>

                                    <span class="status scheduled">
                                        Scheduled
                                    </span>

                                </strong>


                                <span>

                                    <?= htmlspecialchars(
                                        $appointment['appointment_type']
                                    ) ?>

                                    •

                                    <?= htmlspecialchars(
                                        $appointment['reason']
                                        ?: 'General consultation'
                                    ) ?>

                                </span>

                            </div>


                            <div class="appointment-time">

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
                        No upcoming appointments.
                    </div>

                <?php endif; ?>


            </div>


            <!-- DOCTOR PROFILE -->

            <div
                class="card"
                id="profile"
            >

                <div class="card-header">

                    <h3>
                        Doctor Profile
                    </h3>

                </div>


                <div class="profile-card">


                    <div class="large-profile">

                        <div class="avatar">

                            <?= htmlspecialchars(
                                initials(
                                    $doctor['full_name']
                                )
                            ) ?>

                        </div>


                        <div>

                            <h3>
                                <?= htmlspecialchars(
                                    $doctor['full_name']
                                ) ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars(
                                    $doctor['specialization']
                                    ?: 'Medical Doctor'
                                ) ?>
                            </p>

                        </div>

                    </div>


                    <div class="profile-info">


                        <div class="info-row">

                            <span>
                                Doctor ID
                            </span>

                            <span>
                                <?= htmlspecialchars(
                                    $doctor['doctor_code']
                                ) ?>
                            </span>

                        </div>


                        <div class="info-row">

                            <span>
                                Qualification
                            </span>

                            <span>
                                <?= htmlspecialchars(
                                    $doctor['qualification']
                                    ?: '—'
                                ) ?>
                            </span>

                        </div>


                        <div class="info-row">

                            <span>
                                Experience
                            </span>

                            <span>
                                <?= (int)$doctor[
                                    'experience_years'
                                ] ?> years
                            </span>

                        </div>


                        <div class="info-row">

                            <span>
                                License
                            </span>

                            <span>
                                <?= htmlspecialchars(
                                    $doctor['license_number']
                                    ?: '—'
                                ) ?>
                            </span>

                        </div>


                        <div class="info-row">

                            <span>
                                Hospital
                            </span>

                            <span>
                                <?= htmlspecialchars(
                                    $doctor['hospital_name']
                                    ?: '—'
                                ) ?>
                            </span>

                        </div>


                        <div class="info-row">

                            <span>
                                Phone
                            </span>

                            <span>
                                <?= htmlspecialchars(
                                    $doctor['phone']
                                    ?: '—'
                                ) ?>
                            </span>

                        </div>


                    </div>


                </div>

            </div>


        </section>


        <!-- =================================================
             PATIENT SEARCH
        ================================================= -->

        <section
            class="search-area"
            id="patients"
        >

            <div class="section-header">

                <div>

                    <h2>
                        Find a Patient
                    </h2>

                    <span>
                        Search patients associated with your appointments.
                    </span>

                </div>

            </div>


            <form
                method="GET"
                action="doctor-dashboard.php"
                class="search-form"
            >

                <input
                    type="text"
                    name="search"
                    class="search-input"
                    placeholder="Search by patient name, patient code or phone..."
                    value="<?= htmlspecialchars($search) ?>"
                >


                <button
                    type="submit"
                    class="search-button"
                >
                    Search Patient
                </button>

            </form>


            <?php if ($search !== ''): ?>

                <div class="patient-results">

                    <?php if (count($patients) > 0): ?>

                        <?php foreach ($patients as $patient): ?>

                            <div class="patient-result">


                                <div class="avatar">

                                    <?= htmlspecialchars(
                                        initials(
                                            $patient['full_name']
                                        )
                                    ) ?>

                                </div>


                                <div class="patient-result-info">

                                    <strong>
                                        <?= htmlspecialchars(
                                            $patient['full_name']
                                        ) ?>
                                    </strong>

                                    <span>

                                        <?= htmlspecialchars(
                                            $patient['patient_code']
                                        ) ?>

                                        •

                                        <?= calculateAge(
                                            $patient[
                                                'date_of_birth'
                                            ]
                                        ) ?> years

                                        •

                                        Blood Group:
                                        <?= htmlspecialchars(
                                            $patient['blood_group']
                                        ) ?>

                                    </span>

                                </div>


                                <a
                                    href="?patient_id=<?= (int)$patient['id'] ?>"
                                    class="view-button"
                                >
                                    View Patient
                                </a>


                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="empty">

                            No patient found for
                            "<strong>
                                <?= htmlspecialchars($search) ?>
                            </strong>".

                        </div>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </section>


        <!-- =================================================
             SELECTED PATIENT
        ================================================= -->

        <?php if ($selected_patient): ?>

            <section
                class="patient-detail"
                id="patient-detail"
            >


                <div class="patient-detail-header">


                    <div class="patient-heading">

                        <div class="avatar">

                            <?= htmlspecialchars(
                                initials(
                                    $selected_patient[
                                        'full_name'
                                    ]
                                )
                            ) ?>

                        </div>


                        <div>

                            <h2>
                                <?= htmlspecialchars(
                                    $selected_patient[
                                        'full_name'
                                    ]
                                ) ?>
                            </h2>

                            <p>

                                Patient ID:
                                <?= htmlspecialchars(
                                    $selected_patient[
                                        'patient_code'
                                    ]
                                ) ?>

                                •

                                <?= calculateAge(
                                    $selected_patient[
                                        'date_of_birth'
                                    ]
                                ) ?> years old

                            </p>

                        </div>

                    </div>


                    <div class="blood-badge">

                        Blood Group:
                        <?= htmlspecialchars(
                            $selected_patient[
                                'blood_group'
                            ]
                        ) ?>

                    </div>


                </div>


                <div class="patient-content">


                    <!-- BASIC INFORMATION -->

                    <div class="patient-info-grid">


                        <div class="patient-info-box">

                            <span>
                                Gender
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    ucfirst(
                                        $selected_patient[
                                            'gender'
                                        ] ?: '—'
                                    )
                                ) ?>
                            </strong>

                        </div>


                        <div class="patient-info-box">

                            <span>
                                Date of Birth
                            </span>

                            <strong>

                                <?= $selected_patient[
                                    'date_of_birth'
                                ]
                                    ? formatDate(
                                        $selected_patient[
                                            'date_of_birth'
                                        ]
                                    )
                                    : '—'
                                ?>

                            </strong>

                        </div>


                        <div class="patient-info-box">

                            <span>
                                Height
                            </span>

                            <strong>

                                <?= $selected_patient[
                                    'height_cm'
                                ]
                                    ? htmlspecialchars(
                                        $selected_patient[
                                            'height_cm'
                                        ]
                                    ) . " cm"
                                    : '—'
                                ?>

                            </strong>

                        </div>


                        <div class="patient-info-box">

                            <span>
                                Weight
                            </span>

                            <strong>

                                <?= $selected_patient[
                                    'weight_kg'
                                ]
                                    ? htmlspecialchars(
                                        $selected_patient[
                                            'weight_kg'
                                        ]
                                    ) . " kg"
                                    : '—'
                                ?>

                            </strong>

                        </div>


                    </div>


                    <!-- MEDICAL INFORMATION -->

                    <div class="medical-grid">


                        <div class="medical-box">

                            <h4>
                                Medical Conditions
                            </h4>

                            <p>

                                <?= htmlspecialchars(
                                    $selected_patient[
                                        'medical_conditions'
                                    ] ?: 'No conditions recorded.'
                                ) ?>

                            </p>

                        </div>


                        <div class="medical-box">

                            <h4>
                                Allergies
                            </h4>

                            <p>

                                <?= htmlspecialchars(
                                    $selected_patient[
                                        'allergies'
                                    ] ?: 'No known allergies.'
                                ) ?>

                            </p>

                        </div>


                    </div>


                    <!-- MEDICINES -->

                    <div
                        class="card"
                        id="medicines"
                    >

                        <div class="card-header">

                            <h3>
                                Current & Previous Medicines
                            </h3>

                            <span>
                                <?= count(
                                    $patient_medicines
                                ) ?> records
                            </span>

                        </div>


                        <?php if (
                            count($patient_medicines) > 0
                        ): ?>

                            <div class="table-wrap">

                                <table class="data-table">

                                    <thead>

                                        <tr>

                                            <th>
                                                Medicine
                                            </th>

                                            <th>
                                                Dosage
                                            </th>

                                            <th>
                                                Frequency
                                            </th>

                                            <th>
                                                Timing
                                            </th>

                                            <th>
                                                Status
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        <?php foreach (
                                            $patient_medicines
                                            as $medicine
                                        ): ?>

                                            <tr>

                                                <td>

                                                    <div
                                                        class="medicine-name"
                                                    >

                                                        <?= htmlspecialchars(
                                                            $medicine[
                                                                'medicine_name'
                                                            ]
                                                        ) ?>

                                                    </div>

                                                    <div
                                                        class="medicine-detail"
                                                    >

                                                        Prescribed by
                                                        <?= htmlspecialchars(
                                                            $medicine[
                                                                'doctor_name'
                                                            ] ?: 'Doctor'
                                                        ) ?>

                                                    </div>

                                                </td>


                                                <td>

                                                    <?= htmlspecialchars(
                                                        $medicine[
                                                            'dosage'
                                                        ] ?: '—'
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?= htmlspecialchars(
                                                        $medicine[
                                                            'frequency'
                                                        ] ?: '—'
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?= htmlspecialchars(
                                                        $medicine[
                                                            'timing'
                                                        ] ?: '—'
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?php if (
                                                        $medicine[
                                                            'status'
                                                        ] === 'active'
                                                    ): ?>

                                                        <span
                                                            class="status completed"
                                                        >
                                                            Active
                                                        </span>

                                                    <?php elseif (
                                                        $medicine[
                                                            'status'
                                                        ] === 'completed'
                                                    ): ?>

                                                        <span
                                                            class="status completed"
                                                        >
                                                            Completed
                                                        </span>

                                                    <?php else: ?>

                                                        <span
                                                            class="status cancelled"
                                                        >
                                                            Stopped
                                                        </span>

                                                    <?php endif; ?>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>

                        <?php else: ?>

                            <div class="empty">
                                No medicines recorded.
                            </div>

                        <?php endif; ?>


                    </div>


                    <!-- APPOINTMENT HISTORY -->

                    <div
                        class="card"
                        id="meetings"
                        style="margin-top:20px;"
                    >

                        <div class="card-header">

                            <h3>
                                Appointment & Meeting History
                            </h3>

                            <span>
                                Patient timeline
                            </span>

                        </div>


                        <?php if (
                            count($patient_meetings) > 0
                        ): ?>

                            <div class="table-wrap">

                                <table class="data-table">

                                    <thead>

                                        <tr>

                                            <th>
                                                Date
                                            </th>

                                            <th>
                                                Type
                                            </th>

                                            <th>
                                                Reason
                                            </th>

                                            <th>
                                                Diagnosis
                                            </th>

                                            <th>
                                                Status
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        <?php foreach (
                                            $patient_meetings
                                            as $meeting
                                        ): ?>

                                            <tr>

                                                <td>

                                                    <?= formatDateTime(
                                                        $meeting[
                                                            'appointment_date'
                                                        ]
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?= htmlspecialchars(
                                                        ucfirst(
                                                            str_replace(
                                                                '_',
                                                                ' ',
                                                                $meeting[
                                                                    'appointment_type'
                                                                ]
                                                            )
                                                        )
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?= htmlspecialchars(
                                                        $meeting[
                                                            'reason'
                                                        ] ?: '—'
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?= htmlspecialchars(
                                                        $meeting[
                                                            'diagnosis'
                                                        ] ?: '—'
                                                    ) ?>

                                                </td>


                                                <td>

                                                    <?php

                                                    $status =
                                                        $meeting[
                                                            'status'
                                                        ];

                                                    $statusClass =
                                                        $status ===
                                                        'completed'
                                                            ? 'completed'
                                                            : (
                                                                $status ===
                                                                'cancelled'
                                                                    ? 'cancelled'
                                                                    : 'scheduled'
                                                            );

                                                    ?>

                                                    <span
                                                        class="status <?= $statusClass ?>"
                                                    >

                                                        <?= htmlspecialchars(
                                                            ucfirst(
                                                                $status
                                                            )
                                                        ) ?>

                                                    </span>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>

                        <?php else: ?>

                            <div class="empty">

                                No meeting history found.

                            </div>

                        <?php endif; ?>


                    </div>


                </div>


            </section>

        <?php endif; ?>


        <!-- =================================================
             RECENT MEETINGS
        ================================================= -->

        <section
            class="card"
            style="margin-top:28px;"
        >

            <div class="card-header">

                <h3>
                    Recent Patient Meetings
                </h3>

                <span>
                    Latest completed consultations
                </span>

            </div>


            <?php if (count($recent_meetings) > 0): ?>

                <?php foreach (
                    $recent_meetings
                    as $meeting
                ): ?>

                    <div class="appointment">


                        <div class="appointment-avatar">

                            <?= htmlspecialchars(
                                initials(
                                    $meeting[
                                        'full_name'
                                    ]
                                )
                            ) ?>

                        </div>


                        <div class="appointment-main">

                            <strong>

                                <?= htmlspecialchars(
                                    $meeting[
                                        'full_name'
                                    ]
                                ) ?>

                                <span
                                    class="status completed"
                                >
                                    Completed
                                </span>

                            </strong>


                            <span>

                                <?= htmlspecialchars(
                                    $meeting[
                                        'diagnosis'
                                    ]
                                    ?: $meeting[
                                        'reason'
                                    ]
                                    ?: 'Patient consultation'
                                ) ?>

                            </span>

                        </div>


                        <div class="appointment-time">

                            <strong>
                                <?= date(
                                    "d M",
                                    strtotime(
                                        $meeting[
                                            'appointment_date'
                                        ]
                                    )
                                ) ?>
                            </strong>

                            <span>
                                <?= date(
                                    "Y",
                                    strtotime(
                                        $meeting[
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
                    No recent meetings.
                </div>

            <?php endif; ?>


        </section>


    </div>

</main>


<!-- =========================================================
     MOBILE NAVIGATION
========================================================= -->

<nav class="mobile-menu">

    <a
        href="doctor-dashboard.php"
        class="active"
    >

        <i>⌂</i>
        Home

    </a>


    <a href="#appointments">

        <i>◷</i>
        Appointments

    </a>


    <a href="#patients">

        <i>♙</i>
        Patients

    </a>


    <a href="#meetings">

        <i>▣</i>
        Meetings

    </a>


    <a href="#medicines">

        <i>✚</i>
        Medicines

    </a>

</nav>


</body>

</html>