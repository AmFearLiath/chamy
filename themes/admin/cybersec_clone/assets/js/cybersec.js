(function () {
    'use strict';

    function initMatrix() {
        var canvas = document.getElementById('matrixCanvas');
        if (!canvas) return;

        var ctx = canvas.getContext('2d');
        if (!ctx) return;

        function resize() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        resize();

        var glyphs = '01{}[]<>/*'.split('');
        var fontSize = 14;
        var columns = Math.max(1, Math.floor(canvas.width / fontSize));
        var drops = [];
        for (var i = 0; i < columns; i++) drops[i] = 1 + Math.random() * 20;

        function draw() {
            ctx.fillStyle = 'rgba(5, 8, 16, 0.065)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = 'rgba(0, 255, 136, 0.45)';
            ctx.font = fontSize + 'px Fira Code, monospace';

            for (var c = 0; c < drops.length; c++) {
                var ch = glyphs[Math.floor(Math.random() * glyphs.length)];
                var x = c * fontSize;
                var y = drops[c] * fontSize;
                ctx.fillText(ch, x, y);
                if (y > canvas.height && Math.random() > 0.975) drops[c] = 0;
                drops[c]++;
            }
        }

        setInterval(draw, 38);

        window.addEventListener('resize', function () {
            resize();
            columns = Math.max(1, Math.floor(canvas.width / fontSize));
            drops = [];
            for (var j = 0; j < columns; j++) drops[j] = 1 + Math.random() * 20;
        });
    }

    function initParticles() {
        var container = document.getElementById('hackParticles');
        if (!container) return;
        var chars = ['0', '1', '{', '}', '[', ']', '<', '>', '/', '*'];
        for (var i = 0; i < 30; i++) {
            var p = document.createElement('div');
            p.className = 'particle';
            p.textContent = chars[Math.floor(Math.random() * chars.length)];
            p.style.left = Math.random() * 100 + '%';
            p.style.animationDelay = Math.random() * 8 + 's';
            p.style.animationDuration = (6 + Math.random() * 5) + 's';
            container.appendChild(p);
        }
    }

    function initReveal() {
        var cards = document.querySelectorAll('.card, .alert, .page-header, .table tbody tr');
        if (!cards.length) return;

        if (typeof anime !== 'undefined') {
            anime({
                targets: cards,
                opacity: [0, 1],
                translateY: [14, 0],
                delay: anime.stagger(24),
                duration: 520,
                easing: 'easeOutExpo'
            });
            return;
        }

        for (var i = 0; i < cards.length; i++) {
            cards[i].style.opacity = '1';
        }
    }

    function initGlowPulse() {
        var activeNav = document.querySelector('.nav-item.active');
        if (!activeNav || typeof gsap === 'undefined') return;

        gsap.to(activeNav, {
            boxShadow: '0 0 24px rgba(0,255,136,0.35)',
            duration: 1.1,
            repeat: -1,
            yoyo: true,
            ease: 'sine.inOut'
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initMatrix();
        initParticles();
        initReveal();
        initGlowPulse();
    });
})();
