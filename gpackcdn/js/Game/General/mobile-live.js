/* ============================================================
   T26/T29/T31 - Viewport & native-mobile activator (monefa.net)
   v21:
     - T40: karte.php map taps opened the wrong tile (or nothing at all).
       Two stacked faults: (1) the map reads its container's page position
       once at domready, the T31/T26 class+CSS flips then re-layout the
       page but webviews never fire resize, so the transition origin goes
       stale by the shift and every tap/tooltip mapped to a neighbour
       tile; (2) the T35 body.zoom makes event pageX/Y visual (zoomed)
       while the map's constants stay layout, so taps drifted further
       toward the bottom-right corner. Fix = a capture-phase shim that
       rewrites pageX/pageY (mouse + touch) to layout space before
       MooTools builds its Event (the whole event math becomes
       layout-native WITHOUT touching game state: render path intact,
       drag 1:1, tooltip follows the cursor again and no longer swallows
       the first tap), plus an applyMode-tick calibration that re-anchors
       the transition origin to the SERVER-TRUE tile range of the loaded
       map blocks (blocks[].mapCoordinates x block element rect) - the
       same source of truth the rulers render from. Verified: taps,
       tooltips and click dialogs land on the exact tile under the
       finger/cursor, before and after panning.
   v14:
     - T39: the construction bubble (.buildingList, the "ساخت فوری"
       + queue box) is position:fixed inside #resourceFieldContainer,
       and the game's mobile CSS scales that container with
       transform:scale(1.35). A fixed element inside a transformed
       ancestor uses the ancestor as its containing block -> the
       bubble anchored to the 330px map band mid-screen instead of
       the viewport, and under the v12 body zoom Chromium even
       painted it detached from its hit-rect (bubble jumped up-right,
       oversized, taps missed: "دکمه تایید زیر کادر میروند"). The
       game's own dialog overlay (fixed, child of <body>) renders
       fine, so applyMode now reparents the bubble to <body> every
       tick and publishes --t26z (the active body zoom) so the CSS
       can keep the bubble ~105 visual px above the bottom bar on
       zoomed webviews and at the game's own 105px elsewhere.
   v12:
     - T34: webviews that force their own initial scale (they ignore the
       meta initial-scale) rendered the 620 canvas at scale 1 and the
       user saw a cropped zoomed-in slice (mostly the green map). When
       the layout viewport is much wider than the visible viewport at
       scale ~1 on a touch device, the whole game is re-laid-out to the
       real visible width via document.body.style.zoom (Chromium).
   v11:
     - T33: the server-side decision (window.__t26php, from html.php:
       UA detection + sticky t26m cookie + Bale webview marker) is
       authoritative and can no longer be overwritten by heuristics,
     - T33: Bale / ble.ir webview UAs always get the mobile canvas
       (Bale desktop reports the monitor size, not the window),
     - T33: no-touch windows narrower than the canvas (Bale desktop
       webview) shrink-to-fit instead of cropping horizontally,
   v10:
     - explicit initial-scale everywhere (Chrome Android zoom-restore
       rendered the 620 canvas at scale 1.0 without it),
     - html.t26m class is now synced with body.mobileOptimized so the
       CSS fixes apply even when PHP classified the UA as desktop
       (Chrome "Desktop site" mode sends a desktop UA from phones),
     - screen-based fallback for lying UAs: touch + min(screen)<=620.
   Idempotent; also runs on orientationchange/resize.
   ============================================================ */
