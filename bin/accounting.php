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
	// Connect to database
	$db = new db(__MYSQL_HOST__,__MYSQL_DATABASE__,__MYSQL_USER__,__MYSQL_PASSWORD__);

	// Get archive directories from database
	$rows = $db->query("select archive_directory,id,username from accounts where is_enabled=1 and archive_directory is not null");
	$data_usage = new data_usage($db);
	$arch_file = new archive_file($db);
	foreach ( $rows as $key=>$row ){		
		// Gather usage info
		// Total Usage in MB
		$usage = exec("du -am ".__ARCHIVE_DIR__.$row['archive_directory']);
		preg_match("/^(.*)\\t/u", $usage, $matches);
		$usage = $matches[1];
		
		// Per-file info
		// Usage in KB
		unset($allfiles);
		exec("find ".__ARCHIVE_DIR__.$row['archive_directory']." -type f -exec du -ak {} +",$allfiles);
		// Tally up "small" files
		$numsmallfiles = 0;
		foreach ( $allfiles as $key=>$file ){
			preg_match("/^(.*)\\t/u", $file, $matches);
			if( intval($matches[1]) < __SMALL_FILE_SIZE__ ){
				$numsmallfiles += 1;
			}
		}
		
		// Store usage data in database
		$data_usage->create($row['id'],$usage,$numsmallfiles);
		foreach ( $allfiles as $key=>$file ){
			preg_match("/^(.*)\\t(.*)/u", $file, $matches);
			// Get date modified info for each file and save to database
			$datestr = exec("ls -lT '".$matches[2]."' | awk '{print $6,$7,$9, $8}'");
			$date = DateTime::createFromFormat('M d Y H:i:s',$datestr);
			// Store file info in database
			$arch_file->create($matches[2],$matches[1],$data_usage->get_id(),$date->format('Y-m-d H:i:s'));
		}
	}

}