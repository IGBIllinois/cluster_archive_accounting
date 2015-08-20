<?php 
	require_once 'includes/header.inc.php';
	$settings = new settings($db);
?>

<div class='jumbotron'>
	<h1>
		<img src="images/imark_bw.gif" style="padding: 0 10px 10px 0; vertical-align: text-top;">
		Biocluster Archive Accounting
	</h1>
	<p>View, manage, and bill Biocluster Archive usage</p>
</div>
<div class="col-md-8">
	<h3>Archive Cost</h3>
	<p>Archive usage cost is calculated based on the sum total size of all files stored on the archive. Each terabyte of data (rounded up) will cost $<?php echo $settings->get_setting('data_cost');?> per ten years. The first <?php echo html::human_readable_size($settings->get_setting('min_billable_data'),'MB',1);?> of space is free.</p>
	<table class="table table-bordered table-striped">
		<tr>
			<th>Amount</th>
			<th>Cost</th>
		</tr>
		<tr>
			<td>0 - <?php echo html::human_readable_size($settings->get_setting('min_billable_data'),'MB',1)?></td>
			<td>$0</td>
		</tr>
		<tr>
			<td><?php echo html::human_readable_size($settings->get_setting('min_billable_data')+0.1,'MB',4)?> - 0.9999 TB</td>
			<td>$<?php echo $settings->get_setting('data_cost');?></td>
		</tr>
		<tr>
			<td>1.0000 - 1.9999 TB</td>
			<td>$<?php echo 2*$settings->get_setting('data_cost');?></td>
		</tr>
		<tr>
			<td colspan="2">Etc.</td>
		</tr>
	</table>
</div>
<?php
	require_once 'includes/footer.inc.php';
	?>