(function () {
  if (window.__t26live) return;
  window.__t26live = true;

  function effSide() {
    var s = window.screen || {};
    var sw = parseInt(s.width, 10) || 0, sh = parseInt(s.height, 10) || 0;
    var eff = (sw && sh) ? Math.min(sw, sh) : 0;
    if (!eff || eff >= 9999) {
      eff = document.documentElement.clientWidth || window.innerWidth || 9999;
    }
    return eff;
  }

  function isMobile() {
    /* manual overrides for support/debugging
       (localStorage t26force=1 forces the mobile UI,
        t26force=0 forces the desktop UI on this device). */
    try {
      var __f = window.localStorage.getItem('t26force');
      if (__f === '1') return true;
      if (__f === '0') return false;
    } catch (e) {}
    /* T33: the server-side decision (html.php stashes it in __t26php)
       is authoritative: UA detection + sticky t26m cookie + Bale mark. */
    var php = (typeof window.__t26php === 'number') ? window.__t26php : -1;
    if (php === 1) return true;
    if (php === 0) return false;
    var eff = effSide();
    /* T29: phones by UA - authoritative even when touch is not
       reported (in-app webviews, accessibility modes). */
    var ua = navigator.userAgent || '';
    var uaPhone = /Android|iPhone|iPod|Windows Phone|IEMobile/i.test(ua)
                  && !/iPad|Tablet|PlayBook|Silk/i.test(ua);
    if (uaPhone) return eff > 0 && eff <= 620;
    /* tablets & desktops keep the desktop canvas */
    if (/iPad|Tablet|PlayBook|Silk/i.test(ua)) return false;
    /* T33: Bale messenger webviews (mini-app) - always mobile; the
       desktop Bale client reports the monitor size, not the window. */
    if (/Bale|ble\.ir/i.test(ua)) return true;
    /* T31: UAs that lie (Chrome "Desktop site" on phones, some webviews):
       a touch device with a phone-sized screen gets the mobile UI, and any
       screen whose short side is <=480 is a phone even without touch. */
    if (eff > 0 && eff <= 480) return true;
    var touch = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
    if (!touch) return false;
    return eff <= 620;
  }

  function applyMode() {
    var b = document.body;
    if (!b) return;
    var mob = isMobile();
    var eff = effSide();

    /* 1) viewport canvas + explicit initial-scale */
    var vpm = document.querySelector('meta[name="viewport"]');
    if (vpm) {
      var cw = mob ? 620 : 1280;
      var sc = Math.min(1, eff / cw);
      if (!isFinite(sc) || sc <= 0) sc = 1;
      /* T33: no-touch windows narrower than the canvas (Bale desktop
         webview) shrink to fit instead of cropping horizontally. */
      if (mob && !(('ontouchstart' in window) || (navigator.maxTouchPoints > 0))) {
        var icw = document.documentElement.clientWidth || window.innerWidth || 0;
        if (icw > 200 && icw < cw) sc = Math.min(1, icw / cw);
      }
      var want = 'width=' + cw + ', initial-scale=' + sc.toFixed(4)
               + ', user-scalable=yes, viewport-fit=cover';
      if (vpm.getAttribute('content') !== want) vpm.setAttribute('content', want);
    }

    /* T35: universal auto-fit. The game renders on a fixed canvas (cw =
       620 in mobile mode, 1280 in desktop mode). Webviews that ignore
       meta initial-scale (Bale desktop AND mobile clients) or the whole
       meta viewport (desktop browsers) show that canvas cropped at
       scale ~1. Shrink the whole body with CSS zoom until the full
       canvas fits the visible width. No touch/mode gate: one rule for
       every client. Clients that DID apply our initial-scale render at
       scale != 1 and never enter this branch. */
    if (window.visualViewport) {
      var vv = window.visualViewport;
      var vwid = Math.round(vv.width) || 0;
      var gap = cw - vwid;
      var zoomed = parseFloat(document.body.style.zoom) || 0;
      var near1 = vv.scale > 0.98 && vv.scale < 1.02;
      if (vwid > 200 && gap > 40 && near1) {
        var z = +(vwid / cw).toFixed(4);
        if (Math.abs(zoomed - z) > 0.005) {
          document.body.style.zoom = z;
          document.documentElement.style.overflowX = 'hidden';
        }
        /* T39: publish the active zoom for CSS (bubble bottom calc) */
        document.documentElement.style.setProperty('--t26z', z);
      } else if (zoomed && near1 && gap <= 0) {
        /* environment renders our own scale again - drop the fallback */
        document.body.style.zoom = '';
        document.documentElement.style.overflowX = '';
        document.documentElement.style.setProperty('--t26z', '1');
      }
    }

    /* T39: the build bubble must not stay inside the scaled map
       container - see the v14 header note. Gated on mobileOptimized
       (set here, reliably) rather than mobileForced: crypt.js only
       flips mobileForced inside its resize handler, which webviews
       that never resize after load never fire. */
    try {
      if (b.classList.contains('mobileOptimized')) {
        var __bl = document.querySelector('div.buildingList');
        if (__bl && __bl.parentElement && __bl.parentElement !== b) {
          b.appendChild(__bl);
        }
      }
    } catch (e) {}

    /* T40: keep the map geometry in sync with the DOM (stale container
       position after the class/CSS flips) and install the layout-space
       event shim once (zoom page-space fix). Both no-ops elsewhere. */
    try { t40shimEvents(); } catch (e) {}
    try { t40healMapGeometry(); } catch (e) {}

    /* 2) html + body classes and native config sync */
    try { document.documentElement.classList.toggle('t26m', mob); } catch (e) {}
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

  /* ===== T40: karte.php map tap repair (see v16 header note) ===== */
  function t40activeZoom() {
    var z = parseFloat(document.body.style.zoom);
    return (z && isFinite(z) && z > 0.05 && Math.abs(z - 1) > 0.005) ? z : 1;
  }

  function t40isMapPage() {
    try {
      var cl = document.body.classList;
      return cl.contains('map') || cl.contains('positionDetails');
    } catch (e) { return false; }
  }

  function t40fixPage(obj) {
    if (!obj) return;
    var z = t40activeZoom();
    if (z === 1) return;
    try {
      if (typeof obj.clientX !== 'number') return;
      Object.defineProperty(obj, 'pageX', { value: obj.clientX / z, configurable: true });
      Object.defineProperty(obj, 'pageY', { value: obj.clientY / z, configurable: true });
    } catch (e) {}
  }

  function t40shimEvents() {
    if (window.__t40shim) return;
    window.__t40shim = true;
    var types = ['mousedown', 'mousemove', 'mouseup', 'mousewheel', 'wheel',
                 'touchstart', 'touchmove', 'touchend'];
    var fix = function (ev) {
      if (!ev || !t40isMapPage()) return;
      if (ev.type === 'mousedown' || ev.type === 'touchstart') {
        window.__t40lastAct = Date.now();
      }
      t40fixPage(ev);
      if (ev.touches && ev.touches[0]) t40fixPage(ev.touches[0]);
      if (ev.changedTouches && ev.changedTouches[0]) t40fixPage(ev.changedTouches[0]);
    };
    for (var i = 0; i < types.length; i++) {
      document.addEventListener(types[i], fix, true);
    }
  }

  function t40healMapGeometry() {
    if (!t40isMapPage()) return;
    try {
      var host = document.querySelector('#mapContainer');
      var mp = host && host._map;
      if (!mp || !mp.transition || typeof mp.positionChange !== 'function') return;
      var z = t40activeZoom();
      /* Desktop (zoom 1): the game's own geometry is already correct -
         just refresh it (idempotent, native). */
      if (z === 1) {
        mp.positionChange();
        return;
      }
      /* Zoomed webviews: the map init calibrated its transition origin
         before the T31/T26 class+CSS flips re-laid the page out, so the
         tap/tooltip math is stale by the layout shift. Re-anchor ONCE per
         page load from the one immutable server truth: karte.php is
         served centred on the X/Y the server put in the coordinate form
         (and the URL), and the map renders that tile at the container's
         visual centre. Solve the transition origin so the event math
         maps the centre pixel to exactly that tile. Deliberately never
         re-anchored afterwards: pans are tracked natively by the game's
         own relative move() and later block stamps follow the belief. */
      if (window.__t40cal) return;
      var now = Date.now();
      if (window.__t40born === undefined) window.__t40born = now;
      if (now - window.__t40born < 4000) return;
      if (window.__t40lastAct && now - window.__t40lastAct < 4000) return;
      var xi = document.querySelector('#xCoordInputMap');
      var yi = document.querySelector('#yCoordInputMap');
      if (!xi || !yi) return;
      var tx = parseInt(xi.value, 10);
      var ty = parseInt(yi.value, 10);
      if (!isFinite(tx) || !isFinite(ty)) return;
      var r = host.getBoundingClientRect();
      if (r.width < 100) return;
      mp.positionChange();
      var t = mp.transition;
      var cx = (r.left + r.width / 2) / z;
      var cy = (r.top + r.height / 2) / z;
      var ppt = t.pixelPerTile;
      var om = t.positionOrigin.map;
      var f = mp.blockSize.height / t.elementsPerBlock.y;
      /* tile = floor((page - ob)/ppt) + om  ->  centre pixel sits mid-bucket */
      t.positionOrigin.browser.x = cx - (tx - om.x + 0.5) * ppt.x;
      /* tile.y = om.y - floor((page - ob.y + f)/ppt) */
      t.positionOrigin.browser.y = cy + f - (om.y - ty + 0.5) * ppt.y;
      window.__t40cal = true;
    } catch (e) {}
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
