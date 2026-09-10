var routes = {
    "#/home": "views/home.html",
    "#/login": "views/login.html",
    "#/register": "views/register.html",
    "#/verify": "views/verify.html",
    "#/profile": "views/profile.html",
    "#/cart": "views/cart.html",
    "#/checkout": "views/checkout.html",
    "#/order-success": "views/order-success.html",
    "#/admin/products": "views/admin/products.html",
    "#/admin/orders": "views/admin/orders.html"
};

var dynamicRoutes = [
    { pattern: "#/product/", view: "views/product.html" },
    { pattern: "#/admin/product/new", view: "views/admin/product-form.html" },
    { pattern: "#/admin/product/edit", view: "views/admin/product-form.html" }
];

var publicRoutes = ["#/home", "#/login", "#/register", "#/verify", "#/cart", "#/404"];

var routeHandlers = {
    "#/home": async function() {
        await loadFilters();
        await loadProducts("", "", currentSearch);
    },
    "#/product/": async function(hash) {
        var id = hash.split("/")[2];
        var response = await $.get(`api/product.php?id=${id}`);
        var data = typeof response === "string" ? JSON.parse(response) : response;

        if (data.ok === false) {
            $("#product-loading").hide();
            showEl("#product-error");
            $("#product-error h3").text(data.error);
        } else if (data.product) {
            var p = data.product;
            var fields = {
                "product-category": "category_name",
                "product-name": "name",
                "product-artist": "artist",
                "product-description": "description",
                "product-price": "price",
            };
            $.each(fields, function(elementId, apiKey) {
                $(`#${elementId}`).text(p[apiKey]);
            });
            $("#product-image").attr("src", p.image).attr("alt", p.name);
            $("#product-breadcrumb").text(p.name);
            $(".btn-add-cart").attr("data-id", p.id_product);
            $("#qty-value").text(1);
            showEl("#product-detail");
            $("#product-loading").hide();
        }
    },
    "#/cart": async function() {
        await loadCart();
        var params = new URLSearchParams(window.location.hash.split("?")[1]);
        if (params.get("canceled") === "1") {
            showToast("Pago cancelado", "Tu pedido no fue procesado", "warning");
            history.replaceState(null, "", "#/cart");
        }
    },
    "#/checkout": async function() {
        await loadCheckout();
    },
    "#/profile": async function() {
        await loadProfile();
    },
    "#/order-success": async function() {
        var params = new URLSearchParams(window.location.hash.split("?")[1]);
        var orderId = params.get("id");
        if (orderId) {
            $("#order-id").text(orderId);
            $.post("api/checkout.php", { action: "confirm", id_order: orderId });
        }
        $("#btn-back-home").on("click", function(e) {
            e.preventDefault();
            history.pushState(null, "", "#/home");
            loadView("#/home");
        });
    },
    "#/verify": async function() {
        var hashParts = window.location.hash.split("?");
        if (hashParts.length > 1) {
            var params = new URLSearchParams(hashParts[1]);
            var status = params.get("status");
            if (status === "ok") {
                showEl("#verify-success");
            } else {
                showEl("#verify-error");
            }
        } else {
            showEl("#verify-error");
        }
    },
    "#/admin/products": async function() {
        await loadAdminProducts();
    },
    "#/admin/orders": async function() {
        await loadAdminOrders();
    },
    "#/admin/product/new": async function() {
        await loadProductForm();
    },
    "#/admin/product/edit": async function(hash) {
        var id = hash.split("/")[4];
        await loadProductForm(id);
    }
};

var session = { logged_in: false };
var currentSearch = "";
var activeCategory = "";
var activeGenre = "";

function resolveRoute(cleanHash) {
    if (routes[cleanHash]) {
        return { view: routes[cleanHash], key: cleanHash };
    }
    for (var i = 0; i < dynamicRoutes.length; i++) {
        if (cleanHash.startsWith(dynamicRoutes[i].pattern)) {
            return { view: dynamicRoutes[i].view, key: dynamicRoutes[i].pattern };
        }
    }
    return null;
}

function checkPermissions(cleanHash) {
    if (!session.logged_in && !publicRoutes.includes(cleanHash)) {
        return { redirect: "#/login" };
    }
    if (cleanHash.startsWith("#/admin") && session.role !== "admin") {
        return { redirect: "#/home" };
    }
    if (session.logged_in && (cleanHash === "#/login" || cleanHash === "#/register")) {
        return { redirect: "#/home" };
    }
    return null;
}

