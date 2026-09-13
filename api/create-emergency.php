<?php

session_start();

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| CHECK PATIENT LOGIN
|--------------------------------------------------------------------------
*/

// if (!isset($_SESSION['patient_id'])) {

//     die("Patient is not logged in.");

// }

$patient_id = 1;


/*
|--------------------------------------------------------------------------
| GET PATIENT INFORMATION
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        patient_code,
        full_name,
        phone,
        blood_group,
        allergies,
        medical_conditions
    FROM patients
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$patient_id]);

$patient = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$patient) {

    die("Patient information not found.");

}


/*
|--------------------------------------------------------------------------
| GET PATIENT LOCATION
|--------------------------------------------------------------------------
|
| The browser sends location through JavaScript.
| For this simple version we first try to receive it
| through POST/session.
|
*/

$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;
$accuracy = $_POST['accuracy'] ?? null;


/*
|--------------------------------------------------------------------------
| GENERATE UNIQUE EMERGENCY CODE
|--------------------------------------------------------------------------
*/

$emergency_code =
    "ER-" .
    date("Ymd-His") .
    "-" .
    strtoupper(bin2hex(random_bytes(3)));


/*
|--------------------------------------------------------------------------
| FIND ALL ACTIVE HOSPITALS
|--------------------------------------------------------------------------
*/

$hospitalStmt = $pdo->prepare("
    SELECT
        id,
        hospital_code,
        hospital_name,
        phone,
        email
    FROM hospitals
    WHERE status = 'active'
    AND emergency_available = 1
    ORDER BY id ASC
");

$hospitalStmt->execute();

$hospitals = $hospitalStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| NO HOSPITAL AVAILABLE
|--------------------------------------------------------------------------
*/

if (!$hospitals) {

    die("
        <h2>No emergency hospitals are currently available.</h2>
        <p>Please contact emergency services directly.</p>
    ");

}


/*
|--------------------------------------------------------------------------
| CREATE EMERGENCY REQUESTS
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();


    $insert = $pdo->prepare("
        INSERT INTO emergency_requests
        (
            emergency_code,
            hospital_request_code,
            patient_id,
            hospital_id,
            emergency_type,
            emergency_description,
            patient_location,
            latitude,
            longitude,
            request_time,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            NOW(),
            'pending'
        )
    ");


    foreach ($hospitals as $hospital) {

        $hospital_id = (int) $hospital['id'];


        /*
        |--------------------------------------------------------------------------
        | UNIQUE REQUEST FOR EACH HOSPITAL
        |--------------------------------------------------------------------------
        */

        $hospital_request_code =
            $emergency_code .
            "-H" .
            $hospital_id;


        /*
        |--------------------------------------------------------------------------
        | LOCATION TEXT
        |--------------------------------------------------------------------------
        */

        $patient_location = null;

        if (
            $latitude !== null &&
            $longitude !== null
        ) {

            $patient_location =
                $latitude . ", " . $longitude;

        }


        /*
        |--------------------------------------------------------------------------
        | INSERT REQUEST
        |--------------------------------------------------------------------------
        */

        $insert->execute([

            $emergency_code,

            $hospital_request_code,

            $patient_id,

            $hospital_id,

            "Medical Emergency",

            "Patient has activated emergency SOS.",

            $patient_location,

            $latitude,

            $longitude

        ]);

    }


    /*
    |--------------------------------------------------------------------------
    | EVERYTHING SUCCESSFUL
    |--------------------------------------------------------------------------
    */

    $pdo->commit();


}
catch (Exception $e) {

    /*
    |--------------------------------------------------------------------------
    | ROLLBACK IF SOMETHING FAILS
    |--------------------------------------------------------------------------
    */

    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    die("
        <h2>Unable to send emergency request.</h2>
        <p>Please try again.</p>
    ");

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Emergency Request Sent</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #fff1f2,
                    #ffffff
                );
        }

        .emergency-card {

            width: min(520px, 92%);

            background: #ffffff;

            border-radius: 24px;

            padding: 45px 35px;

            text-align: center;

            box-shadow:
                0 20px 60px
                rgba(0,0,0,0.12);
        }

        .success-icon {

            width: 100px;
            height: 100px;

            margin: 0 auto 25px;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 45px;

            background: #dcfce7;

            border: 8px solid #bbf7d0;
        }

        h1 {

            margin: 0 0 12px;

            color: #166534;

            font-size: 30px;
        }

        .message {

            color: #475569;

            font-size: 16px;

            line-height: 1.6;
        }

        .emergency-code {

            margin: 25px 0;

            padding: 18px;

            border-radius: 14px;

            background: #f8fafc;

            border: 1px solid #e2e8f0;
        }

        .emergency-code span {

            display: block;

            font-size: 12px;

            font-weight: bold;

            color: #64748b;

            text-transform: uppercase;

            letter-spacing: 1px;

            margin-bottom: 7px;
        }

        .emergency-code strong {

            font-size: 20px;

            color: #0f172a;

            letter-spacing: 1px;
        }

        .hospital-count {

            color: #334155;

            font-weight: 600;

            margin-bottom: 25px;
        }

        .back-button {

            display: inline-block;

            padding: 14px 25px;

            border-radius: 12px;

            background: #dc2626;

            color: white;

            text-decoration: none;

            font-weight: bold;

        }

        .back-button:hover {

            background: #b91c1c;

        }

    </style>

</head>

<body>

<div class="emergency-card">

    <div class="success-icon">
        ✓
    </div>

    <h1>
        Emergency Request Sent
    </h1>

    <p class="message">

        Your emergency request has been sent
        to the available hospitals.

        <br>

        Hospitals are being notified
        and can respond to your emergency.

    </p>


    <div class="emergency-code">

        <span>
            Emergency ID
        </span>

        <strong>
            <?= htmlspecialchars($emergency_code) ?>
        </strong>

    </div>


    <div class="hospital-count">

        Request sent to
        <?= count($hospitals) ?>
        hospital(s)

    </div>


    <a
        href="patient-dashboard.php"
        class="back-button"
    >
        Return to Dashboard
    </a>

</div>

</body>

</html>