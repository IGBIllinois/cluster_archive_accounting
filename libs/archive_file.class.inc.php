<?php
	function endsWith($haystack, $needle) {
	    // search forward starting from end minus needle length characters
	    return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}
	// Represents the database entry for a file in a user's archive, as scanned at the given time.
	class archive_file {
		private $db;
		private $filename;
		private $filesize;
		private $usage_id;
		private $file_time;
		
		public function __construct($db){
			$this->db = $db;
		}
		
		public function __destruct(){}
		
		// Inserts a file entry into the database with the given values, then loads those values into this object
		public function create($filename,$filesize,$usage_id,$file_time){
			$sql = "insert into archive_files (filename,filesize,usage_id,file_time) values (:filename,:filesize,:usageid,:filetime)";
			$args = array(':filename'=>$filename,':filesize'=>$filesize,':usageid'=>$usage_id,':filetime'=>$file_time);
			$this->db->insert_query($sql,$args);
			
			$this->load($filename,$usage_id);
		}
		
		// Load file info from a given id from the database into this object
		public function load($filename,$usage_id){
			$sql = "select * from archive_files where filename=:filename and usage_id=:usageid";
			$args = array(':filename'=>$filename,':usageid'=>$usage_id);
			$result = $this->db->query($sql,$args);
			
			$this->filename =	$result[0]['filename'];
			$this->filesize =	$result[0]['filesize'];
			$this->usage_id =	$result[0]['usage_id'];
			$this->file_time =	$result[0]['file_time'];
		}
		
		public function get_smallfile(){
			return self::isSmall($this->db,$this->filename,$this->filesize);
		}
		
		// Returns a list of all file info for a given month and user
		public static function get_file_list($db,$month,$year,$directory_id){
			$sql = "select f.* from archive_files f left join archive_usage u on u.id=f.usage_id where month(u.usage_time)=:month and year(u.usage_time)=:year and u.directory_id = :id";
			$args = array(':month'=>$month,':year'=>$year,':id'=>$directory_id);
			$result = $db->query($sql,$args);
			
			for($i=0;$i<count($result);$i++){
				$result[$i]['smallfile'] = self::isSmall($db,$result[$i]['filename'],$result[$i]['filesize']);
			}
			
			return $result;
		}
		
		public static function isSmall($db,$filename,$filesize){
			$settings = new settings($db);
			$isSmall = true;
			$isSmall &= $filesize<$settings->get_setting('small_file_size');
			$isSmall &= !endsWith($filename,".md5");
			$isSmall &= !endsWith($filename,".txt");
			return $isSmall;
		}
	}