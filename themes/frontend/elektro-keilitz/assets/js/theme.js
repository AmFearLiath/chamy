/**
 * Elektro Keilitz – Theme JavaScript
 * Mobile-Menü-Toggle und allgemeine Interaktionen
 */
(function () {
    'use strict';

    /* ─── Mobile Menu ─── */
    var toggle = document.querySelector('.ek-header__toggle');
    var menu   = document.getElementById('mobile-menu');

    if (toggle && menu) {
        toggle.addEventListener('click', function () {
            var isOpen = menu.classList.contains('is-open');
            menu.classList.toggle('is-open');
            menu.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
            toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        });

        // Menü bei Klick auf Link schließen
        var links = menu.querySelectorAll('a');
        for (var i = 0; i < links.length; i++) {
            links[i].addEventListener('click', function () {
                menu.classList.remove('is-open');
                menu.setAttribute('aria-hidden', 'true');
                toggle.setAttribute('aria-expanded', 'false');
            });
        }
    }

    /* ─── Smooth Scroll für Ankerlinks ─── */
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            var target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
})();
