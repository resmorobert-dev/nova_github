(function () {
    'use strict';

    const EXIT_MS = 320;
    const overlay = document.createElement('div');
    overlay.className = 'page-transition-overlay';
    document.body.appendChild(overlay);

    let leaving = false;

    function shouldHandleLink(link) {
        if (!link || leaving) return false;
        if (link.target === '_blank' || link.hasAttribute('download')) return false;
        if (link.dataset.noTransition === 'true') return false;
        if (link.getAttribute('aria-disabled') === 'true') return false;

        const rawHref = link.getAttribute('href');
        if (!rawHref || rawHref.startsWith('#')) return false;
        if (rawHref.startsWith('javascript:')) return false;
        if (/^(mailto:|tel:|data:|blob:)/i.test(rawHref)) return false;

        try {
            const url = new URL(link.href, window.location.href);
            if (url.origin !== window.location.origin) return false;
            if (url.href === window.location.href) return false;
        } catch (error) {
            return false;
        }

        return true;
    }

    function startExit(destination) {
        leaving = true;
        overlay.classList.add('is-active');
        document.body.classList.add('page-exit');

        window.setTimeout(function () {
            window.location.href = destination;
        }, EXIT_MS);
    }

    document.addEventListener('click', function (event) {
        const link = event.target.closest('a');
        if (!shouldHandleLink(link)) return;

        // Dejar que modificadores del navegador funcionen normalmente.
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        if (event.button !== 0) return;

        event.preventDefault();
        startExit(link.href);
    });

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || leaving) return;
        if (form.dataset.noTransition === 'true') return;

        leaving = true;
        overlay.classList.add('is-active');
        document.body.classList.add('page-exit');

        // Dejamos que el submit normal continúe inmediatamente.
    });

    document.addEventListener('pageshow', function () {
        leaving = false;
        overlay.classList.remove('is-active');
        document.body.classList.remove('page-exit');
    });
})();
