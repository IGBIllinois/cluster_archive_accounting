<?php
	require_once 'includes/main.inc.php';
	require_once 'includes/session.inc.php';
	
	if(!$login_user->is_admin()){
		exit;
	}
	
	$message="";
	if(isset($_POST['add_dir'])){
		var_dump($_POST);
		foreach($_POST as $var){
			$var = trim(rtrim($var));
		}
		$dnb = 0;
		if(isset($_POST['do_not_bill'])){
			$dnb = 1;
		}
		$cfop="";
		if($dnb==0){
			$cfop = $_POST['cfop_1']."-".$_POST['cfop_2']."-".$_POST['cfop_3']."-".$_POST['cfop_4'];
			if($cfop=="---")$cfop="";
		}
		
		$directory = new archive_directory($db);
		$directory->create($_POST['user_id'],$_POST['archive_dir'],$cfop,$dnb);
		header('location:user.php?user_id='.$_POST['user_id']);
	} else if (isset($_POST['cancel_user'])){
		unset($_POST);
	}
	
	$user_list = user_functions::get_all_users($db);
	$userselect = "<select class='form-control' name='user_id'>";
	foreach($user_list as $row){
		if( (isset($_GET['user_id']) && $row['id'] == $_GET['user_id']) || (isset($_POST['user_id']) && $row['id'] == $_POST['user_id']) ){
			$userselect .= "<option value='".$row['id']."' selected>".$row['username']."</option>";
		} else {
			$userselect .= "<option value='".$row['id']."'>".$row['username']."</option>";
		}
	}
	$userselect .= "</select>";

	require_once 'includes/header.inc.php';
?>
<form class="form-horizontal" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>" name="form">
	<fieldset>
		<legend>Add Directory</legend>
		<div class="form-group">
			<label class="col-sm-2 control-label">Username:</label>
			<div class="col-sm-4">
				<?php echo $userselect;?>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-2 control-label" for="archive-dir-input">Archive Directory:</label>
			<div class="col-sm-4">
				<div class="input-group">
					<span class="input-group-addon"><?php echo __ARCHIVE_DIR__;?></span>
					<input class="form-control" type="text" name="archive_dir" id="archive-dir-input" value="<?php if (isset($_POST['archive_dir'])){echo $_POST['archive_dir'];}?>"/>
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
					<div class="col-sm-3 cfop"><input class="form-control" type="text" name="cfop_1" maxlength="1" oninput="cfop_advance(1)" value="<?php if (isset($_POST['cfop_1'])){echo $_POST['cfop_1'];}?>" <?php if(isset($_POST['do_not_bill'])){echo 'disabled';}?>/></div>
					<div class="col-sm-3 cfop"><input class="form-control" type="text" name="cfop_2" maxlength="6" oninput="cfop_advance(2)" value="<?php if (isset($_POST['cfop_2'])){echo $_POST['cfop_2'];}?>" <?php if(isset($_POST['do_not_bill'])){echo 'disabled';}?>/></div>
					<div class="col-sm-3 cfop"><input class="form-control" type="text" name="cfop_3" maxlength="6" oninput="cfop_advance(3)" value="<?php if (isset($_POST['cfop_3'])){echo $_POST['cfop_1'];}?>" <?php if(isset($_POST['do_not_bill'])){echo 'disabled';}?>/></div>
					<div class="col-sm-3 cfop"><input class="form-control" type="text" name="cfop_4" maxlength="6" value="<?php if (isset($_POST['cfop_1'])){echo $_POST['cfop_4'];}?>" <?php if(isset($_POST['do_not_bill'])){echo 'disabled';}?>/></div>
				</div>
			</div>
		</div>
		<div class="form-group">
			<div class="col-sm-4 col-sm-offset-2">
				<div class="btn-group">
					<input class="btn btn-primary" type="submit" name="add_dir" value="Add directory" /> <input class="btn btn-default" type="submit" name="cancel_dir" value="Cancel" />
				</div>
			</div>
		</div>
	</fieldset>
</form>
<script type="text/javascript">
	$('#dnb_input').on('click',bill_toggle);
</script>
<?php
	require_once 'includes/footer.inc.php';