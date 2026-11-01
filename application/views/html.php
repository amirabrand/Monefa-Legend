<?php
/**
 * @package	programing
 * https://www.lotryka.com/
 * https://www.youtube.com/@lutryka/
 * https://www.instagram.com/@lutryka/
 * https://www.x.com/@lutryka/
 * https://www.x.com/@lutryka/
 *  طراحی و بیاده سازیه شده توسط حمید مرادی برنامه نویس و فرانت نویس 1 سال زمان برد تا این سیستم تراوین لجند رو طراحی کنم
 *  design projct 2024 no License free work 1 year i had design to travian legend no buge first game browser with and full project no body to design like this
 *  Designed and updated by Hamid Moradi, a programmer and front-end writer
 */
?>
<?php
/* T29 - server-side mobile/tablet detection (single source of truth).
   mobile-live.js v9 re-checks at runtime and stays in sync, but the
   correct layout canvas is now guaranteed from first paint - even if
   JS is slow, blocked, or touch is not reported (in-app webviews). */
$t29ua = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
$t29phone = (strpos($t29ua, 'android') !== false && strpos($t29ua, 'mobile') !== false)
    || strpos($t29ua, 'iphone') !== false
    || strpos($t29ua, 'ipod') !== false
    || strpos($t29ua, 'windows phone') !== false
    || strpos($t29ua, 'iemobile') !== false;
$t29tablet = !$t29phone && (
    strpos($t29ua, 'android') !== false
    || strpos($t29ua, 'ipad') !== false
    || strpos($t29ua, 'tablet') !== false
    || strpos($t29ua, 'kindle') !== false
    || strpos($t29ua, 'silk') !== false);

/* T33 - webview hardening (Bale mini-app): Bale's desktop client embeds
   pages in a webview that sends a plain Windows UA, so auto-detection
   yields the 1280 desktop canvas inside a small window (broken layout).
   a) any UA that identifies itself as Bale / ble.ir counts as a phone;
   b) sticky override cookie "t26m" (1=mobile, 0=desktop, 90 days), set
      by balemini.php or via ?t26m=1|0 - it wins over every heuristic. */
$t26ov = isset($_GET['t26m']) ? $_GET['t26m'] : (isset($_COOKIE['t26m']) ? $_COOKIE['t26m'] : '');
if ($t26ov === '1' || $t26ov === '0') {
    if (!isset($_COOKIE['t26m']) || $_COOKIE['t26m'] !== $t26ov) {
        @setcookie('t26m', $t26ov, array('expires' => time() + 7776000, 'path' => '/', 'samesite' => 'Lax'));
        $_COOKIE['t26m'] = $t26ov;
    }
}
if ($t26ov === '1') {
    $t29phone = true;
    $t29tablet = false;
} elseif ($t26ov === '0') {
    $t29phone = false;
    $t29tablet = false;
} elseif (strpos($t29ua, 'bale') !== false || strpos($t29ua, 'ble.ir') !== false) {
    $t29phone = true;
    $t29tablet = false;
}
?>
<!DOCTYPE html>
<html<?php echo $t29phone ? ' class="t26m"' : ''; ?>>

