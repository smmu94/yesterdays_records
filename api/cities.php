<?php
    include(__DIR__."/../config/database.php");
    $sql_cities = "SELECT id_city, name FROM cities ORDER BY name";
    $res = $con->query($sql_cities);
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
?>