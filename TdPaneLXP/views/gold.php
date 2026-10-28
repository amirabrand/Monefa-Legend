<div class="card">
	<div class="card-header">توزیع طلا بین اعضا</div>
	<div class="card-body">
	<?php 
		if(isset($_POST['addGold'])){
			if(is_numeric($_POST['gold'])){
				$database->query('UPDATE users SET gold = gold + '.(int)$_POST['gold'].'');
				echo '<div class="alert alert-success">add '.$_POST['gold'].' done.</div>';

			}
		}
	?>
	
		<form action="" method="post">
		<div class="form-group">
				<label>مقدار طلا</label>
				<input class="form-control" name="gold" type="number" required>
			</div>
		<div class="form-group">
			<button class="btn btn-primary" name="addGold">افزودن</button>
		</div>
		</form>
	</div>
</div>