<head><meta charset="utf-8">
    <meta name="viewport" id="t26vp" content="width=device-width, initial-scale=1">
    <script>/* T31: universal viewport fixer (runs before first paint).
       1) explicit initial-scale: Chrome Android restores per-site zoom on
          repeat visits and rendered the 620 canvas at scale 1.0 (cropped);
       2) screen-based fallback: Chrome "Desktop site" mode sends a desktop
          UA (X11/Linux) so PHP sees a desktop - real phones are caught via
          touch + min(screen)<=620. */
    (function(){
      var F=null;try{F=window.localStorage.getItem('t26force')}catch(e){}
      if(F==='0')return;
      var s=window.screen||{},w=parseInt(s.width,10)||9e9,h=parseInt(s.height,10)||9e9,m=Math.min(w,h);
      if(m>=9e9){m=document.documentElement.clientWidth||390}
      var touch=('ontouchstart' in window)||(navigator.maxTouchPoints||0)>0;
      var phpMob=<?php echo $t29phone?'1':'0';?>,phpTab=<?php echo $t29tablet?'1':'0';?>;window.__t26php=phpMob;
      var mob=phpMob||((F==='1')||(touch&&m<=620)||(m<=480));
      var cw=mob?620:1280;
      var sc=Math.min(1,m/cw);if(!isFinite(sc)||sc<=0)sc=1;
      var vp=document.getElementById('t26vp');
      if(vp)vp.setAttribute('content','width='+cw+', initial-scale='+sc.toFixed(4)+', user-scalable=yes, viewport-fit=cover');
      if(mob)document.documentElement.classList.add('t26m');
      var a=function(){try{document.body.classList.add('mobileOptimized')}catch(e){}};
      if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',a)}else{a()}
    })();
    </script>
    <script>/* t26NativeMobileConfig */
    window.Travian = window.Travian || {};
    Travian.Game = Travian.Game || {};
    Travian.Game.Layout = Travian.Game.Layout || {};
    Travian.Game.Layout.MobileOptimizations = {
        enabled: true,
        breakPoint: 620,
        viewportSize: { width: (window.outerWidth || window.innerWidth),
                        height: (window.outerHeight || window.innerHeight) }
    };
    </script>
    <title><?= SERVER_NAME ?></title>
    <meta http-equiv="cache-control" content="max-age=0"/>
    <meta http-equiv="pragma" content="no-cache"/>
    <meta http-equiv="expires" content="0"/>
    <meta http-equiv="imagetoolbar" content="no"/>
    
    <meta name="content-language" content="<?php echo LANG_UR; ?>"/>
    <link href="<?= GP_LOCATION ?>lang/<?php echo LANG_UR; ?>/compact.css" rel="stylesheet" type="text/css"/>
    <link href="<?= GP_LOCATION ?>lang/<?php echo LANG_UR; ?>/lang.css" rel="stylesheet" type="text/css"/>
	<link href="<?= GP_LOCATION ?>lang/<?php echo LANG_UR; ?>/fixes.css?rev13" rel="stylesheet" type="text/css"/>
    <link href="/gpackcdn/gpack/mobile-responsive.css?v=9" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="<?= GP_LOCATION1 ?>default/jquery-3.2.1.min.js"></script>
    <script type="text/javascript" src="<?= GP_LOCATION1 ?>default/jquery.md5.min.js"></script>
   
          
    <script type="text/javascript" src="<?= GP_LOCATION1 ?>default/d3/d3.min.js"></script>
    <script type="text/javascript" src="<?= GP_LOCATION1 ?>default/d3/d3pie.min.js"></script>
    <script type="text/javascript" src="<?= GP_LOCATION1 ?>default/gsap/minified/TweenMax.min.js"></script>
    <script type="text/javascript" src="<?= GP_LOCATION1 ?>default/gsap/minified/plugins/MorphSVGPlugin.min.js"></script>
    <script type="text/javascript" src="<?= GP_LOCATION1 ?>Game/General/General.js"></script>
