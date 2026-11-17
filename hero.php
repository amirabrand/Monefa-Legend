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
include("application/Account.php");

if($_POST){
    if($_POST['Random'] == "random"){
        $_POST['HeroFace'] = rand(0,4);
        $_POST['color'] = rand(0,4);
        $_POST['HeroHair'] = rand(0,5);
        $_POST['HeroEar'] = rand(0,4);
        $_POST['HeroEyebrow'] = rand(0,4);
        $_POST['HeroEye'] = rand(0,4);
        $_POST['HeroNose'] = rand(0,4);
        $_POST['HeroMouth'] = rand(0,3);
        $_POST['HeroBeard'] = rand(0,5);
    }
//print_r($_POST);die;
    $database->editTableField('heroface', 'gender', $_POST['HeroGender'], 'uid', $session->uid);
    $database->editEbaloHero($session->uid,$_POST['HeroBeard'],$_POST['HeroEar'],$_POST['HeroEye'],$_POST['HeroEyebrow'],$_POST['HeroFace'],$_POST['HeroHair'],$_POST['HeroMouth'],$_POST['HeroNose'],$_POST['color']);
    header("Location: hero.php");
}
?>


<?php include("application/views/html.php");?>
<body class="v35 webkit <?=$database->bodyClass($_SERVER['HTTP_USER_AGENT']); ?> ar-AE hero <?php if($dorf1==''){echo 'perspectiveBuildings';}else{ echo 'perspectiveResources';} ?> <?php echo DIRECTION; ?> buildingsV3">
<script type="text/javascript">
    window.ajaxToken = 'de3768730d5610742b5245daa67b12cd';
</script>
<div id="background">
<div id="headerBar"></div>
<div id="bodyWrapper">

<div id="header">

    <?php
    include("application/views/topheader.php");


    ?>

</div>
<div id="center">


<?php include("application/views/sideinfo.php"); ?>

	
    
            
      
<?php 
include_once("application/views/Hero/heroMenu.php"); 


?>



<?php
$herodetail = $session->heroD;
$gender=$herodetail['gender'];
if($herodetail['color']==0){
	$color = "black";
}
if($herodetail['color']==1){
	$color = "brown";
}
if($herodetail['color']==2){
	$color = "darkbrown";
}
if($herodetail['color']==3){
	$color = "yellow";
}
if($herodetail['color']==4){
	$color = "red";
}
if($herodetail['gender']==0) {$gstr='male';} else {$gstr='female';}
$gender=$herodetail['gender'];
$geteye = $herodetail['eye'];if ($gender==0) $geteye%=5;
$geteyebrow = $herodetail['eyebrow'];if ($gender==0) $geteyebrow%=5;
$getnose = $herodetail['nose'];if ($gender==0) $getnose%=5;
$getear = $herodetail['ear'];if ($gender==0) $getear%=5;
$getmouth = $herodetail['mouth'];if ($gender==0) $getmouth%=4;
$getbeard = $herodetail['beard']; if ($gender==1) $getbeard=5;
$gethair = $herodetail['hair'];
$getface = $herodetail['face'];if ($gender==0) $getface%=5;
?>


<div id="content" class="heroV2Appearance">
                     <div id="heroV2" class="contentV2"><a id="tabFavorButton" class="icon contentTitleButton buttonFramed green withIcon rectangle undefined"></a>
                       <div>
   
					   <div id="heroEditor" class="genderSwitch <?php echo $gstr; ?>">
