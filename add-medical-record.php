<?php

session_start();

require_once __DIR__ . "/config.php";


/* =========================================================
   CHECK DOCTOR LOGIN
   ========================================================= */

/*if (!isset($_SESSION['doctor_id'])) {

    die("Doctor login required.");

}*/

$doctor_id25 = 1;


/* =========================================================
   GET REGISTERED DOCTOR
   ========================================================= */

$doctor_stmt25 = $pdo->prepare("
    SELECT
        id,
        doctor_code,
        full_name,
        specialization,
        qualification,
        license_number,
        hospital_name
    FROM doctors
    WHERE id = ?
    LIMIT 1
");

$doctor_stmt25->execute([
    $doctor_id25
]);

$logged_doctor25 = $doctor_stmt25->fetch();


/* =========================================================
   VERIFY DOCTOR EXISTS
   ========================================================= */

if (!$logged_doctor25) {

    session_destroy();

    die("Registered doctor not found.");

}


/* =========================================================
   GET ALL PATIENTS
   ========================================================= */

$patients_stmt25 = $pdo->query("
    SELECT
        id,
        patient_code,
        full_name,
        date_of_birth,
        gender,
        blood_group
    FROM patients
    ORDER BY full_name ASC
");

$patients25 = $patients_stmt25->fetchAll();


/* =========================================================
   FORM VARIABLES
   ========================================================= */

$message25 = "";
$error25 = "";

$selected_patient25 = "";
$visit_date25 = date("Y-m-d\TH:i");
$diagnosis25 = "";
$treatment25 = "";
$medicines25 = "";
$doctor_notes25 = "";


/* =========================================================
   FORM SUBMISSION
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    /* -----------------------------------------------------
       GET FORM DATA
       ----------------------------------------------------- */

    $selected_patient25 = isset($_POST["patient_id"])
        ? (int) $_POST["patient_id"]
        : 0;

    $visit_date25 = trim(
        $_POST["visit_date"] ?? ""
    );

    $diagnosis25 = trim(
        $_POST["diagnosis"] ?? ""
    );

    $treatment25 = trim(
        $_POST["treatment"] ?? ""
    );

    $medicines25 = trim(
        $_POST["medicines"] ?? ""
    );

    $doctor_notes25 = trim(
        $_POST["doctor_notes"] ?? ""
    );


    /* -----------------------------------------------------
       VALIDATION
       ----------------------------------------------------- */

    if ($selected_patient25 <= 0) {

        $error25 = "Please select a patient.";

    } elseif ($diagnosis25 === "") {

        $error25 = "Please enter the diagnosis.";

    } elseif ($visit_date25 === "") {

        $error25 = "Please select the visit date.";

    }


    /* -----------------------------------------------------
       VERIFY PATIENT
       ----------------------------------------------------- */

    if ($error25 === "") {

        $patient_check_stmt25 = $pdo->prepare("
            SELECT id, full_name
            FROM patients
            WHERE id = ?
            LIMIT 1
        ");

        $patient_check_stmt25->execute([
            $selected_patient25
        ]);

        $selected_patient_data25 =
            $patient_check_stmt25->fetch();


        if (!$selected_patient_data25) {

            $error25 = "Selected patient was not found.";

        }

    }


    /* -----------------------------------------------------
       INSERT TIMELINE RECORD
       ----------------------------------------------------- */

    if ($error25 === "") {

        try {

            /*
             * IMPORTANT:
             *
             * doctor_id comes from the logged-in doctor's
             * session.
             *
             * It is NOT taken from the form.
             */

            $insert_timeline_stmt25 = $pdo->prepare("
                INSERT INTO patient_timeline
                (
                    patient_id,
                    doctor_id,
                    hospital_name,
                    visit_date,
                    diagnosis,
                    treatment,
                    medicines,
                    doctor_notes
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
                    ?
                )
            ");


            $insert_timeline_stmt25->execute([

                $selected_patient25,

                $doctor_id25,

                $logged_doctor25["hospital_name"] ?? "",

                $visit_date25,

                $diagnosis25,

                $treatment25,

                $medicines25,

                $doctor_notes25

            ]);


            $message25 =
                "Medical record added successfully.";


            /*
             * Clear form after successful insert
             */

            $selected_patient25 = "";

            $visit_date25 = date("Y-m-d\TH:i");

            $diagnosis25 = "";

            $treatment25 = "";

            $medicines25 = "";

            $doctor_notes25 = "";


        } catch (PDOException $e) {

            $error25 =
                "Unable to save the medical record. Please try again.";

        }

    }

}


/* =========================================================
   HELPER
   ========================================================= */

function safe25($value)
{
    return htmlspecialchars(
        (string) $value,
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
        Add Medical Record | PatientCheck
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f5f7fb;

            color: #172033;
        }


        .page25 {
            max-width: 1000px;

            margin: 0 auto;

            padding: 30px 20px 50px;
        }


        /* =================================================
           HEADER
        ================================================= */

        .page-header25 {
            margin-bottom: 25px;
        }


        .page-header25 h1 {
            margin: 0 0 7px;

            font-size: 26px;

            font-weight: 700;

            color: #172033;
        }


        .page-header25 p {
            margin: 0;

            color: #718096;

            font-size: 14px;
        }


        /* =================================================
           DOCTOR CARD
        ================================================= */

        .doctor-card25 {
            background: white;

            border: 1px solid #e5e7eb;

            border-radius: 14px;

            padding: 18px 20px;

            margin-bottom: 20px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;
        }


        .doctor-info25 {
            display: flex;

            align-items: center;

            gap: 14px;
        }


        .doctor-avatar25 {
            width: 48px;

            height: 48px;

            border-radius: 50%;

            background: #eff6ff;

            color: #2563eb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;

            font-weight: 700;
        }


        .doctor-name25 {
            font-size: 15px;

            font-weight: 700;

            color: #172033;
        }


        .doctor-specialization25 {
            margin-top: 4px;

            font-size: 12px;

            color: #64748b;
        }


        .doctor-code25 {
            font-size: 11px;

            color: #94a3b8;

            text-align: right;
        }


        /* =================================================
           ALERTS
        ================================================= */

        .success25 {
            background: #ecfdf5;

            border: 1px solid #a7f3d0;

            color: #166534;

            padding: 13px 15px;

            border-radius: 9px;

            margin-bottom: 20px;

            font-size: 13px;
        }


        .error25 {
            background: #fef2f2;

            border: 1px solid #fecaca;

            color: #991b1b;

            padding: 13px 15px;

            border-radius: 9px;

            margin-bottom: 20px;

            font-size: 13px;
        }


        /* =================================================
           FORM CARD
        ================================================= */

        .form-card25 {
            background: white;

            border: 1px solid #e5e7eb;

            border-radius: 14px;

            padding: 25px;
        }


        .form-title25 {
            margin: 0 0 20px;

            font-size: 18px;

            font-weight: 700;
        }


        /* =================================================
           FORM GRID
        ================================================= */

        .form-grid25 {
            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 18px;
        }


        .form-group25 {
            display: flex;

            flex-direction: column;
        }


        .form-group-full25 {
            grid-column: 1 / -1;
        }


        .form-label25 {
            margin-bottom: 7px;

            font-size: 12px;

            font-weight: 700;

            color: #374151;
        }


        .required25 {
            color: #dc2626;
        }


        .form-input25,
        .form-select25,
        .form-textarea25 {
            width: 100%;

            border: 1px solid #d1d5db;

            border-radius: 8px;

            padding: 11px 12px;

            font-family: inherit;

            font-size: 13px;

            color: #172033;

            background: #ffffff;

            outline: none;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }


        .form-input25:focus,
        .form-select25:focus,
        .form-textarea25:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.10);
        }


        .form-textarea25 {
            min-height: 110px;

            resize: vertical;

            line-height: 1.5;
        }


        /* =================================================
           PATIENT SELECT
        ================================================= */

        .patient-select25 {
            font-weight: 500;
        }


        .form-help25 {
            margin-top: 5px;

            font-size: 11px;

            color: #94a3b8;
        }


        /* =================================================
           BUTTONS
        ================================================= */

        .form-actions25 {
            display: flex;

            align-items: center;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 25px;

            padding-top: 20px;

            border-top: 1px solid #e5e7eb;
        }


        .btn25 {
            border: none;

            border-radius: 8px;

            padding: 11px 18px;

            font-size: 13px;

            font-weight: 700;

            cursor: pointer;

            text-decoration: none;

            display: inline-flex;

            align-items: center;

            justify-content: center;
        }


        .btn-primary25 {
            background: #2563eb;

            color: white;
        }


        .btn-primary25:hover {
            background: #1d4ed8;
        }


        .btn-secondary25 {
            background: #f1f5f9;

            color: #475569;
        }


        .btn-secondary25:hover {
            background: #e2e8f0;
        }


        /* =================================================
           MOBILE
        ================================================= */

        @media (max-width: 700px) {

            .page25 {
                padding:
                    20px 14px 40px;
            }


            .page-header25 h1 {
                font-size: 22px;
            }


            .doctor-card25 {
                align-items: flex-start;

                flex-direction: column;
            }


            .doctor-code25 {
                text-align: left;
            }


            .form-card25 {
                padding: 18px;
            }


            .form-grid25 {
                grid-template-columns: 1fr;
            }


            .form-group-full25 {
                grid-column: auto;
            }


            .form-actions25 {
                flex-direction: column-reverse;
            }


            .btn25 {
                width: 100%;
            }

        }

    </style>

</head>


<body>


<div class="page25">


    <!-- =================================================
         PAGE HEADER
    ================================================= -->

    <div class="page-header25">

        <h1>
            Add Medical Record
        </h1>

        <p>
            Add a new consultation to a patient's medical timeline.
        </p>

    </div>


    <!-- =================================================
         LOGGED IN DOCTOR
    ================================================= -->

    <div class="doctor-card25">

        <div class="doctor-info25">

            <div class="doctor-avatar25">
                Dr
            </div>

            <div>

                <div class="doctor-name25">

                    <?= safe25(
                        $logged_doctor25["full_name"]
                    ) ?>

                </div>

                <div class="doctor-specialization25">

                    <?= safe25(
                        $logged_doctor25["specialization"]
                    ) ?>

                </div>

            </div>

        </div>


        <div class="doctor-code25">

            Doctor ID:
            <?= safe25(
                $logged_doctor25["id"]
            ) ?>

            <br>

            <?= safe25(
                $logged_doctor25["doctor_code"]
            ) ?>

        </div>

    </div>


    <!-- =================================================
         SUCCESS MESSAGE
    ================================================= -->

    <?php if ($message25 !== ""): ?>

        <div class="success25">

            ✓
            <?= safe25($message25) ?>

        </div>

    <?php endif; ?>


    <!-- =================================================
         ERROR MESSAGE
    ================================================= -->

    <?php if ($error25 !== ""): ?>

        <div class="error25">

            <?= safe25($error25) ?>

        </div>

    <?php endif; ?>


    <!-- =================================================
         FORM
    ================================================= -->

    <div class="form-card25">

        <h2 class="form-title25">

            Consultation Details

        </h2>


        <form
            method="POST"
            action=""
        >


            <div class="form-grid25">


                <!-- =====================================
                     PATIENT
                ====================================== -->

                <div class="form-group25 form-group-full25">

                    <label
                        class="form-label25"
                        for="patient_id"
                    >

                        Select Patient
                        <span class="required25">*</span>

                    </label>


                    <select
                        class="form-select25 patient-select25"
                        name="patient_id"
                        id="patient_id"
                        required
                    >

                        <option value="">
                            -- Select Patient --
                        </option>


                        <?php foreach (
                            $patients25
                            as $patient25
                        ): ?>

                            <option
                                value="<?= safe25(
                                    $patient25["id"]
                                ) ?>"
                                <?= (
                                    $selected_patient25
                                    ==
                                    $patient25["id"]
                                )
                                    ? "selected"
                                    : ""
                                ?>
                            >

                                <?= safe25(
                                    $patient25["full_name"]
                                ) ?>

                                -
                                <?= safe25(
                                    $patient25["patient_code"]
                                ) ?>

                                <?php if (
                                    !empty(
                                        $patient25["blood_group"]
                                    )
                                ): ?>

                                    -
                                    <?= safe25(
                                        $patient25["blood_group"]
                                    ) ?>

                                <?php endif; ?>

                            </option>

                        <?php endforeach; ?>

                    </select>


                    <div class="form-help25">

                        Select the patient for this consultation.

                    </div>

                </div>


                <!-- =====================================
                     VISIT DATE
                ====================================== -->

                <div class="form-group25">

                    <label
                        class="form-label25"
                        for="visit_date"
                    >

                        Visit Date & Time
                        <span class="required25">*</span>

                    </label>


                    <input
                        class="form-input25"
                        type="datetime-local"
                        name="visit_date"
                        id="visit_date"
                        value="<?= safe25(
                            $visit_date25
                        ) ?>"
                        required
                    >

                </div>


                <!-- =====================================
                     DIAGNOSIS
                ====================================== -->

                <div class="form-group25">

                    <label
                        class="form-label25"
                        for="diagnosis"
                    >

                        Diagnosis
                        <span class="required25">*</span>

                    </label>


                    <input
                        class="form-input25"
                        type="text"
                        name="diagnosis"
                        id="diagnosis"
                        placeholder="Enter diagnosis"
                        value="<?= safe25(
                            $diagnosis25
                        ) ?>"
                        required
                    >

                </div>


                <!-- =====================================
                     TREATMENT
                ====================================== -->

                <div class="form-group25 form-group-full25">

                    <label
                        class="form-label25"
                        for="treatment"
                    >

                        Treatment

                    </label>


                    <textarea
                        class="form-textarea25"
                        name="treatment"
                        id="treatment"
                        placeholder="Enter treatment or procedure details"
                    ><?= safe25(
                        $treatment25
                    ) ?></textarea>

                </div>


                <!-- =====================================
                     MEDICINES
                ====================================== -->

                <div class="form-group25 form-group-full25">

                    <label
                        class="form-label25"
                        for="medicines"
                    >

                        Medicines

                    </label>


                    <textarea
                        class="form-textarea25"
                        name="medicines"
                        id="medicines"
                        placeholder="Enter prescribed medicines and dosage"
                    ><?= safe25(
                        $medicines25
                    ) ?></textarea>

                </div>


                <!-- =====================================
                     DOCTOR NOTES
                ====================================== -->

                <div class="form-group25 form-group-full25">

                    <label
                        class="form-label25"
                        for="doctor_notes"
                    >

                        Doctor's Notes

                    </label>


                    <textarea
                        class="form-textarea25"
                        name="doctor_notes"
                        id="doctor_notes"
                        placeholder="Enter additional notes or instructions"
                    ><?= safe25(
                        $doctor_notes25
                    ) ?></textarea>

                </div>


            </div>


            <!-- =========================================
                 ACTIONS
            ========================================== -->

            <div class="form-actions25">

                <a
                    href="doctor-dashboard.php"
                    class="btn25 btn-secondary25"
                >

                    Cancel

                </a>


                <button
                    type="submit"
                    class="btn25 btn-primary25"
                >

                    Add Medical Record

                </button>

            </div>


        </form>

    </div>


</div>


</body>

</html>