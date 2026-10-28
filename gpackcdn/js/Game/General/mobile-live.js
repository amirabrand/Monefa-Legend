/* ============================================================
   T26 — Viewport & native-mobile activator (monefa.net)
   The game ships a full mobile layout (body.mobileOptimized +
   ~90 @media 620px blocks) that was dormant because:
     a) no viewport meta existed, and
     b) Travian.Game.Layout.MobileOptimizations was undefined.
   This helper:
     1) picks the right viewport canvas:
          phone (min(screen w/h) <= 620)  -> width=620  + native UI
          tablet                          -> width=1280  (scaled desktop)
          desktop                         -> width=1280 (ignored by PC)
     2) toggles body.mobileOptimized and keeps the Travian
        config object in sync
   Idempotent; also runs on orientationchange/resize.
   ============================================================ */
(function () {
  if (window.__t26live) return;
  window.__t26live = true;

  function isMobile() {
    /* orientation-independent: a device whose smaller screen side
       is <= 620 CSS px is a phone -> native mobile UI (both
       orientations). Tablets/desktops have min side > 620. */
    var s = window.screen || {};
    var sw = s.width || 0, sh = s.height || 0;
    var eff = (sw && sh) ? Math.min(sw, sh) : 0;
    if (!eff || eff >= 9999) {
      eff = document.documentElement.clientWidth || window.innerWidth || 9999;
    }
    return eff <= 620;
  }

  function applyMode() {
    var b = document.body;
    if (!b) return;
    var mob = isMobile();

    /* 1) viewport canvas */
    var vpm = document.querySelector('meta[name="viewport"]');
    if (vpm) {
      var want = mob ? 'width=620, user-scalable=yes' : 'width=1280';
      if (vpm.getAttribute('content') !== want) vpm.setAttribute('content', want);
    }

    /* 2) body class + native config sync */
    try {
      var L = (window.Travian = window.Travian || {});
      L.Game = L.Game || {};
      L.Game.Layout = L.Game.Layout || {};
      L.Game.Layout.MobileOptimizations = L.Game.Layout.MobileOptimizations || {};
      var M = L.Game.Layout.MobileOptimizations;
      M.breakPoint = 620;
      M.enabled = mob;
      M.viewportSize = M.viewportSize || {};
      M.viewportSize.width = window.outerWidth || window.innerWidth;
      M.viewportSize.height = window.outerHeight || window.innerHeight;
    } catch (e) {}
    if (mob) b.classList.add('mobileOptimized');
    else b.classList.remove('mobileOptimized');
  }

  var timer = null;
  function schedule() {
    if (timer) return;
    timer = setTimeout(function () { timer = null; applyMode(); }, 120);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyMode);
  } else {
    applyMode();
  }
  window.addEventListener('resize', schedule);
  window.addEventListener('orientationchange', schedule);
  /* light re-sync for late layout changes */
  setInterval(applyMode, 3000);
})();
