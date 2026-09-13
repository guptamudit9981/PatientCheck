<?php

/* =========================================================
   ERROR REPORTING
   ========================================================= */

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');


/* =========================================================
   LOAD DATABASE CONNECTION
   ========================================================= */

require_once __DIR__ . "/config.php";


/* =========================================================
   VARIABLES
   ========================================================= */

$full_name = "";
$email = "";
$phone = "";
$date_of_birth = "";
$gender = "";
$blood_group = "";
$height_cm = "";
$weight_kg = "";
$address = "";
$city = "";
$state = "";
$pincode = "";
$emergency_contact_name = "";
$emergency_contact_phone = "";
$allergies = "";
$medical_conditions = "";

$error = "";
$success = false;
$patient_id = null;
$patient_code = "";


/* =========================================================
   FORM SUBMITTED
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* ---------------------------------------------
       GET FORM DATA
       --------------------------------------------- */

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    $date_of_birth = trim($_POST["date_of_birth"] ?? "");
    $gender = trim($_POST["gender"] ?? "");
    $blood_group = trim($_POST["blood_group"] ?? "");

    $height_cm = trim($_POST["height_cm"] ?? "");
    $weight_kg = trim($_POST["weight_kg"] ?? "");

    $address = trim($_POST["address"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $state = trim($_POST["state"] ?? "");
    $pincode = trim($_POST["pincode"] ?? "");

    $emergency_contact_name =
        trim($_POST["emergency_contact_name"] ?? "");

    $emergency_contact_phone =
        trim($_POST["emergency_contact_phone"] ?? "");

    $allergies =
        trim($_POST["allergies"] ?? "");

    $medical_conditions =
        trim($_POST["medical_conditions"] ?? "");


    /* ---------------------------------------------
       VALIDATION
       --------------------------------------------- */

    if ($full_name === "") {

        $error = "Patient full name is required.";

    } else {

        try {

            /* -----------------------------------------
               CONVERT EMPTY VALUES TO NULL
               ----------------------------------------- */

            $dob = ($date_of_birth !== "")
                ? $date_of_birth
                : null;

            $height = ($height_cm !== "")
                ? (float)$height_cm
                : null;

            $weight = ($weight_kg !== "")
                ? (float)$weight_kg
                : null;


            /* -----------------------------------------
               GET NEXT PATIENT ID
               ----------------------------------------- */

            $stmt = $pdo->query(
                "SELECT MAX(id) AS last_id FROM patients"
            );

            $row = $stmt->fetch();

            if ($row && $row["last_id"] !== null) {

                $next_id = (int)$row["last_id"] + 1;

            } else {

                $next_id = 1;

            }


            /* -----------------------------------------
               CREATE PATIENT CODE
               ----------------------------------------- */

            $patient_code =
                "PAT-" . (10000 + $next_id);


            /* -----------------------------------------
               INSERT PATIENT
               ----------------------------------------- */

            $sql = "
                INSERT INTO patients
                (
                    patient_code,
                    full_name,
                    email,
                    phone,
                    date_of_birth,
                    gender,
                    blood_group,
                    height_cm,
                    weight_kg,
                    address,
                    city,
                    state,
                    pincode,
                    emergency_contact_name,
                    emergency_contact_phone,
                    allergies,
                    medical_conditions
                )
                VALUES
                (
                    :patient_code,
                    :full_name,
                    :email,
                    :phone,
                    :date_of_birth,
                    :gender,
                    :blood_group,
                    :height_cm,
                    :weight_kg,
                    :address,
                    :city,
                    :state,
                    :pincode,
                    :emergency_contact_name,
                    :emergency_contact_phone,
                    :allergies,
                    :medical_conditions
                )
            ";


            $stmt = $pdo->prepare($sql);


            /* -----------------------------------------
               EXECUTE
               ----------------------------------------- */

            $stmt->execute([

                ":patient_code" =>
                    $patient_code,

                ":full_name" =>
                    $full_name,

                ":email" =>
                    ($email !== "" ? $email : null),

                ":phone" =>
                    ($phone !== "" ? $phone : null),

                ":date_of_birth" =>
                    $dob,

                ":gender" =>
                    ($gender !== "" ? $gender : null),

                ":blood_group" =>
                    ($blood_group !== "" ? $blood_group : null),

                ":height_cm" =>
                    $height,

                ":weight_kg" =>
                    $weight,

                ":address" =>
                    ($address !== "" ? $address : null),

                ":city" =>
                    ($city !== "" ? $city : null),

                ":state" =>
                    ($state !== "" ? $state : null),

                ":pincode" =>
                    ($pincode !== "" ? $pincode : null),

                ":emergency_contact_name" =>
                    ($emergency_contact_name !== ""
                        ? $emergency_contact_name
                        : null),

                ":emergency_contact_phone" =>
                    ($emergency_contact_phone !== ""
                        ? $emergency_contact_phone
                        : null),

                ":allergies" =>
                    ($allergies !== ""
                        ? $allergies
                        : null),

                ":medical_conditions" =>
                    ($medical_conditions !== ""
                        ? $medical_conditions
                        : null)
            ]);


            /* -----------------------------------------
               GET CREATED PATIENT ID
               ----------------------------------------- */

            $patient_id =
                $pdo->lastInsertId();

            $success = true;


        } catch (PDOException $e) {

            /*
             * Show actual error while developing.
             */

            $error =
                "Database Error: " .
                $e->getMessage();
        }
    }
}


/* =========================================================
   SUCCESS PAGE
   ========================================================= */

if ($success) {

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Patient Added | PatientCheck</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #172033;
        }

        .success-container {
            max-width: 600px;
            margin: 80px auto;
            padding: 20px;
        }

        .success-card {
            background: white;
            padding: 45px 35px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 15px 40px rgba(0,0,0,.08);
        }

        .success-icon {
            width: 75px;
            height: 75px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #dcfce7;
            color: #16a34a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: bold;
        }

        h1 {
            margin-bottom: 10px;
        }

        p {
            color: #64748b;
        }

        .patient-code {
            display: inline-block;
            margin: 20px 0;
            padding: 14px 25px;
            background: #eff6ff;
            color: #1d4ed8;
            border-radius: 10px;
            font-weight: bold;
            font-size: 18px;
        }

        .buttons {
            margin-top: 20px;
        }

        .button {
            display: inline-block;
            padding: 13px 20px;
            margin: 5px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
        }

        .primary {
            background: #2563eb;
            color: white;
        }

        .secondary {
            background: #eef2f7;
            color: #374151;
        }

    </style>

</head>

<body>

<div class="success-container">

    <div class="success-card">

        <div class="success-icon">
            ✓
        </div>

        <h1>
            Patient Added Successfully
        </h1>

        <p>
            The patient has been successfully added
            to PatientCheck.
        </p>

        <div class="patient-code">
            <?php echo htmlspecialchars($patient_code); ?>
        </div>

        <div class="buttons">

            <a
                href="add-patient.php"
                class="button primary"
            >
                Add Another Patient
            </a>

            <?php if (file_exists(__DIR__ . "/patient-profile.php")): ?>

                <a
                    href="patient-profile.php?id=<?php echo (int)$patient_id; ?>"
                    class="button secondary"
                >
                    View Patient
                </a>

            <?php endif; ?>

        </div>

    </div>

</div>

</body>

</html>

<?php

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

    <title>Add Patient | PatientCheck</title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                #f4f7fb;

            color:
                #172033;

            padding:
                30px 15px;
        }


        .container {

            max-width:
                1050px;

            margin:
                auto;
        }


        .header {

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            color:
                white;

            padding:
                32px;

            border-radius:
                18px 18px 0 0;
        }


        .header h1 {

            font-size:
                28px;

            margin-bottom:
                8px;
        }


        .header p {

            font-size:
                14px;

            opacity:
                .9;
        }


        .form-card {

            background:
                white;

            padding:
                32px;

            border-radius:
                0 0 18px 18px;

            box-shadow:
                0 12px 35px
                rgba(0,0,0,.08);
        }


        .error {

            background:
                #fee2e2;

            border:
                1px solid #fecaca;

            color:
                #991b1b;

            padding:
                16px;

            border-radius:
                10px;

            margin-bottom:
                25px;

            line-height:
                1.6;

            overflow-wrap:
                anywhere;
        }


        .section {

            margin-bottom:
                32px;
        }


        .section-title {

            font-size:
                19px;

            font-weight:
                700;

            color:
                #1d4ed8;

            padding-bottom:
                12px;

            border-bottom:
                1px solid #e5e7eb;

            margin-bottom:
                20px;
        }


        .grid {

            display:
                grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap:
                18px;
        }


        .full {

            grid-column:
                1 / -1;
        }


        .form-group {

            display:
                flex;

            flex-direction:
                column;
        }


        label {

            font-size:
                14px;

            font-weight:
                600;

            color:
                #374151;

            margin-bottom:
                7px;
        }


        .required {

            color:
                #dc2626;
        }


        input,
        select,
        textarea {

            width:
                100%;

            padding:
                13px 14px;

            border:
                1px solid #d6dbe5;

            border-radius:
                10px;

            background:
                white;

            color:
                #172033;

            font-size:
                14px;

            outline:
                none;
        }


        input:focus,
        select:focus,
        textarea:focus {

            border-color:
                #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37,99,235,.10);
        }


        textarea {

            min-height:
                100px;

            resize:
                vertical;
        }


        .buttons {

            display:
                flex;

            justify-content:
                flex-end;

            gap:
                12px;

            margin-top:
                10px;
        }


        .cancel,
        .save {

            padding:
                13px 22px;

            border-radius:
                10px;

            font-size:
                14px;

            font-weight:
                700;

            text-decoration:
                none;

            cursor:
                pointer;
        }


        .cancel {

            background:
                #eef1f5;

            color:
                #374151;

            border:
                none;
        }


        .save {

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d4ed8
                );

            color:
                white;

            border:
                none;

            box-shadow:
                0 7px 18px
                rgba(37,99,235,.22);
        }


        .save:hover {

            transform:
                translateY(-1px);
        }


        @media (max-width: 700px) {

            body {
                padding: 15px 10px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .full {
                grid-column: auto;
            }

            .header {
                padding: 24px;
            }

            .header h1 {
                font-size: 23px;
            }

            .form-card {
                padding: 20px;
            }

            .buttons {
                flex-direction: column;
            }

            .cancel,
            .save {
                width: 100%;
                text-align: center;
            }

        }

    </style>

</head>


<body>


<div class="container">


    <!-- HEADER -->

    <div class="header">

        <h1>
            Add New Patient
        </h1>

        <p>
            Create a patient profile in PatientCheck
        </p>

    </div>


    <!-- FORM CARD -->

    <div class="form-card">


        <?php if ($error !== ""): ?>

            <div class="error">

                <strong>
                    Unable to save patient
                </strong>

                <br><br>

                <?php
                    echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action="add-patient.php"
        >


            <!-- PERSONAL INFORMATION -->

            <div class="section">

                <div class="section-title">
                    Personal Information
                </div>


                <div class="grid">


                    <div class="form-group full">

                        <label>
                            Full Name
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            value="<?php
                                echo htmlspecialchars($full_name);
                            ?>"
                            placeholder="Enter patient's full name"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            value="<?php
                                echo htmlspecialchars($email);
                            ?>"
                            placeholder="patient@example.com"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Phone
                        </label>

                        <input
                            type="text"
                            name="phone"
                            value="<?php
                                echo htmlspecialchars($phone);
                            ?>"
                            placeholder="Enter phone number"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Date of Birth
                        </label>

                        <input
                            type="date"
                            name="date_of_birth"
                            value="<?php
                                echo htmlspecialchars($date_of_birth);
                            ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Gender
                        </label>

                        <select name="gender">

                            <option value="">
                                Select Gender
                            </option>

                            <option
                                value="male"
                                <?php
                                if ($gender === "male") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Male
                            </option>

                            <option
                                value="female"
                                <?php
                                if ($gender === "female") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Female
                            </option>

                            <option
                                value="other"
                                <?php
                                if ($gender === "other") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Other
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Blood Group
                        </label>

                        <select name="blood_group">

                            <option value="">
                                Select Blood Group
                            </option>

                            <?php

                            $blood_groups = [
                                "A+",
                                "A-",
                                "B+",
                                "B-",
                                "AB+",
                                "AB-",
                                "O+",
                                "O-"
                            ];

                            foreach ($blood_groups as $group) {

                                $selected =
                                    ($blood_group === $group)
                                    ? "selected"
                                    : "";

                                echo
                                '<option value="' .
                                htmlspecialchars($group) .
                                '" ' .
                                $selected .
                                '>' .
                                htmlspecialchars($group) .
                                '</option>';
                            }

                            ?>

                        </select>

                    </div>


                </div>

            </div>


            <!-- HEALTH INFORMATION -->

            <div class="section">

                <div class="section-title">
                    Health Information
                </div>


                <div class="grid">


                    <div class="form-group">

                        <label>
                            Height (cm)
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            name="height_cm"
                            value="<?php
                                echo htmlspecialchars($height_cm);
                            ?>"
                            placeholder="Example: 170"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Weight (kg)
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            name="weight_kg"
                            value="<?php
                                echo htmlspecialchars($weight_kg);
                            ?>"
                            placeholder="Example: 70"
                        >

                    </div>


                    <div class="form-group full">

                        <label>
                            Allergies
                        </label>

                        <textarea
                            name="allergies"
                            placeholder="Example: Penicillin, dust, food allergies..."
                        ><?php
                            echo htmlspecialchars($allergies);
                        ?></textarea>

                    </div>


                    <div class="form-group full">

                        <label>
                            Medical Conditions
                        </label>

                        <textarea
                            name="medical_conditions"
                            placeholder="Example: Hypertension, Diabetes..."
                        ><?php
                            echo htmlspecialchars($medical_conditions);
                        ?></textarea>

                    </div>


                </div>

            </div>


            <!-- ADDRESS -->

            <div class="section">

                <div class="section-title">
                    Address
                </div>


                <div class="grid">


                    <div class="form-group full">

                        <label>
                            Address
                        </label>

                        <textarea
                            name="address"
                            placeholder="Enter complete address"
                        ><?php
                            echo htmlspecialchars($address);
                        ?></textarea>

                    </div>


                    <div class="form-group">

                        <label>
                            City
                        </label>

                        <input
                            type="text"
                            name="city"
                            value="<?php
                                echo htmlspecialchars($city);
                            ?>"
                            placeholder="City"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            State
                        </label>

                        <input
                            type="text"
                            name="state"
                            value="<?php
                                echo htmlspecialchars($state);
                            ?>"
                            placeholder="State"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Pincode
                        </label>

                        <input
                            type="text"
                            name="pincode"
                            value="<?php
                                echo htmlspecialchars($pincode);
                            ?>"
                            placeholder="Pincode"
                        >

                    </div>


                </div>

            </div>


            <!-- EMERGENCY CONTACT -->

            <div class="section">

                <div class="section-title">
                    Emergency Contact
                </div>


                <div class="grid">


                    <div class="form-group">

                        <label>
                            Contact Name
                        </label>

                        <input
                            type="text"
                            name="emergency_contact_name"
                            value="<?php
                                echo htmlspecialchars(
                                    $emergency_contact_name
                                );
                            ?>"
                            placeholder="Emergency contact name"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Contact Phone
                        </label>

                        <input
                            type="text"
                            name="emergency_contact_phone"
                            value="<?php
                                echo htmlspecialchars(
                                    $emergency_contact_phone
                                );
                            ?>"
                            placeholder="Emergency contact phone"
                        >

                    </div>


                </div>

            </div>


            <!-- BUTTONS -->

            <div class="buttons">

                <a
                    href="index.php"
                    class="cancel"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    class="save"
                >
                    Save Patient
                </button>

            </div>


        </form>


    </div>

</div>


</body>

</html>