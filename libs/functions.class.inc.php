<?php

class functions {

	public static function get_pretty_date($date) {
		return substr($date,0,4) . "/" . substr($date,4,2) . "/" . substr($date,6,2);

	}


	public static function output_message($messages) {
		$output = "";
		foreach ($messages as $message) {
			if ($message['RESULT']) {
				$output .= "<div class='alert alert-success'>" . $message['MESSAGE'] . "</div>";
			}
			else {
				$output .= "<div class='alert alert-error'>" . $message['MESSAGE'] . "</div>";
			}
		}
		return $output;

	}

	public static function log_message($message) {
                $current_time = date('Y-m-d H:i:s');
                $full_msg = $current_time . ": " . $message . "\n";
                if (self::log_enabled()) {
                        file_put_contents(self::get_log_file(),$full_msg,FILE_APPEND | LOCK_EX);
                }
                echo $full_msg;

        }

        public static function get_log_file() {
                if (!file_exists(__LOG_FILE__)) {
                        touch(__LOG_FILE__);
                }
                return __LOG_FILE__;

        }

        public static function log_enabled() {
                return __ENABLE_LOG__;
        }

}

?>