<script type="text/javascript" src="<?= GP_LOCATION1 ?>disable.js"></script>









	<script type="text/javascript">
		var j$ = $.noConflict();
	</script>
    <script type="text/javascript">
        window.TravianDefaults = {
            Map: { Size: { width: 201, height: 201, left: -100, right: 100, bottom: -100, top: 100 } }
        };
        function getMapSize() {return 100;}
        function getAutoReloadStatus(){return <?php echo $session->autoreload;?> ;}
    </script>

    <script type="text/javascript" src="crypt.js"></script>
    <link rel="icon" href="favicon.ico" type="image/x-icon"/>

    <script type="text/javascript">
        Travian.Translation.add({
            'allgemein.anleitung': '<?php echo $lang['Manual']['Instructions']; ?>',
            'allgemein.cancel': 'Cancel',
            'allgemein.ok': 'OK',
            'allgemein.close': '<?php echo $lang['Daily']['Close']; ?>',
            'cropfinder.keine_ergebnisse': 'There are no results'
        });
        Travian.applicationId = 'T4.4 Game';
        Travian.Game.version = '4.4';
        Travian.Game.worldId = 'vip7';
        Travian.Game.speed = <?php echo SPEED; ?>;
        Travian.Templates = {};
        Travian.Templates.ButtonTemplate = "<button >\n\t<div class=\"button-container addHoverClick\">\n\t\t<div class=\"button-background\">\n\t\t\t<div class=\"buttonStart\">\n\t\t\t\t<div class=\"buttonEnd\">\n\t\t\t\t\t<div class=\"buttonMiddle\"><\/div>\n\t\t\t\t<\/div>\n\t\t\t<\/div>\n\t\t<\/div>\n\t\t<div class=\"button-content\"><\/div>\n\t<\/div>\n<\/button>\n";
    </script>
    <script type="text/javascript">
        Travian.Game.eventJamHtml = '&lt;a href=&quot;# target=&quot;blank&quot; title=&quot;Response&zwnj;Travian&quot;&gt;&lt;span class=&quot;c0 t&quot;&gt;0:00:0&lt;/span&gt;?&lt;/a&gt;'.unescapeHtml();
                Travian.Game.eventJamHtml = '&lt;a href=&quot;javascript:void&quot; onclick=&quot;document.location.reload();&quot;&gt;&lt;img src=&quot;<?=GP_LOCATION2;?>refresh.png&quot;&gt;&lt;/a&gt;'.unescapeHtml();
            </script>


</head>
<script type="text/javascript">
    window.ajaxToken = '765784eba4d5b4156277301c5674432faca910fc';
    window._player_uuid = '';
    Travian.Game.Preferences.initialize({"highlightsToggle":"true","travian_toggle_hero":"expanded","travian_toggle_infobox":"expanded","travian_toggle_villagelist":"collapsed","travian_toggle_alliance":"expanded","WMBlueprints":"[]","QuestDialogPosition":"{\"position\":null,\"preferenceKey\":\"QuestDialogPosition\"}"});
    Travian.Game.Quest.init();
</script>

<script type="application/javascript">
    Travian.Game.Preferences.initialize({"adSalesVideoSuccessDialogDisabled":"0","allianceReportsFilter":"{\"attackTypes\":[\"16\",\"5\",\"4\",\"3\",\"2\",\"1\",\"19\",\"18\",\"17\",\"15\",\"7\"],\"noOwnAllianceAttacks\":true}","buildingDescriptionCollapsed":"false","entriesPerPage":"10","flagAttributesBoxOpen":"1","lastUsedPaymentMethod":"PAYPAL","marketplaceOffersPerPage":"10","mobileUnitDisplay":"collapsed","shopCountry":"AAB","snowAnimation":"{}","travian_toggle_infobox":"expanded","travian_toggle_villagelist":"collapsed","troopMovementsPerPage":"10"});
    Travian.Game.PaymentWizardEventListener.defaultOptions = {"shopUIVersion":4,"cssClass":"paymentShopV4","data":{"activeTab":"buyGold"}};
</script>
        <iframe name="__cmpLocator" style="display: none;"></iframe><iframe name="__uspapiLocator" style="display: none;"></iframe><iframe name="__tcfapiLocator" style="display: none;"></iframe><iframe name="__gppLocator" style="display: none;"></iframe>        <div id="reactDialogWrapper"></div>

<?php
if($session->uid){
    $p11=array("VV"=>$_SERVER['REMOTE_ADDR'] , "II"=>$session->uid);
    $database->query("UPDATE users set `ip` = :VV where id = :II",$p11);
}


if (basename($_SERVER['PHP_SELF']) !== 'captcha.php' && isset($session->uid)) {
    // Redirect to captcha.php if not already there
    $fullUrl = $_SERVER['REQUEST_URI'];
    $_SESSION['cappage']=$fullUrl;
}
//echo ($_SESSION['CAPTCHATIME']<time());
//die();
if(isset($_SESSION['captime'])&&$_SESSION['captime']<time()&& isset($session->uid)){
    //$session->destroy();
    if (basename($_SERVER['PHP_SELF']) !== 'captcha.php'  && basename($_SERVER['PHP_SELF']) !== 'messages.php'&& !(isset($_GET['ok']) && basename($_SERVER['PHP_SELF']) == 'dorf1.php')) {
        // Redirect to captcha.php if not already there
        $_SESSION['cappage']=basename($_SERVER['PHP_SELF']);
        header('Location: ./captcha.php');
        die();
    }
    
}
//echo ($_SESSION['CAPTCHATIME']<time());
//die();

