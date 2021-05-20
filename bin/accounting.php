<?php
ini_set("display_errors",1);
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
	$ldap = new ldap(__LDAP_HOST__,__LDAP_SSL__,__LDAP_PORT__,__LDAP_BASE_DN__);
	$settings = new settings($db);

	// Get archive directories from database
	$rows = $db->query("select directory,directories.id from directories left join users on users.id=directories.user_id where directories.is_enabled=1 and users.is_enabled=1 and directory is not null and directory!=''");
	$data_usage = new data_usage($db);
	$arch_file = new archive_file($db);
	$dir = new archive_directory($db);
	$prevmonth = date('n')-1;
	$prevyear = date('Y');
	if($prevmonth==0){
		$prevmonth = 12;
		$prevyear-=1;
	}
	$email_users = array();
	foreach ( $rows as $key=>$row ){
	    if(__USE_BUCKETS__){
	        echo sprintf("Bucket '%s'...", $row['directory']);
            // Gather usage info
            // Total Usage in MB
            $usage = exec("get_bucket_size.py --bucket=".$row['directory']);
            preg_match("/^[^\t]*\\t(.*)/u", $usage, $matches);
            $usage = $matches[1]/1024;
            echo $usage . ' MB... ';

            $numsmallfiles = 0;

            // Store usage data in database
            $data_usage->create($row['id'], $usage, $numsmallfiles);

            // Set previous month's usage to not pending
            $latestUsage = data_usage::usage_from_month($db, $row['id'], $prevmonth, $prevyear);
            if ($latestUsage->get_pending() == 1) {
                $latestUsage->set_pending(0);
                $dir->load_by_id($latestUsage->get_directory_id());
                if ($latestUsage->get_cost() > 0 && !in_array($dir->get_user_id(), $email_users)) {
                    array_push($email_users, $dir->get_user_id());
                }
            }

            echo "Done.\n";
            log::log_message("Scanned bucket " . $row['directory'] . ': ' . $usage . ' MB.');
        } else {
            if (file_exists(__ARCHIVE_DIR__ . $row['directory'])) {
                echo __ARCHIVE_DIR__ . $row['directory'] . "... ";
                // Gather usage info
                // Total Usage in MB
                $usage = exec("du -sm " . __ARCHIVE_DIR__ . $row['directory']);
                preg_match("/^(.*)\\t/u", $usage, $matches);
                $usage = $matches[1];
                echo $usage . ' MB... ';

                // # of small files
                unset($allfiles);
                exec(
                    "find " . __ARCHIVE_DIR__ . $row['directory'] . " -type f -size -" . $settings->get_setting(
                        'small_file_size'
                    ) . "k | wc -l",
                    $allfiles
                );
                $numsmallfiles = trim($allfiles[0]);
                echo $numsmallfiles . ' small files... ';
                // Store usage data in database
                $data_usage->create($row['id'], $usage, $numsmallfiles);

                // Set previous month's usage to not pending
                $latestUsage = data_usage::usage_from_month($db, $row['id'], $prevmonth, $prevyear);
                if ($latestUsage->get_pending() == 1) {
                    $latestUsage->set_pending(0);
                    $dir->load_by_id($latestUsage->get_directory_id());
                    if ($latestUsage->get_cost() > 0 && !in_array($dir->get_user_id(), $email_users)) {
                        array_push($email_users, $dir->get_user_id());
                    }
                }

                echo "Done.\n";
                log::log_message("Scanned " . __ARCHIVE_DIR__ . $row['directory'] . ': ' . $usage . ' MB.');
            } else {
                log::log_message("Directory " . __ARCHIVE_DIR__ . $row['directory'] . ' does not exist.');
            }
        }
	}
	// Email users with bills from last month
/*
	$user = new user($db,$ldap);
	foreach ($email_users as $userid){
		$user->load_by_id($userid);
		$user->email_bill(__ADMIN_EMAIL__);
	}
*/
}