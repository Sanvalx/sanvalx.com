document.documentElement.classList.add('js');
if(document.body){
  document.body.classList.add('page-loaded');
  document.body.classList.remove('page-exit');
}
window.addEventListener('pageshow', function(){
  if(document.body){
    document.body.classList.add('page-loaded');
    document.body.classList.remove('page-exit');
  }
});

var isTouch =
  (window.matchMedia && window.matchMedia('(pointer: coarse)').matches) ||
  ('ontouchstart' in window) ||
  (navigator && navigator.maxTouchPoints && navigator.maxTouchPoints > 0);

// ========== MENU TOGGLE (GLOBAL) ==========
document.addEventListener('DOMContentLoaded', function(){
  var menuToggle = document.querySelector('.menu-toggle');
  var navMenu = document.querySelector('.nl');
  var navLinks = document.querySelectorAll('.nl a');
  var mobileBreakpoint = 992;

  // Estado seguro inicial para evitar pantallas bloqueadas/overlay pegado.
  document.body.style.overflow = '';
  if(navMenu){ navMenu.classList.remove('nav-active'); }
  if(menuToggle){ menuToggle.classList.remove('is-active'); }

  if(menuToggle && navMenu){
    menuToggle.setAttribute('aria-expanded', 'false');
    menuToggle.setAttribute('aria-controls', navMenu.id || 'main-menu');
    if(!navMenu.id){ navMenu.id = 'main-menu'; }

    function closeMenu(){
      menuToggle.classList.remove('is-active');
      navMenu.classList.remove('nav-active');
      document.body.style.overflow = '';
      menuToggle.setAttribute('aria-expanded', 'false');
    }

    menuToggle.addEventListener('click', function(){
      menuToggle.classList.toggle('is-active');
      navMenu.classList.toggle('nav-active');
      menuToggle.setAttribute('aria-expanded', String(navMenu.classList.contains('nav-active')));

      if(navMenu.classList.contains('nav-active')){
        document.body.style.overflow = 'hidden';
      }else{
        document.body.style.overflow = '';
      }
    });

    navLinks.forEach(function(link){
      link.addEventListener('click', function(){
        closeMenu();
      });
    });

    navMenu.addEventListener('click', function(e){
      if(e.target === navMenu){
        closeMenu();
      }
    });

    document.addEventListener('keydown', function(e){
      if(e.key === 'Escape' && navMenu.classList.contains('nav-active')){
        closeMenu();
      }
    });

    window.addEventListener('resize', function(){
      if(window.innerWidth > mobileBreakpoint){
        closeMenu();
      }
    });
  }

  document.querySelectorAll('.nav-dropdown').forEach(function(dropdown){
    var toggle = dropdown.querySelector('.nav-dropdown-toggle');
    if(!toggle){ return; }

    toggle.addEventListener('click', function(e){
      e.preventDefault();
      e.stopPropagation();
      var isOpen = dropdown.classList.contains('open');
      document.querySelectorAll('.nav-dropdown.open').forEach(function(d){
        if(d !== dropdown){ d.classList.remove('open'); }
      });
      dropdown.classList.toggle('open', !isOpen);
      toggle.setAttribute('aria-expanded', String(!isOpen));
    });

    dropdown.querySelectorAll('.nav-dropdown-menu a').forEach(function(link){
      link.addEventListener('click', function(){
        dropdown.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
        if(menuToggle && navMenu){ menuToggle.classList.remove('is-active'); navMenu.classList.remove('nav-active'); document.body.style.overflow = ''; }
      });
    });
  });

  document.addEventListener('click', function(e){
    if(!e.target.closest('.nav-dropdown')){
      document.querySelectorAll('.nav-dropdown.open').forEach(function(d){
        d.classList.remove('open');
        var t = d.querySelector('.nav-dropdown-toggle');
        if(t){ t.setAttribute('aria-expanded', 'false'); }
      });
    }
  });
});

