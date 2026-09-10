<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/helpers.php");
    @session_start();

    if (!is_logged_in()) {
        error("Debes iniciar sesión");
    }

    $id_user = get_user_id();
    $action = $_GET["action"] ?? $_POST["action"] ?? "";

    if ($action === "create") {
        $street = trim($_POST["street"] ?? "");
        $city = intval($_POST["city"] ?? 0);
        $cp = trim($_POST["cp"] ?? "");

        if ($street === "" || $city <= 0 || $cp === "") {
            error("Todos los campos son obligatorios");
        }

        $stmt = $con->prepare("INSERT INTO addresses (id_user, id_city, cp, street_address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $id_user, $city, $cp, $street);
        if ($stmt->execute()) {
            $stmt->close();
            success(["message" => "Direccion creada"]);
        } else {
            $stmt->close();
            error("Error al crear la direccion");
        }
    }

    if ($action === "update") {
        $id = intval($_POST["id_address"] ?? 0);
        $street = trim($_POST["street"] ?? "");
        $city = intval($_POST["city"] ?? 0);
        $cp = trim($_POST["cp"] ?? "");

        if ($street === "" || $city <= 0 || $cp === "") {
            error("Todos los campos son obligatorios");
        }

        $stmt = $con->prepare("SELECT id_address FROM addresses WHERE id_address = ? AND id_user = ?");
        $stmt->bind_param("ii", $id, $id_user);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $stmt->close();
            error("Direccion no encontrada");
        }
        $stmt->close();

        $stmt = $con->prepare("UPDATE addresses SET street_address = ?, id_city = ?, cp = ? WHERE id_address = ? AND id_user = ?");
        $stmt->bind_param("sisii", $street, $city, $cp, $id, $id_user);
        if ($stmt->execute()) {
            $stmt->close();
            success(["message" => "Direccion actualizada"]);
        } else {
            $stmt->close();
            error("Error al actualizar la direccion");
        }
    }

    if ($action === "delete") {
        $id = intval($_POST["id_address"] ?? 0);

        $stmt = $con->prepare("SELECT id_address FROM addresses WHERE id_address = ? AND id_user = ?");
        $stmt->bind_param("ii", $id, $id_user);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $stmt->close();
            error("Direccion no encontrada");
        }
        $stmt->close();

        $stmt = $con->prepare("DELETE FROM addresses WHERE id_address = ? AND id_user = ?");
        $stmt->bind_param("ii", $id, $id_user);
        if ($stmt->execute()) {
            $stmt->close();
            success(["message" => "Direccion eliminada"]);
        } else {
            $stmt->close();
            error("Error al eliminar la direccion");
        }
    }

    $stmt = $con->prepare("SELECT a.id_address, a.street_address, a.cp, c.name AS city_name
                          FROM addresses a
                          INNER JOIN cities c ON a.id_city = c.id_city
                          WHERE a.id_user = ?");
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        success(["addresses" => $res->fetch_all(MYSQLI_ASSOC)]);
    } else {
        success(["addresses" => []]);
    }
?>