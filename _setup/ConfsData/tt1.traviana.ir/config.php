<?php
set_time_limit(0);
error_reporting(E_ALL );

//////////////////////////////////
//   ****  SQL SETTINGS  ****   //
//////////////////////////////////
define("SQL_SERVER", "localhost");
define('SQL_USER', '');;
define('SQL_PASS', '');
define('SQL_DB', '');



include_once(APP_BASE_PATH."/DB.php");
$sData = $db->queryFetch("SELECT * FROM config");
$ppData = $db->query("SELECT * FROM buyfeature ")[0];

define("SERVER_NAME",$sData['SERVER_NAME']); // titre server
define("DEFAULT_GOLD",$sData['DEFAULT_GOLD']);  // gold 
define("AUCTIONTIME",$sData['AUCTIONTIME']); //  auction seconds
define("GP_LOCATE", $sData['GP_LOCATE']);
define("GP_LOCATION", "https://gpack1.tdgpacks.ir/gpack/");
define("GP_LOCATION1", "https://gpack1.tdgpacks.ir/js/");
define("GP_LOCATION2", "https://gpack1.tdgpacks.ir/img/");
define("GP_LOCATIONALL", "https://gpack1.tdgpacks.ir/");
define("OPENING", $sData['OPENING']); 
define( "REINSTALL_TIME",$sData['reinstalltime']);
if($sData['OPENING']+259200 <= time() ){
    if($sData['regstatus']==1){
        $db->query("update config set regstatus ='0' ");
    }
   
    
}else{
    if($sData['regstatus']==0){
        $db->query("update config set regstatus ='1' ");
    }
}
//$db->query("update config set regstatus ='1' ");
define("REF_POP",500); 
define("LOYALGOLD",1000); 
define("REF_GOLD",50);
define("OASISX",$sData['OASISX']); 
define("SPEED", $sData['SPEED']); // speed server
define("wwneedalli", $sData['wwneedalli']);
define("ENDLESS", $sData['endless']);
define("MAX_FILES",3000);
define("MAX_FILESH",3000);
define("IMGQUALITY",100);
define("MOMENT_TRAIN",$sData['MOMENT_TRAIN']); 
define("QUEST",True);
define("ARTEFACTS",$sData['ARTEFACTS']); // date artefacts
define("WW_PLAN",$sData['WW_PLAN']); // date plan Building
define("CRANNY_CAP",$sData['CRANNY_CAP']); // cranny
if(($sData['ARTEFACTS']-86400)<time()){
    define("CATASS",true);
}else{
    define("CATASS",false);
}
define("ADV_TIME",86400/$sData['ADV_TIME']); // adventure
define("HERO_ADV",0); // adventure
define("TRAPPER_CAPACITY",$sData['TRAPPER_CAPACITY']); 
define("LANG", $sData['Lang']); // speed server
define("NEWSBOX1", 1);
define("NEWSBOX2", 1);
define("TRI5BES", TRUE); // TRUE OR FALSE
define("MASTERCOUNT",3); 

define("MAX_UNIT",70); // 
define("MAX_TRIBE",7);
// ***** Change storage capacity
define("STORAGE_MULTIPLIER",$sData['STORAGE_MULTIPLIER']); // storage
define("STORAGE_BASE",800*STORAGE_MULTIPLIER);

// ***** World size
// Defines world size. NOTICE: DO NOT EDIT!!
define("WORLD_MAX", "100");

define("INCREASE_SPEED",$sData['INCREASE_SPEED']); // speed Forces

// ***** Beginners Protection
define("PHOUR", "3600");
define("PROTECTIOND",$sData['PROTECTIOND']);
$timestoup = 0;
$fromstart=time()-OPENING;
if($fromstart>=42300){
$timestoup=floor($fromstart/42300);
}

define("PROTECTION",PROTECTIOND);
// ***** Trader capacity
// Values: 1 (normal), 3 (3x speed) etc...
define("TRADER_CAPACITY",$sData['TRADER_CAPACITY']); // 

define("INCLUDE_ADMIN",True);

define("CP", "1");

