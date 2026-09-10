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

        $sql = "SELECT * FROM v_products";
        $countSql = "SELECT COUNT(*) AS total FROM v_products";
        $condition = "";
        $types = "";
        $params = [];

        if (isset($_GET["category"]) && $_GET["category"] != "") {
            $condition .= " AND id_category = ?";
            $types .= "i";
            $params[] = intval($_GET["category"]);
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

        $sql = "SELECT * FROM v_orders";
        $countSql = "SELECT COUNT(*) AS total FROM v_orders";
        $condition = "";
        $types = "";
        $params = [];

        if (isset($_GET["status"]) && $_GET["status"] != "") {
            $condition .= " AND status = ?";
            $types .= "s";
            $params[] = $_GET["status"];
        }
        if (isset($_GET["search"]) && $_GET["search"] != "") {
            $search = "%" . $_GET["search"] . "%";
            $condition .= " AND (client_name LIKE ? OR email LIKE ?)";
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
        $stmt = $con->prepare("SELECT * FROM v_order_detail WHERE id_order = ?");
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

    error("Acción no válida");
?>