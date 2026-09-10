<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/helpers.php");

    $page = max(1, intval($_GET["page"] ?? 1));
    $limit = max(1, min(50, intval($_GET["limit"] ?? 20)));
    $offset = ($page - 1) * $limit;

    $sql = "SELECT p.*, c.name AS category_name, g.name AS genre_name FROM products p INNER JOIN categories c ON p.id_category = c.id_category LEFT JOIN genres g ON p.id_genre = g.id_genre";
    $countSql = "SELECT COUNT(*) AS total FROM products p INNER JOIN categories c ON p.id_category = c.id_category LEFT JOIN genres g ON p.id_genre = g.id_genre";
    $condition = "";
    $types = "";
    $params = [];

    if (isset($_GET["category"]) && $_GET["category"] != "") {
        $condition .= " AND id_category = ?";
        $types .= "i";
        $params[] = intval($_GET["category"]);
    }

    if (isset($_GET["genre"]) && $_GET["genre"] != "") {
        $condition .= " AND id_genre = ?";
        $types .= "i";
        $params[] = intval($_GET["genre"]);
    }

    if (isset($_GET["search"]) && $_GET["search"] != "") {
        $search = "%" . $_GET["search"] . "%";
        $condition .= " AND (name LIKE ? OR artist LIKE ?)";
        $types .= "ss";
        $params[] = $search;
        $params[] = $search;
    }

    if ($condition != "") {
        $where = " WHERE " . substr($condition, 4);
        $sql .= $where;
        $countSql .= $where;
    }

    $stmt = $con->prepare($countSql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()["total"];
    $stmt->close();

    $sql .= " ORDER BY name LIMIT $limit OFFSET $offset";
    $stmt = $con->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->num_rows > 0 ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    success([
        "products" => $products,
        "total" => intval($total),
        "page" => $page,
        "pages" => ceil($total / $limit)
    ]);
?>