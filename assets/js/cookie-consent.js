/**
 * Consentimiento cookies SANVALX.
 * Rellena los IDs abajo o define window.SANVALX_COOKIE_CONFIG antes de este script.
 */
(function () {
  var STORAGE_KEY = 'sanvalx_cookies';

  var SANVALX_DEFAULTS = {
    clarityId: '',
    gaMeasurementId: '',
    metaPixelId: ''
  };

  function cfg() {
    var w = window.SANVALX_COOKIE_CONFIG || {};
    return {
      clarityId: w.clarityId || SANVALX_DEFAULTS.clarityId,
      gaMeasurementId: w.gaMeasurementId || SANVALX_DEFAULTS.gaMeasurementId,
      metaPixelId: w.metaPixelId || SANVALX_DEFAULTS.metaPixelId
    };
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
    link.href = '/assets/css/cookie-banner.css';
    document.head.appendChild(link);
  }

  function applyAccepted() {
    var c = cfg();
    injectClarity(c.clarityId);
    injectGA(c.gaMeasurementId);
    injectMetaPixel(c.metaPixelId);
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
      '<p>Usamos cookies de <strong>Google Analytics</strong>, <strong>Meta Pixel</strong> y, si aceptas, <strong>Microsoft Clarity</strong> (mapas de calor y sesiones) para mejorar el sitio y medir campañas. Consulta la <a href="/politicas/cookies.html">Política de Cookies</a> y la <a href="/politicas/privacidad.html">Política de Privacidad</a>.</p>' +
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
