<?php

require_once "config/database.php";
require_once "includes/helpers.php";

session_start();

if (!isset($_SESSION['hospital_id'])) {
    header("Location: hospital-login.php");
    exit;
}

$hospital_id = (int) $_SESSION['hospital_id'];

$stmt = $pdo->prepare("SELECT * FROM hospitals WHERE id = ? LIMIT 1");
$stmt->execute([$hospital_id]);
$hospital = $stmt->fetch();

if (!$hospital) {
    session_destroy();
    header("Location: hospital-login.php");
    exit;
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
        Emergency Control Center | PatientCheck
    </title>


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"
    >


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        :root {

            --primary: #dc2626;
            --primary-dark: #b91c1c;

            --navy: #172554;
            --blue: #2563eb;

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


        button {
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

            font-size: 18px;

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

            background: var(--danger-bg);
        }


        .nav-item.active {

            color: var(--primary);

            background: var(--danger-bg);
        }


        .nav-item.logout-item {
            color: var(--danger);
        }


        .nav-icon {

            width: 22px;

            text-align: center;

            font-size: 17px;
        }


        .sidebar-status {

            margin: 0 12px 14px;

            padding: 12px 13px;

            border-radius: 10px;

            background: var(--success-bg);

            color: var(--success);

            font-size: 11px;

            font-weight: 700;

            display: flex;

            align-items: center;

            gap: 8px;
        }


        .pulse-dot {

            width: 8px;
            height: 8px;

            border-radius: 50%;

            background: var(--success);

            animation: pulseDot 1.6s ease-in-out infinite;

            flex-shrink: 0;
        }


        @keyframes pulseDot {

            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .4; transform: scale(.7); }
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

            background: var(--danger-bg);

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


        .badge-count {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-width: 22px;

            height: 22px;

            padding: 0 6px;

            border-radius: 999px;

            background: var(--primary);

            color: white;

            font-size: 11px;

            font-weight: 800;

            margin-left: 8px;
        }


        .content {

            padding: 28px 32px 55px;
        }


        .section-title {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 16px;
        }


        .section-title h2 {

            font-size: 16px;

            letter-spacing: -.3px;
        }


        .section-title span {

            color: var(--muted);

            font-size: 11px;
        }


        /* =====================================================
           REQUEST CARDS
        ===================================================== */

        .requests-grid {

            display: grid;

            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));

            gap: 18px;

            margin-bottom: 34px;
        }


        .request-card {

            background: white;

            border: 1px solid var(--border);

            border-radius: 16px;

            overflow: hidden;

            position: relative;

            animation: cardIn .25s ease;
        }


        @keyframes cardIn {

            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }


        .request-card.pending {
            border-color: #fecaca;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.06);
        }


        .request-card.accepted {
            border-color: #bbf7d0;
        }


        .request-head {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 16px 18px;

            border-bottom: 1px solid var(--border);
        }


        .request-avatar {

            width: 44px;
            height: 44px;

            border-radius: 12px;

            background: var(--danger-bg);

            color: var(--primary);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 14px;

            font-weight: 800;

            flex-shrink: 0;
        }


        .request-head-main {
            flex: 1;
            min-width: 0;
        }


        .request-head-main strong {

            display: block;

            font-size: 13px;

            margin-bottom: 3px;
        }


        .request-head-main span {

            color: var(--muted);

            font-size: 10px;
        }


        .live-tag {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            font-size: 9px;

            font-weight: 800;

            color: var(--primary);

            text-transform: uppercase;

            letter-spacing: .4px;
        }


        .live-tag .pulse-dot {
            background: var(--primary);
        }


        .request-map {

            height: 160px;

            width: 100%;

            background: #eef1f6;
        }


        .request-body {

            padding: 16px 18px;
        }


        .request-row {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 12px;

            padding-bottom: 10px;

            margin-bottom: 10px;

            border-bottom: 1px solid #f0f2f6;

            font-size: 11px;
        }


        .request-row:last-of-type {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }


        .request-row span:first-child {
            color: var(--muted);
            flex-shrink: 0;
        }


        .request-row span:last-child {
            font-weight: 600;
            text-align: right;
        }


        .request-row .blood {
            color: var(--danger);
            font-weight: 800;
        }


        .request-actions {

            display: flex;

            gap: 10px;

            padding: 14px 18px 18px;
        }


        .btn {

            flex: 1;

            padding: 11px;

            border: none;

            border-radius: 9px;

            font-size: 12px;

            font-weight: 700;

            cursor: pointer;

            transition: .15s;
        }


        .btn-accept {

            background: var(--success);

            color: white;
        }


        .btn-accept:hover {
            background: #15803d;
        }


        .btn-reject {

            background: white;

            color: var(--danger);

            border: 1px solid #fecaca;
        }


        .btn-reject:hover {
            background: var(--danger-bg);
        }


        .btn:disabled {
            opacity: .55;
            cursor: not-allowed;
        }


        .btn-call {

            display: block;

            width: 100%;

            margin: 0 18px 18px;

            width: calc(100% - 36px);

            text-align: center;

            padding: 11px;

            border-radius: 9px;

            background: var(--blue);

            color: white;

            font-size: 12px;

            font-weight: 700;
        }


        .status-pill {

            position: absolute;

            top: 14px;

            right: 14px;

            font-size: 9px;

            font-weight: 800;

            padding: 4px 8px;

            border-radius: 6px;

            text-transform: uppercase;

            letter-spacing: .3px;
        }


        .status-pill.accepted {
            background: var(--success-bg);
            color: var(--success);
        }


        .empty-state {

            background: white;

            border: 1px dashed var(--border);

            border-radius: 16px;

            padding: 40px 20px;

            text-align: center;

            color: var(--muted);

            font-size: 12px;

            margin-bottom: 34px;
        }


        .empty-state .empty-icon {

            font-size: 28px;

            margin-bottom: 10px;
        }


        /* =====================================================
           TOAST
        ===================================================== */

        .toast-stack {

            position: fixed;

            top: 20px;

            right: 20px;

            z-index: 9999;

            display: flex;

            flex-direction: column;

            gap: 10px;
        }


        .toast {

            background: var(--navy);

            color: white;

            padding: 13px 17px;

            border-radius: 10px;

            font-size: 12px;

            font-weight: 600;

            box-shadow: 0 12px 30px rgba(15,23,42,.2);

            max-width: 300px;

            animation: toastIn .2s ease;
        }


        .toast.danger { background: var(--primary); }
        .toast.success { background: var(--success); }


        @keyframes toastIn {

            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

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
                height: 70px;
                padding: 0 16px;
            }

            .content {
                padding: 18px 14px 60px;
            }

            .requests-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<div class="toast-stack" id="toastStack"></div>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar">


    <div class="logo">

        <div class="logo-icon">
            H
        </div>

        <div class="logo-text">
            Patient<span>Check</span>
        </div>

    </div>


    <div class="portal-label">
        Hospital Portal
    </div>


    <nav class="nav">

        <a href="hospital-dashboard.php" class="nav-item active">
            <span class="nav-icon">🚨</span>
            Emergency Center
        </a>

        <a href="hospital-logout.php" class="nav-item logout-item">
            <span class="nav-icon">⏻</span>
            Logout
        </a>

    </nav>


    <div class="sidebar-status">
        <span class="pulse-dot"></span>
        Listening for SOS requests
    </div>


    <div class="sidebar-profile">

        <div class="mini-profile">

            <div class="avatar">
                <?= safe(initials($hospital['hospital_name'])) ?>
            </div>

            <div>
                <strong><?= safe($hospital['hospital_name']) ?></strong>
                <span><?= safe($hospital['hospital_code']) ?></span>
            </div>

        </div>

    </div>


