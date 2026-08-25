async function loadCart() {
    var response = await $.get("api/cart.php?action=get");
    var items = typeof response === "string" ? JSON.parse(response) : response;

    if (items.length === 0) {
        $("#cart-loading").hide();
        $("#cart-content").hide();
        $("#cart-empty").show();
        return;
    }

    var tbody = $("#cart-items");
    tbody.empty();
    var total = 0;

    items.forEach(function(item) {
        var subtotal = item.price * item.quantity;
        total += subtotal;

        var row = `
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <img src="${item.image}" alt="${item.product_name}" style="width:50px; height:50px; object-fit:cover; border-radius:6px;">
                        <div>
                            <p class="mb-0 fw-bold">${item.product_name}</p>
                            <small class="text-secondary">${item.artist}</small>
                        </div>
                    </div>
                </td>
                <td>${item.price} €</td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-warning btn-cart-minus" data-id="${item.id_product}" data-qty="${item.quantity}">-</button>
                        <span>${item.quantity}</span>
                        <button class="btn btn-sm btn-warning btn-cart-plus" data-id="${item.id_product}" data-qty="${item.quantity}">+</button>
                    </div>
                </td>
                <td class="fw-bold">${subtotal.toFixed(2)} €</td>
                <td>
                    <button class="btn btn-sm btn-danger btn-cart-remove" data-id="${item.id_product}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });

    $("#cart-total").text(total.toFixed(2));
    $("#cart-loading").hide();
    $("#cart-content").show();
}

function updateCartCount() {
    $.get("api/cart.php?action=count", function(response) {
        var data = typeof response === "string" ? JSON.parse(response) : response;
        $("#cart-count").text(data.count);
        if (data.count > 0) {
            $("#cart-count").show();
        } else {
            $("#cart-count").hide();
        }
    });
}

function registerCartEvents() {
    $("body").on("click", ".btn-add-cart", function() {
        var id = $(this).data("id");
        $.post("api/cart.php", { action: "add", id_product: id }, function(response) {
            var data = typeof response === "string" ? JSON.parse(response) : response;
            if (data.ok) {
                showToast("Agregado", "Producto agregado al carrito", "success");
                updateCartCount();
            } else {
                showToast("Error", data.error, "danger");
            }
        });
    });

    $("body").on("click", ".btn-cart-plus", function() {
        var id = $(this).data("id");
        var qty = $(this).data("qty") + 1;
        $.post("api/cart.php", { action: "update", id_product: id, quantity: qty }, function() {
            loadCart();
            updateCartCount();
        });
    });

    $("body").on("click", ".btn-cart-minus", function() {
        var id = $(this).data("id");
        var qty = $(this).data("qty") - 1;
        $.post("api/cart.php", { action: "update", id_product: id, quantity: qty }, function() {
            loadCart();
            updateCartCount();
        });
    });

    $("body").on("click", ".btn-cart-remove", function() {
        var id = $(this).data("id");
        $.post("api/cart.php", { action: "remove", id_product: id }, function() {
            loadCart();
            updateCartCount();
        });
    });

    $("body").on("click", "#btn-go-catalog", async function() {
        history.pushState(null, "", "#/home");
        await loadView("#/home");
        scrollToCatalog();
    });
}
