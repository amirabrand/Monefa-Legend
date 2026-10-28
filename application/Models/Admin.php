<?php
Class Admin{
    
    // Empty all oases from monsters
    function emptyO(){
        global $database, $session;

        if($session->access == 9){
            $oases = $database->query("SELECT * FROM odata WHERE conqured = 0");
            foreach($oases as $oasis){
                $database->query('UPDATE units SET u1=0,u2=0,u3=0,u4=0,u5=0,u6=0,u7=0,u8=0,u9=0,u10=0 WHERE vref = '.$oasis['wref'].'');
            }
        }
    }

    function deleteMsg($id){
        global $database;
        $database->query('DELETE FROM mdata WHERE id = '.$id.'');
    }
    function activeendless($life,$rest){
        global $database;
        $chcker=time();
       // $exp=time()+$life;
       

       $database->query('UPDATE config SET endless = 1 , `endless_checker` = "'.$chcker.'" , `endless_expire` = "'.$chcker.'" , `endless_lifer` = "'.$life.'" , `endless_rester` = "'.$rest.'" ');
       $database->query('DELETE FROM `artefacts` ');
    }
    function deactiveendless(){
        global $database;
        $database->query('UPDATE config SET endless = 0 ');
        $file = '/artefacts.txt';
		unlink($file);
        $file = '/ww_plans.txt';
		unlink($file);
			
		
    }

    function sUpdate($key, $value){
        global $database;
        $database->query('UPDATE config SET '.$key.' = "'.$value.'"');
    }
    function pUpdate($key, $value){
        global $database;
        $arr=array("V"=>$value);
        $database->query('UPDATE buyfeature SET '.$key.' = :V ',$arr);
    }

}

$panel = new Admin;