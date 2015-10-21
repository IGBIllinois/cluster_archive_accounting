<?php
	require_once 'includes/header.inc.php';
	
	// Default settings
	$directories = $login_user->get_directories();
	$directory_id = 0;
	if(count($directories)>0){
		$directory_id = $directories[0]->get_id();
	}
	
	// Currently selected date
	if (isset($_GET['year'])) {
		$year = $_GET['year'];
		$start_date = $year . "0101";
		$directory_id = $_GET['directory_id'];
	}
	else {
		$year = date('Y');
		$start_date = date('Y') . "0101";
	}
	
	$end_date = date('Ymd',strtotime('-1 second',strtotime('+1 year',strtotime($start_date))));
	
	// User list
	$dir_list = array();
	$dir_list = user_functions::get_directories($db,$login_user);
	if($directory_id==0){
		$directory_id = $dir_list[0]['dir_id'];
	}
	
	$directory = new archive_directory($db);
	$directory->load_by_id($directory_id);
	
	if (!$login_user->permission($directory->get_user_id())) {
        echo html::error_message("Invalid Permissions");
        exit;
	}
	
	$dir_list_html = "";
	if (count($dir_list)) {
        $dir_list_html = "<div class='form-group'><label class='inline'>Directory:</label> <select class='form-control input-sm' name='directory_id'>";
        foreach ($dir_list as $dir) {
	        if($dir['do_not_bill']==0){
		        if ($dir['dir_id'] == $directory_id) {
		            $dir_list_html .= "<option value='" . $dir['dir_id'] . "' selected='true'>" . $dir['username'] ." - ".__ARCHIVE_DIR__.$dir['directory'] . "</option>";
		        }
		        else {
		            $dir_list_html .= "<option value='" . $dir['dir_id'] . "'>" . $dir['username'] ." - ".__ARCHIVE_DIR__.$dir['directory'] . "</option>";
		        }
			}
        }
        $dir_list_html .= "</select></div>";
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
	
	$trans_list = $directory->get_transaction_list($year);
	$list_html = "";

	foreach($trans_list as $value){
		$list_html .= "<tr>";
		$list_html .= "<td>".($value['amount']<0?"Usage":"Payment")."</td>";
		$list_html .= "<td ".($value['amount']<0?"class='negative'":"").">".($value['amount']<0?"-":"")."$".abs($value['amount'])."</td>";
		$list_html .= "<td ".($value['balance']<0?"class='negative'":"").">".($value['balance']<0?"-":"")."$".abs($value['balance'])."</td>";
		$list_html .= "<td>".html::get_pretty_date_mysql($value['transaction_time'])."</td>";
		$list_html .= "<td>".$value['cfop']."</td>";
		$list_html .= "<td>".$value['activity_code']."</td>";
		$list_html .= "<td style='width:36px'><a class='btn btn-primary btn-xs' href='edit_transaction.php?id=".$value['id']."'><span class='glyphicon glyphicon-pencil'></span></a></td>";
		$list_html .= "</tr>";
	}

	$user = new user($db,$ldap,$directory->get_user_id());
	?>
	<form class="form-inline" action='<?php echo $_SERVER['PHP_SELF']; ?>' method="get">
		<?php if ($login_user->is_admin()){
			echo $dir_list_html;
		} ?>
		<div class="form-group">
			<label>Year: </label>
			<?php echo $year_html; ?>
		</div>
		<input class="btn btn-primary btn-sm" type="submit" value="Get Transactions" />
	</form>
	
	<h4>User Bill - <?php echo $year; ?></h4>
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
				<th>Type</th>
				<th>Amount</th>
				<th>Balance</th>
				<th>Date</th>
				<th>CFOP</th>
				<th>Activity Code</th>
				<th></th>
			</tr>
		</thead>
		<?php echo $list_html; ?>
	</table>
	
	<?php
	require_once 'includes/footer.inc.php';