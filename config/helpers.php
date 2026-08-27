<?php
    // Verifica si hay sesión activa
    function is_logged_in() {
        return isset($_SESSION["logueado"]);
    }

    // Verifica si es admin
    function is_admin() {
        return is_logged_in() && $_SESSION["logueado"]["role"] === "admin";
    }

    // Obtiene el ID del usuario logueado
    function get_user_id() {
        return $_SESSION["logueado"]["id"] ?? null;
    }

    // Responde con JSON y termina
    function respond($data) {
        echo json_encode($data);
        exit;
    }

    // Respuesta de éxito (sin campo ok)
    function success($data = []) {
        respond($data);
    }

    // Respuesta de error (con ok: false)
    function error($message) {
        respond(["ok" => false, "error" => $message]);
    }
?>
