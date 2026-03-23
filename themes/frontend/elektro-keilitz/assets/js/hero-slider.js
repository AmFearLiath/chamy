/**
 * Elektro Keilitz – Hero Slider
 * Autoplay-Carousel mit Dot/Arrow-Navigation und Swipe-Support.
 */
(function () {
    'use strict';

    var slider = document.querySelector('.ek-hero-slider');
    if (!slider) return;

    var slides   = slider.querySelectorAll('.ek-hero-slider__slide');
    var dots     = slider.querySelectorAll('.ek-hero-slider__dot');
    var prevBtn  = slider.querySelector('.ek-hero-slider__prev');
    var nextBtn  = slider.querySelector('.ek-hero-slider__next');

    if (slides.length < 2) return;

    var current  = 0;
    var total    = slides.length;
    var interval = 6000;
    var timer    = null;
    var touchStartX = 0;
    var touchEndX   = 0;

    function goTo(index) {
        slides[current].classList.remove('is-active');
        slides[current].setAttribute('aria-hidden', 'true');
        if (dots[current]) dots[current].classList.remove('is-active');

        current = ((index % total) + total) % total;

        slides[current].classList.add('is-active');
        slides[current].setAttribute('aria-hidden', 'false');
        if (dots[current]) dots[current].classList.add('is-active');
    }

    function next() { goTo(current + 1); }
    function prev() { goTo(current - 1); }

    function startAutoplay() {
        stopAutoplay();
        timer = setInterval(next, interval);
    }

    function stopAutoplay() {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    }

    // Arrow-Buttons
    if (nextBtn) nextBtn.addEventListener('click', function () { next(); startAutoplay(); });
    if (prevBtn) prevBtn.addEventListener('click', function () { prev(); startAutoplay(); });

    // Dots
    dots.forEach(function (dot, i) {
        dot.addEventListener('click', function () { goTo(i); startAutoplay(); });
    });

    // Keyboard
    slider.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowLeft')  { prev(); startAutoplay(); }
        if (e.key === 'ArrowRight') { next(); startAutoplay(); }
    });

    // Touch/Swipe
    slider.addEventListener('touchstart', function (e) {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    slider.addEventListener('touchend', function (e) {
        touchEndX = e.changedTouches[0].screenX;
        var diff = touchStartX - touchEndX;
        if (Math.abs(diff) > 50) {
            if (diff > 0) next(); else prev();
            startAutoplay();
        }
    }, { passive: true });

    // Pause on hover
    slider.addEventListener('mouseenter', stopAutoplay);
    slider.addEventListener('mouseleave', startAutoplay);

    // Pause wenn Tab nicht sichtbar
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) stopAutoplay();
        else startAutoplay();
    });

    // Start
    startAutoplay();
})();
