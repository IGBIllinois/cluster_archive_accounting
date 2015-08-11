<?php

chdir(dirname(__FILE__));
set_include_path(get_include_path() . ':../libs');
function __autoload($class_name) {
	if(file_exists("../libs/" . $class_name . ".class.inc.php")) {
		require_once $class_name . '.class.inc.php';
	}
}

include_once '../conf/settings.inc.php';


$sapi_type = php_sapi_name();
// If run from command line
if ($sapi_type != 'cli') {
	echo "Error: This script can only be run from the command line.\n";
}
else {
	
	function dataCost($usage){
		if($usage < intval($settings->get_setting("min_billable_data"))){
			$cost = 0;
		} else {
			$cost = intval($settings->get_setting("data_cost")) * ceil($usage / 1048576.0);
		}
		return $cost;
	}
	
	// Connect to database
	$db = new db(__MYSQL_HOST__,__MYSQL_DATABASE__,__MYSQL_USER__,__MYSQL_PASSWORD__);
	$settings = new settings($db);

	// Get directories from database
	$rows = $db->query("select archive_directory,id,username from accounts where is_enabled=1 and archive_directory is not null");

	foreach ( $rows as $key=>$row ){		
		// Gather usage info
		// Usage in TB
		$usage = exec("du -am ".__ARCHIVE_DIR__.$row['archive_directory']);
		preg_match("/^(.*)\\t/u", $usage, $matches);
		$usage = $matches[1];
		// Number of small files
		exec("find ".__ARCHIVE_DIR__.$row['archive_directory']." -type f -exec du -am {} +",$allfiles);
		$numsmallfiles = 0;
		foreach ( $allfiles as $key=>$file ){
			preg_match("/^(.*)\\t/u", $file, $matches);
			if( intval($matches[1]) < __SMALL_FILE_SIZE__ ){
				$numsmallfiles += 1;
			}
		}
		
		$data_usage = new data_usage($db);
		$data_usage->create($row['id'],$usage,$numsmallfiles);
	}

}