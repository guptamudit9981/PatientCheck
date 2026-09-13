<?php

require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: add-patient.php");
    exit;
}


/* -------------------------
   GET FORM VALUES
------------------------- */

$full_name = $_POST["full_name"] ?? "";
$email = $_POST["email"] ?? "";
$phone = $_POST["phone"] ?? "";
$date_of_birth = $_POST["date_of_birth"] ?? "";
$gender = $_POST["gender"] ?? "";
$blood_group = $_POST["blood_group"] ?? "";

$height_cm = $_POST["height_cm"] ?? "";
$weight_kg = $_POST["weight_kg"] ?? "";

$address = $_POST["address"] ?? "";
$city = $_POST["city"] ?? "";
$state = $_POST["state"] ?? "";
$pincode = $_POST["pincode"] ?? "";

$emergency_contact_name =
    $_POST["emergency_contact_name"] ?? "";

$emergency_contact_phone =
    $_POST["emergency_contact_phone"] ?? "";

$allergies =
    $_POST["allergies"] ?? "";

$medical_conditions =
    $_POST["medical_conditions"] ?? "";


/* -------------------------
   BASIC VALIDATION
------------------------- */

$full_name = trim($full_name);

if ($full_name == "") {
    die("Patient name is required.");
}


/* -------------------------
   PATIENT CODE
------------------------- */

$patient_code = "PAT-" . time();


/* -------------------------
   SQL QUERY
------------------------- */

$sql = "INSERT INTO patients
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
    '$patient_code',
    '$full_name',
    '$email',
    '$phone',
    '$date_of_birth',
    '$gender',
    '$blood_group',
    '$height_cm',
    '$weight_kg',
    '$address',
    '$city',
    '$state',
    '$pincode',
    '$emergency_contact_name',
    '$emergency_contact_phone',
    '$allergies',
    '$medical_conditions'
)";


/* -------------------------
   EXECUTE QUERY
------------------------- */

$result = mysqli_query($conn, $sql);


if (!$result) {

    echo "<h2>Database Error</h2>";

    echo "<p>" . mysqli_error($conn) . "</p>";

    echo "<br>";

    echo '<a href="add-patient.php">Go Back</a>';

    exit;
}


/* -------------------------
   SUCCESS
------------------------- */

$patient_id = mysqli_insert_id($conn);

header(
    "Location: patient-profile.php?id=" . $patient_id
);

exit;

?>