if(isset($session->uid) && basename($_SERVER['PHP_SELF']) !== 'captcha.php' && basename($_SERVER['PHP_SELF']) !== 'messages.php'&& !(isset($_GET['ok']) && basename($_SERVER['PHP_SELF']) == 'dorf1.php')){
    ?>
<script>
   if (<?php echo $_SESSION['captime'] < time() ? 'true' : 'false'; ?>) {
    window.location.href = './captcha.php';
}

    let consoleOpened = false;

    // Interval monitoring of console open state
    function monitorConsoleOpen() {
        let lastGapH = window.outerHeight - window.innerHeight;
        let lastGapW = window.outerWidth - window.innerWidth;

        setInterval(() => {
            const gapH = window.outerHeight - window.innerHeight;
            const gapW = window.outerWidth - window.innerWidth;
            const consoleGap = (gapH - lastGapH > 150) || (gapW - lastGapW > 150);
            lastGapH = gapH;
            lastGapW = gapW;
            if (
                consoleGap ||
                isConsoleOpenViaDebugger() ||
                detectConsoleThroughTiming()
            ) {
                if (!consoleOpened) {
                    consoleOpened = true;
                    sendDetectionData(consoleOpened);
                }
            }
        }, 2000); // Adjust interval as needed
    }

    // Debugger detection
    function isConsoleOpenViaDebugger() {
        let opened = false;
        const start = performance.now();
        debugger; // This triggers only if the console is open
        const end = performance.now();
        if (end - start > 100) { // Adjust threshold as needed
            opened = true;
        }
        return opened;
    }

    // Console timing check
    function detectConsoleThroughTiming() {
        const start = performance.now();
        console.log('Checking console...');
        const end = performance.now();
        return end - start > 200; // Adjust this threshold as needed
    }

    // Collect browser data
    function collectBrowserData(consoleOpenedStatus = false) {
        return {
            userAgent: navigator.userAgent,
            languages: navigator.languages ? navigator.languages.join(',') : navigator.language,
            isWebdriver: navigator.webdriver ? 1 : 0,
            platform: navigator.platform || '',
            isIE: /MSIE|Trident/.test(navigator.userAgent),
            consoleOpened: consoleOpenedStatus
        };
    }

    // Send detection data
    function sendDetectionData(consoleStatus = false) {
        const browserData = collectBrowserData(consoleStatus);
        fetch('/botdetector.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(browserData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Server error: ${response.status}`);
            }
            return response.text();
        })
        .then(score => {
            console.log('Bot detection score:', score);
            if (parseFloat(score) < 0.5) {
              //  alert('Got you!');
                window.location.href = './captcha.php';
            } else {
              //  alert('You have passed the bot check.');
            }
        })
        .catch(error => console.error('Error during fetch:', error));
    }

    // Initial data sending and console monitoring setup
    sendDetectionData();
    monitorConsoleOpen();  // Initialize console open detection monitoring
