<?php
	require_once 'includes/header.inc.php';
	if(!$login_user->is_admin()){
		exit;
	}
	
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$transaction_id = $_REQUEST['id'];
		$transaction = new transaction($db);
		$transaction->get_transaction($transaction_id);
		$directory = new archive_directory($db);
		$directory->load_by_id($transaction->get_directory_id());
		$user = new user($db,$ldap,$directory->get_user_id());
	} else {
		header('location:user.php');
	}
	
	if(isset($_POST['edit_trans'])){
		$date = date_parse($_POST['date']);
		$mysql_date = $date['year']."-".$date['month']."-".$date['day']." ".$date['hour'].":".$date['minute'].":".$date['second'];
		$sql = "update transactions set amount=:amount, transaction_time=:time where id=:id";
		$args = array(':amount'=>$_POST['amount'],':time'=>$mysql_date,':id'=>$_POST['id']);
		$db->non_select_query($sql,$args);
		$message = "<div class='alert alert-success'>Transaction updated</div>";
	} else if(isset($_POST['delete_trans'])){
		$sql = "delete from transactions where id=:id";
		$args = array(':id'=>$_POST['id']);
		$db->non_select_query($sql,$args);
		$date_arr = date_parse($transaction->get_transaction_time());
		header('location:list_transactions.php?directory_id='.$directory->get_id().'&year='.$date_arr['year']);
	}

	?>
	<form class="form-horizontal" action="edit_transaction.php" method="post" name="form">
		<fieldset>
			<legend>Edit Transaction</legend>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="userid">Directory:</label>
				<div class="col-sm-4">
					<?php echo $user->get_username()." - ".__ARCHIVE_DIR__.$directory->get_directory();?>
					<input type="hidden" name="id" value="<?php echo $transaction->get_id();?>"/>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="amount-input">Amount:</label>
				<div class="col-sm-4">
					<div class="input-group">
						<span class="input-group-addon">$</span>
						<input class="form-control" type="text" name="amount" id="amount-input" value="<?php echo $transaction->get_amount();?>"/>
					</div>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="date-input">Date:</label>
				<div class="col-sm-4">
					<select class="form-control" name="dateselect" id="dateselect">
						<option value="0">Today</option>
						<option value="1" selected>On date:</option>
					</select>
				</div>
			</div>
			<div class="form-group" id="dateinput" <?php if( isset($_POST['dateselect'])&&$_POST['dateselect']==0 )echo 'style="display:none"';?>>
				<div class="col-sm-4 col-sm-offset-2">
					<input class="form-control" name="date" placeholder="YYYY-MM-DD" value="<?php echo html::get_pretty_date_mysql($transaction->get_transaction_time());?>"/>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-4 col-sm-offset-2">
					<div class="btn-group">
						<input type="submit" class="btn btn-primary" name="edit_trans" value="Update"/>
						<input type="submit" class="btn btn-danger" name="delete_trans" value="Delete" onClick='return (confirm_delete_transaction());'/>
						<a href="list_transactions.php?directory_id=<?php echo $directory->get_id();?>&year=<?php $date_arr = date_parse($transaction->get_transaction_time()); echo $date_arr['year'];?>" class="btn btn-default">Cancel</a>
					</div>
				</div>
			</div>
		</fieldset>
	</form>
	<script type="text/javascript">
		$('#dateselect').on('change',date_toggle);
	</script>
<?php
	if(isset($message))echo $message;
	require_once 'includes/footer.inc.php';
	?>