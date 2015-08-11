<?php

class settings {

	private $db;
	private $settings;
	
	public function __construct($db){
		$this->db = $db;
		$this->settings = array();
		$settings = $db->query("select * from settings");
		foreach ($settings as $key=>$setting){
			$this->settings[$setting['key']] = $setting['value'];
		}
	}
	
	public function get_setting($key){
		if(isset($this->settings[$key])){
			return $this->settings[$key];
		}
		return "";
	}

	public static function get_server_name() {
        $server_name = substr($_SERVER['SERVER_NAME'],0,strpos($_SERVER['SERVER_NAME'],"."));
        return $server_name;
	}
	
}

?>
