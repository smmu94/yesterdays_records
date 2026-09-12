function initScrollReveal() {
    var scrollContainer = document.getElementById("app");
    if (!scrollContainer) return;

    var targets = document.querySelectorAll(".reveal:not(.revealed)");
    if (!targets.length) return;

    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        targets.forEach(function (el) {
            el.classList.add("revealed");
        });
        return;
    }

    var observer = new IntersectionObserver(
        function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add("revealed");
                    observer.unobserve(entry.target);
                }
            });
        },
        {
            root: scrollContainer,
            rootMargin: "50px 0px",
            threshold: 0.01,
        },
    );

    targets.forEach(function (el) {
        observer.observe(el);
    });

    setTimeout(function () {
        document
            .querySelectorAll(".reveal:not(.revealed)")
            .forEach(function (el) {
                el.classList.add("revealed");
            });
    }, 800);
}

function injectHeroDust() {
    var hero = document.querySelector(".hero");
    if (!hero || hero.querySelector(".hero-dust")) return;

    var container = document.createElement("div");
    container.className = "hero-dust";

    for (var i = 0; i < 12; i++) {
        var dot = document.createElement("div");
        dot.className = "hero-dust__dot";
        container.appendChild(dot);
    }

    hero.insertBefore(container, hero.firstChild);
}

function reinitEffects() {
    injectHeroDust();
    initScrollReveal();
}
