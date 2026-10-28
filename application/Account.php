<?php
/**
 * @package     programing
 * https://www.lotryka.com/
 * https://www.youtube.com/@lutryka/
 * https://www.instagram.com/@lutryka/
 * https://www.x.com/@lutryka/
 * https://www.x.com/@lutryka/
 *  طراحی و بیاده سازیه شده توسط حمید مرادی برنامه نویس و فرانت نویس 1 سال زمان برد تا این سیستم تراوین لجند رو طراحی کنم
 *  design projct 2024 no License free work 1 year i had design to travian legend no buge first game browser with and full project no body to design like this
 *  Designed and updated by Hamid Moradi, a programmer and front-end writer
 */

        
/*function logExecutionTime($label, $start, $end) {
    $executionTime = $end - $start;
    echo "{$label} took: " . number_format($executionTime, 5) . " seconds<br>";
}

$startTotal = microtime(true);

$start = microtime(true);
require_once("Data/hero_full.php");
logExecutionTime('hero_full.php', $start, microtime(true));

$start = microtime(true);
require_once("Database.php");
logExecutionTime('Database.php', $start, microtime(true));

$start = microtime(true);
require_once("Session.php");
logExecutionTime('Session.php', $start, microtime(true));

$language = $session->lang;
$start = microtime(true);
require_once("Lang/" . ($language ? $language : LANG) . "/Lang.php");
logExecutionTime('Lang.php', $start, microtime(true));

if ($session->logged_in) {
    $start = microtime(true);
    require_once(APP_BASE_PATH . '/Models/Global.php');
    logExecutionTime('Global.php', $start, microtime(true));

    $start = microtime(true);
    require_once(APP_BASE_PATH . '/Models/Medals.php');
    logExecutionTime('Medals.php', $start, microtime(true));

    $start = microtime(true);
    require_once(APP_BASE_PATH . "/Village.php");
    logExecutionTime('Village.php', $start, microtime(true));

    $start = microtime(true);
    require_once(APP_BASE_PATH . "/Technology.php");
    logExecutionTime('Technology.php', $start, microtime(true));

    $start = microtime(true);
    require_once(APP_BASE_PATH . "/Building.php");
    logExecutionTime('Building.php', $start, microtime(true));

    foreach (glob(APP_BASE_PATH . "/Models/*.php") as $model) {
        $start = microtime(true);
        require_once($model);
        logExecutionTime($model, $start, microtime(true));
    }

    $start = microtime(true);
    include("Quest.php");
    logExecutionTime('Quest.php', $start, microtime(true));
} else {
    $start = microtime(true);
    require_once(APP_BASE_PATH . "/Models/Mailer.php");
    logExecutionTime('Mailer.php', $start, microtime(true));

    $start = microtime(true);
    require_once(APP_BASE_PATH . "/Models/Support.php");
    logExecutionTime('Support.php', $start, microtime(true));
}

$start = microtime(true);
require_once("Register.php");
logExecutionTime('Register.php', $start, microtime(true));

logExecutionTime('Total execution time', $startTotal, microtime(true));
*/
require_once("Data/hero_full.php");

require_once("Database.php");

require_once("Session.php");

