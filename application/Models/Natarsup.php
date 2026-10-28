<?php
/*
     
*/
class NatarsUp
{
/*     public function checkStatusnnn()
    {
        global $database;

        $qData = $database->queryFetch("SELECT * FROM `naratsjob`");
        if (count($qData) == 0) {
            $this->startjobnn();
        } else {
            $this->updatejobnn($qData);
        }
    }

    function startjobnn()
    {
        global $database, $bid40;
        $estend = 0;
        $dataarray = $bid40;
        for ($i = 1; $i <= 100; $i++) {
            $estend += $dataarray[$i]['time'];


        }
        $jobs = $database->queryFetch("SELECT * FROM `vdata` where owner = 3 , natar =1 order by created asc");
        if (count($jobs) > 0) {

            //run upgrade 
            $wid = $jobs[0]['wref'];
            $this->createjobnn($wid, $estend);

        } else {
            //no narats found to upgrade sp get from user 
            $jobs = $database->queryFetch("SELECT * FROM `vdata` where  natar =1 order by created desc limit 1");
            if (count($jobs) == 0) {
                //cretical error no village exist


            } else {
                //set village for existant in natarjobs and send msg too all players...
                $wid = $jobs[0]['wref'];



                $this->createjobnn($wid, $estend);

            }
        }
    }
    function createjobnn($wid, $estend)
    {
        global $database, $global;
        $updateJobSql = "INSERT INTO `naratsjob` (nwid, level, start, estending) VALUES (NULL, '" . $wid . "', '0', '" . time() . "', '" . $estend . "')";
        $database->query($updateJobSql);
        //send message to all players about new job...
        $global->updateGlobal('NatarsWWupgd');

    }
    function updatejobnn($qData)
    {
        global $database, $bid40;
        foreach ($qData as $qjob) {
            $now = time();
            $jobs = $database->queryFetch("SELECT * FROM `bdata` where  wid ='" . $qjob['nwid'] . "' ");

            if (count($jobs) == 0) {
                //job do
                $level = $qjob['level'] + 1;
                $joboo = $database->queryFetch("SELECT f99 FROM `fdata` where  vref ='" . $qjob['nwid'] . "' ");
                $ttt = $joboo[0]['f99'];
                if ($ttt != $level) {
                    $level = $ttt;
                }
                $estend = 0;
                $dataarray = $bid40;
                $uptime = 0;
                for ($i = $level; $i <= 100; $i++) {
                    if ($i == $level) {
                        $uptime = $dataarray[$i]['time'] + time();
                    }
                    $estend += $dataarray[$i]['time'];


                }
                $database->query("UPDATE `naratsjob` SET `estending` = '$estend' , `level`='$level'  WHERE `nwid` = '" . $qjob['nwid'] . "'");
                //add upgrade for level
                $database->addBuilding($qjob['nwid'], 99, 40, 0, $uptime, $level, 0, 0);


            } else {
                break;
            }
        }



    } */



}
$natarsup = new NatarsUp;
?>