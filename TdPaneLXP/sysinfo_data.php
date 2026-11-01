<?php
/* T40: live server health for the admin panel (CPU / RAM / disk).
   Same auth model as index.php: Account.php + access 9; everyone else
   gets a JSON 403. Pure /proc reads + PHP disk functions - no exec,
   no shell. Called from views/home.php and views/serverstatus.php. */
include("../application/Account.php");
header('Content-Type: application/json; charset=utf-8');
if ($session->access != 9) {
	echo json_encode(array('ok' => false, 'error' => 'auth'));
	die();
}

/* CPU%: two /proc/stat samples ~0.35s apart (idle+iowait vs total) */
function t40CpuLine()
{
	$s = @file_get_contents('/proc/stat');
	if ($s === false || strpos($s, "\n") === false) {
		return null;
	}
	$first = substr($s, 0, strpos($s, "\n"));
	$parts = preg_split('/\s+/', trim($first));
	array_shift($parts); /* drop the "cpu" label */
	$nums = array();
	foreach ($parts as $p) {
		$nums[] = floatval($p);
	}
	return $nums;
}

$cpu = null;
$a = t40CpuLine();
usleep(350000);
$b = t40CpuLine();
if ($a && $b && count($a) >= 4 && count($b) >= 4) {
	$dt = array_sum($b) - array_sum($a);
	$di = ($b[3] + (isset($b[4]) ? $b[4] : 0)) - ($a[3] + (isset($a[4]) ? $a[4] : 0));
	if ($dt > 0) {
		$cpu = round(max(0, min(100, (1 - $di / $dt) * 100)), 1);
	}
}

/* RAM + swap from /proc/meminfo (kB) */
$mem = array();
foreach (explode("\n", (string)@file_get_contents('/proc/meminfo')) as $ln) {
	if (preg_match('/^(\w+):\s+(\d+)\s*kB/', $ln, $m)) {
		$mem[$m[1]] = (int)$m[2];
	}
}
$ramTotal = isset($mem['MemTotal']) ? $mem['MemTotal'] : 0;
$ramAvail = isset($mem['MemAvailable']) ? $mem['MemAvailable'] : (isset($mem['MemFree']) ? $mem['MemFree'] : 0);
$ramUsed = max(0, $ramTotal - $ramAvail);
$swTotal = isset($mem['SwapTotal']) ? $mem['SwapTotal'] : 0;
$swFree = isset($mem['SwapFree']) ? $mem['SwapFree'] : 0;
$swUsed = max(0, $swTotal - $swFree);

/* Disk: web root lives on / */
$diskTotal = @disk_total_space('/');
$diskFree = @disk_free_space('/');
$diskUsed = ($diskTotal !== false && $diskFree !== false) ? max(0, $diskTotal - $diskFree) : 0;

/* load averages + uptime */
$load = array(0, 0, 0);
$la = trim((string)@file_get_contents('/proc/loadavg'));
if ($la !== '') {
	$lp = preg_split('/\s+/', $la);
	if (count($lp) >= 3) {
		$load = array(floatval($lp[0]), floatval($lp[1]), floatval($lp[2]));
	}
}
$up = 0;
$ul = trim((string)@file_get_contents('/proc/uptime'));
if ($ul !== '') {
	$up = (int)floatval($ul);
}

function t40KbToGb($kb)
{
	return round($kb / 1048576, 2);
}

echo json_encode(array(
	'ok' => true,
	'cpu' => $cpu,
	'load' => $load,
	'ram' => array(
		'used' => t40KbToGb($ramUsed),
		'total' => t40KbToGb($ramTotal),
		'pct' => $ramTotal > 0 ? round($ramUsed / $ramTotal * 100, 1) : 0,
	),
	'swap' => array(
		'used' => t40KbToGb($swUsed),
		'total' => t40KbToGb($swTotal),
		'pct' => $swTotal > 0 ? round($swUsed / $swTotal * 100, 1) : 0,
	),
	'disk' => array(
		'used' => $diskTotal !== false ? round($diskUsed / 1073741824, 2) : 0,
		'total' => $diskTotal !== false ? round($diskTotal / 1073741824, 2) : 0,
		'pct' => ($diskTotal !== false && $diskTotal > 0) ? round($diskUsed / $diskTotal * 100, 1) : 0,
	),
	'uptime' => $up,
));
