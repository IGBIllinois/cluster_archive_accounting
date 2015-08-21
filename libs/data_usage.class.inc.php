<?php
	// Represents the entry in the database corresponding to a monthly data usage scan for a user.
	class data_usage {
		private $db;
		private $data_usage_id;
		private $time_created;
		private $directory_size; // In MB
		private $num_small_files; // < 1GB
		private $cost;
		
		public function __construct($db,$id = 0) {
			$this->db = $db;
			if($id!=0){
				$this->get_data_usage($id);
			}
		}
		public function __destruct(){}
		
		// Inserts an entry with the given data into the database, and loads that entry into this object.
		public function create($account_id,$usage,$num_small_files){
			// If a usage tally was already created this month, remove it before adding the new one
			//  The associated transaction and file entries will be removed automatically.
			$sql = "delete from archive_usage where month(usage_time)=month(NOW()) and year(usage_time)=year(NOW()) and account_id=:accountid";
			$args = array(':accountid'=>$account_id);
			$existing = $this->db->non_select_query($sql,$args);
			
			// Calculate cost
			$cost = self::dataCost($this->db,$usage);
			
			// Subtract amount already paid for
			$latestUsage = data_usage::latestUsage($this->db,$account_id);
			$paid = $this->dataCost($this->db,$latestUsage->directory_size);
			$cost = max(0,$cost-$paid);
			
			// Save info to database
			$sql = "insert into `archive_usage` (`account_id`,`directory_size`,`num_small_files`,`usage_time`,`cost`) values (:accountid,:usage,:smallfiles,NOW(),:cost)";
			$args = array(':accountid'=>$account_id,':usage'=>$usage,':smallfiles'=>$num_small_files,':cost'=>$cost);
			$this->data_usage_id = $this->db->insert_query($sql,$args);
			$this->get_data_usage($this->data_usage_id);
			
			// Add transaction if necessary
			if($cost>0){
				$trans = new transaction($this->db);
				$trans->create($account_id,$cost,$this->data_usage_id);
			}
			
			return $this;
		}
		
		// Calculates the cost of a given directory size, based on the current settings and the previous month's usage data.
		private static function dataCost($db,$usage){
			$settings = new settings($db);
			if($usage < intval($settings->get_setting("min_billable_data"))){
				$cost = 0;
			} else {
				$cost = intval($settings->get_setting("data_cost")) * ceil($usage / 1048576.0);
			}
			return $cost;
		}
		
		// Returns a data_usage object representing the latest usage scan for the given user.
		public static function latestUsage($db,$account_id){
			$sql = "select id from archive_usage where account_id=:accountid order by usage_time desc limit 1";
			$args = array(':accountid'=>$account_id);
			$usage = $db->query($sql,$args);
			if(count($usage)>0){
				return new data_usage($db,$usage[0]['id']);
			} else {
				return null;
			}
		}
		
		// Loads the data usage entry with the given id into this object
		public function get_data_usage($id){
			$sql = "select * from archive_usage where id=:id";
			$args = array(':id'=>$id);
			$data = $this->db->query($sql,$args);
			$this->data_usage_id = $data[0]['id'];
			$this->time_created = $data[0]['usage_time'];
			$this->directory_size = $data[0]['directory_size'];
			$this->num_small_files = $data[0]['num_small_files'];
			$this->cost = $data[0]['cost'];
		}
		
		public function get_id(){
			return $this->data_usage_id;
		}
		public function get_cost(){
			return $this->cost;
		}
		public function get_smallfiles(){
			return $this->num_small_files;
		}
		public function get_directory_size(){
			return $this->directory_size;
		}
	
	}
	