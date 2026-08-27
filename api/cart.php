<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/helpers.php");
    session_start();

    $action = $_GET["action"] ?? $_POST["action"] ?? "";
    $logged_in = is_logged_in();

    function delete($id_product) {
        global $con, $logged_in;
        if ($logged_in) {
            $id_user = get_user_id();
            $con->query("DELETE FROM cart WHERE id_user = $id_user AND id_product = " . intval($id_product));
        } else {
            unset($_SESSION["cart"][$id_product]);
        }
    }

    if ($action === "get") {
        if ($logged_in) {
            $id_user = get_user_id();
            $res = $con->query("SELECT c.id_cart, c.id_product, c.quantity,
                    p.name AS product_name, p.artist, p.price, p.image, p.stock
                    FROM cart c
                    INNER JOIN products p ON c.id_product = p.id_product
                    WHERE c.id_user = $id_user");
            success(["items" => $res->fetch_all(MYSQLI_ASSOC)]);
        } else {
            $cart = $_SESSION["cart"] ?? [];
            if (empty($cart)) {
                success(["items" => []]);
            }
            $ids = array_keys($cart);
            $ids_str = implode(",", $ids);
            $res = $con->query("SELECT id_product, name AS product_name, artist, price, image, stock
                    FROM products WHERE id_product IN ($ids_str)");
            $products = $res->fetch_all(MYSQLI_ASSOC);
            foreach ($products as &$product) {
                $product["quantity"] = $cart[$product["id_product"]];
            }
            success(["items" => $products]);
        }
    }

    if ($action === "count") {
        if ($logged_in) {
            $id_user = get_user_id();
            $res = $con->query("SELECT COALESCE(SUM(quantity), 0) AS total FROM cart WHERE id_user = $id_user");
            success(["count" => $res->fetch_assoc()["total"]]);
        } else {
            $cart = $_SESSION["cart"] ?? [];
            success(["count" => array_sum($cart)]);
        }
    }

    if ($action === "add") {
        $id_product = intval($_POST["id_product"] ?? 0);
        if ($id_product == 0) {
            error("ID requerido");
        }

        if ($logged_in) {
            $id_user = get_user_id();
            $res = $con->query("SELECT id_cart, quantity FROM cart WHERE id_user = $id_user AND id_product = $id_product");
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $new_qty = $row["quantity"] + 1;
                $con->query("UPDATE cart SET quantity = $new_qty WHERE id_cart = {$row['id_cart']}");
            } else {
                $con->query("INSERT INTO cart (id_user, id_product, quantity) VALUES ($id_user, $id_product, 1)");
            }
        } else {
            if (!isset($_SESSION["cart"])) {
                $_SESSION["cart"] = [$id_product => 1];
            } else {
                $_SESSION["cart"][$id_product]++;
            }
        }
        success();
    }

    if ($action === "update") {
        $id_product = intval($_POST["id_product"] ?? 0);
        $quantity = intval($_POST["quantity"] ?? 0);

        if ($quantity <= 0) {
            delete($id_product);
        } else {
            if ($logged_in) {
                $id_user = get_user_id();
                $con->query("UPDATE cart SET quantity = $quantity WHERE id_user = $id_user AND id_product = $id_product");
            } else {
                $_SESSION["cart"][$id_product] = $quantity;
            }
        }
        success();
    }

    if ($action === "remove") {
        $id_product = intval($_POST["id_product"] ?? 0);
        delete($id_product);
        success();
    }

    error("Accion no valida");
?>
