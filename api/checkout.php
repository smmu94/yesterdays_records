<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/keys.php");
    include(__DIR__."/../vendor/autoload.php");

    session_start();

    $logged_in = isset($_SESSION["logueado"]);
    $action = $_GET["action"] ?? $_POST["action"] ?? "";

    if (!$logged_in) {
        echo json_encode(["ok" => false, "error" => "Debes iniciar sesión"]);
        exit;
    }

    $id_user = $_SESSION["logueado"]["id"];

    if ($action === "confirm") {
        $id_order = intval($_POST["id_order"] ?? 0);
        $sql_update_order_status = "UPDATE orders SET status = 'paid', paid_date = NOW() 
                                    WHERE id_order = $id_order AND id_user = $id_user";
        $con->query($sql_update_order_status);
        echo json_encode(["ok" => true]);
        exit;
    }

    $sql_cart = "SELECT c.id_product, c.quantity, p.name, p.price, p.stock
                 FROM cart c
                 INNER JOIN products p ON c.id_product = p.id_product
                 WHERE c.id_user = $id_user";
    $res = $con->query($sql_cart);
    $items = $res->fetch_all(MYSQLI_ASSOC);

    foreach ($items as $item) {
        if ($item["quantity"] > $item["stock"]) {
            echo json_encode(["ok" => false, "error" => "Stock insuficiente para {$item['name']}"]);
            exit;
        }
    }

    $id_address = null;

    if (isset($_POST["id_address"])) {
        $id_address = intval($_POST["id_address"]);
        $sql_check = "SELECT id_address FROM addresses 
                      WHERE id_address = $id_address AND id_user = $id_user";
        $res_check = $con->query($sql_check);
        if ($res_check->num_rows === 0) {
            echo json_encode(["ok" => false, "error" => "Dirección no válida"]);
            exit;
        }
    } else if (isset($_POST["new_address"])) {
        $street = $_POST["street"];
        $city = intval($_POST["city"]);
        $cp = $_POST["cp"];

        $sql_addr = "INSERT INTO addresses (id_user, id_city, cp, street_address)
                     VALUES ($id_user, $city, '$cp', '$street')";
        $con->query($sql_addr);
        $id_address = $con->insert_id;
    }

    // Calcular total
    $total = 0;
    foreach ($items as $item) {
        $total += $item["price"] * $item["quantity"];
    }

    // Crear pedido en BD
    $sql_order = "INSERT INTO orders (id_user, id_address, total, status, date)
                  VALUES ($id_user, $id_address, $total, 'pending', NOW())";
    $con->query($sql_order);
    $id_order = $con->insert_id;

    // Crear detalle del pedido + descontar stock
    foreach ($items as $item) {
        $subtotal = $item["price"] * $item["quantity"];
        $sql_detail = "INSERT INTO order_detail (id_order, id_product, quantity, unit_price)
                       VALUES ($id_order, {$item['id_product']}, {$item['quantity']}, $subtotal)";
        $con->query($sql_detail);

        $new_stock = $item["stock"] - $item["quantity"];
        $sql_update_stock = "UPDATE products SET stock = $new_stock WHERE id_product = {$item['id_product']}";
        $con->query($sql_update_stock);
    }

    // Limpiar carrito
    $sql_delete_cart = "DELETE FROM cart WHERE id_user = $id_user";
    $con->query($sql_delete_cart);

    // Stripe Checkout Session
    $stripe = new \Stripe\StripeClient(STRIPE_SECRET);

    $line_items = [];
    foreach ($items as $item) {
        $line_items[] = [
            "price_data" => [
                "currency" => "eur",
                "product_data" => ["name" => $item["name"]],
                "unit_amount" => intval($item["price"] * 100),
            ],
            "quantity" => $item["quantity"],
        ];
    }

    $session = $stripe->checkout->sessions->create([
        "payment_method_types" => ["card"],
        "line_items" => $line_items,
        "mode" => "payment",
        "success_url" => APP_URL."/index.html#/order-success?id=$id_order",
        "cancel_url" => APP_URL."/index.html#/cart?canceled=1",
        "metadata" => ["id_order" => $id_order],
    ]);

    echo json_encode(["ok" => true, "url" => $session->url]);
?>