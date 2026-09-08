var adminOrders = [];

async function loadAdminOrders() {
    var response = await $.get("api/admin.php", { action: "list_orders" });
    var data = typeof response === "string" ? JSON.parse(response) : response;
    adminOrders = data.orders || [];
    var tbody = $("#admin-orders-body");
    tbody.empty();

    if (adminOrders.length === 0) {
        tbody.html('<tr><td colspan="5" class="text-center py-4">No hay pedidos</td></tr>');
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
                <td>${o.client_name}</td>
                <td>${o.date}</td>
                <td>${o.total} €</td>
                <td><span class="badge ${statusClass}">${statusLabel}</span></td>
            </tr>
        `;
        tbody.append(row);
    });
}

function registerAdminOrderEvents() {
    $("body").on("click", ".admin-order-row", async function() {
        var id = $(this).data("id");
        var order = adminOrders.find(function(o) { return o.id_order == id; });
        if (!order) return;

        var statusClass = "bg-secondary";
        var statusLabel = order.status;
        if (order.status === "paid") { statusClass = "bg-success"; statusLabel = "Pagado"; }
        if (order.status === "sent") { statusClass = "bg-primary"; statusLabel = "Enviado"; }
        if (order.status === "pending") { statusClass = "bg-warning text-dark"; statusLabel = "Pendiente"; }

        $("#admin-modal-order-title").html("Pedido #" + order.id_order + ' <span class="badge ' + statusClass + '">' + statusLabel + "</span>");
        $("#admin-modal-order-info").text(order.date + " — " + order.client_name);

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
                    <td>${item.artist}</td>
                    <td>${item.unit_price} €</td>
                    <td>${item.quantity}</td>
                    <td>${subtotal.toFixed(2)} €</td>
                </tr>
            `);
        });

        $("#admin-modal-order-total").text(total.toFixed(2) + " €");
    });
}