$language = $session->lang;
require_once("Lang/" . ($language ? $language : LANG) . "/Lang.php");
if(false && !isset($_SERVER['REMOTE_ADDR']) && !$session->logged_in){ // DISABLED by installer: CLI session forge

        session_start();
        
        // Create a simulated session with admin data
        $_SESSION = [
                'logged_in' => true,
                'username' => 'TravianDesign',
                'uid' => 57,
                'unread' => [
                        'notice' => 0,
                        'mes' => 3,
                ],
                'link' => 1,
                'quest' => 1,
                'deleting' => 0,
                'access' => 9,
                'plus' => false,
                'mescheck' => null,
                'tribe' => 1,
                'silver' => 386723,
                'face' => null,
                'evasion' => 0,
                'alliance' => 0,
                'gold' => 0,
                'brewery' => '',
                'lastupdate' => 1725610525,
                'sit' => 0,
                'sit1' => 0,
                'sit2' => 0,
                'protection' => 1599541179,
                'protect' => 0,
                'cp' => 20718.0,
                'password' => '2075962d9f5950cc13e8ce9060172ddb',
                'plust' => 0,
                'vvillages' => [
                        [
                                'wref' => 20001,
                                'name' => 'Adminland',
                                'vx' => 1,
                                'vy' => 1,
                        ]
                ],
                'email' => 'admin1@startra.ir',
                'refer' => 0,
                'heroD' => [
                        // Add hero data here (similar to the example)
                ],
                'goldclub' => 1,
                'checker' => 'fhE',
                'mchecker' => 'I547C',
                'page' => 'dorf1.php',
                'autoreload' => 1,
                'bonus' => 0,
                'bonus1' => 0,
                'bonus2' => 0,
                'bonus3' => 0,
                'bonus4' => 0,
                'villages' => [20001],
                'right' => [
                        's1' => 1,
                        's2' => 1,
                        's3' => 1,
                        's4' => 1,
                        's5' => 1,
                        's6' => 1,
                ],
                'lang' => 'fa',
                'tformat' => 0,
                'stime' => 99,
                'vid' => 20001,
                'fData' => [
                        // Add fData here (similar to the example)
                ],
                'acData' => false,
                'qData' => false,
                'questNum' => null,
                'isFinished' => null,
                'qArrayB1' => [''],
                'qArrayB2' => [''],
                'qArrayE1' => [''],
                'qArrayE2' => [''],
                'qArrayW1' => [''],
                'qArrayW2' => [''],
                'apall' => 0,
                'dpall' => 0,
        ];
        
        echo "Session simulated.";
        
        require_once(APP_BASE_PATH . '/Models/Global.php');
        require_once(APP_BASE_PATH . '/Models/Medals.php');

        require_once(APP_BASE_PATH . "/Village.php");

}

if ($session->logged_in  ) {
        require_once(APP_BASE_PATH . '/Models/Global.php');
        require_once(APP_BASE_PATH . '/Models/Medals.php');
        //logExecutionTime('ttt', $start, microtime(true));
        
        require_once(APP_BASE_PATH . "/Village.php");
        
        require_once(APP_BASE_PATH . "/Technology.php");
        require_once(APP_BASE_PATH . "/Building.php");
        
        foreach (glob(APP_BASE_PATH . "/Models/*.php") as $model) {
                require_once($model);
                //logExecutionTime($model, $start, microtime(true));

        }
        include("Quest.php");
} else {
        require_once(APP_BASE_PATH . "/Models/Mailer.php");
        require_once(APP_BASE_PATH . "/Models/Support.php");
}

require_once("Register.php");
//var_dump($session->lang);die();
//include("DB2.php");
//if($session->uid==56){
  // print_r($_SESSION);die();
//}
class Account
{
        function __construct()
        {
                global $session, $database, $support;
                if (isset($_POST['support'])) {
                        $support->newsupport($_POST);
                }
                if (isset($_GET['handshake'])) {
                        
                        $token = sha1(trim($_GET['handshake']));
            $find = $database->query("SELECT * FROM login_handshake WHERE token='$token'");
                        //var_dump(count($find));die();
           if(count($find)>0){
                        $ddd=$find[0];
                        $ndaaaa = $database->queryFetch("SELECT username FROM users WHERE id = '".$ddd['uid']."'");
                
                        
                        $_POST['user'] = $_SESSION['username']=$ndaaaa['username'];
                         $database->query("DELETE FROM login_handshake WHERE token='$token'");
                        
                        $this->Login(1);
                   }
                }
                if (isset($_POST['ft'])) {
                        switch ($_POST['ft']) {
                                case "a1":
                                        if ($database->config()['regstatus']) {
                                                $this->Signup();
                                        }
                                        break;
                                case "a2":
                                        $this->Activate();
                                        break;
                                case "a3":
                                        $this->Unreg();
                                        break;
                                case "a4":
                                        $this->Login();
                                        break;
                                case "a5":

                                        $this->Activate2();
                                        break;
                        }


                }
                if (isset($_POST['forgotPassword']) && $_POST['forgotPassword'] == 1) {
                        $this->forgotPassword($_POST['pw_email']);

                }

                //if (isset($_GET['code'])) {
                //      $_POST['id'] = $_GET['code'];
                        //$this->Activate();
                //} else {

                        if ($session->logged_in && in_array("logout.php", explode("/", $_SERVER['PHP_SELF']))) {
                                $this->Logout();
                        }

                }
        //}

        function strposa($haystack, $needles = array(), $offset = 0)
        {
                $chr = array();
                foreach ($needles as $needle) {
                        $res = strpos($haystack, $needle, $offset);
                        if ($res !== false)
                                $chr[$needle] = $res;
                }
                if (empty($chr))
                        return false;
                return min($chr);
        }

