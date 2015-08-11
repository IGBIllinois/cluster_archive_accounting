<?php
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
		
		public function create($account_id,$usage,$num_small_files){
			// If a usage tally was already created this month, remove it before adding the new one
			$sql = "delete from archive_usage where month(usage_time)=month(NOW()) and year(usage_time)=year(NOW()) and account_id=:accountid";
			$args = array(':accountid'=>$account_id);
			$existing = $this->db->non_select_query($sql,$args);
			
			// Calculate cost
			$cost = dataCost($usage);
			
			// Subtract amount already paid for
			$latestUsage = data_usage::latestUsage($db);
			$paid = dataCost($latestUsage->directory_size);
			$cost = max(0,$cost-$paid);
			
			// Save info to database
			$sql = "insert into `archive_usage` (`accountid`,`directory_size`,`num_small_files`,`datetime`,`cost`) values (:accountid,:usage,:smallfiles,NOW(),:cost)";
			$args = array(':accountid'=>$account_id,':usage'=>$usage,':smallfiles'=>$num_small_files,':cost'=>$cost);
			$obj->data_usage_id = $db->insert_query($sql,$args);
			$obj->get_data_usage($obj->data_usage_id);
			
			// Add transaction
			transaction::create($account_id,$cost,$obj->data_usage_id);
			
			return $obj;
		}
		
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
		
		public function get_directory_size(){
			return $this->directory_size;
		}
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
	
	}
	