</aside>


<!-- =========================================================
     MAIN
========================================================= -->

<main class="main">


    <header class="topbar">

        <div>
            <h1>Emergency Control Center</h1>
            <p>Live SOS requests from patients near you.</p>
        </div>

        <div class="mini-profile" style="gap:9px;">
            <div class="avatar" style="width:38px;height:38px;">
                <?= safe(initials($hospital['hospital_name'])) ?>
            </div>
            <div>
                <strong style="font-size:12px;"><?= safe($hospital['hospital_name']) ?></strong>
            </div>
        </div>

    </header>


    <div class="content">


        <!-- INCOMING REQUESTS -->

        <div class="section-title">
            <h2>
                Incoming Requests
                <span class="badge-count" id="pendingCount">0</span>
            </h2>
            <span id="pendingHint">Waiting for new emergencies…</span>
        </div>

        <div id="pendingContainer">
            <div class="empty-state" id="pendingEmpty">
                <div class="empty-icon">📡</div>
                No incoming emergency requests right now.
            </div>
            <div class="requests-grid" id="pendingGrid"></div>
        </div>


        <!-- ACCEPTED / ACTIVE -->

        <div class="section-title">
            <h2>
                Active Emergencies
                <span class="badge-count" id="acceptedCount">0</span>
            </h2>
            <span>Requests your hospital has accepted</span>
        </div>

        <div id="acceptedContainer">
            <div class="empty-state" id="acceptedEmpty">
                <div class="empty-icon">🚑</div>
                No active emergencies at the moment.
            </div>
            <div class="requests-grid" id="acceptedGrid"></div>
        </div>


    </div>


