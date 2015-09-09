<?php
	require_once 'includes/header.inc.php';
	
	// Currently selected user
	$user_id = $login_user->get_user_id();
	if (isset($_GET['user_id']) && (is_numeric($_GET['user_id']))) {
        $user_id = $_GET['user_id'];
	}
	if (!$login_user->permission($user_id)) {
        echo "<div class='alert alert-error'>Invalid Permissions</div>";
        exit;
	}
	
	// Currently selected date
	if (isset($_GET['month']) && isset($_GET['year'])) {
		$year = $_GET['year'];
		$month = $_GET['month'];
		$start_date = $year . $month . "01";
	}
	else {
		$year = date('Y');
		$month = date('m');
		$start_date = date('Ym') . "01";
	}
	
	$end_date = date('Ymd',strtotime('-1 second',strtotime('+1 month',strtotime($start_date))));
	$month_name = date('F',strtotime($start_date));
	
	//list of users to select from
	$user_list = array();
	if ($login_user->is_admin()) {
		$user_list = user_functions::get_all_users($db);
	}
	$user_list_html = "";
	if (count($user_list)) {
		$user_list_html = "<div class='form-group'><label>User: </label> <select class='form-control input-sm' name='user_id'>";
		foreach ($user_list as $user) {
			if ($user['id'] == $user_id) {
				$user_list_html .= "<option value='" . $user['id'] . "' selected='true'>" . $user['username'] . "</option>";
			} else {
				$user_list_html .= "<option value='" . $user['id'] . "'>" . $user['username'] . "</option>";
			}
		}
		$user_list_html .= "</select></div>";
	}
	
	//////Year////////
	$year_html = "<select class='form-control input-sm' name='year'>";
	for ($i=2015; $i<=date("Y");$i++) {
		if ($i == $year) {
			$year_html .= "<option value='$i' selected='true'>$i</option>";
		}
		else { $year_html .= "<option value='$i'>$i</option>";
		}
	}
	$year_html .= "</select>";
	
	///////Month///////
	$month_array = array('01','02','03','04','05','06','07','08','09','10','11','12');
	$month_html = "<select class='form-control input-sm' name='month'>";
	foreach ($month_array as $month_number) {
		if ($month_number == $month) {
			$month_html .= "<option value='" . $month_number . "' selected='true'>" . $month_number . "</option>";
		}
		else { $month_html .= "<option value='" . $month_number . "'>" . $month_number . "</option>";
		}
	}
	$month_html .= "</select>";
	
	$user = new user($db,$ldap,$user_id);
	
	$data_usage = $user->get_data_summary($month,$year);
	$data_html = "";
	foreach($data_usage as $value){
		if($value['terabytes']!=null){
			$data_html .= "<tr>";
			$data_html .= "<td>".__ARCHIVE_DIR__.$value['directory']."</td>";
			$data_html .= "<td>".$value['terabytes']." TB</td>";
			$data_html .= "<td>".$value['prevusage']." TB</td>";
			if($value['do_not_bill']==0){
				$data_html .= "<td>$".number_format($value['cost'],2)."</td>";
				$data_html .= "<td>".$value['cfop']."</td>";
			} else {
				$data_html .= "<td colspan='2'></td>";
			}
			$data_html .= "</tr>";
		}
	}
	?>
	<form class="form-inline" action='<?php echo $_SERVER['PHP_SELF']; ?>' method="get">
		<?php if ($login_user->is_admin()){
			echo $user_list_html;
		} ?>
		<div class="form-group">
			<label>Month: </label>
			<?php echo $month_html; ?>
		</div>
		<div class="form-group">
			<label>Year: </label>
			<?php echo $year_html; ?>
		</div>
		<input class="btn btn-primary btn-sm" type="submit" value="Get Bill" />
	</form>
	
	<h4>User Bill - <?php echo $month_name . " " . $year; ?></h4>
	<table class='table table-condensed table-striped table-bordered'>
	
		<tr>
			<td>Name:</td>
			<td><?php echo $user->get_name(); ?></td>
		</tr>
		<tr>
			<td>Username:</td>
			<td><?php echo $user->get_username(); ?></td>
		</tr>
		<tr>
			<td>Billing Dates:</td>
			<td><?php echo html::get_pretty_date($start_date); ?> - <?php echo html::get_pretty_date($end_date); ?></td>
		</tr>
	</table>

	<h4>Data Usage</h4>
	<table class="table table-bordered table-condensed table-striped">
		<thead>
			<tr>
				<th>Directory</th>
				<th>Usage</th>
				<th>Previous Usage</th>
				<th>Cost</th>
				<th>CFOP</th>
			</tr>
		</thead>
		<?php echo $data_html; ?>
	</table>
	
	<?php
	require_once 'includes/footer.inc.php';