<?php

class data_functions {

	// Returns billing info for all users for the given month and year. Column names are human-readable for output directly to a spreadsheet
	public static function get_data_bill($db,$month,$year) {
		// This SQL statement uses column aliases as they'll be fed directly into a spreadsheet in some cases
		$sql = "SELECT accounts.username as Username, accounts.archive_directory as Directory, ROUND(archive_usage.directory_size / 1048576,3) as Usage, archive_usage.cost as Cost, (select sum(transactions.amount) from transactions where transactions.account_id=accounts.id and ((month(transaction_time)<=:month and year(transaction_time)=year) or year(transaction_time)<:year)) as Balance, accounts.cfop as CFOP FROM archive_usage LEFT JOIN accounts ON archive_usage.account_id=accounts.id WHERE YEAR(archive_usage.usage_time)=:year AND MONTH(archive_usage.usage_time)=:month group by accounts.id ORDER BY Directory ASC";
		$args = array(':year'=>$year,':month'=>$month);
    	return $db->query($sql,$args);
	}

	// Returns a list of all the directories under the base directory.
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
	
	// Returns a list of all directories under the base directory that are not associated with a user in the database.
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
