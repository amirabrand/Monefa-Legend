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
//error_reporting(E_ALL);
define("oMonsters", 86400);

if (file_exists(CONFSDATA . "/queue.txt")) {
    // var_dump($database->Winnerkills());die();
    $curr_time = time();
    if ($curr_time - @filemtime(CONFSDATA . "/queue.txt") >= 1) {
        @unlink(CONFSDATA . "/queue.txt");

        if ($curr_time > OPENING) {
            // NoBody - Better system
            $lastMedals = $database->config()['lastmedal'];
            if (time() - $lastMedals > MEDALS) {
                $database->query("UPDATE config SET lastmedal = " . time() . "");
                $database->query("UPDATE bounuses SET tweek = '0' , cpweek ='0' , sweek ='0' ,mcweek ='0'  ");
                $medals->dMedals();
            }
            $lastMedals1 = $database->config()['ddonatereset'];
            if (time() - $lastMedals1 > 86400) {
                $time222 = time();
                $database->query("UPDATE config SET ddonatereset = " . $time222 . "");
                $medals->ddreset($time222);
            }
        }


        include 'Attacks.php';

        class cron extends Att
        {


            function __construct()
            {
                global $database, $global;
                $t = time();
                /*
                $maxRetries = 3;
                $attempts = 0;

                while ($attempts < $maxRetries) {
                    $attempts++;

                    $database->query("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
                    $database->starttransaction();

                    try {
                        $sql = $database->query("SELECT id, if1, jobID, type FROM queue WHERE finish <= '" . $t . "' and status = '' ORDER BY finish ASC, id ASC FOR UPDATE");

                        if (!empty($sql)) {
                            $this->queue($sql);
                        }

                        $database->commitq(); // Commit the transaction if successful
                        break; // Exit the loop on success
                    } catch (Exception $e) {
                        $database->rollBackq(); // Rollback the transaction on failure
                        if ($e->getCode() == 1213) { // Check for deadlock error
                            // Log and handle deadlock (optional)
                            continue; // Retry the transaction
                        } else {
                            throw $e; // Rethrow unexpected exceptions
                        }
                    }
                }
                */

                $database->query("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
                $database->starttransaction();
                $sql = $database->query("SELECT id,if1,jobID,type FROM queue WHERE finish <= '" . $t . "' and status ='0' ORDER BY finish ASC,id ASC FOR UPDATE");
                //$database->query("UPDATE queue set `status` ='1' WHERE finish <= '" . $t . "'");
                // var_dump($sql);die();
                if (!empty($sql)) {
                    $this->queue($sql);
                }



                $database->commitq();
                $database->transferQueueBackup();
                //debug return:

                /* $q11 = "SELECT ref FROM movement as m INNER JOIN  attacks as a ON
                 a.id = m.ref
                 WHERE
                 m.proc = '0' and  m.sort_type = '4' and
                 m.endtime < '" . (time()-300) . "'";
     
             $data11 = $database->query($q11);
             foreach($data11 as $ff){
                 $this->returnCom($ff['ref']);
             }*/
                $this->Builder();
                $this->TradeRoute();

                $this->autoFarm();
                $this->MasterBuilder();
                $this->AutoAuction();
                $this->updateProtect();
                if (time() - $database->config()['lastioasisUpdate'] > oMonsters) {
                    $this->updateOases();
                }
                if (time() - $database->config()['lastAchiev'] > 86400) {
                    $database->RestartAchiev();
                    $database->query("UPDATE config SET lastAchiev = " . time() . "");
                }
                $this->autoRenew();
                if (OPENING + 7200 <= time() && $database->config()['farmrels'] == 0) {
                    for ($i = 1; $i <= 50; $i++) {
                        $database->generateFarm(0, 3, 'Natars');

                    }
                    $database->query("UPDATE config SET farmrels = " . time() . "");
                    $global->updateGlobal('Farmsrels');
                    $database->query('UPDATE `users` SET `msg` = 0');

                }

                $reviiv = $database->getReviveHerr();
                //var_dump($reviiv);die();
                if (is_array($reviiv) && count($reviiv) > 0) {
                    foreach ($reviiv as $herrr) {
                        $uid = $herrr['uid'];
                        $vref = $herrr['wref'];
                        $q = "UPDATE hero, units SET hero.dead = '0', hero.health = '100', hero.revivetime = '0', hero.lastupdate = '" . time() . "', units.u11 = 1 WHERE hero.uid = '" . $uid . "' AND units.vref = '" . $vref . "'";
                        $database->query($q);
                    }
                }
                if (ENDLESS) {
                    $this->calculateEndlessPoints();
                } else {
                    if (ARTEFACTS <= time() && !file_exists(CONFSDATA . "/artefacts.txt")) {
                        $ourFileHandle = fopen(CONFSDATA . "/artefacts.txt", 'w') or die("Unable to open file!");
                        fclose($ourFileHandle);
                        $this->showArts();
                        $global->updateGlobal('Arts');

                    }

                    if (WW_PLAN <= time() && !file_exists(CONFSDATA . "/ww_plans.txt")) {
                        $ourFileHandle = fopen(CONFSDATA . "/ww_plans.txt", 'w') or die("Unable to open file!");
                        fclose($ourFileHandle);
                        $this->showWWPlans();
                        $global->updateGlobal('WW');
                    }
                    $timernatarsjobs = REINSTALL_TIME;
                    $timernatarsjobs -= 86400;
                    $timm=time();
                   // 
                    if ($timernatarsjobs <= $timm) {
                       
                      // 
                        $this->checkStatusnnn();
                       

                    }
                }
                //manual stick time resolve
                $timer11 = time() - 300;
                /* $sql = $database->query("SELECT ref FROM movement WHERE sort_type = '3'  and proc ='0' and endtime < '" . $timer11 . "'");
                 foreach ($sql as $f) {

                     $this->attComFailed($f['ref']);
                 }
                 //advneture debug:
                 $q = "SELECT ref FROM movement where `proc` = '0' and `sort_type` = '9' and `endtime` < '" . (time()-60) . "'";
                 $data = $database->query($q);
                 foreach($data as $dd){
                     $this->sendAdventuresFailed($dd['ref']);
                 }*/
                //debug:


                /* $q = "SELECT ref FROM movement as m INNER JOIN  attacks as a ON
                     a.id = m.ref
                     WHERE
                     m.proc = '0' and
                     m.endtime < '" . (time()-100) . "' and m.sort_type='4' ";
         
                 $data1 = $database->query($q);
                 if (count($data1) > 0) {
                     foreach($data1 as $dd){
                         $this->ReturnFailed($dd['ref']);
                   
                 }
         
                 
                    
                 }*/


                //optimizing
                //  $table = $database->query("SHOW TABLES");
                // $i = 0;
                // while ($table = mysqli_fetch_assoc($alltables)) {
                /* foreach ($table as $db => $tablename) {
                     $database->query('OPTIMIZE TABLE ' . $tablename);
                     $database->query('REPAIR TABLE ' . $tablename);
                 }*/
                //}

            }

            function checkStatusnnn()
            {
                global $database;

                $qData = $database->query("SELECT * FROM natarsjob ");
                //var_dump($qData);die();
                if (count((array)$qData)==0 ) {
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
                $jobs = $database->query("SELECT * FROM `vdata` where owner = 3 and natar =1 order by created asc");
                if (count((array)$jobs) > 0) {

                    //run upgrade 
                    $wid = $jobs[0]['wref'];
                   // var_dump( $wid);die();
                    $this->createjobnn($wid, $estend);

                } else {
                    //no narats found to upgrade sp get from user 
                    $jobs = $database->query("SELECT * FROM `vdata` where  natar =1 order by created desc ");
                    if (count((array)$jobs) == 0) {
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
                $updateJobSql = "INSERT INTO `natarsjob` (`nwid`, `level`, `start`, `estending`) VALUES ( '" . $wid . "', '0', '" . time() . "', '" . $estend . "')";
                //var_dump( $updateJobSql  );die();
               
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

                    if (!$jobs) {
                        //job do
                       
                        $joboo = $database->queryFetch("SELECT f99 FROM `fdata` where  vref ='" . $qjob['nwid'] . "' ");
                        $ttt = $joboo['f99'];
                        if ($ttt != $level) {
                            $level = $ttt;
                        }
                        $level = $level + 1;
                        $estend = 0;
                        $dataarray = $bid40;
                        $uptime = 0;
                        if($level>100){
                            break;
                        }
                        for ($i = $level; $i <= 100; $i++) {
                            if ($i == $level) {
                                $uptime = ($dataarray[$i]['time']/(SPEED*10)) + time();
                            }
                            $estend += ($dataarray[$i]['time']/(SPEED*10));


                        }
                        $database->query("UPDATE `naratsjob` SET `estending` = '$estend' , `level`='$level'  WHERE `nwid` = '" . $qjob['nwid'] . "'");
                        //add upgrade for level
                        $database->addBuilding($qjob['nwid'], 99, 40, 0, $uptime, $level, 0, 0);


                    } else {
                        break;
                    }
                }



            }
            function calculateEndlessPoints()
            {
                global $database;
                //$percent, $user_id;
                // Fetch the current endless_checker value
                $sql = "SELECT endless_checker , endless_lifer , endless_expire , endless_rester FROM config LIMIT 1";
                $result = $database->queryFetch($sql);

                if ($result['endless_expire'] > time()) {
                    // Fetch the result as an associative array
                    // $row = $result->fetch_assoc();
                    $row = $result;
                    $endless_checker = (float) $row['endless_checker'];

                    // Get the current time
                    $current_time = microtime(true);
                    // var_dump(ROUND($endless_checker - $current_time));die();
                    if (ROUND($current_time - $endless_checker) > 5) {
                        // Calculate the time difference
                        $time_diff = $current_time - $endless_checker;
                        $sql = "SELECT a.f99t , a.f99 , b.owner FROM `fdata` as a left join `vdata` as b on(a.vref=b.wref) WHERE a.f99t='40'";
                        $result = $database->query($sql);
                        foreach ($result as $vals) {
                            // Calculate the endless_point
                            if (0 == $vals['f99']) {
                                $precent = 100;
                            } elseif (0 < $vals['f99'] && $vals['f99'] <= 20) {
                                $precent = 100 + 5;  // 0-20
                            } elseif (20 < $vals['f99'] && $vals['f99'] <= 40) {
                                $precent = 100 + 10; // 21-40
                            } elseif (40 < $vals['f99'] && $vals['f99'] <= 60) {
                                $precent = 100 + 15; // 41-60
                            } elseif (60 < $vals['f99'] && $vals['f99'] <= 99) {
                                $precent = 100 + 20; // 61-99
                            } elseif ($vals['f99'] == 100) {
                                $precent = 100 + 25; // 100
                            }
                            $endless_point = $time_diff * ($precent / 100);

                            // Update the user's endless_point in the database
                            $sql_update = "UPDATE users SET endless_point = endless_point + '" . $endless_point . "' , endless_time = endless_time + '" . $time_diff . "' WHERE id = '" . $vals['owner'] . "'";
                            $database->query($sql_update);
                        }
                        $sql_update = "UPDATE config SET endless_checker = '" . $current_time . "' ";
                        $database->query($sql_update);
                    }
                } elseif ($result['endless_expire'] < time() && ($result['endless_expire'] + $result['endless_rester']) > time()) {
                    // Do all resets here for endless and start new round
                    // if need send any msg! send
                    // Reset endpoint points 
                    $sql_update = "UPDATE users SET endless_point = '0' , endless_time = '0' ";
                    $database->query($sql_update);

                    // Get back WW village from player
                    $sql = "SELECT a.f99t , a.f99 , a.vref FROM `fdata` as a WHERE a.f99t='40'";
                    $result1 = $database->query($sql);
                    foreach ($result1 as $vals) {
                        $sql_update = "UPDATE vdata SET owner = '3' where wref='" . $vals['vref'] . "'";
                        $database->query($sql_update);


                        $units = 5000 * SPEED;  // Ensure SPEED is defined
                        $sql_update = "UPDATE `units` SET 
                            `u1` = '" . $units . "',
                            `u2` = '" . $units . "',
                            `u3` = '" . $units . "',
                            `u4` = '" . $units . "',
                            `u5` ='" . $units . "',
                            `u6` = '" . $units . "',
                            `u7` = '" . $units . "',
                            `u8` = '" . $units . "',
                            `u9` ='" . $units . "',
                            `u10` = '" . $units . "' where vref ='" . $vals['vref'] . "'";
                        $database->query($sql_update);
                    }
                    $sql_update = "UPDATE fdata SET wwname = 'Endless WWbuild' where f99t='40'";
                    $database->query($sql_update);

                    $sql_update = "UPDATE fdata SET f99 = '0' ";
                    $database->query($sql_update);

                    // Reset units in village and re units WW villages
                    $sql_update = "UPDATE `units` SET 
                        `u1` = 0,
                        `u2` = 0,
                        `u3` = 0,
                        `u4` = 0,
                        `u5` = 0,
                        `u6` = 0,
                        `u7` = 0,
                        `u8` = 0,
                        `u9` = 0,
                        `u10` = 0 ,`u99`=0";
                    $database->query($sql_update);

                    $sql_update = "DELETE FROM `training`";
                    $database->query($sql_update);
                    $sql_update = "DELETE FROM `enforcement`";
                    $database->query($sql_update);

                    // Reset ab_data
                    $sql_update = "UPDATE `abdata` SET 
                        `a1` = 0,
                        `a2` = 0,
                        `a3` = 0,
                        `a4` = 0,
                        `a5` = 0,
                        `a6` = 0,
                        `a7` = 0,
                        `a8` = 0,
                        `b1` = 0,
                        `b2` = 0,
                        `b3` = 0,
                        `b4` = 0,
                        `b5` = 0,
                        `b6` = 0,
                        `b7` = 0,
                        `b8` = 0";
                    $database->query($sql_update);

                    // Reset timers in config 
                    $newexpire = $result['endless_expire'] + $result['endless_rester'] + $result['endless_lifer'];
                    $newchecker = $result['endless_expire'] + $result['endless_rester'];
                    $sql_update = "UPDATE config SET wwneedalli='0', endless_checker = '" . $newchecker . "' , endless_expire = '" . $newexpire . "'";
                    $database->query($sql_update);
                }
            }


            function AutoAuction()
            {
                global $database;
                $t = $_SERVER['REQUEST_TIME'];

                $q = "SELECT `itemid` FROM auction WHERE owner = '6' ORDER BY itemid ASC LIMIT 1";
                $result1 = $database->query($q);
                $result = $result1[0];

                if ($result["itemid"] > 0) {
                    $iiddd = $result["itemid"];
                    $q = "delete  FROM heroitems WHERE uid = '6' AND id < '" . $iiddd . "' ";
                    $result = $database->query($q);
                    // var_dump(mysqli_fetch_assoc($result));
                    //die($q);
                }

                $q = 'SELECT `time`,`lasttime`,`id`,`number` FROM autoauction WHERE active = 1';
                $result = $database->query($q);

                foreach ($result as $row) {

                    if (($row['time'] * 60 + $row['lasttime']) - $t < 0) {

                        $btype = $y = $row['id'];

                        $item_qty = $row['number'];

                        $item_time = $row['time'] * 60;


                        switch ($btype) {
                            case 1:
                                $type = rand(1, 15);
                                break;
                            case 2:
                                $type = rand(82, 93);
                                break;
                            case 3:
                                $type = rand(61, 81);
                                break;
                            case 4:
                                $dd = rand(0, 1);

                                $type = ($dd == 0) ? rand(16, 60) : rand(115, 144);
                                break;
                            case 5:
                                $type = rand(94, 102);
                                break;
                            case 6:
                                $type = rand(103, 105);
                                break;
                            case 7:
                                $type = 112;
                                break;
                            case 8:
                                $type = 113;
                                break;
                            case 9:
                                $type = 114;
                                break;
                            case 10:
                                $type = 107;
                                break;
                            case 11:
                                $type = 106;
                                break;
                            case 12:
                                $type = 108;
                                break;
                            case 13:
                                $type = 110;
                                break;
                            case 14:
                                $type = 109;
                                break;
                            case 15:
                                $type = 111;
                                break;

                        }

                        $this->addAuctionNew(6, $btype, $type, $item_qty, $item_time);

                    }
                }
            }

            function addAuctionNew($owner, $btype, $type, $amount, $mtime)
            {
                global $database;
                $time = time() + $mtime;
                $q = "INSERT INTO heroitems (`uid`, `btype`, `type`, `num`, `proc`) VALUES ('$owner', '$btype', '$type', '$amount', 1)";
                $database->query($q);
                $item_id = $database->get_last_id();

                if ($btype == 7 || $btype == 8 || $btype == 10 || $btype == 11 || $btype == 13 || $btype == 14) {
                    $silver = $amount;
                    $q = "INSERT INTO auction (`owner`, `itemid`, `btype`, `type`, `num`, `uid`, `bids`, `silver`, `time`, `finish`) VALUES ('$owner', '$item_id', '$btype', '$type', '$amount', 0, 0, '$silver', '$time', 0)";
                    $database->query($q);
                } elseif ($type == 9) {
                    $silver = 100;
                    $q = "INSERT INTO auction (`owner`, `itemid`, `btype`, `type`, `num`, `uid`, `bids`, `silver`, `time`, `finish`) VALUES ('$owner', '$item_id', '$btype', '$type', '$amount', 0, 0, '$silver', '$time', 0)";
                    $database->query($q);
                } else {
                    $silver = 100;
                    $q = "INSERT INTO auction (`owner`, `itemid`, `btype`, `type`, `num`, `uid`, `bids`, `silver`, `time`, `finish`) VALUES ('$owner', '$item_id', '$btype', '$type', '$amount', 0, 0, '$silver', '$time', 0)";
                    $database->query($q);
                }
                $database->query("UPDATE autoauction SET lasttime = " . time() . " WHERE id =" . $btype);
            }
            function showWWPlans()
            {
                global $database;
                $amt = 13;
                $speed = round(SPEED / 100);
                for ($i = 1; $i <= $amt; $i++) {
                    $kid += 1;
                    if ($kid > 4) {
                        $kid = 1;
                    }
                    $wid = $database->generateBase($kid);
                    $database->setFieldTaken($wid);
                    $time = time();
                    $coo = $database->getWInfo($wid);
                    $q = "INSERT  into vdata (`wref`,`owner`,`name`,`capital`,`pop`,`cp`,`celebration`,`type`,`wood`,`clay`,`iron`,`maxstore`,`crop`,`maxcrop`,`lastupdate`,`loyalty`,`exp1`,`exp2`,`exp3`,`created`,`vx`,`vy`,`vtype`) values ('$wid','3','plan Building',0,230,0,0,0,80000.00,80000.00,80000.00,80000,80000.00,80000,1314974534,100,0,0,0,1314968914," . $coo['x'] . "," . $coo['y'] . "," . $coo['fieldtype'] . ")";
                    $database->query($q);
                    $q = "INSERT  into fdata (`vref`,`f1`,`f1t`,`f2`,`f2t`,`f3`,`f3t`,`f4`,`f4t`,`f5`,`f5t`,`f6`,`f6t`,`f7`,`f7t`,`f8`,`f8t`,`f9`,`f9t`,`f10`,`f10t`,`f11`,`f11t`,`f12`,`f12t`,`f13`,`f13t`,`f14`,`f14t`,`f15`,`f15t`,`f16`,`f16t`,`f17`,`f17t`,`f18`,`f18t`,`f19`,`f19t`,`f20`,`f20t`,`f21`,`f21t`,`f22`,`f22t`,`f23`,`f23t`,`f24`,`f24t`,`f25`,`f25t`,`f26`,`f26t`,`f27`,`f27t`,`f28`,`f28t`,`f29`,`f29t`,`f30`,`f30t`,`f31`,`f31t`,`f32`,`f32t`,`f33`,`f33t`,`f34`,`f34t`,`f35`,`f35t`,`f36`,`f36t`,`f37`,`f37t`,`f38`,`f38t`,`f39`,`f39t`,`f40`,`f40t`,`f99`,`f99t`,`wwname`) values ($wid,0,1,0,4,0,1,0,3,0,2,0,2,0,3,0,4,0,4,0,3,0,3,0,4,0,4,0,1,0,4,0,2,0,1,0,2,20,17,20,11,10,27,20,10,10,22,10,25,0,0,20,15,10,19,0,0,0,0,0,0,10,23,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,16,0,0,0,0,'Miracle uncle')";
                    $database->query($q);
                    $database->addUnits($wid);
                    $database->addTech($wid);
                    $database->addABTech($wid);
                    $database->query("UPDATE units SET u1 = " . (rand(2000, 4000) * $speed) . ", u2 = " . (rand(3000, 4000) * $speed) . ", u3 = " . (rand(4600, 5600) * $speed) . ", u4 = " . (rand(50, 150) * $speed) . ", u5 = " . (rand(2400, 3800) * $speed) . ", u6 = " . (rand(3000, 4000) * $speed) . ", u7 = " . (rand(1000, 1800) * $speed) . ", u8 = " . (rand(200, 600) * $speed) . " , u9 = " . (rand(2, 10) * $speed) . ", u10 = " . (rand(2, 10) * $speed) . " WHERE vref = " . $wid . "");
                    $database->addArtefact($wid, 3, 11, 1);
                }
            }


            // Show arts
            function showArts()
            {
                for ($kid = 1; $kid <= 4; $kid++) {
                    if ($kid == 1) {
                        $this->Artefact($uid, 1, 3, 'A rare architectural masterpiece', $kid);
                    }
                    if ($kid == 2) {
                        $this->Artefact($uid, 2, 3, 'A rare speed masterpiece', $kid);
                    }
                    if ($kid == 3) {
                        $this->Artefact($uid, 3, 3, 'A rare explorer masterpiece', $kid);
                    }
                    if ($kid == 4) {
                        $this->Artefact($uid, 5, 3, 'A rare building masterpiece', $kid);
                    }
                    $this->Artefact($uid, 1, 1, 'A masterpiece of a small architect', $kid);
                    $this->Artefact($uid, 1, 2, 'Masterpiece of a great architect', $kid);

                    /**
                     * MILITARY HASTE
                     */
                    $this->Artefact($uid, 2, 1, 'a small speed masterpiece', $kid);
                    $this->Artefact($uid, 2, 2, 'a speed masterpiece', $kid);

                    /**
                     * HAWK'S EYESIGHT
                     */
                    $this->Artefact($uid, 3, 1, 'a small explorer masterpiece', $kid);
                    $this->Artefact($uid, 3, 2, 'A great explorer masterpiece', $kid);
                    $this->Artefact($uid, 5, 1, 'A small building masterpiece', $kid);
                    $this->Artefact($uid, 5, 2, 'A masterpiece building', $kid);

                    /**
                     * STORAGE MASTER PLAN
                     */
                    $this->Artefact($uid, 6, 1, 'a small storage masterpiece', $kid);
                    $this->Artefact($uid, 6, 2, 'a great storage masterpiece', $kid);

                }
            }

            function Artefact($uid, $type, $size, $village_name, $kid)
            {
                global $database;
                $uid = 3;
                $speed = round(SPEED / 100);
                $wid = $database->generateBase2($kid);
                $database->addArtefact($wid, $uid, $type, $size);
                $database->setFieldTaken($wid);
                $database->addVillage($wid, $uid, $village_name, '0', 163);
                $database->addResourceFields($wid, $database->getVillageType($wid));
                $database->addUnits($wid);
                $database->addTech($wid);
                $database->addABTech($wid);
                $database->setVillageField($wid, "name", $village_name);
                $speed = round(SPEED / 100);
                if ($size == 1) {
                    $database->query("UPDATE units SET u1 = " . (rand(1000, 2000) * $speed) . ", u2 = " . (rand(1500, 2000) * $speed) . ", u3 = " . (rand(2300, 2800) * $speed) . ", u4 = " . (rand(25, 75) * $speed) . ", u5 = " . (rand(1200, 1900) * $speed) . ", u6 = " . (rand(1500, 2000) * $speed) . ", u7 = " . (rand(500, 900) * $speed) . ", u8 = " . (rand(100, 300) * $speed) . " , u9 = " . (rand(1, 5) * $speed) . ", u10 = " . (rand(1, 5) * $speed) . " WHERE vref = " . $wid . "");
                    $database->query("UPDATE fdata SET f22t = 27, f22 = 10, f28t = 25, f28 = 10, f19t = 23, f19 = 10, f32t = 23, f32 = 10 WHERE vref = $wid");
                } elseif ($size == 2) {
                    $database->query("UPDATE units SET u1 = " . (rand(2000, 4000) * $speed) . ", u2 = " . (rand(3000, 4000) * $speed) . ", u3 = " . (rand(4600, 5600) * $speed) . ", u4 = " . (rand(50, 150) * $speed) . ", u5 = " . (rand(2400, 3800) * $speed) . ", u6 = " . (rand(3000, 4000) * $speed) . ", u7 = " . (rand(1000, 1800) * $speed) . ", u8 = " . (rand(200, 600) * $speed) . " , u9 = " . (rand(2, 10) * $speed) . ", u10 = " . (rand(2, 10) * $speed) . " WHERE vref = " . $wid . "");
                    $database->query("UPDATE fdata SET f22t = 27, f22 = 10, f28t = 25, f28 = 20, f19t = 23, f19 = 10, f32t = 23, f32 = 10 WHERE vref = $wid");
                } elseif ($size == 3) {
                    $database->query("UPDATE units SET u1 = " . (rand(4000, 8000) * $speed) . ", u2 = " . (rand(6000, 8000) * $speed) . ", u3 = " . (rand(9200, 11200) * $speed) . ", u4 = " . (rand(100, 300) * $speed) . ", u5 = " . (rand(4800, 7600) * $speed) . ", u6 = " . (rand(6000, 8000) * $speed) . ", u7 = " . (rand(2000, 3600) * $speed) . ", u8 = " . (rand(400, 1200) * $speed) . " , u9 = " . (rand(4, 20) * $speed) . ", u10 = " . (rand(4, 20) * $speed) . " WHERE vref = " . $wid . "");
                    $database->query("UPDATE fdata SET f22t = 27, f22 = 10, f28t = 25, f28 = 20, f19t = 23, f19 = 10, f32t = 23, f32 = 10 WHERE vref = $wid");
                }
            }


            function autoRenew()
            {
                global $database, $config;

                $users = $database->query("SELECT * FROM users");
                foreach ($users as $user) {
                    $qq = $database->query("SELECT * FROM autorenewals WHERE userid = " . $user['id'] . "");
                    $xx = 0;
                    foreach ($qq as $qdata) {
                        $xx++;
                    }
                    if ($xx == 0) {
                        $database->query("INSERT INTO autorenewals VALUES(NULL," . $user['id'] . ",0,0,0,0,0)");
                    }
                }

                $q = $database->query("SELECT * FROM autorenewals");
                foreach ($q as $autoData) {
                    $userData = $database->queryFetch("SELECT * FROM users WHERE id = " . $autoData['userid'] . "");
                    if ($autoData['plus']) {
                        if (time() >= $userData['plus']) {
                            if ($userData['gold'] >= $config['Plus']) {
                                $database->modifyGold($userData['id'], $config['Plus'], 0);
                                $database->query("UPDATE users SET plus = plus + " . PLUS_TIME . " WHERE id = " . $userData['id'] . "");
                            } else {
                                $database->query("UPDATE autorenewals SET plus = 0 WHERE userid =" . $userData['id'] . "");
                            }
                        }
                    }

                    if ($autoData['productionboostWood']) {
                        if (time() >= $userData['b1']) {
                            if ($userData['gold'] >= $config['addonProduction']) {
                                $database->modifyGold($userData['id'], $config['addonProduction'], 0);
                                $database->query("UPDATE users SET b1 = b1 + " . PLUS_PRODUCTION . " WHERE id = " . $userData['id'] . "");
                            } else {
                                $database->query("UPDATE autorenewals SET productionboostWood = 0 WHERE userid =" . $userData['id'] . "");
                            }
                        }
                    }

                    if ($autoData['productionboostClay']) {
                        if (time() >= $userData['b2']) {
                            if ($userData['gold'] >= $config['addonProduction']) {
                                $database->modifyGold($userData['id'], $config['addonProduction'], 0);
                                $database->query("UPDATE users SET b2 = b2 + " . PLUS_PRODUCTION . " WHERE id = " . $userData['id'] . "");
                            } else {
                                $database->query("UPDATE autorenewals SET productionboostClay = 0 WHERE userid =" . $userData['id'] . "");
                            }
                        }
                    }

                    if ($autoData['productionboostIron']) {
                        if (time() >= $userData['b3']) {
                            if ($userData['gold'] >= $config['addonProduction']) {
                                $database->modifyGold($userData['id'], $config['addonProduction'], 0);
                                $database->query("UPDATE users SET b3 = b3 + " . PLUS_PRODUCTION . " WHERE id = " . $userData['id'] . "");
                            } else {
                                $database->query("UPDATE autorenewals SET productionboostIron = 0 WHERE userid =" . $userData['id'] . "");
                            }
                        }
                    }

                    if ($autoData['productionboostCrop']) {
                        if (time() >= $userData['b4']) {
                            if ($userData['gold'] >= $config['addonProduction']) {
                                $database->modifyGold($userData['id'], $config['addonProduction'], 0);
                                $database->query("UPDATE users SET b4 = b4 + " . PLUS_PRODUCTION . " WHERE id = " . $userData['id'] . "");
                            } else {
                                $database->query("UPDATE autorenewals SET productionboostCrop = 0 WHERE userid =" . $userData['id'] . "");
                            }
                        }
                    }

                }
            }

            // Update Oasis - NoBody
            function updateOases()
            {
                global $database;
                $database->query("UPDATE config SET lastioasisUpdate = " . time() . "");

                // Update Resources and troops
                $oases = $database->query("SELECT * FROM odata WHERE conqured = 0");
                foreach ($oases as $oasis) {
                    // maxOasisRes
                    if ($oasis['wood'] < maxOasisRes) {
                        $database->query("UPDATE odata SET wood = wood + " . maxOasisRes . " WHERE wref =  " . $oasis['wref'] . "");
                    }
                    if ($oasis['clay'] < maxOasisRes) {
                        $database->query("UPDATE odata SET clay = clay + " . maxOasisRes . " WHERE wref =  " . $oasis['wref'] . "");
                    }
                    if ($oasis['iron'] < maxOasisRes) {
                        $database->query("UPDATE odata SET iron = iron + " . maxOasisRes . " WHERE wref =  " . $oasis['wref'] . "");
                    }
                    if ($oasis['crop'] < maxOasisRes) {
                        $database->query("UPDATE odata SET crop = crop + " . maxOasisRes . " WHERE wref =  " . $oasis['wref'] . "");
                    }

                    $oasisData = $database->query("SELECT * FROM wdata WHERE id = " . $oasis['wref'] . "");
                    if ($oasisData[0]['oasistype']) {
                        // 25% - 50% lumbar
                        if ($oasisData[0]['oasistype'] == 1 || $oasisData[0]['oasistype'] == 2) {
                            $database->query("UPDATE units 
                        SET
                        u5 = u5 + " . (FLOOR(5 + RAND(1, 5) * 10)) . ", 
                        u6 = u6 + " . (FLOOR(0 + RAND(1, 5) * 5)) . ",
                        u7 = u7 + " . (FLOOR(0 + RAND(1, 5) * 5)) . " WHERE vref=" . $oasis['wref'] . " AND u5 < 100");
                        }

                        // +25% lumber and +25% crop oasis
                        if ($oasisData[0]['oasistype'] == 3) {
                            $database->query("UPDATE units 
                        SET
                        u5 = u5 + " . (FLOOR(5 + RAND(1, 5) * 15)) . ", 
                        u6 = u6 + " . (FLOOR(0 + RAND(1, 5) * 5)) . ",
                        u7 = u7 + " . (FLOOR(0 + RAND(1, 5) * 5)) . ",
                        u8 = u8 + " . (FLOOR(0 + RAND(1, 5) * 5)) . ",
                        u10 = u10 + " . (FLOOR(0 + RAND(1, 5) * 3)) . " WHERE vref=" . $oasis['wref'] . " AND u5 < 100");
                        }

                        // +25% - 50% clay oasis
                        if ($oasisData[0]['oasistype'] == 4 || $oasisData[0]['oasistype'] == 5) {
                            $database->query("UPDATE units 
                        SET
                        u1 = u1 + " . (FLOOR(5 + RAND(1, 5) * 15)) . ", 
                        u2 = u2 + " . (FLOOR(0 + RAND(1, 5) * 15)) . ",
                        u5 = u5 + " . (FLOOR(0 + RAND(1, 5) * 10)) . " WHERE vref=" . $oasis['wref'] . " AND u1 < 100");
                        }


                        // +25% clay and +25% crop oasis
                        if ($oasisData[0]['oasistype'] == 6) {
                            $database->query("UPDATE units 
                        SET
                        u1 = u1 + " . (FLOOR(5 + RAND(1, 5) * 20)) . ", 
                        u2 = u2 + " . (FLOOR(0 + RAND(1, 5) * 15)) . ",
                        u5 = u5 + " . (FLOOR(0 + RAND(1, 5) * 10)) . ",
                        u10 = u10 + " . (FLOOR(0 + RAND(1, 5) * 3)) . " WHERE vref=" . $oasis['wref'] . " AND u1 < 100");
                        }

                        // +25% - 50% iron oasis
                        if ($oasisData[0]['oasistype'] == 7 || $oasisData[0]['oasistype'] == 8) {
                            $database->query("UPDATE units 
                        SET
                        u1 = u1 + " . (FLOOR(5 + RAND(1, 5) * 15)) . ", 
                        u2 = u2 + " . (FLOOR(0 + RAND(1, 5) * 15)) . ",
                        u4 = u4 + " . (FLOOR(0 + RAND(1, 5) * 10)) . " WHERE vref=" . $oasis['wref'] . " AND u1 < 100");
                        }

                        // +25% iron and +25% crop oasis
                        if ($oasisData[0]['oasistype'] == 9) {
                            $database->query("UPDATE units 
                        SET
                        u1 = u1 + " . (FLOOR(5 + RAND(1, 5) * 20)) . ", 
                        u2 = u2 + " . (FLOOR(0 + RAND(1, 5) * 15)) . ",
                        u4 = u4 + " . (FLOOR(0 + RAND(1, 5) * 10)) . ",
                        u9 = u9 + " . (FLOOR(0 + RAND(1, 5) * 3)) . " WHERE vref=" . $oasis['wref'] . " AND u1 < 100");
                        }

                        // +25% crop oasis
                        if ($oasisData[0]['oasistype'] == 10 || $oasisData[0]['oasistype'] == 11) {
                            $database->query("UPDATE units 
                        SET
                        u1 = u1 + " . (FLOOR(5 + RAND(1, 5) * 15)) . ", 
                        u3 = u3 + " . (FLOOR(0 + RAND(1, 5) * 10)) . ",
                        u7 = u7 + " . (FLOOR(0 + RAND(1, 5) * 10)) . ",
                        u8 = u8 + " . (FLOOR(0 + RAND(1, 5) * 5)) . ",
                        u9 = u9 + " . (FLOOR(0 + RAND(1, 5) * 5)) . " WHERE vref=" . $oasis['wref'] . " AND u1 < 100");
                        }

                        // +50% crop oasis
                        if ($oasisData[0]['oasistype'] == 12) {
                            $database->query("UPDATE units 
                        SET
                        u1 = u1 + " . (FLOOR(5 + RAND(1, 5) * 15)) . ", 
                        u3 = u3 + " . (FLOOR(0 + RAND(1, 5) * 10)) . ",
                        u7 = u7 + " . (FLOOR(0 + RAND(1, 5) * 10)) . ",
                        u8 = u8 + " . (FLOOR(0 + RAND(1, 5) * 5)) . ",
                        u9 = u9 + " . (FLOOR(0 + RAND(1, 5) * 5)) . ",
                        u10 = u10 + " . (FLOOR(0 + RAND(1, 5) * 3)) . " WHERE vref=" . $oasis['wref'] . " AND u1 < 100");
                        }
                    }
                }
            }

            function updateProtect()
            {
                global $database;
                $ttt = time();

                $q = $database->query("SELECT * FROM users WHERE protect < " . $ttt);
                foreach ($q as $user) {
                    //if(time() - $user['ptime'] > PROTECTION){ // Edited by NoBody
                    if (time() > $user['ptime']) { // Edited by NoBody
                        $database->query("UPDATE users SET protect  = 0 WHERE id = " . $user['id'] . "");
                    }
                }
            }

            function DelTrash($id, $type)
            {
                global $database;
                $database->query("DELETE FROM `queue` WHERE `jobID`='" . $id . "' and `type`='" . $type . "'");
            }

            function queue($s)
            {
                global $database, $session;


                foreach ($s as $f) {
                    switch ($f['type']) {
                        case 1: //атака
                            if ($this->attCom($f['jobID'])) {
                            } else {
                                $database->query("INSERT INTO critical_log (`id`,`work`,`work_id`,`job_id`) VALUES ('0','атака','" . $f['id'] . "','" . $f['jobID'] . "')");
                            }
                            break;
                        case 2: //inозinрат
                            if ($this->returnCom($f['jobID'])) {
                                $database->query("DELETE FROM `queue` WHERE `id`='" . $f['id'] . "'");
                            } else {
                                $database->query("INSERT INTO critical_log (`id`,`work`,`work_id`,`job_id`) VALUES ('0','inозinрат','" . $f['id'] . "','" . $f['jobID'] . "')");
                            }
                            break;
                        case 3: //подкреп

                            if ($this->reiCom($f['jobID'])) {
                                $database->query("DELETE FROM `queue` WHERE `id`='" . $f['id'] . "'");
                            } else {
                                $database->query("INSERT INTO critical_log (`id`,`work`,`work_id`,`job_id`) VALUES ('0','подкреп','" . $f['id'] . "','" . $f['jobID'] . "')");
                            }
                            break;
                        case 4: //   поселы

                            if ($this->settCom($f['jobID'])) {
                                $database->query("DELETE FROM `queue` WHERE `id`='" . $f['id'] . "'");
                            } else {
                                $database->query("INSERT INTO critical_log (`id`,`work`,`work_id`,`job_id`) VALUES ('0','поселы','" . $f['id'] . "','" . $f['jobID'] . "')");
                            }
                            break;

                        case 6: //   изучение
                            if ($this->researchComplete($f['jobID'])) {
                                $database->query("DELETE FROM `queue` WHERE `id`='" . $f['id'] . "'");
                            } else {
                                $database->query("INSERT INTO critical_log (`id`,`work`,`work_id`,`job_id`) VALUES ('0','изучение','" . $f['id'] . "','" . $f['jobID'] . "')");
                            }
                            break;
                        case 7: //   рынок отпраinка
                            if ($this->marketComplete($f['jobID'])) {
                                $database->query("DELETE FROM `queue` WHERE `id`='" . $f['id'] . "'");
                            } else {
                                $database->query("INSERT INTO critical_log (`id`,`work`,`work_id`,`job_id`) VALUES ('0','рынок х1','" . $f['id'] . "','" . $f['jobID'] . "')");
                            }
                            break;
                        case 8: //   рынок inозinрат и х2/х3 транспорт
                            if ($this->Market2($f['jobID'])) {
                                $database->query("DELETE FROM `queue` WHERE `id`='" . $f['id'] . "'");
                            } else {
                                $database->query("INSERT INTO critical_log (`id`,`work`,`work_id`,`job_id`) VALUES ('0','рынок х2/3','" . $f['id'] . "','" . $f['jobID'] . "')");
                            }
                            break;
                        case 9: //   снос здания
                            if ($this->demolitionComplete($f['jobID'])) {
                                $database->query("DELETE FROM `queue` WHERE `id`='" . $f['id'] . "'");
                            } else {
                                $database->query("INSERT INTO critical_log (`id`,`work`,`work_id`,`job_id`) VALUES ('0','снос здания','" . $f['id'] . "','" . $f['jobID'] . "')");
                            }
                            break;
                        case 10: //   удаление аккаунта
                            if ($this->clearDeleting($f['if1'])) {
                                $database->query("DELETE FROM `queue` WHERE `id`='" . $f['id'] . "'");
                            } else {
                                $database->query("INSERT INTO critical_log (`id`,`work`,`work_id`,`job_id`) VALUES ('0','удаление аккаунта','" . $f['id'] . "','" . $f['jobID'] . "')");
                            }
                            break;
                        case 11: //   ожиinление герыча
                            // if ($this->reviveHero($f['jobID'], $f['if1'])) {
                            $database->query("DELETE FROM `queue` WHERE `id`='" . $f['id'] . "'");
                            //  } else {
                            //     $database->query("INSERT INTO critical_log (`id`,`work`,`work_id`,`job_id`) VALUES ('0','ожиinление героя','" . $f['id'] . "','" . $f['jobID'] . "')");
                            //   }
                            break;
                        case 13:

                            if ($this->sendAdventuresComplete($f['jobID'])) {
                                $database->query("DELETE FROM `queue` WHERE `id`='" . $f['id'] . "'");
                            } else {
                                $database->query("INSERT INTO critical_log (`id`,`work`,`work_id`,`job_id`) VALUES ('0','приключения','" . $f['id'] . "','" . $f['jobID'] . "')");
                            }
                            break;
                        case 14:

                            //if ($this->autoFarm($f['jobID'])) {
                            $database->query("DELETE FROM `queue` WHERE `id`='" . $f['id'] . "'");
                            // } else {
                            //     $database->query("INSERT INTO critical_log (`id`,`work`,`work_id`,`job_id`) VALUES ('0','autofarm','" . $f['id'] . "','" . $f['jobID'] . "')");
                            // }
                            break;
                    }

                }

            }


        }

        new cron();

    }
} else {
    //
    $ourFileHandle = fopen(CONFSDATA . "/queue.txt", 'w') or die("Unable to create queue file!");
    fclose($ourFileHandle);
}