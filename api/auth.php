<?php
    include(__DIR__."/../config/database.php");

    session_start();

    $action = $_GET["action"] ?? $_POST["action"] ?? "";

    function register() {
        global $con;
        $name = $_POST["name"];
        $email = $_POST["email"];
        $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
        $role = $_POST["role"] ?? "client";

        $sql_email_already_exist = "SELECT id_user FROM users WHERE email = '$email'";
        $res = $con->query($sql_email_already_exist);

        if($res->num_rows > 0) {
            echo json_encode(["ok" => false, "error" => "Este email ya está registrado"]); 
            return;
        }

        $token = bin2hex(random_bytes(32));
        $token_hash = hash("sha256", $token);
        $token_expire = date("Y-m-d H:i:s", strtotime("+24 hours"));

        $sql_register = "INSERT INTO users (name, email, password, role, token, token_expire, status, date) 
                        VALUES ('$name', '$email', '$password', '$role', '$token_hash', '$token_expire', 'pending', NOW())";

        if($con->query($sql_register)){
            echo json_encode(["ok" => true, "message" => "Revisa tu correo para activarla."]); 

            $para = $email;
            $asunto = "Activa tu cuenta en Yesterdays Records";
            $mensaje = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2>¡Bienvenido/a $name!</h2>
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
        } else {
            echo json_encode(["ok" => false, "error" => "Error al registrar el usuario"]); 
        }

    }

    function verify_account() {
        global $con;
        $token = $_GET["tok"];
        $token_hash = hash("sha256", $token);
        
        $sql_verify = "SELECT * FROM users
                WHERE token = '$token_hash' AND token_expire >= NOW()";
        $res = $con->query($sql_verify);

        if($row = $res->fetch_assoc()){
            $id = $row["id_user"];
            $sql_activate = "UPDATE users
                             SET status = 'verified', token = NULL, token_expire = NULL
                             WHERE id_user = $id";
            if($con->query($sql_activate)){
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

        $sql_search_email = "SELECT * FROM users WHERE email = '$email'";
        $res = $con->query($sql_search_email);
        if($row = $res->fetch_assoc()){
            $password_db = $row["password"];
            if(password_verify($password_form, $password_db)){
                if($row["status"] == "verified"){
                    $_SESSION["logueado"] = [
                        "id" => $row["id_user"],
                        "name" => $row["name"],
                        "email" => $row["email"],
                        "role" => $row["role"],
                    ];

                    $session_cart = $_SESSION["cart"] ?? [];
                    if (!empty($session_cart)) {
                        foreach ($session_cart as $id_product => $qty) {
                            $sql_check = "SELECT id_cart, quantity FROM cart 
                                          WHERE id_user = {$row['id_user']} AND id_product = $id_product";
                            $res_cart = $con->query($sql_check);
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

                    echo json_encode(["ok" => true, "message" => "Usuario logueado con éxito"]);
                } else {
                    echo json_encode(["ok" => false, "error" => "Debes activar tu cuenta!"]);
                }
            } else {
                echo json_encode(["ok" => false, "error" => "Contraseña inválida"]);
            }
        } else {
            echo json_encode(["ok" => false, "error" => "Usuario no registrado"]);
        }
    }

    function check_session() {
        if(isset($_SESSION["logueado"])){
            echo json_encode([
                "logged_in" => true,
                "id" => $_SESSION["logueado"]["id"],
                "name" => $_SESSION["logueado"]["name"],
                "email" => $_SESSION["logueado"]["email"],
                "role" => $_SESSION["logueado"]["role"],
            ]);
        } else {
            echo json_encode([
                "logged_in" => false,
            ]);
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
            echo json_encode(["ok" => false, "error" => "Acción no válida"]);
            break;
    }  
?>