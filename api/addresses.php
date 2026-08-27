<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/helpers.php");
    session_start();

    if (!is_logged_in()) {
        success(["addresses" => []]);
    }

    $id_user = get_user_id();
    $res = $con->query("SELECT a.id_address, a.street_address, a.cp, c.name AS city_name
                        FROM addresses a
                        INNER JOIN cities c ON a.id_city = c.id_city
                        WHERE a.id_user = $id_user");

    if ($res && $res->num_rows > 0) {
        success(["addresses" => $res->fetch_all(MYSQLI_ASSOC)]);
    } else {
        success(["addresses" => []]);
    }
?>