// ***** PLUS
//Plus account lenght
define("PLUS_TIME",$sData['PLUS_TIME']);
define("PLUS_TIME",$sData['PLUS_TIME']);
//+25% production lenght
define("PLUS_PRODUCTION",$sData['PLUS_PRODUCTION']);
// ***** through сколько клеток начинается дейстinие арены
define("TS_THRESHOLD",20);

define("TOP10N1",1000);

define("TOP10N2",700);

define("TOP10N3", 400);
define("PROTECTBUYLIMIT",3); // Completely demolished





//////////////////////////////////////////
//   ****  DO NOT EDIT SETTINGS  ****   //
//////////////////////////////////////////
define("ALLOW_ALL_TRIBE",false);
define("USRNM_MIN_LENGTH",3);
define("PW_MIN_LENGTH",4);
define("BANNED",0);
define("MULTIHUNTER",8);
define("ADMIN",9);
define("COOKIE_EXPIRE", 60*60*24*7);
define("COOKIE_PATH", "/");
define("HOMEPAGE", $sData['HOMEPAGE']);
define("MAXLENGHT","15");
define("RADIUS",2); //делитель для максимального радиуса inыпадения артоin,например если карта 400 а делитель 2 то дальше 200х200 арт НЕ улетит


// New setting

define("MAX_LEVEL", FALSE); //  With gold
define("MAX_LEVEL_COST", 0); // With gold

define("FINISH_ALL",FALSE); // Training With gold 
define("FINISH_ALL_COST",30);

define("MEDALS", $sData['MEDALS']); // distribution seconds

define("farmList", 20); 
define("maxOasisRes", 10000); 

define("DEMOLISH_FULL",0); // Completely demolished


define("adminMail", $sData['adminMail']);
if(empty($sData['serverpage'])){
    $parsed_url = parse_url($sData['HOMEPAGE'], PHP_URL_HOST);
    
    // Remove 'www.' prefix if present
    if (substr($parsed_url, 0, 4) == "www.") {
        $parsed_url = substr($parsed_url, 4);
    }
    $db->query("update config set serverpage ='". $parsed_url."' ");
    define("SERVERPAGE",$parsed_url);
}else{
    define("SERVERPAGE",$sData['serverpage']);
}

if(empty($sData['HASHASHIAN'])){
    $hashss=md5("Travian_ts1.tramian.ir_Design");
     $db->query("update config set HASHASHIAN ='".$hashss."' ");
     define("HASHASHIAN",$hashss);
}else{
    define("HASHASHIAN",$sData['HASHASHIAN']);
}
date_default_timezone_set('Asia/Tehran');
define("BAGOLD_ADD",3000);
define("BDGOLD_ADD",3000);
define("WINNERGOLD_ADD",15000);
define("ND2GOLD_ADD",4000);
define("GEGOLD_ADD",8000);
define("WAGOLD_ADD",750);
global $connection;
$connection = [
    'speed' =>  $sData['SPEED'],
    'round_length' => '10',
    'worldId' => '100X',
    'secure_hash_code' => HASHASHIAN,
    'title' => $sData['SERVER_NAME'],
    'gameWorldUrl' => $sData['HOMEPAGE'],
    'serverName' => $sData['SERVER_NAME'],
    'start' => $sData['OPENING'],
    'auto_reinstall' => '0',
    'auto_reinstall_start_after' => '86400',
    'engine_filename' => 'travian_dev.service',
    'database' => [
        'hostname' => SQL_SERVER,
        'username' => SQL_USER,
        'password' => SQL_PASS,
        'database' => SQL_DB,
        'charset' => 'utf8mb4',
    ],
];
$confing = $ppData;
$sagdata=array();
foreach($confing as $k=>$v){
    $sagdata[$k]=json_decode($v,true);
}
//menus
$plusPacksNew=array();
$elhaghi=array();
if(isset($sagdata['menus']['generals'])&&$sagdata['menus']['generals']>0){
    foreach($sagdata['generals'] as $k=>$v){
        $plusPacksNew[$k]=json_decode($v,true);
    }
   
}else{
    foreach($sagdata['generals'] as $k=>$v){
        $plusPacksNew[$k]=0;
    }
}

