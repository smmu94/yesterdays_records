var adminProductsPage = 1;
var adminProductFilters = { search: "", category: "" };

async function loadAdminProducts(page) {
    if (page !== undefined) adminProductsPage = page;

    var catResponse = await $.get("api/categories.php");
    var catData = typeof catResponse === "string" ? JSON.parse(catResponse) : catResponse;
    var categories = catData.categories || [];
    var catSelect = $("#admin-product-category");
    if (catSelect.children().length <= 1) {
        categories.forEach(function(c) {
            catSelect.append(`<option value="${c.id_category}">${c.name}</option>`);
        });
    }

    var params = { action: "list_products", page: adminProductsPage };
    if (adminProductFilters.category) params.category = adminProductFilters.category;
    if (adminProductFilters.search) params.search = adminProductFilters.search;

    var response = await $.get("api/admin.php", params);
    var data = typeof response === "string" ? JSON.parse(response) : response;
    var products = data.products || [];
    var tbody = $("#admin-products-body");
    tbody.empty();

    if (products.length === 0) {
        tbody.html('<tr><td colspan="7" class="text-center py-4">No hay productos</td></tr>');
        $("#admin-products-pagination").empty();
        return;
    }

    products.forEach(function(p) {
        var row = `
            <tr>
                <td><img src="${escapeHtml(p.image)}" alt="${escapeHtml(p.name)}" class="table-thumb"></td>
                <td>${escapeHtml(p.name)}</td>
                <td>${escapeHtml(p.artist)}</td>
                <td>${escapeHtml(p.category_name)}</td>
                <td>${p.price} €</td>
                <td>${p.stock}</td>
                <td>
                    <a href="#/admin/product/edit/${p.id_product}" class="btn btn-sm btn-warning me-1"><i class="bi bi-pencil"></i></a>
                    <button class="btn btn-sm btn-danger btn-delete-product" data-id="${p.id_product}" data-name="${escapeHtml(p.name)}"><i class="bi bi-trash"></i></button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });

    renderAdminPagination("admin-products", data.pages, data.page);
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
        $("#form-heading").text("Editar Producto");
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
        }
    }

    $("#form-loading").hide();
    $("#product-form").removeClass("d-none");
}

function saveProduct() {
    var name = $("#form-name").val().trim();
    var artist = $("#form-artist").val().trim();
    var description = $("#form-description").val().trim();
    var price = parseFloat($("#form-price").val());
    var stock = parseInt($("#form-stock").val());
    var category = $("#form-category").val();

    if (!name || !artist || !description || !category) {
        $("#form-error").text("Todos los campos son obligatorios").removeClass("d-none");
        return;
    }

    if (isNaN(price) || price < 0) {
        $("#form-error").text("El precio no puede ser negativo").removeClass("d-none");
        return;
    }

    if (isNaN(stock) || stock < 0) {
        $("#form-error").text("El stock no puede ser negativo").removeClass("d-none");
        return;
    }

    $("#form-error").addClass("d-none");

    var id = $("#form-id").val();
    var fileInput = $("#form-image")[0].files[0];

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

            showToast("Éxito", id ? "Producto actualizado" : "Producto creado", "success");
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

    $("body").on("click", "#btn-select-image", function(e) {
        e.preventDefault();
        document.getElementById("form-image").click();
    });

    $("body").on("change", "#form-image", function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $("#image-preview").attr("src", e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });

    $("body").on("input", "#admin-product-search", function() {
        adminProductFilters.search = $(this).val();
        debounce("admin-products", function() { loadAdminProducts(1); }, 300);
    });

    $("body").on("change", "#admin-product-category", function() {
        adminProductFilters.category = $(this).val();
        loadAdminProducts(1);
    });

    $("body").on("click", "#admin-product-clear", function() {
        adminProductFilters = { search: "", category: "" };
        $("#admin-product-search").val("");
        $("#admin-product-category").val("");
        loadAdminProducts(1);
    });
}