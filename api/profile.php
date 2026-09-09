<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/helpers.php");
    @session_start();

    if (!is_logged_in()) {
        error("Debes iniciar sesion");
    }

    $id_user = get_user_id();
    $page = max(1, intval($_GET["page"] ?? 1));
    $limit = 50;
    $offset = ($page - 1) * $limit;

    $stmt = $con->prepare("SELECT name, email FROM users WHERE id_user = ?");
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $con->prepare("SELECT COUNT(*) AS total FROM orders WHERE id_user = ?");
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()["total"];
    $stmt->close();

    $stmt = $con->prepare("SELECT o.id_order, o.total, o.status, o.date,
                          a.street_address, a.cp, ci.name AS city_name
                          FROM orders o
                          INNER JOIN addresses a ON o.id_address = a.id_address
                          INNER JOIN cities ci ON a.id_city = ci.id_city
                          WHERE o.id_user = ?
                          ORDER BY o.date DESC
                          LIMIT ? OFFSET ?");
    $stmt->bind_param("iii", $id_user, $limit, $offset);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($orders as &$order) {
        $id_order = $order['id_order'];
        $stmt = $con->prepare("SELECT od.quantity, od.unit_price,
                              p.name AS product_name, p.artist, COALESCE(NULLIF(p.image, ''), 'assets/default.webp') AS image
                              FROM order_detail od
                              INNER JOIN products p ON od.id_product = p.id_product
                              WHERE od.id_order = ?");
        $stmt->bind_param("i", $id_order);
        $stmt->execute();
        $order["items"] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    success([
        "user" => $user,
        "orders" => $orders,
        "total" => intval($total),
        "page" => $page,
        "pages" => ceil($total / $limit)
    ]);
?>