<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/helpers.php");

    if (!isset($_GET["id"]) || $_GET["id"] == "") {
        error("ID requerido");
    }

    $id = intval($_GET["id"]);
    $stmt = $con->prepare("SELECT * FROM v_products WHERE id_product = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $product = $res->fetch_assoc();
        if (empty($product["image"])) $product["image"] = "assets/default.webp";
        $stmt->close();
        success(["product" => $product]);
    } else {
        $stmt->close();
        error("Producto no encontrado");
    }
?>