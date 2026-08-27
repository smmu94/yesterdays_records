<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/helpers.php");
    session_start();

    $action = $_GET["action"] ?? $_POST["action"] ?? "";

    function register() {
        global $con;
        $name = $_POST["name"];
        $email = $_POST["email"];
        $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
        $role = $_POST["role"] ?? "client";

        $res = $con->query("SELECT id_user FROM users WHERE email = '$email'");
        if ($res->num_rows > 0) {
            error("Este email ya esta registrado");
        }

        $token = bin2hex(random_bytes(32));
        $token_hash = hash("sha256", $token);
        $token_expire = date("Y-m-d H:i:s", strtotime("+24 hours"));

        $sql = "INSERT INTO users (name, email, password, role, token, token_expire, status, date) 
                VALUES ('$name', '$email', '$password', '$role', '$token_hash', '$token_expire', 'pending', NOW())";

        if ($con->query($sql)) {
            $para = $email;
            $asunto = "Activa tu cuenta en Yesterdays Records";
            $mensaje = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2>Bienvenido/a $name!</h2>
                    <p>Haz clic en el enlace para activar tu cuenta:</p>
                    <a href='http://localhost/efbs_web/portfolio/yesterday_records/api/auth.php?action=verify&tok=$token'
                       style='display:inline-block; padding:12px 24px; background:#6c5ce7; color:#fff; text-decoration:none; border-radius:6px;'>
                        Activar mi cuenta
                    </a>
                    <p style='color:#888; font-size:12px; margin-top:20px;'>Este enlace expira en 24 horas.</p>
                </div>";

            $cabeceras = "MIME-Version: 1.0\r\n";
            $cabeceras .= "Content-Type: text/html; charset=UTF-8\r\n";
            $cabeceras .= "From: noreply@yesterdaysrecords.com\r\n";

            mail($para, $asunto, $mensaje, $cabeceras);
            success(["message" => "Revisa tu correo para activarla."]);
        } else {
            error("Error al registrar el usuario");
        }
    }

    function verify_account() {
        global $con;
        $token = $_GET["tok"];
        $token_hash = hash("sha256", $token);

        $res = $con->query("SELECT * FROM users WHERE token = '$token_hash' AND token_expire >= NOW()");

        if ($row = $res->fetch_assoc()) {
            $id = $row["id_user"];
            if ($con->query("UPDATE users SET status = 'verified', token = NULL, token_expire = NULL WHERE id_user = $id")) {
                header("location:../index.html#/verify?status=ok");
                die();
            } else {
                header("location:../index.html#/verify?status=error");
                die();
            }
        } else {
            header("location:../index.html#/verify?status=error");
            die();
        }
    }

    function login() {
        global $con;
        $email = $_POST["email"];
        $password_form = $_POST["password"];

        $res = $con->query("SELECT * FROM users WHERE email = '$email'");
        if ($row = $res->fetch_assoc()) {
            if (password_verify($password_form, $row["password"])) {
                if ($row["status"] == "verified") {
                    $_SESSION["logueado"] = [
                        "id" => $row["id_user"],
                        "name" => $row["name"],
                        "email" => $row["email"],
                        "role" => $row["role"],
                    ];

                    $session_cart = $_SESSION["cart"] ?? [];
                    if (!empty($session_cart)) {
                        foreach ($session_cart as $id_product => $qty) {
                            $res_cart = $con->query("SELECT id_cart, quantity FROM cart 
                                                    WHERE id_user = {$row['id_user']} AND id_product = $id_product");
                            if ($res_cart && $res_cart->num_rows > 0) {
                                $row_cart = $res_cart->fetch_assoc();
                                $new_qty = $row_cart["quantity"] + $qty;
                                $con->query("UPDATE cart SET quantity = $new_qty WHERE id_cart = {$row_cart['id_cart']}");
                            } else {
                                $con->query("INSERT INTO cart (id_user, id_product, quantity) 
                                             VALUES ({$row['id_user']}, $id_product, $qty)");
                            }
                        }
                        unset($_SESSION["cart"]);
                    }

                    success(["message" => "Usuario logueado con exito"]);
                } else {
                    error("Debes activar tu cuenta!");
                }
            } else {
                error("Contrasena invalida");
            }
        } else {
            error("Usuario no registrado");
        }
    }

    function check_session() {
        if (is_logged_in()) {
            success([
                "logged_in" => true,
                "id" => $_SESSION["logueado"]["id"],
                "name" => $_SESSION["logueado"]["name"],
                "email" => $_SESSION["logueado"]["email"],
                "role" => $_SESSION["logueado"]["role"],
            ]);
        } else {
            success(["logged_in" => false]);
        }
    }

    function logout() {
        session_destroy();
        header("location:../index.html");
        die();
    }

    switch ($action) {
        case "register":
            register();
            break;
        case "verify":
            verify_account();
            break;
        case "login":
            login();
            break;
        case "logout":
            logout();
            break;
        case "check":
            check_session();
            break;
        default:
            error("Accion no valida");
            break;
    }
?>
