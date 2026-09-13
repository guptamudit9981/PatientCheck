<?php

require_once "config/database.php";
require_once "includes/helpers.php";

session_start();

/*
|--------------------------------------------------------------------------
| IF ALREADY LOGGED IN AS A HOSPITAL, SKIP LOGIN
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['hospital_id'])) {
    header("Location: hospital-dashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| HANDLE LOGIN SUBMISSION
|--------------------------------------------------------------------------
*/

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {

        $error = "Please enter both email and password.";

    } else {

        $stmt = $pdo->prepare("
            SELECT *
            FROM hospitals
            WHERE email = ?
            AND status = 'active'
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $hospital = $stmt->fetch();

        // NOTE: passwords are currently stored in plain text in the
        // hospitals table, so we compare directly. If you switch to
        // password_hash() when creating hospital accounts, change this
        // to password_verify($password, $hospital['password']).
        if ($hospital && $password === $hospital['password']) {

            session_regenerate_id(true);

            $_SESSION['hospital_id']   = $hospital['id'];
            $_SESSION['hospital_name'] = $hospital['hospital_name'];
            $_SESSION['hospital_code'] = $hospital['hospital_code'];

            header("Location: hospital-dashboard.php");
            exit;

        } else {

            $error = "Incorrect email or password.";

        }
    }
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
        Hospital Login | PatientCheck
    </title>


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
                #7f1d1d,
                #dc2626
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

            border: 50px solid rgba(255, 255, 255, .08);

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

            background: rgba(255, 255, 255, .18);

            border: 1px solid rgba(255, 255, 255, .26);

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
            color: #fecaca;
        }


        .brand-top h2 {

            font-size: 26px;

            letter-spacing: -.6px;

            margin-bottom: 12px;

            line-height: 1.3;
        }


        .brand-top p {

            color: #fee2e2;

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

            color: #fef2f2;
        }


        .brand-feature-icon {

            width: 26px;
            height: 26px;

            border-radius: 8px;

            background: rgba(255, 255, 255, .16);

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

            box-shadow: 0 0 0 3px #fee2e2;
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


        .form-footer a {
            color: var(--primary);
            font-weight: 700;
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
                        H
                    </div>

                    <div class="brand-logo-text">
                        PatientCheck<span> Hospital</span>
                    </div>

                </div>


                <h2>
                    Emergency Response
                    Control Center
                </h2>

                <p>
                    Sign in to receive live SOS requests from patients,
                    view their medical details and location, and
                    respond in real time.
                </p>

            </div>


            <div class="brand-features">

                <div class="brand-feature">
                    <span class="brand-feature-icon">🚨</span>
                    Instant emergency alerts
                </div>

                <div class="brand-feature">
                    <span class="brand-feature-icon">📍</span>
                    Live patient location tracking
                </div>

                <div class="brand-feature">
                    <span class="brand-feature-icon">✚</span>
                    Full medical profile on arrival
                </div>

            </div>


        </div>


        <!-- RIGHT / FORM -->

        <div class="form-panel">


            <h1>
                Hospital Login
            </h1>

            <p>
                Sign in with your registered hospital account.
            </p>


            <?php if ($error): ?>

                <div class="error-box">
                    ⚠ <?= safe($error) ?>
                </div>

            <?php endif; ?>


            <form method="POST" action="hospital-login.php" autocomplete="off">


                <div class="field">

                    <label for="email">
                        Hospital Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="e.g. info@yourhospital.com"
                        value="<?= safe($_POST['email'] ?? '') ?>"
                        required
                    >

                </div>


                <div class="field">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        required
                    >

                </div>


                <button type="submit" class="login-button">
                    Login to Control Center
                </button>


            </form>


            <div class="form-footer">
                Patient looking to log in?
                <a href="login.php">Go to patient login</a>
            </div>


        </div>


    </div>


</body>

</html>