</main>


<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<script>

const POLL_INTERVAL_MS = 5000;

const maps = {};        // id -> { map, marker }
const knownPendingIds = new Set();

function showToast(message, type = "") {

    const stack = document.getElementById("toastStack");
    const toast = document.createElement("div");

    toast.className = "toast " + type;
    toast.textContent = message;

    stack.appendChild(toast);

    setTimeout(() => toast.remove(), 5000);
}


function timeAgoLabel(row) {
    return row.location_updated_ago || row.requested_ago || "—";
}


function buildMap(elementId, lat, lng) {

    const map = L.map(elementId, {
        zoomControl: false,
        attributionControl: false,
        dragging: true,
        scrollWheelZoom: false
    }).setView([lat, lng], 15);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19
    }).addTo(map);

    const icon = L.divIcon({
        className: "",
        html: '<div style="width:18px;height:18px;border-radius:50%;background:#dc2626;border:3px solid white;box-shadow:0 0 0 4px rgba(220,38,38,.25);"></div>',
        iconSize: [18, 18],
        iconAnchor: [9, 9]
    });

    const marker = L.marker([lat, lng], { icon }).addTo(map);

    return { map, marker };
}


function requestCardHtml(row, mode) {

    const initials = row.initials || "?";
    const hasLocation = row.latitude !== null && row.longitude !== null;
    const mapId = "map-" + mode + "-" + row.id;

    const actions = mode === "pending"
        ? `
            <div class="request-actions">
                <button class="btn btn-accept" onclick="respond(${row.id}, 'accept', this)">
                    ✓ Accept
                </button>
                <button class="btn btn-reject" onclick="respond(${row.id}, 'reject', this)">
                    ✕ Reject
                </button>
            </div>
        `
        : `
            <a class="btn-call" href="tel:${row.phone}">
                📞 Call Patient
            </a>
        `;

    const statusPill = mode === "accepted"
        ? `<div class="status-pill accepted">Accepted</div>`
        : "";

    return `
        <div class="request-card ${mode}" id="card-${mode}-${row.id}">

            ${statusPill}

            <div class="request-head">
                <div class="request-avatar">${initials}</div>
                <div class="request-head-main">
                    <strong>${row.patient_name} <span style="color:#94a3b8;font-weight:600;">(${row.patient_code})</span></strong>
                    <span>${row.patient_age} yrs • ${row.patient_gender} • Requested ${row.requested_ago}</span>
                </div>
            </div>

            ${hasLocation
                ? `<div class="request-map" id="${mapId}"></div>
                   <div style="padding:8px 18px 0;">
                        <span class="live-tag"><span class="pulse-dot"></span>
                        Location updated ${timeAgoLabel(row)}</span>
                   </div>`
                : `<div style="padding:16px 18px 0;color:#94a3b8;font-size:11px;">Waiting for GPS location…</div>`
            }

            <div class="request-body">

                <div class="request-row">
                    <span>Blood Group</span>
                    <span class="blood">${row.blood_group}</span>
                </div>

                <div class="request-row">
                    <span>Allergies</span>
                    <span>${row.allergies}</span>
                </div>

                <div class="request-row">
                    <span>Medical Conditions</span>
                    <span>${row.medical_conditions}</span>
                </div>

                <div class="request-row">
                    <span>Patient Phone</span>
                    <span>${row.phone}</span>
                </div>

                <div class="request-row">
                    <span>Emergency Contact</span>
                    <span>${row.emergency_contact_name}${row.emergency_contact_phone ? " • " + row.emergency_contact_phone : ""}</span>
                </div>

            </div>

            ${actions}

        </div>
    `;
}


