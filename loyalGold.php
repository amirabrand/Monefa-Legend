<?php
include_once "application/Account.php";
include_once APP_BASE_PATH . '/library/Bale/BaleApi.php';
/*if ($session->goldclub == 0) {
    header("Location:dorf" . $session->link . ".php");
}*/

?>




<?php include("application/views/html.php"); ?>

<body class="v35 webkit <?= $database->bodyClass($_SERVER['HTTP_USER_AGENT']); ?> ar-AE cropfinder <?php if ($dorf1 == '') {
     echo 'perspectiveBuildings';
   } else {
     echo 'perspectiveResources';
   } ?> <?php echo DIRECTION; ?> buildingsV3">
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


        <?php include("application/views/sideinfo.php");





        if (1) {
          $useresm = $session->username;
          // var_dump($useresm );die();
          $q = "SELECT * FROM subs WHERE username='" . $useresm . "' LIMIT 1";
          $result = $database->query($q);

          if (count($result) == 0) {
            $random = rand(100000, 9999999);
            $q = "INSERT INTO subs( username, step,lastsendsms, telid,code) VALUES ('" . $useresm . "','-1','0','0','" . $random . "')";
            $result = $database->query($q);

            $step = -1;


          }
          $q = "SELECT * FROM subs WHERE username='" . $useresm . "' LIMIT 1";
          $result5 = $database->queryFetch($q);
          $arrrww = $result5;
          $random = $arrrww["code"];
          $q = "SELECT * FROM users WHERE username='" . $useresm . "' LIMIT 1";
          $result = $database->queryFetch($q);
          $arrrww1 = $result;
          //var_dump($_POST);
          if (isset($_POST)) {
            //doo backends 
            $sss = 0;
            if ($arrrww["step"] == '-1') {
              if (isset($_POST["phone"])) {
                $sss = 1;
              }
            }
            if ($arrrww["step"] == '0') {
              if (isset($_POST["resend"])) {
                $sss = 2;
              } elseif (isset($_POST["code"]) && $_POST["code"] > 0) {
                $sss = 3;
              } elseif (isset($_GET["wrongnum"])) {
                $sss = 6;

              }
            }
            if ($arrrww["step"] == '1') {
              if (isset($_POST["telverify"])) {
                $sss = 4;
              }
            }
            if ($arrrww["step"] == '2') {
              if (isset($_POST["dailygold"])) {
                $sss = 4;
              //  var_dump($arrrww["step"]);die();
              }
            }
            $useresm = $session->username;
           // 
            switch ($sss) {

              case 1:
                if (!isset($_POST["phone"])) { $phone = ""; } else { $phone = preg_replace('/[^0-9]/', '', $_POST["phone"]); }
                if (strlen($phone) == 10) {
                  $sql = "update users set phone='+98" . $_POST["phone"] . "'  where username='" . $useresm . "' ";
                  $result = $database->query($sql);
                  $num = "+98" . $phone . "";
                  $val = $arrrww["code"];
                  $database->sendsms($num, 0, $val);
                  $sql = "update subs set lastsendsms='" . (time() + 300) . "' ,step='0' where username='" . $useresm . "' ";
                  // var_dump($sql);
                  $result = $database->query($sql);

                } else {

                  ?>

                  <script>
                    alert("شماره درست وارد نشده لطفا با فرمت 9123456789 وارد کنید!!");
                  </script>
                  <?php
                }


                break;
              case 2:
                if ($arrrww["lastsendsms"] < time() && $arrrww["verifynum"] == "0") {
                  $num = $arrrww1["phone"];
                  $val = $arrrww["code"];
                  $database->sendsms($num, 2, $val);
                  $sql = "update subs set lastsendsms='" . (time() + 300) . "'  where username='" . $arrrww["username"] . "' ";
                  $result = $database->query($sql);
                  ?>

                  <script>
                    alert("پیامک به گوشی شماارسال شده است");
                  </script>
                  <?php

                } elseif ($arrrww["lastsendsms"] >= time() && $arrrww["verifynum"] == "0") {
                  $sec = $arrrww["lastsendsms"] - time();
                  ?>
                  <script>
                    alert("<?php echo $sec . " ثانیه دیگر تلاش کنید"; ?>");
                  </script><?php
                } else {

                }
                break;
              case 3:
                // var_dump($arrrww["verifynum"] == "0" );
                if ($arrrww["verifynum"] == "0") {
                  if ($_POST["code"] > 0) {


                    if ($arrrww["code"] == $_POST["code"]) {
                      ?>

                      <script>
                        alert("شماره شما راستی‌آزمایی شد؛ اکنون ربات بله بازی را استارت کنید و کد را برای آن بفرستید");
                      </script>
                      <?php
                      $sql = "update subs set verifynum='1' ,step='1' where username='" . $useresm . "' ";
                      $result = $database->query($sql);

                    } else {
                      ?>

                      <script>
                        alert("کد اشتباه است ");
                      </script>
                      <?php
                    }
                  } else {
                    ?>

                    <script>
                      alert("کد اشتباه است ");
                    </script>
                    <?php
                  }
                } else {
                  ?>

                  <script>
                    alert("قبلا شماره شما وریفای شده است");
                  </script>
                  <?php
                }
                break;
              case 4:
                $step = $arrrww["step"];
                include(dirname(CONFSDATA).'/botConfig.php');
                global $conn;

                //$step = 1; //$step=0yani hata sms ham rad nashode
                $sql112 = "SELECT * FROM botusersshaker where shaker='" . $arrrww["code"] . "' AND servtoken='" . SERVERPAGE . "_" . OPENING . "' ";
                $result112 = mysqli_query($conn, $sql112);

                $rrr = mysqli_fetch_array($result112);
                //var_Dump($rrr);die();
                if ($rrr["telid"] > 0) {
                  $goldb = LOYALGOLD;
                  $sql = "update subs set telid='" . $rrr["telid"] . "',verifytel='1' ,step='4', reward= '".$goldb."', lastrewarded= '" . time() . "' where username='" . $arrrww["username"] . "' ";
                  $result = $database->query($sql);
                  if ($arrrww["lastrewarded"] == 0) {
                   
                    $database->giveFREEGold($arrrww["username"], $goldb);
                    
                    //$rand1=100+$rand1;
        


                    $send = 0;
                    $client = $session->uid;
                    $owner = 4;

                    $topic = " $goldb سکه طلا هدیه عضویت";
                    $message = ' ' . $goldb . 'طلا ی وفاداری واریز شد ممنون از همراهی شما.';

                    $database->sendMessage($client, $owner, $topic, $message, $send, 0, 0, 0, 0);

                  }

                }
                // $conn->clode();
                break;
              case 5:
                //var_dump($arrrww);die();
                if ($_POST["dailygold"] == "dailygold" && $arrrww["step"] == 4 && $arrrww["verifynum"] == 1 && $arrrww["verifytel"] == 1 && $arrrww["lastrewarded"] ==0) {

                  $q = "SELECT * FROM subs WHERE username='" . $useresm . "' LIMIT 1";
                  $result = $database->queryFetch($q);

                  $apikey = '';

                  $resultsub = $result;
                  $arrrww = $resultsub;
                  if ($arrrww["telid"] > 0) {
                    $chatID = $arrrww["telid"];
                    $data = "https://api.telegram.org/bot".$apikey."/getChatMember?chat_id=@traviana&user_id=".$chatID;
   
                   
                    $data["botoken"]=$apikey;
                    $data["channel"]='@traviana';
                    $data["user"]=$chatID;
             $jsonData = json_encode($data);
                // zai-fix: third-party telegram forwarder disabled
                    $javab = array('ok' => 0); if (false) { $ch = curl_init('https://self.benwrites.ir/tramian/telRequestForwarder.php');
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json'
                ));
                
                $result = curl_exec($ch);
                $err = curl_error($ch);
               // $result = json_decode($result, JSON_PRETTY_PRINT);
                curl_close($ch);
                    $javab = json_decode($result,true);
                  } else {
                    $javab['ok'] = 0;
                  }
                  if ($javab['ok'] && ($javab['result']['status'] == 'member' || $javab['result']['status'] == 'creator' || $javab['result']['status'] == 'administrator')) {
                    $goldb = LOYALGOLD;
                     $database->giveFREEGold($arrrww["username"], $goldb);
                    
                    //$rand1=100+$rand1;
        
                    ?>

                    
                    <?php

                    $send = 0;
                    $client = $session->uid;
                    $owner = 4;

                    $topic = " $goldb سکه طلا هدیه عضویت";
                    $message = ' ' . $goldb . 'طلای وفاداری واریز شد ممنون زا همراهی شما';

                    $database->sendMessage($client, $owner, $topic, $message, $send, 0, 0, 0, 0);

                  } else {
                    ?>

                    <script>
                      alert("طلای رایگان برای کاربرانی که ربات بله بازی را استارت کرده‌اند قابل دریافت است");
                    </script>
                    <?php
                  }
                }
                }
                break;
              case 6:
                if (isset($_GET["wrongnum"]) && $arrrww["step"] < 1 && $arrrww["verifynum"] == 0 && $arrrww["verifytel"] == 0) {
                  $sql = "update subs set step='-1' where username='" . $useresm . "' ";
                  $result = $database->query($sql);
                }

                break;
              default:
                //do nothing
                break;


            }

          }
        }

        ?>







        <?php
        include("manurefer1.php");

      ?>
      

        
        <div class="referAFriendInfo">
          <div class="header">
            <div class="badgeBigDecoration">
            <a id="heroImageButton" onclick="window.location.href='hero_inventory.php';" class="heroImageButton " type="button">
                <img class="heroImage" style="top: 12px; left: 30px;  position: absolute; width: 60px;" src="hero_image.php?uid=<?php echo $session->uid; ?>&amp;size=sideinfo&amp;4203304204" alt="">
            
                    <div class="heroImageHover">
            <img class="heroImage">
        </div>
	</a>
              <div class="caption"> </div>
            </div>
          </div>



          <div class="descriptionHeader">هر سرور طلای رایگان بگیرید</div>
          <div class="description">
            <span>در هر سرور با وریفای شماره خود و اتصال به ربات بلهٔ بازی میتوانید <?= LOYALGOLD; ?> طلا دریافت
              کنید</span>
          </div>


          <div class="infoGraphic">


          </div>
          <div class="registrationOpen">
            
            <?php
            $useresm = $session->username;
            $q = "SELECT * FROM subs WHERE username='" . $useresm . "' LIMIT 1";
            $result = $database->queryFetch($q);


            $resultsub = $result;
            $resultsubarr = $resultsub;
            $step = $resultsubarr["step"];
            include(dirname(CONFSDATA) . '/botConfig.php');
            // global $conn;
            //var_dump($conn);
            //  die();
            $step = 1; //$step=0yani hata sms ham rad nashode
            $sql = "SELECT * FROM botusersshaker where username='" . $useresm . "' AND servtoken='" . SERVERPAGE . "_" . OPENING . "' ";
            $result = mysqli_query($conn, $sql);
            $telid = "0";

            if (mysqli_num_rows($result) > 0) {
              //do nothing
              $result11 = mysqli_fetch_array($result);
              $random = $result11["shaker"];
              $telid = $result11["telid"];
              $step = $resultsubarr["step"] ? $resultsubarr["step"] : 1;
            } else {
              //$random = rand(100000, 9999999);
              $sql11 = "insert into botusersshaker (`id`, `shaker`,  `username`, `servtoken`) VALUES (NULL, '" . $random . "', '" . $useresm . "', '" . SERVERPAGE . "_" . OPENING . "') ";
              $result11 = mysqli_query($conn, $sql11);
              //  var_dump($result11);die();
              // $sql = "update subs set step='0'  where username='" . $useresm . "' ";
              // $result = mysqli_query($database->connection,$sql);
            
              $step = 1;

            }

            if (!empty($telid) && $telid > 0 && $resultsubarr["step"] < 2) {
              //$result11;
              $step = 2;
              //update konam step ro rafte vaase 2
              $sql = "update subs set step='2' , telid='" . $telid . "' , verifytel ='1' where username='" . $useresm . "' ";
              $result = $database->query($sql);

              //var_dump($result11);
            }
            $marhale = 1;
            if ($resultsubarr["step"] == "-1" && !$resultsubarr["verifynum"]) {
              //0km
              $marhale = 1;

            } elseif ($resultsubarr["step"] == "0" && !$resultsubarr["verifynum"]) {
              //shomare sabt krde vali verify nkrde
              $marhale = 2;

            } elseif ($resultsubarr["verifynum"] && !$resultsubarr["verifytel"]) {
              // shomare ro verify krde hala byd bere telegram
              $marhale = 3;

            } elseif ($resultsubarr["verifynum"] && $resultsubarr["verifytel"]) {
              //hame chi verify shode too timer jayezasho begire
              $marhale = 4;

            }
            $q = "SELECT * FROM subs WHERE username='" . $useresm . "' LIMIT 1";
            $result = $database->queryFetch($q);
            $arrrww = $result;

            $q = "SELECT * FROM users WHERE username='" . $useresm . "' LIMIT 1";
            $result = $database->queryFetch($q);
            $arrrww1 = $result;

            $conn->close();
            // include("Templates/Plus/freegold.tpl");
            
            ?>
           
            <?php



            ?>

         
              

              <p>این سیستم شامل اتصال شما به ربات بله و سامانه اطلاع‌رسانی Monefa می‌باشد.</p>

              <font color=red>مزایای عضویت در سامانه های خبر رسانی Monefa</font><br>
              <li>
                دیگه هیچ وقت از شروع سرور جا نمی مونی و همیشه از قبل بهت اطلاع رسانی میشه و میتونی جزو برنده های خوش
                شانس بازی
                باشی.
              </li>
              <li>
                هر سرور طلای وفاداری دریافت کنی
              </li>

              <h3>واسه ثبت نام باید چی کار کنیم؟</h3>
              <?php if ($marhale <= 2) { ?>
                <li>مرحله به مرحله با هم میریم جلو </li>
              <?php } elseif ($marhale == 3) {
                ?>
                <li>یک مرحله دیگه تا دریافت جایزه بیشتر فاصله نداری</li>
                <?php

              } elseif ($step > 3) {

                ?>
                <li>خب دیگه الان عضو سامانه ای از اخبار هم مطلع میشی یادت نره هر روز به این صفحه سر بزنی و طلای 
                  رایگانت رو
                  دریافت کنی </li>
                <?php
              } ?>
              <br>
              <?php

              //var_dump($telid);
              switch ($marhale) {
                default:
                case 1:
                  //sms mikhad 
              
                  ?>
                  <font color=red>شماره موبایل خود را در جعبه پایین وارد کنید و بر روی دکمه «ذخیره» کلیک کنید تا کد تایید برای شما پیامک شود و از اخبار و شروع سرورهای جدید باخبر شوید.</font><br> </li>
                  <form action="loyalGold.php?freegold" method="post">


                    <center>
                      <div class="boxes boxesColor gray exchange3">
                        <div class="boxes-tl"></div>
                        <div class="boxes-tr"></div>
                        <div class="boxes-tc"></div>
                        <div class="boxes-ml"></div>
                        <div class="boxes-mr"></div>
                        <div class="boxes-mc"></div>
                        <div class="boxes-bl"></div>
                        <div class="boxes-br"></div>
                        <div class="boxes-bc"></div>
                        <div class="boxes-contents">
                          <table cellpadding="1" cellspacing="1" class="exchangeOffice transparent">
                            <tbody>
                              <tr>
                                <td>
                                  <center>
                                    <?php echo 'شماره:';
                                    $new_gold = $arrrww1["phone"];
                                    echo $new_gold;
                                    $new_gold = str_replace("+98", "", $new_gold);


                                    ?>
                                </td>
                              </tr>
                              <tr>
                                <td>
                                  <center>

                                    <input name="phone" id="phone" type="number" value="<?php echo $new_gold; ?>"
                                      style="width:120px;" title="شماره خود را وارد کنید" maxlength="10">

                                </td>
                              </tr>
                            </tbody>
                          </table>
                          <br />
                        </div>
                      </div>

                      <p>

                        <button type="submit" name="phonesubmit" value="Send" class="green ">
                          <div class="button-container addHoverClick">
                            <div class="button-background">
                              <div class="buttonStart">
                                <div class="buttonEnd">
                                  <div class="buttonMiddle"></div>
                                </div>
                              </div>
                            </div>
                            <div class="button-content">
                              ذخیره
                            </div>
                          </div>
                        </button>
                    </center>

                    <br />

                    </p>
                  </form>
                  <?php


                  break;
                case 2:
                  //shomare zade hala byd code ro bzne
              
                  ?>
                  <font color=red>لطفا کد دریافتی را وارد کنید</font><br> </li>
                  <form action="loyalGold.php?freegold" method="post">


                    <center>
                      <div class="boxes boxesColor gray exchange3">
                        <div class="boxes-tl"></div>
                        <div class="boxes-tr"></div>
                        <div class="boxes-tc"></div>
                        <div class="boxes-ml"></div>
                        <div class="boxes-mr"></div>
                        <div class="boxes-mc"></div>
                        <div class="boxes-bl"></div>
                        <div class="boxes-br"></div>
                        <div class="boxes-bc"></div>
                        <div class="boxes-contents">
                          <table cellpadding="1" cellspacing="1" class="exchangeOffice transparent">
                            <tbody>
                              <tr>
                                <td>
                                  <center>
                                    <?php echo 'کد دریافتی:';


                                    ?>
                                </td>
                              </tr>
                              <tr>
                                <td>
                                  <center>
                                    <input name="code" id="code" type="number" value="" style="width:120px;"
                                      title="کد تایید را وارد کنید." maxlength="10">
                                    <br><a href="./loyalGold.php?freegold&wrongnum=1">شماره اشتباه است؟</a>

                                </td>

                              </tr>
                            </tbody>
                          </table>
                          <br />
                        </div>
                      </div>

                      <p>

                        <button type="submit" name="phonesubmit" value="Send" class="green ">
                          <div class="button-container addHoverClick">
                            <div class="button-background">
                              <div class="buttonStart">
                                <div class="buttonEnd">
                                  <div class="buttonMiddle"></div>
                                </div>
                              </div>
                            </div>
                            <div class="button-content">
                              ذخیره
                            </div>
                          </div>
                        </button>
                        <br><button type="submit" name="resend" value="resend" class="green ">
                          <div class="button-container addHoverClick">
                            <div class="button-background">
                              <div class="buttonStart">
                                <div class="buttonEnd">
                                  <div class="buttonMiddle"></div>
                                </div>
                              </div>
                            </div>
                            <div class="button-content">
                              ارسال مجدد کد
                            </div>
                          </div>
                        </button>
                    </center>

                    <br />
                    <div class="error RTL">
                      <?php echo $form->getError("phone"); ?>
                      <br />
                      <div class="error RTL">
                        <?php echo $form->getError("phone"); ?>
                      </div>
                    </div>
                    </p>
                  </form>
                  <?php
                  // $random; //codi k byd be bot telegram befreste
              
                  break;
                case 3:
                  //tel verify mikhad bokone 
                  ?>
                  <?php $__baleLink = BaleApi::botLink(); ?>
                  <?php if ($__baleLink): ?>
                  <font color=red>برای دریافت طلای وفاداری، روی دکمهٔ زیر بزنید تا ربات بله باز شود و کد شما به‌صورت خودکار ثبت شود:</font><br> </li>
                  <br>
                  <a class="green" style="display:inline-block;padding:10px 26px;border-radius:6px;background:#28a909;color:#fff;text-decoration:none;font-weight:bold" href="<?php echo htmlspecialchars($__baleLink . '?start=' . $arrrww['code'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">🟢 اتصال به ربات بله</a>
                  <br><br>
                  <font color=red>یا اگر ربات باز نشد، کد زیر را به‌صورت دستی برای ربات بله بفرستید:</font><br> </li>
                  <?php else: ?>
                  <font color=red>ربات بله هنوز توسط مدیر تنظیم نشده است؛ لطفاً بعداً تلاش کنید.</font><br> </li>
                  <?php endif; ?>
                  <br>
                  <form action="loyalGold.php?freegold" method="post">


                    <center>
                      <div class="boxes boxesColor gray exchange3">
                        <div class="boxes-tl"></div>
                        <div class="boxes-tr"></div>
                        <div class="boxes-tc"></div>
                        <div class="boxes-ml"></div>
                        <div class="boxes-mr"></div>
                        <div class="boxes-mc"></div>
                        <div class="boxes-bl"></div>
                        <div class="boxes-br"></div>
                        <div class="boxes-bc"></div>
                        <div class="boxes-contents">
                          <table cellpadding="1" cellspacing="1" class="exchangeOffice transparent">
                            <tbody>
                              <tr>
                                <td>
                                  <center>
                                    <?php echo 'کد :';


                                    ?>
                                </td>
                              </tr>
                              <tr>
                                <td>
                                  <center>
                                    <input name="code" id="code" type="text" value="/code_<?php echo $arrrww["code"]; ?>"
                                      style="width:120px;" title="کد تایید را وارد کنید." maxlength="10">
                                    <br>

                                </td>

                              </tr>
                            </tbody>
                          </table>
                          <br />
                        </div>
                      </div>

                      <p>


                        <br><button type="submit" name="telverify" value="telverify" class="green ">
                          <div class="button-container addHoverClick">
                            <div class="button-background">
                              <div class="buttonStart">
                                <div class="buttonEnd">
                                  <div class="buttonMiddle"></div>
                                </div>
                              </div>
                            </div>
                            <div class="button-content">
                              بررسی عضویت در بات
                            </div>
                          </div>
                        </button>
                    </center>

                    <br />

                    </p>
                  </form>
                  <?php
                  // $random; //codi k byd be bot telegram befreste
              
                  break;
                case 4:
                  //hame kara shode tala mikhad
                  ?>
                  <font color=green>خب دوست من تو توی همه ی سامانه های اطلاع رسانی Monefa عضو شدی دیگه از شروع سرور ها جا
                    نمونی راستی این عضوییت توی هر سرور بهت  <?php echo LOYALGOLD; ?> طلا میده</font><br> </li>
                  <br>
                  <form action="loyalGold.php?freegold" method="post">


                    <center>
                      <div class="boxes boxesColor gray exchange3">
                        <div class="boxes-tl"></div>
                        <div class="boxes-tr"></div>
                        <div class="boxes-tc"></div>
                        <div class="boxes-ml"></div>
                        <div class="boxes-mr"></div>
                        <div class="boxes-mc"></div>
                        <div class="boxes-bl"></div>
                        <div class="boxes-br"></div>
                        <div class="boxes-bc"></div>
                        <div class="boxes-contents">
                          <table cellpadding="1" cellspacing="1" class="exchangeOffice transparent">
                            <tbody>
                              <?php if ($arrrww["lastrewarded"] ==0) {
                                ?>

                                <button type="submit" name="dailygold" value="dailygold" class="green ">
                                  <div class="button-container addHoverClick">
                                    <div class="button-background">
                                      <div class="buttonStart">
                                        <div class="buttonEnd">
                                          <div class="buttonMiddle"></div>
                                        </div>
                                      </div>
                                    </div>
                                    <div class="button-content">
                                      دریافت طلای وفاداری
                                    </div>
                                  </div>
                                </button>

                                <?php
                              } else {
                                $needtime = $arrrww["lastrewarded"] - time();
                                if($needtime<1 && $needtime>=0 ){?>
                                  برای دریافت طلا یک بار صفحه را بروز کنید طلای شما واریز شده است.
                                  <?php
                                  }else{
                                  
                                
                                ?>
                                طلای وفاداری  در تاریخ <?php echo jdate('J F V H:i:s', $arrrww["lastrewarded"]); ?> دریافت شده است

                                <?php
                                }
                              }
                              ?>
                            </tbody>
                          </table>
                          <br />
                        </div>
                      </div>

                      <p>



                    </center>

                    <br />

                    </p>
                  </form>
                  <?php



                  break;
              }
              

              ?>

          
            
          </div>
        </div>


      </div>
    </div>


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