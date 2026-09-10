function showEl(sel) { $(sel).removeClass("d-none").removeAttr("hidden"); }
function hideEl(sel) { $(sel).addClass("d-none").attr("hidden", ""); }

function formatDate(dateStr) {
    if (!dateStr) return "";
    var parts = dateStr.split("-");
    if (parts.length !== 3) return dateStr;
    return parts[2] + "/" + parts[1] + "/" + parts[0];
}

var _debounceTimers = {};
function debounce(key, fn, delay) {
    clearTimeout(_debounceTimers[key]);
    _debounceTimers[key] = setTimeout(fn, delay);
}

function escapeHtml(str) {
    if (!str) return "";
    var div = document.createElement("div");
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

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

function renderAdminPagination(prefix, totalPages, currentPage) {
    var container = $(`#${prefix}-pagination`);
    container.empty();
    if (totalPages <= 1) return;

    var html = '<nav><ul class="pagination">';
    html += `<li class="page-item ${currentPage === 1 ? "disabled" : ""}">
        <a class="page-link admin-pagination-btn" href="#" data-prefix="${prefix}" data-page="${currentPage - 1}">&laquo; Anterior</a></li>`;

    for (var i = 1; i <= totalPages; i++) {
        html += `<li class="page-item ${i === currentPage ? "active" : ""}">
            <a class="page-link admin-pagination-btn" href="#" data-prefix="${prefix}" data-page="${i}">${i}</a></li>`;
    }

    html += `<li class="page-item ${currentPage === totalPages ? "disabled" : ""}">
        <a class="page-link admin-pagination-btn" href="#" data-prefix="${prefix}" data-page="${currentPage + 1}">Siguiente &raquo;</a></li>`;
    html += '</ul></nav>';
    container.html(html);
}
