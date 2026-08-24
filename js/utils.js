function showToast(titulo, mensaje, tipo) {
    var toast = $("#app-toast");
    var header = toast.find(".toast-header");
    var body = toast.find(".toast-body");

    header.removeClass("bg-success bg-danger").addClass("bg-" + tipo);
    body.text(mensaje);
    $("#toast-title").text(titulo);

    var bsToast = new bootstrap.Toast(toast[0]);
    bsToast.show();
}