        private function Signup()
        {
                global $database, $form, $mailer, $generator, $regme, $mailer, $config;
                $array = array(',', '!', '&', '@', '#', '%', '^', '*');
                if ($this->strposa($_POST['name'], $array, 1)) {
                        $form->addError("name", 'Erreur');
                }
                if (!isset($_POST['name']) || trim($_POST['name']) == "") {
                        $form->addError("name", USRNM_EMPTY);
                } else {
                        if (strlen($_POST['name']) < USRNM_MIN_LENGTH) {
                                $form->addError("name", USRNM_SHORT);
                        } else if (preg_match("/[^/&([\x{0600}-\x{06FF}a-z,A-Z,0-9,А-Яа-яЁё,\-,\s,\_]/u", $_POST['name'])) {
                                $form->addError("name", USRNM_CHAR);
                        } else if ($database->checkExist($_POST['name'], 0)) {
                                $form->addError("name", USRNM_TAKEN);
                        } else if ($database->checkExist_activate($_POST['name'], 0)) {
                                $form->addError("name", USRNM_TAKEN);
                        } else if (in_array($_POST['name'], array("Support", "support", "suppоrt", "multihunter", "multihаnter", "multihantер"))) {
                                $form->addError("name", "Invalid Nickname");
                        }

                }

                if (!isset($_POST['pw']) || trim($_POST['pw']) == "") {
                        $form->addError("pw", PW_EMPTY);
                } else {
                        if (strlen($_POST['pw']) < PW_MIN_LENGTH) {
                                $form->addError("pw", PW_SHORT);
                        } else if ($_POST['pw'] == $_POST['name']) {
                                $form->addError("pw", PW_INSECURE);

                        }
                }
                if (!isset($_POST['email'])) {
                        $form->addError("email", EMAIL_EMPTY);
                } else {
                        if (!$this->validEmail($_POST['email'])) {
                                $form->addError("email", EMAIL_INVALID);
                        } else if ($database->checkExist($_POST['email'], 1)) {
                                $form->addError("email", EMAIL_TAKEN);
                        } else if ($database->checkExist_activate($_POST['email'], 1)) {
                                $form->addError("email", EMAIL_TAKEN);
                        }
                }
                if (!isset($_POST['vid'])) {
                        $form->addError("tribe", TRIBE_EMPTY);
                }
                if (!isset($_POST['kid'])) {
                        $form->addError("kid", "inыберите регион!");
                }
                if (!isset($_POST['agb'])) {
                        $form->addError("agree", AGREE_ERROR);
                }
                if ($database->CheckUniqueIP($_SERVER['REMOTE_ADDR'])) {
                        $form->addError("agree", "ONLY IS ONE ACCOUNT PER SERVER!");
                }
                if ($form->returnErrors() > 0) {
                        $_SESSION['errorarray'] = $form->getErrors();
                        $_SESSION['valuearray'] = $_POST;
                        if (!empty($_POST['invited'])) {
                                header("Location: anmelden.php?ref=" . $_POST['invited']);
                                exit;
                        } else {
                                header("Location: anmelden.php");
                                exit;
                        }
                } else {
                        if (empty($_POST['invited']) and !empty($_POST['referal'])) {
                                $_POST['invited'] = 0;
                                $invbyname = $database->getUserField($_POST['referal'], "id", 1);
                                if (!empty($invbyname)) {
                                        $_POST['invited'] = $invbyname;
                                }
                        }
                        $act = $generator->generateRandStr(10);
                        $act2 = $generator->generateRandStr(5);
                        $uid = $regme->activate($_POST['name'], md5($_POST['pw'] . mb_convert_case($_POST['name'], MB_CASE_LOWER, "UTF-8")), $_POST['email'], 0, 0, $act, $act2, $_POST['invited'], $_POST['country']);
                        if ($uid) {
                                $mailer->sendActivate($_POST['email'], $_POST['name'], $_POST['pw'], $act);
                                
                                        
                                if ($config['needActivate']) {
                                        header("Location: login.php?email=".$_POST['email']);
                                } else {
                                        $_POST['id'] = $act;
                                        $this->Activate2();
                                }
                        }


                }
        }

