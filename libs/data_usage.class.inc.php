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
		private $token_transaction_id;
		
		public function __construct($db,$id = 0) {
			$this->db = $db;
			if($id!=0){
				$this->load_by_id($id);
			}
		}
		public function __destruct(){}
		
		// Inserts an entry with the given data into the database, and loads that entry into this object.
		public function create($directory_id,$usage,$num_small_files){
			$settings = new settings($this->db);
			// TODO the comments in this function don't make any sense
			// If a usage tally was already created this month, remove it before adding the new one
			//  The associated transaction and file entries will be removed automatically.
			$sql = "select id,cost,token_transaction_id from archive_usage where month(usage_time)=month(NOW()) and year(usage_time)=year(NOW()) and directory_id=:dirid";
			$args = array(':dirid'=>$directory_id);
			$existing = $this->db->query($sql,$args);
			
			// Calculate cost
			// Get last month's usage bracket
			$prevmonth = date('n')-1;
			$prevyear = date('Y');
			if($prevmonth==0){
				$prevmonth = 12;
				$prevyear-=1;
			}
			$latestUsage = self::usage_from_month($this->db,$directory_id,$prevmonth,$prevyear);
			$latestBracket = self::terabyteBracket($this->db,$latestUsage->directory_size);
			// Get this month's usage bracket
			$currentBracket = self::terabyteBracket($this->db,$usage);
			
			$bracketChange = max(0,$currentBracket - $latestBracket);
			$cost = $bracketChange * $settings->get_setting("data_cost"); // This is the theoretical cost of the month.
			$billed_cost = $cost;

			// Check if do not bill is enabled
			$directory = new archive_directory($this->db,$directory_id);
			if($directory->get_do_not_bill()){
				$billed_cost = 0;
			}

			// Use tokens, if applicable
			$tokensUsed = 0;
			if($billed_cost > 0){
				// Check if the directory has any tokens to spend
				$prevmonth = new DateTime("$prevmonth/01/$prevyear");
				$prevmonth->add(new DateInterval("P1M"));
				$prevmonth->sub(new DateInterval("PT1S"));
				$tokenbalance = token_transaction::tokenBalance($this->db,$directory_id,$prevmonth->format("Y-m-d H:i:s"));
				if($tokenbalance > 0){
					// Use as many tokens as we need
					$tokensUsed = $bracketChange;
					if($tokenbalance < $bracketChange){
						// If we don't have enough tokens, use all of the ones we have.
						$tokensUsed = $tokenbalance;
					}
				}
				// Update billed cost to reflect tokens used
				$billed_cost -= $tokensUsed * $settings->get_setting("data_cost");
			}
			echo "\nPrevious Bracket: ".$latestBracket."TB; Current Bracket: ".$currentBracket."TB";
			echo "\nCost: $cost; Billed Cost: $billed_cost; Tokens Used: $tokensUsed\n";			

			if(count($existing)>0){
				// Update existing usage
				$sql = "update `archive_usage` set directory_size=:usage,num_small_files=:smallfiles,usage_time=NOW(),cost=:cost,billed_cost=:billedcost where id=:usageid";
				$args = array(':usage'=>$usage,':smallfiles'=>$num_small_files,':cost'=>$cost,':billedcost'=>$billed_cost,':usageid'=>$existing[0]['id']);
				$this->db->non_select_query($sql,$args);
				$this->load_by_id($existing[0]['id']);
				
				if($existing[0]['token_transaction_id']!=null){
					// A token transaction exists
					$trans = new token_transaction($this->db,$existing[0]['token_transaction_id']);
					// Update it
					$trans->update(-1*$tokensUsed);
				} else {
					// Create a new transaction, if necessary
					if($tokensUsed>0){
						$trans = new token_transaction($this->db);
						$trans->create($directory_id,-1*$tokensUsed,$this->id);
						$this->set_token_transaction_id($trans->get_id());
					}
				}
			} else {
				// Create new usage
				// Save info to database
				$sql = "insert into `archive_usage` (`directory_id`,`directory_size`,`num_small_files`,`usage_time`,`cost`,`billed_cost`,`pending`) values (:dirid,:usage,:smallfiles,NOW(),:cost,:billedcost,1)";
				$args = array(':dirid'=>$directory_id,':usage'=>$usage,':smallfiles'=>$num_small_files,':cost'=>$cost,':billedcost'=>$billed_cost);
				$this->id = $this->db->insert_query($sql,$args);
				$this->load_by_id($this->id);
				
				// Add transaction if necessary
				if($tokensUsed>0){
					$trans = new token_transaction($this->db);
					$trans->create($directory_id,-1*$tokensUsed,$this->id);
					$this->set_token_transaction_id($trans->get_id());
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
		
		public static function terabyteBracket($db,$usage){
			$settings = new settings($db);
			if($usage < intval($settings->get_setting("min_billable_data"))){
				$bracket = 0;
			} else {
				$bracket = ceil($usage / 1048576.0);
			}
			return $bracket;
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
		// Looks for a usage from this month or earlier, in case a month was skipped
		public static function usage_from_month($db,$directory_id,$month,$year){
			$prevmonth = new DateTime("$month/01/$year");
			$prevmonth->add(new DateInterval("P1M"));
			$prevmonth->sub(new DateInterval("PT1S"));
			
			$sql = "select id from archive_usage where usage_time<=:time and directory_id=:dirid order by usage_time desc limit 1";
			$args = array(':time'=>$prevmonth->format("Y-m-d H:i:s"),':dirid'=>$directory_id);
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
			$this->token_transaction_id = $data[0]['token_transaction_id'];
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
		
		public function set_token_transaction_id($id){
			if($this->token_transaction_id != $id){
				$sql = "update archive_usage set token_transaction_id=:tid where id=:id";
				$args = array(":id"=>$this->id,":tid"=>$id);
				$this->db->non_select_query($sql,$args);
				$this->token_transaction_id = $id;
			}
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
	