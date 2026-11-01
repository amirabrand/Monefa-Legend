<div class="card">
        <div class="card-header"><?php echo $lang['Admin']['home01']; ?></div>
        <div class="card-body">
        <?php echo $lang['Admin']['home02']; ?> <b><?php echo $_SESSION['username']; ?></b>, <?php echo $lang['Admin']['home03']; ?>: <b><font color="Red"><?php echo $lang['Admin']['home04']; ?></font></b>
        </div>
</div><br>
<div class="card">
        <div class="card-header">دسترسی سریع</div>
        <div class="card-body">
         <a href="?p=setting">تنظیمات عمومی</a><br><a href="?p=plus">تنظیمات پلاس</a><br><a href="?p=register">غیرفعال کردن ثبت‌نام</a><br>
        <a href="?p=reset3">نصب مجدد</a>
        </div>
</div><br>
<div class="card">
        <div class="card-header">وضعیت سرور (CPU / RAM / هارد) <a href="?p=serverstatus" style="float:left;font-size:12px;">صفحه کامل »</a></div>
        <div class="card-body" id="t40sysinfo">در حال بارگذاری ...</div>
</div>
<script>
(function () {
        function t40Bar(pct, color) {
                pct = Math.max(0, Math.min(100, pct || 0));
                return '<div style="background:#e9ecef;border-radius:4px;height:12px;overflow:hidden;margin:3px 0 8px;">' +
                        '<div style="width:' + pct + '%;height:12px;background:' + color + ';border-radius:4px;transition:width .4s;"></div></div>';
        }
        function t40Color(p) {
                p = p || 0;
                return p >= 90 ? '#dc3545' : (p >= 70 ? '#ffc107' : '#28a745');
        }
        function t40Render(box, d) {
                var h = '<div style="display:flex;flex-wrap:wrap;gap:18px;">';
                h += '<div style="flex:1;min-width:150px;"><b>پردازنده</b> <b style="color:' + t40Color(d.cpu) + '">' + (d.cpu === null ? '---' : d.cpu + '%') + '</b>' + t40Bar(d.cpu, t40Color(d.cpu)) + '</div>';
                h += '<div style="flex:1;min-width:150px;"><b>حافظه</b> <b style="color:' + t40Color(d.ram.pct) + '">' + d.ram.pct + '%</b>' + t40Bar(d.ram.pct, t40Color(d.ram.pct)) + '<small>' + d.ram.used + ' از ' + d.ram.total + ' GB</small></div>';
                h += '<div style="flex:1;min-width:150px;"><b>هارد</b> <b style="color:' + t40Color(d.disk.pct) + '">' + d.disk.pct + '%</b>' + t40Bar(d.disk.pct, t40Color(d.disk.pct)) + '<small>' + d.disk.used + ' از ' + d.disk.total + ' GB</small></div>';
                h += '</div>';
                box.innerHTML = h;
        }
        function t40Load() {
                var box = document.getElementById('t40sysinfo');
                if (!box) return;
                var x = new XMLHttpRequest();
                x.open('GET', 'sysinfo_data.php?_=' + Date.now(), true);
                x.onload = function () {
                        try {
                                var d = JSON.parse(x.responseText);
                                if (d.ok) { t40Render(box, d); }
                        } catch (e) {}
                };
                x.send();
        }
        t40Load();
        setInterval(t40Load, 10000);
})();
</script>


<div class="row mt-3">
        <div class="col-md-4 mb-3">
                <div class="card">
                        <div class="card-header"><?php echo $lang['Admin']['home05']; ?></div>
                        <div class="card-body">
                                <div class="card-title"><b class="h2 text-danger"><?php echo $database->queryNumRow('SELECT * FROM users'); ?></b> <?php echo $lang['Admin']['home06']; ?></div> 
                                <p class="card-text"><?php echo $lang['Admin']['home07']; ?>.</p>
                        </div>
                </div>
        </div>
        <div class="col-md-4 mb-3">
                <div class="card">
                        <div class="card-header"><?php echo $lang['Admin']['home17']; ?></div>
                        <div class="card-body">
                                <div class="card-title"><b class="h2 text-danger"><?php echo count($admin->getUserActive()); ?></b> <?php echo $lang['Admin']['Player']; ?></div> 
                                <p class="card-text"><?php echo $lang['Admin']['home18']; ?>.</p>
                        </div>
                </div>
        </div>

        <div class="col-md-4 mb-3">
                <div class="card">
                        <div class="card-header"><?php echo $lang['Admin']['home08']; ?></div>
                        <div class="card-body">
                                <div class="card-title"><b class="h2 text-danger"><?php echo $database->queryNumRow('SELECT * FROM payments'); ?></b> <?php echo $lang['Admin']['home09']; ?></div> 
                                <p class="card-text"><?php echo $lang['Admin']['home10']; ?>.</p>
                        </div>
                </div>
        </div>
        <div class="col-md-4 mb-3">
                <div class="card">
                        <div class="card-header"><?php echo $lang['Admin']['home11']; ?></div>
                        <div class="card-body">
                                <?php 
                                        $fullM = 0;
                                        $paymentsD = $database->query('SELECT cost FROM payments');

                                        foreach($paymentsD as $payment){
                                                $fullM = $fullM + $payment['cost'];
                                        }
                                ?>
                                <div class="card-title"><b class="h2 text-danger"><?php echo $fullM; ?></b>$</div> 
                                <p class="card-text"><?php echo $lang['Admin']['home12']; ?>.</p>
                        </div>
                </div>
        </div>

        <div class="col-md-4 mb-3">
                <div class="card">
                        <div class="card-header"><?php echo $lang['Admin']['home13']; ?></div>
                        <div class="card-body">
                                <div class="card-title"><b class="h2 text-danger"><?php echo $database->queryNumRow('SELECT * FROM support'); ?></b> <?php echo $lang['Admin']['Message']; ?></div> 
                                <p class="card-text"><?php echo $lang['Admin']['home14']; ?>.</p>
                        </div>
                </div>
        </div>
        <div class="col-md-4 mb-3">
                <div class="card">
                        <div class="card-header"><?php echo $lang['Admin']['home15']; ?></div>
                        <div class="card-body">
                        <?php 
                                        $fullG = 0;
                                        $goldD = $database->query('SELECT gold FROM users');

                                        foreach($goldD as $gold){
                                                $fullG = $fullG + $gold['gold'];
                                        }
                                ?>
                                <div class="card-title"><b class="h2 text-danger"><?php echo number_format($fullG); ?></b> <?php echo $lang['Admin']['Gold']; ?></div> 
                                <p class="card-text"><?php echo $lang['Admin']['home16']; ?>.</p>
                        </div>
                </div>
        </div>

</div>