<div class="hero_head head">
					   
    
<div class="image">
	
	

	
        <img style="width:254px; height:330px; position:absolute;left:0px;top:0px;" src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/head/254x330/face0.png" alt="" />
        <img id="pvEye" style="width:254px; height:330px; position:absolute;left:0px;top:0px;" src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/head/254x330/eye/eye<?php echo $geteye; ?>.png" alt="" />
        <img id="pvEyebrow" style="width:254px; height:330px; position:absolute;left:0px;top:0px;" src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/head/254x330/eyebrow/eyebrow<?php echo $geteyebrow.(($gender==0)?'-'.$color:''); ?>.png" alt="" />
        <?php if(!($gender==0 && $gethair==5)){ ?><img id="pvHair" style="width:254px; height:330px; position:absolute;left:0px;top:0px;" src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/head/254x330/hair/hair<?php echo $gethair.'-'.$color; ?>.png" alt="" /><?php } ?>
        <img id="pvFace" style="width:254px; height:330px; position:absolute;left:0px;top:0px;" src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/head/254x330/face/face<?php echo $getface; ?>.png" alt="" />
        <img id="pvMouth" style="width:254px; height:330px; position:absolute;left:0px;top:0px;" src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/head/254x330/mouth/mouth<?php echo $getmouth; ?>.png" alt="" />
        <img id="pvNose" style="width:254px; height:330px; position:absolute;left:0px;top:0px;" src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/head/254x330/nose/nose<?php echo $getnose; ?>.png" alt="" />
        <img id="pvEar" style="width:254px; height:330px; position:absolute;left:0px;top:0px;" src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/head/254x330/ear/ear<?php echo $getear; ?>.png" alt="" />
        <?php if($getbeard!=5){ ?><img id="pvBeard" style="width:254px; height:330px; position:absolute;left:0px;top:0px;" src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/head/254x330/beard/beard<?php echo $getbeard.'-'.$color; ?>.png" alt="" /><?php } ?>

<script type="text/javascript">
var heroEd = {
    g: "<?php echo $gstr; ?>",
    male: <?php echo ($herodetail['gender']==0)?'true':'false'; ?>,
    face: <?php echo $getface; ?>,
    color: <?php echo $herodetail['color']; ?>,
    hair: <?php echo $gethair; ?>,
    ear: <?php echo $getear; ?>,
    eyebrow: <?php echo $geteyebrow; ?>,
    eye: <?php echo $geteye; ?>,
    nose: <?php echo $getnose; ?>,
    mouth: <?php echo $getmouth; ?>,
    beard: <?php echo $getbeard; ?>,
    base: "<?php echo GP_LOCATION2; ?>hero/"
};
var heroColors = ["black", "brown", "darkbrown", "yellow", "red"];
var heroKeyMap = { HeroFace: "face", color: "color", HeroHair: "hair", HeroEar: "ear",
    HeroEyebrow: "eyebrow", HeroEye: "eye", HeroNose: "nose", HeroMouth: "mouth", HeroBeard: "beard" };

function heroPick(field, val, el) {
    var f = document.getElementById("HeroEditorForm");
    if (f && f[field]) { f[field].value = val; }
    var n = parseInt(val, 10);
    heroEd[heroKeyMap[field] || field] = isNaN(n) ? val : n;
    if (el && el.parentElement) {
        var imgs = el.parentElement.querySelectorAll("img.attribute");
        for (var i = 0; i < imgs.length; i++) { imgs[i].classList.remove("attributeSelected"); }
        el.classList.add("attributeSelected");
    }
    heroRender();
}

function heroSet(id, show, src) {
    var el = document.getElementById(id);
    if (!el) return;
    if (!show) { el.style.display = "none"; return; }
    el.style.display = "";
    if (el.getAttribute("src") != src) { el.setAttribute("src", src); }
}

function heroRender() {
    var b = heroEd.base + heroEd.g + "/head/254x330/";
    var c = heroColors[heroEd.color] || "black";
    var m = heroEd.male;
    heroSet("pvEye", true, b + "eye/eye" + (m ? heroEd.eye % 5 : heroEd.eye) + ".png");
    heroSet("pvEyebrow", true, b + "eyebrow/eyebrow" + (m ? heroEd.eyebrow % 5 : heroEd.eyebrow) + "-" + c + ".png");
    heroSet("pvHair", !(m && heroEd.hair == 5), b + "hair/hair" + heroEd.hair + "-" + c + ".png");
    heroSet("pvFace", true, b + "face/face" + (m ? heroEd.face % 5 : heroEd.face) + ".png");
    heroSet("pvMouth", true, b + "mouth/mouth" + (m ? heroEd.mouth % 4 : heroEd.mouth) + ".png");
    heroSet("pvNose", true, b + "nose/nose" + (m ? heroEd.nose % 5 : heroEd.nose) + ".png");
    heroSet("pvEar", true, b + "ear/ear" + (m ? heroEd.ear % 5 : heroEd.ear) + ".png");
    heroSet("pvBeard", m && heroEd.beard != 5, b + "beard/beard" + heroEd.beard + "-" + c + ".png");
}

(function heroMarkInitial() {
    var rows = document.querySelectorAll("#heroEditor .info .details");
    for (var r = 0; r < rows.length; r++) {
        var imgs = rows[r].querySelectorAll("img.attribute");
        for (var i = 0; i < imgs.length; i++) {
            var oc = imgs[i].getAttribute("onclick") || "";
            var mch = oc.match(/heroPick\("([^"]+)","(\d+)"/);
            if (!mch) continue;
            var key = heroKeyMap[mch[1]] || mch[1], v = parseInt(mch[2], 10);
            if (heroEd[key] === v) { imgs[i].classList.add("attributeSelected"); break; }
        }
    }
})();
heroRender();
</script>

    </div>
</div>
<div class="attributes">
<div >

    <div >
        <div >
            <div class="info">
                <div class="headline">
                
                </div>
                <div id="genderButtons">

                </div>
            </div>
        </div>
    </div>
</div>


<div style="left: 280px; background-color:#5e4500;" class="boxes boxesColor gray">
    <div class="boxes-tl"></div>
    <div class="boxes-tr"></div>
    <div class="boxes-tc"></div>
    <div class="boxes-ml"></div>
    <div class="boxes-mr"></div>
    <div class="boxes-mc"></div>
    <div class="boxes-bl"></div>
    <div class="boxes-br"></div>
    <div class="boxes-bc"></div>
    <div class="boxes-contents cf">
        <div class="container cf" id="attributesContainer">
            <div class="info" id="headProfile">
                <div class="headline switchClosed">
                    <a href="#" class="title"><?php echo JR_HEROHEAD; ?></a>
                    <div class="clear"></div>
                </div>
                <div   class="details ">
				
                    <img style=" width:60px; hight:100px; background-color:#5e4500;" id="attribute_button_0" class="attribute" onclick='heroPick(\"HeroFace\",\"0\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/face/face0.png" alt=""/>
                    <img id="attribute_button_1" class="attribute" onclick='heroPick(\"HeroFace\",\"1\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/face/face1.png" alt=""/>
                    <img id="attribute_button_2" class="attribute" onclick='heroPick(\"HeroFace\",\"2\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/face/face2.png" alt=""/>
                    <img id="attribute_button_3" class="attribute" onclick='heroPick(\"HeroFace\",\"3\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/face/face3.png" alt=""/>
                    <img id="attribute_button_4" class="attribute" onclick='heroPick(\"HeroFace\",\"4\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/face/face4.png" alt=""/>
                    <?php if ($herodetail['gender']==1) {?>
                        <img id="attribute_button_5" class="attribute" onclick='heroPick(\"HeroFace\",\"5\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/face/face5.png" alt=""/>
                    <?php } ?>
                    <div class="clear"></div>
                </div>
            </div>
            <div class="info" id="hairColor">
                <div class="headline switchClosed">
                    <a href="#" class="title"><?php echo JR_HEROHAIRCOLOR; ?></a>
                    <div class="clear"></div>
                </div>
                <div class="details">
                    <img id="attribute_button_0" class="attribute" onclick='heroPick(\"color\",\"0\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/hair/color0.png" alt=""/>
                    <img id="attribute_button_1" class="attribute" onclick='heroPick(\"color\",\"1\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/hair/color1.png" alt=""/>
                    <img id="attribute_button_2" class="attribute" onclick='heroPick(\"color\",\"2\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/hair/color2.png" alt=""/>
                    <img id="attribute_button_3" class="attribute" onclick='heroPick(\"color\",\"3\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/hair/color3.png" alt=""/>
                    <img id="attribute_button_4" class="attribute" onclick='heroPick(\"color\",\"4\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/hair/color4.png" alt=""/>
                    <div class="clear"></div>
                </div>
            </div>
            <div class="info" id="hairStyle">
                <div class="headline switchClosed">
                    <a href="#" class="title"><?php echo JR_HEROHAIRSTYLE; ?></a>
                    <div class="clear"></div>
                </div>
                <div class="details">
				
                    <img id="attribute_button_0" class="attribute" onclick='heroPick(\"HeroHair\",\"0\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/hair/hair0.png" alt=""/>
                    <img id="attribute_button_1" class="attribute" onclick='heroPick(\"HeroHair\",\"1\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/hair/hair1.png" alt=""/>
                    <img id="attribute_button_2" class="attribute" onclick='heroPick(\"HeroHair\",\"2\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/hair/hair2.png" alt=""/>
                    <img id="attribute_button_3" class="attribute" onclick='heroPick(\"HeroHair\",\"3\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/hair/hair3.png" alt=""/>
                    <img id="attribute_button_4" class="attribute" onclick='heroPick(\"HeroHair\",\"4\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/hair/hair4.png" alt=""/>
                    <?php if($herodetail['gender']==0) {?>
                        <img id="attribute_button_5" class="attribute" onclick='heroPick(\"HeroHair\",\"5\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/hair/hairNone.png" alt=""/>
                    <?php }?>
                    <?php if ($herodetail['gender']==1) {?>
                        <img id="attribute_button_5" class="attribute" onclick='heroPick(\"HeroHair\",\"5\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/hair/hair5.png" alt=""/>
                    <?php } ?>
                    <div class="clear"></div>
                </div>
            </div>

            <div class="info" id="ears">
                <div class="headline switchClosed">
                    <a href="#" class="title"><?php echo JR_HEROEARS; ?></a>
                    <div class="clear"></div>
                </div>
                <div class="details">
                    <img id="attribute_button_0" class="attribute" onclick='heroPick(\"HeroEar\",\"0\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/ear/ear0.png" alt=""/>
                    <img id="attribute_button_1" class="attribute" onclick='heroPick(\"HeroEar\",\"1\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/ear/ear1.png" alt=""/>
                    <img id="attribute_button_2" class="attribute" onclick='heroPick(\"HeroEar\",\"2\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/ear/ear2.png" alt=""/>
                    <img id="attribute_button_3" class="attribute" onclick='heroPick(\"HeroEar\",\"3\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/ear/ear3.png" alt=""/>
                    <img id="attribute_button_4" class="attribute" onclick='heroPick(\"HeroEar\",\"4\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/ear/ear4.png" alt=""/>
                    <?php  if ($herodetail['gender']==1) {?>
                        <img id="attribute_button_5" class="attribute" onclick='heroPick(\"HeroEar\",\"5\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/ear/ear5.png" alt=""/>
                        <img id="attribute_button_6" class="attribute" onclick='heroPick(\"HeroEar\",\"6\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/ear/ear6.png" alt=""/>
                        <img id="attribute_button_7" class="attribute" onclick='heroPick(\"HeroEar\",\"7\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/ear/ear7.png" alt=""/>
                    <?php } ?>
                    <div class="clear"></div>
                </div>
            </div>

            <div class="info" id="eyebrow">
                <div class="headline switchClosed">
                    <a href="#" class="title"><?php echo JR_HEROEYEBROWS; ?></a>
                    <div class="clear"></div>
                </div>
                <div class="details">
                    <img id="attribute_button_0" class="attribute" onclick='heroPick(\"HeroEyebrow\",\"0\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/eyebrow/eyebrow0.png" alt=""/>
                    <img id="attribute_button_1" class="attribute" onclick='heroPick(\"HeroEyebrow\",\"1\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/eyebrow/eyebrow1.png" alt=""/>
                    <img id="attribute_button_2" class="attribute" onclick='heroPick(\"HeroEyebrow\",\"2\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/eyebrow/eyebrow2.png" alt=""/>
                    <img id="attribute_button_3" class="attribute" onclick='heroPick(\"HeroEyebrow\",\"3\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/eyebrow/eyebrow3.png" alt=""/>
                    <img id="attribute_button_4" class="attribute" onclick='heroPick(\"HeroEyebrow\",\"4\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/eyebrow/eyebrow4.png" alt=""/>
                    <?php
                    if ($herodetail['gender']==1) {?>
                        <img id="attribute_button_5" class="attribute" onclick='heroPick(\"HeroEyebrow\",\"5\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/eyebrow/eyebrow5.png" alt=""/>
                        <img id="attribute_button_6" class="attribute" onclick='heroPick(\"HeroEyebrow\",\"6\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/eyebrow/eyebrow6.png" alt=""/>
                        <img id="attribute_button_7" class="attribute" onclick='heroPick(\"HeroEyebrow\",\"7\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/eyebrow/eyebrow7.png" alt=""/>
                        <img id="attribute_button_8" class="attribute" onclick='heroPick(\"HeroEyebrow\",\"8\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/eyebrow/eyebrow8.png" alt=""/>
                        <img id="attribute_button_9" class="attribute" onclick='heroPick(\"HeroEyebrow\",\"9\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/eyebrow/eyebrow9.png" alt=""/>
                    <?php } ?>
                    <div class="clear"></div>
                </div>
            </div>

            <div class="info" id="eyes">
                <div class="headline switchClosed">
                    <a href="#" class="title"><?php echo JR_HEROEYES; ?></a>
                    <div class="clear"></div>
                </div>
                <div class="details">
                    <img id="attribute_button_0" class="attribute" onclick='heroPick(\"HeroEye\",\"0\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/eye/eye0.png" alt=""/>
                    <img id="attribute_button_1" class="attribute" onclick='heroPick(\"HeroEye\",\"1\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/eye/eye1.png" alt=""/>
                    <img id="attribute_button_2" class="attribute" onclick='heroPick(\"HeroEye\",\"2\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/eye/eye2.png" alt=""/>
                    <img id="attribute_button_3" class="attribute" onclick='heroPick(\"HeroEye\",\"3\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/eye/eye3.png" alt=""/>
                    <img id="attribute_button_4" class="attribute" onclick='heroPick(\"HeroEye\",\"4\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/eye/eye4.png" alt=""/>
                    <?php if ($herodetail['gender']==1) {?>
                        <img id="attribute_button_5" class="attribute" onclick='heroPick(\"HeroEye\",\"5\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/eye/eye5.png" alt=""/>
                        <img id="attribute_button_6" class="attribute" onclick='heroPick(\"HeroEye\",\"6\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/eye/eye6.png" alt=""/>
                        <img id="attribute_button_7" class="attribute" onclick='heroPick(\"HeroEye\",\"7\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/eye/eye7.png" alt=""/>
                        <img id="attribute_button_8" class="attribute" onclick='heroPick(\"HeroEye\",\"8\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/eye/eye8.png" alt=""/>
                        <img id="attribute_button_9" class="attribute" onclick='heroPick(\"HeroEye\",\"9\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/eye/eye9.png" alt=""/>
                    <?php } ?>
                    <div class="clear"></div>
                </div>
            </div>

            <div class="info" id="nose">
                <div class="headline switchClosed">
                    <a href="#" class="title"><?php echo JR_HERONOSE; ?></a>
                    <div class="clear"></div>
                </div>
                <div class="details">
                    <img id="attribute_button_0" class="attribute" onclick='heroPick(\"HeroNose\",\"0\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/nose/nose0.png" alt=""/>
                    <img id="attribute_button_1" class="attribute" onclick='heroPick(\"HeroNose\",\"1\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/nose/nose1.png" alt=""/>
                    <img id="attribute_button_2" class="attribute" onclick='heroPick(\"HeroNose\",\"2\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/nose/nose2.png" alt=""/>
                    <img id="attribute_button_3" class="attribute" onclick='heroPick(\"HeroNose\",\"3\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/nose/nose3.png" alt=""/>
                    <img id="attribute_button_4" class="attribute" onclick='heroPick(\"HeroNose\",\"4\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/nose/nose4.png" alt=""/>
                    <?php if ($herodetail['gender']==1) {?>
                        <img id="attribute_button_5" class="attribute" onclick='heroPick(\"HeroNose\",\"5\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/nose/nose5.png" alt=""/>
                        <img id="attribute_button_6" class="attribute" onclick='heroPick(\"HeroNose\",\"6\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/nose/nose6.png" alt=""/>
                        <img id="attribute_button_7" class="attribute" onclick='heroPick(\"HeroNose\",\"7\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/nose/nose7.png" alt=""/>
                    <?php } ?>
                    <div class="clear"></div>
                </div>
            </div>

            <div class="info" id="mouth">
                <div class="headline switchClosed">
                    <a href="#" class="title"><?php echo JR_HEROMOUTH; ?></a>
                    <div class="clear"></div>
                </div>
                <div class="details">
                    <img id="attribute_button_0" class="attribute" onclick='heroPick(\"HeroMouth\",\"0\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/mouth/mouth0.png" alt=""/>
                    <img id="attribute_button_1" class="attribute" onclick='heroPick(\"HeroMouth\",\"1\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/mouth/mouth1.png" alt=""/>
                    <img id="attribute_button_2" class="attribute" onclick='heroPick(\"HeroMouth\",\"2\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/mouth/mouth2.png" alt=""/>
                    <img id="attribute_button_3" class="attribute" onclick='heroPick(\"HeroMouth\",\"3\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/mouth/mouth3.png" alt=""/>
                    <?php if ($herodetail['gender']==1) {?>
                        <img id="attribute_button_4" class="attribute" onclick='heroPick(\"HeroMouth\",\"4\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/mouth/mouth4.png" alt=""/>
                        <img id="attribute_button_5" class="attribute" onclick='heroPick(\"HeroMouth\",\"5\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/mouth/mouth5.png" alt=""/>
                        <img id="attribute_button_6" class="attribute" onclick='heroPick(\"HeroMouth\",\"6\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/mouth/mouth6.png" alt=""/>
                        <img id="attribute_button_7" class="attribute" onclick='heroPick(\"HeroMouth\",\"7\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/mouth/mouth7.png" alt=""/>
                        <img id="attribute_button_8" class="attribute" onclick='heroPick(\"HeroMouth\",\"8\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/mouth/mouth8.png" alt=""/>
                    <?php } ?>
                    <div class="clear"></div>
                </div>
            </div>
            <?php if($herodetail['gender']==0) { ?>

                <div class="info" id="beard">
                    <div class="headline switchClosed">
                        <a href="#" class="title"><?php echo JR_HEROBEARD; ?></a>
                        <div class="clear"></div>
                    </div>
                    <div class="details">
                        <img id="attribute_button_5000" class="attribute" onclick='heroPick(\"HeroBeard\",\"0\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/beard/beard0.png" alt=""/>
                        <img id="attribute_button_5001" class="attribute" onclick='heroPick(\"HeroBeard\",\"1\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/beard/beard1.png" alt=""/>
                        <img id="attribute_button_5002" class="attribute" onclick='heroPick(\"HeroBeard\",\"2\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/beard/beard2.png" alt=""/>
                        <img id="attribute_button_5003" class="attribute" onclick='heroPick(\"HeroBeard\",\"3\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/beard/beard3.png" alt=""/>
                        <img id="attribute_button_5004" class="attribute" onclick='heroPick(\"HeroBeard\",\"4\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/beard/beard4.png" alt=""/>
                        <img id="attribute_button_5999" class="attribute" onclick='heroPick(\"HeroBeard\",\"5\",this)' src="<?=GP_LOCATION2 ?>hero/<?php echo $gstr; ?>/thumb/head/beard/beardNone.png" alt=""/>
                        <div class="clear"></div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

                       </div>
     
                       <div class="selectionWrapper ">
                         <div class="page page0 active">
                           <div class="optionsWrapper">
            
                   
                        
                             <div class="  " data-option="8">
                               <div class="optionPreview female jaw"></div>
                               <div >
                                   <defs>
                                   
                             </div>
                             <div class="  " data-option="9">
                               <div class="optionPreview female jaw"></div>
                               <div >
                                   <defs>

                                   </defs>
                                
                             </div>
                        
                             </div>
                           
                             
                            
                             </div>
                           </div>
                         </div>
                         <div class="previewWrapper">
                           <div class="preview female zoomedIn "><canvas id="heroAppearancePreview" width="875" height="1574" style="touch-action: none; cursor: inherit;"></canvas></div>
                           <div class="actions"><button class="textButtonV2 buttonFramed toggleGender female  rectangle withIcon green" <?php if($herodetail['gender']==1){ echo "class=\"icon iconActive disabled\"";} else { echo "class='icon' onclick=\"$('HeroEditorForm').HeroGender.value='1'; $('HeroEditorForm').submit(); return false;\" ";}?> id="heroEditorActivateFemale" value="heroEditorActivateFemale" type="button"><svg><svg viewBox="0 0 21 21" class="outline">
                                   <path class="female" d="M21 .8v4.4a.8.8 0 0 1-.8.8h-.4a.8.8 0 0 1-.8-.8V3.42l-4 4a5.51 5.51 0 0 1-5.71 8.5 5.37 5.37 0 0 1-1.22-.43A6.46 6.46 0 0 0 10.2 14a3.36 3.36 0 0 0 1-.05A3.5 3.5 0 0 0 9.76 7.1a3.53 3.53 0 0 0-2.1 1.35 3.38 3.38 0 0 0-.59 1.36 3.41 3.41 0 0 0 0 1.49 2.44 2.44 0 0 1-1.07.58 2.62 2.62 0 0 1-.79 0l-.06-.21A5.52 5.52 0 0 1 9.37 5.1c.23 0 .46-.08.69-.1h.44a5.51 5.51 0 0 1 3.11 1l4-4H15.8a.8.8 0 0 1-.8-.8V.8a.8.8 0 0 1 .8-.8h4.4a.8.8 0 0 1 .8.8Z"></path>
                                   <path class="male" d="M10.82 8.11a2.62 2.62 0 0 0-.79 0 2.46 2.46 0 0 0-1.13.59v.11a3.51 3.51 0 0 1-2.77 4.12 3.41 3.41 0 0 1-1.35 0 2.79 2.79 0 0 1-.39-.1A3.5 3.5 0 0 1 5.8 6a6.55 6.55 0 0 1 2.09-1.45A5.58 5.58 0 0 0 5.41 4 5.57 5.57 0 0 0 0 9.22a5.49 5.49 0 0 0 4.5 5.68V17H3.3a.8.8 0 0 0-.8.8v.4a.8.8 0 0 0 .8.8h1.2v1.2a.8.8 0 0 0 .8.8h.4a.8.8 0 0 0 .8-.8V19h1.2a.8.8 0 0 0 .8-.8v-.4a.8.8 0 0 0-.8-.8H6.5v-2.1A5.53 5.53 0 0 0 11 9.5a5.41 5.41 0 0 0-.18-1.39Z"></path>
                                 </svg><svg viewBox="0 0 21 21" class="icon">
                                   <path class="female" d="M21 .8v4.4a.8.8 0 0 1-.8.8h-.4a.8.8 0 0 1-.8-.8V3.42l-4 4a5.51 5.51 0 0 1-5.71 8.5 5.37 5.37 0 0 1-1.22-.43A6.46 6.46 0 0 0 10.2 14a3.36 3.36 0 0 0 1-.05A3.5 3.5 0 0 0 9.76 7.1a3.53 3.53 0 0 0-2.1 1.35 3.38 3.38 0 0 0-.59 1.36 3.41 3.41 0 0 0 0 1.49 2.44 2.44 0 0 1-1.07.58 2.62 2.62 0 0 1-.79 0l-.06-.21A5.52 5.52 0 0 1 9.37 5.1c.23 0 .46-.08.69-.1h.44a5.51 5.51 0 0 1 3.11 1l4-4H15.8a.8.8 0 0 1-.8-.8V.8a.8.8 0 0 1 .8-.8h4.4a.8.8 0 0 1 .8.8Z"></path>
                                   <path class="male" d="M10.82 8.11a2.62 2.62 0 0 0-.79 0 2.46 2.46 0 0 0-1.13.59v.11a3.51 3.51 0 0 1-2.77 4.12 3.41 3.41 0 0 1-1.35 0 2.79 2.79 0 0 1-.39-.1A3.5 3.5 0 0 1 5.8 6a6.55 6.55 0 0 1 2.09-1.45A5.58 5.58 0 0 0 5.41 4 5.57 5.57 0 0 0 0 9.22a5.49 5.49 0 0 0 4.5 5.68V17H3.3a.8.8 0 0 0-.8.8v.4a.8.8 0 0 0 .8.8h1.2v1.2a.8.8 0 0 0 .8.8h.4a.8.8 0 0 0 .8-.8V19h1.2a.8.8 0 0 0 .8-.8v-.4a.8.8 0 0 0-.8-.8H6.5v-2.1A5.53 5.53 0 0 0 11 9.5a5.41 5.41 0 0 0-.18-1.39Z"></path>
                                 </svg></svg>
								 
								 
								 
								 
								 
								 
								 
								 </button><button class="textButtonV2 buttonFramed shuffleConfig  rectangle withIcon green" class="textButtonV1 green " onclick="$('HeroEditorForm').Random.value='random'; $('HeroEditorForm').submit(); return false;" type="button"><svg><svg viewBox="0 0 20 19" class="outline">
                                   <path d="M11.83 8.35A2.23 2.23 0 0 0 10.05 7l-4.6-.6a2.24 2.24 0 0 0-2 .8L.51 10.74A2.2 2.2 0 0 0 .16 13l1.59 4a2.25 2.25 0 0 0 1.79 1.39l4.59.61a2.24 2.24 0 0 0 2-.79l2.94-3.58a2.23 2.23 0 0 0 .35-2.23ZM5 8.67c.6-.28 1.22-.23 1.37.1s-.21.83-.82 1.11-1.21.23-1.36-.11S4.4 9 5 8.67Zm-3.37 5.21c-.31-.52-.3-1.1 0-1.28s.82.09 1.13.62.29 1.11 0 1.29-.82-.1-1.13-.63ZM4 17.54c-.32.18-.82-.1-1.13-.63s-.29-1.1 0-1.29.82.1 1.12.63.3 1.1 0 1.29Zm1-3.15c-.31-.53-.3-1.11 0-1.29s.82.1 1.12.63.3 1.1 0 1.28-.82-.09-1.12-.62Zm2.32 3.48c-.31.18-.82-.09-1.12-.62s-.3-1.11 0-1.29.82.09 1.13.62.29 1.11 0 1.29Zm1.75.59a.45.45 0 0 1-.58-.25l-2.1-5.3a.47.47 0 0 1-.17 0l-5.36-.7a.44.44 0 0 1-.39-.48.44.44 0 0 1 .45-.39H1l5.36.66.15.06 3.55-4.33a.44.44 0 0 1 .62-.06.43.43 0 0 1 .06.62l-3.5 4.31 2.09 5.29a.43.43 0 0 1-.24.57Zm1.29-1.91c-.36.07-.74-.41-.86-1.06s.08-1.23.44-1.3.75.42.87 1.07-.08 1.22-.44 1.29Zm.69-5.19c-.36.07-.75-.41-.86-1.06s.08-1.23.44-1.3.75.41.86 1.07-.08 1.22-.44 1.29Zm8.45-6.95L16.57.82a2.19 2.19 0 0 0-2-.8L10 .59A2.28 2.28 0 0 0 8.18 2L6.82 5.38l4 .52h.06A2.21 2.21 0 0 1 12.47 7l.3-.74a.47.47 0 0 1-.13-.11L9.22 1.93a.44.44 0 0 1 .64-.6l3.42 4.19a.55.55 0 0 1 .08.15L19 5a.44.44 0 0 1 .11.87l-5.51.7-.6 1.55 1.27 3.2a2.34 2.34 0 0 1 .15 1l2.02-.32a2.23 2.23 0 0 0 1.79-1.38l1.61-4a2.22 2.22 0 0 0-.34-2.21ZM9.3 3c.36.09.54.64.39 1.23s-.55 1-.9.92-.53-.63-.39-1.23.6-1.01.9-.92Zm3.91-.89c-.12.35-.72.45-1.34.24s-1-.67-.92-1 .72-.46 1.34-.24 1 .66.92 1Zm4.94 2.24c-.12.35-.72.46-1.34.24s-1-.66-.92-1 .72-.45 1.34-.24 1 .67.92 1Zm-2 4.8c-.42.51-1 .72-1.27.49s-.17-.84.26-1.35 1-.72 1.28-.49.16.84-.27 1.35Z"></path>
                                 </svg><svg viewBox="0 0 20 19" class="icon">
                                   <path d="M11.83 8.35A2.23 2.23 0 0 0 10.05 7l-4.6-.6a2.24 2.24 0 0 0-2 .8L.51 10.74A2.2 2.2 0 0 0 .16 13l1.59 4a2.25 2.25 0 0 0 1.79 1.39l4.59.61a2.24 2.24 0 0 0 2-.79l2.94-3.58a2.23 2.23 0 0 0 .35-2.23ZM5 8.67c.6-.28 1.22-.23 1.37.1s-.21.83-.82 1.11-1.21.23-1.36-.11S4.4 9 5 8.67Zm-3.37 5.21c-.31-.52-.3-1.1 0-1.28s.82.09 1.13.62.29 1.11 0 1.29-.82-.1-1.13-.63ZM4 17.54c-.32.18-.82-.1-1.13-.63s-.29-1.1 0-1.29.82.1 1.12.63.3 1.1 0 1.29Zm1-3.15c-.31-.53-.3-1.11 0-1.29s.82.1 1.12.63.3 1.1 0 1.28-.82-.09-1.12-.62Zm2.32 3.48c-.31.18-.82-.09-1.12-.62s-.3-1.11 0-1.29.82.09 1.13.62.29 1.11 0 1.29Zm1.75.59a.45.45 0 0 1-.58-.25l-2.1-5.3a.47.47 0 0 1-.17 0l-5.36-.7a.44.44 0 0 1-.39-.48.44.44 0 0 1 .45-.39H1l5.36.66.15.06 3.55-4.33a.44.44 0 0 1 .62-.06.43.43 0 0 1 .06.62l-3.5 4.31 2.09 5.29a.43.43 0 0 1-.24.57Zm1.29-1.91c-.36.07-.74-.41-.86-1.06s.08-1.23.44-1.3.75.42.87 1.07-.08 1.22-.44 1.29Zm.69-5.19c-.36.07-.75-.41-.86-1.06s.08-1.23.44-1.3.75.41.86 1.07-.08 1.22-.44 1.29Zm8.45-6.95L16.57.82a2.19 2.19 0 0 0-2-.8L10 .59A2.28 2.28 0 0 0 8.18 2L6.82 5.38l4 .52h.06A2.21 2.21 0 0 1 12.47 7l.3-.74a.47.47 0 0 1-.13-.11L9.22 1.93a.44.44 0 0 1 .64-.6l3.42 4.19a.55.55 0 0 1 .08.15L19 5a.44.44 0 0 1 .11.87l-5.51.7-.6 1.55 1.27 3.2a2.34 2.34 0 0 1 .15 1l2.02-.32a2.23 2.23 0 0 0 1.79-1.38l1.61-4a2.22 2.22 0 0 0-.34-2.21ZM9.3 3c.36.09.54.64.39 1.23s-.55 1-.9.92-.53-.63-.39-1.23.6-1.01.9-.92Zm3.91-.89c-.12.35-.72.45-1.34.24s-1-.67-.92-1 .72-.46 1.34-.24 1 .66.92 1Zm4.94 2.24c-.12.35-.72.46-1.34.24s-1-.66-.92-1 .72-.45 1.34-.24 1 .67.92 1Zm-2 4.8c-.42.51-1 .72-1.27.49s-.17-.84.26-1.35 1-.72 1.28-.49.16.84-.27 1.35Z"></path>
                                 </svg></svg>
								 
								 
								 
								 
								 
								 
								 
								 
								 <button class="textButtonV2 buttonFramed toggleGender male  rectangle withIcon green" <?php if($herodetail['gender']==0){ echo "class=\"icon iconActive disabled\"";} else { echo "class='icon' onclick=\"$('HeroEditorForm').HeroGender.value='0'; $('HeroEditorForm').submit(); return false;\" ";}?> id="heroEditorActivateMale" value="heroEditorActivateMale" type="button">><svg><svg viewBox="0 0 21 21" class="outline">
      <path class="female" d="M21 .8v4.4a.8.8 0 0 1-.8.8h-.4a.8.8 0 0 1-.8-.8V3.42l-4 4a5.51 5.51 0 0 1-5.71 8.5 5.37 5.37 0 0 1-1.22-.43A6.46 6.46 0 0 0 10.2 14a3.36 3.36 0 0 0 1-.05A3.5 3.5 0 0 0 9.76 7.1a3.53 3.53 0 0 0-2.1 1.35 3.38 3.38 0 0 0-.59 1.36 3.41 3.41 0 0 0 0 1.49 2.44 2.44 0 0 1-1.07.58 2.62 2.62 0 0 1-.79 0l-.06-.21A5.52 5.52 0 0 1 9.37 5.1c.23 0 .46-.08.69-.1h.44a5.51 5.51 0 0 1 3.11 1l4-4H15.8a.8.8 0 0 1-.8-.8V.8a.8.8 0 0 1 .8-.8h4.4a.8.8 0 0 1 .8.8Z"></path>
      <path class="male" d="M10.82 8.11a2.62 2.62 0 0 0-.79 0 2.46 2.46 0 0 0-1.13.59v.11a3.51 3.51 0 0 1-2.77 4.12 3.41 3.41 0 0 1-1.35 0 2.79 2.79 0 0 1-.39-.1A3.5 3.5 0 0 1 5.8 6a6.55 6.55 0 0 1 2.09-1.45A5.58 5.58 0 0 0 5.41 4 5.57 5.57 0 0 0 0 9.22a5.49 5.49 0 0 0 4.5 5.68V17H3.3a.8.8 0 0 0-.8.8v.4a.8.8 0 0 0 .8.8h1.2v1.2a.8.8 0 0 0 .8.8h.4a.8.8 0 0 0 .8-.8V19h1.2a.8.8 0 0 0 .8-.8v-.4a.8.8 0 0 0-.8-.8H6.5v-2.1A5.53 5.53 0 0 0 11 9.5a5.41 5.41 0 0 0-.18-1.39Z"></path>
    </svg><svg viewBox="0 0 21 21" class="icon">
      <path class="female" d="M21 .8v4.4a.8.8 0 0 1-.8.8h-.4a.8.8 0 0 1-.8-.8V3.42l-4 4a5.51 5.51 0 0 1-5.71 8.5 5.37 5.37 0 0 1-1.22-.43A6.46 6.46 0 0 0 10.2 14a3.36 3.36 0 0 0 1-.05A3.5 3.5 0 0 0 9.76 7.1a3.53 3.53 0 0 0-2.1 1.35 3.38 3.38 0 0 0-.59 1.36 3.41 3.41 0 0 0 0 1.49 2.44 2.44 0 0 1-1.07.58 2.62 2.62 0 0 1-.79 0l-.06-.21A5.52 5.52 0 0 1 9.37 5.1c.23 0 .46-.08.69-.1h.44a5.51 5.51 0 0 1 3.11 1l4-4H15.8a.8.8 0 0 1-.8-.8V.8a.8.8 0 0 1 .8-.8h4.4a.8.8 0 0 1 .8.8Z"></path>
      <path class="male" d="M10.82 8.11a2.62 2.62 0 0 0-.79 0 2.46 2.46 0 0 0-1.13.59v.11a3.51 3.51 0 0 1-2.77 4.12 3.41 3.41 0 0 1-1.35 0 2.79 2.79 0 0 1-.39-.1A3.5 3.5 0 0 1 5.8 6a6.55 6.55 0 0 1 2.09-1.45A5.58 5.58 0 0 0 5.41 4 5.57 5.57 0 0 0 0 9.22a5.49 5.49 0 0 0 4.5 5.68V17H3.3a.8.8 0 0 0-.8.8v.4a.8.8 0 0 0 .8.8h1.2v1.2a.8.8 0 0 0 .8.8h.4a.8.8 0 0 0 .8-.8V19h1.2a.8.8 0 0 0 .8-.8v-.4a.8.8 0 0 0-.8-.8H6.5v-2.1A5.53 5.53 0 0 0 11 9.5a5.41 5.41 0 0 0-.18-1.39Z"></path>
    </svg></svg></button></div>
                         </div>
               
					   

					   
					   
                       <div class="buttonWrapper">
                       
                   












<div class="options">
    <form id="HeroEditorForm" method="post" action="hero.php">
        <button type="button" value="" name="" id="btn_login" style="float:right;" class="textButtonV2 buttonFramed buyOptions  disabled rectangle withText gold" type="button">
            <div class="button-container addHoverClick">
                <div class="button-background">
                    <div class="buttonStart">
                        <div class="buttonEnd">
                            <div class="buttonMiddle"></div>
                        </div>
                    </div>
                </div>
                <div class="button-content"><?=herohero6?></div></div>
        </button>
        
        <button class="textButtonV2 buttonFramed saveChanges  rectangle withText green" type="submit" value="save" name="save" id="btn_login" style="float:left;" class="textButtonV1 green ">
            <div class="button-container addHoverClick">
                <div class="button-background">
                    <div class="buttonStart">
                        <div class="buttonEnd">
                            <div class="buttonMiddle"></div>
                        </div>
                    </div>
                </div>
                <div class="button-content"><?php echo JR_SAVE; ?></div></div>
        </button>
		
		
		
		
		
		
		
		
		
		
        <input type="hidden" name="uid" value="<?php echo $session->uid; ?>" />
        <input type="hidden" name="HeroFace" value="<?php echo $herodetail['face']; ?>" />
        <input type="hidden" name="color" value="<?php echo $herodetail['color']; ?>" />
        <input type="hidden" name="HeroHair" value="<?php echo $herodetail['hair']; ?>" />
        <input type="hidden" name="HeroEar" value="<?php echo $herodetail['ear']; ?>" />
        <input type="hidden" name="HeroEyebrow" value="<?php echo $herodetail['eyebrow']; ?>" />
        <input type="hidden" name="HeroEye" value="<?php echo $herodetail['eye']; ?>" />
        <input type="hidden" name="HeroNose" value="<?php echo $herodetail['nose']; ?>" />
        <input type="hidden" name="HeroMouth" value="<?php echo $herodetail['mouth']; ?>" />
        <input type="hidden" name="HeroBeard" value="<?php echo $herodetail['beard']; ?>" />
        <input type="hidden" name="HeroGender" value="<?php echo $herodetail['gender']; ?>" />
        <input type="hidden" name="Random" value="" />
    </form>
</div>
		  <button class="textButtonV2 buttonFramed resetChanges  disabled rectangle withIcon green" type="submit" value="save" name="save" id="btn_login" type="button"><svg><svg viewBox="0 0 16.02 16" class="outline">
        <path d="M2.35 2.35A8 8 0 1 1 .27 10h2.08A6 6 0 1 0 8 2a5.93 5.93 0 0 0-4.22 1.78L7 7H0V0Z" data-name="Path 78"></path>
      </svg><svg viewBox="0 0 16.02 16" class="icon">
		
		
</div>

<div class="clear"></div>
</div>












<script type="text/javascript">
    new Travian.Game.Hero.Editor(
        {
            element: 'heroEditor',
            command: 'heroEditor',
           // urlHeroImage: 'hero_image.php?uid=<?php //echo $session->uid; ?>&amp;size=sideinfo&amp;{time}',
            attributes: {"headProfile":<?php echo $herodetail['face']; ?>,"hairColor":<?php echo $herodetail['color']; ?>,"hairStyle":<?php echo $herodetail['hair']; ?>,"ears":<?php echo $herodetail['ear']; ?>,"eyebrow":<?php echo $herodetail['eyebrow']; ?>,"eyes":<?php echo $herodetail['eye']; ?>,"nose":<?php echo $herodetail['nose']; ?>,"mouth":<?php echo $herodetail['mouth']; ?>,"beard":<?php echo $herodetail['beard']; ?>,"gender":"<?php echo $gstr; ?>"},
            elementHeroImage: 'heroImage'
        });
</script>



<div class="clear">&nbsp;</div>
</div>
<div class="clear"></div>
</div></div>
</div>
</div>
</div>
</div>

<?php
include("application/views/rightsideinfor.php");
?>
<div class="clear"></div>
</div>
<?php

 include("application/views/header.php");
     
        include("application/views/footer.php");
?>
</div>
<div id="ce"></div>
</div>
</body>
</html>
