<?php
/**
 * balemini.php — Bale Mini App: Monefa Legend gold shop (T28)
 * ---------------------------------------------------------------------------
 * Opened inside Bale messenger:
 *   - inline web_app buttons sent by balebot.php (/start, /buy)
 *   - direct link  https://ble.ir/monefabot?startapp
 *   - BotFather main mini-app / menu button (optional, owner sets it)
 *
 * Auth: Bale.WebApp.initData validated per docs.bale.ai/miniapp:
 *   secret = HMAC_SHA256(key=bot_token, data="WebAppData")
 *   hash   = HMAC_SHA256(key=secret,    data=data-check-string)
 *   data-check-string = fields sorted alphabetically joined with "\n"
 *   (hash field excluded, complex fields are JSON-serialized)
 *
 * POST endpoints (JSON):
 *   action=me    {initData}              -> bale user + linked account + packages
 *   action=link  {initData, code}        -> link game account (botusersshaker code)
 *   action=buy   {initData, product}     -> pending payments row + createInvoiceLink
 * Payment completes through balebot.php webhook (successful_payment -> gold credit).
 */
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
@set_time_limit(20);

$__appRoot = dirname(__FILE__);
include_once($__appRoot . '/application/config.php');
include_once(APP_BASE_PATH . '/Database/db_MYSQL.php');
include_once(APP_BASE_PATH . '/library/Bale/BaleApi.php');
if (!class_exists('MYSQL_DB')) {
    include_once(APP_BASE_PATH . '/application/Database/db_MYSQL.php');
}
$database = new MYSQL_DB();
$__cfg = BaleApi::config();

function balemini_json($arr)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Validate Bale.WebApp.initData; returns WebAppUser array or false.
 *  Tries the raw (url-encoded) form first, then the decoded form —
 *  both are HMAC-verified, so accepting either stays safe. */
function balemini_validate($initData, $botToken)
{
    if (!is_string($initData) || $initData === '' || !is_string($botToken) || $botToken === '') {
        return false;
    }
    /* parse once into both raw (url-encoded) and decoded views */
    $raw = array();
    $decoded = array();
    foreach (explode('&', $initData) as $kv) {
        if ($kv === '') continue;
        $eq = strpos($kv, '=');
        if ($eq === false) continue;
        $k = substr($kv, 0, $eq);
        $v = substr($kv, $eq + 1);
        $raw[$k] = $v;
        if (!isset($decoded[$k])) {
            $decoded[$k] = urldecode($v);
        }
    }
    $u = balemini_validate_parts($raw, $botToken);
    if ($u) return $u;
    return balemini_validate_parts($decoded, $botToken);
}

function balemini_validate_parts($parts, $botToken)
{
    if (empty($parts['hash'])) return false;
    $hash = $parts['hash'];
    unset($parts['hash']);
    $keys = array_keys($parts);
    sort($keys);
    $dcs = array();
    foreach ($keys as $k) {
        $dcs[] = $k . '=' . $parts[$k];
    }
    $secret = hash_hmac('sha256', 'WebAppData', $botToken);
    if (!hash_equals(hash_hmac('sha256', implode("\n", $dcs), $secret), $hash)) {
        return false;
    }
    /* reject stale init data (older than 24h) */
    if (!empty($parts['auth_date']) && (time() - (int)$parts['auth_date'] > 86400)) {
        return false;
    }
    if (empty($parts['user'])) return false;
    $user = json_decode(urldecode($parts['user']), true);
    if (!is_array($user) || empty($user['id'])) return false;
    return $user;
}

/** Resolve the game account linked to a Bale user id (botusersshaker.telid). */
function balemini_account($database, $baleId)
{
    $row = $database->queryFetch(
        "SELECT username FROM botusersshaker WHERE telid = :t ORDER BY id DESC LIMIT 1",
        array('t' => (int)$baleId)
    );
    if (empty($row['username'])) return null;
    $u = $database->queryFetch(
        "SELECT id, username, gold FROM users WHERE username = :u LIMIT 1",
        array('u' => $row['username'])
    );
    return $u ?: null;
}

