<?php
/*
     
*/
Class gMsg{
    public function getMsg(){
        global $database,$lang;

        $qData = $database->queryFetch("SELECT `global` FROM `config`");
        switch($qData['global']){
            case 'Arts':
                return $lang['Msgs']['Arts'];
            break;

            case 'WW':
                return $lang['Msgs']['WW'];
            break;
            case 'Farmsrels':
                return $lang['Msgs']['Farmsrels'];
            break;
            case 'NatarsWWupgd':
                return $lang['Msgs']['NatarsWWupgd'];
            break;
            default:
                return $qData['global'];
            break;
        }
    }

    function updateGlobal($g){
        global $database;
        
        $database->query('UPDATE `config` SET `global` = "'.addslashes($g).'"');
        $database->query('UPDATE `users` SET `msg` = 0');
    }
}

$global = new gMsg;