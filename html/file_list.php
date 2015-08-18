<?php
	require_once 'includes/main.inc.php';
	
	$year = date('Y');
	if (isset($_GET['year'])) {
		$year = $_GET['year'];
	}
	$month = date('m');
	if (isset($_GET['month'])) {
		$month = $_GET['month'];
	}
	if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
		$user_id = $_GET['user_id'];
	} else {
		exit;
	}
	
	$filelist = archive_file::get_file_list($db,$month,$year,$user_id);
	echo json_encode($filelist);