function renderSection(rows, mode, gridId, emptyId, countId) {

    const grid = document.getElementById(gridId);
    const empty = document.getElementById(emptyId);
    const countEl = document.getElementById(countId);

    countEl.textContent = rows.length;

    if (rows.length === 0) {
        grid.innerHTML = "";
        empty.style.display = "block";
        return;
    }

    empty.style.display = "none";

    grid.innerHTML = rows.map(row => requestCardHtml(row, mode)).join("");

    rows.forEach(row => {

        if (row.latitude === null || row.longitude === null) {
            return;
        }

        const mapKey = mode + "-" + row.id;
        const mapId = "map-" + mapKey;

        // Leaflet needs the container in the DOM before init.
        setTimeout(() => {

            if (maps[mapKey]) {
                maps[mapKey].map.remove();
            }

            const built = buildMap(mapId, row.latitude, row.longitude);
            maps[mapKey] = built;

        }, 0);
    });
}


async function refresh() {

    try {

        const res = await fetch("get-emergency-requests.php", { cache: "no-store" });
        const data = await res.json();

        if (!data.success) {
            return;
        }

        // Notify on brand-new pending requests
        data.pending.forEach(row => {
            if (!knownPendingIds.has(row.id)) {
                knownPendingIds.add(row.id);
            }
        });

        renderSection(data.pending, "pending", "pendingGrid", "pendingEmpty", "pendingCount");
        renderSection(data.accepted, "accepted", "acceptedGrid", "acceptedEmpty", "acceptedCount");

        document.getElementById("pendingHint").textContent =
            data.pending.length > 0
                ? "Please respond as soon as possible."
                : "Waiting for new emergencies…";

    } catch (err) {
        // Silent fail on a single poll; will retry on next interval.
        console.error("Poll failed:", err);
    }
}


async function respond(id, action, buttonEl) {

    const card = buttonEl.closest(".request-card");
    const buttons = card.querySelectorAll("button");
    buttons.forEach(b => b.disabled = true);

    try {

        const formData = new FormData();
        formData.append("id", id);
        formData.append("action", action);

        const res = await fetch("hospital-respond.php", {
            method: "POST",
            body: formData
        });

        const data = await res.json();

        if (data.success) {

            showToast(
                action === "accept"
                    ? "Emergency accepted. Patient has been notified."
                    : "Request rejected.",
                action === "accept" ? "success" : ""
            );

        } else {

            showToast(data.message || "This request was already handled.", "danger");
        }

    } catch (err) {

        showToast("Network error — please try again.", "danger");
        buttons.forEach(b => b.disabled = false);
    }

    refresh();
}


refresh();
setInterval(refresh, POLL_INTERVAL_MS);

</script>


</body>

</html>
