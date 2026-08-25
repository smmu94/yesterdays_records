<?php
    include(__DIR__."/../config/database.php");
    session_start();

    $action = $_GET["action"] ?? $_POST["action"] ?? "";
    $logged_in = isset($_SESSION["logueado"]);

    function delete($id_product){
        global $con;
        global $logged_in;
        if ($logged_in) {
            $id_user = $_SESSION["logueado"]["id"];
            $sql_delete = "DELETE FROM cart 
                            WHERE id_user = $id_user AND id_product = $id_product";
            $con->query($sql_delete);
        } else {
            unset($_SESSION["cart"][$id_product]);
        }
    }

    if ($action === "get") {
        if ($logged_in) {
            $id_user = $_SESSION["logueado"]["id"];
            $sql = "SELECT c.id_cart, c.id_product, c.quantity,
                    p.name AS product_name, p.artist, p.price, p.image, p.stock
                    FROM cart c
                    INNER JOIN products p 
                    ON c.id_product = p.id_product
                    WHERE c.id_user = $id_user";
            $res = $con->query($sql);
            echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        } else {
            $cart = $_SESSION["cart"] ?? [];
            if (empty($cart)) {
                echo json_encode([]);
                return;
            }
            $ids = array_keys($cart);
            $ids_str = implode(",", $ids);
            $sql = "SELECT id_product, name AS product_name, artist, price, image, stock
                    FROM products WHERE id_product IN ($ids_str)";
            $res = $con->query($sql);
            $products = $res->fetch_all(MYSQLI_ASSOC);
            foreach ($products as &$product) {
                $product["quantity"] = $cart[$product["id_product"]];
            }
            echo json_encode($products);
        }
        return;
    }

    if ($action === "count") {
        if ($logged_in) {
            $id_user = $_SESSION["logueado"]["id"];
            $sql = "SELECT COALESCE(SUM(quantity), 0) AS total 
                    FROM cart 
                    WHERE id_user = $id_user";
            $res = $con->query($sql);
            echo json_encode(["count" => $res->fetch_assoc()["total"]]);
        } else {
            $cart = $_SESSION["cart"] ?? [];
            $total = array_sum($cart);
            echo json_encode(["count" => $total]);
        }
        return;
    }

    if ($action === "add") {
        $id_product = $_POST["id_product"] ?? "";
        if ($id_product == "") {
            echo json_encode(["ok" => false, "error" => "ID requerido"]);
            return;
        }

        if ($logged_in) {
            $id_user = $_SESSION["logueado"]["id"];
            $sql_check = "SELECT id_cart, quantity 
                          FROM cart 
                          WHERE id_user = $id_user AND id_product = $id_product";
            $res = $con->query($sql_check);
            if ($row = $res->fetch_assoc()) {
                $quantity = $row["quantity"] + 1;
                $id_cart = $row["id_cart"];
                $sql_update_quantity = "UPDATE cart 
                                        SET quantity = $quantity 
                                        WHERE id_cart = $id_cart";
                $con->query($sql_update_quantity);
            } else {
                $sql_insert_product = "INSERT INTO cart (id_user, id_product, quantity) 
                                        VALUES ($id_user, $id_product, 1)";
                $con->query($sql_insert_product);
            }
        } else {
            if (!isset($_SESSION["cart"])){
                $_SESSION["cart"] = [$id_product => 1];
            } else {
                $_SESSION["cart"][$id_product]++;
            } 
        }
        echo json_encode(["ok" => true]);
        return;
    }

  
    if ($action === "update") {
        $id_product = $_POST["id_product"] ?? "";
        $quantity = intval($_POST["quantity"] ?? 0);

        if ($quantity <= 0) {
            delete($id_product);
        } else {
            if ($logged_in) {
                $id_user = $_SESSION["logueado"]["id"];
                $sql_update = "UPDATE cart 
                               SET quantity = $quantity
                               WHERE id_user = $id_user AND id_product = $id_product";
                $con->query($sql_delete);
            } else {
                $_SESSION["cart"][$id_product] = $quantity;
            }
        }
        echo json_encode(["ok" => true]);
        return;
    }

    if ($action === "remove") {
        $id_product = $_POST["id_product"] ?? "";

        delete($id_product);

        echo json_encode(["ok" => true]);
        return;
    }

    echo json_encode(["ok" => false, "error" => "Acción no válida"]);
?>