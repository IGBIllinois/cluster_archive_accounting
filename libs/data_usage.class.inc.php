<?php
	// Represents the entry in the database corresponding to a monthly data usage scan for a user.
	class data_usage {
		private $db;
		private $id;
		private $directory_id;
		private $time_created;
		private $directory_size; // In MB
		private $num_small_files; // < 1GB
		private $cost;
		private $pending;
		
		public function __construct($db,$id = 0) {
			$this->db = $db;
			if($id!=0){
				$this->load_by_id($id);
			}
		}
		public function __destruct(){}
		
		// Inserts an entry with the given data into the database, and loads that entry into this object.
		public function create($directory_id,$usage,$num_small_files){
			// TODO the comments in this function don't make any sense
			// If a usage tally was already created this month, remove it before adding the new one
			//  The associated transaction and file entries will be removed automatically.
			$sql = "select id,cost from archive_usage where month(usage_time)=month(NOW()) and year(usage_time)=year(NOW()) and directory_id=:dirid";
			$args = array(':dirid'=>$directory_id);
			$existing = $this->db->query($sql,$args);
			
			// Calculate cost
			$cost = self::dataCost($this->db,$usage,$directory_id);
			
			// Subtract amount already paid for
			$prevmonth = date('n')-1;
			$prevyear = date('Y');
			if($prevmonth==0){
				$prevmonth = 12;
				$prevyear-=1;
			}
			$latestUsage = data_usage::usage_from_month($this->db,$directory_id,$prevmonth,$prevyear);
			$paid = self::dataCost($this->db,$latestUsage->directory_size,$directory_id);
			$cost = max(0,$cost-$paid);
			
			if(count($existing)>0){
				// Update existing usage
				$sql = "update `archive_usage` set directory_size=:usage,num_small_files=:smallfiles,usage_time=NOW(),cost=:cost,billed_cost=:cost where id=:usageid";
				$args = array(':usage'=>$usage,':smallfiles'=>$num_small_files,':cost'=>$cost,':usageid'=>$existing[0]['id']);
				$this->db->non_select_query($sql,$args);
				$this->load_by_id($existing[0]['id']);
				
				if($existing[0]['cost']>0){
					// A transaction exists
					$trans = new transaction($this->db);
					$trans->get_transaction_with_usage_id($this->id);
					if($cost>0){
						// Update it
						$trans->update(-1*$cost);
					} else {
						// Delete old transaction
						$trans->delete();
					}
				} else {
					// Create a new transaction, if necessary
					if($cost>0){
						$trans = new transaction($this->db);
						$trans->create($directory_id,-1*$cost,$this->id);
					}
				}
				$trans = new transaction($this->db);
			} else {
				// Create new usage
				// Save info to database
				$sql = "insert into `archive_usage` (`directory_id`,`directory_size`,`num_small_files`,`usage_time`,`cost`,`billed_cost`,`pending`) values (:dirid,:usage,:smallfiles,NOW(),:cost,:cost,1)";
				$args = array(':dirid'=>$directory_id,':usage'=>$usage,':smallfiles'=>$num_small_files,':cost'=>$cost);
				$this->id = $this->db->insert_query($sql,$args);
				$this->load_by_id($this->id);
				
				// Add transaction if necessary
				if($cost>0){
					$trans = new transaction($this->db);
					$trans->create($directory_id,-1*$cost,$this->id);
				}
			}
			
			return $this;
		}
		
		// Calculates the cost of a given directory size, based on the current settings and the previous month's usage data. TODO no it doesn't
		private static function dataCost($db,$usage,$directory_id){
			$sql = "select do_not_bill from directories where id=:id";
			$args = array(':id'=>$directory_id);
			$result = $db->query($sql,$args);
			if($result[0]['do_not_bill'] == 0){
				$settings = new settings($db);
				if($usage < intval($settings->get_setting("min_billable_data"))){
					$cost = 0;
				} else {
					$cost = intval($settings->get_setting("data_cost")) * ceil($usage / 1048576.0);
				}
				return $cost;
			} else {
				return 0;
			}
		}
		
		// Returns a data_usage object representing the latest usage scan for the given user.
		public static function latestUsage($db,$directory_id){
			$sql = "select id from archive_usage where directory_id=:dirid order by usage_time desc limit 1";
			$args = array(':dirid'=>$directory_id);
			$usage = $db->query($sql,$args);
			if(count($usage)>0){
				return new data_usage($db,$usage[0]['id']);
			} else {
				return self::emptyUsage($db);
			}
		}
		public static function usage_from_month($db,$directory_id,$month,$year){
			$sql = "select id from archive_usage where month(usage_time)=:month and year(usage_time)=:year and directory_id=:dirid limit 1";
			$args = array(':month'=>$month,':year'=>$year,':dirid'=>$directory_id);
			$usage = $db->query($sql,$args);
			if(count($usage)>0){
				return new data_usage($db,$usage[0]['id']);
			} else {
				return self::emptyUsage($db);
			}
		}
		
		// Makes an empty usage object
		public static function emptyUsage($db){
			$usage = new data_usage($db);
			$usage->id = 0;
			$usage->time_created = 0;
			$usage->directory_size = 0;
			$usage->num_small_files = 0;
			$usage->cost = 0;
			return $usage;
		}
		
		// Loads the data usage entry with the given id into this object
		public function load_by_id($id){
			$sql = "select * from archive_usage where id=:id";
			$args = array(':id'=>$id);
			$data = $this->db->query($sql,$args);
			$this->id =					$data[0]['id'];
			$this->directory_id =		$data[0]['directory_id'];
			$this->time_created =		$data[0]['usage_time'];
			$this->directory_size =		$data[0]['directory_size'];
			$this->num_small_files =	$data[0]['num_small_files'];
			$this->cost =				$data[0]['cost'];
			$this->pending =			$data[0]['pending'];
		}
		
		public function get_id(){
			return $this->id;
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
		public function get_pending(){
			return $this->pending;
		}
		public function get_directory_id(){
			return $this->directory_id;
		}
		
		public function set_pending($pending){
			if($this->pending != $pending){
				$sql = "update archive_usage set pending=:pending where id=:id";
				$args = array(':pending'=>$pending,':id'=>$this->id);
				$this->db->non_select_query($sql,$args);
				$this->pending = $pending;
			}
		}
	
	}
	