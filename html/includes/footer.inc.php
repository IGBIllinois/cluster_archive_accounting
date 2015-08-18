		</div>
		<div class="col-md-2 col-md-pull-10">
			<div class="sidebar-nav">
				<ul class="nav nav-pills nav-stacked">
					<li><a href="index.php">Main</a></li>
					<li><a href="user.php">User Information</a></li>
					<li><a href="user_bill.php">User Bill</a></li>
					<li><a href="user_graphs.php">User Graphs</a></li>
					<li><a href="user_files.php">User Files</a></li>
					<?php
					if ( isset($login_user) && $login_user->is_admin() ){
						?>
					<li><a href="data_billing.php">Billing Report</a></li>
					<li><a href="stats_accumulated.php">Accumulated Stats</a></li>
					<li><a href="stats_monthly.php">Monthly Stats</a></li>
					<li><a href="stats_yearly.php">Yearly Stats</a></li>
					<li><a href="stats_fiscal.php">Fiscal Stats</a></li>
					<li><a href="list_users.php">List Users</a></li>
					<li><a href="add_user.php">Add User</a></li>
					<li><a href="log_transaction.php">Log Transaction</a></li>
						<?php	
					}
					?>
				</ul>
			</div>
		</div>
	</div>
	<div class='navbar navbar-fixed-bottom' style='text-align: center'>
		<em>&copy 2015 University of Illinois Board of Trustees</em>
	</div>
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="includes/bootstrap/js/bootstrap.min.js"></script>
  </body>
</html>