        function Activate()
        {
                global $regme, $database;
                $dbarray = $database->query("SELECT * FROM activate WHERE `username` ='" . $_SESSION['username'] . "'");
                $uid = 0;
                if (count($dbarray) > 0) {
                        setcookie("COOKUSR", $_POST['name'], time() + COOKIE_EXPIRE, '/login.php');
                        setcookie("COOKEMAIL", $_POST['email'], time() + COOKIE_EXPIRE, '/login.php');
                        if (!empty($dbarray[0]['username']) && !empty($dbarray[0]['password']) && !empty($dbarray[0]['email']) && !empty($dbarray[0]['tribe'])) {
                                $uid = $regme->register($dbarray[0]['username'], $dbarray[0]['password'], $dbarray[0]['email'], $dbarray[0]['tribe'], $dbarray[0]['ref'], $dbarray[0]['country']);
                        }
                        $frandom0 = rand(0, 4);
                        $frandom1 = rand(0, 3);
                        $frandom2 = rand(0, 4);
                        $frandom3 = rand(0, 3);
                        
                                $database->addHeroFace($uid, $frandom0, $frandom1, $frandom2, $frandom3, $frandom3, $frandom2, $frandom1, $frandom0, $frandom2);
                                $database->addHero($uid);
                                $database->addHeroinventory($uid);
                                $wid = $this->generateBase($dbarray[0]['location'], $uid, $dbarray[0]['username']);
                                $database->modifyUnit($wid, array(11), array(1), 1);
                                $database->modifyHero2('wref', $wid, $uid, 0);
                                $database->InsertRights($uid);
                                $database->addAdventure($wid, $uid, 10);
                                $database->AddAchiev($uid);
                                $topic = "نکات مفید و اطلاعات";
                                $message = " " . $dbarray[0]['username'] . "به تراوین خوش آمدی,<br><br>این خانه‌ی جدید توست.<br><br>به اطرافت نگاه کن. مزارع سرسبز، مزارع گندم طلایی و کوه‌های آهن - همه این‌ها متعلق به توست. دهکده تو شاید الان کوچک باشد، اما با ذکاوت و تلاش کافی، آن را به یک امپراطوری تبدیل خواهی کرد. البته، یک فرمانروای بزرگ به چیزی بیش از فضیلت نیاز دارد – او به خرد نیز نیاز دارد. پس به من گوش کن:<br><br>
                                جهان بر پایه‌ی منابع می‌چرخد. ساختمان‌هایت به آنها نیاز دارند، سربازانت از آنها تغذیه می‌کنند، و جنگ‌های بزرگی بر سرشان رخ می‌دهند. با این حال، باید این را بدانی: منابع تنها وسیله‌ای برای رسیدن به اهداف هستند. همیشه آنها را مصرف کن. محافظت اولیه تو پس از ۵ روز از بین خواهد رفت و غارتگران صبر نخواهند کرد. به بازی ادامه بده و در پناهگاه و منابع خود سرمایه‌گذاری کن تا اقتصادی پررونق داشته باشی.<br><br>
                                نفس عمیقی بکش. شاید هوا هنوز از باران تازگی داشته باشد، اما بوی خاکستر هم به مشام می‌رسد. نمی‌توانی نشانه‌های جنگ را نادیده بگیری. اما هنر دیپلماسی دیگر نباید برایت راز باشد: با دیگران صحبت کن. دوستانی پیدا کن و به اتحادی بپیوند. آنها در زمان‌های نیاز از تو حمایت خواهند کرد و مشاوره‌های بی‌شماری به تو خواهند داد. حتی باید به متجاوزینت نیز پیام بدهی. گاهی می‌توان به توافقی رسید که به نفع هر دو طرف باشد.<br><br>
                                حالا اولین سنگ را بگذار. این یادبود توست. اما این تنها یادبود نخواهد بود. برنامه تو باید این باشد که به محض امکان، دهکده‌ی دوم خود را تأسیس کنی. با این کار گزینه‌ها و قدرتت به شدت افزایش می‌یابد. منابع بیشتر، امکان ساخت ساختمان‌های تخصصی‌تر و استراتژی‌های بهتر به دست خواهی آورد. همچنین، تأسیس دهکده نزدیک به دوستانت مزیتی است که نباید نادیده گرفته شود.<br><br>
                                بیش از این نباید حرفی زد. زمان عمل فرا رسیده است. به سراغ وظیفه‌دار برو – او برنامه‌های بزرگی برای تو دارد. بگذار این نکته‌ی ارزشمند را با تو در میان بگذارم: تو هرگز به‌طور واقعی شکست نخورده‌ای تا زمانی که تسلیم نشده باشی. چالش‌ها و موانعی در مسیرت خواهند بود، اما تنها با عبور از آنها رشد خواهی کرد. هر داستان بزرگی سختی‌های خودش را دارد. حالا برو و به دنیا نشان بده از چه ساخته شده‌ای.<br>";
                                $database->sendMessage($uid, 6, $topic, $message, 0, 0, 0, 0);
                                $database->InsertUniqueIP($_SERVER['REMOTE_ADDR'], $dbarray[0]['email']);
                                $regme->unreg($dbarray[0]['username']);
                                if (OPENING > time()) {
                                        $ptime = OPENING + PROTECTION;
                                } else {
                                        $ptime = time() + PROTECTION;
                                }
                                $database->modifyProtection($uid, $ptime);
                                if (OPENING > time()) {
                                        header('Location: login.php');
                                } else {

                                        $this->Login(1);
                                }

                        
                                
                                
                } else {
                        echo "dooste khube multi accounte man";
                        die();
                }

        }


