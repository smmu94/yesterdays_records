/* ==========================================================
   effects.js — Micro-interactions para Yesterday's Records
   Solo efecto 1 (scroll reveal) requiere JS.
   Los demás son CSS puro.
   ========================================================== */

/* ----------------------------------------------------------
   1. SCROLL REVEAL — Intersection Observer
      Para desactivar: comentar initScrollReveal() más abajo
   ---------------------------------------------------------- */
function initScrollReveal() {
    var scrollContainer = document.getElementById('app');
    if (!scrollContainer) return;

    var targets = document.querySelectorAll('.reveal');
    if (!targets.length) return;

    // Si reduced-motion está activado, no observar
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                observer.unobserve(entry.target); // una sola vez
            }
        });
    }, {
        root: scrollContainer,
        rootMargin: '0px 0px -40px 0px',
        threshold: 0.15
    });

    targets.forEach(function (el) {
        observer.observe(el);
    });
}

/* ----------------------------------------------------------
   4. INYECTAR PARTÍCULAS DE POLVO en el hero
      Para desactivar: comentar esta función
   ---------------------------------------------------------- */
function injectHeroDust() {
    var hero = document.querySelector('.hero');
    if (!hero || hero.querySelector('.hero-dust')) return;

    var container = document.createElement('div');
    container.className = 'hero-dust';

    for (var i = 0; i < 12; i++) {
        var dot = document.createElement('div');
        dot.className = 'hero-dust__dot';
        container.appendChild(dot);
    }

    hero.insertBefore(container, hero.firstChild);
}

/* ----------------------------------------------------------
   reinitEffects — llamar después de cada cambio de vista
   ---------------------------------------------------------- */
function reinitEffects() {
    injectHeroDust();
    initScrollReveal();
}
