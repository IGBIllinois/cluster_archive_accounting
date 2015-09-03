<?php
	include_once 'includes/main.inc.php';
	include_once 'includes/session.inc.php';
	
	if ( !$login_user->is_admin() ){
		exit;
	}
	if ( isset($_GET['user_id']) ){
		$user_id = $_GET['user_id'];
		$user = new user($db,$ldap,$user_id);
	}
	$message = "";
	if ( isset($_POST['edit_user']) ) {
		foreach( $_POST as $var ){
			$var = trim(rtrim($var));
		}
		$admin = 0; 
		if ( isset($_POST['is_admin']) ){
			$admin = 1;
		}
		
		$user = new user($db,$ldap,$_POST['user_id']);
		if ( $user->is_admin() != $admin ){
			if ($user->set_admin($admin)) {
				$message = "<div class='alert alert-success'>User Administrator successfully set</div>";
			}
		}
	}
	else if ( isset($_POST['delete_user']) ){
		$result = $user->disable();
		if ( $result['RESULT'] ){
			header("Location: list_users.php");
		}
	}
	else if ( isset($_POST['cancel_user']) ){
		unset($_POST);
		header('Location:user.php?user_id='.$user_id);
	}
	
	require_once 'includes/header.inc.php';
?>

<form class="form-horizontal" method="post" action="<?php echo $_SERVER['PHP_SELF']."?user_id=".$user_id;?>" name="form">
	<input type="hidden" name="user_id" value="<?php echo $user_id;?>" />
	<fieldset>
		<legend>Edit User</legend>
		<div class="form-group">
			<label class="col-sm-2 control-label">Username:</label>
			<div class="col-sm-4">
				<label class="control-label"><?php echo $user->get_username();?></label>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-2 control-label" for="admin-input">Is Administrator:</label>
			<div class="col-sm-4">
				<div class="checkbox">
					<label><input type="checkbox" name="is_admin" id="admin-input" <?php if($user->is_admin()){echo 'checked="checked"';}?> /></label>
				</div>
			</div>
		</div>
		
		<div class="form-group">
			<div class="col-sm-4 col-sm-offset-2">
				<div class="btn-group">
					<input class="btn btn-primary" type="submit" name="edit_user" value="Update User" />
					<input class="btn btn-danger" type="submit" name="delete_user" value="Delete User" onClick='return (confirm_disable_user());' />
					<input class='btn btn-default' type='submit' name='cancel_user' value='Cancel'>
				</div>
			</div>
		</div>
	</fieldset>
</form>
<script type="text/javascript">
	$('#hasdir-input').on("click",directory_toggle);
</script>

<?php
	if (isset($message)) { echo $message; }
	require_once 'includes/footer.inc.php';