<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/helpers.php");

    if (!isset($_GET["id"]) || $_GET["id"] == "") {
        error("ID requerido");
    }

    $id = intval($_GET["id"]);
    $stmt = $con->prepare("SELECT p.*, c.name AS category_name, g.name AS genre_name FROM products p INNER JOIN categories c ON p.id_category = c.id_category LEFT JOIN genres g ON p.id_genre = g.id_genre WHERE p.id_product = ?");
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