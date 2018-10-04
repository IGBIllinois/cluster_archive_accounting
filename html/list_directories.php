<?php
	require_once 'includes/header.inc.php';
	
	if (!$login_user->is_admin()){
		exit;
	}
	
	$all_directories = archive_directory::get_all_directories($db);
	?>
	
	<h3>Directories</h3>

	<table class="table table-bordered table-condensed table-striped">
		<thead>
			<tr>
				<th>Directory</th>
				<th>Owner</th>
				<th>Size</th>
				<th>CFOP</th>
				<th>Activity Code</th>
				<th>Date Added</th>
			</tr>
		</thead>
		<tbody>
			<?php 
				foreach($all_directories as $dir){
					if($dir->get_enabled()){
						$user = $dir->get_user();
						$usage = $dir->get_latest_usage();
						echo "<tr><td><a href='edit_directory.php?directory_id=".$dir->get_id()."'>".$dir->get_directory()."</a></td><td><a href='user.php?user_id=".$user->get_user_id()."'>".$user->get_username()."</a></td><td>".html::human_readable_size($usage->get_directory_size())."</td><td>".($dir->get_do_not_bill()?"Not billed":$dir->get_cfop())."</td><td>".$dir->get_activity_code()."</td><td>".html::get_pretty_date_mysql($dir->get_time_created())."</td></tr>";
					}
				}
			?>
		</tbody>
	</table>
	
<?php
	require_once 'includes/footer.inc.php';