        private function Activate2()
        {
                global $regme;
                $dbarray = $regme->checkActivate($_POST['id']);
                if (count($dbarray)) {
                        $_SESSION['username'] = $dbarray[0]['username'];
                        header("Location: activate.php?token=" . $dbarray[0]['password'] . "");

                } else {
                        header("Location: activate.php?e=3");
                }

        }


        private function Unreg()
        {
                global $regme;
                $dbarray = $regme->checkAccount($_POST['name'], $_POST['email']);
                if (md5($_POST['pw'] . mb_convert_case($_POST['name'], MB_CASE_LOWER, "UTF-8")) == $dbarray['password']) {
                        $regme->unreg($dbarray['username']);
                        header("Location: anmelden.php");
                } else {
                        header("Location: activate.php?e=4");
                }
        }

        private function Login($isActive = FALSE)
        {
                global $database, $session, $form, $generator, $config;

                // check if not activated
                $aData = $database->queryFetch("SELECT * FROM activate WHERE username = '" . $_POST['user'] . "'");
                if ($aData['id']) {
                        if ($config['needActivate']) {
                                $form->addError("user", 'شما ایمیل خود را تایید نکرده اید لطفا ایمیل فعال سازی را تایید نمایید ');
                        } else {
                                $_SESSION['username'] = $_POST['user'];
                                header('Location: activate.php?token=' . $aData['password'] . '');
                        }

                } else {
                        if ($isActive) {
                                $_POST['user'] = $_SESSION['username'];
                                session_unset(); // This for activate.php — PHP8 fix: session_unset() takes no args (was: session_unset($_SESSION['usrname']) → ArgumentCountError fatal)
                                $sessid = $generator->generateRandID();
                                $_SESSION['sessid'] = $sessid;
                                $_SESSION['s1'] = $_SESSION['s2'] = $_SESSION['s3'] = $_SESSION['s4'] = $_SESSION['s5'] = $_SESSION['s6'] = 1;
                                $_SESSION['sit'] = 0;
                                $database->UpdateOnline("login", $_POST['user'], 0, $sessid);

                                setcookie("COOKUSR", $_POST['user'], time() + COOKIE_EXPIRE, '/login.php');
                                $session->login($_POST['user']);

                        } else {
                                $time = time();
                                $starttime = OPENING;
                                if ($starttime < $time) {
                                        $sitlogin = $database->sitterLogin($_POST['user'], $_POST['pw']);

                                        if (!$sitlogin[0]) {
                                                $ownlogin = $database->login($_POST['user'], $_POST['pw']);
                                        } else {
                                                $ownlogin = false;
                                        }
                                        if (!isset($_POST['user']) || $_POST['user'] == "") {
                                                $form->addError("user", LOGIN_USR_EMPTY);
                                        } else if (!$database->checkExist($_POST['user'], 0)) {
                                                if (!$database->checkExist($_POST['user'], 1)) {
                                                        $form->addError("user", USR_NT_FOUND);
                                                }
                                        }
                                        if (!isset($_POST['pw']) || $_POST['pw'] == "") {
                                                $form->addError("pw", LOGIN_PASS_EMPTY);
                                        } else if (!$sitlogin[0] && !$ownlogin) {
                                                $form->addError("pw", LOGIN_PW_ERROR);
                                        }

                                        if ($form->returnErrors() > 0) {
                                                $_SESSION['errorarray'] = $form->getErrors();
                                                $_SESSION['valuearray'] = $_POST;

                                                header("Location: login.php");
                                        } else {

                                                $sessid = $generator->generateRandID();
                                                $_SESSION['sessid'] = $sessid;

                                                //print_r($sitlogin); echo $sitlogin[0]; die();
                                                if ($sitlogin[0]) {
                                                        $rights = $database->SitterRights($sitlogin[1]);
                                                        $k = ($sitlogin[2]) == 2 ? 2 : '';
                                                        $_SESSION['s1'] = $rights['s' . $k . '1'];
                                                        $_SESSION['s2'] = $rights['s' . $k . '2'];
                                                        $_SESSION['s3'] = $rights['s' . $k . '3'];
                                                        $_SESSION['s4'] = $rights['s' . $k . '4'];
                                                        $_SESSION['s5'] = $rights['s' . $k . '5'];
                                                        $_SESSION['s6'] = $rights['s' . $k . '6'];
                                                        $database->UpdateOnline("sitter", $_POST['user'], $sitlogin[1], $sessid);
                                                        $_SESSION['sit'] = $sitlogin[2];
                                                        $database->addPalevoLogin($sitlogin[1], $_SERVER['REMOTE_ADDR'], 0, "Зам №" . $sitlogin[2] . " - " . $sitlogin[3], "", $sitlogin[2]);
                                                        $session->login($_POST['user']);

                                                } elseif ($ownlogin) {
                                                        $_SESSION['s1'] = $_SESSION['s2'] = $_SESSION['s3'] = $_SESSION['s4'] = $_SESSION['s5'] = $_SESSION['s6'] = 1;
                                                        $_SESSION['sit'] = 0;
                                                        $database->UpdateOnline("login", $_POST['user'], $sitlogin[1], $sessid);

                                                        // Why using cookies? 
                                                        //setcookie('username', $_POST['user'], time());
                                                        //setcookie("username",$_POST['user'],time()+COOKIE_EXPIRE,'/');
                                                        //setcookie("PW",$_POST['pw'],time()+COOKIE_EXPIRE,'/');
                                                        $session->login($_POST['user']);
                                                        $database->addPalevoLogin($sitlogin[1], $_SERVER['REMOTE_ADDR'], 0, '', "", $sitlogin[2]);
                                                }



                                        }

                                }
                        }
                }
        }

