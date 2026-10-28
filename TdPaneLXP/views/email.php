<?php
/* T20 - Email server section (zai): status + mailbox info + DNS records + test send + log tail */

$mailini = @parse_ini_file('/var/www/monefa-secure/mail.ini');
if (!is_array($mailini)) { $mailini = ['mailbox'=>'admin@monefa.net','mailbox_pass'=>'','sender'=>'game@monefa.net','smtp_host'=>'mail.monefa.net','smtp_port_submission'=>'587','imap_host'=>'mail.monefa.net','imap_port'=>'993']; }

function zaiSvc($s){ return trim((string)@shell_exec('systemctl is-active '.escapeshellarg($s).' 2>/dev/null')) === 'active'; }
function zaiPort($h,$p,$t=3){ $c=@fsockopen($h,$p,$e,$s,$t); if($c){@fclose($c);return true;} return false; }

/* test send */
$sentOK = false; $sentErr = '';
if (isset($_POST['mailto']) && $_POST['mailto'] !== '') {
    $to = trim($_POST['mailto']);
    $subj = trim($_POST['mailsubj'] ?? '');
    $body = trim($_POST['mailbody'] ?? '');
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $sentErr = 'آدرس گیرنده معتبر نیست.';
    } else {
        try {
            $m = new \PHPMailer\PHPMailer\PHPMailer();
            $m->CharSet = 'UTF-8'; $m->IsHTML(true);
            $m->SetFrom($mailini['sender'], 'Monefa Legend');
            $m->AddAddress($to);
            $m->Subject = ($subj !== '' ? $subj : 'ایمیل تست - Monefa Legend');
            $m->MsgHTML('<div dir="rtl" style="font-family:Tahoma">' . ($body !== '' ? nl2br(htmlspecialchars($body)) : 'این ایمیل از پنل مدیریت سرور Monefa Legend ارسال شده است.') . '</div>');
            if ($m->Send()) { $sentOK = true; } else { $sentErr = $m->ErrorInfo; }
        } catch (\Throwable $ex) { $sentErr = $ex->getMessage(); }
    }
}

/* stats */
$queue = 0; foreach ((glob('/var/spool/postfix/deferred/*/*') ?: []) as $f) $queue++;
$today = 0; $day = date('Y-m-d');
$logTail = [];
if (is_readable('/var/log/mail.log')) {
    $lines = file('/var/log/mail.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines) {
        foreach ($lines as $l) { if (strpos($l, $day) !== false && strpos($l, 'status=sent (250') !== false) $today++; }
        $logTail = array_slice($lines, -15);
    }
}
$svcPostfix = zaiSvc('postfix'); $svcDovecot = zaiSvc('dovecot'); $svcDkim = zaiSvc('opendkim');
$p25  = zaiPort('127.0.0.1', 25); $p587 = zaiPort('127.0.0.1', 587); $p993 = zaiPort('127.0.0.1', 993);
function zaiBadge($ok, $y, $n){ return $ok ? '<span class="badge badge-success p-2">'.$y.'</span>' : '<span class="badge badge-danger p-2">'.$n.'</span>'; }
?>

<div class="card mb-3">
  <div class="card-header"><b>ایمیل سرور — وضعیت سامانه پستی</b></div>
  <div class="card-body">
    <table class="table table-bordered table-sm mb-0" style="direction:rtl">
      <tr><td>سرویس Postfix (ارسال)</td><td><?php echo zaiBadge($svcPostfix,'فعال','غیرفعال'); ?></td>
          <td>سرویس Dovecot (صندوق IMAP)</td><td><?php echo zaiBadge($svcDovecot,'فعال','غیرفعال'); ?></td>
          <td>سرویس OpenDKIM (امضا)</td><td><?php echo zaiBadge($svcDkim,'فعال','غیرفعال'); ?></td></tr>
      <tr><td>پورت SMTP محلی 25</td><td><?php echo zaiBadge($p25,'باز','بسته'); ?></td>
          <td>پورت ارسال کاربر 587</td><td><?php echo zaiBadge($p587,'باز','بسته'); ?></td>
          <td>پورت IMAPS 993</td><td><?php echo zaiBadge($p993,'باز','بسته'); ?></td></tr>
      <tr><td>پیام‌های معلق در صف</td><td><b><?php echo $queue; ?></b></td>
          <td>ارسال موفق امروز (250)</td><td><b><?php echo $today; ?></b></td>
          <td>فرستنده بازی</td><td><b><?php echo htmlspecialchars($mailini['sender']); ?></b></td></tr>
    </table>
  </div>
