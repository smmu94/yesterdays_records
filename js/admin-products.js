async function loadAdminProducts() {
    var response = await $.get("api/admin.php", { action: "list_products" });
    var data = typeof response === "string" ? JSON.parse(response) : response;
    var products = data.products || [];
    var tbody = $("#admin-products-body");
    tbody.empty();

    if (products.length === 0) {
        tbody.html('<tr><td colspan="7" class="text-center py-4">No hay productos</td></tr>');
        return;
    }

    products.forEach(function(p) {
        var row = `
            <tr>
                <td><img src="${p.image}" alt="${p.name}" style="width:50px; height:50px; object-fit:cover; border-radius:6px;"></td>
                <td>${p.name}</td>
                <td>${p.artist}</td>
                <td>${p.category_name}</td>
                <td>${p.price} €</td>
                <td>${p.stock}</td>
                <td>
                    <a href="#/admin/product/edit/${p.id_product}" class="btn btn-sm btn-warning me-1"><i class="bi bi-pencil"></i></a>
                    <button class="btn btn-sm btn-danger btn-delete-product" data-id="${p.id_product}" data-name="${p.name}"><i class="bi bi-trash"></i></button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

async function loadProductForm(id) {
    var catResponse = await $.get("api/categories.php");
    var catData = typeof catResponse === "string" ? JSON.parse(catResponse) : catResponse;
    var categories = catData.categories || [];

    var genResponse = await $.get("api/genres.php");
    var genData = typeof genResponse === "string" ? JSON.parse(genResponse) : genResponse;
    var genres = genData.genres || [];

    var catSelect = $("#form-category");
    categories.forEach(function(c) {
        catSelect.append(`<option value="${c.id_category}">${c.name}</option>`);
    });

    var genSelect = $("#form-genre");
    genres.forEach(function(g) {
        genSelect.append(`<option value="${g.id_genre}">${g.name}</option>`);
    });

    if (id) {
        $("#form-title").text("Editar Producto");
        var response = await $.get("api/admin.php", { action: "get_product", id: id });
        var data = typeof response === "string" ? JSON.parse(response) : response;

        if (data.ok === false) {
            showToast("Error", data.error, "danger");
            return;
        }

        var p = data.product;
        $("#form-id").val(p.id_product);
        $("#form-name").val(p.name);
        $("#form-artist").val(p.artist);
        $("#form-description").val(p.description);
        $("#form-price").val(p.price);
        $("#form-stock").val(p.stock);
        catSelect.val(p.id_category);
        genSelect.val(p.id_genre);

        if (p.image) {
            $("#image-preview").attr("src", p.image);
            $("#image-preview-container").show();
        }
    }

    $("#form-loading").hide();
    $("#product-form").removeClass("d-none");
}

function saveProduct() {
    var id = $("#form-id").val();
    var fileInput = $("#form-image")[0].files[0];

    if (!id && !fileInput) {
        $("#form-error").text("Debes seleccionar una imagen").removeClass("d-none");
        return;
    }

    var formData = new FormData();
    formData.append("name", $("#form-name").val());
    formData.append("artist", $("#form-artist").val());
    formData.append("description", $("#form-description").val());
    formData.append("price", $("#form-price").val());
    formData.append("stock", $("#form-stock").val());
    formData.append("id_category", $("#form-category").val());
    formData.append("id_genre", $("#form-genre").val());

    if (fileInput) {
        formData.append("image", fileInput);
    }

    if (id) {
        formData.append("action", "update_product");
        formData.append("id_product", id);
    } else {
        formData.append("action", "create_product");
    }

    $("#btn-save").prop("disabled", true);
    $("#save-icon").addClass("d-none");
    $("#save-spinner").removeClass("d-none");

    $.ajax({
        url: "api/admin.php",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            var data = typeof response === "string" ? JSON.parse(response) : response;

            $("#btn-save").prop("disabled", false);
            $("#save-icon").removeClass("d-none");
            $("#save-spinner").addClass("d-none");

            if (data.ok === false) {
                $("#form-error").text(data.error).removeClass("d-none");
                return;
            }

            showToast("Exito", id ? "Producto actualizado" : "Producto creado", "success");
            history.pushState(null, "", "#/admin/products");
            loadView("#/admin/products");
        },
        error: function() {
            $("#btn-save").prop("disabled", false);
            $("#save-icon").removeClass("d-none");
            $("#save-spinner").addClass("d-none");
            showToast("Error", "Error al guardar", "danger");
        }
    });
}

function deleteProduct(id, name) {
    $("#delete-product-name").text(name);
    $("#btn-confirm-delete").data("id", id);
    var modal = new bootstrap.Modal(document.getElementById("deleteProductModal"));
    modal.show();
}

function registerAdminProductEvents() {
    $("body").on("click", ".btn-delete-product", function() {
        var id = $(this).data("id");
        var name = $(this).data("name");
        deleteProduct(id, name);
    });

    $("body").on("click", "#btn-confirm-delete", function() {
        var id = $(this).data("id");
        var modal = bootstrap.Modal.getInstance(document.getElementById("deleteProductModal"));
        modal.hide();

        $.post("api/admin.php", { action: "delete_product", id_product: id }, function(response) {
            var data = typeof response === "string" ? JSON.parse(response) : response;

            if (data.ok === false) {
                showToast("Error", data.error, "danger");
                return;
            }

            showToast("Eliminado", "Producto borrado", "success");
            loadAdminProducts();
        });
    });

    $("body").on("submit", "#product-form", function(event) {
        event.preventDefault();
        saveProduct();
    });

    $("body").on("change", "#form-image", function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $("#image-preview").attr("src", e.target.result);
                $("#image-preview-container").show();
            };
            reader.readAsDataURL(file);
        }
    });
}