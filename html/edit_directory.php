<?php
	require_once 'includes/main.inc.php';
	require_once 'includes/session.inc.php';
	
	if(!$login_user->is_admin()){
		exit;
	}
	if ( isset($_GET['directory_id'])){
		$directory_id = $_GET['directory_id'];
		$directory = new archive_directory($db);
		$directory->load_by_id($directory_id);
	} else {
		header('location:user.php');
	}
	
	$message = "";
	if(isset($_POST['edit_dir'])){
		foreach($_POST as $var){
			$var = trim(rtrim($var));
		}
		$dnb = 0;
		if(isset($_POST['do_not_bill'])){
			$dnb = 1;
		}
		if($directory->get_do_not_bill()!=$dnb){
			$directory->set_do_not_bill($dnb);
			$message .= html::success_message("Do not bill successfully set");
		}
		$cfop="";
		if($dnb==0){
			$cfop = $_POST['cfop_1']."-".$_POST['cfop_2']."-".$_POST['cfop_3']."-".$_POST['cfop_4'];
			if($cfop=="---")$cfop="";
			if($directory->get_cfop() != $cfop){
				if($directory->set_cfop($cfop)){
					$message .= html::success_message("CFOP successfully set");
				}
			}
		}
		if($directory->get_directory() != $_POST['archive_dir']){
			if($directory->set_directory($_POST['archive_dir'])){
				$message .= html::success_message("Directory successfully set");
			}
		}
	} else if(isset($_POST['delete_dir'])){
		$result = $directory->disable();
		if($result){
			header('location: user.php?user_id='.$directory->get_user_id());
		}
	} else if(isset($_POST['cancel_dir'])){
		unset($_POST);
		header('location:user.php?user_id='.$directory->get_user_id());
	}
	
	require_once 'includes/header.inc.php';
	?>
<form class="form-horizontal" method="post" action="<?php echo $_SERVER['PHP_SELF']."?directory_id=".$directory_id;?>" name="form">
	<input type="hidden" name="user_id" value="<?php echo $directory_id;?>" />
	<fieldset>
		<legend>Edit Directory</legend>
		<div class="form-group">
			<label class="col-sm-2 control-label">Directory:</label>
			<div class="col-sm-4">
				<div class="input-group">
					<span class="input-group-addon"><?php echo __ARCHIVE_DIR__;?></span>
					<input class="form-control" type="text" name="archive_dir" id="archive-dir-input" value="<?php echo $directory->get_directory();?>"/>
				</div>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-2 control-label" for="hasdir-input">Do not bill:</label>
			<div class="col-sm-4">
				<div class="checkbox">
					<label><input type="checkbox" name="do_not_bill" id="dnb_input" <?php if($directory->get_do_not_bill()!=0){echo 'checked="checked"';}?> /></label>
				</div>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-2 control-label">CFOP:</label>
			<div class="col-sm-4">
				<div class="row">
					<div class="col-sm-3 cfop"><input class="form-control" type="text" name="cfop_1" maxlength="1" oninput="cfop_advance(1)" value="<?php echo $directory->get_cfop_college();?>" <?php if($directory->get_do_not_bill()!=0){echo 'disabled';}?>/></div>
					<div class="col-sm-3 cfop"><input class="form-control" type="text" name="cfop_2" maxlength="6" oninput="cfop_advance(2)" value="<?php echo $directory->get_cfop_fund();?>" <?php if($directory->get_do_not_bill()!=0){echo 'disabled';}?>/></div>
					<div class="col-sm-3 cfop"><input class="form-control" type="text" name="cfop_3" maxlength="6" oninput="cfop_advance(3)" value="<?php echo $directory->get_cfop_organization();?>" <?php if($directory->get_do_not_bill()!=0){echo 'disabled';}?>/></div>
					<div class="col-sm-3 cfop"><input class="form-control" type="text" name="cfop_4" maxlength="6" value="<?php echo $directory->get_cfop_program();?>" <?php if($directory->get_do_not_bill()!=0){echo 'disabled';}?>/></div>
				</div>
			</div>
		</div>
		<div class="form-group">
			<div class="col-sm-4 col-sm-offset-2">
				<div class="btn-group">
					<input class="btn btn-primary" type="submit" name="edit_dir" value="Update Directory" />
					<input class="btn btn-danger" type="submit" name="delete_dir" value="Delete Directory" onClick='return (confirm_disable_directory());' />
					<input class='btn btn-default' type='submit' name='cancel_dir' value='Cancel'>
				</div>
			</div>
		</div>
	</fieldset>
</form>
<script type="text/javascript">
	$('#dnb_input').on('click',bill_toggle);
</script>
<?php
	if(isset($message)){echo $message;}
	require_once 'includes/footer.inc.php';