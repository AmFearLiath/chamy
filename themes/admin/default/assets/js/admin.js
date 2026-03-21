/* ============================================================
   CHAMY ADMIN – NEON DARK THEME · JavaScript
   Sidebar toggle, Toasts, Modal helpers, Dropdown
   ============================================================ */

(function () {
    'use strict';

    /* ---------- Sidebar toggle ---------- */
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('[data-toggle-sidebar]');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('sidebar-collapsed');
            localStorage.setItem('chamy_sidebar', sidebar.classList.contains('collapsed') ? '1' : '0');
        });

        if (localStorage.getItem('chamy_sidebar') === '1') {
            sidebar.classList.add('collapsed');
            document.querySelector('.main-content')?.classList.add('sidebar-collapsed');
        }
    }

    /* ---------- Dropdown menus ---------- */
    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('[data-dropdown]');

        // Determine the menu element associated with the trigger.
        // Some markup places the menu as the trigger's next sibling, others as a child.
        const triggerMenu = trigger ? (
            (trigger.nextElementSibling && trigger.nextElementSibling.classList.contains('dropdown-menu'))
                ? trigger.nextElementSibling
                : trigger.querySelector('.dropdown-menu')
        ) : null;

        // Close other open menus
        document.querySelectorAll('.dropdown-menu.open').forEach(function (menu) {
            if (!trigger || menu !== triggerMenu) {
                menu.classList.remove('open');
            }
        });

        if (trigger && triggerMenu) {
            e.preventDefault();
            triggerMenu.classList.toggle('open');
        }
    });

    /* ---------- Toast notifications ---------- */
    window.ChamyToast = {
        container: null,

        init: function () {
            if (this.container) return;
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            this.container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
            document.body.appendChild(this.container);
        },

        show: function (message, type) {
            this.init();
            type = type || 'info';
            var toast = document.createElement('div');
            toast.className = 'alert alert-' + type;
            toast.style.cssText = 'min-width:280px;max-width:420px;animation:toastIn .3s ease;cursor:pointer;';
            toast.textContent = message;
            toast.addEventListener('click', function () {
                toast.style.animation = 'toastOut .25s ease forwards';
                setTimeout(function () { toast.remove(); }, 250);
            });
            this.container.appendChild(toast);
            setTimeout(function () {
                toast.style.animation = 'toastOut .25s ease forwards';
                setTimeout(function () { toast.remove(); }, 250);
            }, 4500);
        }
    };

    /* ---------- Multi-select popup for comma-separated inputs ---------- */
        
        /* Delegated fallback removed — use per-element handlers registered by initParentToggles
           to avoid double-toggling caused by multiple listeners. */
    (function(){
        var activePopup = null;

        function closePopup() {
            if (activePopup && activePopup.parentNode) activePopup.remove();
            activePopup = null;
        }

        function openPopupForInput(input) {
            closePopup();
            var tplId = input.getAttribute('data-popup-template');
            if (!tplId) return;
            var tpl = document.getElementById(tplId);
            if (!tpl) return;
            var content = tpl.innerHTML;
            var wrapper = document.createElement('div');
            wrapper.className = 'multi-select-popup';
            wrapper.innerHTML = content;
            // remember target
            wrapper._targetInput = input;
            document.body.appendChild(wrapper);

            // Sync popup options with currently selected values in the target (hide/disable already selected)
            try {
                var hidden = input.querySelector('input[type="hidden"]');
                var cur = [];
                if (hidden && hidden.value) cur = hidden.value.split(',').map(function(s){ return s.trim(); }).filter(function(s){ return s !== ''; });
                wrapper.querySelectorAll('.multi-select-option').forEach(function(opt){
                    var v = opt.getAttribute('data-value');
                    if (cur.indexOf(v) !== -1) {
                        opt.classList.add('selected');
                        opt.setAttribute('data-selected', '1');
                        opt.setAttribute('aria-disabled', 'true');
                        opt.style.opacity = 0.6;
                        opt.style.pointerEvents = 'none';
                    }
                });
            } catch (e) {
                // ignore
            }

            // Positioning: try below input, else above
            var rect = input.getBoundingClientRect();
            var top = rect.bottom + window.scrollY + 6;
            var left = rect.left + window.scrollX;
            wrapper.style.minWidth = Math.max(200, rect.width) + 'px';
            wrapper.style.left = left + 'px';
            wrapper.style.top = top + 'px';

            activePopup = wrapper;
        }

        document.addEventListener('click', function(e){
            // Open popup only when + button clicked or when the label preceding the container is clicked
            var addBtn = e.target.closest('.multi-select-add');
            if (addBtn) {
                e.preventDefault();
                var containerBtn = addBtn.closest('.multi-select-container');
                if (containerBtn) openPopupForInput(containerBtn);
                return;
            }

            var lbl = e.target.closest('.form-label');
            if (lbl) {
                // if the next sibling is the multi-select container, open its popup
                var next = lbl.nextElementSibling;
                if (next && next.classList && next.classList.contains('multi-select-container')) {
                    e.preventDefault();
                    openPopupForInput(next);
                    return;
                }
            }

            var opt = e.target.closest('.multi-select-option');
            if (opt && activePopup) {
                e.preventDefault();
                // ignore clicks on already selected/disabled options
                if (opt.getAttribute('data-selected') === '1' || opt.classList.contains('selected')) {
                    return;
                }
                var val = opt.getAttribute('data-value');
                var label = opt.getAttribute('data-label') || opt.textContent.trim();
                var target = activePopup._targetInput;
                if (!target) { closePopup(); return; }

                var hidden = target.querySelector('input[type="hidden"]');
                if (!hidden) {
                    // create hidden input if missing
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = target.getAttribute('data-hidden-name') || target.getAttribute('data-name') || '';
                    target.insertBefore(hidden, target.querySelector('.multi-select-input'));
                }

                var cur = (hidden.value || '').split(',').map(function(s){ return s.trim(); }).filter(function(s){ return s !== ''; });
                if (cur.indexOf(val) === -1) {
                    cur.push(val);
                    hidden.value = cur.join(', ');
                    // render badge
                    var badge = document.createElement('span');
                    badge.className = 'multi-select-badge';
                    badge.setAttribute('data-value', val);
                    badge.innerHTML = '<span class="badge-label">' + escapeHtml(label) + '</span> <span class="remove">✕</span>';
                    var inputEl = target.querySelector('.multi-select-input');
                    target.insertBefore(badge, inputEl);
                    // mark container as having badges (so placeholder can be hidden)
                    target.classList.add('has-badges');
                }
                return;
            }

            // Click on badge remove
            var remove = e.target.closest('.multi-select-badge .remove');
            if (remove) {
                var badge = remove.closest('.multi-select-badge');
                var containerEl = badge.closest('.multi-select-container');
                var hidden = containerEl.querySelector('input[type="hidden"]');
                var val = badge.getAttribute('data-value');
                badge.remove();
                if (hidden) {
                    var cur2 = (hidden.value || '').split(',').map(function(s){ return s.trim(); }).filter(function(s){ return s !== '' && s !== val; });
                    hidden.value = cur2.join(', ');
                }
                // remove has-badges class if no badges remain
                if (containerEl.querySelectorAll('.multi-select-badge').length === 0) {
                    containerEl.classList.remove('has-badges');
                }
                return;
            }

            // Clicked outside -> close any popup
            if (!e.target.closest('.multi-select-popup')) {
                if (activePopup && activePopup.parentNode) activePopup.remove();
                activePopup = null;
            }
        });

        // escape HTML helper
        function escapeHtml(str) {
            return String(str).replace(/[&<>\"']/g, function (s) {
                return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[s];
            });
        }

        // render initial badges from existing hidden inputs on DOMContentLoaded
        document.addEventListener('DOMContentLoaded', function(){
            document.querySelectorAll('.multi-select-container').forEach(function(container){
                var hidden = container.querySelector('input[type="hidden"]');
                if (hidden && hidden.value) {
                    var vals = hidden.value.split(',').map(function(s){ return s.trim(); }).filter(function(s){ return s !== ''; });
                    vals.forEach(function(v){
                        // Attempt to find label in popup template
                        var tplId = container.getAttribute('data-popup-template');
                        var label = v;
                        var tpl = document.getElementById(tplId);
                        if (tpl) {
                            var opt = tpl.querySelector('[data-value="' + v + '"]');
                            if (opt) label = opt.getAttribute('data-label') || opt.textContent.trim();
                        }
                        var badge = document.createElement('span');
                        badge.className = 'multi-select-badge';
                        badge.setAttribute('data-value', v);
                        badge.innerHTML = '<span class="badge-label">' + escapeHtml(label) + '</span> <span class="remove">✕</span>';
                        var inputEl = container.querySelector('.multi-select-input');
                        container.insertBefore(badge, inputEl);
                    });
                    // if we rendered any badges, mark the container
                    if (container.querySelectorAll('.multi-select-badge').length > 0) container.classList.add('has-badges');
                }
            });
        });
    })();
    /* Inject toast animations */
    var style = document.createElement('style');
    style.textContent = '@keyframes toastIn{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:translateX(0)}} @keyframes toastOut{from{opacity:1;transform:translateX(0)}to{opacity:0;transform:translateX(40px)}}';
    document.head.appendChild(style);

    /* ---------- Confirm dialogs ---------- */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-confirm]');
        if (btn) {
            var message = btn.getAttribute('data-confirm') || 'Are you sure?';
            var double = btn.getAttribute('data-confirm-double');
            if (double && double !== '0') {
                // First confirmation
                if (!confirm(message)) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return;
                }
                // Second confirmation with optional second message
                var second = btn.getAttribute('data-confirm-second') || (message + '\n\n' + 'Bitte bestätigen Sie erneut.');
                if (!confirm(second)) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return;
                }
                // both confirmed -> allow event
                return;
            }

            if (!confirm(message)) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        }
    });

    /* ---------- Auto-slug generation ---------- */
    var titleInput = document.querySelector('[data-slug-source]');
    var slugInput = document.querySelector('[data-slug-target]');

    if (titleInput && slugInput) {
        titleInput.addEventListener('input', function () {
            if (!slugInput.dataset.edited) {
                slugInput.value = titleInput.value
                    .toLowerCase()
                    .replace(/[äÄ]/g, 'ae').replace(/[öÖ]/g, 'oe').replace(/[üÜ]/g, 'ue').replace(/ß/g, 'ss')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/(^-|-$)/g, '');
            }
        });
        slugInput.addEventListener('input', function () {
            slugInput.dataset.edited = '1';
        });
    }

    /* ---------- Flash messages auto-dismiss ---------- */
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function (el) {
        setTimeout(function () {
            el.style.animation = 'toastOut .25s ease forwards';
            setTimeout(function () { el.remove(); }, 250);
        }, parseInt(el.dataset.autoDismiss) || 5000);
    });

    /* ---------- Dark / Light mode toggle ---------- */
    var themeToggles = document.querySelectorAll('[data-theme-toggle]');
    if (themeToggles && themeToggles.length) {
        // Restore saved theme
        var saved = localStorage.getItem('chamy_theme');
        if (saved) {
            document.body.setAttribute('data-theme', saved);
        }

        themeToggles.forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                var current = document.body.getAttribute('data-theme') || 'dark';
                var next = current === 'dark' ? 'light' : 'dark';
                document.body.setAttribute('data-theme', next);
                localStorage.setItem('chamy_theme', next);
            });
        });
    }

    /* ---------- Tabs: generic tab support for admin pages ---------- */
    try {
        var tabsContainer = document.querySelector('.tabs');
        if (tabsContainer) {
            console.debug('admin.js: tabs container found');
            tabsContainer.addEventListener('click', function (e) {
                var btn = e.target.closest('.tab-btn');
                if (!btn) return;
                e.preventDefault();
                var allBtns = Array.from(tabsContainer.querySelectorAll('.tab-btn'));
                var panelsRoot = document.querySelector('.tab-panels');
                var allPanels = panelsRoot ? Array.from(panelsRoot.querySelectorAll('.tab-panel')) : Array.from(document.querySelectorAll('.tab-panel'));
                allBtns.forEach(function (b) { b.classList.remove('active'); });
                allPanels.forEach(function (p) { p.classList.remove('active'); });
                btn.classList.add('active');
                var tab = btn.getAttribute('data-tab');
                var panel = document.querySelector('#tab-' + tab);
                if (panel) panel.classList.add('active');
                console.debug('admin.js: activated tab', tab, 'panel=', panel);
            });
        }
    } catch (err) {
        console.error('admin.js: tab handler error', err);
    }

    /* ---------- Inline modal templates (open template content in modal) ---------- */
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-modal-target]');
        if (!trigger) return;
        e.preventDefault();
        var tplId = trigger.getAttribute('data-modal-target');
        var title = trigger.getAttribute('data-modal-title') || trigger.textContent.trim();
        var tpl = document.getElementById(tplId);
        if (!tpl) return console.warn('Modal template not found:', tplId);
        var content = tpl.innerHTML;
        window.ChamyModal.open(title, content);
        // Wire modal-close buttons inside template
        setTimeout(function () {
            document.querySelectorAll('.modal-close, .modal-close-btn').forEach(function (btn) {
                btn.addEventListener('click', function () { window.ChamyModal.close(); });
            });
            // When form inside modal is submitted, disable buttons to prevent double submit
            var form = document.querySelector('.modal-overlay .modal form');
            if (form) {
                form.addEventListener('submit', function () {
                    form.querySelectorAll('button').forEach(function (b) { b.disabled = true; });
                });
            }
        }, 20);
    });

    /* ---------- Modal helper ---------- */
    window.ChamyModal = {
        open: function (title, bodyHtml, footerHtml) {
            this.close();
            var overlay = document.createElement('div');
            overlay.className = 'modal-overlay';
            overlay.innerHTML =
                '<div class="modal">' +
                    '<div class="modal-header"><h3>' + title + '</h3><button class="modal-close" type="button">&times;</button></div>' +
                    '<div class="modal-body">' + bodyHtml + '</div>' +
                    (footerHtml ? '<div class="modal-footer">' + footerHtml + '</div>' : '') +
                '</div>';
            document.body.appendChild(overlay);
            overlay.querySelector('.modal-close').addEventListener('click', function () { window.ChamyModal.close(); });
            overlay.addEventListener('click', function (e) { if (e.target === overlay) window.ChamyModal.close(); });
            return overlay;
        },
        close: function () {
            var overlay = document.querySelector('.modal-overlay');
            if (overlay) overlay.remove();
        }
    };

    /* ---------- Sidebar parent toggles (ensure module parents can be toggled) ---------- */
    (function () {
        function initParentToggles() {
            document.querySelectorAll('.nav-item-parent[data-nav-toggle]').forEach(function (parentItem) {
                var key = 'nav_section_' + parentItem.getAttribute('data-nav-toggle');

                // Restore open state from localStorage (unless server already set it open)
                try {
                    if (!parentItem.classList.contains('open') && localStorage.getItem(key) === '1') {
                        parentItem.classList.add('open');
                    }
                } catch (e) { /* ignore localStorage errors */ }

                // Attach click handler
                parentItem.addEventListener('click', function (e) {
                    e.preventDefault();
                    parentItem.classList.toggle('open');
                    try { localStorage.setItem(key, parentItem.classList.contains('open') ? '1' : '0'); } catch (err) { /* ignore */ }
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initParentToggles);
        } else {
            initParentToggles();
        }
    })();

    /* ---------- Smart back-button & navigation history ---------- */
    // Helpers: key for a location (relative path+search+hash)
    function locKey(url) {
        try {
            var u = url ? new URL(url, location.href) : location;
            return u.pathname + (u.search || '') + (u.hash || '');
        } catch (e) { return location.pathname + location.search + location.hash; }
    }

    // Save current page state (scroll position, active tab) under a key
    function saveCurrentPageState() {
        try {
            var key = locKey();
            var state = { scrollY: window.scrollY || 0 };
            // detect active tab (if any)
            var activeTabBtn = document.querySelector('.tabs .tab-btn.active');
            if (activeTabBtn) state.activeTab = activeTabBtn.getAttribute('data-tab');
            sessionStorage.setItem('chamy_state::' + key, JSON.stringify(state));
        } catch (e) { /* ignore */ }
    }

    // Restore page state if stored for this location; then remove it
    function restorePageStateIfPresent() {
        try {
            var key = locKey();
            var s = sessionStorage.getItem('chamy_state::' + key);
            if (!s) return;
            var state = JSON.parse(s);
            // restore tab
            if (state.activeTab) {
                var tabBtn = document.querySelector('.tabs .tab-btn[data-tab="' + state.activeTab + '"]');
                if (tabBtn) { tabBtn.click(); }
            }
            // restore scroll after a short delay to allow layout
            setTimeout(function () {
                try { window.scrollTo(0, parseInt(state.scrollY || 0)); } catch (e) {}
            }, 50);
            sessionStorage.removeItem('chamy_state::' + key);
        } catch (e) { /* ignore */ }
    }

    // Record internal navigations so we can navigate back to the actual previous internal page
    document.addEventListener('click', function (e) {
        try {
            var a = e.target.closest('a[href]');
            if (!a) return;
            // skip links that open modals or external targets
            if (a.hasAttribute('data-modal-target') || a.target === '_blank') return;
            // only consider plain left-click without modifiers
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            var url = new URL(a.href, location.href);
            if (url.origin === location.origin) {
                // save current page state before navigating away
                saveCurrentPageState();
                // store destination as last internal
                sessionStorage.setItem('chamy_last_internal', url.pathname + url.search + url.hash);
            }
        } catch (err) {
            // ignore
        }
    }, true);

    // also save state before unload as a fallback
    window.addEventListener('beforeunload', function () { try { saveCurrentPageState(); } catch (e) {} });

    // Back button handler: anchors with data-back="auto"
    document.addEventListener('click', function (e) {
        var back = e.target.closest('a[data-back="auto"]');
        if (!back) return;
        e.preventDefault();
        var fallback = back.getAttribute('href') || '/admin';
        var last = null;
        try { last = sessionStorage.getItem('chamy_last_internal'); } catch (err) { last = null; }
        function isSameOrigin(url) { try { return new URL(url, location.href).origin === location.origin; } catch (e) { return false; } }
        if (last && last !== location.pathname + location.search + location.hash && isSameOrigin(last)) {
            location.href = last;
            return;
        }
        if (document.referrer && isSameOrigin(document.referrer)) {
            location.href = document.referrer;
            return;
        }
        location.href = fallback;
    });

    // Restore page state if present (run on load)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restorePageStateIfPresent);
    } else {
        setTimeout(restorePageStateIfPresent, 20);
    }

})();