$men['generals']=isset($sagdata['menus']['generals'])?$sagdata['menus']['generals']:0;
$men['buyresources']=isset($sagdata['menus']['buyresources'])?$sagdata['menus']['buyresources']:0;
$men['buytroops']=isset($sagdata['menus']['buytroops'])?$sagdata['menus']['buytroops']:0;
$men['buywilds']=isset($sagdata['menus']['buywilds'])?$sagdata['menus']['buywilds']:0;
$men['buyprotection']=isset($sagdata['menus']['buyprotection'])?$sagdata['menus']['buyprotection']:0;


$config = array(
    
    // Paypal
    'paypalStatus' => 'sandbox', // live - sandbox 
    'paypalAPIUser' => '',
    'paypalAPIPwd' => '',
    'paypalAPISign' => '',
    'ZarinpalapiKey' => '',

    // SMTP Setting
    'SMTPHost' => '127.0.0.1',
    'SMTPUser' => '', // mail
    'SMTPPass' => '', // password

 
    'goldClub' => $sData['goldClub'],
    'Plus' => $sData['Plus'],
    'addonProduction' => $sData['addonProduction'],

    'needActivate' => 1,
    'plusFeatures' => $men['generals'], 
    'storageUpgrade' => $plusPacksNew['storageUpgrade'], 
    '25pFaster' => $plusPacksNew['25pFaster'], 
    'allSmithy' => $plusPacksNew['allSmithy'], 
    'searchAll' => $plusPacksNew['searchAll'], 
    'moreClub' => $plusPacksNew['moreClub'], 
    'delTrain' => $plusPacksNew['delTrain'], 
   
);
if(isset($sagdata['buyfeatures'])&&$sagdata['menus']['buyresources']>0){
    $ii=0;
    foreach($sagdata['buyfeatures'] as $k=>$v){
       // 
      //  $v=json_decode($v,true);
      
        if($k =='buyres'.$ii ){
            //var_dump($k);die();
            $config[$k]=$v['cost'];
            $config[$k.'A']=$v['num'];
            $ii++;
        }
        
      
    }
   
}

if(isset($sagdata['buyfeatures'])&&$sagdata['menus']['buytroops']>0){
    $ii=0;
    foreach($sagdata['buyfeatures'] as $k=>$v){
        //$v=json_decode($v,true);
        if($k =='buytro'.$ii ){
           //var_dump($k);die();
           $config[$k]=$v['cost'];
           $config[$k.'A']=$v['num'];
           $config['cata'.$ii]=$v['cata'];
           
            $ii++;
        }
      
    }
   
}
if(isset($sagdata['buyfeatures'])&&$sagdata['menus']['buywilds']>0){
    $ii=0;
    foreach($sagdata['buyfeatures'] as $k=>$v){
        //$v=json_decode($v,true);
        if($k =='buywil'.$ii ){
           //var_dump($k);die();
           $config[$k]=$v['cost'];
           $config[$k.'A']=$v['num'];
           $config['phil'.$ii]=$v['phil'];
            $ii++;
        }
      
    }
   
}
if(isset($sagdata['buyothers'])&&$sagdata['menus']['buyprotection']>0){
    $ii=0;
    foreach($sagdata['buyothers'] as $k=>$v){
        //$v=json_decode($v,true);
        if($k =='buyprotection'.$ii ){
           //var_dump($k);die();
           $config["protect".$ii]=$v['time'];
           $config["protect0".$ii]=$v['cost'];
           
            $ii++;
        }
      
    }
   
}
$packages	= array (
);

if(isset($sagdata['buypacks'])){
    $ii=0;
    foreach($sagdata['buypacks'] as $k=>$v){
       // $v=json_decode($v,true);
        $iii=$ii+1;
        if($iii>=7){
            $iiii=6;
        }else{
            $iiii=$iii;
        }
            $packages[]=array(
                'id' 		  => $iii,
                'gold'		=> $v['gold'],
                'cost'		=> $v['price'],
                'currency'	=> 'IRT',
                'img'       => 'Travian_Facelift_'.$iiii.'.png'
            );
            $ii++;
        
      
    }
   
}
global $packages;
