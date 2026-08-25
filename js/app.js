var routes = {
    "#/home": "views/home.html",
    "#/login": "views/login.html",
    "#/register": "views/register.html",
    "#/verify": "views/verify.html",
    "#/profile": "views/profile.html",
    "#/cart": "views/cart.html"
};

var session = { logged_in: false };
var currentSearch = "";
var activeCategory = "";
var activeGenre = "";

async function loadView(hash) {
    var cleanHash = hash.split("?")[0];
    var view = routes[cleanHash];
    var publicRoutes = ["#/home", "#/login", "#/register", "#/verify", "#/cart"];

    if(cleanHash.startsWith("#/product")){
        view =  "views/product.html";
    } else if (!routes[cleanHash]) {
        history.pushState(null, "", "#/home");
        view = routes["#/home"];
    } else if (!session.logged_in && !publicRoutes.includes(cleanHash)) {
        history.pushState(null, "", "#/login");
        view = routes["#/login"];
    } else if (cleanHash.startsWith("#/admin") && session.role !== "admin") {
        history.pushState(null, "", "#/home");
        view = routes["#/home"];
    } else if (session.logged_in && (cleanHash === "#/login" || cleanHash === "#/register")) {
        history.pushState(null, "", "#/home");
        view = routes["#/home"];
    }

    var html = await $.get(view);
    $("#app").html(html);
    $("#app").scrollTop(0);

    if(cleanHash.startsWith("#/product/")) {
        var id = cleanHash.split("/")[2];
        var response = await $.get(`api/product.php?id=${id}`);
        var data = JSON.parse(response);
    
        if(data.ok){

            var fields = {
                "product-category": "category_name",
                "product-name": "product_name",
                "product-artist": "artist",
                "product-description": "description",
                "product-price": "price",
            };

            $.each(fields, function(elementId, apiKey) {
                $(`#${elementId}`).text(data.product[apiKey]);
            })
            
            $("#product-image").attr("src", data.product.image).attr("alt", data.product.product_name);
            $("#product-breadcrumb").text(data.product.product_name);
            $(".btn-add-cart").attr("data-id", data.product.id_product);
    
            $("#product-detail").show();
            $("#product-loading").hide();
        } else {
            $("#product-loading").hide();
            $("#product-error h3").text(data.message);
        }
    }

    if (cleanHash === "#/cart") {
        await loadCart();
    }

    if (cleanHash === "#/login" || cleanHash === "#/register" || cleanHash === "#/verify") {
        $(".navbar-center, .navbar-right").hide();
        $(".navbar").addClass("navbar-minimal");
    } else {
        $(".navbar-center, .navbar-right").show();
        $(".navbar").removeClass("navbar-minimal");
    }

    if (cleanHash === "#/verify") {
        var hashParts = window.location.hash.split("?");
        if (hashParts.length > 1) {
            var params = new URLSearchParams(hashParts[1]);
            var status = params.get("status");
            if (status === "ok") {
                $("#verify-success").show();
            } else {
                $("#verify-error").show();
            }
        } else {
            $("#verify-error").show();
        }
    }

    $("#btn-explore").on("click", function () {
        scrollToCatalog();
    });

    if (view === "views/home.html") {
        await loadFilters();
        await loadProducts("", "", currentSearch);
    } else {
        currentSearch = "";
        $("#search-input").val("");
        $("#clear-search").hide();
    }
}

function updateNavbar() {
    if (session.logged_in) {
        $(".auth-only").hide();
        $(".user-only").show();
        if (session.role === "admin") {
            $(".admin-only").show();
        }
        $("#user-display").text(
            `${session.role === "admin" ? session.name + " (Admin)" : session.name}`
        );
    } else {
        $(".auth-only").show();
        $(".user-only").hide();
        $(".admin-only").hide();
    }
}

async function refreshSession() {
    var data = await $.get("api/auth.php", { action: "check" });
    session = JSON.parse(data);
    updateNavbar();
}

function registerNavEvents() {
    $("body").on("click", ".nav-link", function (event) {
        var href = $(this).attr("href");
        if (href && href.startsWith("#")) {
            event.preventDefault();
            history.pushState(null, "", href);
            loadView(href);
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
}

$(window).on("popstate", function () {
    loadView(window.location.hash || "#/home");
});

init();
