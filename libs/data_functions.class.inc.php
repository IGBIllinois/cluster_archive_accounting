<?php

class data_functions {

	public static function get_data_bill($db,$month,$year) {
		$sql = "SELECT accounts.username, accounts.archive_directory as 'Directory', ROUND(archive_usage.directory_size / 1048576,3) as 'Terabytes', sum(archive_usage.cost) as 'Cost', sum(transactions.amount) as balance, accounts.cfop as 'CFOP' FROM archive_usage LEFT JOIN accounts ON archive_usage.account_id=accounts.id left join transactions on transactions.account_id=accounts.id WHERE YEAR(archive_usage.usage_time)=:year AND MONTH(archive_usage.usage_time)=:month group by accounts.id ORDER BY Directory ASC";
		$args = array(':year'=>$year,':month'=>$month);
    	return $db->query($sql,$args);
	}

	public static function get_existing_dirs() {
		$root_dirs = settings::get_root_data_dirs();
		
		$existing_dirs = array();
		foreach ($root_dirs as $dir) {
			
			$found_dirs = array();
			$found_dirs = array_diff(scandir($dir), array('..', '.'));
			foreach ($found_dirs as &$value) {
				$value = $dir . "/" . $value;
			}
			if (count($found_dirs)) {
				$existing_dirs = array_merge($existing_dirs,$found_dirs);
			}
			
			
		}
		return $existing_dirs;
		
	}
	public static function get_unmonitored_dirs($db) {
		$full_monitored_dirs = self::get_all_directories($db);

		$existing_dirs = self::get_existing_dirs();
		$monitored_dirs = array();
		foreach ($full_monitored_dirs as $dir) {
			array_push($monitored_dirs,$dir['data_dir_path']);
			
		}
		
		$unmonitored_dirs = array_diff($existing_dirs,$monitored_dirs);
		return $unmonitored_dirs;
		
		
		
	}
}
?>
