var adminOrders = [];
var adminOrdersPage = 1;
var adminOrderFilters = { search: "", status: "" };
var currentOrderModal = null;

async function loadAdminOrders(page) {
    if (page !== undefined) adminOrdersPage = page;

    var catResponse = await $.get("api/categories.php");
    var catData = typeof catResponse === "string" ? JSON.parse(catResponse) : catResponse;
    var categories = catData.categories || [];
    var catSelect = $("#admin-product-category");
    if (catSelect.children().length <= 1) {
        categories.forEach(function(c) {
            catSelect.append(`<option value="${c.id_category}">${c.name}</option>`);
        });
    }

    var params = { action: "list_orders", page: adminOrdersPage };
    if (adminOrderFilters.status) params.status = adminOrderFilters.status;
    if (adminOrderFilters.search) params.search = adminOrderFilters.search;

    var response = await $.get("api/admin.php", params);
    var data = typeof response === "string" ? JSON.parse(response) : response;
    adminOrders = data.orders || [];
    var tbody = $("#admin-orders-body");
    tbody.empty();

    if (adminOrders.length === 0) {
        tbody.html('<tr><td colspan="7" class="text-center py-4">No hay pedidos</td></tr>');
        $("#admin-orders-pagination").empty();
        return;
    }

    adminOrders.forEach(function(o) {
        var statusClass = "bg-secondary";
        var statusLabel = o.status;
        if (o.status === "paid") { statusClass = "bg-success"; statusLabel = "Pagado"; }
        if (o.status === "sent") { statusClass = "bg-primary"; statusLabel = "Enviado"; }
        if (o.status === "pending") { statusClass = "bg-warning text-dark"; statusLabel = "Pendiente"; }

        var row = `
            <tr class="admin-order-row" data-id="${o.id_order}" style="cursor:pointer;">
                <td>${o.id_order}</td>
                <td>${escapeHtml(o.client_name)}</td>
                <td class="d-none d-sm-table-cell">${escapeHtml(o.email)}</td>
                <td>${formatDate(o.date)}</td>
                <td>${o.total} €</td>
                <td><span class="badge ${statusClass}">${statusLabel}</span></td>
                <td>
                    <button class="btn btn-sm btn-danger btn-delete-order" data-id="${o.id_order}" title="Eliminar"><i class="bi bi-trash"></i></button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });

    renderAdminPagination("admin-orders", data.pages, data.page);
}

function registerAdminOrderEvents() {
    $("body").on("click", ".admin-order-row", async function(e) {
        if ($(e.target).closest(".btn-delete-order").length) return;
        var id = $(this).data("id");
        var order = adminOrders.find(function(o) { return o.id_order == id; });
        if (!order) return;

        currentOrderModal = order;

        var statusClass = "bg-secondary";
        var statusLabel = order.status;
        if (order.status === "paid") { statusClass = "bg-success"; statusLabel = "Pagado"; }
        if (order.status === "sent") { statusClass = "bg-primary"; statusLabel = "Enviado"; }
        if (order.status === "pending") { statusClass = "bg-warning text-dark"; statusLabel = "Pendiente"; }

        $("#admin-modal-order-title").html("Pedido #" + order.id_order + ' <span class="badge ' + statusClass + '">' + statusLabel + "</span>");
        $("#admin-modal-order-info").text(formatDate(order.date) + " — " + order.client_name);
        $("#admin-modal-order-status").val(order.status);

        var tbody = $("#admin-modal-order-items");
        tbody.html('<tr><td colspan="5" class="text-center py-3"><div class="spinner-border text-warning" role="status"></div></td></tr>');

        var modal = new bootstrap.Modal(document.getElementById("adminOrderDetailModal"));
        modal.show();

        var response = await $.get("api/admin.php", { action: "order_detail", id: id });
        var data = typeof response === "string" ? JSON.parse(response) : response;
        var items = data.items || [];
        tbody.empty();

        if (items.length === 0) {
            tbody.html('<tr><td colspan="5" class="text-center py-3">No hay items</td></tr>');
            return;
        }

        var total = 0;
        items.forEach(function(item) {
            var subtotal = item.unit_price * item.quantity;
            total += subtotal;
            tbody.append(`
                <tr>
                    <td>${item.product_name}</td>
                    <td class="d-none d-sm-table-cell">${item.artist}</td>
                    <td>${item.unit_price} €</td>
                    <td>${item.quantity}</td>
                    <td>${subtotal.toFixed(2)} €</td>
                </tr>
            `);
        });

        $("#admin-modal-order-total").text(total.toFixed(2) + " €");
    });

    $("body").on("change", "#admin-modal-order-status", function() {
        if (!currentOrderModal) return;
        var newStatus = $(this).val();
        var id = currentOrderModal.id_order;

        $.post("api/admin.php", { action: "update_order_status", id_order: id, status: newStatus }, function(response) {
            var data = typeof response === "string" ? JSON.parse(response) : response;
            if (data.ok === false) {
                showToast("Error", data.error, "danger");
                return;
            }
            showToast("Éxito", "Estado actualizado", "success");
            loadAdminOrders(adminOrdersPage);
        });
    });

    $("body").on("input", "#admin-order-search", function() {
        adminOrderFilters.search = $(this).val();
        debounce("admin-orders", function() { loadAdminOrders(1); }, 300);
    });

    $("body").on("change", "#admin-order-status", function() {
        adminOrderFilters.status = $(this).val();
        loadAdminOrders(1);
    });

    $("body").on("click", "#admin-order-clear", function() {
        adminOrderFilters = { search: "", status: "" };
        $("#admin-order-search").val("");
        $("#admin-order-status").val("");
        loadAdminOrders(1);
    });

    $("body").on("click", ".btn-delete-order", function(e) {
        e.stopPropagation();
        var id = $(this).data("id");
        $("#delete-order-id").text("#" + id);
        $("#btn-confirm-delete-order").data("id", id);
        var modal = new bootstrap.Modal(document.getElementById("deleteOrderModal"));
        modal.show();
    });

    $("body").on("click", "#btn-confirm-delete-order", function() {
        var id = $(this).data("id");
        var modal = bootstrap.Modal.getInstance(document.getElementById("deleteOrderModal"));
        modal.hide();

        $.post("api/admin.php", { action: "delete_order", id_order: id }, function(response) {
            var data = typeof response === "string" ? JSON.parse(response) : response;
            if (data.ok === false) {
                showToast("Error", data.error, "danger");
                return;
            }
            showToast("Eliminado", "Pedido borrado y stock restaurado", "success");
            loadAdminOrders(adminOrdersPage);
        });
    });

    $("body").on("click", "#admin-order-cancel-stale", function() {
        if (!confirm("¿Cancelar todos los pedidos pendientes de más de 24 horas? Se restaurará el stock.")) return;

        $.post("api/admin.php", { action: "cancel_stale_orders" }, function(response) {
            var data = typeof response === "string" ? JSON.parse(response) : response;
            if (data.ok === false) {
                showToast("Error", data.error, "danger");
                return;
            }
            showToast("Éxito", data.cancelled + " pedido(s) cancelado(s)", "success");
            loadAdminOrders(adminOrdersPage);
        });
    });
}
