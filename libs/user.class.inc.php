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
	private $default_project; //default project object
	private $default_data_dir; //default data_dir object
	private $email;
	private $admin;
	private $default_project_id;
	private $default_data_dir_id;
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
	public function default_project() {
		$project = new project($this->db,0,$this->get_username());
		return $project;

	}
	public function default_data_dir() {
		$data_dir = new data_dir($this->db,$this->default_data_dir_id);
		return $data_dir;
	}
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
	
	public function get_projects() {
		$sql = "SELECT * FROM projects WHERE project_enabled='1'";
		$all_projects = $this->db->query($sql);
		$ldap_groups = $this->ldap->get_user_groups($this->get_username());
		$user_projects = array();
		foreach ($ldap_groups as $group) {
			foreach ($all_projects as $project) {
				if ($group == $project['project_ldap_group']) {
					array_push($user_projects,$project);
				}

			}

		}
		return $user_projects;

	}
	public function is_project_member($project) {
		$user_projects = $this->get_projects();
		foreach ($user_projects as $user_project) {
                        if ($user_project['project_name'] == $project) {
                                return true;
                        }
                }
		return false;
	}
	public function get_queues() {
		$sql = "SELECT queue_name,queue_ldap_group FROM queues WHERE queue_enabled='1'";
		$all_queues = $this->db->query($sql);
		$ldap_groups = $this->ldap->get_user_groups($this->get_username());
		$user_queues = array();
		foreach ($all_queues as $queue) {
			if ($queue['queue_ldap_group'] === "") {
				array_push($user_queues,$queue['queue_name']);
			}
			else {
				foreach ($ldap_groups as $group) {
					if ($group == $queue['queue_ldap_group']) {
						array_push($user_queues,$queue['queue_name']);
					}
				}
			}

		}
		return $user_queues;




	}
	public function is_supervisor() {
		if (!$this->get_supervisor_id()) {
			return true;
		}
		return false;

	}
	public function enable() {
		$sql = "UPDATE accounts SET is_enabled='1' WHERE id=:id LIMIT 1";
		$args = array(':id'=>$this->get_user_id());
		$this->db->non_select_query($sql,$args);
		$this->enabled = true;
		return true;
	}
	public function disable() {
		$supervising_users = $this->get_supervising_users();
		$message;
		$error = false;
		if (count($supervising_users)) {
			$message = "Unable to delete user.  User is supervising " . count($supervising_users) . " other users.";
			$error = true;
		}		
		if (!$error) {
			$sql = "UPDATE users SET user_enabled='0' WHERE user_id=:id LIMIT 1";
			$args = array(':id'=>$this->get_user_id());
			$this->enabled = false;
			$this->db->non_select_query($sql,$args);
			$this->default_project()->disable();
			$this->default_data_dir()->disable();
			
			$message = "User successfully deleted";
			return array('RESULT'=>true,'MESSAGE'=>$message);
		}
		else {
			return array('RESULT'=>false,'MESSAGE'=>$message);
		}

	}
	public function set_supervisor($supervisor_id) {
		$sql = "UPDATE users SET user_supervisor=:supervisor WHERE user_id=:id";
		$args = array(':supervisor'=>$supervisor_id,':id'=>$this->get_user_id());
		$this->db->non_select_query($sql,$args);
		//gets supervisors username
		$supervisor_sql = "SELECT user_name FROM users WHERE user_id=:id LIMIT 1";
		$args = array(':id'=>$supervisor_id);
		$result = $this->db->query($supervisor_sql,$args);

		$this->supervisor_id = $supervisor_id;
		$this->supervisor_name = $result[0]['user_name'];
		return true;
	}
	public function get_supervising_users() {
		if ($this->is_supervisor()) {
			$sql = "SELECT users.* ";
			$sql .= "FROM users ";
			$sql .= "WHERE user_supervisor=:id AND user_enabled='1' ";
			$sql .= "AND user_admin='0' ORDER BY user_name ASC";
			$args = array(':id'=>$this->get_user_id());
			return $this->db->query($sql,$args);
		}
		return array();
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
	
	public function email_bill($admin_email,$start_date = 0,$end_date = 0) {
		if (($start_date == 0) && ($end_date == 0)) {
			$end_date = date('Ymd',strtotime('-1 second', strtotime(date('Ym') . "01")));
			$start_date = substr($end_date,0,4) . substr($end_date,4,2) . "01";
		}
		$month = date('m',strtotime($start_date));
		$year = date('Y',strtotime($start_date));

		$user_stats = new user_stats($this->db,$this->get_user_id(),$start_date,$end_date);

		$subject = "Biocluster Accounting Bill - " . functions::get_pretty_date($start_date) . "-" . functions::get_pretty_date($end_date);
		$to = $this->get_email();
		$message = "<p>Biocluster Accounting Bill - " . functions::get_pretty_date($start_date) . "-" . functions::get_pretty_date($end_date) . "</p>";
		$message .= "<br>Name: " . $this->get_full_name();
		$message .= "<br>Username: " . $this->get_username();
		$message .= "<br>Start Date: " . functions::get_pretty_date($start_date);
		$message .= "<br>End Date: " . functions::get_pretty_date($end_date);
		$message .= "<br>Number of Jobs: " . $user_stats->get_num_jobs();
		$message .= "<p>Below is your bill.  You can go to https://biocluster.igb.illinois.edu/accounting/ ";
		$message .= "to view a detail listing of your jobs.";
		$message .= "<p>Cluster Usage</p>";
		
		$message .= $this->get_jobs_table($start_date,$end_date);


		
		$message .= "<p>Data Usage</p>";	
		$message .= $this->get_data_table($month,$year);


		$headers = "From: " . $admin_email . "\r\n";
		$headers .= "Content-Type: text/html; charset=iso-8859-1" . "\r\n";
		mail($to,$subject,$message,$headers," -f " . $admin_email);



	}

	public function get_jobs_table($start_date,$end_date) {
		$jobs_summary = $this->get_jobs_summary($start_date,$end_date);
		$jobs_html = "<p><table border='1'>";
		if (count($jobs_summary)) {
                        $jobs_html .= "<tr><td>Queue</td><td>Project</td>";
                        $jobs_html .= "<td>Cost</td><td>Billed Amount</td><td>CFOP</td><td>Activity Code</td></tr>";
                        foreach ($jobs_summary as $summary) {
                                $jobs_html .= "<tr>";
                                $jobs_html .= "<td>" . $summary['queue'] . "</td>";
                                $jobs_html .= "<td>" . $summary['project'] . "</td>";
                                $jobs_html .= "<td>$" . number_format($summary['total_cost'],2) . "</td>";
                                $jobs_html .= "<td>$" . number_format($summary['billed_cost'],2) . "</td>";
                                if (!$summary['cfop_restricted']) {
                                        $jobs_html .= "<td>" . $summary['cfop'] . "</td>";
                                        $jobs_html .= "<td>" . $summary['activity'] . "</td>";
                                }
                                else {
                                        $jobs_html .= "<td colspan='2'>RESTRICTED</td>";
                                }
                                $jobs_html .= "</tr>";
                        }
                }
                else {
                        $jobs_html .= "<tr><td>No Jobs</td></tr>";

                }
		$jobs_html .= "</table>";
		return $jobs_html;



	}

	public function get_data_table($month,$year) {

		$data_summary = $this->get_data_summary($month,$year);
		$data_html = "<p><table border='1'>";
		if (count($data_summary)) {
                        $data_html .= "<tr><td>Directory</td>";
                        $data_html .= "<td>Type</td>";
                        $data_html .= "<td>Project</td>";
                        $data_html .= "<td>Terabytes</td>";
                        $data_html .= "<td>Cost</td>";
                        $data_html .= "<td>Billed Amount</td>";
                        $data_html .= "<td>CFOP</td>";
                        $data_html .= "<td>Activity Code</td>";
                        $data_html .= "</tr>";
                        foreach ($data_summary as $data) {
                                $data_html .= "<tr>";
                                $data_html .= "<td>" . $data['directory'] . "</td>";
                                $data_html .= "<td>" . $data['data_cost_dir'] . "</td>";
                                $data_html .= "<td>" . $data['project'] . "</td>";
                                $data_html .= "<td>" . $data['terabytes'] . "</td>";
                                $data_html .= "<td>$" . number_format($data['total_cost'],2) . "</td>";
                                $data_html .= "<td>$" . number_format($data['billed_cost'],2) . "</td>";
                                if (!$data['cfop_restricted']) {
                                        $data_html .= "<td>".  $data['cfop'] . "</td>";
                                        $data_html .= "<td>" . $data['activity_code'] . "</td>";
                                }
                                else {
                                        $data_html .= "<td colspan='2'>RESTRICTED</td>";
                                }
                                $data_html .= "</tr>";


                        }
                }
                else {
                        $data_html .= "<tr><td>No Data Usage.</td></tr>";
                }
		$data_html .= "</table>";
		return $data_html;


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
	private function get_disable_user_id($username) {

		
		$sql = "SELECT user_id FROM users WHERE user_name=:username AND user_enabled='0'";
		$args = array(':username'=>$username);
		$result = $this->db->query($sql,$args);
		if (count($result)) {
			return $result[0]['user_id'];
		}
		else {
			return false;
		}
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
