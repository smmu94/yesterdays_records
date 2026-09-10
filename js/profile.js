var profileOrders = [];
var profilePage = 1;

async function loadProfile(page) {
    if (page !== undefined) profilePage = page;

    var response = await $.get("api/profile.php", { page: profilePage });
    var data = typeof response === "string" ? JSON.parse(response) : response;

    $("#profile-name").text(data.user.name);
    $("#profile-email").text(data.user.email);
    $("#edit-name").val(data.user.name);
    $("#edit-email").val(data.user.email);

    await loadAddresses();
    await loadAddressCities();

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

async function loadAddresses() {
    var response = await $.get("api/addresses.php");
    var data = typeof response === "string" ? JSON.parse(response) : response;
    var addresses = data.addresses || [];
    var container = $("#address-list");
    container.empty();

    if (addresses.length === 0) {
        container.html('<p class="text-secondary mb-0">No tienes direcciones guardadas.</p>');
        return;
    }

    addresses.forEach(function(addr) {
        container.append(`
            <div class="address-card mb-2" data-id="${addr.id_address}">
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <div class="address-info">
                        <span class="d-block">${addr.street_address}</span>
                        <small class="text-secondary">${addr.city_name} (${addr.cp})</small>
                    </div>
                    <div class="d-flex gap-1 flex-shrink-0">
                        <button class="btn btn-sm btn-outline-warning btn-edit-address" data-id="${addr.id_address}"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger btn-delete-address" data-id="${addr.id_address}"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </div>
        `);
    });
}

function openAddressModal(id) {
    $("#address-error").addClass("d-none");
    $("#save-address-spinner").addClass("d-none");
    $("#address-save-icon").removeClass("d-none");

    if (id) {
        $("#address-modal-title").text("Editar dirección");
        $("#address-id").val(id);
        var street = "";
        $("#address-list .address-card").each(function() {
            var cardId = $(this).data("id");
            if (cardId == id) {
                street = $(this).find(".address-info span").text();
                var small = $(this).find(".address-info small").text();
                var match = small.match(/^(.*) \((\d+)\)$/);
                if (match) {
                    $("#address-cp").val(match[2]);
                    var cityName = match[1];
                    $("#address-city option").each(function() {
                        if ($(this).text() === cityName) $(this).prop("selected", true);
                    });
                }
            }
        });
        $("#address-street").val(street.trim());
    } else {
        $("#address-modal-title").text("Nueva dirección");
        $("#address-id").val("");
        $("#address-street").val("");
        $("#address-cp").val("");
        $("#address-city").val("");
    }

    var modal = new bootstrap.Modal(document.getElementById("addressModal"));
    modal.show();
}

async function loadAddressCities() {
    var response = await $.get("api/cities.php");
    var data = typeof response === "string" ? JSON.parse(response) : response;
    var cities = data.cities || [];
    var select = $("#address-city");
    select.find("option:gt(0)").remove();
    cities.forEach(function(city) {
        select.append(`<option value="${city.id_city}">${city.name}</option>`);
    });
}

function saveAddress() {
    var id = $("#address-id").val();
    var street = $("#address-street").val().trim();
    var city = $("#address-city").val();
    var cp = $("#address-cp").val().trim();

    if (!street || !city || !cp) {
        $("#address-error").text("Todos los campos son obligatorios").removeClass("d-none");
        return;
    }

    $("#btn-save-address").prop("disabled", true);
    $("#address-save-icon").addClass("d-none");
    $("#address-save-spinner").removeClass("d-none");

    var data = { action: id ? "update" : "create", street: street, city: city, cp: cp };
    if (id) data.id_address = id;

    $.post("api/addresses.php", data, function(response) {
        var result = typeof response === "string" ? JSON.parse(response) : response;

        $("#btn-save-address").prop("disabled", false);
        $("#address-save-icon").removeClass("d-none");
        $("#address-save-spinner").addClass("d-none");

        if (result.ok === false) {
            $("#address-error").text(result.error).removeClass("d-none");
            return;
        }

        var modal = bootstrap.Modal.getInstance(document.getElementById("addressModal"));
        modal.hide();
        showToast("Éxito", id ? "Dirección actualizada" : "Dirección creada", "success");
        loadAddresses();
    });
}

function deleteAddress(id) {
    $.post("api/addresses.php", { action: "delete", id_address: id }, function(response) {
        var result = typeof response === "string" ? JSON.parse(response) : response;
        if (result.ok === false) {
            showToast("Error", result.error, "danger");
            return;
        }
        showToast("Eliminada", "Dirección borrada", "success");
        loadAddresses();
    });
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
            var subtotal = item.unit_price * item.quantity;
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

    $("body").on("click", "#btn-edit-profile", function() {
        $("#profile-edit-error").addClass("d-none");
        hideEl("#profile-view-mode");
        showEl("#profile-edit-mode");
    });

    $("body").on("click", "#btn-cancel-profile", function() {
        hideEl("#profile-edit-mode");
        showEl("#profile-view-mode");
    });

    $("body").on("click", "#btn-save-profile", function() {
        var name = $("#edit-name").val().trim();
        var email = $("#edit-email").val().trim();

        if (!name || !email) {
            $("#profile-edit-error").text("Nombre y email son obligatorios").removeClass("d-none");
            return;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            $("#profile-edit-error").text("Email no válido").removeClass("d-none");
            return;
        }

        $("#btn-save-profile").prop("disabled", true);
        $("#profile-save-icon").addClass("d-none");
        $("#profile-save-spinner").removeClass("d-none");

        $.post("api/profile.php", { action: "update_profile", name: name, email: email }, function(response) {
            var result = typeof response === "string" ? JSON.parse(response) : response;

            $("#btn-save-profile").prop("disabled", false);
            $("#profile-save-icon").removeClass("d-none");
            $("#profile-save-spinner").addClass("d-none");

            if (result.ok === false) {
                $("#profile-edit-error").text(result.error).removeClass("d-none");
                return;
            }

            $("#profile-name").text(result.user.name);
            $("#profile-email").text(result.user.email);
            $("#user-display").text(result.user.name);
            session.name = result.user.name;
            session.email = result.user.email;
            hideEl("#profile-edit-mode");
            showEl("#profile-view-mode");
            showToast("Éxito", "Perfil actualizado", "success");
        });
    });

    $("body").on("click", "#btn-add-address", function() {
        openAddressModal();
    });

    $("body").on("click", ".btn-edit-address", function() {
        var id = $(this).data("id");
        openAddressModal(id);
    });

    $("body").on("click", ".btn-delete-address", function() {
        var id = $(this).data("id");
        deleteAddress(id);
    });

    $("body").on("click", "#btn-save-address", function() {
        saveAddress();
    });
}