function applyNavbarVisibility(hash) {
    if (hash === "#/login" || hash === "#/register" || hash === "#/verify") {
        $(".navbar-nav, .search-bar, .search-icon-mobile, .navbar-user-area").hide();
        $(".navbar").addClass("navbar-minimal");
    } else {
        $(".navbar-nav, .search-bar, .search-icon-mobile, .navbar-user-area").show();
        $(".navbar").removeClass("navbar-minimal");
    }
}

async function loadView(hash) {
    var cleanHash = hash.split("?")[0];
    var route = resolveRoute(cleanHash);

    if (!route) {
        history.pushState(null, "", "#/404");
        route = { view: "views/404.html", key: "#/404" };
        cleanHash = "#/404";
    }

    var perm = checkPermissions(cleanHash);
    if (perm) {
        history.pushState(null, "", perm.redirect);
        route = { view: routes[perm.redirect], key: perm.redirect };
        cleanHash = perm.redirect;
    }

    var html = await $.get(route.view);
    $("#app").html(html);
    $("#app").scrollTop(0);

    if (routeHandlers[route.key]) {
        await routeHandlers[route.key](cleanHash);
    }

    applyNavbarVisibility(cleanHash);
    updateActiveNavLink(cleanHash);

    if (route.view !== "views/home.html") {
        currentSearch = "";
        $("#search-input").val("");
        hideEl("#clear-search");
    }

    $("#btn-explore").on("click", function() {
        scrollToCatalog();
    });

    reinitEffects();
}

function updateNavbar() {
    if (session.logged_in) {
        $(".auth-only").hide();
        showEl(".user-only");
        if (session.role === "admin") {
            showEl(".admin-only");
            showEl(".admin-only-nav");
            hideEl(".client-only");
            $("#user-display").text(session.name);
        } else {
            showEl(".client-only");
            hideEl(".admin-only-nav");
            $("#user-display").text(session.name);
        }
    } else {
        $(".auth-only").show();
        hideEl(".user-only");
        hideEl(".admin-only");
        hideEl(".client-only");
        hideEl(".admin-only-nav");
    }
}

async function refreshSession() {
    var data = await $.get("api/auth.php", { action: "check" });
    session = JSON.parse(data);
    updateNavbar();
}

function registerNavEvents() {
    $("body").on("click", ".nav-link", function(event) {
        var href = $(this).attr("href");
        if (href && href.startsWith("#")) {
            event.preventDefault();
            history.pushState(null, "", href);
            loadView(href);
        }
    });

}

function updateActiveNavLink(hash) {
    $(".navbar-nav .nav-link").removeClass("active");
    var cleanHash = hash.split("?")[0];
    $(".navbar-nav .nav-link").each(function() {
        var href = $(this).attr("href");
        if (href === cleanHash) {
            $(this).addClass("active");
        }
    });
}

function scrollToCatalog() {
    var catalog = document.getElementById("catalog");
    if (catalog) {
        catalog.scrollIntoView({ behavior: "smooth" });
    }
}

async function init() {
    var navbar = await $.get("views/navbar.html");
    $("#navbar").html(navbar);

    await refreshSession();
    await loadView(window.location.hash || "#/home");

    registerNavEvents();
    registerCatalogEvents();
    registerCartEvents();
    updateCartCount();
    registerCheckoutEvents();
    registerProfileEvents();
    registerAdminProductEvents();
    registerAdminOrderEvents();

    $("body").on("click", ".admin-pagination-btn", function(e) {
        e.preventDefault();
        var prefix = $(this).data("prefix");
        var page = $(this).data("page");
        if (!page || page < 1) return;
        if (prefix === "admin-products") loadAdminProducts(page);
        if (prefix === "admin-orders") loadAdminOrders(page);
    });

    $("body").on("click", "#qty-minus", function() {
        var val = parseInt($("#qty-value").text()) || 1;
        if (val > 1) $("#qty-value").text(val - 1);
    });

    $("body").on("click", "#qty-plus", function() {
        var val = parseInt($("#qty-value").text()) || 1;
        if (val < 99) $("#qty-value").text(val + 1);
    });
}

$(window).on("popstate", function() {
    loadView(window.location.hash || "#/home");
});

init();