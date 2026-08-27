async function loadProducts(category, genre, search) {
    var url = "api/products.php";
    var params = [];

    if (category) {
        params.push(`category=${category}`);
    }

    if (genre) {
        params.push(`genre=${genre}`);
    }

    if (search) {
        params.push(`search=${search}`);
    }

    if (params.length > 0) {
        url = `${url}?${params.join("&")}`;
    }

    var response = await $.get(url);
    var data = typeof response === "string" ? JSON.parse(response) : response;
    var products = data.products || [];
    var grid = $("#products-grid");

    grid.empty();

    if (products.length === 0) {
        grid.html('<div class="col-12 text-center flex-1 py-5"><i class="bi bi-search fs-1 text-light d-block mb-3"></i><h3 class="text-light">No se encontraron productos</h3><p class="text-light fs-5">Intenta con otros filtros o terminos de busqueda.</p></div>');
        return;
    }

    products.forEach(product => {
        var card = `
            <div class="col-md-4 mb-4">
                <div class="card product-card h-100">
                    <img src="${product.image}" class="card-img-top" alt="${product.name}">
                    <div class="card-body">
                        <h5 class="card-title">${product.name}</h5>
                        <p class="card-text">${product.artist}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="card-text fw-bold mb-0">${product.price} €</p>
                            <button class="btn btn-warning btn-add-cart" data-id="${product.id_product}"><i class="bi bi-cart-plus"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        grid.append(card);
    });
}

async function loadFilters() {
    $("#category-filters").empty();
    $("#genre-filters").empty();

    $("#category-filters").append('<button type="button" class="btn btn-outline-warning active" data-category="">Todos</button>');
    $("#genre-filters").append('<button type="button" class="btn btn-outline-warning active" data-genre="">Todos</button>');

    var catResponse = await $.get("api/categories.php");
    var catData = typeof catResponse === "string" ? JSON.parse(catResponse) : catResponse;
    var categories = catData.categories || [];

    var genResponse = await $.get("api/genres.php");
    var genData = typeof genResponse === "string" ? JSON.parse(genResponse) : genResponse;
    var genres = genData.genres || [];

    categories.forEach(function(cat) {
        var btn = `<button type="button" class="btn btn-outline-warning" data-category="${cat.id_category}">${cat.name}</button>`;
        $("#category-filters").append(btn);
    });

    genres.forEach(function(gen) {
        var btn = `<button type="button" class="btn btn-outline-warning" data-genre="${gen.id_genre}">${gen.name}</button>`;
        $("#genre-filters").append(btn);
    });
}

function registerCatalogEvents() {
    $("body").on("click", "#category-filters .btn", function() {
        $("#category-filters .btn").removeClass("active");
        $(this).addClass("active");
        activeCategory = $(this).data("category");
        loadProducts(activeCategory, activeGenre);
    });

    $("body").on("click", "#genre-filters .btn", function() {
        $("#genre-filters .btn").removeClass("active");
        $(this).addClass("active");
        activeGenre = $(this).data("genre");
        loadProducts(activeCategory, activeGenre);
    });

    $("body").on("submit", "#search-form", async function(event) {
        event.preventDefault();
        var search = $("#search-input").val().trim();
        if (search !== "") {
            currentSearch = search;
            history.pushState(null, "", "#/home");
            await loadView("#/home");
            var catalog = document.getElementById("catalog");
            if (catalog) {
                catalog.scrollIntoView({ behavior: "smooth" });
            }
        }
    });

    $("body").on("input", "#search-input", function() {
        if ($(this).val().length > 0) {
            $("#clear-search").show();
        } else {
            $("#clear-search").hide();
        }
    });

    $("body").on("click", "#clear-search", function() {
        $("#search-input").val("");
        $(this).hide();
        currentSearch = "";
        if ($("#products-grid").length) {
            loadProducts();
        }
        $("#search-input").focus();
    });

    $("body").on("click", ".product-card", function(event) {
        if ($(event.target).closest(".btn-add-cart").length) return;
        var id = $(this).find("button").attr("data-id");
        history.pushState(null, "", `#/product/${id}`);
        loadView(`#/product/${id}`);
    })
}
