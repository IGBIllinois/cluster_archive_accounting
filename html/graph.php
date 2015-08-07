<?php
	require_once 'includes/main.inc.php';
	set_time_limit(0);
	
	$start_date = "";
	$end_date = "";
	$year = date('Y');
	if (isset($_GET['year'])) {
		$year = $_GET['year'];
	}
	
	elseif (isset($_GET['start_date']) && isset($_GET['end_date'])) {
	
	        $start_date = $_GET['start_date'];
	        $end_date = $_GET['end_date'];
	}
	
	$user_id = 0;
	if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
		$user_id = $_GET['user_id'];
	}
	
	$graph_type = "";
	if (isset($_GET['graph_type'])) {
		$graph_type = $_GET['graph_type'];
	}
	
	if($graph_type=='user_usage_per_month'){
		$stats = new statistics($db);
		$data = $stats->get_usage_per_month($year,$user_id);
		$jsonData = array();
		$jsonData['cols'] = array( array("label"=>"Month","type"=>"string"),array("label"=>"Usage (TB)","type"=>"number") );
		$jsonData['rows'] = array();
		foreach($data as $row){
			array_push($jsonData['rows'],
				array( "c"=>array( 
					array("v"=>$row[0]),
					array("v"=>floatval($row[1]), "f"=>number_format($row[1],4)) 
				))
			);
		}
		echo json_encode($jsonData);
	}
	else if ($graph_type == 'user_delta_usage_per_month'){
		$stats = new statistics($db);
		$data = $stats->get_delta_usage_per_month($year,$user_id);
		$jsonData = array();
		$jsonData['cols'] = array( array("label"=>"Month","type"=>"string"), array("label"=>"∆ Usage (TB)","type"=>"number") );
		$jsonData['rows'] = array();
		foreach($data as $row){
			array_push($jsonData['rows'],
				array( "c"=>array( 
					array("v"=>$row[0]),
					array("v"=>floatval($row[1]), "f"=>number_format($row[1],4)) 
				))
			);	
		}
		echo json_encode($jsonData);
	}
	else if ($graph_type=='user_smallfiles_per_month'){
		$stats = new statistics($db);
		$data = $stats->get_smallfiles_per_month($year,$user_id);
		$jsonData = array();
		$jsonData['cols'] = array( array("label"=>"Month","type"=>"string"),array("label"=>"# of Files","type"=>"number") );
		$jsonData['rows'] = array();
		foreach($data as $row){
			array_push($jsonData['rows'],
				array( "c"=>array( 
					array("v"=>$row[0]),
					array("v"=>intval($row[1])) 
				))
			);
		}
		echo json_encode($jsonData);
	}
	else if ($graph_type=='user_cost_per_month'){
		$stats = new statistics($db);
		$data = $stats->get_cost_per_month($year,$user_id);
		$jsonData = array();
		$jsonData['cols'] = array( array("label"=>"Month","type"=>"string"),array("label"=>"Cost of Archive Usage","type"=>"number") );
		$jsonData['rows'] = array();
		foreach($data as $row){
			array_push($jsonData['rows'],
				array( "c"=>array( 
					array("v"=>$row[0]),
					array("v"=>floatval($row[1]), "f"=>"$".number_format($row[1],2)) 
				))
			);
		}
		echo json_encode($jsonData);
	}
	else if ($graph_type=='user_balance_per_month'){
		$stats = new statistics($db);
		$data = $stats->get_balance_per_month($year,$user_id);
		$jsonData = array();
		$jsonData['cols'] = array( array("label"=>"Month","type"=>"string"),array("label"=>"Balance","type"=>"number") );
		$jsonData['rows'] = array();
		foreach($data as $row){
			array_push($jsonData['rows'],
				array( "c"=>array( 
					array("v"=>$row[0]),
					array("v"=>floatval($row[1]), "f"=>"$".number_format($row[1],2)) 
				))
			);
		}
		echo json_encode($jsonData);
	}
	else if($graph_type=='usage_per_month'){
		$stats = new statistics($db);
		$data = $stats->get_usage_per_month($year);
		$jsonData = array();
		$jsonData['cols'] = array( array("label"=>"Month","type"=>"string"),array("label"=>"Usage (TB)","type"=>"number") );
		$jsonData['rows'] = array();
		foreach($data as $row){
			array_push($jsonData['rows'],
				array( "c"=>array( 
					array("v"=>$row[0]),
					array("v"=>floatval($row[1]), "f"=>number_format($row[1],4)) 
				))
			);
		}
		echo json_encode($jsonData);
	}
	else if ($graph_type == 'delta_usage_per_month'){
		$stats = new statistics($db);
		$data = $stats->get_delta_usage_per_month($year);
		$jsonData = array();
		$jsonData['cols'] = array( array("label"=>"Month","type"=>"string"), array("label"=>"∆ Usage (TB)","type"=>"number") );
		$jsonData['rows'] = array();
		foreach($data as $row){
			array_push($jsonData['rows'],
				array( "c"=>array( 
					array("v"=>$row[0]),
					array("v"=>floatval($row[1]), "f"=>number_format($row[1],4)) 
				))
			);	
		}
		echo json_encode($jsonData);
	}
	else if ($graph_type=='smallfiles_per_month'){
		$stats = new statistics($db);
		$data = $stats->get_smallfiles_per_month($year);
		$jsonData = array();
		$jsonData['cols'] = array( array("label"=>"Month","type"=>"string"),array("label"=>"# of Files","type"=>"number") );
		$jsonData['rows'] = array();
		foreach($data as $row){
			array_push($jsonData['rows'],
				array( "c"=>array( 
					array("v"=>$row[0]),
					array("v"=>intval($row[1])) 
				))
			);
		}
		echo json_encode($jsonData);
	}
	else if ($graph_type=='cost_per_month'){
		$stats = new statistics($db);
		$data = $stats->get_cost_per_month($year);
		$jsonData = array();
		$jsonData['cols'] = array( array("label"=>"Month","type"=>"string"),array("label"=>"Cost of Archive Usage","type"=>"number") );
		$jsonData['rows'] = array();
		foreach($data as $row){
			array_push($jsonData['rows'],
				array( "c"=>array( 
					array("v"=>$row[0]),
					array("v"=>floatval($row[1]), "f"=>"$".number_format($row[1],2)) 
				))
			);
		}
		echo json_encode($jsonData);
	}
	else if($graph_type=='top_usage_users'){
		$stats = new statistics($db);
		$data = $stats->get_top_usage_users($start_date,$end_date,8);
		$jsonData = array();
		$jsonData['cols'] = array( array("label"=>"User","type"=>"string"),array("label"=>"Usage (TB)","type"=>"number") );
		$jsonData['rows'] = array();
		foreach($data as $row){
			array_push($jsonData['rows'],
				array( "c"=>array( 
					array("v"=>$row['username']),
					array("v"=>floatval($row['total_usage']), "f"=>number_format($row['total_usage'],4)) 
				))
			);
		}
		echo json_encode($jsonData);
	}
	else if($graph_type=='top_cost_users'){
		$stats = new statistics($db);
		$data = $stats->get_top_cost_users($start_date,$end_date,8);
		$jsonData = array();
		$jsonData['cols'] = array( array("label"=>"User","type"=>"string"),array("label"=>"Cost","type"=>"number") );
		$jsonData['rows'] = array();
		foreach($data as $row){
			array_push($jsonData['rows'],
				array( "c"=>array( 
					array("v"=>$row['username']),
					array("v"=>floatval($row['total_cost']), "f"=>"$".number_format($row['total_cost'],2)) 
				))
			);
		}
		echo json_encode($jsonData);
	}
	else if($graph_type=='top_smallfiles_users'){
		$stats = new statistics($db);
		$data = $stats->get_top_smallfiles_users($start_date,$end_date,8);
		$jsonData = array();
		$jsonData['cols'] = array( array("label"=>"User","type"=>"string"),array("label"=>"# of small files","type"=>"number") );
		$jsonData['rows'] = array();
		foreach($data as $row){
			array_push($jsonData['rows'],
				array( "c"=>array( 
					array("v"=>$row['username']),
					array("v"=>intval($row['total_smallfiles']), "f"=>$row['total_smallfiles']) 
				))
			);
		}
		echo json_encode($jsonData);
	}