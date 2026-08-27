<?php
    $con = new mysqli("localhost", "root", "", "yesterdays_records");
    $con->set_charset("utf8mb4");

    include(__DIR__."/views.php");
    createViews($con);
?>