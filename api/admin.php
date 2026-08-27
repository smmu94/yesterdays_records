<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/helpers.php");
    session_start();

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

        $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $filename = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        $route = $dir . $filename;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $route)) {
            return "uploads/products/" . $filename;
        }

        return null;
    }

    if ($action === "list_products") {
        $res = $con->query("SELECT * FROM v_products ORDER BY name");
        if ($res && $res->num_rows > 0) {
            success(["products" => $res->fetch_all(MYSQLI_ASSOC)]);
        } else {
            success(["products" => []]);
        }
    }

    if ($action === "create_product") {
        $name = $con->real_escape_string($_POST["name"]);
        $artist = $con->real_escape_string($_POST["artist"]);
        $description = $con->real_escape_string($_POST["description"]);
        $price = floatval($_POST["price"]);
        $stock = intval($_POST["stock"]);
        $id_category = intval($_POST["id_category"]);
        $id_genre = intval($_POST["id_genre"]);

        $image = handle_image_upload();
        if ($image === null) {
            error("Error al subir la imagen");
        }

        $sql = "INSERT INTO products (name, description, id_category, id_genre, artist, price, stock, image)
                VALUES ('$name', '$description', $id_category, $id_genre, '$artist', $price, $stock, '$image')";
        if ($con->query($sql)) {
            success(["id" => $con->insert_id]);
        } else {
            error("Error al crear producto");
        }
    }

    if ($action === "get_product") {
        $id = intval($_GET["id"]);
        $res = $con->query("SELECT * FROM products WHERE id_product = $id");
        if ($res && $res->num_rows > 0) {
            success(["product" => $res->fetch_assoc()]);
        } else {
            error("Producto no encontrado");
        }
    }

    if ($action === "update_product") {
        $id = intval($_POST["id_product"]);
        $name = $con->real_escape_string($_POST["name"]);
        $artist = $con->real_escape_string($_POST["artist"]);
        $description = $con->real_escape_string($_POST["description"]);
        $price = floatval($_POST["price"]);
        $stock = intval($_POST["stock"]);
        $id_category = intval($_POST["id_category"]);
        $id_genre = intval($_POST["id_genre"]);

        $image = handle_image_upload();
        if ($image !== null) {
            $con->query("UPDATE products SET image='$image' WHERE id_product=$id");
        }

        $sql = "UPDATE products SET name='$name', description='$description',
                id_category=$id_category, id_genre=$id_genre, artist='$artist',
                price=$price, stock=$stock
                WHERE id_product=$id";
        if ($con->query($sql)) {
            success();
        } else {
            error("Error al actualizar producto");
        }
    }

    if ($action === "delete_product") {
        $id = intval($_POST["id_product"]);

        $res = $con->query("SELECT image FROM products WHERE id_product = $id");
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $image_path = __DIR__ . "/../" . $row["image"];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        if ($con->query("DELETE FROM products WHERE id_product = $id")) {
            success();
        } else {
            error("Error al eliminar producto");
        }
    }

    if ($action === "list_orders") {
        $res = $con->query("SELECT * FROM v_orders ORDER BY date DESC");
        if ($res && $res->num_rows > 0) {
            success(["orders" => $res->fetch_all(MYSQLI_ASSOC)]);
        } else {
            success(["orders" => []]);
        }
    }

    if ($action === "order_detail") {
        $id = intval($_GET["id"]);
        $res = $con->query("SELECT * FROM v_order_detail WHERE id_order = $id");
        if ($res && $res->num_rows > 0) {
            success(["items" => $res->fetch_all(MYSQLI_ASSOC)]);
        } else {
            success(["items" => []]);
        }
    }

    error("Accion no valida");
?>
