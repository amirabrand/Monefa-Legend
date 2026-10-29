<?php
/**
 * Bale Bot webhook — Monefa Legend (T25)
 * ---------------------------------------------------------------------------
 * Setup (from admin panel ?p=balebot):
 *   setWebhook url = https://monefa.net/balebot.php
 *   secret_token   = $baleWebhookSecret  (sent as X-Telegram-Bot-Api-Secret-Token)
 *
 * Handled updates:
 *   - /start <linkcode>     -> links Bale chat with botusersshaker row (loyalty gold)
 *   - /start, /help, /buy   -> help / gold packages info
 *   - pre_checkout_query    -> approve
 *   - successful_payment    -> credit gold (idempotent via payments.idTrans)
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

/* ------------------------------ T31 request log ------------------------------ */
/* Every webhook POST is logged (incl. rejected ones) so webhook delivery
   problems are visible in /var/www/monefa-secure/ConfsData/monefa.net/logs/webhook.log */
$__t31cli = (PHP_SAPI === 'cli');
$__t31log = '/var/www/monefa-secure/ConfsData/monefa.net/logs/webhook.log';
if (!$__t31cli && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $__t31body = (string)file_get_contents('php://input');
    $__t31sec = isset($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN']) ? $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] : '';
    @file_put_contents($__t31log, date('Y-m-d H:i:s')
        . ' | ' . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '-')
        . ' | UA=' . (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '-')
        . ' | secretHeader=' . ($__t31sec !== '' ? ('present(len=' . strlen($__t31sec) . ')') : 'ABSENT')
        . ' | qs=' . (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '')
        . ' | body=' . substr(preg_replace('/\s+/', ' ', $__t31body), 0, 300) . "\n",
        FILE_APPEND | LOCK_EX);
}

/* ------------------------------ auth ------------------------------ */
/* T31: Bale does NOT sign webhook POSTs with X-Telegram-Bot-Api-Secret-Token
   (verified: real Bale POSTs hit 403 because the expected header never came).
   Policy:
     - header or ?key present and valid -> allow
     - header present but wrong         -> 403 (probe with a stale secret)
     - no credential at all             -> allow (logged) until Bale sends one
   CLI (--update=/--updatefile=) bypasses auth. */
