async function loadCheckout() {
    var response = await $.get("api/cart.php?action=get");
    var data = typeof response === "string" ? JSON.parse(response) : response;
    var items = data.items || [];

    if (items.length === 0) {
        $("#checkout-loading").hide();
        showEl("#checkout-empty");
        return;
    }

    var container = $("#checkout-items");
    container.empty();
    var total = 0;

    items.forEach(function(item) {
        var subtotal = item.price * item.quantity;
        total += subtotal;
        container.append(`
            <div class="checkout-item">
                <div>
                    <span class="item-name">${item.product_name}</span>
                    <span class="item-qty"> x${item.quantity}</span>
                </div>
                <span class="item-price">${subtotal.toFixed(2)} €</span>
            </div>
        `);
    });

    container.append(`
        <div class="checkout-summary-total">
            <strong>Total</strong>
            <strong>${total.toFixed(2)} €</strong>
        </div>
    `);

    $("#checkout-total").text(total.toFixed(2));

    var citiesResponse = await $.get("api/cities.php");
    var citiesData = typeof citiesResponse === "string" ? JSON.parse(citiesResponse) : citiesResponse;
    var cities = citiesData.cities || [];
    var citySelect = $("#checkout-city");
    citySelect.find("option:gt(0)").remove();
    cities.forEach(function(city) {
        citySelect.append(`<option value="${city.id_city}">${city.name}</option>`);
    });

    var addrResponse = await $.get("api/addresses.php");
    var addrData = typeof addrResponse === "string" ? JSON.parse(addrResponse) : addrResponse;
    var addresses = addrData.addresses || [];
    var containerAddr = $("#saved-addresses");
    containerAddr.empty();

    if (addresses.length > 0) {
        addresses.forEach(function(addr, i) {
            containerAddr.append(`
                <div class="checkout-address-card mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="address" 
                               id="addr-${addr.id_address}" value="${addr.id_address}"
                               ${i === 0 ? "checked" : ""}>
                        <label class="form-check-label" for="addr-${addr.id_address}">
                            ${addr.street_address}, ${addr.city_name} (${addr.cp})
                        </label>
                    </div>
                </div>
            `);
        });
    }

    containerAddr.append(`
        <div class="checkout-address-card mb-2">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="address" 
                       id="addr-new" value="new" ${addresses.length === 0 ? "checked" : ""}>
                <label class="form-check-label" for="addr-new">Nueva dirección</label>
            </div>
        </div>
    `);

    toggleNewAddress();
    containerAddr.show();

    $("#checkout-loading").hide();
    showEl("#checkout-content");
}

function toggleNewAddress() {
    if ($("#addr-new").is(":checked")) {
        showEl("#new-address-form");
    } else {
        hideEl("#new-address-form");
    }
}

function registerCheckoutEvents() {
    $("body").on("change", "input[name='address']", function() {
        toggleNewAddress();
    });

    $("body").on("click", "#btn-pay", function() {
        var isNew = $("#addr-new").is(":checked");
        hideEl("#checkout-error");

        if (isNew) {
            var street = $("#checkout-street").val().trim();
            var city = $("#checkout-city").val();
            var cp = $("#checkout-cp").val().trim();

            if (!street || !city || !cp) {
                $("#checkout-error").text("Completa todos los campos de dirección");
                showEl("#checkout-error");
                return;
            }
        }

        var btn = $(this);
        var data = {};

        if (isNew) {
            data.new_address = "1";
            data.street = $("#checkout-street").val().trim();
            data.city = $("#checkout-city").val();
            data.cp = $("#checkout-cp").val().trim();
        } else {
            data.id_address = $("input[name='address']:checked").val();
        }

        btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.post("api/checkout.php", data, function(response) {
            var result = typeof response === "string" ? JSON.parse(response) : response;
            if (result.url) {
                window.location.href = result.url;
            } else {
                showToast("Error", result.error || "No se pudo procesar", "danger");
                btn.prop("disabled", false).html('<i class="bi bi-credit-card"></i> Pagar con Stripe');
            }
        });
    });

    $("body").on("click", "#btn-empty-catalog", function() {
        history.pushState(null, "", "#/home");
        loadView("#/home").then(function() {
            scrollToCatalog();
        });
    });
}
