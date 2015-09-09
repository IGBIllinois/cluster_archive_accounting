<?php
	class archive_directory {
		private $db;
		private $id;
		private $user_id;
		private $directory;
		private $time_created;
		private $cfop;
		private $enabled;
		private $do_not_bill;
		
		public function __construct($db){
			$this->db = $db;
		}
		public function __destruct(){}
		
		public function create($user_id,$directory,$cfop,$dnb){
			// TODO add a bit of error-checking
			if(self::is_disabled($this->db,$directory)){
				// Re-enable if disabled.
				$this->get_by_directory($directory);
				$this->enable();
				$this->set_user_id($user_id);
				$this->set_cfop($cfop);
				$this->set_do_not_bill($dnb);
			} else {
				// Create if does not exist				
				$sql = "insert into directories (user_id,directory,time_created,cfop,is_enabled,do_not_bill) values (:userid,:directory,NOW(),:cfop,1,:dnb)";
				$args = array(':userid'=>$user_id,':directory'=>$directory,':cfop'=>$cfop,':dnb'=>$dnb);
				$this->id = $this->db->insert_query($sql,$args);
				$this->load_by_id($this->id);
			}
		}
		public function load_by_id($id){
			$sql = "select * from directories where id = :id";
			$args = array(':id'=>$id);
			$results = $this->db->query($sql,$args);
			$this->id =				$results[0]['id'];
			$this->user_id =		$results[0]['user_id'];
			$this->directory =		$results[0]['directory'];
			$this->time_created =	$results[0]['time_created'];
			$this->cfop =			$results[0]['cfop'];
			$this->enabled =		$results[0]['is_enabled'];
			$this->do_not_bill =	$results[0]['do_not_bill'];
		}
		public function get_id(){
			return $this->id;
		}
		public function get_user_id(){
			return $this->user_id;
		}
		public function get_directory(){
			return $this->directory;
		}
		public function get_cfop() {
			return $this->cfop;
		}
		public function get_cfop_college(){
			return substr($this->get_cfop(),0,1);
		}
		public function get_cfop_fund(){
			return substr($this->get_cfop(),2,6);
		}
		public function get_cfop_organization(){
			return substr($this->get_cfop(),9,6);
		}
		public function get_cfop_program(){
			return substr($this->get_cfop(),16,6);
		}
		public function get_do_not_bill() {
			return $this->do_not_bill;
		}
		
		public static function is_disabled($db,$directory){
			$sql = "select count(id) as count from directories left join users on users.id=directories.user_id where directories.directory=:directory and (directories.is_enabled = 0 or users.is_enabled = 0)";
			$args = array(':directory'=>$directory);
			$result = $db->query($sql,$args);
			if($result[0]['count']>0){
				return true;
			} else {
				return false;
			}
		}
		
		public function get_by_directory($directory){
			$sql = "select id from directories where directory=:directory";
			$args = array(':directory'=>$directory);
			$id = $this->db->query($sql,$args);
			if(count($id)>0);
			$this->load_by_id($id[0]['id']);
		}
		
		public function enable(){
			$sql = "update directories set is_enabled=1 where id=:id";
			$args = array(':id'=>$this->id);
			$result = $this->db->non_select_query($sql,$args);
			if($result){
				$this->enabled = true;
			}
			return $result;
		}
		
		public function disable(){
			$sql = "update directories set is_enabled=0 where id=:id limit 1";
			$args = array(':id'=>$this->id);
			$result = $this->db->non_select_query($sql,$args);
			if($result){
				$this->enabled = false;
			}
			return $result;
		}
		
		public function set_user_id($user_id){
			$sql = "update directories set user_id=:userid where id=:id limit 1";
			$args = array(':userid'=>$user_id,':id'=>$this->id);
			$result = $this->db->non_select_query($sql,$args);
			if($result){
				$this->user_id = $user_id;
			}
			return $result;
		}
		
		public function set_cfop($cfop){
			$sql = "update directories set cfop=:cfop where id=:id limit 1";
			$args = array(':cfop'=>$cfop,':id'=>$this->id);
			$result = $this->db->non_select_query($sql,$args);
			if($result){
				$this->cfop = $cfop;
			}
			return $result;
		}
		public function set_directory($directory){
			$sql = "update directories set directory=:directory where id=:id limit 1";
			$args = array(':directory'=>$directory,':id'=>$this->id);
			$result = $this->db->non_select_query($sql,$args);
			if($result){
				$this->directory = $directory;
			}
			return $result;
		}
		public function set_do_not_bill($dnb){
			$sql = "update directories set do_not_bill=:dnb where id=:id limit 1";
			$args = array(':dnb'=>$dnb,':id'=>$this->id);
			$result = $this->db->non_select_query($sql,$args);
			if($result){
				$this->do_not_bill = $dnb;
			}
			return $result;
		}
	}