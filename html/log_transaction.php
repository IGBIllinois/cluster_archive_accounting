<?php
	require_once 'includes/header.inc.php';
	if(!$login_user->is_admin()){
		exit;
	}
	
	$directory_id=0;
	if(isset($_GET['directory_id']) && is_numeric($_GET['directory_id'])){
		$directory_id = $_GET['directory_id'];
	}

	if(isset($_POST['directory_id'])){
		if(!isset($_POST['amount']) || !is_numeric($_POST['amount'])){
			$message = "<p class='alert alert-danger'>Invalid amount.</p>";
		} else {
			$trans = new transaction($db);
			$trans->create($_POST['directory_id'],$_POST['amount'],null);
			log::log_message("Transaction ".$trans->get_id()." added to database by user ".$login_user->get_username());
			$message = "<p class='alert alert-success'>Transaction logged.</p>";
		}
	}

	$user_list = user_functions::get_graph_users($db);
	$user_list_html = "";
	if (count($user_list)) {
		$user_list_html = "<select class='form-control' name='directory_id'>";
		foreach ($user_list as $user) {
			if ($user['dir_id'] == $directory_id) {
				$user_list_html .= "<option value='" . $user['dir_id'] . "' selected='true'>" . $user['username'] ." - ".__ARCHIVE_DIR__.$user['directory'] . "</option>";
			} else {
				$user_list_html .= "<option value='" . $user['dir_id'] . "'>" . $user['username'] ." - ".__ARCHIVE_DIR__.$user['directory'] . "</option>";
			}
		}
		$user_list_html .= "</select>";
	}
	?>
	<form class="form-horizontal" action="log_transaction.php" method="post">
		<fieldset>
			<legend>Log Transaction</legend>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="userid">Username:</label>
				<div class="col-sm-4">
					<?php echo $user_list_html;?>
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