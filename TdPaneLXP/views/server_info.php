<?php

#################################################################################

##              -= YOU MAY NOT.phpOVE OR CHANGE THIS NOTICE =-                 ##

## --------------------------------------------------------------------------- ##

##  Filename       server_info.tpl                                             ##

##  Developed by:  Dzoki                                                       ##

##  License:       TravianX Project                                            ##

##  Copyright:     TravianX (c) 2010-2011. All rights reserved.                ##

##  Enhanced:      aggenkeech                                                  ##

#################################################################################



$tribe1 = $database->query("SELECT COUNT(*) as sum FROM users WHERE tribe = 1");

$tribe2 = $database->query("SELECT COUNT(*) as sum  FROM users WHERE tribe = 2");

$tribe3 = $database->query("SELECT COUNT(*) as sum FROM users WHERE tribe = 3");

$tribes = Array($tribe1[0]['sum'],$tribe2[0]['sum'],$tribe3[0]['sum']);

$users = $tribe1[0]['sum']+$tribe2[0]['sum']+$tribe3[0]['sum'];

?>



<br /><br />

<table class="table">

    <thead>

    <tr>

        <th colspan="2">اطلاعات بازیکن</th>

    </tr>

    </thead>

    <tbody>

    <tr>

        <td>بازیکنان ثبت‌نام‌شده</td>

        <td><?php echo $users; ?></td>

    </tr>

    <tr>

        <td>بازیکنان فعال</td>

        <td><?php  echo $database->ActiveAndOnline((3600*24)); ?></td>

    </tr>

    <tr>

        <td>بازیکنان آنلاین</td>

        <td><?php echo $database->ActiveAndOnline((60*10));?>

        </td>

    </tr>

    <tr>

        <td>بازیکنان مسدود</td>

        <td><?php

            $result = $database->query("SELECT  COUNT(*) as sum  FROM users WHERE access = 0");

            $num_rows = $result[0]['sum'];

            echo $num_rows;?>

        </td>

    </tr>

    <tr>

        <td>دهکده‌های تأسیس‌شده</td>

        <td><?php

            $result = $database->query("SELECT COUNT(*) as sum FROM vdata");

            $num_rows = $result[0]['sum'];

            echo $num_rows; ?>

        </td>

    </tr>

    </tbody>

</table>



<br />



<table class="table">

    <thead>

    <tr><th colspan="3">اطلاعات بازیکن</th></tr>

    <td class="b">قوم</td>

    <td class="b">ثبت‌نام‌شده</td>

    <td class="b">درصد</td>

    </thead>

    <tbody>

    <tr>

        <td>رومی‌ها</td>

        <td><?php echo $tribes[0]; ?></td>

        <td><?php $percents = 100*($tribes[0] / $users); echo $percents = intval ($percents); echo "%"; ?></td>

    </tr>

    <tr>

        <td>توتن‌ها</td>

        <td><?php echo $tribes[1]; ?></td>

        <td><?php $percents = 100*($tribes[1] / $users); echo $percents = intval ($percents); echo "%";?></td>

    </tr>

    <tr>

        <td>گول‌ها</td>

        <td><?php echo $tribes[2]; ?></td>

        <td><?php $percents = 100*($tribes[2] / $users); echo $percents = intval ($percents); echo "%"; ?></td>

    </tr>

    </tbody>

</table>



<br />



<table class="table">

    <thead>

    <tr>

        <th colspan="3">اطلاعات سرور</th>

    </tr>

    <td class="b"></td>

    <td class="b">مجموع</td>

    <td class="b">میانگین</td>

    </thead>

    <tbody>

    <tr>

        <td><img src="../<?php echo GP_LOCATE; ?>img/a/gold.gif" alt="Gold" title="Gold"> Gold</td>

        <td><?php $gold = $database->query("SELECT SUM(gold) AS sum FROM users");  echo $gold[0]['sum']; ?></td>

        <td><?php  echo round($gold[0]['sum'] / $users);?></td>

    </tr>

    </tbody>

</table>

</div>

<table class="table">

    <?php

    for($i=1;$i<=30;$i++){$units[$i]=0;}$units['hero']=0;

    $getinfa= $database->query("SELECT un.*, IF(u.tribe IS NULL, 4,u.tribe) as tribe FROM ((

    units as un

    LEFT JOIN  vdata as v ON v.wref=un.vref)

    LEFT JOIN users as u ON u.id=v.owner) WHERE u.tribe<4");



    foreach($getinfa as $inf){



     for($i=1;$i<=10;$i++){

      $units[(($inf['tribe']-1)*10+$i)]+=$inf['u'.$i];

     }

    }



    ?>

    <thead>

    <tr>

        <th colspan="10">نیروهای موجود در سرور</th>

    </tr>

    <?php

    for($i=1; $i<11; $i++)

    {

        echo '<td class="on"><img src="../img/admin/en/u/'.$i.'.gif"></td>';

    }

    echo '</thead><tbody>';

    for($i=1; $i<11; $i++)

    {

    echo '<td class="on">'.$units[$i].'</td>';

    }



    echo "</tr>";

    for($i=11; $i<21; $i++)

    {

    echo '<td class="on"><img src="../img/admin/en/u/'.$i.'.gif"></td>';

    }

    echo '</thead><tbody>';

    for($i=11; $i<21; $i++)

    {

    echo '<td class="on">'.$units[$i].'</td>';

    }



    echo "</tr>";

    for($i=21; $i<31; $i++)

    {

    echo '<td class="on"><img src="../img/admin/en/u/'.$i.'.gif"></td>';

    }

    echo '</thead><tbody>';

    for($i=21; $i<31; $i++)

    {

    echo '<td class="on">'.$units[$i].'</td>';

    }



    echo "</tr>";?>



</table>

