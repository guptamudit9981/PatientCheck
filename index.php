```php
<?php
/*
|--------------------------------------------------------------------------
| PatientCheck - Dashboard Navigation
|--------------------------------------------------------------------------
|
| Place this file in the same folder as:
|
| patient-dashboard.php
| doctor-dashboard.php
| hospital-dashboard.php
|
*/
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>PatientCheck | Dashboard Portal</title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {

            min-height: 100vh;

            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Arial,
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #f8fafc 0%,
                    #eef2ff 50%,
                    #f8fafc 100%
                );

            color: #0f172a;

            display: flex;

            flex-direction: column;
        }


        /* ------------------------------------------------
           HEADER
        ------------------------------------------------ */

        header {

            width: 100%;

            background: rgba(255,255,255,.92);

            border-bottom:
                1px solid #e2e8f0;

            backdrop-filter: blur(10px);

            padding: 18px 6%;

            display: flex;

            align-items: center;

            justify-content: space-between;
        }


        .brand {

            display: flex;

            align-items: center;

            gap: 12px;

            text-decoration: none;

            color: #0f172a;
        }


        .brand-icon {

            width: 46px;

            height: 46px;

            border-radius: 13px;

            background: #2563eb;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 23px;

            box-shadow:
                0 8px 20px
                rgba(37,99,235,.20);
        }


        .brand-text h2 {

            font-size: 20px;

            font-weight: 800;

            letter-spacing: -.3px;
        }


        .brand-text span {

            display: block;

            margin-top: 2px;

            color: #64748b;

            font-size: 12px;

            font-weight: 500;
        }


        .status {

            display: flex;

            align-items: center;

            gap: 8px;

            font-size: 13px;

            color: #16a34a;

            font-weight: 700;
        }


        .status-dot {

            width: 9px;

            height: 9px;

            border-radius: 50%;

            background: #22c55e;

            box-shadow:
                0 0 0 5px
                rgba(34,197,94,.12);
        }


        /* ------------------------------------------------
           MAIN
        ------------------------------------------------ */

        main {

            flex: 1;

            width: 100%;

            max-width: 1200px;

            margin: auto;

            padding: 70px 25px;
        }


        .hero {

            text-align: center;

            max-width: 750px;

            margin: 0 auto 50px;
        }


        .hero-badge {

            display: inline-block;

            padding: 7px 13px;

            border-radius: 30px;

            background: #dbeafe;

            color: #1d4ed8;

            font-size: 12px;

            font-weight: 800;

            letter-spacing: .5px;

            margin-bottom: 16px;
        }


        .hero h1 {

            font-size: clamp(34px, 5vw, 52px);

            line-height: 1.1;

            letter-spacing: -1.5px;

            margin-bottom: 16px;
        }


        .hero h1 span {

            color: #2563eb;
        }


        .hero p {

            color: #64748b;

            font-size: 17px;

            line-height: 1.7;
        }


        /* ------------------------------------------------
           DASHBOARD CARDS
        ------------------------------------------------ */

        .dashboard-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 24px;
        }


        .dashboard-card {

            background: white;

            border:
                1px solid #e2e8f0;

            border-radius: 22px;

            padding: 30px;

            text-decoration: none;

            color: #0f172a;

            position: relative;

            overflow: hidden;

            box-shadow:
                0 12px 35px
                rgba(15,23,42,.06);

            transition:
                transform .25s ease,
                box-shadow .25s ease,
                border-color .25s ease;
        }


        .dashboard-card::before {

            content: "";

            position: absolute;

            top: 0;

            left: 0;

            right: 0;

            height: 4px;

            background: #2563eb;
        }


        .dashboard-card:hover {

            transform:
                translateY(-7px);

            box-shadow:
                0 20px 45px
                rgba(15,23,42,.11);

            border-color: #bfdbfe;
        }


        .card-icon {

            width: 62px;

            height: 62px;

            border-radius: 17px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 29px;

            margin-bottom: 22px;
        }


        .patient .card-icon {

            background: #eff6ff;
        }


        .doctor .card-icon {

            background: #f0fdf4;
        }


        .hospital .card-icon {

            background: #fff1f2;
        }


        .dashboard-card h2 {

            font-size: 21px;

            margin-bottom: 10px;
        }


        .dashboard-card p {

            color: #64748b;

            font-size: 14px;

            line-height: 1.65;

            min-height: 70px;
        }


        .open-dashboard {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            margin-top: 22px;

            font-size: 14px;

            font-weight: 800;

            color: #2563eb;
        }


        .doctor .open-dashboard {

            color: #16a34a;
        }


        .hospital .open-dashboard {

            color: #dc2626;
        }


        .arrow {

            transition:
                transform .2s ease;
        }


        .dashboard-card:hover .arrow {

            transform:
                translateX(5px);
        }


        /* ------------------------------------------------
           INFO
        ------------------------------------------------ */

        .info-section {

            margin-top: 45px;

            background: white;

            border:
                1px solid #e2e8f0;

            border-radius: 18px;

            padding: 22px 25px;

            display: flex;

            align-items: center;

            gap: 15px;

            color: #64748b;

            font-size: 13px;

            line-height: 1.6;
        }


        .info-icon {

            width: 38px;

            height: 38px;

            flex-shrink: 0;

            border-radius: 10px;

            background: #f1f5f9;

            display: flex;

            align-items: center;

            justify-content: center;
        }


        /* ------------------------------------------------
           FOOTER
        ------------------------------------------------ */

        footer {

            padding: 25px;

            text-align: center;

            color: #94a3b8;

            font-size: 12px;
        }


        /* ------------------------------------------------
           MOBILE
        ------------------------------------------------ */

        @media (max-width: 850px) {

            .dashboard-grid {

                grid-template-columns: 1fr;

                max-width: 550px;

                margin: auto;
            }

            .dashboard-card p {

                min-height: auto;
            }

            .status {

                display: none;
            }

            main {

                padding-top: 45px;

                padding-bottom: 40px;
            }
        }


        @media (max-width: 500px) {

            header {

                padding: 15px 20px;
            }

            .brand-text h2 {

                font-size: 18px;
            }

            .brand-icon {

                width: 40px;

                height: 40px;

                font-size: 20px;
            }

            .hero h1 {

                font-size: 35px;
            }

            .hero p {

                font-size: 15px;
            }

            .dashboard-card {

                padding: 24px;
            }
        }

    </style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header>

    <a
        href="index.php"
        class="brand"
    >

        <div class="brand-icon">
            ✚
        </div>

        <div class="brand-text">

            <h2>
                PatientCheck
            </h2>

            <span>
                Connected Healthcare Platform
            </span>

        </div>

    </a>
<a href="add-patient.php" class="add-patient-btn">
    <span>+</span>
    Add Patient
</a>

    <div class="status">

        <span class="status-dot"></span>

        System Online

    </div>

</header>



<!-- =====================================================
     MAIN
===================================================== -->

<main>


    <section class="hero">

        <div class="hero-badge">
            SECURE HEALTHCARE PORTAL
        </div>


        <h1>
            Choose your
            <span>dashboard</span>
        </h1>


        <p>
            Access the PatientCheck healthcare management
            system through the dashboard designed for your role.
        </p>

    </section>



    <!-- =================================================
         DASHBOARD LINKS
    ================================================= -->

    <section class="dashboard-grid">


        <!-- PATIENT -->

        <a
            href="patient-dashboard.php"
            class="dashboard-card patient"
        >

            <div class="card-icon">
                👤
            </div>


            <h2>
                Patient Dashboard
            </h2>


            <p>
                Manage your health information, medicines,
                appointments, doctors and emergency SOS services.
            </p>


            <span class="open-dashboard">

                Open Patient Dashboard

                <span class="arrow">
                    →
                </span>

            </span>

        </a>



        <!-- DOCTOR -->

        <a
            href="doctor-dashboard.php"
            class="dashboard-card doctor"
        >

            <div class="card-icon">
                🩺
            </div>


            <h2>
                Doctor Dashboard
            </h2>


            <p>
                Manage patients, appointments, medical records,
                diagnoses, prescriptions and doctor notes.
            </p>


            <span class="open-dashboard">

                Open Doctor Dashboard

                <span class="arrow">
                    →
                </span>

            </span>

        </a>



        <!-- HOSPITAL -->

        <a
            href="hospital-dashboard.php"
            class="dashboard-card hospital"
        >

            <div class="card-icon">
                🚑
            </div>


            <h2>
                Hospital Dashboard
            </h2>


            <p>
                Monitor live emergency requests, view patient
                medical information and respond to SOS alerts.
            </p>


            <span class="open-dashboard">

                Open Hospital Dashboard

                <span class="arrow">
                    →
                </span>

            </span>

        </a>


    </section>



    <!-- =================================================
         INFORMATION
    ================================================= -->

    <div class="info-section">

        <div class="info-icon">
            🔒
        </div>

        <div>
            <strong>
                Secure access
            </strong>

            <br>

            In the production version, each dashboard should
            be protected by role-based authentication so that
            patients, doctors and hospitals can only access
            information authorized for their account.
        </div>

    </div>


</main>



<!-- =====================================================
     FOOTER
===================================================== -->

<footer>

    © <?= date('Y') ?> PatientCheck.
    All rights reserved.

</footer>


</body>

</html>
```