/* ------------------------------ API ------------------------------ */
if (isset($_POST['action'])) {
    $user = balemini_validate(isset($_POST['initData']) ? $_POST['initData'] : '', $__cfg['token']);
    if (!$user) {
        balemini_json(array('ok' => false, 'error' => 'auth'));
    }
    $baleId = (int)$user['id'];
    $act = (string)$_POST['action'];

    if ($act === 'me') {
        $acc = balemini_account($database, $baleId);
        $packs = array();
        foreach ((isset($packages) ? $packages : array()) as $p) {
            $packs[] = array('id' => (int)$p['id'], 'gold' => (int)$p['gold'], 'cost' => (int)$p['cost']);
        }
        balemini_json(array(
            'ok'         => true,
            'bale'       => array('id' => $baleId, 'name' => isset($user['first_name']) ? $user['first_name'] : ''),
            'account'    => $acc ? array('uid' => (int)$acc['id'], 'username' => $acc['username'], 'gold' => (int)$acc['gold']) : null,
            'packages'   => $packs,
            'multiplier' => (int)$__cfg['multiplier'],
        ));
    }

    if ($act === 'link') {
        $code = preg_replace('/[^A-Za-z0-9]/', '', isset($_POST['code']) ? $_POST['code'] : '');
        if ($code === '') balemini_json(array('ok' => false, 'error' => 'code'));
        $row = $database->queryFetch(
            "SELECT id, username FROM botusersshaker WHERE shaker = :c LIMIT 1",
            array('c' => $code)
        );
        if (empty($row['id'])) {
            balemini_json(array('ok' => false, 'error' => 'invalid'));
        }
        $database->query(
            "UPDATE botusersshaker SET telid = :t WHERE id = :i",
            array('t' => $baleId, 'i' => (int)$row['id'])
        );
        balemini_json(array('ok' => true, 'username' => $row['username']));
    }

    if ($act === 'buy') {
        $acc = balemini_account($database, $baleId);
        if (!$acc) balemini_json(array('ok' => false, 'error' => 'notlinked'));
        $pid = isset($_POST['product']) ? (int)$_POST['product'] : 0;
        $pack = null;
        foreach ((isset($packages) ? $packages : array()) as $p) {
            if ((int)$p['id'] === $pid) { $pack = $p; break; }
        }
        if (!$pack) balemini_json(array('ok' => false, 'error' => 'pack'));
        if (empty($__cfg['token']) || empty($__cfg['provider'])) {
            balemini_json(array('ok' => false, 'error' => 'unconfigured'));
        }
        $price   = (int)$pack['cost'];
        $rial    = $price * (int)$__cfg['multiplier'];
        if ($rial < 10000) balemini_json(array('ok' => false, 'error' => 'min'));
        $payload = 'MFA:' . (int)$acc['id'] . ':' . $pid . ':' . bin2hex(random_bytes(6));
        $database->query(
            "INSERT INTO payments VALUES(NULL, :pm, '', :d, :u, :g, :c)",
            array('pm' => 'bale:' . $payload, 'd' => time(), 'u' => (int)$acc['id'], 'g' => (int)$pack['gold'], 'c' => (string)$price)
        );
        $res = BaleApi::call('createInvoiceLink', array(
            'title'          => 'خرید ' . (int)$pack['gold'] . ' سکه طلا',
            'description'    => 'شارژ ' . (int)$pack['gold'] . ' سکه طلا برای ' . $acc['username'] . ' در Monefa Legend',
            'payload'        => $payload,
            'provider_token' => $__cfg['provider'],
            'currency'       => 'IRR',
            'prices'         => array(array('label' => (int)$pack['gold'] . ' سکه طلا', 'amount' => $rial)),
        ), 15);
        if (!empty($res['ok']) && !empty($res['result'])) {
            balemini_json(array('ok' => true, 'invoice' => $res['result'], 'amount' => $price));
        }
        balemini_json(array('ok' => false, 'error' => 'api', 'desc' => isset($res['description']) ? $res['description'] : 'no-response'));
    }

    balemini_json(array('ok' => false, 'error' => 'action'));
}

/* ------------------------------ page ------------------------------ */
/* T33: opening the mini-app marks this device as mobile for the whole
   site (sticky 90-day cookie). Bale's desktop client embeds pages in a
   webview with a plain Windows UA - without this, game pages fall back
   to the 1280 desktop canvas inside a small window (broken layout). */
