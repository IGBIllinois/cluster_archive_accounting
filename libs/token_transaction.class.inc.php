<?php
	// Represents the database entry for a single transaction, either a cost incurred or an amount paid
	class token_transaction {
		private $db;
		private $id;
		private $directory_id;
		private $transaction_amount;
		private $usage_id;
		private $transaction_time;
		private $cost_of_tokens;
		
		public function __construct($db,$id=0){
			$this->db = $db;
			if($id != 0){
				$this->get_transaction($id);
			}
		}
		public function __destruct(){}
		
		// Inserts a transaction with the given values into the database, then loads that transaction into this object
		public function create($directory_id,$amount,$usage_id,$date=null,$cost=null){
			if($date == null){
				$sql = "insert into token_transactions (directory_id,transaction_amount,usage_id,transaction_time,cost_of_tokens) values (:dirid,:amount,:usageid,NOW(),:cost)";
				$args = array(':dirid'=>$directory_id,':amount'=>$amount,':usageid'=>$usage_id,':cost'=>$cost);
			} else {
				$sql = "insert into token_transactions (directory_id,transaction_amount,usage_id,transaction_time,cost_of_tokens) values (:dirid,:amount,:usageid,:time,:cost)";
				$args = array(':dirid'=>$directory_id,':amount'=>$amount,':usageid'=>$usage_id,':time'=>$date,':cost'=>$cost);
			}
			$id = $this->db->insert_query($sql,$args);
			$this->get_transaction($id);
		}
		public function update($amount){
			$sql = "update token_transactions set transaction_amount=:amount,transaction_time=NOW() where id=:id";
			$args = array(':amount'=>$amount,':id'=>$this->id);
			
			$this->db->non_select_query($sql,$args);
		}
		public function delete(){
			$sql = "delete from token_transactions where id=:id limit 1";
			$args = array(':id'=>$this->id);
			$this->db->non_select_query($sql,$args);
		}

		public static function tokenBalance($db,$directory_id,$time=null){
			$sql = "select sum(transaction_amount) as sum from token_transactions where directory_id=:directoryid";
			$args = array(':directoryid'=>$directory_id);
			if($time != null){
				$sql .= " and transaction_time<:time";
				$args[':time'] = $time;
			}
			$results = $db->query($sql,$args);
			if($results[0]['sum'] == null){
				return 0;
			}
			return $results[0]['sum'];
		}
		public static function emptyTransaction($db){
			$transaction = new transaction($db);
			$transaction->id = 0;
			$transaction->directory_id = 0;
			$transaction->balance = 0;
			$transaction->amount = 0;
			$transaction->usage_id = 0;
			$transaction->transaction_time = 0;
			return $transaction;
		}
		
		public function get_id(){
			return $this->id;
		}
		public function get_directory_id(){
			return $this->directory_id;
		}
		public function get_amount(){
			return $this->amount;
		}
		public function get_transaction_time(){
			return $this->transaction_time;
		}
		
		// Loads the transaction with the given id into this object
		public function get_transaction($id){
			$sql = "select t.* from token_transactions t where id=:id limit 1";
			$args = array(':id'=>$id);
			$transaction = $this->db->query($sql,$args);
			$this->id = $transaction[0]['id'];
			$this->directory_id = $transaction[0]['directory_id'];
			$this->transaction_amount = $transaction[0]['transaction_amount'];
			$this->usage_id = $transaction[0]['usage_id'];
			$this->transaction_time = $transaction[0]['transaction_time'];
			$this->cost_of_tokens = $transaction[0]['cost_of_tokens'];
		}
		public function get_transaction_with_usage_id($usage_id){
			$sql = "select t.id from token_transactions t where usage_id=:usageid limit 1";
			$args = array(':usageid'=>$usage_id);
			$transaction = $this->db->query($sql,$args);
			if(count($transaction)==1){
				$this->get_transaction($transaction[0]['id']);
			}
		}
	}