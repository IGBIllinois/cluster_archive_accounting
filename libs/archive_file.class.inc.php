<?php
	class archive_file {
		private $db;
		private $id;
		private $filename;
		private $filesize;
		private $usage_id;
		private $file_time;
		
		public function __construct($db){
			$this->db = $db;
		}
		
		public function __destruct(){}
		
		public function create($filename,$filesize,$usage_id,$file_time){
			$sql = "insert into archive_files (filename,filesize,usage_id,file_time) values (:filename,:filesize,:usageid,:filetime)";
			$args = array(':filename'=>$filename,':filesize'=>$filesize,':usageid'=>$usage_id,':filetime'=>$file_time);
			$this->id = $this->db->insert_query($sql,$args);
			
			$this->load_by_id($this->id);
		}
		
		public function load_by_id($id){
			$sql = "select * from archive_files where id=:id";
			$args = array(':id'=>$id);
			$result = $this->db->query($sql,$args);
			
			$this->id =			$result[0]['id'];
			$this->filename =	$result[0]['filename'];
			$this->filesize =	$result[0]['filesize'];
			$this->usage_id =	$result[0]['usage_id'];
			$this->file_time =	$result[0]['file_time'];
		}
		
		public static function get_file_list($db,$month,$year,$user_id){
			$sql = "select f.* from archive_files f left join archive_usage u on u.id=f.usage_id where month(u.usage_time)=:month and year(u.usage_time)=:year and u.account_id = :id";
			$args = array(':month'=>$month,':year'=>$year,':id'=>$user_id);
			$result = $db->query($sql,$args);
			return $result;
		}
	}