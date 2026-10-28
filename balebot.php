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

/* ------------------------------ auth ------------------------------ */
$__cfg = BaleApi::config();
if ($__cfg['secret'] !== '') {
    $hdr = isset($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN']) ? $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] : '';
    $okHeader = $hdr !== '' && hash_equals($__cfg['secret'], (string)$hdr);
    $okQuery  = isset($_GET['key']) && hash_equals($__cfg['secret'], (string)$_GET['key']);
    if (!$okHeader && !$okQuery) {
        http_response_code(403);
        exit('forbidden');
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
$update = json_decode((string)file_get_contents('php://input'), true);
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
        bale_reply($chatId,
            "👋 به ربات رسمی <b>Monefa Legend</b> خوش آمدید!\n\n"
            . "• برای دریافت <b>طلای وفاداری</b>: از بازی کد بگیرید و همان کد را اینجا بفرستید.\n"
            . "• برای <b>خرید طلا</b>: از منوی بازی (سکهٔ طلا) بستهٔ مورد نظر را انتخاب و پرداخت بله را بزنید.\n\n"
            . "🌐 " . htmlspecialchars('https://monefa.net', ENT_QUOTES, 'UTF-8'));
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
    bale_reply($chatId,
        "🛒 بسته‌های طلا:\n" . implode("\n", $lines) .
        "\n\nبرای خرید، وارد بازی شوید و از منوی سکهٔ طلا، پرداخت با بله را انتخاب کنید.\nhttps://monefa.net");
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
