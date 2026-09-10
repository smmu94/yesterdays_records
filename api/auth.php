<?php
    include(__DIR__."/../config/database.php");
    include(__DIR__."/../config/helpers.php");
    @session_start();

    $action = $_GET["action"] ?? $_POST["action"] ?? "";

    function register() {
        global $con;
        $name = $_POST["name"];
        $email = $_POST["email"];
        $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
        $role = "client";

        $stmt = $con->prepare("SELECT id_user FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();
            error("Este email ya esta registrado");
        }
        $stmt->close();

        $token = bin2hex(random_bytes(32));
        $token_hash = hash("sha256", $token);
        $token_expire = date("Y-m-d H:i:s", strtotime("+24 hours"));

        $stmt = $con->prepare("INSERT INTO users (name, email, password, role, token, token_expire, status, date) 
                              VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("ssssss", $name, $email, $password, $role, $token_hash, $token_expire);

        if ($stmt->execute()) {
            $stmt->close();
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
            $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'];
            $verify_url = $base_url . dirname($_SERVER['SCRIPT_NAME']) . "/auth.php?action=verify&tok=$token";

            $para = $email;
            $asunto = "Activa tu cuenta en Yesterdays Records";
            $mensaje = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2>Bienvenido/a $name!</h2>
                    <p>Haz clic en el enlace para activar tu cuenta:</p>
                    <a href='$verify_url'
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
            $stmt->close();
            error("Error al registrar el usuario");
        }
    }

    function verify_account() {
        global $con;
        $token = $_GET["tok"];
        $token_hash = hash("sha256", $token);

        $stmt = $con->prepare("SELECT id_user FROM users WHERE token = ? AND token_expire >= NOW()");
        $stmt->bind_param("s", $token_hash);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            $id = $row["id_user"];
            $stmt->close();

            $stmt2 = $con->prepare("UPDATE users SET status = 'verified', token = NULL, token_expire = NULL WHERE id_user = ?");
            $stmt2->bind_param("i", $id);
            if ($stmt2->execute()) {
                $stmt2->close();
                header("location:../index.html#/verify?status=ok");
                die();
            } else {
                $stmt2->close();
                header("location:../index.html#/verify?status=error");
                die();
            }
        } else {
            $stmt->close();
            header("location:../index.html#/verify?status=error");
            die();
        }
    }

    function login() {
        global $con;
        $email = $_POST["email"];
        $password_form = $_POST["password"];

        $stmt = $con->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

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
                            $stmt_cart = $con->prepare("SELECT id_cart, quantity FROM cart 
                                                       WHERE id_user = ? AND id_product = ?");
                            $stmt_cart->bind_param("ii", $row['id_user'], $id_product);
                            $stmt_cart->execute();
                            $res_cart = $stmt_cart->get_result();

                            if ($res_cart && $res_cart->num_rows > 0) {
                                $row_cart = $res_cart->fetch_assoc();
                                $new_qty = $row_cart["quantity"] + $qty;
                                $stmt_cart->close();

                                $stmt_up = $con->prepare("UPDATE cart SET quantity = ? WHERE id_cart = ?");
                                $stmt_up->bind_param("ii", $new_qty, $row_cart['id_cart']);
                                $stmt_up->execute();
                                $stmt_up->close();
                            } else {
                                $stmt_cart->close();

                                $stmt_ins = $con->prepare("INSERT INTO cart (id_user, id_product, quantity) VALUES (?, ?, ?)");
                                $stmt_ins->bind_param("iii", $row['id_user'], $id_product, $qty);
                                $stmt_ins->execute();
                                $stmt_ins->close();
                            }
                        }
                        unset($_SESSION["cart"]);
                    }

                    $stmt->close();
                    success(["message" => "Usuario logueado con exito"]);
                } else {
                    $stmt->close();
                    error("Debes activar tu cuenta!");
                }
            } else {
                $stmt->close();
                error("Contrasena invalida");
            }
        } else {
            $stmt->close();
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