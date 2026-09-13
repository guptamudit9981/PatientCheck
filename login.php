<?php

require_once "config/database.php";

session_start();

/*
|--------------------------------------------------------------------------
| IF ALREADY LOGGED IN, SKIP LOGIN
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['patient_id'])) {
    header("Location: patient-dashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| HANDLE LOGIN SUBMISSION
|--------------------------------------------------------------------------
*/

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $patient_code = trim($_POST['patient_code'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $dob          = trim($_POST['date_of_birth'] ?? '');

    if ($patient_code === '' || $phone === '' || $dob === '') {

        $error = "Please fill in all fields.";

    } else {

        $stmt = $pdo->prepare("
            SELECT *
            FROM patients
            WHERE patient_code = ?
            AND phone = ?
            AND date_of_birth = ?
            LIMIT 1
        ");

        $stmt->execute([
            $patient_code,
            $phone,
            $dob
        ]);

        $patient = $stmt->fetch();

        if ($patient) {

            // Prevent session fixation
            session_regenerate_id(true);

            $_SESSION['patient_id']   = $patient['id'];
            $_SESSION['patient_code'] = $patient['patient_code'];

            header("Location: patient-dashboard.php");
            exit;

        } else {

            $error = "The Patient ID, mobile number and date of birth do not match our records.";

        }
    }
}


function safe($value)
{
    return htmlspecialchars(
        $value ?? "",
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
        Login | PatientCheck
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

            --danger: #dc2626;
            --danger-bg: #fef2f2;
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

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 24px;
        }


        a {
            text-decoration: none;
            color: inherit;
        }


        /* =====================================================
           WRAPPER
        ===================================================== */

        .login-wrapper {

            width: 100%;

            max-width: 900px;

            background: var(--card);

            border: 1px solid var(--border);

            border-radius: 20px;

            overflow: hidden;

            display: grid;

            grid-template-columns: 1fr 1fr;

            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.08);
        }


        /* =====================================================
           LEFT PANEL / BRAND
        ===================================================== */

        .brand-panel {

            background: linear-gradient(
                135deg,
                #172554,
                #2563eb
            );

            color: white;

            padding: 44px 38px;

            position: relative;

            overflow: hidden;

            display: flex;

            flex-direction: column;

            justify-content: space-between;
        }


        .brand-panel::after {

            content: "";

            position: absolute;

            width: 280px;
            height: 280px;

            border-radius: 50%;

            border: 50px solid rgba(255, 255, 255, .05);

            right: -100px;

            bottom: -120px;
        }


        .brand-top {
            position: relative;
            z-index: 2;
        }


        .brand-logo {

            display: flex;

            align-items: center;

            gap: 11px;

            margin-bottom: 34px;
        }


        .brand-logo-icon {

            width: 42px;
            height: 42px;

            background: rgba(255, 255, 255, .16);

            border: 1px solid rgba(255, 255, 255, .22);

            color: white;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;

            font-weight: 800;
        }


        .brand-logo-text {

            font-size: 20px;

            font-weight: 800;

            letter-spacing: -.6px;
        }


        .brand-logo-text span {
            color: #bfdbfe;
        }


        .brand-top h2 {

            font-size: 26px;

            letter-spacing: -.6px;

            margin-bottom: 12px;

            line-height: 1.3;
        }


        .brand-top p {

            color: #dbeafe;

            font-size: 13px;

            line-height: 1.7;

            max-width: 320px;
        }


        .brand-features {

            position: relative;

            z-index: 2;

            display: grid;

            gap: 14px;

            margin-top: 30px;
        }


        .brand-feature {

            display: flex;

            align-items: center;

            gap: 10px;

            font-size: 12px;

            color: #e0e9ff;
        }


        .brand-feature-icon {

            width: 26px;
            height: 26px;

            border-radius: 8px;

            background: rgba(255, 255, 255, .14);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 12px;

            flex-shrink: 0;
        }


        /* =====================================================
           RIGHT PANEL / FORM
        ===================================================== */

        .form-panel {

            padding: 46px 42px;

            display: flex;

            flex-direction: column;

            justify-content: center;
        }


        .form-panel h1 {

            font-size: 23px;

            letter-spacing: -.5px;

            margin-bottom: 6px;
        }


        .form-panel > p {

            color: var(--muted);

            font-size: 12px;

            margin-bottom: 26px;
        }


        .field {

            margin-bottom: 17px;
        }


        .field label {

            display: block;

            font-size: 11px;

            font-weight: 700;

            color: #475569;

            text-transform: uppercase;

            letter-spacing: .4px;

            margin-bottom: 7px;
        }


        .field input {

            width: 100%;

            padding: 12px 14px;

            border: 1px solid var(--border);

            border-radius: 10px;

            font-size: 13px;

            color: var(--text);

            background: #f8fafc;

            transition: .15s;
        }


        .field input:focus {

            outline: none;

            border-color: var(--primary);

            background: white;

            box-shadow: 0 0 0 3px #dbeafe;
        }


        .field small {

            display: block;

            margin-top: 6px;

            color: var(--muted);

            font-size: 10px;
        }


        .login-button {

            width: 100%;

            padding: 13px;

            border: none;

            border-radius: 10px;

            background: var(--primary);

            color: white;

            font-size: 13px;

            font-weight: 700;

            cursor: pointer;

            margin-top: 6px;

            transition: .15s;
        }


        .login-button:hover {
            background: var(--primary-dark);
        }


        .error-box {

            display: flex;

            align-items: flex-start;

            gap: 9px;

            background: var(--danger-bg);

            border: 1px solid #fecaca;

            color: var(--danger);

            padding: 11px 13px;

            border-radius: 10px;

            font-size: 12px;

            margin-bottom: 18px;

            line-height: 1.5;
        }


        .form-footer {

            margin-top: 22px;

            text-align: center;

            font-size: 11px;

            color: var(--muted);
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 760px) {

            .login-wrapper {

                grid-template-columns: 1fr;

                max-width: 440px;
            }


            .brand-panel {

                padding: 32px 28px;
            }


            .brand-top h2 {
                font-size: 21px;
            }


            .brand-features {
                display: none;
            }


            .form-panel {

                padding: 32px 28px;
            }

        }

    </style>

</head>


<body>


    <div class="login-wrapper">


        <!-- LEFT / BRAND -->

        <div class="brand-panel">


            <div class="brand-top">

                <div class="brand-logo">

                    <div class="brand-logo-icon">
                        P
                    </div>

                    <div class="brand-logo-text">
                        Patient<span>Check</span>
                    </div>

                </div>


                <h2>
                    Your health information,
                    securely in one place.
                </h2>

                <p>
                    Sign in to view your medicines, appointments,
                    medical history and connect with your doctor.
                </p>

            </div>


            <div class="brand-features">

                <div class="brand-feature">
                    <span class="brand-feature-icon">✚</span>
                    Track your active medicines
                </div>

                <div class="brand-feature">
                    <span class="brand-feature-icon">◷</span>
                    View upcoming appointments
                </div>

                <div class="brand-feature">
                    <span class="brand-feature-icon">▣</span>
                    See your full medical timeline
                </div>

            </div>


        </div>


        <!-- RIGHT / FORM -->

        <div class="form-panel">


            <h1>
                Patient Login
            </h1>

            <p>
                Enter your details exactly as registered
                with your clinic.
            </p>


            <?php if ($error): ?>

                <div class="error-box">
                    ⚠ <?= safe($error) ?>
                </div>

            <?php endif; ?>


            <form method="POST" action="login.php" autocomplete="off">


                <div class="field">

                    <label for="patient_code">
                        Patient ID
                    </label>

                    <input
                        type="text"
                        id="patient_code"
                        name="patient_code"
                        placeholder="e.g. PAT-10001"
                        value="<?= safe($_POST['patient_code'] ?? '') ?>"
                        required
                    >

                </div>


                <div class="field">

                    <label for="phone">
                        Mobile Number
                    </label>

                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        placeholder="e.g. 9876500001"
                        value="<?= safe($_POST['phone'] ?? '') ?>"
                        required
                    >

                </div>


                <div class="field">

                    <label for="date_of_birth">
                        Date of Birth
                    </label>

                    <input
                        type="date"
                        id="date_of_birth"
                        name="date_of_birth"
                        value="<?= safe($_POST['date_of_birth'] ?? '') ?>"
                        required
                    >

                    <small>
                        As registered with the clinic (DD/MM/YYYY).
                    </small>

                </div>


                <button type="submit" class="login-button">
                    Login to Dashboard
                </button>


            </form>


            <div class="form-footer">
                Having trouble logging in? Contact your clinic
                for assistance.
            </div>


        </div>


    </div>


</body>

</html>
