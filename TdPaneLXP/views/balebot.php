<?php
/**
 * Admin view: بات بله و پرداخت (?p=balebot) — T25
 * Configure the official Bale bot (messenger + wallet payments) that
 * replaces the old ZarinPal checkout and powers the loyalty-gold linking.
 */
if (!defined('APP_BASE_PATH')) {
    // direct access guard — views are included from index.php
    exit('forbidden');
}
include_once(APP_BASE_PATH . '/library/Bale/BaleApi.php');

$__cfg = BaleApi::config();
$__msg = '';
$__err = '';
$__test = null;

/* ---------- actions (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bale_action'])) {
    $act = (string)$_POST['bale_action'];

    if ($act === 'save') {
        $newSecret = BaleApi::saveConfig(array(
            'token'     => isset($_POST['bale_token']) ? (string)$_POST['bale_token'] : '',
            'username'  => isset($_POST['bale_username']) ? (string)$_POST['bale_username'] : '',
            'provider'  => isset($_POST['bale_provider']) ? (string)$_POST['bale_provider'] : '',
            'multiplier'=> isset($_POST['bale_multiplier']) ? (int)$_POST['bale_multiplier'] : 10,
            'secret'    => isset($_POST['bale_secret']) ? (string)$_POST['bale_secret'] : '',
        ));
        $__cfg = BaleApi::config();
        $__msg = 'تنظیمات با موفقیت ذخیره شد.';
    } elseif ($act === 'test') {
        $__test = BaleApi::call('getMe', array(), 10);
    } elseif ($act === 'webhook') {
        $__url = 'https://' . $_SERVER['HTTP_HOST'] . '/balebot.php';
        $__params = array('url' => $__url);
        if ($__cfg['secret'] !== '') {
            $__params['secret_token'] = $__cfg['secret'];
        }
        $__test = BaleApi::call('setWebhook', $__params, 15);
    } elseif ($act === 'webhook_del') {
        $__test = BaleApi::call('deleteWebhook', array(), 15);
    } elseif ($act === 'gen_secret') {
        BaleApi::saveConfig(array(
            'token' => $__cfg['token'], 'username' => $__cfg['username'],
            'provider' => $__cfg['provider'], 'multiplier' => $__cfg['multiplier'],
            'secret' => bin2hex(random_bytes(16)),
        ));
        $__cfg = BaleApi::config();
        $__msg = 'کلید وب‌هوک جدید ساخته شد. وب‌هوک را دوباره ثبت کنید.';
    }
}

$__webhook = BaleApi::call('getWebhookInfo', array(), 10);
$__whOk = !empty($__webhook['ok']) ? $__webhook['result'] : null;
$__botLink = BaleApi::botLink();

/* recent bale payments */
$__pays = $database->query("SELECT * FROM payments WHERE pMethod LIKE 'bale:%' ORDER BY id DESC LIMIT 12");
if (!is_array($__pays)) $__pays = array();

/* recent loyalty link codes */
$__codes = $database->query("SELECT * FROM botusersshaker ORDER BY id DESC LIMIT 8");
if (!is_array($__codes)) $__codes = array();
?>

