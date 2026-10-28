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
class Registr {
	function register($username, $password, $email, $tribe, $ref, $country) {
		global $database;
        $country = (!$country)? '': $country;
		$time = time();
		$starttime= OPENING;
		if($starttime < $time){$timep = $time;}else{$timep = $starttime;}
        $params = array( 'user' => $username, 'pass' => $password, 'email' => $email, 'tribe' =>intval($tribe),'ref' => intval($ref),'timer'=> $timep,'up'=> $timep,'country'=>$country);
		//$ptime = time() + PROTECTION;
		
		
		$nuidaa=$database->registerMine($params);
		if(!empty($ref)){
			$p=array("u"=>$nuidaa , "inby"=>$ref);
			$q = "INSERT INTO referals (uid,invitedby, vilage ) VALUES (:u , :inby , 0 )";
		$database->query($q,$p);
		}
		return $nuidaa;

	}

	function checkActivate($act) {
	global $database;
		$q = "SELECT * FROM activate where act = :act";
		$params = array('act'=>$act);
      return $database->query($q,$params);
	}
    function checkAccount($name,$email) {
        global $database;
        $q = "SELECT * FROM activate where `username` = :name or `email` = :email";
        $params = array('name'=>$name,'email'=>$email);
        return $database->query($q,$params);
    }

		function activate($username, $password, $email, $tribe, $loc,  $act, $act2,$ref,$country) {
		global $database;
		$params = array('user'=>$username,'pass'=>$password,'email'=>$email,'tribe'=>0,'act'=>$act,'act2'=>$act2,'ref'=>$ref,'time'=>time(),'location'=>0,'country'=>$country);
		$q = "INSERT INTO activate (username,password,email,tribe,timestamp,act,act2,ref,location,country) VALUES (:user, :pass, :email, :tribe, :time, :act, :act2,:ref,:location,:country)";
		$database->query($q,$params);
		return $database->get_last_id();
	}

		function unreg($username) {
		global $database;
		$params=array('user'=>$username);
		$q = "DELETE from activate where username = '".$username."'";
		$database->query($q,$params);
	}
}
$regme= new Registr;
