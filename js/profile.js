var profileOrders = [];
var profilePage = 1;

async function loadProfile(page) {
    if (page !== undefined) profilePage = page;

    var response = await $.get("api/profile.php", { page: profilePage });
    var data = typeof response === "string" ? JSON.parse(response) : response;

    $("#profile-name").text(data.user.name);
    $("#profile-email").text(data.user.email);

    profileOrders = data.orders;
    var tbody = $("#profile-orders");
    tbody.empty();

    if (data.orders.length === 0 && profilePage === 1) {
        $("#profile-loading").hide();
        showEl("#profile-empty");
        return;
    }

    data.orders.forEach(function(order) {
        var statusClass = "bg-secondary";
        var statusLabel = order.status;
        if (order.status === "paid") { statusClass = "bg-success"; statusLabel = "Pagado"; }
        if (order.status === "sent") { statusClass = "bg-primary"; statusLabel = "Enviado"; }
        if (order.status === "pending") { statusClass = "bg-warning text-dark"; statusLabel = "Pendiente"; }

        var row = `
            <tr class="order-row" data-id="${order.id_order}" style="cursor:pointer;">
                <td>${order.id_order}</td>
                <td>${formatDate(order.date)}</td>
                <td>${order.total} €</td>
                <td><span class="badge ${statusClass}">${statusLabel}</span></td>
            </tr>
        `;
        tbody.append(row);
    });

    renderAdminPagination("profile-orders", data.pages, data.page);
    $("#profile-loading").hide();
    showEl("#profile-content");
}

function registerProfileEvents() {
    $("body").on("click", ".order-row", function() {
        var id = $(this).data("id");
        var order = profileOrders.find(function(o) { return o.id_order == id; });
        if (!order) return;

        var statusClass = "bg-secondary";
        var statusLabel = order.status;
        if (order.status === "paid") { statusClass = "bg-success"; statusLabel = "Pagado"; }
        if (order.status === "sent") { statusClass = "bg-primary"; statusLabel = "Enviado"; }
        if (order.status === "pending") { statusClass = "bg-warning text-dark"; statusLabel = "Pendiente"; }

        $("#modal-order-title").html("Pedido #" + order.id_order + ' <span class="badge ' + statusClass + '">' + statusLabel + "</span>");
        $("#modal-order-date").text(formatDate(order.date) + " — " + order.street_address + ", " + order.city_name + " (" + order.cp + ")");
        $("#modal-order-total").text(order.total + " €");

        var tbody = $("#modal-order-items");
        tbody.empty();

        order.items.forEach(function(item) {
            var subtotal = item.unit_price;
            tbody.append(`
                <tr>
                    <td>${item.product_name}</td>
                    <td>${item.artist}</td>
                    <td>${item.unit_price} €</td>
                    <td>${item.quantity}</td>
                    <td>${subtotal} €</td>
                </tr>
            `);
        });

        var modal = new bootstrap.Modal(document.getElementById("orderDetailModal"));
        modal.show();
    });

    $("body").on("click", "#profile-orders-pagination .admin-pagination-btn", function(e) {
        e.preventDefault();
        var page = $(this).data("page");
        if (!page || page < 1) return;
        loadProfile(page);
    });
}