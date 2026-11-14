<?php
include_once "application/Account.php";
if(isset($_GET['id'])){
    $_GET['id'] = $database->filterIntValue($database->filterVar($_GET['id']));
}
switch($_GET['id'] ?? null){
    case 3:
        $plusView = "application/views/Plus/3.php"; break;
    case 5:
        $plusView = "application/views/Plus/5.php"; break;
    case 6:
        $plusView = "application/views/Plus/codeGen.php"; break;
    case 7:
    case 15:
        $plusView = "application/views/Plus/code.php"; break;
    default:
        $plusView = "application/views/Plus/1.php";
        if(isset($_GET['id'])){ unset($_GET['id']); } // pmenu marks the buy-gold tab active without id
        break;
}
?>
<?php include("application/views/html.php");?>

<body class="v35 webkit <?=$database->bodyClass($_SERVER['HTTP_USER_AGENT']); ?> ar-AE plus <?php if($dorf1==''){echo 'perspectiveBuildings';}else{ echo 'perspectiveResources';} ?> <?php echo DIRECTION; ?> season- buildingsV3">
<script type="text/javascript">
    window.ajaxToken = 'de3768730d5610742b5245daa67b12cd';
</script>
<div id="background">
    <div id="headerBar"></div>
    <div id="bodyWrapper">

        <div id="header">
            <?php include("application/views/topheader.php"); ?>
        </div>
        <div id="center">
            <?php include("application/views/sideinfo.php"); ?>
            <div id="contentOuterContainer" class=" contentPage">
                <div class="contentTitle buttonCount1">
                    <a id="closeContentButton" class="contentTitleButton buttonFramed green withIcon rectangle" href="dorf1.php">
                        <svg viewBox="0 0 20 20"><g class="outline">
                            <path d="M0 17.01L7.01 10 .14 3.13 3.13.14 10 7.01 17.01 0 20 2.99 12.99 10l6.87 6.87-2.99 2.99L10 12.99 2.99 20 0 17.01z"></path>
                        </g><g class="icon">
                            <path d="M0 17.01L7.01 10 .14 3.13 3.13.14 10 7.01 17.01 0 20 2.99 12.99 10l6.87 6.87-2.99 2.99L10 12.99 2.99 20 0 17.01z"></path>
                        </g></svg>
                    </a>
                    <a id="knowledgeBaseButton" class="contentTitleButton buttonFramed withIcon rectangle green" href="help.php" target="_blank">&nbsp;</a>
                </div>
                <div class="contentContainer">
                    <div id="content" class="plus">
                        <?php include($plusView); ?>
                    </div>
                </div>
            </div>
            <?php include("application/views/rightsideinfor.php"); ?>
            <div class="clear"></div>
        </div>
        <?php
        include("application/views/header.php");
        include("application/views/footer.php");
        ?>
        <div id="ce"></div>
    </div>
</body>
</html>
