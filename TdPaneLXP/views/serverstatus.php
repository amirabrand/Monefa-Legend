<?php
/* T40: server status page (?p=serverstatus) - CPU / RAM / disk usage
   with auto-refresh. Data comes from ../sysinfo_data.php (same access-9
   auth as the rest of the panel). */
?>
<br />
<div class="card">
	<div class="card-header">وضعیت سرور <span id="t40statRefresh" style="float:left;cursor:pointer;color:#2e6da4;">⟳ به‌روزرسانی</span></div>
	<div class="card-body" id="t40statBox">در حال بارگذاری ...</div>
</div>

<script>
(function () {
	function t40Bar(pct, color) {
		pct = Math.max(0, Math.min(100, pct || 0));
		return '<div style="background:#e9ecef;border-radius:4px;height:16px;overflow:hidden;margin:3px 0 10px;">' +
			'<div style="width:' + pct + '%;height:16px;background:' + color + ';border-radius:4px;transition:width .4s;"></div></div>';
	}
	function t40Color(p) {
		p = p || 0;
		return p >= 90 ? '#dc3545' : (p >= 70 ? '#ffc107' : '#28a745');
	}
	function t40Up(s) {
		var d = Math.floor(s / 86400), h = Math.floor((s % 86400) / 3600), m = Math.floor((s % 3600) / 60);
		return d + ' روز و ' + h + ' ساعت و ' + m + ' دقیقه';
	}
	function t40Render(box, d) {
		var h = '';
		h += '<div class="row">';
		h += '<div class="col-md-4 mb-3"><b>پردازنده (CPU)</b>';
		h += '<div style="font-size:22px;font-weight:bold;color:' + t40Color(d.cpu) + '">' + (d.cpu === null ? '---' : d.cpu + '%') + '</div>';
		h += t40Bar(d.cpu, t40Color(d.cpu));
		h += '<small>بار سیستم: ' + d.load.join(' | ') + '</small></div>';
		h += '<div class="col-md-4 mb-3"><b>حافظه (RAM)</b>';
		h += '<div style="font-size:22px;font-weight:bold;color:' + t40Color(d.ram.pct) + '">' + d.ram.pct + '%</div>';
		h += t40Bar(d.ram.pct, t40Color(d.ram.pct));
		h += '<small>' + d.ram.used + ' گیگابایت از ' + d.ram.total + ' گیگابایت</small></div>';
		h += '<div class="col-md-4 mb-3"><b>هارد دیسک</b>';
		h += '<div style="font-size:22px;font-weight:bold;color:' + t40Color(d.disk.pct) + '">' + d.disk.pct + '%</div>';
		h += t40Bar(d.disk.pct, t40Color(d.disk.pct));
		h += '<small>' + d.disk.used + ' گیگابایت از ' + d.disk.total + ' گیگابایت</small></div>';
		h += '</div>';
		h += '<table class="table table-sm" style="max-width:560px;">';
		h += '<tr><td>Up time سرور</td><td>' + t40Up(d.uptime) + '</td></tr>';
		if (d.swap.total > 0) {
			h += '<tr><td>Swap</td><td>' + d.swap.used + ' از ' + d.swap.total + ' گیگابایت (' + d.swap.pct + '%)</td></tr>';
		} else {
			h += '<tr><td>Swap</td><td>غیرفعال</td></tr>';
		}
		h += '<tr><td>آخرین به‌روزرسانی</td><td>' + new Date().toLocaleTimeString('fa-IR') + '</td></tr>';
		h += '</table>';
		box.innerHTML = h;
	}
	function t40Load() {
		var box = document.getElementById('t40statBox');
		if (!box) return;
		var x = new XMLHttpRequest();
		x.open('GET', 'sysinfo_data.php?_=' + Date.now(), true);
		x.onload = function () {
			try {
				var d = JSON.parse(x.responseText);
				if (d.ok) { t40Render(box, d); } else { box.innerHTML = 'خطای دسترسی'; }
			} catch (e) { box.innerHTML = 'خطا در دریافت اطلاعات'; }
		};
		x.onerror = function () { box.innerHTML = 'خطا در ارتباط با سرور'; };
		x.send();
	}
	t40Load();
	setInterval(t40Load, 5000);
	var btn = document.getElementById('t40statRefresh');
	if (btn) btn.addEventListener('click', t40Load);
})();
</script>
