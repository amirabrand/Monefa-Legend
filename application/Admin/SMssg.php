<?php
if($session->uid > 2 && $session->uid != 6) die('Hacking attemp!');
$dqr="SELECT `global` FROM config ";
$dqr1=$database->query($dqr) ;
$txtpo = $dqr1[0]['global'];


	if(isset($_GET['del'])){
		$query="SELECT `id` FROM users ORDER BY id + 0 DESC";
		$result=$database->query($query) ;
       
		$max = count($result);
		for ($i=0; $i<=$max; $i++){
			$row=$result[$i];
			$updateattquery = $database->query("UPDATE users SET msg = '1' WHERE id = '".$row[0]."'");	
		}
		
        $txt="";
// bbcode = html code
$txt = preg_replace("/\[b\]/is",'<b>', $txt);
$txt = preg_replace("/\[\/b\]/is",'</b>', $txt);
$txt = preg_replace("/\[i\]/is",'<i>', $txt);
$txt = preg_replace("/\[\/i\]/is",'</i>', $txt);
$txt = preg_replace("/\[u\]/is",'<u>', $txt);
$txt = preg_replace("/\[\/u\]/is",'</u>', $txt);
$updateattquery = $database->query("UPDATE config SET  `global` = '".$txt."'");	
	}
if ($_POST['submit'] == 'ارسال'){
unset($_SESSION['m_message']);
$_SESSION['m_message'] = $_POST['message'];
$NextStep = true;
}

if (isset($_POST['confirm'])){
	if ($_POST['confirm'] == 'نه')$Interupt = true;
	if ($_POST['confirm'] == 'بله'){
		if(!empty($_SESSION['m_message'])){
		
		$query="SELECT `id` FROM users ORDER BY id + 0 DESC";
		$result=$database->query($query) ;
		$max = count($result);
		
			$updateattquery = $database->query("UPDATE users SET msg = '0' ");	
		
		$done = true;
        $updateattquery = $database->query("UPDATE config SET  `global` = '".$_SESSION['m_message']."'");	
        
		}else{die('<br/><br/><br/>wrong');}
	}
}
include('Templates/html.tpl');  
include('Templates/text.tpl');?>

<h4 class="round">ارسال نامه همگانی</h4>
<?php if (@!$NextStep && @!$NextStep2 && @!$done){
	        $txt=$txtpo;
// bbcode = html code
$txt = preg_replace("/\[b\]/is",'<b>', $txt);
$txt = preg_replace("/\[\/b\]/is",'</b>', $txt);
$txt = preg_replace("/\[i\]/is",'<i>', $txt);
$txt = preg_replace("/\[\/i\]/is",'</i>', $txt);
$txt = preg_replace("/\[u\]/is",'<u>', $txt);
$txt = preg_replace("/\[\/u\]/is",'</u>', $txt);
?>
<form method="POST" action="Admins.php?tid=2" name="myform" id="myform">
	<table cellspacing="1" cellpadding="1" class="tbg" style="background-color:#C0C0C0; border: 0px solid #C0C0C0; font-size: 10pt;">    
		<tbody>
		<tr>
			<td class="rbg" style="font-size: 10pt; text-align:center;">پیام همگانی</td>    
		</tr>
		<tr>
			<td style="font-size: 10pt; text-align:center;">BB کد :<br><b>[b] txt [/b]</b> - <i>[i] txt [/i]</i> - <u>[u] txt [/u]</u> <br />
			<textarea class="fm" name="message" cols="60" rows="23"><?php   echo $txt;  ?></textarea></td>    
		</tr>
		<tr>
			<td style="text-align:center;">تمامی قسمت ها اجباریست</td>    
		</tr>
		<tr>
			<td style="text-align:center;">
			<input type="submit" value="ارسال" name="submit" />    </td>
		</tr>
		</tbody>
	</table> 
</form>
<a href="Mssg.php?del">پاک کردن پیام همگانی قبلی</a>
<?php }elseif (@$NextStep){?>
<form method="POST" action="Admins.php?tid=2">
	<table cellspacing="1" cellpadding="2" class="tbg">    
		<tbody>
		<tr>
			<td class="rbg" colspan="2">تایید پیام</td>    
		</tr>
		<tr>
			<td style="text-align: left; width: 200px;">آیا مطمئنید که میخواهید پیام را ارسال کنید؟</td>
			<td style="text-align: left;">
			<input type="submit" style="width: 240px;" class="fm" name="confirm" value="بله">
			<input type="submit" style="width: 240px;" class="fm" name="confirm" value="نه"></td>    
		</tr>
		</tbody>
	</table> 
</form>
<?php
$txt= $_SESSION['m_message'];
$txt = preg_replace("/\[b\]/is",'<b>', $txt);
$txt = preg_replace("/\[\/b\]/is",'</b>', $txt);
$txt = preg_replace("/\[i\]/is",'<i>', $txt);
$txt = preg_replace("/\[\/i\]/is",'</i>', $txt);
$txt = preg_replace("/\[u\]/is",'<u>', $txt);
$txt = preg_replace("/\[\/u\]/is",'</u>', $txt);

echo 'پیام ارسالی : <br /><br />';
echo '
<textarea class="fm" name="message" cols="60" rows="13" disabled=disable>';
echo $txt;
echo '</textarea>';
}elseif ($Interupt){?>
<b><?php echo MASS_ABORT; ?></b>
<?php }elseif ($done){?>
پیام همگانی ارسال شد
<?php }else{die('مشکلی وجود دارد');}?>