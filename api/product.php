<?php
    include(__DIR__."/../config/database.php");

    
    if(!isset($_GET["id"]) || $_GET["id"] == "") {
        echo json_encode(["ok" => false, "message" => "ID requerido"]);
        exit;
    }
        
    $id = $_GET["id"];
    $sql_producto = "SELECT p.id_product, p.name AS product_name,
                        p.description, p.artist, p.price,
                        p.stock, p.image, p.date,
                        c.name AS category_name,
                        g.name AS genre_name
                        FROM products p
                        INNER JOIN categories c
                        ON p.id_category = c.id_category
                        LEFT JOIN genres g
                        ON p.id_genre = g.id_genre
                        WHERE id_product = $id";

    $res = $con->query($sql_producto);

    if($res->num_rows > 0){
        echo json_encode(["ok" => true, "product" => $res->fetch_assoc()]);
    } else {
        echo json_encode(["ok" => false, "message" => "Este producto no existe"]);
    }
?>