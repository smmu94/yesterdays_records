<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/helpers.php");

    $res = $con->query("SELECT * FROM categories ORDER BY name");

    if ($res && $res->num_rows > 0) {
        success(["categories" => $res->fetch_all(MYSQLI_ASSOC)]);
    } else {
        success(["categories" => []]);
    }
?>
