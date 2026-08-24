$(document).on("submit", "#register-form", async function (event) {
    event.preventDefault();

    var btn = $(this).find("button[type='submit']");
    btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span> Registrando...');

    var name = $("#register-name").val();
    var email = $("#register-email").val();
    var password = $("#register-password").val();

    var response = await $.post("api/auth.php", { action: "register", name, email, password });
    response = JSON.parse(response);

    btn.prop("disabled", false).html("Registrarse");

    if(response.ok) {
        showToast("Registro exitoso", "Revisa tu correo para activar tu cuenta.", "success");
    } else {
         showToast("Error", response.error, "danger");
    }
});

$(document).on("submit", "#login-form", async function (event) {
    event.preventDefault();

    var btn = $(this).find("button[type='submit']");
    btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span> Iniciando...');

    var email = $("#login-email").val();
    var password = $("#login-password").val();

    var response = await $.post("api/auth.php", { action: "login", email, password });
    response = JSON.parse(response);

    btn.prop("disabled", false).html("Entrar");

    if(response.ok) {
        showToast("Bienvenido", response.message, "success");
        setTimeout(async function() {
            await refreshSession();
            history.pushState(null, "", "#/home");
            await loadView("#/home");
        }, 1500);
    } else {
        showToast("Error", response.error, "danger");
    }
});