// ========== CURSOR ==========
var C = document.getElementById('C');
var F = document.getElementById('F');
if(C && F && !isTouch){
  var mx = window.innerWidth/2, my = window.innerHeight/2, fx = mx, fy = my;
  document.addEventListener('mousemove', function(e){
    mx = e.clientX; my = e.clientY;
    C.style.left = mx + 'px';
    C.style.top = my + 'px';
  });
  (function loop(){
    fx += (mx-fx)*.1; fy += (my-fy)*.1;
    F.style.left = fx + 'px';
    F.style.top = fy + 'px';
    requestAnimationFrame(loop);
  })();
  document.querySelectorAll('a,button,input,select,textarea').forEach(function(el){
    el.addEventListener('mouseenter', function(){C.style.width='16px';C.style.height='16px';F.style.width='48px';F.style.height='48px';});
    el.addEventListener('mouseleave', function(){C.style.width='8px';C.style.height='8px';F.style.width='36px';F.style.height='36px';});
  });
}

// ========== PARTICLES (INDEX ONLY) ==========
var canvas = document.getElementById('particles');
if (canvas) {
  var ctx = canvas.getContext('2d');
  var W, H, parts = [], mouseP = { x: -999, y: -999 };
  var canvasRectCache = null;
  var canvasRectStale = true;
  function markCanvasRectStale() {
    canvasRectStale = true;
  }
  function readCanvasRect() {
    if (canvasRectStale || !canvasRectCache) {
      canvasRectCache = canvas.getBoundingClientRect();
      canvasRectStale = false;
    }
    return canvasRectCache;
  }
  function resizeC() {
    W = canvas.width = canvas.offsetWidth;
    H = canvas.height = canvas.offsetHeight;
    markCanvasRectStale();
  }
  function initP() {
    parts = [];
    var density = isTouch ? 12000 : 8000;
    var n = Math.floor((W * H) / density);
    for (var i = 0; i < n; i++) parts.push(new Pt());
  }
  window.addEventListener('resize', function () {
    resizeC();
    initP();
  });
  window.addEventListener('scroll', markCanvasRectStale, { passive: true });
  document.addEventListener(
    'mousemove',
    function (e) {
      var r = readCanvasRect();
      mouseP.x = e.clientX - r.left;
      mouseP.y = e.clientY - r.top;
    },
    { passive: true }
  );
  function Pt() {
    this.x = Math.random() * W;
    this.y = Math.random() * H;
    this.vx = (Math.random() - 0.5) * 0.42;
    this.vy = (Math.random() - 0.5) * 0.42;
    this.r = Math.random() * 1.7 + 0.4;
    this.a = Math.random() * 0.4 + 0.07;
    this.ba = this.a;
  }
  function drawP() {
    ctx.clearRect(0, 0, W, H);
    for (var i = 0; i < parts.length; i++) {
      var p = parts[i];
      p.x += p.vx;
      p.y += p.vy;
      if (p.x < 0) p.x = W;
      if (p.x > W) p.x = 0;
      if (p.y < 0) p.y = H;
      if (p.y > H) p.y = 0;
      var dx = p.x - mouseP.x,
        dy = p.y - mouseP.y,
        d = Math.sqrt(dx * dx + dy * dy);
      if (d < 140 && d > 1e-6) {
        var f = (140 - d) / 140;
        p.x += (dx / d) * f * 2.4;
        p.y += (dy / d) * f * 2.4;
        p.a = Math.min(0.85, p.ba + f * 0.65);
      } else {
        p.a += (p.ba - p.a) * 0.04;
      }
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(200,240,74,' + p.a + ')';
      ctx.fill();
      for (var j = i + 1; j < parts.length; j++) {
        var q = parts[j],
          ex = p.x - q.x,
          ey = p.y - q.y,
          ed = Math.sqrt(ex * ex + ey * ey);
        if (ed < 120) {
          ctx.beginPath();
          ctx.moveTo(p.x, p.y);
          ctx.lineTo(q.x, q.y);
          ctx.strokeStyle = 'rgba(200,240,74,' + (1 - ed / 120) * 0.09 + ')';
          ctx.lineWidth = 0.5;
          ctx.stroke();
        }
      }
    }
    requestAnimationFrame(drawP);
  }
  requestAnimationFrame(function () {
    resizeC();
    initP();
    drawP();
  });
}

