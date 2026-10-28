<?php
if($session->uid > 2 && $session->uid != 6) die('Hacking attemp!');
$dqr="SELECT `global` FROM config ";
$dqr1=$database->query($dqr) ;
$txtpo = $dqr1[0]['global'];


if (!isset($_GET['action'])) {
    // Get the total number of messages
    $stmt = $mysqli->prepare("SELECT COUNT(`id`) as total FROM $prefix");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $query = $row['total'];

    // Pagination settings
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $itemsPerPage = 10;
    $lastPage = ceil($query / $itemsPerPage);
    $page = max(1, min($page, $lastPage));

    $centerPages = '';

    // Generate pagination links
    for ($i = max(1, $page - 3); $i <= min($lastPage, $page + 3); $i++) {
        if ($i == $page) {
            $centerPages .= '<span class="number currentPage">' . $i . '</span> ';
        } else {
            $centerPages .= '<a class="number" href="?tid=3&page=' . $i . '">' . $i . '</a> ';
        }
    }

    // Get messages for the current page
    $offset = ($page - 1) * $itemsPerPage;
    $stmt = $mysqli->prepare("SELECT * FROM $prefix ORDER BY time DESC LIMIT ?, ?");
    $stmt->bind_param('ii', $offset, $itemsPerPage);
    $stmt->execute();
    $result = $stmt->get_result();

    $paginationDisplay = '';
    if ($page > 1) {
        $paginationDisplay .= '<a class="first" href="?tid=3&page=1"><img alt="صفحه اول" src="img/x.gif"></a> ';
        $paginationDisplay .= '<a class="previous" href="?tid=3&page=' . ($page - 1) . '"><img alt="صفحه قبل" src="img/x.gif"></a> ';
    } else {
        $paginationDisplay .= '<img alt="صفحه اول" src="img/x.gif" class="first disabled"> ';
        $paginationDisplay .= '<img alt="صفحه قبل" src="img/x.gif" class="previous disabled"> ';
    }

    $paginationDisplay .= $centerPages;

    if ($page < $lastPage) {
        $paginationDisplay .= '<a class="next" href="?tid=3&page=' . ($page + 1) . '"><img alt="صفحه بعد" src="img/x.gif"></a> ';
        $paginationDisplay .= '<a class="last" href="?tid=3&page=' . $lastPage . '"><img alt="صفحه آخر" src="img/x.gif"></a> ';
    } else {
        $paginationDisplay .= '<img alt="صفحه بعد" src="img/x.gif" class="next disabled"> ';
        $paginationDisplay .= '<img alt="صفحه آخر" src="img/x.gif" class="last disabled"> ';
    }

    // Display messages
    echo '<table cellpadding="0" cellspacing="1" border="0">';
    echo '<tr><td>تنظیمات</td><td>فرستنده</td><td>گیرنده</td><td>موضوع</td><td>پیام</td><td>وضعیت</td><td>زمان</td></tr>';
    while ($row = $result->fetch_assoc()) {
        $ids = $row['id'];
        $fromplayername = $row['owner'];
        $toplayername = $row['target'];
        $msgtitle = $row['topic'];
        $msgbody = $row['message'];
        $isreaded = $row['viewed'];
        $time = $row['time'];
        $delowner = $row['delowner'];
        $deltarget = $row['deltarget'];

        $Fcolor = ($delowner == 1) ? 'red' : 'black';
        $Tcolor = ($deltarget == 1) ? 'red' : 'black';
        $date = date('Y-m-d H:i:s', $time);

        // Fetch usernames
        $stmt = $mysqli->prepare("SELECT `username` FROM ".TB_PREFIX."users WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $fromplayername);
        $stmt->execute();
        $fromUser = $stmt->get_result()->fetch_assoc();
        $fromplayername = $fromUser['username'];

        $stmt->bind_param('i', $toplayername);
        $stmt->execute();
        $toUser = $stmt->get_result()->fetch_assoc();
        $toplayername = $toUser['username'];

        $msgbody = stripslashes(nl2br($msgbody));
        $read = ($isreaded == 0) ? '<img src="../img/admin/merror.png" height="20" title="خوانده نشده">' : '<img src="../img/admin/msuccess.gif" height="20" title="خوانده شده">';

        echo '<tr>
                <td>
                    <a href="?action=del&did=' . $ids . '" onClick="return del(\'did\',' . $ids . ');"><img src="../img/admin/del.gif" class="del" title="حذف"></a>
                    <a href="?action=edit&did=' . $ids . '" onClick="return edit(\'did\',' . $ids . ');"><img src="../img/admin/edit.gif" class="edit" title="ویرایش"></a>
                </td>
                <td><font color="' . $Fcolor . '">' . $fromplayername . '</font></td>
                <td><font color="' . $Tcolor . '">' . $toplayername . '</font></td>
                <td><textarea title="' . $msgtitle . '" readonly="readonly" style="width:80px; height:60px; text-align:right;">' . $msgtitle . '</textarea></td>
                <td><textarea title="' . $msgbody . '" readonly="readonly" style="width:80px; height:60px; text-align:right;">' . $msgbody . '</textarea></td>
                <td>' . $read . '</td>
                <td class="dat">' . $date . '</td>
              </tr>';
    }
    echo '</table>';
} elseif (isset($_GET['action']) && $_GET['action'] == 'edit') {
    // Edit message
    $did = filter_var($_GET['did'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $mysqli->prepare("SELECT * FROM $prefix WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $did);
    $stmt->execute();
    $rowss2 = $stmt->get_result()->fetch_assoc();

    $ids = $rowss2['id'];
    $fromplayername = $rowss2['owner'];
    $toplayername = $rowss2['target'];
    $msgtitle = $rowss2['topic'];
    $msgbody = stripslashes(nl2br($rowss2['message']));

    echo '<form action="?action=edit&done=edit" method="POST">
          <input type="hidden" name="id" id="id" value="' . $ids . '">
          <table cellpadding="0" cellspacing="1" border="0">
          <tr><td></td><td>فرستنده</td><td>گیرنده</td><td>موضوع</td><td>پیام</td></tr>';

    echo '<tr>
            <td><a href="' . $_SERVER['HTTP_REFERER'] . '"><img src="../img/admin/mreturn.png" title="بازگشت"></a></td>
            <td><input name="from" value="' . $fromplayername . '" size=3></td>
            <td><input name="to" value="' . $toplayername . '" size=3></td>
            <td><textarea name="msgtitle" title="' . $msgtitle . '" style="width:150px; height:300px; text-align:right;">' . $msgtitle . '</textarea></td>
            <td><textarea name="msgbody" title="' . $msgbody . '" style="width:150px; height:300px; text-align:right;">' . $msgbody . '</textarea></td>
          </tr>';

    if (isset($_GET['done']) && $_GET['done'] == 'edit') {
        $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        $msgtitle = htmlspecialchars($_POST['msgtitle']);
        $msgbody = htmlspecialchars($_POST['msgbody']);
        $from = filter_var($_POST['from'], FILTER_SANITIZE_STRING);
        $to = filter_var($_POST['to'], FILTER_SANITIZE_STRING);

        // Get user IDs
        $stmt = $mysqli->prepare("SELECT id FROM ".TB_PREFIX."users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $from);
        $stmt->execute();
        $fromUser = $stmt->get_result()->fetch_assoc();
        $from = $fromUser['id'];

        $stmt->bind_param('s', $to);
        $stmt->execute();
        $toUser = $stmt->get_result()->fetch_assoc();
        $to = $toUser['id'];

        $stmt = $mysqli->prepare("UPDATE $prefix SET owner = ?, target = ?, topic = ?, message = ? WHERE id = ?");
        $stmt->bind_param('iissi', $from, $to, $msgtitle, $msgbody, $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo '<script type="text/javascript">window.location = "AdminMSG.php?tid=3&page=1";</script>';
        } else {
            echo '<script type="text/javascript">alert("خطا در ویرایش پیام.");</script>';
        }
    }

    echo '<tr>
            <td><input type="submit" value="ارسال"></td>
          </tr>
          </table>
          </form>';
} elseif (isset($_GET['action']) && $_GET['action'] == 'del') {
    $did = filter_var($_GET['did'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $mysqli->prepare("DELETE FROM $prefix WHERE id = ?");
    $stmt->bind_param('i', $did);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo '<script type="text/javascript">window.location = "AdminMSG.php?tid=3&page=1";</script>';
    } else {
        echo '<script type="text/javascript">alert("خطا در حذف پیام.");</script>';
    }
}

$mysqli->close();
?>
