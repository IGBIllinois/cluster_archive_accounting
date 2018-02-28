<?php
	require_once 'includes/header.inc.php';
	
	
	$user_id = $login_user->get_user_id();
	if (isset($_GET['user_id']) && (is_numeric($_GET['user_id']))) {
	    $user_id = $_GET['user_id'];
	}
	if (!$login_user->permission($user_id)) {
        echo html::error_message("Invalid Permissions");
        exit;
	}	
	$user = new user($db,$ldap,$user_id);

	$directory_html = "";	
	if ($user->has_directory()){ 
		$directories = $user->get_directories();
		foreach($directories as $directory){
			$usage = data_usage::latestUsage($db,$directory->get_id());
			$directory_html .= "<tr class='topborder'><td>Directory:</td><td>".__ARCHIVE_DIR__.$directory->get_directory();
			$directory_html .= "</td></tr>";
			if($directory->get_do_not_bill()==0) $directory_html .= "<tr><td>CFOP:</td><td>".$directory->get_cfop()."</td></tr>";
			if($directory->get_do_not_bill()==0) $directory_html .= "<tr><td>Activity Code:</td><td>".$directory->get_activity_code()."</td></tr>";
			$directory_html .= "<tr><td>Usage:</td><td>".number_format($usage->get_directory_size()/1048576,4)." TB</td></tr>";
			if($directory->get_do_not_bill()==0) $directory_html .= "<tr><td>Pre-paid Terabytes:</td><td>".token_transaction::tokenBalance($db,$directory->get_id())."</td></tr>";
			if($login_user->is_admin() && count($directories)>1){
				$directory_html .= "<tr><td></td><td><div class='btn-group btn-group-sm'>";
				$directory_html .= "<a href='edit_directory.php?directory_id=".$directory->get_id()."' class='btn btn-primary'><span class='glyphicon glyphicon-pencil'></span> Edit Directory</a>";
				$directory_html .= '<a href="add_tokens.php?directory_id='.$directory->get_id().'" class="btn btn-success"><span class="glyphicon glyphicon-usd"></span> Pre-pay</a>';
				$directory_html .= "</div></td></tr>";
			}
		}
	}
	?>
	<style>
		tr.topborder {
			border-top: 2px solid darkgrey;
		}	
	</style>
	<table class="table table-bordered table-condensed table-striped">
		<tr>
			<td>Name:</td>
			<td><?php echo $user->get_name(); ?></td>
		</tr>
		<tr>
			<td>Username:</td>
			<td><?php echo $user->get_username(); ?></td>
		</tr>
		<tr>
			<td>Email:</td>
			<td><?php echo $user->get_email(); ?></td>
		</tr>
		<tr>
			<td>Time Created:</td>
			<td><?php echo $user->get_time_created(); ?></td>
		</tr>
		<tr>
			<td>Administrator:</td>
			<td><?php if ($user->is_admin()){
				echo '<span class="glyphicon glyphicon-ok"></span>';
			} else {
				echo '<span class="glyphicon glyphicon-remove"></span>';
			}
			?>
			</td>
		</tr>
		<tr>
			<td>Has directory:</td>
			<td><?php if ($user->has_directory()){
				echo '<span class="glyphicon glyphicon-ok"></span>';
			} else {
				echo '<span class="glyphicon glyphicon-remove"></span>';
			}
			?>
			</td>
		</tr>
		<?php 
			echo $directory_html;
		?>
	</table>
	
	<?php if ($login_user->is_admin()){ ?>
	<div class="btn-group">
		<a href="edit_user.php?user_id=<?php echo $user_id; ?>" class="btn btn-primary"><span class="glyphicon glyphicon-pencil"></span> Edit User</a>
		<?php if(count($user->get_directories())==1){?>
		<a href="edit_directory.php?directory_id=<?php echo $directories[0]->get_id();?>" class="btn btn-primary btn"><span class="glyphicon glyphicon-pencil"></span> Edit Directory</a>
		<a href="add_tokens.php?directory_id=<?php echo $directories[0]->get_id(); ?>" class="btn btn-success"><span class="glyphicon glyphicon-usd"></span> Pre-pay</a>
		<?php } ?>
		<a href="user_bill.php?user_id=<?php echo $user_id; ?>" class="btn btn-info">User Bill</a>
	</div>
		
<?php
	}
	require_once 'includes/footer.inc.php';