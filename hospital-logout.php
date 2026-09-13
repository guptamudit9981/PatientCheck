<?php

session_start();

unset($_SESSION['hospital_id']);
unset($_SESSION['hospital_name']);
unset($_SESSION['hospital_code']);

header("Location: hospital-login.php");
exit;