</div>

<div class="row">
<div class="col-md-6">
  <div class="card mb-3">
    <div class="card-header"><b>اطلاعات صندوق ایمیل ادمین</b></div>
    <div class="card-body">
      <table class="table table-sm" style="direction:rtl">
        <tr><td>آدرس صندوق</td><td><code><?php echo htmlspecialchars($mailini['mailbox']); ?></code></td></tr>
        <tr><td>رمز صندوق</td><td><code><?php echo htmlspecialchars($mailini['mailbox_pass']); ?></code></td></tr>
        <tr><td>دریافت (IMAP)</td><td><code><?php echo htmlspecialchars($mailini['imap_host'].':'.$mailini['imap_port']); ?></code> با SSL</td></tr>
        <tr><td>ارسال (SMTP)</td><td><code><?php echo htmlspecialchars($mailini['smtp_host'].':'.$mailini['smtp_port_submission']); ?></code> با STARTTLS</td></tr>
        <tr><td>نام کاربری</td><td><code>admin</code> (همان آدرس کامل هم قبول است)</td></tr>
      </table>
      <div class="alert alert-info mb-0" style="font-size:.9em">این صندوق با هر کلاینت استاندارد (Outlook / Thunderbird / Gmail اپ) کار می‌کند. آدرس‌های <code>game@</code> و <code>noreply@</code> و <code>webmaster@</code> همگی به همین صندوق منتقل می‌شوند.</div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><b>رکوردهای ایمیل DNS (monefa.net)</b></div>
    <div class="card-body">
      <table class="table table-sm" style="direction:rtl">
        <tr><td>MX</td><td><code>10 mail.monefa.net</code></td></tr>
        <tr><td>SPF</td><td><code>v=spf1 a mx ip4:89.42.137.124 -all</code></td></tr>
        <tr><td>DKIM</td><td><code>mail._domainkey.monefa.net</code> (RSA 2048، امضای خودکار)</td></tr>
        <tr><td>DMARC</td><td><code>v=DMARC1; p=quarantine; rua=mailto:admin@monefa.net</code></td></tr>
      </table>
      <div class="alert alert-warning mb-0" style="font-size:.9em">برای نمره کامل تحویل‌پذیری (Gmail/Outlook)، ارائه‌دهنده سرور باید رکورد معکوس (rDNS/PTR) آی‌پی 89.42.137.124 را روی <code>mail.monefa.net</code> تنظیم کند — این مورد فقط از پنل شرکت میزبانی قابل انجام است.</div>
    </div>
  </div>
</div>

<div class="col-md-6">
  <div class="card mb-3">
    <div class="card-header"><b>ارسال ایمیل آزمایشی</b></div>
    <div class="card-body">
      <?php if ($sentOK) { ?>
        <div class="alert alert-success">ایمیل با موفقیت به صف ارسال رفت و برای تحویل ارسال شد.</div>
      <?php } elseif ($sentErr !== '') { ?>
        <div class="alert alert-danger">خطا در ارسال: <?php echo htmlspecialchars($sentErr); ?></div>
      <?php } ?>
      <form method="post" action="index.php?p=email" style="direction:rtl">
        <input type="hidden" name="p" value="email">
        <div class="form-group"><label>به (آدرس گیرنده)</label>
          <input class="form-control" type="text" name="mailto" placeholder="someone@example.com" required></div>
        <div class="form-group"><label>موضوع</label>
          <input class="form-control" type="text" name="mailsubj" placeholder="ایمیل تست Monefa Legend"></div>
        <div class="form-group"><label>متن</label>
          <textarea class="form-control" name="mailbody" rows="3" placeholder="متن ایمیل (اختیاری)"></textarea></div>
        <button type="submit" class="btn btn-primary">ارسال آزمایشی</button>
      </form>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><b>آخرین رویدادهای لاگ ایمیل</b></div>
    <div class="card-body">
      <pre style="direction:ltr;text-align:left;font-size:.75em;max-height:260px;overflow:auto;background:#f8f9fa;border:1px solid #e9ecef;padding:8px"><?php
        echo htmlspecialchars(implode("\n", $logTail) ?: 'لاگ در دسترس نیست');
      ?></pre>
    </div>
  </div>
</div>
</div>
