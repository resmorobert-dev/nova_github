/*
 * NOVA · Los Andes — comportamiento visual compartido
 *  1) Tooltips: cualquier elemento con data-tip muestra su descripción
 *     al pasar el cursor, al enfocar con teclado o al mantener pulsado (táctil).
 *  2) Respaldo de transición entre páginas para navegadores sin
 *     View Transitions entre documentos (la animación principal está en el CSS).
 * No depende de librerías ni modifica la lógica de formularios o rutas.
 */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var root = document.documentElement;

    /* ---------- Accesibilidad: nombre accesible a partir de data-tip ---------- */
    function prepareTips() {
        document.querySelectorAll('[data-tip]').forEach(function (el) {
            var hasText = (el.textContent || '').replace(/\s+/g, '').length > 0;
            if (!hasText && !el.hasAttribute('aria-label') && !el.hasAttribute('aria-labelledby')) {
                el.setAttribute('aria-label', el.getAttribute('data-tip'));
            }
        });
    }

    /* ---------- Tooltip ---------- */
    var tip = document.createElement('div');
    tip.className = 'nova-tip';
    tip.id = 'nova-tip';
    tip.setAttribute('role', 'tooltip');
    var current = null;
    var showTimer = null;
    var hideTimer = null;
    var GAP = 12;
    var MARGIN = 8;

    function place(target) {
        var r = target.getBoundingClientRect();
        var tw = tip.offsetWidth;
        var th = tip.offsetHeight;
        var vw = document.documentElement.clientWidth;
        var vh = window.innerHeight;

        var side = (r.top - th - GAP >= MARGIN) ? 'top' : 'bottom';
        if (side === 'bottom' && r.bottom + th + GAP > vh - MARGIN && r.top - th - GAP < MARGIN) {
            side = (vh - r.bottom > r.top) ? 'bottom' : 'top';
        }
        var top = side === 'top' ? r.top - th - GAP : r.bottom + GAP;
        top = Math.max(MARGIN, Math.min(top, vh - th - MARGIN));

        var center = r.left + r.width / 2;
        var left = center - tw / 2;
        left = Math.max(MARGIN, Math.min(left, vw - tw - MARGIN));

        tip.style.left = Math.round(left) + 'px';
        tip.style.top = Math.round(top) + 'px';
        tip.style.setProperty('--arrow-x', Math.round(Math.max(14, Math.min(center - left, tw - 14))) + 'px');
        tip.setAttribute('data-side', side);
    }

    function show(target) {
        var text = target.getAttribute('data-tip');
        if (!text) { return; }
        clearTimeout(hideTimer);
        current = target;
        if (!tip.parentNode) { document.body.appendChild(tip); }
        tip.textContent = text;
        tip.classList.remove('is-visible');
        tip.style.left = '0px'; tip.style.top = '0px';
        place(target);
        target.setAttribute('aria-describedby', 'nova-tip');
        // Fuerza un cuadro para que la animación de entrada se vea
        requestAnimationFrame(function () { tip.classList.add('is-visible'); });
    }

    function hide() {
        clearTimeout(showTimer);
        if (current) { current.removeAttribute('aria-describedby'); }
        current = null;
        tip.classList.remove('is-visible');
    }

    function tipTarget(node) {
        return node && node.closest ? node.closest('[data-tip]') : null;
    }

    var touching = false;

    document.addEventListener('mouseover', function (e) {
        if (touching) { return; }
        var t = tipTarget(e.target);
        if (!t || t === current) { return; }
        clearTimeout(showTimer);
        showTimer = setTimeout(function () { show(t); }, 120);
    });
    document.addEventListener('mouseout', function (e) {
        var t = tipTarget(e.target);
        if (!t) { return; }
        if (e.relatedTarget && t.contains(e.relatedTarget)) { return; }
        hide();
    });
    document.addEventListener('focusin', function (e) {
        var t = tipTarget(e.target);
        // Solo con teclado: evita que aparezca tras un clic de ratón
        if (t && e.target.matches && e.target.matches(':focus-visible')) { show(t); }
    });
    document.addEventListener('focusout', hide);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { hide(); } });
    window.addEventListener('scroll', hide, { passive: true });
    window.addEventListener('resize', hide);

    /* ---------- Táctil: mantener pulsado para ver la descripción ---------- */
    var pressTimer = null;
    var longPressed = false;
    var autoHide = null;

    document.addEventListener('touchstart', function (e) {
        touching = true;
        longPressed = false;
        var t = tipTarget(e.target);
        clearTimeout(pressTimer);
        if (!t) { hide(); return; }
        pressTimer = setTimeout(function () {
            longPressed = true;
            show(t);
            clearTimeout(autoHide);
            autoHide = setTimeout(hide, 3200);
            if (navigator.vibrate) { try { navigator.vibrate(8); } catch (err) { /* sin vibración */ } }
        }, 420);
    }, { passive: true });

    function endTouch() { clearTimeout(pressTimer); }
    document.addEventListener('touchend', endTouch, { passive: true });
    document.addEventListener('touchmove', endTouch, { passive: true });
    document.addEventListener('touchcancel', endTouch, { passive: true });

    // Si hubo pulsación larga, no ejecuta la acción del botón al soltar
    document.addEventListener('click', function (e) {
        if (longPressed) {
            longPressed = false;
            e.preventDefault();
            e.stopPropagation();
        }
    }, true);
    document.addEventListener('contextmenu', function (e) {
        if (touching && tipTarget(e.target)) { e.preventDefault(); }
    });
    document.addEventListener('mousemove', function () { touching = false; }, { passive: true });

    /* ---------- Respaldo de transición entre páginas ---------- */
    if (root.classList.contains('no-vt') && !reduceMotion) {
        document.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return; }
            var a = e.target.closest ? e.target.closest('a[href]') : null;
            if (!a) { return; }
            var href = a.getAttribute('href');
            if (!href || href.charAt(0) === '#' || a.target === '_blank' || a.hasAttribute('download')) { return; }
            if (/^(mailto:|tel:|javascript:)/i.test(href)) { return; }
            var url;
            try { url = new URL(a.href, location.href); } catch (err) { return; }
            if (url.origin !== location.origin) { return; }
            if (url.pathname === location.pathname && url.search === location.search) { return; }

            e.preventDefault();
            root.classList.add('nova-leaving');
            setTimeout(function () { location.href = url.href; }, 220);
        });
        // Al volver con el botón "atrás" (caché de página) se quita la salida
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) { root.classList.remove('nova-leaving'); }
        });
    }


    /* ---------- Reloj de fecha y hora actuales ----------
     * <span data-nova-clock data-server-ts="EPOCH_MS" data-tz="America/Bogota">
     * Parte de la hora del servidor (no del equipo) y avanza cada segundo. */
    function startClocks() {
        document.querySelectorAll('[data-nova-clock]').forEach(function (el) {
            var serverMs = parseInt(el.getAttribute('data-server-ts'), 10);
            var tz = el.getAttribute('data-tz') || 'America/Bogota';
            if (!serverMs) { return; }
            var t0 = performance.now();
            var fDate = new Intl.DateTimeFormat('es-CO', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: tz });
            var fTime = new Intl.DateTimeFormat('es-CO', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true, timeZone: tz });
            var dEl = el.querySelector('[data-clock-date]');
            var tEl = el.querySelector('[data-clock-time]');
            function tick() {
                var now = new Date(serverMs + (performance.now() - t0));
                if (dEl) { dEl.textContent = fDate.format(now); }
                if (tEl) { tEl.textContent = fTime.format(now); }
            }
            tick();
            setInterval(tick, 1000);
        });
    }

    prepareTips();
    startClocks();
})();