<div class="contentWrapper">
  <h1 class="titleInHeader">بات بله و پرداخت</h1>

  <?php if ($__msg): ?>
    <div style="background:#e8f5e9;border:1px solid #4caf50;color:#1b5e20;padding:10px 14px;border-radius:6px;margin:10px 0"><?php echo $__msg; ?></div>
  <?php endif; ?>
  <?php if ($__err): ?>
    <div style="background:#ffebee;border:1px solid #f44336;color:#b71c1c;padding:10px 14px;border-radius:6px;margin:10px 0"><?php echo $__err; ?></div>
  <?php endif; ?>

  <!-- ============ status ============ -->
  <div style="display:flex;flex-wrap:wrap;gap:14px;margin:14px 0">
    <div style="flex:1;min-width:240px;background:#fffdf6;border:1px solid #beae9a;border-radius:8px;padding:14px">
      <h3 style="margin-top:0">وضعیت ربات</h3>
      <ul style="line-height:2.1;list-style:none;padding:0">
        <li>توکن بازو: <b><?php echo $__cfg['token'] !== '' ? '✔ تنظیم شده' : '✘ خالی'; ?></b></li>
        <li>توکن کیف‌پول (provider): <b><?php echo $__cfg['provider'] !== '' ? '✔ تنظیم شده' : '✘ خالی'; ?></b></li>
        <li>نام کاربری: <b><?php echo $__cfg['username'] !== '' ? '@' . htmlspecialchars($__cfg['username'], ENT_QUOTES, 'UTF-8') : '—'; ?></b></li>
        <li>ضریب تومان→ریال: <b><?php echo (int)$__cfg['multiplier']; ?></b></li>
        <li>وب‌هوک:
          <?php if ($__whOk && !empty($__whOk['url'])): ?>
            <b style="color:#2e7d32">فعال</b> ← <span style="direction:ltr;display:inline-block"><?php echo htmlspecialchars((string)$__whOk['url'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php if (!empty($__whOk['last_error_message'])): ?>
              <br><span style="color:#b71c1c">آخرین خطا: <?php echo htmlspecialchars((string)$__whOk['last_error_message'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
          <?php else: ?>
            <b style="color:#b71c1c">ثبت نشده</b>
          <?php endif; ?>
        </li>
      </ul>
      <?php if ($__botLink): ?>
        <p>لینک ربات: <a href="<?php echo $__botLink; ?>" target="_blank" style="direction:ltr;display:inline-block"><?php echo $__botLink; ?></a></p>
      <?php endif; ?>
    </div>

    <div style="flex:1;min-width:240px;background:#fffdf6;border:1px solid #beae9a;border-radius:8px;padding:14px">
      <h3 style="margin-top:0">عملیات سریع</h3>
      <form method="post" style="margin:6px 0">
        <input type="hidden" name="bale_action" value="test">
        <button class="btn btn-primary">تست اتصال (getMe)</button>
      </form>
      <form method="post" style="margin:6px 0">
        <input type="hidden" name="bale_action" value="webhook">
        <button class="btn btn-success">ثبت وب‌هوک</button>
      </form>
      <form method="post" style="margin:6px 0">
        <input type="hidden" name="bale_action" value="webhook_del">
        <button class="btn btn-secondary">حذف وب‌هوک</button>
      </form>
      <form method="post" style="margin:6px 0">
        <input type="hidden" name="bale_action" value="gen_secret">
        <button class="btn btn-warning">تغییر کلید وب‌هوک</button>
      </form>
      <?php if (is_array($__test)): ?>
        <div style="margin-top:10px;background:#f5f5f5;border-radius:6px;padding:8px;direction:ltr;text-align:left;font-size:12px;max-height:150px;overflow:auto">
          <?php echo htmlspecialchars(json_encode($__test, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ============ config form ============ -->
  <div style="background:#fffdf6;border:1px solid #beae9a;border-radius:8px;padding:14px;margin:14px 0">
    <h3 style="margin-top:0">تنظیمات بات بله</h3>
    <form method="post">
      <input type="hidden" name="bale_action" value="save">
      <table style="width:100%;line-height:2.4">
        <tr>
          <td style="width:220px">توکن بازو (از BotFather بله):</td>
          <td><input type="text" name="bale_token" value="<?php echo htmlspecialchars($__cfg['token'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;direction:ltr" placeholder="123456789:abcd..."></td>
        </tr>
        <tr>
          <td>توکن کیف‌پول پرداخت (provider_token):</td>
          <td><input type="text" name="bale_provider" value="<?php echo htmlspecialchars($__cfg['provider'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;direction:ltr" placeholder="WALLET-... (از BotFather → Payments)"></td>
        </tr>
        <tr>
          <td>نام کاربری بازو (بدون @):</td>
          <td><input type="text" name="bale_username" value="<?php echo htmlspecialchars($__cfg['username'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;direction:ltr" placeholder="MonefaLegendBot"></td>
        </tr>
        <tr>
          <td>ضریب تومان→ریال (قیمت × این عدد):</td>
          <td><input type="number" name="bale_multiplier" value="<?php echo (int)$__cfg['multiplier']; ?>" min="1" style="width:120px"></td>
        </tr>
        <tr>
          <td>کلید وب‌هوک (secret):</td>
          <td><input type="text" name="bale_secret" value="<?php echo htmlspecialchars($__cfg['secret'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;direction:ltr"></td>
        </tr>
        <tr><td></td><td><button class="btn btn-primary">ذخیره تنظیمات</button></td></tr>
      </table>
    </form>
    <p style="font-size:13px;color:#7a6a55">
      راهنما: در بله با @botfather بازو بسازید، توکن را اینجا ذخیره و «ثبت وب‌هوک» را بزنید.
      برای دریافت پول، از BotFather بخش Payments توکن کیف‌پول بگیرید.
      برای تست بدون پول واقعی می‌توانید از توکن آزمایشی <span dir="ltr">WALLET-TEST-1111111111111111</span> استفاده کنید.
    </p>
  </div>

  <!-- ============ payments log ============ -->
  <div style="background:#fffdf6;border:1px solid #beae9a;border-radius:8px;padding:14px;margin:14px 0">
    <h3 style="margin-top:0">آخرین پرداخت‌های بله</h3>
    <?php if (!count($__pays)): ?>
      <p>هنوز پرداختی از طریق بله ثبت نشده است.</p>
    <?php else: ?>
      <table class="table table-striped" style="width:100%;text-align:center">
        <tr><th>#</th><th>کاربر</th><th>طلا</th><th>قیمت (تومان)</th><th>وضعیت</th><th>تاریخ</th></tr>
        <?php foreach ($__pays as $__r):
            $__u = $database->queryFetch("SELECT username FROM users WHERE id = :i LIMIT 1", array('i' => (int)$__r['idUser']));
            $__done = !empty($__r['idTrans']);
        ?>
        <tr>
          <td><?php echo (int)$__r['id']; ?></td>
          <td><?php echo $__u ? htmlspecialchars($__u['username'], ENT_QUOTES, 'UTF-8') : (int)$__r['idUser']; ?></td>
          <td><?php echo (int)$__r['gAmount']; ?></td>
          <td><?php echo htmlspecialchars((string)$__r['cost'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo $__done ? '<b style="color:#2e7d32">پرداخت‌شده</b>' : '<span style="color:#e65100">در انتظار</span>'; ?></td>
          <td><?php echo date('Y/m/d H:i', (int)$__r['dTrans']); ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>

  <!-- ============ loyalty codes ============ -->
  <div style="background:#fffdf6;border:1px solid #beae9a;border-radius:8px;padding:14px;margin:14px 0">
    <h3 style="margin-top:0">کدهای اتصال طلای وفاداری (اخرین‌ها)</h3>
    <?php if (!count($__codes)): ?>
      <p>کدی ثبت نشده است.</p>
    <?php else: ?>
      <table class="table table-striped" style="width:100%;text-align:center">
        <tr><th>کد</th><th>کاربر</th><th>اتصال به بله</th></tr>
        <?php foreach ($__codes as $__r): ?>
        <tr>
          <td style="direction:ltr"><?php echo htmlspecialchars((string)$__r['shaker'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars((string)$__r['username'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo ((int)$__r['telid'] > 0) ? '<b style="color:#2e7d32">متصل</b>' : '<span style="color:#e65100">برای ارسال کد به ربات</span>'; ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>
</div>
