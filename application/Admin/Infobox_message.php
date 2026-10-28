<?php
session_start();
include('application/html.php');


// Database connection
$mysqli = new mysqli('localhost', 'username', 'password', 'database_name');
if ($mysqli->connect_error) {
    die('Database connection error: ' . $mysqli->connect_error);
}

// Get message by ID
$nid = isset($_GET['nid']) ? intval($_GET['nid']) : 0;
$stmt = $mysqli->prepare("SELECT * FROM messages WHERE id = ?");
$stmt->bind_param('i', $nid);
$stmt->execute();
$msg = $stmt->get_result()->fetch_assoc();

if ($msg) {
    $txt2 = "";
    
    // BBCode to HTML conversion
    function bbcode_to_html($text) {
        $bbcode = [
            '/\[b\](.*?)\[\/b\]/is' => '<b>$1</b>',
            '/\[i\](.*?)\[\/i\]/is' => '<i>$1</i>',
            '/\[u\](.*?)\[\/u\]/is' => '<u>$1</u>',
        ];
        return preg_replace(array_keys($bbcode), array_values($bbcode), $text);
    }
    
    if ($_POST['submit'] == 'ارسال') {
        unset($_SESSION['m_message']);
        $_SESSION['m_message'] = htmlspecialchars($_POST['message']);
        $NextStep = true;
    }

    if (isset($_POST['confirm'])) {
        if ($_POST['confirm'] == 'نه') {
            $Interupt = true;
        }
        if ($_POST['confirm'] == 'بله') {
            $myFile = 'Templates/text_infobox.tpl';
            if (file_exists($myFile)) {
                $fh = fopen($myFile, 'w') or die("<br/><br/><br/>Can't open file: templates/text_infobox.tpl");
                $text = file_get_contents('Templates/text_infobox_format.tpl');
                $text = preg_replace("'%TEKST%'", $_SESSION['m_message'], $text);
                fwrite($fh, $text);
                fclose($fh);
                $done = true;
            } else {
                die('<br/><br/><br/>wrong');
            }
        }
    }
?>

<h4 class="round">پیام داخل جعبه اطلاعات</h4>
<?php if (!isset($NextStep) && !isset($NextStep2) && !isset($done)) { ?>
    <form method="POST" action="Admins.php?tid=4" name="myform" id="myform">
        <table cellspacing="1" cellpadding="1" class="tbg" style="background-color:#C0C0C0; border: 0px solid #C0C0C0; font-size: 10pt;">
            <tbody>
            <tr>
                <td class="rbg" style="font-size: 10pt; text-align:center;">جعبه اطلاعات</td>
            </tr>
            <tr>
                <td style="font-size: 10pt; text-align:center;">
                    BB کد :<br>
                    <b>[b] txt [/b]</b> - <i>[i] txt [/i]</i> - <u>[u] txt [/u]</u><br>
                    <textarea class="fm" name="message" cols="60" rows="23"><?php echo isset($txt) ? htmlspecialchars($txt) : ''; ?></textarea>
                </td>
            </tr>
            <tr>
                <td style="text-align:center;">تمامی قسمت ها اجباریست</td>
            </tr>
            <tr>
                <td style="text-align:center;">
                    <input type="submit" value="ارسال" name="submit">
                </td>
            </tr>
            </tbody>
        </table>
    </form>
    <a href="Admins.php?tid=4&del">پاک کردن پیام همگانی قبلی</a>
<?php } elseif (isset($NextStep)) { ?>
    <form method="POST" action="Admins.php?tid=4">
        <table cellspacing="1" cellpadding="2" class="tbg">
            <tbody>
            <tr>
                <td class="rbg" colspan="2">تایید پیام</td>
            </tr>
            <tr>
                <td style="text-align: left; width: 200px;">آیا مطمئنید که میخواهید پیام را ارسال کنید؟</td>
                <td style="text-align: left;">
                    <input type="submit" style="width: 240px;" class="fm" name="confirm" value="بله">
                    <input type="submit" style="width: 240px;" class="fm" name="confirm" value="نه">
                </td>
            </tr>
            </tbody>
        </table>
    </form>
    <?php
    $txt2 = bbcode_to_html($_SESSION['m_message']);
    echo 'پیام ارسالی : <br><br>';
    echo '<textarea class="fm" name="message" cols="60" rows="13" disabled>' . $txt2 . '</textarea>';
    ?>
<?php } elseif (isset($Interupt)) { ?>
    <b><?php echo 'ارسال پیام متوقف شد'; ?></b>
<?php } elseif (isset($done)) { ?>
    پیام همگانی ارسال شد
<?php } else {
    die('مشکلی وجود دارد');
} ?>

<?php
}
$mysqli->close();
?>
