<?php
class user {

	////////////////Private Variables//////////

	private $db; //mysql database object
	private $id;
	private $user_name;
	private $full_name;
	private $archive_directory;
	private $enabled;
	private $time_created;
	private $ldap;
	private $cfop;
	private $email;
	private $admin;
	////////////////Public Functions///////////

	public function __construct($db,$ldap,$id = 0,$username = "") {
		$this->db = $db;
		$this->ldap = $ldap;
		if ($id != 0) {
			$this->load_by_id($id);
		}
		elseif (($id == 0) && ($username != "")) {
			$this->load_by_username($username);
			$this->user_name = $username;
		}
	}
	public function __destruct() {
	}
	
	// Inserts a user into the database with the given values, then loads that user into this object. Displays errors if there are any.
	public function create($username,$admin,$archive_dir,$cfop) {
		$username = trim(rtrim($username));
		

		$error = false;
		//Verify Username
		if ($username == "") {
			$error = true;
			$message = "<div class='alert'>Please enter a username.</div>";
		}
		elseif ($this->get_user_exist($username)) {
			$error = true;
			$message .= "<div class='alert'>User already exists in database.</div>";
		}
		elseif (!$this->ldap->is_ldap_user($username)) {
			$error = true;
			$message = "<div class='alert'>User does not exist in LDAP database.</div>";
		}
		
		// Check if archive dir is already there
		if ($this->data_dir_exists($archive_dir)) {
			$error = true;
			$message .= "<div class='alert'>Directory " . $archive_dir . " is already in the database</div>";
		}

		//If Errors, return with error messages
		if ($error) {
			return array('RESULT'=>false,
					'MESSAGE'=>$message);
		}

		//Everything looks good, add user and default user project
		else {
		
			if ($this->is_disabled($username)) {
				$this->load_by_username($username);
				$this->enable();		
			}
			else {
				$full_name = $this->ldap->get_ldap_full_name($username);
				$sql = "insert into accounts (`username`,`name`,`is_admin`,`is_enabled`,`archive_directory`,`cfop`,`time_created`) values (:username,:fullname,:admin,1,:archivedir,:cfop,NOW())";
				$args = array(':username'=>$username,':fullname'=>$full_name,':admin'=>$admin,':archivedir'=>$archive_dir,':cfop'=>$cfop);
				$user_id = $this->db->insert_query($sql,$args);
				$this->load_by_id($user_id);
			}
			return array('RESULT'=>true,
					'MESSAGE'=>'User successfully added.',
					'user_id'=>$user_id);
		}

	}
	public function get_user_id() {
		return $this->id;
	}
	public function get_username() {
		return $this->user_name;
	}
	public function get_email() {
		return $this->email;
	}
	public function get_full_name() {
		return $this->full_name;
	}
	public function get_archive_directory() {
		return $this->archive_directory;
	}
	public function get_enabled() {
		return $this->enabled;
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
	public function get_time_created() {
		return $this->time_created;
	}
	// Gets a summary of data usage for this user for the given month
	public function get_data_summary($month,$year) {
		$prevmonth = $month-1;
		$prevyear = $year;
		if($prevmonth==0){
			$prevyear = $prevyear - 1;
			$prevmonth = 12;
		}
		$sql = "SELECT account_id, ROUND(directory_size/1048576,4) as terabytes, num_small_files, usage_time, sum(cost) as cost, (select ROUND(directory_size/1048576,4) from archive_usage where account_id=:id and year(`usage_time`)=:prevyear and month(`usage_time`)=:prevmonth order by usage_time limit 1) as prevusage ";
		$sql .= "FROM archive_usage ";
		$sql .= "WHERE account_id=:id ";
		$sql .= "AND YEAR(`usage_time`)=:year ";
        $sql .= "AND MONTH(`usage_time`)=:month ";
        $sql .= "order by usage_time";
        $args = array(':id'=>$this->get_user_id(),':year'=>$year,':month'=>$month,':prevyear'=>$prevyear,':prevmonth'=>$prevmonth);
		return $this->db->query($sql,$args);
		
	}
	
	// Checks to see if the given directory is already associated with any user
	private function data_dir_exists($directory) {
		$sql = "SELECT count(1) as count FROM accounts ";
		$sql .= "WHERE archive_directory LIKE :dir ";
		$sql .= "AND is_enabled='1'";
		$args = array(':dir'=>$directory.'%');
		$result = $this->db->query($sql,$args);

		if ($result[0]['count']) {
			return true;
		}
		else { return false;
		}
	}
	
	// Enables this user
	public function enable() {
		$sql = "UPDATE accounts SET is_enabled='1' WHERE id=:id LIMIT 1";
		$args = array(':id'=>$this->get_user_id());
		$this->db->non_select_query($sql,$args);
		$this->enabled = true;
		return true;
	}
	// Disables this user
	public function disable() {
		$message;
		$error = false;
		
		$sql = "UPDATE users SET user_enabled='0' WHERE user_id=:id LIMIT 1";
		$args = array(':id'=>$this->get_user_id());
		$this->enabled = false;
		$this->db->non_select_query($sql,$args);
		$this->default_project()->disable();
		$this->default_data_dir()->disable();
		
		$message = "User successfully deleted";
		return array('RESULT'=>true,'MESSAGE'=>$message);

	}

	public function has_directory(){
		return ($this->archive_directory != NULL);
	}

	public function is_admin() {
		return $this->admin;
	}

	public function is_user() {
		return !$this->admin;
    }
	
	// Makes the user an admin (or not)
	public function set_admin($admin) {
		$sql = "UPDATE accounts SET is_admin=:admin ";
		$sql .= "WHERE id=:id LIMIT 1";
		$args = array(':admin'=>$admin,':id'=>$this->get_user_id());
		$result = $this->db->non_select_query($sql,$args);
		if ($result) {
			$this->admin = $admin;
		}
		return $result;
	}
	public function set_archive_directory($archive_dir){
		$sql = "update accounts set archive_directory=:archive_dir where id=:id limit 1";
		$args = array(':archive_dir'=>$archive_dir,':id'=>$this->get_user_id());
		$result = $this->db->non_select_query($sql,$args);
		if($result){
			$this->archive_directory = $archive_dir;
		}
		return $result;
	}
	public function set_cfop($cfop){
		$sql = "update accounts set cfop=:cfop where id=:id limit 1";
		$args = array(':cfop'=>$cfop,':id'=>$this->get_user_id());
		$result = $this->db->non_select_query($sql,$args);
		if($result){
			$this->cfop = $cfop;
		}
		return $result;
	}

	public function authenticate($password) {
	$result = false;
        $rdn = $this->get_user_rdn();
        if (($this->ldap->bind($rdn,$password)) && ($this->get_user_exist($this->user_name))) {
            $result = true;
        }
        return $result;
    }

	//permission()
    //$user_id - id of user to see if you have permissions to view his details
    //returns true if you do have permissions, false otherwise
    public function permission($user_id) {
        if ($this->is_admin()) {
            return TRUE;
        }
        elseif ($this->get_user_id() == $user_id) {
            return TRUE;
        }
        else {
            return FALSE;
        }

    }

	//////////////////Private Functions//////////
	private function load_by_id($id) {
		$this->id = $id;
		$this->get_user();
	}
	private function load_by_username($username) {
		$sql = "SELECT id FROM accounts WHERE username = :username LIMIT 1";
		$args = array(':username'=>$username);
		$result = $this->db->query($sql,$args);
		if (isset($result[0]['id'])) {
			$this->id = $result[0]['id'];
			$this->get_user();
		}
	}
	private function get_user() {

		$sql = "SELECT name, username, archive_directory, is_admin, is_enabled, time_created, cfop ";
		$sql .= "FROM accounts ";
		$sql .= "WHERE accounts.id=:id ";
		$sql .= "LIMIT 1";
		$args = array(':id'=>$this->id);
		$result = $this->db->query($sql,$args);
		if (count($result)) {
			$this->user_name = $result[0]['username'];
			$this->admin = $result[0]['is_admin'];
			$this->full_name = $result[0]['name'];
			$this->cfop = $result[0]['cfop'];
			$this->time_created = $result[0]['time_created'];
			$this->enabled = $result[0]['is_enabled'];
			$this->email = $this->ldap->get_email($this->get_username());
			$this->archive_directory = $result[0]['archive_directory'];
		}
	}
	private function get_user_exist($username) {

		$sql = "SELECT COUNT(1) as count FROM accounts WHERE username=:username AND is_enabled='1'";
		$args = array(':username'=>$username);
		$result = $this->db->query($sql,$args);
		return $result[0]['count'];

	}

	private function get_user_rdn() {
        $filter = "(uid=" . $this->get_username() . ")";       
        $attributes = array('dn');
        $result = $this->ldap->search($filter,'',$attributes);
        if (isset($result[0]['dn'])) {
            return $result[0]['dn'];
        }
        else {
            return false;
        }
    }

	private function is_disabled($username) {
		$sql = "SELECT count(1) as count FROM accounts WHERE username=:username ";
		$sql .= "AND is_enabled='0' LIMIT 1";
		$args = array(':username'=>$username);
		$result = $this->db->query($sql,$args);
		if ($result['count']) {
			return true;
		}
		return false;
	}
}


?>
