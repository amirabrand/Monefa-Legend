<?php
/* T35: honor ?t26m=1|0 before the redirect. index.php never includes
   html.php (where the sticky mobile cookie is normally asserted), so
   the flag has to be applied here and carried through the redirect. */
$t26ix = isset($_GET['t26m']) ? $_GET['t26m'] : '';
if ($t26ix === '1' || $t26ix === '0') {
    @setcookie('t26m', $t26ix, array('expires' => time() + 7776000, 'path' => '/', 'samesite' => 'Lax'));
    header('Location: login.php?t26m=' . $t26ix);
    exit();
}
header('Location: login.php'); exit();
