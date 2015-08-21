<?php

// Log functions
class log {

	// Records the given message in the log file. $quiet logs in log file but not to screen
	public static function log_message($message,$quiet=true) {
        $current_time = date('Y-m-d H:i:s');
        $full_msg = $current_time . ": " . $message . "\n";
        if (__ENABLE_LOG__) {
            file_put_contents(self::get_log_file(),$full_msg,FILE_APPEND | LOCK_EX);
        }
        if(!$quiet) echo $full_msg;
    }

	// Makes sure the log file exists and return its location
    public static function get_log_file() {
        if (!file_exists(__LOG_FILE__)) {
            touch(__LOG_FILE__);
        }
        return __LOG_FILE__;
    }
}

?>
