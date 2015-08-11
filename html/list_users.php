<?php
	require_once 'includes/header.inc.php';
	
	if (!$login_user->is_admin()){
		exit;
	}
	
	$get_array = array();
	$start = 0;
	$count = 30;
	
	if ( isset($_GET['start']) && is_numeric($_GET['start']) ){
		$start = $_GET['start'];
		$get_array['start'] = $start;
	}
	
	$search = "";
	if ( isset($_GET['search']) ){
		$search = $_GET['search'];
		$get_array['search'] = $search;
	}
	
	$all_users = user_functions::get_users($db,$ldap,$search);
	$num_users = count($all_users);
	$pages_url = $_SERVER['PHP_SELF']."?".http_build_query($get_array);
	$pages_html = html::get_pages_html($pages_url,$num_users,$start,$count);
	$users_html = "";
	$user_count = 0;
	
	$users_html = html::get_users_rows($all_users,$start,$count);
	
	?>
	
	<h3>List of Users</h3>
	<div class="row" style="margin-bottom:15px;">
		<div class="col-md-4">
			<form method="get" action='<?php echo $_SERVER['PHP_SELF']; ?>'>
				<div class="input-group">
					<input type="text" name="search" class="form-control" value="<?php if (isset($search)){echo $search; } ?>" />
					<span class="input-group-btn">
						<input type="submit" class="btn" value="Search" />
					</span>
				</div>
			</form>
		</div>
	</div>
	
	<table class="table table-bordered table-condensed table-striped">
		<thead>
			<tr>
				<th>NetID</th>
				<th>Name</th>
				<th>Directory</th>
				<th>Administrator</th>
				<th>Active LDAP Account</th>
			</tr>
		</thead>
		<tbody>
			<?php echo $users_html; ?>
		</tbody>
	</table>
	
	<?php echo $pages_html; ?>
<?php
	require_once 'includes/footer.inc.php';