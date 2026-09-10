<?php
    error_reporting(0);
    ini_set('display_errors', 0);
    ob_start();
    $con = new mysqli("sql102.infinityfree.com", "if0_42885270", "Smmu24940729", "if0_42885270_yesterdays_records");
    $con->set_charset("utf8mb4");