if (!$__t31cli) {
    $__cfg = BaleApi::config();
    if ($__cfg['secret'] !== '') {
        $hdr = isset($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN']) ? $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] : '';
        $okHeader = $hdr !== '' && hash_equals($__cfg['secret'], (string)$hdr);
        $okQuery  = isset($_GET['key']) && hash_equals($__cfg['secret'], (string)$_GET['key']);
        if (!$okHeader && !$okQuery) {
            if ($hdr === '' && !isset($_GET['key'])) {
                @file_put_contents($__t31log, date('Y-m-d H:i:s') . " | AUTH=no-cred-allowed\n", FILE_APPEND | LOCK_EX);
            } else {
                @file_put_contents($__t31log, date('Y-m-d H:i:s') . " | AUTH=WRONG-CRED-REJECTED\n", FILE_APPEND | LOCK_EX);
                http_response_code(403);
                exit('forbidden');
            }
        }
    }
}

/* ------------------------------ helpers ------------------------------ */
function bale_reply($chatId, $text)
{
    BaleApi::call('sendMessage', array(
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
    ), 8);
}

function bale_reply_kb($chatId, $text, $kb)
{
    BaleApi::call('sendMessage', array(
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode($kb, JSON_UNESCAPED_UNICODE),
    ), 8);
}

/* T28: MiniApp entry — opens balemini.php inside Bale */
function bale_shop_keyboard()
{
    return array('inline_keyboard' => array(
        array(array('text' => '🛒 فروشگاه طلا (مینی‌اپ بله)', 'web_app' => array('url' => 'https://monefa.net/balemini.php'))),
        array(array('text' => '🌐 سایت بازی', 'url' => 'https://monefa.net')),
    ));
}

function bale_credit_from_row($row, $chargeId, $chatId, $paidRial)
{
    global $database;
    if (empty($row) || empty($row['id'])) {
        return false;
    }
    if (!empty($row['idTrans'])) {
        bale_reply($chatId, "این پرداخت قبلاً ثبت و طلاي آن واریز شده است.");
        return true; // idempotent replay
    }
    $uid  = (int)$row['idUser'];
    $gold = (int)$row['gAmount'];
    if ($uid <= 0 || $gold <= 0) {
        return false;
    }
    $database->query("UPDATE payments SET idTrans = :t, dTrans = :d WHERE id = :i", array(
        't' => (string)$chargeId, 'd' => time(), 'i' => (int)$row['id'],
    ));
    $database->query("UPDATE users SET gold = gold + :g, boughtgold = boughtgold + :g WHERE id = :u", array(
        'g' => $gold, 'u' => $uid,
    ));
    $u = $database->queryFetch("SELECT username, gold FROM users WHERE id = :u LIMIT 1", array('u' => $uid));
    $uname = $u ? $u['username'] : ('#' . $uid);
    $newgold = $u ? (int)$u['gold'] : 0;
    try {
        $database->sendMessage($uid, 4, 'خرید طلا با بله — تایید شد',
            "پرداخت شما از طریق پیام‌رسان بله تایید شد و {$gold} سکه طلا به حساب شما اضافه شد.\n"
            . "موجودی فعلی: {$newgold} طلا\nکد تراکنش: {$chargeId}", 0, 0, 0, 0, 0);
    } catch (Exception $e) { /* non-fatal */ }
    bale_reply($chatId,
        "✅ پرداخت با موفقیت انجام شد.\n"
        . "🎮 حساب: <b>" . htmlspecialchars($uname, ENT_QUOTES, 'UTF-8') . "</b>\n"
        . "💰 {$gold} سکه طلا به حسابتان اضافه شد.\n"
        . "🔐 کد تراکنش: <code>" . htmlspecialchars((string)$chargeId, ENT_QUOTES, 'UTF-8') . "</code>");
    return true;
}

/* ------------------------------ update ------------------------------ */
/* T31: CLI mode — php balebot.php --update='<json>'  |  --updatefile=<path>
   lets the server replay a queued update (getUpdates) through the exact same
   handler the webhook uses. */
$update = null;
if ($__t31cli) {
    foreach ($argv as $__a) {
        if (strpos($__a, '--update=') === 0) {
            $update = json_decode(substr($__a, 9), true);
        } elseif (strpos($__a, '--updatefile=') === 0) {
            $update = json_decode((string)file_get_contents(substr($__a, 13)), true);
        }
    }
} else {
    $update = json_decode((string)file_get_contents('php://input'), true);
}
if (!is_array($update)) {
    http_response_code(200);
    exit('ok');
}

/* --- pre checkout: approve (server already created the invoice) --- */
if (!empty($update['pre_checkout_query'])) {
    $pre = $update['pre_checkout_query'];
    BaleApi::call('answerPreCheckoutQuery', array(
        'pre_checkout_query_id' => $pre['id'],
        'ok' => true,
    ), 8);
    http_response_code(200);
    exit('ok');
}

if (empty($update['message'])) {
    http_response_code(200);
    exit('ok');
}
$msg    = $update['message'];
$chatId = isset($msg['chat']['id']) ? (int)$msg['chat']['id'] : 0;
if (!$chatId) {
    http_response_code(200);
    exit('ok');
}

/* --- successful payment --- */
if (!empty($msg['successful_payment'])) {
    $sp       = $msg['successful_payment'];
    $payload  = (string)(isset($sp['invoice_payload']) ? $sp['invoice_payload'] : '');
    $charge   = (string)(isset($sp['telegram_payment_charge_id']) ? $sp['telegram_payment_charge_id'] : ('bale-' . time()));
    $paidRial = (int)(isset($sp['total_amount']) ? $sp['total_amount'] : 0);

    $row = null;
    if ($payload !== '') {
        $row = $database->queryFetch("SELECT * FROM payments WHERE pMethod = :pm LIMIT 1", array('pm' => 'bale:' . $payload));
    }
    if ($row) {
        /* T31b: verify the paid amount against the pending row (cost is Toman,
           the invoice was issued with cost * multiplier in Rial). A mismatch is
           flagged, never auto-credited. */
        $__cfgV = BaleApi::config();
        $__expectRial = (int)$row['cost'] * max(1, (int)$__cfgV['multiplier']);
        if ($paidRial > 0 && $__expectRial > 0 && $paidRial != $__expectRial) {
            bale_reply($chatId,
                "مبلغ پرداخت‌شده (" . number_format($paidRial) . " ریال) با فاکتور ثبت‌شده ("
                . number_format($__expectRial) . " ریال) مطابقت ندارد.\n"
                . "طلا به‌صورت خودکار واریز نشد؛ لطفاً با پشتیبانی بازی در تماس باشید. کد تراکنش: <code>"
                . htmlspecialchars((string)$charge, ENT_QUOTES, 'UTF-8') . "</code>");
            http_response_code(200);
            exit('ok');
        }
        bale_credit_from_row($row, $charge, $chatId, $paidRial);
    } elseif (preg_match('/^MFA:(\d+):(\d+):/', $payload, $m)) {
        // fallback: rebuild from payload MFA:{uid}:{pkgId}:{rand}
        $uid   = (int)$m[1];
        $pkgId = (int)$m[2];
        $gold  = 0;
        $packs = isset($packages) ? $packages : null;
        if (!$packs && function_exists('mysql_shim_noop')) { $packs = null; }
        if ($packs) {
            foreach ($packs as $pk) { if ((int)$pk['id'] === $pkgId) { $gold = (int)$pk['gold']; break; } }
        }
        if ($gold > 0 && $uid > 0) {
            $database->query("INSERT INTO payments VALUES(NULL, :pm, :t, :d, :u, :g, '')", array(
                'pm' => 'bale:' . $payload, 't' => (string)$charge, 'd' => time(), 'u' => $uid, 'g' => $gold,
            ));
            $row = $database->queryFetch("SELECT * FROM payments WHERE pMethod = :pm LIMIT 1", array('pm' => 'bale:' . $payload));
            bale_credit_from_row($row, $charge, $chatId, $paidRial);
        } else {
            bale_reply($chatId, "پرداخت دریافت شد اما بستهٔ مربوط پیدا نشد. لطفاً به پشتیبانی بازی پیام دهید. کد: {$charge}");
        }
    } else {
        bale_reply($chatId, "پرداختی برای این بازی شناسایی نشد (payload نامعتبر). کد: {$charge}");
    }
    http_response_code(200);
    exit('ok');
}

/* --- text commands --- */
$text = isset($msg['text']) ? trim((string)$msg['text']) : '';
if ($text === '') {
    http_response_code(200);
    exit('ok');
}

if (preg_match('/^\/code[_ -]?([A-Za-z0-9]{4,20})/i', $text, $cm)) {
    $code = $cm[1];
    $row = $database->queryFetch("SELECT id FROM botusersshaker WHERE shaker = :c LIMIT 1", array('c' => $code));
    if ($row) {
        $database->query("UPDATE botusersshaker SET telid = :t WHERE id = :i", array('t' => $chatId, 'i' => (int)$row['id']));
        bale_reply($chatId, "\u2705 کد شما تایید شد!\nحالا به بازی برگردید و روی دکمهٔ «بررسی عضویت در بات» بزنید تا طلای وفاداری واریز شود.");
    } else {
        bale_reply($chatId, "\u274c این کد معتبر نیست یا منقضی شده است. از صفحهٔ طلای وفاداری در بازی کد جدید بگیرید.");
    }
    http_response_code(200);
    exit('ok');
}

if (strpos($text, '/start') === 0) {
    $param = trim(substr($text, 6));
    /* T31: /start PAYxxxxxxxxxxxx -> bot-mode invoice for a pending web payment
       (tgpay.php records the pending row and sends the player here). */
    if (preg_match('/^PAY([A-Fa-f0-9]{12})$/', $param, $__pm)) {
        $__rand   = strtolower($__pm[1]);
        $__cfgP   = BaleApi::config();
        $__rowP   = $database->queryFetch("SELECT * FROM payments WHERE pMethod LIKE :pm LIMIT 1", array('pm' => 'bale:%:' . $__rand));
        if (empty($__rowP)) {
            bale_reply($chatId, "این فاکتور پیدا نشد. لطفاً از فروشگاه بازی دوباره اقدام کنید.");
        } elseif (!empty($__rowP['idTrans'])) {
            bale_reply($chatId, "این فاکتور قبلاً پرداخت یا استفاده شده است. از فروشگاه بازی دوباره اقدام کنید.");
        } elseif (empty($__cfgP['provider'])) {
            bale_reply($chatId, "پرداخت بله هنوز راه‌اندازی نشده است.");
        } else {
            $__rialP = (int)$__rowP['cost'] * (int)$__cfgP['multiplier'];
            if ($__rialP < 10000) {
                bale_reply($chatId, "مبلغ این فاکتور کمتر از حداقل بله‌پی است. از فروشگاه بازی دوباره اقدام کنید.");
            } else {
                $__invP = BaleApi::call('sendInvoice', array(
                    'chat_id'        => $chatId,
                    'title'          => 'خرید ' . (int)$__rowP['gAmount'] . ' سکه طلا',
                    'description'    => 'شارژ ' . (int)$__rowP['gAmount'] . ' سکه طلا — Monefa Legend',
                    'payload'        => substr((string)$__rowP['pMethod'], 5),
                    'provider_token' => $__cfgP['provider'],
                    'currency'       => 'IRR',
                    'prices'         => array(array('label' => (int)$__rowP['gAmount'] . ' سکه طلا', 'amount' => $__rialP)),
                ), 12);
                if (!empty($__invP['ok'])) {
                    bale_reply($chatId, "🧾 فاکتور «" . (int)$__rowP['gAmount'] . " سکه طلا» برای شما ارسال شد.\nبا کیف‌پول بله پرداخت کنید؛ طلا بلافاصله واریز می‌شود.");
                } else {
                    bale_reply($chatId, "خطا در ارسال فاکتور: " . htmlspecialchars(isset($__invP['description']) ? $__invP['description'] : 'پاسخی از بله دریافت نشد', ENT_QUOTES, 'UTF-8'));
                }
            }
        }
        if (!$__t31cli) { http_response_code(200); }
        exit('ok');
    }
    if ($param !== '') {
        $code = preg_replace('/[^A-Za-z0-9]/', '', $param);
        $row = $database->queryFetch("SELECT id, username FROM botusersshaker WHERE shaker = :c LIMIT 1", array('c' => $code));
        if ($row) {
            $database->query("UPDATE botusersshaker SET telid = :t WHERE id = :i", array('t' => $chatId, 'i' => (int)$row['id']));
            bale_reply($chatId,
                "✅ کد شما تایید شد!\n"
                . "حالا به بازی برگردید و در صفحهٔ «طلای وفاداری» روی دکمهٔ بررسی/تایید بزنید تا طلاي هدیه واریز شود.");
        } else {
            bale_reply($chatId, "❌ این کد معتبر نیست یا منقضی شده است. از صفحهٔ طلای وفاداری در بازی یک کد جدید بگیرید.");
        }
    } else {
        $link = BaleApi::botLink();
        bale_reply_kb($chatId,
            "👋 به ربات رسمی <b>Monefa Legend</b> خوش آمدید!\n\n"
            . "• برای دریافت <b>طلای وفاداری</b>: از بازی کد بگیرید و همان کد را اینجا بفرستید.\n"
            . "• برای <b>خرید طلا</b>: از منوی بازی (سکهٔ طلا) بستهٔ مورد نظر را انتخاب و پرداخت بله را بزنید.\n\n"
            . "🌐 " . htmlspecialchars('https://monefa.net', ENT_QUOTES, 'UTF-8'),
            bale_shop_keyboard());
    }
    http_response_code(200);
    exit('ok');
}

if ($text === '/buy' || $text === 'خرید طلا') {
    $lines = array();
    $packs = isset($packages) ? $packages : array();
    foreach ($packs as $pk) {
        $lines[] = '• ' . (int)$pk['gold'] . ' طلا ← ' . (int)$pk['cost'] . ' تومان';
    }
    bale_reply_kb($chatId,
        "🛒 بسته‌های طلا:\n" . implode("\n", $lines) .
        "\n\nبرای خرید مستقیم، دکمهٔ «فروشگاه طلا» را بزنید:",
        bale_shop_keyboard());
    http_response_code(200);
    exit('ok');
}

if ($text === '/help' || $text === 'راهنما') {
    bale_reply($chatId,
        "📖 راهنما:\n/start — شروع و اتصال حساب\n/buy — لیست بسته‌های طلا\n\n"
        . "برای اتصال حساب طلای وفاداری، کد دریافتی از بازی را همین‌جا ارسال کنید.");
    http_response_code(200);
    exit('ok');
}

http_response_code(200);
exit('ok');