@setcookie('t26m', '1', array('expires' => time() + 7776000, 'path' => '/', 'samesite' => 'Lax'));
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<script src="https://tapi.bale.ai/miniapp.js"></script>
<title>Monefa Legend — فروشگاه طلا</title>
<style>
  :root{
    --bg:#f4efe2; --card:#fffdf6; --line:#d8c9ae; --tx:#4a3b2a; --dim:#8a7a60;
    --gold:#8a5a1e; --green:#28a909; --red:#b71c1c;
  }
  *{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
  body{margin:0;font-family:Tahoma,'IRYekan',sans-serif;background:var(--bg);color:var(--tx);line-height:1.9}
  .wrap{max-width:480px;margin:0 auto;padding:14px 12px 90px}
  .hero{background:linear-gradient(135deg,#7a4a12,#b98a3e);color:#fff;border-radius:14px;padding:14px 16px;box-shadow:0 4px 10px rgba(122,74,18,.35)}
  .hero .t{font-size:17px;font-weight:bold}
  .hero .s{font-size:12px;opacity:.85}
  .card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:12px 14px;margin-top:12px}
  .gold{font-size:22px;font-weight:bold;color:var(--gold)}
  .row{display:flex;align-items:center;justify-content:space-between;gap:8px}
  .pill{display:inline-block;background:#efe4cb;border-radius:20px;padding:2px 10px;font-size:12px;color:var(--dim)}
  .pack{display:flex;align-items:center;justify-content:space-between;background:var(--card);border:1px solid var(--line);border-radius:12px;padding:10px 14px;margin-top:10px}
  .pack .g{font-size:16px;font-weight:bold;color:var(--gold)}
  .pack .p{font-size:13px;color:var(--dim)}
  button.buy{background:var(--green);color:#fff;border:0;border-radius:10px;padding:9px 18px;font-size:14px;font-family:inherit;cursor:pointer}
  button.buy:disabled{opacity:.6}
  input.code{width:100%;border:1px solid var(--line);border-radius:10px;padding:10px 12px;font-size:15px;font-family:inherit;background:#fff;direction:ltr;text-align:center;letter-spacing:2px}
  .note{font-size:12px;color:var(--dim);margin-top:6px}
  .err{color:var(--red);font-size:13px}
  .ok{color:var(--green);font-size:13px}
  .warn{background:#fff3cd;border:1px solid #e0c36a;border-radius:12px;padding:12px 14px;margin-top:12px;font-size:13px;color:#7a5c10}
</style>
</head>
<body>
<div class="wrap">
  <div class="hero">
    <div class="t">🛒 فروشگاه طلا — Monefa Legend</div>
    <div class="s">پرداخت امن با کیف‌پول بله (بله‌پی)</div>
  </div>
  <div id="app"><div class="card">در حال بارگذاری…</div></div>
</div>
<script>
(function () {
  try { document.cookie = 't26m=1;path=/;max-age=7776000;SameSite=Lax'; } catch (e) {}
  var W = (window.Bale && window.Bale.WebApp) ? window.Bale.WebApp : null;
  var app = document.getElementById('app');

  function post(a, extra) {
    var f = new FormData();
    f.append('action', a);
    f.append('initData', W ? (W.initData || '') : '');
    if (extra) { for (var k in extra) { f.append(k, extra[k]); } }
    return fetch(location.pathname, { method: 'POST', body: f })
      .then(function (r) { return r.json(); });
  }

  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

  function buy(pid, btn) {
    if (btn) { btn.disabled = true; btn.textContent = 'در حال اتصال…'; }
    post('buy', { product: pid }).then(function (r) {
      if (btn) { btn.disabled = false; btn.textContent = 'خرید'; }
      if (r.ok && r.invoice) {
        if (W && W.openInvoice) {
          W.openInvoice(r.invoice, function (st) {
            if (st && (st.status === 'paid' || st.status === 'pending')) { load(); }
          });
        } else {
          window.open(r.invoice, '_blank');
        }
      } else {
        var m = {
          auth: 'نشست نامعتبر — مینی‌اپ را از داخل بله باز کنید.',
          notlinked: 'ابتدا حساب بازی خود را متصل کنید.',
          pack: 'بستهٔ نامعتبر است.',
          min: 'مبلغ این بسته کمتر از حداقل فاکتور بله (۱٬۰۰۰ تومان) است. لطفاً قیمت بسته‌ها را با مدیر بازی چک کنید.',
          unconfigured: 'پرداخت بله هنوز راه‌اندازی نشده است.',
          api: 'خطا در ایجاد فاکتور' + (r.desc ? (': ' + r.desc) : '')
        };
        app.insertAdjacentHTML('afterbegin',
          '<div class="card"><span class="err">' + esc(m[r.error] || 'خطای نامشخص') + '</span></div>');
      }
    }).catch(function () {
      if (btn) { btn.disabled = false; btn.textContent = 'خرید'; }
    });
  }

  function doLink() {
    var inp = document.getElementById('t28code');
    var msg = document.getElementById('t28linkmsg');
    var code = inp ? inp.value.trim() : '';
    if (!code) { return; }
    post('link', { code: code }).then(function (r) {
      if (r.ok) { load(); }
      else if (msg) {
        msg.innerHTML = '<span class="err">کد نامعتبر است. از صفحهٔ «طلای وفاداری» در بازی کد جدید بگیرید.</span>';
      }
    });
  }

  function render(d) {
    if (!d || !d.ok) {
      if (W) {
        /* bridge loaded but initData missing/invalid: user IS inside
           Bale - a stale webview session usually fixes on reopen. */
        app.innerHTML = '<div class="warn">ارتباط با بله برقرار نشد.<br>صفحه را کاملاً ببندید و دوباره از داخل بله (از ربات) باز کنید.'
          + '<div style="margin-top:10px;text-align:center"><button class="buy" onclick="location.reload()">تلاش دوباره</button></div></div>';
      } else {
      app.innerHTML = '<div class="warn">برای استفاده، این صفحه را از داخل پیام‌رسان <b>بله</b> باز کنید:<br>'
        + '<span dir="ltr">ble.ir/monefabot?startapp</span><br>'
        + 'یا در گفتگو با بازو، دکمهٔ «فروشگاه طلا» را بزنید.</div>';
        }return;
    }
    var h = '';
    if (d.account) {
      h += '<div class="card"><div class="row"><div>'
        + '<span class="pill">حساب متصل</span> <b>' + esc(d.account.username) + '</b></div>'
        + '<div class="gold">' + d.account.gold.toLocaleString('fa-IR') + ' 🪙</div></div></div>';
    } else {
      h += '<div class="card"><b>اتصال حساب بازی</b>'
        + '<div class="note">کد ۶ رقمی صفحهٔ «طلای وفاداری» در بازی را وارد کنید:</div>'
        + '<input class="code" id="t28code" maxlength="20" placeholder="CODE">'
        + '<div style="margin-top:8px;text-align:center"><button class="buy" onclick="t28link()">اتصال حساب</button></div>'
        + '<div id="t28linkmsg"></div></div>';
    }
    h += '<div class="card" style="padding:8px 14px"><b>بسته‌های طلا</b><span class="note">پس از پرداخت، طلا بلافاصله واریز می‌شود.</span></div>';
    var tooLow = (d.multiplier || 0) * 10000;
    for (var i = 0; i < d.packages.length; i++) {
      var p = d.packages[i];
      var blocked = (p.cost * d.multiplier) < 10000;
      h += '<div class="pack"' + (blocked ? ' style="opacity:.55"' : '') + '><div><div class="g">' + p.gold.toLocaleString('fa-IR') + ' سکه طلا</div>'
        + '<div class="p">' + p.cost.toLocaleString('fa-IR') + ' تومان' + (blocked ? ' — زیر حداقل بله' : '') + '</div></div>'
        + '<button class="buy" onclick="t28buy(' + p.id + ',this)"' + (blocked ? ' disabled' : '') + '>خرید</button></div>';
    }
    h += '<div class="note" style="text-align:center">Monefa Legend — پرداخت با بله‌پی</div>';
    app.innerHTML = h;
  }

  function load() {
    post('me').then(function (d) {
      render(d);
      try { W.ready(); W.expand(); } catch (e) {}
    });
  }

  window.t28buy = buy;
  window.t28link = doLink;
  load();
})();
</script>
</body>
</html>