</script>
    <?php

}
?>





    <style>
                         
  .importantStyle {
      top: 5px !important;
  }

        @font-face {
	font-family: IRANSans;
	font-style: normal;
	font-weight: bold;
	src: url('../fonts/IRANSansWeb_Bold.eot');
	src: url('../fonts/IRANSansWeb_Bold.eot@#iefix') format('embedded-opentype'),  /* IE6-8 */
		 url('../fonts/IRANSansWeb_Bold.woff2') format('woff2'),  /* FF39+,Chrome36+, Opera24+*/
		 url('../fonts/IRANSansWeb_Bold.woff') format('woff'),  /* FF3.6+, IE9, Chrome6+, Saf5.1+*/
		 url('../fonts/IRANSansWeb_Bold.ttf') format('truetype');
}
@font-face {
	font-family: IRANSans;
	font-style: normal;
	font-weight: 500;
	src: url('../fonts/IRANSansWeb_Medium.eot');
	src: url('../fonts/IRANSansWeb_Medium.eot@#iefix') format('embedded-opentype'),  /* IE6-8 */
		 url('../fonts/IRANSansWeb_Medium.woff2') format('woff2'),  /* FF39+,Chrome36+, Opera24+*/
		 url('../fonts/IRANSansWeb_Medium.woff') format('woff'),  /* FF3.6+, IE9, Chrome6+, Saf5.1+*/
		 url('../fonts/IRANSansWeb_Medium.ttf') format('truetype');
}
@font-face {
	font-family: IRANSans;
	font-style: normal;
	font-weight: 300;
	src: url('../fonts/IRANSansWeb_Light.eot');
	src: url('../fonts/IRANSansWeb_Light.eot@#iefix') format('embedded-opentype'),  /* IE6-8 */
		 url('../fonts/IRANSansWeb_Light.woff2') format('woff2'),  /* FF39+,Chrome36+, Opera24+*/
		 url('../fonts/IRANSansWeb_Light.woff') format('woff'),  /* FF3.6+, IE9, Chrome6+, Saf5.1+*/
		 url('../fonts/IRANSansWeb_Light.ttf') format('truetype');
}
@font-face {
	font-family: IRANSans;
	font-style: normal;
	font-weight: 200;
	src: url('../fonts/IRANSansWeb_UltraLight.eot');
	src: url('../fonts/IRANSansWeb_UltraLight.eot@#iefix') format('embedded-opentype'),  /* IE6-8 */
		 url('../fonts/IRANSansWeb_UltraLight.woff2') format('woff2'),  /* FF39+,Chrome36+, Opera24+*/
		 url('../fonts/IRANSansWeb_UltraLight.woff') format('woff'),  /* FF3.6+, IE9, Chrome6+, Saf5.1+*/
		 url('../fonts/IRANSansWeb_UltraLight.ttf') format('truetype');
}
@font-face {
	font-family: IRANSans;
	font-style: normal;
	font-weight: normal;
	src: url('../fonts/IRANSansWeb.eot');
	src: url('../fonts/IRANSansWeb.eot@#iefix') format('embedded-opentype'),  /* IE6-8 */
		 url('../fonts/IRANSansWeb.woff2') format('woff2'),  /* FF39+,Chrome36+, Opera24+*/
		 url('../fonts/IRANSansWeb.woff') format('woff'),  /* FF3.6+, IE9, Chrome6+, Saf5.1+*/
		 url('../fonts/IRANSansWeb.ttf') format('truetype');
}
    </style>

<style id="t25uifix">
/* T25: content-title close (X) button was 0x0 / invisible on pages without inline SVG */
.contentTitle{position:relative;}
.contentTitle #closeContentButton{
  width:26px;height:26px;min-width:26px;min-height:26px;
  display:block !important;
  cursor:pointer;z-index:75;
  position:absolute !important;top:10px !important;left:28px !important;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3E%3Cpath d='M4 4l12 12M16 4L4 16' stroke='%23ffffff' stroke-width='2.8' stroke-linecap='round'/%3E%3C/svg%3E") !important;
  background-repeat:no-repeat !important;
  background-position:center !important;
  background-size:13px 13px !important;
  background-color:#6aa63b !important;
  border:1px solid #4e7d2a;
  border-radius:4px;
  box-shadow:0 1px 2px rgba(0,0,0,.25);
}
.contentTitle #answersButton{
  position:absolute !important;top:10px !important;left:62px !important;
  width:26px;height:26px;z-index:74;
  background-color:#6aa63b !important;
  border:1px solid #4e7d2a;
  border-radius:4px;
}
</style>
<script src="/gpackcdn/js/Game/General/shopui-live.js?v=2"></script>
<script src="/gpackcdn/js/Game/General/mobile-live.js?v=15"></script>
