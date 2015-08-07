<?php
	class transaction {
		private $db;
		private $transaction_id;
		private $account_id;
		private $balance;
		private $amount;
		private $usage_id;
		private $transaction_time;
		
		public function __construct($db,$id=0){
			$this->db = $db;
			if($id != 0){
				$this->get_transaction($id);
			}
		}
		public function __destruct(){}
		
		public function create($account_id,$amount,$usage_id){
			$sql = "insert into transactions (account_id,amount,usage_id,transaction_time) values (:accountid,:amount,:usageid,NOW())";
			$args = array(':accountid'=>$account_id,':amount'=>$amount,':usageid'=>$usage_id);
			$id = $this->db->insert_query($sql,$args);
			$this->get_transaction($id);
		}
		
		public static function latestTransaction($db,$account_id){
			$sql = "select id from transactions where account_id=:accountid order by transaction_time desc limit 1";
			$args = array(":accountid"=>$account_id);
			$transaction = $db->query($sql,$args);
			if(count($transaction)>0){
				return new transaction($db,$transaction[0]['id']);
			} else {
				return NULL;
			}
		}
		
		public function get_balance(){
			return $this->balance;
		}
		
		public function get_transaction($id){
			$sql = "select t.*, (select sum(t1.amount) from transactions t1 where t1.transaction_time<=t.transaction_time and t1.account_id=t.account_id) as balance from transactions t where id=:id limit 1";
			$args = array(':id'=>$id);
			$transaction = $this->db->query($sql,$args);
			$this->transaction_id = $transaction[0]['id'];
			$this->account_id = $transaction[0]['account_id'];
			$this->balance = $transaction[0]['balance'];
			$this->amount = $transaction[0]['amount'];
			$this->usage_id = $transaction[0]['usage_id'];
			$this->transaction_time = $transaction[0]['transaction_time'];
		}
	}