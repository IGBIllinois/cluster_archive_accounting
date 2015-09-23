<?php
	require_once 'includes/header.inc.php';
	
	if (!$login_user->is_admin()){
		exit;
	}
	
	$message="";
	if (isset($_POST['add_user'])) {
		foreach($_POST as $var){
			$var = trim(rtrim($var));
		}
		$admin = 0;
		if (isset($_POST['is_admin'])){
			$admin = 1;
		}

		$hasdir = 0;
		if (isset($_POST['has_dir'])){
			$hasdir = 1;
		}
		$cfop = "";
		if($hasdir==1){
			$dnb = 0;
			if(isset($_POST['do_not_bill'])){
				$dnb = 1;
			}
			if($dnb==0){
				$cfop = $_POST['cfop_1']."-".$_POST['cfop_2']."-".$_POST['cfop_3']."-".$_POST['cfop_4'];
				if($cfop=="---")$cfop="";
			}
			$archive_dir = "";
			if(isset($_POST['archive_dir']) && $_POST['archive_dir']!=""){
				$archive_dir = $_POST['archive_dir'];
			} else {
				$message = html::error_message("Please enter a directory");
			}
		}
		
		
		if($message == ""){
			$user = new user($db,$ldap);
			$result = $user->create($_POST['new_username'],$admin);
		
			if($result['RESULT'] == true){
				// Add directory if needed
				if($hasdir==1){
					$directory = new archive_directory($db);
					$directory->create($result['user_id'],$archive_dir,$cfop,$_POST['activity_code'],$dnb);
				}
				header("Location: user.php?user_id=".$result['user_id']);
			} else if ($result['RESULT'] == false) {
				$message = $result['MESSAGE'];
			}
		}
	} else if (isset($_POST['cancel_user'])) {
		unset($_POST);
	}
?>
<form class="form-horizontal" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>" name="form">
	<fieldset>
		<legend>Add User</legend>
		<div class="form-group">
			<label class="col-sm-2 control-label" for="username-input">Username:</label>
			<div class="col-sm-4">
				<input class="form-control" type="text" name="new_username" id="username_input" value="<?php if (isset($_POST['new_username'])){echo $_POST['new_username'];}?>" />
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-2 control-label" for="admin-input">Is Administrator:</label>
			<div class="col-sm-4">
				<div class="checkbox">
					<label><input type="checkbox" name="is_admin" id="admin_input" <?php if (isset($_POST['is_admin'])){echo 'checked="checked"';}?> /></label>
				</div>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-2 control-label" for="hasdir-input">Has directory:</label>
			<div class="col-sm-4">
				<div class="checkbox">
					<label><input type="checkbox" name="has_dir" id="hasdir_input" <?php if (!isset($_POST['add_user']) || isset($_POST['has_dir'])){echo 'checked="checked"';}?> /></label>
				</div>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-2 control-label" for="archive-dir-input">Archive Directory:</label>
			<div class="col-sm-4">
				<div class="input-group">
					<span class="input-group-addon"><?php echo __ARCHIVE_DIR__;?></span>
					<input class="form-control" type="text" name="archive_dir" id="archive-dir-input" value="<?php if (isset($_POST['archive_dir'])){echo $_POST['archive_dir'];}?>" <?php if(isset($_POST['add_user'])&&!isset($_POST['has_dir']))echo 'disabled';?>/>
				</div>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-2 control-label" for="hasdir-input">Do not bill:</label>
			<div class="col-sm-4">
				<div class="checkbox">
					<label><input type="checkbox" name="do_not_bill" id="dnb_input" <?php if(isset($_POST['do_not_bill'])){echo 'checked="checked"';}?> /></label>
				</div>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-2 control-label">CFOP:</label>
			<div class="col-sm-4">
				<div class="row">
					<div class="col-sm-3 cfop"><input class="form-control" type="text" name="cfop_1" maxlength="1" oninput="cfop_advance(1)" value="<?php if (isset($_POST['cfop_1'])){echo $_POST['cfop_1'];}?>" <?php if( (isset($_POST['add_user'])&&!isset($_POST['has_dir'])) || isset($_POST['do_not_bill']) )echo 'disabled';?>/></div>
					<div class="col-sm-3 cfop"><input class="form-control" type="text" name="cfop_2" maxlength="6" oninput="cfop_advance(2)" value="<?php if (isset($_POST['cfop_2'])){echo $_POST['cfop_2'];}?>" <?php if( (isset($_POST['add_user'])&&!isset($_POST['has_dir'])) || isset($_POST['do_not_bill']) )echo 'disabled';?>/></div>
					<div class="col-sm-3 cfop"><input class="form-control" type="text" name="cfop_3" maxlength="6" oninput="cfop_advance(3)" value="<?php if (isset($_POST['cfop_3'])){echo $_POST['cfop_1'];}?>" <?php if( (isset($_POST['add_user'])&&!isset($_POST['has_dir'])) || isset($_POST['do_not_bill']) )echo 'disabled';?>/></div>
					<div class="col-sm-3 cfop"><input class="form-control" type="text" name="cfop_4" maxlength="6" value="<?php if (isset($_POST['cfop_1'])){echo $_POST['cfop_4'];}?>" <?php if( (isset($_POST['add_user'])&&!isset($_POST['has_dir'])) || isset($_POST['do_not_bill']) )echo 'disabled';?>/></div>
				</div>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-2 control-label">Activity Code:</label>
			<div class="col-sm-4">
				<input class="form-control" type="text" name="activity_code" value="<?php if(isset($_POST['activity_code'])){echo $_POST['activity_code'];}?>" />
			</div>
		</div>
		<div class="form-group">
			<div class="col-sm-4 col-sm-offset-2">
				<div class="btn-group">
					<input class="btn btn-primary" type="submit" name="add_user" value="Add user" /> <input class="btn btn-default" type="submit" name="cancel_user" value="Cancel" />
				</div>
			</div>
		</div>
	</fieldset>
</form>
<script type="text/javascript">
	$('#hasdir_input').on("click",directory_toggle);
	$('#dnb_input').on('click',bill_toggle);
</script>
<?php
	if(isset($message))echo $message;
	require_once 'includes/footer.inc.php';
?>