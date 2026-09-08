<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/helpers.php");

    $page = max(1, intval($_GET["page"] ?? 1));
    $limit = max(1, min(50, intval($_GET["limit"] ?? 20)));
    $offset = ($page - 1) * $limit;

    $sql = "SELECT * FROM v_products";
    $countSql = "SELECT COUNT(*) AS total FROM v_products";

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
        $where = " WHERE " . substr($condition, 5);
        $sql .= $where;
        $countSql .= $where;
    }

    $totalRes = $con->query($countSql);
    $total = $totalRes->fetch_assoc()["total"];

    $sql .= " ORDER BY name LIMIT $limit OFFSET $offset";
    $res = $con->query($sql);

    success([
        "products" => $res->num_rows > 0 ? $res->fetch_all(MYSQLI_ASSOC) : [],
        "total" => intval($total),
        "page" => $page,
        "pages" => ceil($total / $limit)
    ]);
?>