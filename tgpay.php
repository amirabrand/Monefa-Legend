<?php
/**
 * tgpay.php — gold purchase checkout — Monefa Legend (T25)
 * ---------------------------------------------------------------------------
 * Payment provider: Bale messenger wallet (بله‌پی) via official Bot API.
 *   - createInvoiceLink  -> user is redirected to the invoice inside Bale app
 *   - balebot.php webhook-> pre_checkout approve + successful_payment credit
 * The previous ZarinPal flow was removed in T25 (user request).
 *
 * Usage: tgpay.php?product=<packageId>&provider=<unused-kept-for-compat>
 */
include('application/Account.php');
include_once(APP_BASE_PATH . '/library/Bale/BaleApi.php');

if (empty($session->uid)) {
    header('Location: login.php');
    exit;
}

$__product = isset($_GET['product']) ? (int)$_GET['product'] : 0;
if ($__product < 1) {
    header('Location: dorf1.php');
    exit;
}

$__pack = null;
foreach ($packages as $__p) {
    if ((int)$__p['id'] === $__product) {
        $__pack = $__p;
        break;
    }
}
if (!$__pack) {
    echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>خطا</title></head>'
       . '<body style="font-family:Tahoma;text-align:center;padding:60px;line-height:2">'
       . '<h2>⛔ تقلب</h2>'
       . 'شما سعی دارید در خرید طلا تقلب کنید. بستهٔ انتخابی معتبر نیست.<br>'
       . 'IP شما در سرور ثبت شد.</body></html>';
    exit;
}

$__cfg = BaleApi::config();

?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<title>اتصال به پرداخت بله — Monefa Legend</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body{font-family:Tahoma,Vazirmatn,sans-serif;background:#f4efe2;text-align:center;padding:60px 16px;line-height:2;color:#4a3b2a}
  .box{max-width:520px;margin:0 auto;background:#fffdf6;border:2px solid #beae9a;border-radius:8px;padding:28px;box-shadow:0 6px 14px rgba(190,174,154,.5)}
  .logo{font-size:22px;font-weight:bold;color:#8a5a1e;margin-bottom:10px}
  .amount{font-size:30px;font-weight:bold;color:#2e7d32;margin:12px 0}
  .btn{display:inline-block;margin-top:18px;background:#28a909;color:#fff;text-decoration:none;padding:12px 34px;border-radius:8px;font-size:17px}
  .err{color:#b71c1c}
  .note{font-size:13px;color:#7a6a55;margin-top:14px}
</style>
</head>
<body>
<div class="box">
  <div class="logo"><img src="/gpackcdn/img/provider/Logo-Bale.svg" alt="بله‌پی" style="height:30px;vertical-align:middle;margin-left:8px">خرید <?php echo (int)$__pack['gold']; ?> سکه طلا</div>
<?php
if (empty($__cfg['token']) || empty($__cfg['provider'])) {
    echo '<p class="err">پرداخت با پیام‌رسان بله هنوز راه‌اندازی نشده است.</p>'
       . '<p>لطفاً بعداً تلاش کنید یا با پشتیبانی بازی در تماس باشید.</p>'
       . '<p class="note">(مدیر سرور: توکن بازو و توکن کیف‌پول را از پنل مدیریت → «بات بله و پرداخت» وارد کند.)</p>'
       . '<a class="btn" href="javascript:history.back()">بازگشت</a>';
} else {
    $__price = (int)$__pack['cost'];
    $__rial  = $__price * (int)$__cfg['multiplier'];
    $__rand = bin2hex(random_bytes(6));
    $__payload = 'MFA:' . (int)$session->uid . ':' . $__product . ':' . $__rand;

    // record pending payment (idempotency + audit)
    $database->query(
        "INSERT INTO payments VALUES(NULL, :pm, '', :d, :u, :g, :c)",
        array(
            'pm' => 'bale:' . $__payload,
            'd'  => time(),
            'u'  => (int)$session->uid,
            'g'  => (int)$__pack['gold'],
            'c'  => (string)$__price,
        )
    );

    if ($__rial < 10000) {
        echo '<p class="err">مبلغ این بسته کمتر از حداقل فاکتور بله (۱٬۰۰۰ تومان) است.</p>'
           . '<p class="note">(مدیر سرور: قیمت بسته‌ها یا «ضریب تبدیل» در پنل → بات بله و پرداخت را افزایش دهد.)</p>'
           . '<a class="btn" href="javascript:history.back()">بازگشت</a></div></body></html>';
        exit;
    }

    /* T31: bot-mode payment. Bale's createInvoiceLink returns a bare
       "invoice_id=..." fragment (NOT a URL) so the web page cannot send the
       player anywhere useful. Instead the player is deep-linked into the bot
       with ?start=PAY<rand>; the bot then sends the wallet invoice (sendInvoice)
       and the existing webhook (pre_checkout_query / successful_payment) completes
       the payment and credits the gold. */
    $__botUrl = BaleApi::botLink();
    if ($__botUrl === '') {
        echo '<p class="err">نام کاربری ربات بله تنظیم نشده است.</p>'
           . '<p class="note">(مدیر سرور: در پنل مدیریت ← «بات بله و پرداخت» نام کاربری بات را وارد کند.)</p>'
           . '<a class="btn" href="javascript:history.back()">بازگشت</a>';
    } else {
        $__payToken = 'PAY' . strtoupper($__rand);
        echo '<p>در حال انتقال به ربات <b>بله</b>…</p>'
           . '<div class="amount">' . number_format($__rial / (int)$__cfg['multiplier']) . ' تومان</div>'
           . '<a class="btn" id="goBale" href="' . htmlspecialchars($__botUrl . '?start=' . $__payToken, ENT_QUOTES, 'UTF-8') . '">ادامهٔ پرداخت در بله</a>'
           . '<p class="note">پس از باز شدن ربات، دکمهٔ <b>START / شروع</b> را بزنید تا فاکتور خرید برای شما ارسال شود؛ سپس با کیف‌پول بله پرداخت کنید تا طلا بلافاصله به حسابتان اضافه شود.</p>'
           . '<script>setTimeout(function(){var a=document.getElementById("goBale");if(a){window.location.href=a.href;}},800);</script>';
    }
}
?>
</div>
</body>
</html>
