<?php
    include(__DIR__."/../config/database.php");

    $sql_all_products = "SELECT p.id_product, p.name AS product_name,
                        p.description, p.artist, p.price,
                        p.stock, p.image, p.date,
                        c.name AS category_name,
                        g.name AS genre_name
                        FROM products p
                        INNER JOIN categories c
                        ON p.id_category = c.id_category
                        LEFT JOIN genres g
                        ON p.id_genre = g.id_genre";

    $condition = "";

    if(isset($_GET["category"]) && $_GET["category"] != ""){
        $condition.=" AND p.id_category = ".$_GET["category"];
    }

    if(isset($_GET["genre"]) && $_GET["genre"] != ""){
        $condition.=" AND p.id_genre = ".$_GET["genre"];
    }

    if (isset($_GET["search"]) && $_GET["search"] != "") {
        $search = $_GET["search"];
        $condition .= " AND (p.name LIKE '%$search%' OR p.artist LIKE '%$search%')";
    }

    if($condition != "") {
        $sql_all_products.= " WHERE ".substr($condition, 5);
    }

    $res = $con->query($sql_all_products);

    if($res->num_rows >0) {
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    } else {
        echo json_encode([]);
    }
?>