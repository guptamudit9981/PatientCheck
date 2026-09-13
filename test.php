<?php
require_once "../config.php";
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

    <link
        rel="stylesheet"
        href="../assets/css/add-patient.css"
    >

</head>

<body>

<div class="add-patient-page">

    <div class="patient-form-card">

        <!-- =========================
             HEADER
        ========================== -->

        <div class="form-header">

            <div class="form-header-icon">
                +
            </div>

            <div>

                <h1>Add New Patient</h1>

                <p>
                    Create a new patient profile
                </p>

            </div>

        </div>


        <form
            action="save-patient.php"
            method="POST"
            class="patient-form"
        >


            <!-- =========================
                 PERSONAL INFORMATION
            ========================== -->

            <div class="form-section">

                <div class="section-heading">

                    <div class="section-number">
                        01
                    </div>

                    <div>

                        <h2>Personal Information</h2>

                        <p>
                            Basic information about the patient
                        </p>

                    </div>

                </div>


                <div class="form-grid">


                    <!-- FULL NAME -->

                    <div class="form-group">

                        <label>
                            Full Name
                            <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            placeholder="Enter patient's full name"
                            required
                        >

                    </div>


                    <!-- EMAIL -->

                    <div class="form-group">

                        <label>
                            Email Address
                        </label>

                        <input
                            type="email"
                            name="email"
                            placeholder="patient@example.com"
                        >

                    </div>


                    <!-- PHONE -->

                    <div class="form-group">

                        <label>
                            Phone Number
                        </label>

                        <input
                            type="tel"
                            name="phone"
                            placeholder="Enter phone number"
                        >

                    </div>


                    <!-- DATE OF BIRTH -->

                    <div class="form-group">

                        <label>
                            Date of Birth
                        </label>

                        <input
                            type="date"
                            name="date_of_birth"
                        >

                    </div>


                    <!-- GENDER -->

                    <div class="form-group">

                        <label>
                            Gender
                        </label>

                        <select name="gender">

                            <option value="">
                                Select gender
                            </option>

                            <option value="male">
                                Male
                            </option>

                            <option value="female">
                                Female
                            </option>

                            <option value="other">
                                Other
                            </option>

                        </select>

                    </div>


                    <!-- BLOOD GROUP -->

                    <div class="form-group">

                        <label>
                            Blood Group
                        </label>

                        <select name="blood_group">

                            <option value="">
                                Select blood group
                            </option>

                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>

                        </select>

                    </div>

                </div>

            </div>



            <!-- =========================
                 HEALTH INFORMATION
            ========================== -->

            <div class="form-section">

                <div class="section-heading">

                    <div class="section-number">
                        02
                    </div>

                    <div>

                        <h2>Health Information</h2>

                        <p>
                            Basic health information
                        </p>

                    </div>

                </div>


                <div class="form-grid">


                    <!-- HEIGHT -->

                    <div class="form-group">

                        <label>
                            Height
                            <small>(cm)</small>
                        </label>

                        <input
                            type="number"
                            name="height_cm"
                            step="0.01"
                            min="0"
                            placeholder="e.g. 168"
                        >

                    </div>


                    <!-- WEIGHT -->

                    <div class="form-group">

                        <label>
                            Weight
                            <small>(kg)</small>
                        </label>

                        <input
                            type="number"
                            name="weight_kg"
                            step="0.01"
                            min="0"
                            placeholder="e.g. 72.5"
                        >

                    </div>


                    <!-- ALLERGIES -->

                    <div class="form-group full-width">

                        <label>
                            Allergies
                        </label>

                        <textarea
                            name="allergies"
                            placeholder="Enter known allergies, e.g. Penicillin, Aspirin..."
                        ></textarea>

                    </div>


                    <!-- MEDICAL CONDITIONS -->

                    <div class="form-group full-width">

                        <label>
                            Medical Conditions
                        </label>

                        <textarea
                            name="medical_conditions"
                            placeholder="Enter existing medical conditions, e.g. Hypertension, Diabetes..."
                        ></textarea>

                    </div>

                </div>

            </div>



            <!-- =========================
                 ADDRESS
            ========================== -->

            <div class="form-section">

                <div class="section-heading">

                    <div class="section-number">
                        03
                    </div>

                    <div>

                        <h2>Address</h2>

                        <p>
                            Patient's residential details
                        </p>

                    </div>

                </div>


                <div class="form-grid">


                    <!-- ADDRESS -->

                    <div class="form-group full-width">

                        <label>
                            Address
                        </label>

                        <textarea
                            name="address"
                            placeholder="Enter complete address"
                        ></textarea>

                    </div>


                    <!-- CITY -->

                    <div class="form-group">

                        <label>
                            City
                        </label>

                        <input
                            type="text"
                            name="city"
                            placeholder="Enter city"
                        >

                    </div>


                    <!-- STATE -->

                    <div class="form-group">

                        <label>
                            State
                        </label>

                        <input
                            type="text"
                            name="state"
                            placeholder="Enter state"
                        >

                    </div>


                    <!-- PINCODE -->

                    <div class="form-group">

                        <label>
                            Pincode
                        </label>

                        <input
                            type="text"
                            name="pincode"
                            maxlength="10"
                            placeholder="Enter pincode"
                        >

                    </div>

                </div>

            </div>



            <!-- =========================
                 EMERGENCY CONTACT
            ========================== -->

            <div class="form-section emergency-section">

                <div class="section-heading">

                    <div class="section-number emergency-number">
                        04
                    </div>

                    <div>

                        <h2>Emergency Contact</h2>

                        <p>
                            Contact person in case of emergency
                        </p>

                    </div>

                </div>


                <div class="form-grid">


                    <!-- CONTACT NAME -->

                    <div class="form-group">

                        <label>
                            Contact Name
                        </label>

                        <input
                            type="text"
                            name="emergency_contact_name"
                            placeholder="e.g. Sunita Kumar"
                        >

                    </div>


                    <!-- CONTACT PHONE -->

                    <div class="form-group">

                        <label>
                            Contact Phone
                        </label>

                        <input
                            type="tel"
                            name="emergency_contact_phone"
                            placeholder="Emergency contact number"
                        >

                    </div>

                </div>

            </div>



            <!-- =========================
                 ACTIONS
            ========================== -->

            <div class="form-actions">

                <a
                    href="patients.php"
                    class="cancel-btn"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="create-patient-btn"
                >
                    <span>+</span>
                    Create Patient
                </button>

            </div>


        </form>

    </div>

</div>

</body>

</html>