<?php
    include(__DIR__."/../config/database.php");
    session_start();

    if (!isset($_SESSION["logueado"])) {
        echo json_encode([]);
        exit;
    }

    $id_user = $_SESSION["logueado"]["id"];
    $sql = "SELECT a.id_address, a.street_address, a.cp, c.name AS city_name
            FROM addresses a
            INNER JOIN cities c ON a.id_city = c.id_city
            WHERE a.id_user = $id_user";
    $res = $con->query($sql);
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
?>