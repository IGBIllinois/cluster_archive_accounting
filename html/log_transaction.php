<?php
	require_once 'includes/header.inc.php';
	if(!$login_user->is_admin()){
		exit;
	}

	if(isset($_POST['userid'])){
		if(!isset($_POST['amount']) || !is_numeric($_POST['amount'])){
			$message = "<p class='bg-danger'>Invalid amount.</p>";
		}
		$trans = new transaction($db);
		$trans->create($_POST['userid'],$_POST['amount'],null);
		log::log_message("Transaction ".$trans->get_id()." added to database by user ".$login_user->get_username());
	}

	$user_list = user_functions::get_graph_users($db);
	$userselect = "<select class='form-control' name='userid'>";
	foreach($user_list as $row){
		if( (isset($_GET['user_id']) && $row['id'] == $_GET['user_id']) || (isset($_POST['user_id']) && $row['id'] == $_POST['user_id']) ){
			$userselect .= "<option value='".$row['id']."' selected>".$row['username']."</option>";
		} else {
			$userselect .= "<option value='".$row['id']."'>".$row['username']."</option>";
		}
	}
	$userselect .= "</select>";

	?>
	<form class="form-horizontal" action="log_transaction.php" method="post">
		<fieldset>
			<legend>Log Transaction</legend>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="userid">Username:</label>
				<div class="col-sm-4">
					<?php echo $userselect;?>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="amount-input">Amount:</label>
				<div class="col-sm-4">
					<div class="input-group">
						<span class="input-group-addon">$</span>
						<input class="form-control" type="text" name="amount" id="amount-input" value="<?php if(isset($_POST['amount']))echo $_POST['amount'];?>"/>
					</div>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-4 col-sm-offset-2">
					<input type="submit" class="btn btn-primary" value="Submit"/>
				</div>
			</div>
		</fieldset>
	</form>
	
<?php
	if(isset($message))echo $message;
	require_once 'includes/footer.inc.php';
	?>