<?php
    function is_logged_in() {
        return isset($_SESSION["logueado"]);
    }

    function is_admin() {
        return is_logged_in() && $_SESSION["logueado"]["role"] === "admin";
    }

    function get_user_id() {
        return $_SESSION["logueado"]["id"] ?? null;
    }

    function respond($data) {
        echo json_encode($data);
        exit;
    }

    function success($data = []) {
        respond($data);
    }

    function error($message) {
        respond(["ok" => false, "error" => $message]);
    }
