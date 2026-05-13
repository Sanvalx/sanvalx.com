/**
 * Consentimiento cookies SANVALX
 *
 * RECOMENDADO — Google Tag Manager (mide todo desde un solo sitio):
 *   SANVALX_DEFAULTS.gtmId = 'GTM-XXXXXXX';
 * En tagmanager.google.com añade etiquetas: GA4, Google Ads, Meta Pixel,
 * LinkedIn Insight, Microsoft Clarity, etc.
 *
 * ALTERNATIVA — sin GTM (solo si dejas gtmId vacío):
 *   clarityId, gaMeasurementId, metaPixelId
 *
 * También puedes usar window.SANVALX_COOKIE_CONFIG antes de cargar este script.
 */
(function () {
  var STORAGE_KEY = 'sanvalx_cookies';

  var SANVALX_DEFAULTS = {
    /** Contenedor GTM: GA4, Ads, Meta, LinkedIn, Clarity… se configuran dentro de GTM */
    gtmId: 'GTM-NML9CVWK',
    clarityId: '',
    gaMeasurementId: '',
    metaPixelId: ''
  };

  function cfg() {
    var w = window.SANVALX_COOKIE_CONFIG || {};
    return {
      gtmId: w.gtmId || SANVALX_DEFAULTS.gtmId,
      clarityId: w.clarityId || SANVALX_DEFAULTS.clarityId,
      gaMeasurementId: w.gaMeasurementId || SANVALX_DEFAULTS.gaMeasurementId,
      metaPixelId: w.metaPixelId || SANVALX_DEFAULTS.metaPixelId
    };
  }

  function injectGTM(containerId) {
    if (!containerId || typeof containerId !== 'string') return;
    var id = containerId.replace(/^\s+|\s+$/g, '');
    if (!id.toUpperCase().startsWith('GTM-')) return;

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      event: 'sanvalx_cookie_consent',
      sanvalx_consent: 'granted',
      marketing_consent: 'granted',
      analytics_consent: 'granted'
    });
    window.dataLayer.push({
      'gtm.start': new Date().getTime(),
      event: 'gtm.js'
    });

    var j = document.createElement('script');
    j.async = true;
    j.src = 'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(id);
    var first = document.getElementsByTagName('script')[0];
    first.parentNode.insertBefore(j, first);
  }

  function injectClarity(projectId) {
    if (!projectId) return;
    (function (c, l, a, r, i, t, y) {
      c[a] =
        c[a] ||
        function () {
          (c[a].q = c[a].q || []).push(arguments);
        };
      t = l.createElement(r);
      t.async = 1;
      t.src = 'https://www.clarity.ms/tag/' + i;
      y = l.getElementsByTagName(r)[0];
      y.parentNode.insertBefore(t, y);
    })(window, document, 'clarity', 'script', projectId);
  }

  function injectGA(measurementId) {
    if (!measurementId) return;
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(measurementId);
    document.head.appendChild(s);
    window.dataLayer = window.dataLayer || [];
    window.gtag = function () {
      window.dataLayer.push(arguments);
    };
    window.gtag('js', new Date());
    window.gtag('config', measurementId);
  }

  function injectMetaPixel(pixelId) {
    if (!pixelId) return;
    !(function (f, b, e, v, n, t, s) {
      if (f.fbq) return;
      n = f.fbq = function () {
        n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
      };
      if (!f._fbq) f._fbq = n;
      n.push = n;
      n.loaded = !0;
      n.version = '2.0';
      n.queue = [];
      t = b.createElement(e);
      t.async = !0;
      t.src = v;
      s = b.getElementsByTagName(e)[0];
      s.parentNode.insertBefore(t, s);
    })(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
    window.fbq('init', pixelId);
    window.fbq('track', 'PageView');
  }

  function loadStylesheet() {
    if (document.getElementById('sanvalx-cookie-banner-css')) return;
    var link = document.createElement('link');
    link.id = 'sanvalx-cookie-banner-css';
    link.rel = 'stylesheet';
    link.href = '/assets/css/cookie-banner.css?v=20260517';
    document.head.appendChild(link);
  }

  function applyAccepted() {
    var c = cfg();
    if (c.gtmId) {
      injectGTM(c.gtmId);
    } else {
      injectClarity(c.clarityId);
      injectGA(c.gaMeasurementId);
      injectMetaPixel(c.metaPixelId);
    }
    try {
      window.dispatchEvent(new CustomEvent('sanvalx:cookieConsent', { detail: { status: 'accepted' } }));
    } catch (e) {}
  }

  function hideBanner() {
    var el = document.getElementById('cookie-banner');
    if (el) el.style.display = 'none';
  }

  function acceptCookies() {
    try {
      localStorage.setItem(STORAGE_KEY, 'accepted');
    } catch (e) {}
    hideBanner();
    applyAccepted();
  }

  function rejectCookies() {
    try {
      localStorage.setItem(STORAGE_KEY, 'rejected');
    } catch (e) {}
    hideBanner();
    try {
      window.dispatchEvent(new CustomEvent('sanvalx:cookieConsent', { detail: { status: 'rejected' } }));
    } catch (e) {}
  }

  window.sanvalxAcceptCookies = acceptCookies;
  window.sanvalxRejectCookies = rejectCookies;

  function showBanner() {
    loadStylesheet();
    var wrap = document.createElement('div');
    wrap.id = 'cookie-banner';
    wrap.innerHTML =
      '<p>Con tu consentimiento cargamos <strong>Google Tag Manager</strong> para medir el uso del sitio (p. ej. <strong>Analytics</strong>, <strong>Google Ads</strong>, <strong>Meta</strong>, <strong>LinkedIn</strong>, mapas de calor, etc. según lo configures). Consulta la <a href="/politicas/cookies.html">Política de Cookies</a> y la <a href="/politicas/privacidad.html">Política de Privacidad</a>.</p>' +
      '<div class="cookie-btns">' +
      '<button type="button" class="cookie-btn-reject" id="cookie-reject">Rechazar</button>' +
      '<button type="button" class="cookie-btn-accept" id="cookie-accept">Aceptar</button>' +
      '</div>';
    document.body.appendChild(wrap);
    document.getElementById('cookie-accept').addEventListener('click', acceptCookies);
    document.getElementById('cookie-reject').addEventListener('click', rejectCookies);
  }

  function init() {
    var v;
    try {
      v = localStorage.getItem(STORAGE_KEY);
    } catch (e) {
      v = null;
    }
    if (v === 'accepted') {
      applyAccepted();
      return;
    }
    if (v === 'rejected') {
      return;
    }
    showBanner();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
