<?php
class data_stats {


	public static function get_total_cost($db,$start_date,$end_date,$format = 0) {
	        $sql = "SELECT SUM(cost) as total_cost ";
        	$sql .= "FROM archive_usage ";
	        $sql .= "WHERE usage_time BETWEEN :startdate AND :enddate ";
	        $args = array(':startdate'=>$start_date,':enddate'=>$end_date);
	        $result = $db->query($sql,$args);
		$cost = 0;
	        if ($result) {
			$cost = $result[0]['total_cost'];
			if ($format) {
				$cost = number_format($result[0]['total_cost'],2);
			}
		}
		return $cost;
	}

}
?>
