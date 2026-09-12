<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/helpers.php");
    @session_start();

    $action = $_GET["action"] ?? $_POST["action"] ?? "";

    if (!is_admin()) {
        error("Acceso denegado");
    }

    function handle_image_upload() {
        if (!isset($_FILES["image"]) || $_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
            return null;
        }

        $dir = __DIR__ . "/../uploads/products/";
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $original = pathinfo($_FILES["image"]["name"], PATHINFO_FILENAME);
        $safe = preg_replace('/[^a-zA-Z0-9-_]/', '-', $original);
        $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $filename = $safe."-".time().".".$ext;
        $route = $dir.$filename;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $route)) {
            return "uploads/products/" . $filename;
        }

        return null;
    }

    if ($action === "list_products") {
        $page = max(1, intval($_GET["page"] ?? 1));
        $limit = 100;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT p.id_product, p.name, p.description, p.artist, p.price, p.stock, p.id_category, p.id_genre, p.date, COALESCE(NULLIF(p.image, ''), 'assets/default.webp') AS image, c.name AS category_name, g.name AS genre_name FROM products p INNER JOIN categories c ON p.id_category = c.id_category LEFT JOIN genres g ON p.id_genre = g.id_genre";
        $countSql = "SELECT COUNT(*) AS total FROM products p INNER JOIN categories c ON p.id_category = c.id_category LEFT JOIN genres g ON p.id_genre = g.id_genre";
        $condition = "";
        $types = "";
        $params = [];

        if (isset($_GET["category"]) && $_GET["category"] != "") {
            $condition .= " AND p.id_category = ?";
            $types .= "i";
            $params[] = intval($_GET["category"]);
        }
        if (isset($_GET["search"]) && $_GET["search"] != "") {
            $search = "%" . $_GET["search"] . "%";
            $condition .= " AND (p.name LIKE ? OR p.artist LIKE ?)";
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

        $sql .= " ORDER BY p.name LIMIT $limit OFFSET $offset";
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
    }

    if ($action === "create_product") {
        $name = $_POST["name"] ?? "";
        $artist = $_POST["artist"] ?? "";
        $description = $_POST["description"] ?? "";
        $price = floatval($_POST["price"] ?? 0);
        $stock = intval($_POST["stock"] ?? 0);
        $id_category = intval($_POST["id_category"] ?? 0);
        $id_genre = intval($_POST["id_genre"] ?? 0);

        $image = handle_image_upload();
        if ($image === null) {
            $image = "assets/default.webp";
        }

        $stmt = $con->prepare("INSERT INTO products (name, description, id_category, id_genre, artist, price, stock, image)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiisdis", $name, $description, $id_category, $id_genre, $artist, $price, $stock, $image);
        if ($stmt->execute()) {
            $id = $con->insert_id;
            $stmt->close();
            success(["id" => $id]);
        } else {
            $stmt->close();
            error("Error al crear producto");
        }
    }

    if ($action === "get_product") {
        $id = intval($_GET["id"]);
        $stmt = $con->prepare("SELECT * FROM products WHERE id_product = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $product = $res->fetch_assoc();
            $stmt->close();
            success(["product" => $product]);
        } else {
            $stmt->close();
            error("Producto no encontrado");
        }
    }

    if ($action === "update_product") {
        $id = intval($_POST["id_product"]);
        $name = $_POST["name"] ?? "";
        $artist = $_POST["artist"] ?? "";
        $description = $_POST["description"] ?? "";
        $price = floatval($_POST["price"] ?? 0);
        $stock = intval($_POST["stock"] ?? 0);
        $id_category = intval($_POST["id_category"] ?? 0);
        $id_genre = intval($_POST["id_genre"] ?? 0);

        $image = handle_image_upload();
        if ($image !== null) {
            $stmt_img = $con->prepare("UPDATE products SET image = ? WHERE id_product = ?");
            $stmt_img->bind_param("si", $image, $id);
            $stmt_img->execute();
            $stmt_img->close();
        }

        $stmt = $con->prepare("UPDATE products SET name = ?, description = ?, id_category = ?, id_genre = ?,
                              artist = ?, price = ?, stock = ? WHERE id_product = ?");
        $stmt->bind_param("ssiisdii", $name, $description, $id_category, $id_genre, $artist, $price, $stock, $id);
        if ($stmt->execute()) {
            $stmt->close();
            success();
        } else {
            $stmt->close();
            error("Error al actualizar producto");
        }
    }

    if ($action === "delete_product") {
        $id = intval($_POST["id_product"]);

        $stmt = $con->prepare("SELECT image FROM products WHERE id_product = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $image_path = __DIR__ . "/../" . $row["image"];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        $stmt->close();

        $stmt = $con->prepare("DELETE FROM products WHERE id_product = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $stmt->close();
            success();
        } else {
            $stmt->close();
            error("Error al eliminar producto");
        }
    }

    if ($action === "list_orders") {
        $page = max(1, intval($_GET["page"] ?? 1));
        $limit = 100;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT o.*, u.name AS client_name, u.email, a.street_address, a.cp, ci.name AS city_name FROM orders o INNER JOIN users u ON o.id_user = u.id_user INNER JOIN addresses a ON o.id_address = a.id_address INNER JOIN cities ci ON a.id_city = ci.id_city";
        $countSql = "SELECT COUNT(*) AS total FROM orders o INNER JOIN users u ON o.id_user = u.id_user INNER JOIN addresses a ON o.id_address = a.id_address INNER JOIN cities ci ON a.id_city = ci.id_city";
        $condition = "";
        $types = "";
        $params = [];

        if (isset($_GET["status"]) && $_GET["status"] != "") {
            $condition .= " AND o.status = ?";
            $types .= "s";
            $params[] = $_GET["status"];
        }
        if (isset($_GET["search"]) && $_GET["search"] != "") {
            $search = "%" . $_GET["search"] . "%";
            $condition .= " AND (u.name LIKE ? OR u.email LIKE ?)";
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

        $sql .= " ORDER BY date DESC LIMIT $limit OFFSET $offset";
        $stmt = $con->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = $result->num_rows > 0 ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        success([
            "orders" => $orders,
            "total" => intval($total),
            "page" => $page,
            "pages" => ceil($total / $limit)
        ]);
    }

    if ($action === "order_detail") {
        $id = intval($_GET["id"]);
        $stmt = $con->prepare("SELECT od.*, p.name AS product_name, p.artist FROM order_detail od INNER JOIN products p ON od.id_product = p.id_product WHERE od.id_order = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $items = $res->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            success(["items" => $items]);
        } else {
            $stmt->close();
            success(["items" => []]);
        }
    }

    if ($action === "update_order_status") {
        $id = intval($_POST["id_order"]);
        $newStatus = $_POST["status"] ?? "";
        $allowed = ["pending", "paid", "sent"];
        if (!in_array($newStatus, $allowed)) {
            error("Estado no válido");
        }

        $stmt = $con->prepare("UPDATE orders SET status = ? WHERE id_order = ?");
        $stmt->bind_param("si", $newStatus, $id);
        if ($stmt->execute()) {
            $stmt->close();
            success();
        } else {
            $stmt->close();
            error("Error al actualizar estado");
        }
    }

    if ($action === "delete_order") {
        $id = intval($_POST["id_order"]);

        $stmt = $con->prepare("SELECT status FROM orders WHERE id_order = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res || $res->num_rows === 0) {
            $stmt->close();
            error("Pedido no encontrado");
        }
        $order = $res->fetch_assoc();
        $stmt->close();

        if ($order["status"] === "paid" || $order["status"] === "sent") {
            $stmt = $con->prepare("SELECT id_product, quantity FROM order_detail WHERE id_order = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            foreach ($items as $item) {
                $stmt = $con->prepare("UPDATE products SET stock = stock + ? WHERE id_product = ?");
                $stmt->bind_param("ii", $item["quantity"], $item["id_product"]);
                $stmt->execute();
                $stmt->close();
            }
        }

        $stmt = $con->prepare("DELETE FROM order_detail WHERE id_order = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $con->prepare("DELETE FROM orders WHERE id_order = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $stmt->close();
            success();
        } else {
            $stmt->close();
            error("Error al eliminar pedido");
        }
    }

    if ($action === "cancel_stale_orders") {
        $stmt = $con->prepare("SELECT id_order FROM orders WHERE status = 'pending' AND date < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stmt->execute();
        $res = $stmt->get_result();
        $stale = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $cancelled = 0;
        foreach ($stale as $order) {
            $id = $order["id_order"];

            $stmt = $con->prepare("SELECT id_product, quantity FROM order_detail WHERE id_order = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            foreach ($items as $item) {
                $stmt = $con->prepare("UPDATE products SET stock = stock + ? WHERE id_product = ?");
                $stmt->bind_param("ii", $item["quantity"], $item["id_product"]);
                $stmt->execute();
                $stmt->close();
            }

            $stmt = $con->prepare("DELETE FROM order_detail WHERE id_order = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $con->prepare("DELETE FROM orders WHERE id_order = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $cancelled++;
        }

        success(["cancelled" => $cancelled]);
    }

    error("Acción no válida");
?>