<?php
/* T30 — gold packages editor (coin amount + price), managed from admin panel.
   Writes buyfeature.buypacks JSON; ConfsData/config.php turns it into the
   $packages array used by the in-game shop, tgpay.php and balemini.php. */
$__msg30 = '';
$__err30 = '';
if (isset($_POST['gp_action']) && $_POST['gp_action'] === 'save') {
    $__rows30 = array();
    for ($i = 0; $i < 6; $i++) {
        $g = isset($_POST['gp_gold_' . $i]) ? (int)$_POST['gp_gold_' . $i] : 0;
        $p = isset($_POST['gp_price_' . $i]) ? (int)$_POST['gp_price_' . $i] : 0;
        if ($g <= 0 || $p <= 0) {
            $__err30 = 'مقادیر باید عددی و بزرگ‌تر از صفر باشند (بستهٔ ' . ($i + 1) . '). هیچ تغییری ذخیره نشد.';
            break;
        }
        $__rows30['buypacks' . $i] = array('gold' => (string)$g, 'price' => (string)$p);
    }
    if ($__err30 === '') {
        $database->query(
            "UPDATE buyfeature SET buypacks = :B WHERE id = 1",
            array('B' => json_encode($__rows30, JSON_UNESCAPED_UNICODE))
        );
        $__msg30 = 'بسته‌های طلا ذخیره شد و بلافاصله در فروشگاه بازی، بله‌پی و مینی‌اپ اعمال گردید.';
    }
}
$__row30 = $database->query("SELECT buypacks FROM buyfeature WHERE id = 1");
$__json30 = (is_array($__row30) && isset($__row30[0]['buypacks'])) ? json_decode($__row30[0]['buypacks'], true) : array();
if (!is_array($__json30)) $__json30 = array();
if (!class_exists('BaleApi')) {
    include_once(APP_BASE_PATH . '/library/Bale/BaleApi.php');
}
$__mult30 = 10;
try { $__c30 = BaleApi::config(); if (isset($__c30['multiplier'])) $__mult30 = (int)$__c30['multiplier']; } catch (Exception $e) {}
?>
<div class="contentWrapper">
  <h1 class="titleInHeader">بسته‌های طلا — تعداد و قیمت</h1>

  <?php if ($__msg30): ?>
    <div style="background:#e8f5e9;border:1px solid #4caf50;color:#1b5e20;padding:10px 14px;border-radius:6px;margin:10px 0"><?php echo $__msg30; ?></div>
  <?php endif; ?>
  <?php if ($__err30): ?>
    <div style="background:#ffebee;border:1px solid #f44336;color:#b71c1c;padding:10px 14px;border-radius:6px;margin:10px 0"><?php echo $__err30; ?></div>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="gp_action" value="save">
    <table class="table table-striped" style="background:#fffdf6;border:1px solid #beae9a">
      <thead>
        <tr>
          <th>بسته</th>
          <th>تعداد سکه طلا</th>
          <th>قیمت (تومان)</th>
          <th>مبلغ پرداختی مشتری</th>
          <th>وضعیت فروش در بله‌پی</th>
        </tr>
      </thead>
      <tbody>
      <?php for ($i = 0; $i < 6; $i++):
        $g = isset($__json30['buypacks' . $i]['gold']) ? (int)$__json30['buypacks' . $i]['gold'] : 0;
        $p = isset($__json30['buypacks' . $i]['price']) ? (int)$__json30['buypacks' . $i]['price'] : 0;
        $rial = $p * $__mult30; ?>
        <tr>
          <td>بستهٔ <?php echo $i + 1; ?></td>
          <td><input type="number" name="gp_gold_<?php echo $i; ?>" value="<?php echo $g; ?>" min="1" class="form-control" style="max-width:150px"></td>
          <td><input type="number" name="gp_price_<?php echo $i; ?>" value="<?php echo $p; ?>" min="1" class="form-control" style="max-width:150px"></td>
          <td dir="ltr"><?php echo number_format($rial); ?> Rial</td>
          <td><?php if ($rial >= 10000) { echo '<span style="color:#2e7d32">✔ قابل فروش</span>'; } else { echo '<span style="color:#b71c1c">✘ زیر حداقل بله (۱۰٬۰۰۰ ریال)</span>'; } ?></td>
        </tr>
      <?php endfor; ?>
      </tbody>
    </table>
    <button class="btn btn-primary">ذخیرهٔ تغییرات</button>
  </form>

  <p style="margin-top:12px;color:#555;line-height:2">
    توجه: «مبلغ پرداختی مشتری» = قیمت (تومان) × ضریب تومان→ریال (فعلاً <?php echo $__mult30; ?>).
    حداقل مبلغ فاکتور در بله‌پی ۱۰٬۰۰۰ ریال است؛ بسته‌های زیر این مقدار در فروشگاه و مینی‌اپ غیرفعال می‌مانند.
    قیمت‌ها بلافاصله بعد از ذخیره در همه‌جا (فروشگاه داخل بازی، بله‌پی و مینی‌اپ بله) اعمال می‌شوند.
  </p>
</div>
