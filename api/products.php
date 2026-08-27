<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/helpers.php");

    $sql = "SELECT * FROM v_products";

    $condition = "";

    if (isset($_GET["category"]) && $_GET["category"] != "") {
        $condition .= " AND id_category = " . intval($_GET["category"]);
    }

    if (isset($_GET["genre"]) && $_GET["genre"] != "") {
        $condition .= " AND id_genre = " . intval($_GET["genre"]);
    }

    if (isset($_GET["search"]) && $_GET["search"] != "") {
        $search = $con->real_escape_string($_GET["search"]);
        $condition .= " AND (name LIKE '%$search%' OR artist LIKE '%$search%')";
    }

    if ($condition != "") {
        $sql .= " WHERE " . substr($condition, 5);
    }

    $res = $con->query($sql);

    if ($res && $res->num_rows > 0) {
        success(["products" => $res->fetch_all(MYSQLI_ASSOC)]);
    } else {
        success(["products" => []]);
    }
?>
