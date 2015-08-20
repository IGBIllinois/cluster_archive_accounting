<?php
	require_once 'includes/header.inc.php';

	if(!$login_user->is_admin()){
		exit;
	}
	
	$unwatched = data_functions::get_unmonitored_dirs($db);
	$table_html = "";
	foreach($unwatched as $dir){
		$table_html .= '<tr><td>'.$dir.'</td></tr>';
	}
?>
<h3>Unmonitored Archive Directories</h3>
<p>The following directories are in the archive but are not being monitored by the accounting program.</p>
<table class="table table-bordered table-condensed table-striped">
	<?php echo $table_html;?>
</table>
<?php
	require_once 'includes/footer.inc.php';