// ========== HERO GLOW FOLLOW (INDEX) ==========
// Actualiza variables CSS para que el glow del hero siga el cursor (parallax ligero + barrido).
(function(){
  var heroEl = document.querySelector('.hero');
  if(!heroEl) return;
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if(reduceMotion) return;

  var rect = null;
  var lastX = 0, lastY = 0;
  var rafId = 0;

  function refreshRect(){
    rect = heroEl.getBoundingClientRect();
    if(!rect || rect.width === 0 || rect.height === 0) return;
  }

  function clamp(v,min,max){return Math.max(min,Math.min(max,v));}

  function setFromPoint(x,y){
    if(!rect) refreshRect();
    if(!rect) return;

    var cx = clamp(x - rect.left, 0, rect.width);
    var cy = clamp(y - rect.top, 0, rect.height);

    var mx = (rect.width ? (cx/rect.width*100) : 50);
    var my = (rect.height ? (cy/rect.height*100) : 50);

    // Posición del glow en px para transform (parallax suave).
    var dx = (cx - rect.width/2) / (rect.width/2 || 1);
    var dy = (cy - rect.height/2) / (rect.height/2 || 1);

    heroEl.style.setProperty('--mx', mx.toFixed(2) + '%');
    heroEl.style.setProperty('--my', my.toFixed(2) + '%');
    heroEl.style.setProperty('--px', (dx*18).toFixed(2) + 'px');
    heroEl.style.setProperty('--py', (dy*10).toFixed(2) + 'px');
  }

  function tick(){
    rafId = 0;
    setFromPoint(lastX, lastY);
  }

  // Inicialización al centro (evita que salga “a la esquina”).
  refreshRect();
  if(rect){
    lastX = rect.left + rect.width/2;
    lastY = rect.top + rect.height/2;
    setFromPoint(lastX, lastY);
  }else{
    lastX = window.innerWidth/2;
    lastY = window.innerHeight/2;
  }

  document.addEventListener('pointermove', function(e){
    lastX = e.clientX;
    lastY = e.clientY;
    if(!rafId) rafId = requestAnimationFrame(tick);
  }, {passive:true});

  window.addEventListener('resize', function(){
    refreshRect();
    if(rect){
      lastX = rect.left + rect.width/2;
      lastY = rect.top + rect.height/2;
      setFromPoint(lastX, lastY);
    }
  });
  window.addEventListener('scroll', function(){ rect = null; }, {passive: true});
})();

