<div class="card">
<div class="card-header">عملیات شارژ</div>
<div class="card-body">
<table cellpadding="1" cellspacing="1" class="table">
	<thead>
		<tr>
			<th colspan="10">ثبت ارسال</th>
		</tr>
		<tr>
			<td class="on">#</td>
			<td class="on">بازیکن</td>
			<td class="on">روش پرداخت</td>
			<td class="on">تعداد سکه</td>
			<td class="on">تاریخچهٔ طلا</td>
			<td class="on">مقدار طلا</td>
			<td class="on">Cost</td>
			<td class="on"></td>
		</tr>
	</thead>
	<tbody>

	<?php 
        $pyList = $database->query("SELECT * FROM payments ORDER BY id DESC");
        foreach($pyList as $pyData){
            $pData = $database->getUserInfo($pyData['idUser']);
    ?>
				<tr>
					<td><?php echo $pyData['id']; ?></td>
					<td><a href="index.php?p=player&uid=<?php echo $pyData['idUser']; ?>"><?php echo $pData['username']; ?></a></td>
					<td><?php echo $pyData['pMethod']; ?></td>
					<td><?php echo $pyData['idTrans']; ?></td>
					<td><?php echo date("Y/m/d h:m", $pyData['dTrans']); ?></td>
					<td><?php echo number_format($pyData['gAmount']); ?></td>
					<td><?php echo $pyData['cost']; ?>USD</td>
					<td><center><?php echo $pyData['idTrans'] ? 'Completed' : 'Failed'; ?></center></td>
				</tr>
<?php } ?>
	</tbody>

</table>
</div>
</div>
