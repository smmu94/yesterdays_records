<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/helpers.php");
    @session_start();

    if (!is_logged_in()) {
        error("Debes iniciar sesion");
    }

    $id_user = get_user_id();

    $res = $con->query("SELECT name, email FROM users WHERE id_user = $id_user");
    $user = $res->fetch_assoc();

    $res = $con->query("SELECT o.id_order, o.total, o.status, o.date,
                        a.street_address, a.cp, ci.name AS city_name
                        FROM orders o
                        INNER JOIN addresses a ON o.id_address = a.id_address
                        INNER JOIN cities ci ON a.id_city = ci.id_city
                        WHERE o.id_user = $id_user
                        ORDER BY o.date DESC");
    $orders = $res->fetch_all(MYSQLI_ASSOC);

    foreach ($orders as &$order) {
        $res = $con->query("SELECT od.quantity, od.unit_price,
                            p.name AS product_name, p.artist, p.image
                            FROM order_detail od
                            INNER JOIN products p ON od.id_product = p.id_product
                            WHERE od.id_order = {$order['id_order']}");
        $order["items"] = $res->fetch_all(MYSQLI_ASSOC);
    }

    success(["user" => $user, "orders" => $orders]);
?>