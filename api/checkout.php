<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/helpers.php");
    include(__DIR__."/../config/keys.php");
    include(__DIR__."/../vendor/autoload.php");
    session_start();

    $action = $_GET["action"] ?? $_POST["action"] ?? "";

    if (!is_logged_in()) {
        error("Debes iniciar sesion");
    }

    $id_user = get_user_id();

    if ($action === "confirm") {
        $id_order = intval($_POST["id_order"] ?? 0);
        $con->query("UPDATE orders SET status = 'paid', paid_date = NOW() 
                     WHERE id_order = $id_order AND id_user = $id_user");
        success();
    }

    $res = $con->query("SELECT c.id_product, c.quantity, p.name, p.price, p.stock
                        FROM cart c
                        INNER JOIN products p ON c.id_product = p.id_product
                        WHERE c.id_user = $id_user");
    $items = $res->fetch_all(MYSQLI_ASSOC);

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
        $res_check = $con->query("SELECT id_address FROM addresses 
                                  WHERE id_address = $id_address AND id_user = $id_user");
        if ($res_check->num_rows === 0) {
            error("Direccion no valida");
        }
    } else if (isset($_POST["new_address"])) {
        $street = $con->real_escape_string($_POST["street"]);
        $city = intval($_POST["city"]);
        $cp = $con->real_escape_string($_POST["cp"]);

        $con->query("INSERT INTO addresses (id_user, id_city, cp, street_address)
                     VALUES ($id_user, $city, '$cp', '$street')");
        $id_address = $con->insert_id;
    }

    $total = 0;
    foreach ($items as $item) {
        $total += $item["price"] * $item["quantity"];
    }

    $con->query("INSERT INTO orders (id_user, id_address, total, status, date)
                 VALUES ($id_user, $id_address, $total, 'pending', NOW())");
    $id_order = $con->insert_id;

    foreach ($items as $item) {
        $subtotal = $item["price"] * $item["quantity"];
        $con->query("INSERT INTO order_detail (id_order, id_product, quantity, unit_price)
                     VALUES ($id_order, {$item['id_product']}, {$item['quantity']}, $subtotal)");

        $new_stock = $item["stock"] - $item["quantity"];
        $con->query("UPDATE products SET stock = $new_stock WHERE id_product = {$item['id_product']}");
    }

    $con->query("DELETE FROM cart WHERE id_user = $id_user");

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
