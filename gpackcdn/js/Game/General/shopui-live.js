/**
 * shopui-live.js — Monefa Legend payment wizard bootstrap (T25)
 * The original ShopUIV2 inline scripts never execute after the dialog HTML is
 * injected via AJAX (scripts inside Travian.Dialog content are not evaluated),
 * so package selection, slider arrows, tab switching and the buy button were dead.
 * This bootstrap re-implements the needed interactivity with plain JS + jQuery,
 * using a MutationObserver so it (re)activates whenever a wizard appears in the DOM.
 * Idempotent: elements get a __t25bound flag.
 */
(function () {
    if (window.__shopuiLiveLoaded) return;
    window.__shopuiLiveLoaded = true;

    function q(sel, ctx) { return (ctx || document).querySelector(sel); }
    function qa(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

    function bound(el) {
        if (!el || el.__t25bound) return true;
        el.__t25bound = true;
        return false;
    }

    /* ---------- package + payment-method sliders ---------- */
    function sliderFix(sliderSel, pageSel) {
        var slider = q(sliderSel);
        if (!slider || bound(slider)) return;
        var pages = qa(pageSel, slider);
        var inner = q('.slidingContentInner', slider);
        if (!pages.length || !inner) return;

        function pageW() { return pages[0].offsetWidth || 780; }
        function visibleIndex() {
            for (var i = 0; i < pages.length; i++) {
                if (pages[i].classList.contains('visible')) return i;
            }
            return 0;
        }
        function apply(anim) {
            var idx = visibleIndex();
            var off = -(idx * pageW());
            pages.forEach(function (p, i) {
                p.classList.toggle('visible', i === idx);
                p.classList.toggle('hidden', i !== idx);
            });
            var a1 = q('.slideArea.area1', slider), a2 = q('.slideArea.area2', slider);
            if (a1) a1.classList.toggle('inactive', idx <= 0);
            if (a2) a2.classList.toggle('inactive', idx >= pages.length - 1);
            if (anim) { jQuery(inner).stop(true).animate({ 'margin-right': off + 'px' }, 400); }
            else { inner.style.marginRight = off + 'px'; }
        }
        function go(delta) {
            if (pages.length < 2) return;
            var idx = visibleIndex() + delta;
            if (idx < 0) idx = 0;
            if (idx > pages.length - 1) idx = pages.length - 1;
            pages.forEach(function (p, i) {
                p.classList.toggle('visible', i === idx);
                p.classList.toggle('hidden', i !== idx);
            });
            apply(true);
        }
        var a1 = q('.slideArea.area1', slider), a2 = q('.slideArea.area2', slider);
        if (a1 && !bound(a1)) a1.addEventListener('click', function () { go(-1); });
        if (a2 && !bound(a2)) a2.addEventListener('click', function () { go(1); });
        apply(false);
    }

    /* ---------- package selection ---------- */
    function bindPackages() {
        qa('#packageSlider .package').forEach(function (card) {
            if (bound(card)) return;
            card.addEventListener('click', function (ev) {
                ev.preventDefault();
                qa('#packageSlider .package').forEach(function (p) { p.classList.remove('selected'); });
                card.classList.add('selected');
                // enable continue/buy buttons
                qa('#confirmBuy, #continueBuy, .buyButton').forEach(function (b) { b.classList.remove('disabled'); });
                // refresh result box
                var units = q('.goldUnits', card);
                var price = q('.price', card);
                var gNew = q('#goldBalanceNew');
                var gPay = q('#priceToPay');
                if (units && gNew) {
                    var bal = q('.accountBalance span');
                    var base = bal ? parseInt(bal.textContent.replace(/[^\d]/g, ''), 10) || 0 : 0;
                    gNew.textContent = (base + parseInt(units.textContent.replace(/[^\d]/g, ''), 10) || 0);
                }
                if (price && gPay) gPay.textContent = price.textContent;
                var ok = q('.resultBox .selectedPackageText, .selectedPackage');
                if (ok) ok.style.display = '';
            });
        });
    }

    /* ---------- payment method selection ---------- */
    function bindMethods() {
        qa('.methodItem').forEach(function (m) {
            if (bound(m)) return;
            m.addEventListener('click', function () {
                qa('.methodItem').forEach(function (x) { x.classList.remove('selected'); });
                m.classList.add('selected');
                var pkg = q('#packageSlider .package.selected');
                if (pkg) qa('#confirmBuy, .buyButton').forEach(function (b) { b.classList.remove('disabled'); });
            });
        });
    }

    /* ---------- buy button ---------- */
    function bindBuy() {
        var btn = q('#confirmBuy');
        if (btn && !bound(btn)) {
            btn.addEventListener('click', function (ev) {
                if (btn.classList.contains('disabled')) { ev.preventDefault(); return; }
                var pkg = q('#packageSlider .package.selected .goldProductId') || q('#packageSlider .goldProductId');
                var mth = q('.methodItem.selected .providerId') || q('.providerId');
                if (!pkg) { alert('ابتدا بستهٔ طلا را انتخاب کنید.'); return; }
                var url = 'tgpay.php?product=' + encodeURIComponent(pkg.value) +
                          '&provider=' + (mth ? encodeURIComponent(mth.value) : '11');
                window.open(url, 'tgpay', 'scrollbars=yes,status=yes,resizable=yes,toolbar=yes,width=480,height=640,left=60,top=60');
            });
        }
        var cont = q('#continueBuy');
        if (cont && !bound(cont)) {
            cont.addEventListener('click', function (ev) {
                if (cont.classList.contains('disabled')) { ev.preventDefault(); return; }
                var ps = q('#paymentMethodsSlider');
                if (ps) { ps.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
            });
        }
    }

    /* ---------- tab switching ---------- */
    function bindTabs() {
        qa('.tabButton').forEach(function (tab) {
            if (bound(tab)) return;
            tab.addEventListener('click', function (ev) {
                ev.preventDefault();
                var name = '';
                tab.classList.forEach(function (c) {
                    if (['buyGold', 'pros', 'plusSupport', 'openOrders', 'paymentFeatures'].indexOf(c) > -1) name = c;
                });
                if (!name) return;
                var pkg = q('#packageSlider .package.selected .goldProductId') || q('#packageSlider .goldProductId');
                var cur = q('#paymentWizard');
                if (!cur) return;
                jQuery.post('/ajax.php?cmd=paymentWizard', {
                    cmd: 'paymentWizard',
                    goldProductId: pkg ? pkg.value : '1',
                    goldProductLocation: '',
                    location: '',
                    activeTab: name,
                    ajaxToken: window.ajaxToken || ''
                }).done(function (resp) {
                    try {
                        var j = (typeof resp === 'string') ? JSON.parse(resp) : resp;
                        var html = j && j.response && j.response.data && j.response.data.html;
                        if (!html) return;
                        var tmp = document.createElement('div');
                        tmp.innerHTML = html;
                        var nw = tmp.querySelector('#paymentWizard') || tmp.firstElementChild;
                        if (nw) {
                            cur.replaceWith(nw);
                            init(nw.parentElement || document);
                        }
                    } catch (e) { /* noop */ }
                });
            });
        });
    }

    function init(root) {
        sliderFix('#packageSlider', '.productsPage');
        sliderFix('#paymentMethodsSlider', '.productsPage');
        sliderFix('#paymentMethodsSlider', '.methodsPage');
        bindPackages();
        bindMethods();
        bindBuy();
        bindTabs();
    }

    /* ---------- observer: wizards can appear at any time (AJAX dialogs) ---------- */
    function scan() {
        if (q('#paymentWizard') || q('#packageSlider')) init(document);
    }

    document.addEventListener('DOMContentLoaded', function () {
        scan();
        var mo = new MutationObserver(function () { scan(); });
        mo.observe(document.documentElement, { childList: true, subtree: true });
    });
    if (document.readyState !== 'loading') {
        scan();
        var mo2 = new MutationObserver(function () { scan(); });
        mo2.observe(document.documentElement, { childList: true, subtree: true });
    }
})();
