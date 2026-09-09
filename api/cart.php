<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/helpers.php");
    @session_start();

    $action = $_GET["action"] ?? $_POST["action"] ?? "";
    $logged_in = is_logged_in();

    function get_cart_count($con, $id_user) {
        $stmt = $con->prepare("SELECT COALESCE(SUM(quantity), 0) AS total FROM cart WHERE id_user = ?");
        $stmt->bind_param("i", $id_user);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()["total"];
        $stmt->close();
        return $count;
    }

    function delete($id_product) {
        global $con, $logged_in;
        if ($logged_in) {
            $id_user = get_user_id();
            $stmt = $con->prepare("DELETE FROM cart WHERE id_user = ? AND id_product = ?");
            $stmt->bind_param("ii", $id_user, $id_product);
            $stmt->execute();
            $stmt->close();
        } else {
            unset($_SESSION["cart"][$id_product]);
        }
    }

    if ($action === "get") {
        if ($logged_in) {
            $id_user = get_user_id();
            $stmt = $con->prepare("SELECT c.id_cart, c.id_product, c.quantity,
                    p.name AS product_name, p.artist, p.price, COALESCE(NULLIF(p.image, ''), 'assets/default.webp') AS image, p.stock
                    FROM cart c
                    INNER JOIN products p ON c.id_product = p.id_product
                    WHERE c.id_user = ?");
            $stmt->bind_param("i", $id_user);
            $stmt->execute();
            $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            success(["items" => $items]);
        } else {
            $cart = $_SESSION["cart"] ?? [];
            if (empty($cart)) {
                success(["items" => []]);
            }
            $ids = array_keys($cart);
            $ids_str = implode(",", array_map('intval', $ids));
            $res = $con->query("SELECT id_product, name AS product_name, artist, price, COALESCE(NULLIF(image, ''), 'assets/default.webp') AS image, stock
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
            $count = get_cart_count($con, $id_user);
            success(["count" => $count]);
        } else {
            $cart = $_SESSION["cart"] ?? [];
            success(["count" => array_sum($cart)]);
        }
    }

    if ($action === "add") {
        $id_product = intval($_POST["id_product"] ?? 0);
        $qty = max(1, intval($_POST["quantity"] ?? 1));
        if ($id_product == 0) {
            error("ID requerido");
        }

        if ($logged_in) {
            $id_user = get_user_id();

            $stmt = $con->prepare("SELECT id_cart, quantity FROM cart WHERE id_user = ? AND id_product = ?");
            $stmt->bind_param("ii", $id_user, $id_product);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $new_qty = $row["quantity"] + $qty;
                $stmt->close();

                $stmt_up = $con->prepare("UPDATE cart SET quantity = ? WHERE id_cart = ?");
                $stmt_up->bind_param("ii", $new_qty, $row['id_cart']);
                $stmt_up->execute();
                $stmt_up->close();
            } else {
                $stmt->close();

                $stmt_ins = $con->prepare("INSERT INTO cart (id_user, id_product, quantity) VALUES (?, ?, ?)");
                $stmt_ins->bind_param("iii", $id_user, $id_product, $qty);
                $stmt_ins->execute();
                $stmt_ins->close();
            }

            $count = get_cart_count($con, $id_user);
        } else {
            if (!isset($_SESSION["cart"])) {
                $_SESSION["cart"] = [$id_product => $qty];
            } else {
                $_SESSION["cart"][$id_product] = ($_SESSION["cart"][$id_product] ?? 0) + $qty;
            }
            $count = array_sum($_SESSION["cart"]);
        }
        success(["count" => $count]);
    }

    if ($action === "update") {
        $id_product = intval($_POST["id_product"] ?? 0);
        $quantity = intval($_POST["quantity"] ?? 0);

        if ($quantity <= 0) {
            delete($id_product);
        } else {
            if ($logged_in) {
                $id_user = get_user_id();
                $stmt = $con->prepare("UPDATE cart SET quantity = ? WHERE id_user = ? AND id_product = ?");
                $stmt->bind_param("iii", $quantity, $id_user, $id_product);
                $stmt->execute();
                $stmt->close();
            } else {
                $_SESSION["cart"][$id_product] = $quantity;
            }
        }
        $count = $logged_in
            ? get_cart_count($con, get_user_id())
            : array_sum($_SESSION["cart"] ?? []);
        success(["count" => $count]);
    }

    if ($action === "remove") {
        $id_product = intval($_POST["id_product"] ?? 0);
        delete($id_product);
        $count = $logged_in
            ? get_cart_count($con, get_user_id())
            : array_sum($_SESSION["cart"] ?? []);
        success(["count" => $count]);
    }

    error("Accion no valida");
?>