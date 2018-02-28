<?php
	require_once 'includes/header.inc.php';
	if(!$login_user->is_admin()){
		exit;
	}
	
	$directory_id=0;
	if(isset($_REQUEST['directory_id']) && is_numeric($_REQUEST['directory_id'])){
		$directory_id = $_REQUEST['directory_id'];
	}

	if(isset($_POST['directory_id'])){
		if(!isset($_POST['amount']) || !is_numeric($_POST['amount'])){
			$message = html::error_message("Invalid amount.");
		} else {
			$trans = new token_transaction($db);
			$trans->create($_POST['directory_id'],$_POST['amount'],null,null,intval($settings->get_setting('data_cost')));
			
			log::log_message($_POST['amount']."TB prepaid for directory ".$_POST['directory_id']);
			$message = html::success_message("Transaction logged.");
		}
	}

	$user_list = user_functions::get_billable_directories($db,$login_user);
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
	<form class="form-horizontal" action="add_tokens.php" method="post" name="form">
		<fieldset>
			<legend>Pre-Pay</legend>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="userid">Directory:</label>
				<div class="col-sm-4">
					<?php echo $user_list_html;?>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="amount-input">Amount:</label>
				<div class="col-sm-4">
					<div class="input-group">
						<input class="form-control" type="text" name="amount" id="amount-input" value="<?php if(isset($_POST['amount']))echo $_POST['amount'];?>"/>
						<span class="input-group-addon">TB</span>
					</div>
				</div>
			</div>
<!--
			<div class="form-group">
				<label class="col-sm-2 control-label" for="date-input">Date:</label>
				<div class="col-sm-4">
					<select class="form-control" name="dateselect" id="dateselect">
						<option value="0" <?php if(isset($_POST['dateselect'])&&$_POST['dateselect']==0)echo 'selected';?>>Today</option>
						<option value="1" <?php if(isset($_POST['dateselect'])&&$_POST['dateselect']==1)echo 'selected';?>>On date:</option>
					</select>
				</div>
			</div>
			<div class="form-group" id="dateinput" <?php if( !(isset($_POST['dateselect'])&&$_POST['dateselect']==1) )echo 'style="display:none"';?>>
				<div class="col-sm-4 col-sm-offset-2">
					<input class="form-control" name="date" placeholder="YYYY-MM-DD" value="<?php if(isset($_POST['date']))echo $_POST['date'];?>"/>
				</div>
			</div>
-->
			<div class="row">
				<div class="col-sm-4 col-sm-offset-2">
					<p>
						<span id="tokeninfo"> </span>
					</p>
				</div>
			</div>
			
			<div class="form-group">
				<div class="col-sm-4 col-sm-offset-2">
					<input type="submit" id="tokensubmit" class="btn btn-primary" value="Submit"/>
				</div>
			</div>
		</fieldset>
	</form>
	<script type="text/javascript">
		$('#dateselect').on('change',date_toggle);
		$('#amount-input').on('input',function(e){
			var cost = <?php echo $settings->get_setting('data_cost'); ?>;
			var terabytes = $(this).val();
			if ($.isNumeric(terabytes)){
				$('#tokeninfo').html("Cost: $"+parseInt(terabytes) * cost);
				$('#tokensubmit').prop('disabled',false);
			} else {
				$('#tokeninfo').html("Please enter a numeric value");
				$('#tokensubmit').prop('disabled',true);
			}
		})
	</script>
<?php
	if(isset($message))echo $message;
	require_once 'includes/footer.inc.php';
	?>