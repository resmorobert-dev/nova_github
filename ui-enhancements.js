/**
 * Interacciones de Usuario: Transición Diagonal Tipo Slash y Descripciones Emergentes (Tooltips)
 * Proyecto: Colegio NOVA — Institución Educativa Los Andes
 */

(function () {
  'use strict';

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // 1. Inicializar Cortinilla Diagonal (Slash Transition)
  let curtain = null;
  if (!prefersReducedMotion) {
    curtain = document.createElement('div');
    curtain.className = 'slash-curtain';
    curtain.setAttribute('aria-hidden', 'true');
    document.body.appendChild(curtain);
  }

  // Interceptar enlaces internos seguros sin bloquear descargas, formularios ni pestañas nuevas
  document.addEventListener('click', function (e) {
    if (prefersReducedMotion || !curtain) return;

    const link = e.target.closest('a[href]');
    if (!link) return;

    const href = link.getAttribute('href');
    if (
      !href ||
      href.startsWith('#') ||
      href.startsWith('javascript:') ||
      link.hasAttribute('download') ||
      link.getAttribute('target') === '_blank' ||
      link.dataset.noSlash === 'true' ||
      href.includes('exportar.php') ||
      e.ctrlKey || e.metaKey || e.shiftKey || e.altKey
    ) {
      return;
    }

    // Verificar que sea navegación interna del mismo origen
    try {
      const targetUrl = new URL(href, window.location.href);
      if (targetUrl.origin !== window.location.origin) return;

      e.preventDefault();
      curtain.classList.add('active');

      setTimeout(function () {
        window.location.href = targetUrl.href;
      }, 360);
    } catch (_) {
      // Si falla el parseo de URL, permitir navegación nativa inmediata
    }
  });

  // Limpiar cortinilla si se restaura desde bfcache
  window.addEventListener('pageshow', function () {
    if (curtain) {
      curtain.classList.remove('active');
    }
  });

  // 2. Sistema de Tooltips Accesibles (data-tooltip)
  const tooltipEl = document.createElement('div');
  tooltipEl.className = 'nova-tooltip';
  tooltipEl.setAttribute('role', 'tooltip');
  document.body.appendChild(tooltipEl);

  function showTooltip(target) {
    const text = target.getAttribute('data-tooltip');
    if (!text) return;

    tooltipEl.textContent = text;
    tooltipEl.classList.add('visible');

    const rect = target.getBoundingClientRect();
    const tooltipRect = tooltipEl.getBoundingClientRect();

    let top = rect.bottom + 8;
    let left = rect.left + (rect.width - tooltipRect.width) / 2;

    if (top + tooltipRect.height > window.innerHeight - 10) {
      top = rect.top - tooltipRect.height - 8;
    }
    if (left < 10) left = 10;
    if (left + tooltipRect.width > window.innerWidth - 10) {
      left = window.innerWidth - tooltipRect.width - 10;
    }

    tooltipEl.style.top = top + 'px';
    tooltipEl.style.left = left + 'px';
  }

  function hideTooltip() {
    tooltipEl.classList.remove('visible');
  }

  document.addEventListener('mouseover', function (e) {
    const target = e.target.closest('[data-tooltip]');
    if (target) showTooltip(target);
  });

  document.addEventListener('mouseout', function (e) {
    const target = e.target.closest('[data-tooltip]');
    if (target) hideTooltip();
  });

  document.addEventListener('focusin', function (e) {
    const target = e.target.closest('[data-tooltip]');
    if (target) showTooltip(target);
  });

  document.addEventListener('focusout', function (e) {
    const target = e.target.closest('[data-tooltip]');
    if (target) hideTooltip();
  });
})();
