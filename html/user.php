<?php
	require_once 'includes/header.inc.php';
	
	
	$user_id = $login_user->get_user_id();
	if (isset($_GET['user_id']) && (is_numeric($_GET['user_id']))) {
	    $user_id = $_GET['user_id'];
	}
	if (!$login_user->permission($user_id)) {
        echo "<div class='alert alert-error'>Invalid Permissions</div>";
        exit;
	}	
	$user = new user($db,$ldap,$user_id);
	
	?>
	<table class="table table-bordered table-condensed table-striped">
		<tr>
			<td>Name:</td>
			<td><?php echo $user->get_full_name(); ?></td>
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
		<?php if ($user->has_directory()){ 
			$usage = data_usage::latestUsage($db,$user->get_user_id());
			$balance = transaction::latestTransaction($db,$user->get_user_id());
		?>
		<tr>
			<td>CFOP:</td>
			<td><?php echo $user->get_cfop(); ?></td>
		</tr>
		<tr>
			<td>Archive Directory:</td>
			<td><?php echo __ARCHIVE_DIR__.$user->get_archive_directory(); ?></td>
		</tr>
		<tr>
			<td>Archive Usage:</td>
			<td><?php if($usage != null){echo number_format($usage->get_directory_size()/1048576,4);} else {echo 0;} ?> TB</td>
		</tr>
		<tr>
			<td>Balance:</td>
			<td>$<?php echo number_format( ($balance==NULL?0:$balance->get_balance()), 2); ?></td>
		</tr>
		<?php } ?>
	</table>
	
	<?php if ($login_user->is_admin()){ ?>
	<div class="btn-group">
		<a href="edit_user.php?user_id=<?php echo $user_id; ?>" class="btn btn-primary"><span class="glyphicon glyphicon-pencil"></span> Edit User</a>
		<a href="log_transaction.php?user_id=<?php echo $user_id; ?>" class="btn btn-success"><span class="glyphicon glyphicon-usd"></span> Add Transaction</a>
		<a href="user_bill.php?user_id=<?php echo $user_id; ?>" class="btn btn-info">User Bill</a>
	</div>
		
<?php
	}
	require_once 'includes/footer.inc.php';