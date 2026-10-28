<?php
session_start();
if($_SESSION['access'] < 9) die("<h1><font color=\"red\">Access Denied: You are not Admin!</font></h1>");

include_once("../../config.php");
include_once("../../DB.php");
include_once("../../Database/db_MYSQL.php");


$id = (int)$_POST["id"];

$database->queryFetch("UPDATE users SET cp = cp + '".(int)$_POST['cp']."' WHERE id = '".(int)$id."' ");
header("Location: ../../../TdPaneLXP/?p=player&uid=".$id."");
