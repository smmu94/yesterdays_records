<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/helpers.php");

    if (!isset($_GET["id"]) || $_GET["id"] == "") {
        error("ID requerido");
    }

    $id = intval($_GET["id"]);
    $res = $con->query("SELECT * FROM v_products WHERE id_product = $id");

    if ($res && $res->num_rows > 0) {
        success(["product" => $res->fetch_assoc()]);
    } else {
        error("Producto no encontrado");
    }
?>
