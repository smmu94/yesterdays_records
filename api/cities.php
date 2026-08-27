<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/helpers.php");

    $res = $con->query("SELECT id_city, name FROM cities ORDER BY name");

    if ($res && $res->num_rows > 0) {
        success(["cities" => $res->fetch_all(MYSQLI_ASSOC)]);
    } else {
        success(["cities" => []]);
    }
?>