        private function Logout()
        {
                global $session, $database;
                if ($_SESSION['adminLogin']) { // if admin access
                        $_SESSION['username'] = $_SESSION['adminUser'];
                        unset($_SESSION['adminLogin']);
                        header('Location: dorf1.php');
                        exit();
                } else {
                        unset($_SESSION['wid']);
                        $_SESSION['logoutU'] = $_SESSION['username'];
                        $database->FuckOnline($_SESSION['username'], $_SESSION['sessid']);
                        $session->Logout();
                }
        }


        private function forgotPassword($email)
        {
                global $database, $generator, $form, $mailer, $mailer;
                $npw = $generator->generateRandStr(6);
                $act = $generator->generateRandStr(10);
                $getData = $database->getUserWithEmail($email);
                $er = false;
                if ($email == "") {
                        $form->addError("pw_email", EMAIL_EMPTY);
                        $er = true;
                } elseif ($database->checkProcExist($getData['id'])) {
                        if ($database->checkExist($email, 1)) {
                                $database->addNewProc($getData['id'], $npw, 0, $act, 0);
                                $mailer->sendPassword($email, $getData['username'], $npw, $act);
                        } else {
                                $form->addError("pw_email", 'Email not found');
                                $er = true;
                        }
                } else {
                        $form->addError("pw_email", 'You have already received');
                        $er = true;
                }
                if ($er) {
                        $_SESSION['errorarray'] = $form->getErrors();
                        $_SESSION['valuearray'] = $_POST;
                } else {

                        header("Location: login.php?action=forgotPassword&finish=true");
                        exit;
                }
        }
        private function validEmail($email)
        {
                $regexp = "/^[a-z0-9]+([_\\.-][a-z0-9]+)*@([a-z0-9]+([\.-][a-z0-9]+)*)+\\.[a-z]{2,}$/i";
                if (!preg_match($regexp, $email)) {
                        return false;
                }
                return true;
        }

        function generateBase($kid, $uid, $username)
        {
                global $database;
                if ($kid == 0) {
                        $kid = rand(1, 4);
                }
                $time = 0;
                if (OPENING > time()) {
                        $time = OPENING;
                }
                $wid = $database->generateBase($kid);
                $database->setFieldTaken($wid);
                $database->addVillage($wid, $uid, $username, 1, 2, $time);
                $database->addResourceFields($wid, $database->getVillageType($wid));
                $database->addUnits($wid);
                $database->addTech($wid);
                $database->addABTech($wid);

                return $wid;
        }



}
;
$account = new Account;

