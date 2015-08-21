<?php
	require_once 'includes/header.inc.php';
	if(!$login_user->is_admin()){
		exit;
	}
	$settings = new settings($db);
	// Handle incoming post
	if(isset($_POST['setting_key'])){
		if($settings->set_setting($_POST['setting_key'],$_POST['setting_value'])){
			$message = "<div class='alert alert-success'>Update successful</div>";
		} else {
			$message = "<div class='alert alert-danger'>Update unsuccessful</div>";
		}
	}
	
	// Set up settings table
	$all_settings = $settings->get_all_settings();
	$settings_html = "";
	foreach($all_settings as $setting){
		$settings_html .= "<div class='row'>";
		$settings_html .= "<form class='form-inline' method='post' action='settings.php'>";
		$settings_html .= "<div class='col-sm-3'><p class='form-control-static'>".$setting->get_name()."</p></div>";
		$settings_html .= "<div class='col-sm-3'><p class='form-control-static'>".$setting->get_value()."</p></div>";
		$settings_html .= "<div class='col-sm-3'><p class='form-control-static'>".$setting->get_modified()."</p></div>";
		$settings_html .= "<div class='col-sm-3'><input type='hidden' name='setting_key' value='".$setting->get_key()."'><div class='input-group'><input class='form-control' type='text' name='setting_value'/><span class='input-group-btn'><input class='btn btn-primary' type='submit' name='setting_submit' value='Update'/></span></div></div>";
		$settings_html .= "</form>";
		$settings_html .= "</div>";
	}
?>
	<style>
		.settings-table .row {
			padding: 4px 0px;
		}
		.settings-table .row:nth-child(even){
			background-color: #f9f9f9;
		}	
	</style>
	<h3>Settings</h3>
	<div class='row'>
		<div class='col-md-8'>
			<div class='settings-table'>
				<div class='row'>
					<div class='col-sm-3'><strong>Setting</strong></div>
					<div class='col-sm-3'><strong>Value</strong></div>
					<div class='col-sm-3'><strong>Last Modified</strong></div>
				</div>
				<?php echo $settings_html; ?>
			</div>
			<?php if(isset($message)){echo $message;} ?>
		</div>
	</div>
<?php
	require_once 'includes/footer.inc.php';
	