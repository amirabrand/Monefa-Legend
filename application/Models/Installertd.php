<?php
/*
     
*/
Class installerTD {
    public function checkStatus() {
        global $database;

        $qData = $database->queryFetch("SELECT * FROM `config`");
        if (time() > $qData['reinstalltime'] ) {
            $this->updateInstall($qData);
        }
    }

    function updateInstall($sData) {
        global $database;
    
        // Adjust path
        $adjustedPath = dirname(CONFSDATA);
    
        // Tables to drop
        $tablesToDrop = [
            'a2b','natarsjob','subs', 'abdata', 'achiev', 'activate', 'adventure', 'alibounuses', 'alidata', 'ali_invite', 'ali_log', 
            'ali_permission', 'antimult', 'artefacts', 'attacks', 'auction', 'autoauction', 'autorenewals', 
            'banlist', 'bdata', 'bounuses', 'buygold', 'codes', 'config', 'critical_log', 
            'deleted', 'demolition', 'diplomacy', 'enforcement', 'farmlist', 'fdata', 'forum_cat', 'forum_edit', 
            'forum_poll', 'forum_post', 'forum_topic', 'fpost_rules', 'hero', 'heroface', 'heroinventory', 
            'heroitems', 'ignore', 'links', 'log', 'login_handshake', 'map_control', 'map_marks', 'market', 
            'mdata', 'medal', 'messages', 'movement', 'ndata', 'newproc', 'odata', 'online', 'palevo', 
            'password', 'payments', 'plusaddons', 'pnews', 'prisoners', 'quests', 'queue', 'queue_backup', 
            'raidlist', 'referals', 'research', 'route', 'sitters', 'spravka', 'storage', 'superpoint', 
            'support', 'tdata', 'training', 'units', 'users', 'user_deleted_messages', 'user_messages', 
            'vdata', 'wdata'
        ];
    
        // Drop tables
        foreach ($tablesToDrop as $table) {
            $sql = "DROP TABLE IF EXISTS `$table`";
            if ($database->query($sql) === TRUE) {
                echo "Table $table dropped successfully.<br>";
            }
        }
    
        // Read the SQL file (ss.sql)
        $sqlFilePath = $adjustedPath . '/ss.sql';
        $sqlContent = file_get_contents($sqlFilePath);
    
        // OPTIONAL: Replace characters in the SQL content if needed
        // $sqlContent = str_replace('old_character', 'new_character', $sqlContent);
    
        // Use the existing importSQLDump function
        //var_dump($database->importSQLDumper($sqlContent));//die();
        if ($database->importSQLDumper($sqlContent)) {
            echo "Database reinstalled successfully.<br>";
        } else {
            echo "Error during database reinstall.<br>";
        }
        $now = new DateTime();

// Clone the current time and set the time to 18:00
$nextSixPM = clone $now;
$nextSixPM->setTime(18, 0);

// If the current time is past 18:00, move to the next day
if ($now > $nextSixPM) {
    $nextSixPM->modify('+2 day');
}

$timestamp = $nextSixPM->getTimestamp();
        $val1=$timestamp;
        $val2=($sData['ARTEFACTS']-$sData['OPENING'])+$val1;
        $val3=($sData['WW_PLAN']-$sData['OPENING'])+$val1;
        $val4=$val3+(5*86400);
        $updateConfigSQL = "UPDATE config SET 
    SERVER_NAME = '" . $sData['SERVER_NAME'] . "',
    DEFAULT_GOLD = '" . $sData['DEFAULT_GOLD'] . "',
    AUCTIONTIME = '" . $sData['AUCTIONTIME'] . "',
    GP_LOCATE = '" . $sData['GP_LOCATE'] . "',
    OPENING = '" . $val1 . "',
    lastAchiev = '" . $val1 . "',
    lastioasisUpdate = '" . $val1 . "',
    lastmedal = '" . $val1 . "',
    reinstalltime = '" . $val4 . "',
    SPEED = '" . $sData['SPEED'] . "',
    wwneedalli = '" . $sData['wwneedalli'] . "',
    endless = '" . $sData['endless'] . "',
    MOMENT_TRAIN = '" . $sData['MOMENT_TRAIN'] . "',
    ARTEFACTS = '" . $val2 . "',
    WW_PLAN = '" . $val3 . "',
    CRANNY_CAP = '" . $sData['CRANNY_CAP'] . "',
    ADV_TIME = '" . $sData['ADV_TIME'] . "',
    TRAPPER_CAPACITY = '" . $sData['TRAPPER_CAPACITY'] . "',
    Lang = '" . $sData['Lang'] . "',
    STORAGE_MULTIPLIER = '" . $sData['STORAGE_MULTIPLIER'] . "',
    INCREASE_SPEED = '" . $sData['INCREASE_SPEED'] . "',
    PROTECTIOND = '" . $sData['PROTECTIOND'] . "',
    farmrels = '0',
    PLUS_TIME = '" . $sData['PLUS_TIME'] . "',
    PLUS_PRODUCTION = '" . $sData['PLUS_PRODUCTION'] . "',
    HOMEPAGE = '" . $sData['HOMEPAGE'] . "',
    serverpage = '" . $sData['serverpage'] . "',
    HASHASHIAN = '" . $sData['HASHASHIAN'] . "'


  
";
$database->query($updateConfigSQL);
include_once('jdf.php');
$protectionDuration = $sData['PROTECTIOND']; // in seconds
$formattedProtectionTime = gmdate("H:i:s", $protectionDuration); // e.g., "12:00:00" for 12 hours

$message = "درود بر دوستان عزیز و قهرمانان تراوینا! 🌟
امیدوارم روز خوبی داشته باشید! ✨

📣 با خوشحالی به اطلاع می‌رسانیم که سرور جدید تراوینا با سرعت " . $sData['SPEED'] . "، " . jdate('J F V H:i:s', $val1) . " راه‌اندازی می‌شود! آماده یک تجربه هیجان‌انگیز باشید! ⚔️

🎁 طلای  وفاداری برای بازیکنان کانال: ۱۰۰۰ طلا  
⚡️ سرعت بازی: " . $sData['SPEED'] . "  
⚡️ سرعت تربیت نیروها: " . $sData['SPEED'] . "  
⚡️ سرعت جابجایی نیروها: " . $sData['INCREASE_SPEED'] . "  
🛡 زمان حمایت اولیه: " . $formattedProtectionTime . "  
🔐 تأیید و فعال‌سازی ایمیل در این سرور الزامی است!

🔗 آدرس سرور:  
" . $sData['serverpage'] . "

حتماً در این سرور ثبت‌نام کنید و دوستانتان را هم دعوت کنید تا از این ماجراجویی بی‌نظیر لذت ببرند! 👑

در صورت بروز هرگونه مشکل، می‌توانید با پشتیبانی در تماس باشید:  
@traviana

منتظرتان هستیم! 😉🌹";

// Send the message to the Telegram channel
$ddd=$this->sendMessageToChannel($message);


}

    

function sendMessageToChannel($message)
{
    $channelId='-1002028123102';
    // Your Telegram bot token
    $botToken = '';

    // The Telegram API URL for sending messages
   // $url = "https://api.telegram.org/bot$botToken/sendMessage";

    // Parameters for the API request
    $params = [
        'chat_id' => $channelId,  // Channel ID or username (@channelUsername)
        'text' => $message,       // The message content
        'parse_mode' => 'HTML'    // Optional: Use HTML or Markdown for text formatting
    ];
    
    $data = array(
        "botoken" => $botToken,
        "data" => $params
    );
    // Initialize a cURL session
    $jsonData = json_encode($data);
    $ch = curl_init('https://self.benwrites.ir/tramian/single.php');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json'
    ));

    $result = curl_exec($ch);
    $err = curl_error($ch);
    $result = json_decode($result, JSON_PRETTY_PRINT);
   // var_dump($result );
   // var_dump($err);
    curl_close($ch);
    // Return the API response
    return (empty($err))?$result:$err;
}
}
$installertd = new installerTD;
?>