// ========== INDEX INTRO SEQUENCE ==========
function easeOut(t){return 1-Math.pow(1-t,3);}
function animate(el,prop,from,to,dur,delay,cb){
  setTimeout(function(){
    var start=performance.now();
    function step(now){
      var t=Math.min(1,(now-start)/dur),v=from+(to-from)*easeOut(t);
      if(prop==='opacity')el.style.opacity=v;
      else if(prop==='translateY')el.style.transform='translateY('+v+'px)';
      if(t<1)requestAnimationFrame(step);else if(cb)cb();
    }
    requestAnimationFrame(step);
  },delay);
}
function countTo(el,target,dur,delay){
  setTimeout(function(){
    var start=performance.now();
    function step(now){
      var t=Math.min(1,(now-start)/dur);
      el.textContent=Math.round(easeOut(t)*target);
      if(t<1)requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  },delay);
}
var introEl=document.getElementById('intro');
var introLogo=document.getElementById('intro-logo');
var introBar=document.getElementById('intro-bar');
var introKey='sanvalx_intro_seen';
function hasIntroSeen(){
  try{ if(sessionStorage.getItem(introKey)==='1') return true; }catch(err){}
  try{ if(localStorage.getItem(introKey)==='1') return true; }catch(err){}
  try{ if(window.name.indexOf('__sanvalx_intro_seen__')!==-1) return true; }catch(err){}
  return document.cookie.indexOf('sanvalx_intro_seen=1')!==-1;
}
function markIntroSeen(){
  try{sessionStorage.setItem(introKey,'1');}catch(err){}
  try{localStorage.setItem(introKey,'1');}catch(err){}
  try{
    if(window.name.indexOf('__sanvalx_intro_seen__')===-1){
      window.name += '__sanvalx_intro_seen__';
    }
  }catch(err){}
  document.cookie='sanvalx_intro_seen=1; path=/; max-age=31536000; SameSite=Lax';
}
var shouldPlayIntro=!hasIntroSeen();
if(introEl && introLogo && introBar && shouldPlayIntro){
  markIntroSeen();
  animate(introLogo,'opacity',0,1,500,200);
  setTimeout(function(){introBar.style.width='100%';},400);
  setTimeout(function(){
    introLogo.style.transition='color .05s';
    [function(){introLogo.style.transform='translate(-3px,1px) skewX(2deg)';introLogo.style.color='#c8f04a';},
     function(){introLogo.style.transform='translate(3px,-1px) skewX(-1deg)';introLogo.style.color='#fff';},
     function(){introLogo.style.transform='translate(-2px,2px)';introLogo.style.color='#c8f04a';},
     function(){introLogo.style.transform='none';introLogo.style.color='';}]
    .forEach(function(fn,i){setTimeout(fn,i*60);});
  },1400);
  setTimeout(function(){introLogo.style.transition='transform .5s cubic-bezier(.4,0,.2,1),opacity .5s';introLogo.style.transform='scale(1.6)';introLogo.style.opacity='0';},1700);
  setTimeout(function(){introEl.style.transition='opacity .4s';introEl.style.opacity='0';setTimeout(function(){introEl.style.display='none';},400);},2000);
  var T0=2100;
  setTimeout(function(){var n=document.getElementById('nav');if(n)n.classList.add('visible');},T0);
  setTimeout(function(){var b=document.getElementById('badge');if(b){b.style.transition='opacity .55s ease,transform .55s ease';b.style.opacity='1';b.style.transform='translateY(0)';}},T0+100);
  ['hl1','hl2','hl3'].forEach(function(id,i){setTimeout(function(){var el=document.getElementById(id);if(el){el.style.transition='transform .8s cubic-bezier(.16,1,.3,1)';el.style.transform='translateY(0)';}},T0+220+i*160);});
  setTimeout(function(){var hf=document.getElementById('hfoot');if(hf){hf.style.transition='opacity .6s ease,transform .6s ease';hf.style.opacity='1';hf.style.transform='translateY(0)';}},T0+720);
  setTimeout(function(){var c1=document.getElementById('hcard1');if(c1)c1.classList.add('show');},T0+900);
  setTimeout(function(){var c2=document.getElementById('hcard2');if(c2)c2.classList.add('show');},T0+1100);
  var cnt1=document.getElementById('cnt1');if(cnt1)countTo(cnt1,10,1000,T0+900);
  setTimeout(function(){var si=document.getElementById('scroll-ind');if(si){si.style.transition='opacity .5s';si.style.opacity='1';}},T0+1200);
}else{
  if(introEl){introEl.style.display='none';}
  var n=document.getElementById('nav');if(n)n.classList.add('visible');
  var b=document.getElementById('badge');if(b){b.style.opacity='1';b.style.transform='translateY(0)';}
  ['hl1','hl2','hl3'].forEach(function(id){var el=document.getElementById(id);if(el){el.style.transform='translateY(0)';}});
  var hf=document.getElementById('hfoot');if(hf){hf.style.opacity='1';hf.style.transform='translateY(0)';}
  var c1=document.getElementById('hcard1');if(c1)c1.classList.add('show');
  var c2=document.getElementById('hcard2');if(c2)c2.classList.add('show');
  var cnt1=document.getElementById('cnt1');if(cnt1)cnt1.textContent='10';
  var si=document.getElementById('scroll-ind');if(si)si.style.opacity='1';
  markIntroSeen();
}

// ========== NAV SCROLL ==========
var navEl = document.getElementById('nav');
if(navEl){
  window.addEventListener('scroll', function(){navEl.classList.toggle('solid',window.scrollY>40);});
}

// ========== SCROLL REVEAL (todas las páginas) ==========
function initScrollReveal(){
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var skipWithin = '#nav, footer, #cookie-banner, #intro, .hero .h1hero, .hero .hfoot';

  function shouldSkip(el){
    if(!el || el.closest(skipWithin)) return true;
    return false;
  }

  var selectorList = [
    '.r',
    'section.sec',
    'body.servicio-page section.sec',
    'section.proc',
    'section.testi',
    'section.cta',
    'section.page-cta',
    'section.mq',
    'section.about',
    'main.metodo-wrap > *',
    '.methodology-timeline .timeline-item',
    '.stats .st',
    '.svc .sc',
    '.pg .pi',
    '.pg2 .pi2',
    '.tg2 .tc',
    '.servicios-hub-grid .servicio-card',
    '.servicio-body',
    '.servicio-intro',
    '.servicio-kicker',
    'h1.ttl',
    'h2.ttl',
    '.lbl',
    '.metodo-title',
    '.metodo-sub',
    '.metodo-kicker',
    '.form-shell',
    '.thanks-card',
    '.pol-wrap > h1',
    '.pol-wrap > h2',
    '.pol-wrap > p',
    '.pol-wrap > ul',
    '.pol-wrap > table',
    '.page-cta .r',
    '.page-cta > div'
  ];

  var seen = new Set();
  var elements = [];
  selectorList.forEach(function(sel){
    document.querySelectorAll(sel).forEach(function(el){
      if(seen.has(el) || shouldSkip(el)) return;
      seen.add(el);
      el.classList.add('reveal-up');
      elements.push(el);
    });
  });

  function markVisible(el){
    el.classList.add('is-visible');
    if(el.classList.contains('r')) el.classList.add('on');
  }

  if(reduceMotion || !('IntersectionObserver' in window)){
    elements.forEach(markVisible);
    return;
  }

  var observer = new IntersectionObserver(function(entries){
    entries.forEach(function(entry){
      if(!entry.isIntersecting) return;
      var el = entry.target;
      var delay = parseInt(el.getAttribute('data-reveal-delay') || '0', 10);
      setTimeout(function(){ markVisible(el); }, delay);
      observer.unobserve(el);
    });
  }, { root: null, rootMargin: '0px 0px -10% 0px', threshold: 0.12 });

  elements.forEach(function(el){
    var parent = el.parentElement;
    if(parent){
      var siblings = Array.prototype.filter.call(parent.children, function(c){
        return c.classList && c.classList.contains('reveal-up');
      });
      var idx = siblings.indexOf(el);
      if(idx > 0 && idx < 8) el.setAttribute('data-reveal-delay', String(idx * 65));
    }
    observer.observe(el);
  });

  requestAnimationFrame(function(){
    elements.forEach(function(el){
      var rect = el.getBoundingClientRect();
      if(rect.top < window.innerHeight * 0.9 && rect.bottom > 0) markVisible(el);
    });
  });

  setTimeout(function(){
    document.querySelectorAll('.reveal-up:not(.is-visible), .r:not(.is-visible)').forEach(markVisible);
  }, 1200);
}

document.addEventListener('DOMContentLoaded', function(){
  initScrollReveal();
});

document.addEventListener('DOMContentLoaded', () => {
  if (document.body.classList.contains('contacto-page')) {
    var params = new URLSearchParams(window.location.search);
    var code = params.get('e');
    if (code) {
      var messages = {
        validacion: 'Revisa los campos obligatorios (marcados con asterisco) y vuelve a enviar.',
        servidor: 'No pudimos registrar tu solicitud. Inténtalo en unos minutos o escríbenos por WhatsApp.',
        privacidad: 'Debes aceptar la política de privacidad para continuar.',
        config: 'El formulario no está disponible temporalmente. Por favor contacta por otro canal.',
        limite: 'Has enviado varias solicitudes seguidas. Espera un momento o escríbenos por WhatsApp.'
      };
      var text = messages[code] || messages.servidor;
      var banner = document.createElement('div');
      banner.className = 'form-error-banner';
      banner.setAttribute('role', 'alert');
      banner.textContent = text;
      var shell = document.querySelector('.form-shell');
      if (shell) {
        shell.insertBefore(banner, shell.firstChild);
      }
      try {
        window.history.replaceState({}, '', '/contacto.html');
      } catch (err) {}
    }
    if (window.location.hash === '#formulario-contacto') {
      window.setTimeout(function () {
        var formBlock = document.getElementById('formulario-contacto');
        if (formBlock) {
          formBlock.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }, 650);
    }
  }

  // 1. Page Load Transition
  requestAnimationFrame(() => {
    document.body.classList.add('page-loaded');
    document.body.classList.remove('page-exit');
  });

  // 2. Interceptar enlaces para salida fluida (Fade-out antes de navegar)
  const links = document.querySelectorAll(
    'a[href]:not([target="_blank"]):not([href^="#"]):not([href^="mailto:"]):not([href^="tel:"]):not(.cta-btn):not(.bpri):not(form a)'
  );
  links.forEach(link => {
    link.addEventListener('click', (e) => {
      if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
        return;
      }
      let targetUrl;
      try {
        targetUrl = new URL(link.href, window.location.href).href;
      } catch (err) {
        return;
      }
      if (window.location.protocol === 'file:') {
        return;
      }
      e.preventDefault();
      document.body.classList.add('page-exit');
      setTimeout(() => {
        window.location.href = targetUrl;
      }, 500);
    });
  });

  setTimeout(function(){
    document.body.classList.add('page-loaded');
  }, 900);
});
