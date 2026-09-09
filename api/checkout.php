<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/helpers.php");
    include(__DIR__."/../config/keys.php");
    include(__DIR__."/../vendor/autoload.php");
    @session_start();

    $action = $_GET["action"] ?? $_POST["action"] ?? "";

    if (!is_logged_in()) {
        error("Debes iniciar sesion");
    }

    $id_user = get_user_id();

    if ($action === "confirm") {
        $id_order = intval($_POST["id_order"] ?? 0);
        $stmt = $con->prepare("UPDATE orders SET status = 'paid' WHERE id_order = ? AND id_user = ?");
        $stmt->bind_param("ii", $id_order, $id_user);
        $stmt->execute();
        $stmt->close();
        success();
        exit;
    }

    $stmt = $con->prepare("SELECT c.id_product, c.quantity, p.name, p.price, p.stock
                          FROM cart c
                          INNER JOIN products p ON c.id_product = p.id_product
                          WHERE c.id_user = ?");
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($items)) {
        error("El carrito esta vacio");
    }

    foreach ($items as $item) {
        if ($item["quantity"] > $item["stock"]) {
            error("Stock insuficiente para {$item['name']}");
        }
    }

    $id_address = null;

    if (isset($_POST["id_address"])) {
        $id_address = intval($_POST["id_address"]);
        $stmt = $con->prepare("SELECT id_address FROM addresses WHERE id_address = ? AND id_user = ?");
        $stmt->bind_param("ii", $id_address, $id_user);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $stmt->close();
            error("Direccion no valida");
        }
        $stmt->close();
    } else if (isset($_POST["new_address"])) {
        $street = $_POST["street"];
        $city = intval($_POST["city"]);
        $cp = $_POST["cp"];

        $stmt = $con->prepare("INSERT INTO addresses (id_user, id_city, cp, street_address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $id_user, $city, $cp, $street);
        $stmt->execute();
        $id_address = $con->insert_id;
        $stmt->close();
    }

    $total = 0;
    foreach ($items as $item) {
        $total += $item["price"] * $item["quantity"];
    }

    $stmt = $con->prepare("INSERT INTO orders (id_user, id_address, total, status, date) VALUES (?, ?, ?, 'pending', NOW())");
    $stmt->bind_param("iid", $id_user, $id_address, $total);
    $stmt->execute();
    $id_order = $con->insert_id;
    $stmt->close();

    foreach ($items as $item) {
        $subtotal = $item["price"] * $item["quantity"];
        $id_product = $item['id_product'];
        $quantity = $item['quantity'];

        $stmt_ins = $con->prepare("INSERT INTO order_detail (id_order, id_product, quantity, unit_price) VALUES (?, ?, ?, ?)");
        $stmt_ins->bind_param("iiid", $id_order, $id_product, $quantity, $subtotal);
        $stmt_ins->execute();
        $stmt_ins->close();

        $new_stock = $item["stock"] - $quantity;
        $stmt_stock = $con->prepare("UPDATE products SET stock = ? WHERE id_product = ?");
        $stmt_stock->bind_param("ii", $new_stock, $id_product);
        $stmt_stock->execute();
        $stmt_stock->close();
    }

    $stmt = $con->prepare("DELETE FROM cart WHERE id_user = ?");
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $stmt->close();

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
        "success_url" => APP_URL . "/index.html#/order-success?id=$id_order",
        "cancel_url" => APP_URL . "/index.html#/cart?canceled=1",
        "metadata" => ["id_order" => $id_order],
    ]);

    success(["url" => $session->url]);
?>