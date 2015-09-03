<?php
class user {

	////////////////Private Variables//////////

	private $db; //mysql database object
	private $id;
	private $username;
	private $name;
	private $enabled;
	private $time_created;
	private $ldap;
	private $email;
	private $admin;
	
	private $directories = NULL;
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
	public function create($username,$admin) {
		$username = trim(rtrim($username));
		
		$error = false;
		$message = "";
		//Verify Username
		if ($username == "") {
			$error = true;
			$message = "<div class='alert alert-danger'>Please enter a username.</div>";
		}
		elseif ($this->get_user_exist($username)) {
			$error = true;
			$message .= "<div class='alert alert-danger'>User already exists in database.</div>";
		}
		elseif (!$this->ldap->is_ldap_user($username)) {
			$error = true;
			$message = "<div class='alert alert-danger'>User does not exist in LDAP database.</div>";
		}

		//If Errors, return with error messages
		if ($error) {
			return array('RESULT'=>false,
					'MESSAGE'=>$message);
		}

		//Everything looks good, add user
		else {
		
			if ($this->is_disabled($username)) {
				$this->load_by_username($username);
				$this->enable();
				$this->set_admin($admin);
				if($hasdir){
					$this->set_archive_directory($archive_dir);
					$this->set_cfop($cfop);
				}
				$user_id = $this->id;	
			}
			else {
				$full_name = $this->ldap->get_ldap_full_name($username);
				$sql = "insert into users (`username`,`name`,`is_admin`,`is_enabled`,`time_created`) values (:username,:fullname,:admin,1,NOW())";
				$args = array(':username'=>$username,':fullname'=>$full_name,':admin'=>$admin);
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
		return $this->username;
	}
	public function get_email() {
		return $this->email;
	}
	public function get_name() {
		return $this->name;
	}
	public function get_enabled() {
		return $this->enabled;
	}
	public function get_time_created() {
		return $this->time_created;
	}
	public function get_directories(){
		if($this->directories == NULL){
			$sql = "select id from directories where user_id=:id and is_enabled=1";
			$args = array(':id'=>$this->id);
			$results = $this->db->query($sql,$args);
			
			$this->directories = array();
			foreach($results as $row){
				$directory = new archive_directory($this->db);
				$directory->load_by_id($row['id']);
				$this->directories[] = $directory;
			}
		}
		return $this->directories;
	}
	// Gets a summary of data usage for this user for the given month
	public function get_data_summary($month,$year) {
		$prevmonth = $month-1;
		$prevyear = $year;
		if($prevmonth==0){
			$prevyear = $prevyear - 1;
			$prevmonth = 12;
		}
		$sql = "SELECT d.directory, ROUND(u.directory_size/1048576,4) as terabytes, u.num_small_files, u.usage_time, u.cost as cost, coalesce((select ROUND(u1.directory_size/1048576,4) from archive_usage u1 where u1.directory_id=u.directory_id and year(u1.`usage_time`)=:prevyear and month(u1.`usage_time`)=:prevmonth order by u1.usage_time limit 1),0) as prevusage, d.cfop as cfop ";
		$sql .= "FROM archive_usage u ";
		$sql .= "left join directories d on u.directory_id=d.id ";
		$sql .= "WHERE d.user_id=:id ";
		$sql .= "AND YEAR(u.`usage_time`)=:year ";
        $sql .= "AND MONTH(u.`usage_time`)=:month ";
        $sql .= "order by u.usage_time";
        $args = array(':id'=>$this->get_user_id(),':year'=>$year,':month'=>$month,':prevyear'=>$prevyear,':prevmonth'=>$prevmonth);
        return $this->db->query($sql,$args);
	}
	
	// Checks to see if the given directory is already associated with any user
	private function data_dir_exists($directory) {
		$sql = "SELECT count(1) as count FROM directories ";
		$sql .= "WHERE directory LIKE :dir ";
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
		$sql = "UPDATE users SET is_enabled='1' WHERE id=:id LIMIT 1";
		$args = array(':id'=>$this->get_user_id());
		$this->db->non_select_query($sql,$args);
		$this->enabled = true;
		return true;
	}
	// Disables this user
	public function disable() {
		$message;
		$error = false;
		
		$sql = "update users set is_enabled='0' where id=:id limit 1";
		$args = array(':id'=>$this->get_user_id());
		$this->enabled = false;
		$this->db->non_select_query($sql,$args);
		
		$message = "User successfully deleted";
		return array('RESULT'=>true,'MESSAGE'=>$message);
	}

	public function is_admin() {
		return $this->admin;
	}

	public function is_user() {
		return !$this->admin;
    }
    
    public function has_directory(){
	    $sql = "select count(id) as count from directories where user_id=:userid and is_enabled=1";
	    $args = array(':userid'=>$this->id);
	    $result = $this->db->query($sql,$args);
	    if($result[0]['count'] > 0){
		    return true;
	    } else {
		    return false;
	    }
    }
	
	// Makes the user an admin (or not)
	public function set_admin($admin) {
		$sql = "UPDATE users SET is_admin=:admin ";
		$sql .= "WHERE id=:id LIMIT 1";
		$args = array(':admin'=>$admin,':id'=>$this->get_user_id());
		$result = $this->db->non_select_query($sql,$args);
		if ($result) {
			$this->admin = $admin;
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
		$sql = "SELECT id FROM users WHERE username = :username LIMIT 1";
		$args = array(':username'=>$username);
		$result = $this->db->query($sql,$args);
		if (isset($result[0]['id'])) {
			$this->id = $result[0]['id'];
			$this->get_user();
		}
	}
	private function get_user() {

		$sql = "SELECT name, username, is_admin, is_enabled, time_created ";
		$sql .= "FROM users ";
		$sql .= "WHERE id=:id ";
		$sql .= "LIMIT 1";
		$args = array(':id'=>$this->id);
		$result = $this->db->query($sql,$args);
		if (count($result)) {
			$this->username = $result[0]['username'];
			$this->admin = $result[0]['is_admin'];
			$this->name = $result[0]['name'];
			$this->time_created = $result[0]['time_created'];
			$this->enabled = $result[0]['is_enabled'];
			$this->email = $this->ldap->get_email($this->get_username());
		}
	}
	private function get_user_exist($username) {

		$sql = "SELECT COUNT(1) as count FROM users WHERE username=:username AND is_enabled='1'";
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
		$sql = "select count(id) as count from users where username=:username and is_enabled=0 limit 1";
		$args = array(':username'=>$username);
		$result = $this->db->query($sql,$args);
		if ($result[0]['count'] == 1) {
			return true;
		}
		return false;
	}
}


?>
