<?php

class user_functions {

	//get_users()
	//returns array of users
	public static function get_search_users($db,$ldap = "",$search = "") {
    	$search = strtolower(trim(rtrim($search)));
        $where_sql = array();

    	$sql = "select u.*, (select group_concat(concat('/archive/',d1.directory) separator ', ') from users u1 left join directories d1 on u1.id=d1.user_id where d1.is_enabled=1 and u1.id=u.id) as `directory` from users u ";
    	$args = array(':basedir'=>__ARCHIVE_DIR__);
        array_push($where_sql,"u.is_enabled=1 ");

    	if ($search != "" ) {
        	$terms = explode(" ",$search);
        	$termcount=0;
            foreach ($terms as $term) {
	                $search_sql = "(LOWER(u.username) LIKE :term".$termcount." OR ";
        	        $search_sql .= "LOWER(u.name) LIKE :term".$termcount.") ";
                	array_push($where_sql,$search_sql);
                	$args['term'.$termcount] = '%'.$term.'%';
                	$termcount++;
            }
        }
    	$num_where = count($where_sql);
        if ($num_where) {
    	        $sql .= "WHERE ";
            	$i = 0;
                foreach ($where_sql as $where) {
    	                $sql .= $where;
            	        if ($i<$num_where-1) {
                    	        $sql .= "AND ";
                        }
    	                $i++;
            	}

        }
    	$sql .= "  ORDER BY u.username ASC ";
    	$result = $db->query($sql,$args);

        if ($ldap != "") {
    	        $ldap_all_users = $ldap->get_all_users();
            	foreach ($result as &$user) {
                        if (in_array($user['username'],$ldap_all_users)) {
    	                        $user['user_ldap'] = 1;

    	                }
            	        else {
                    	        $user['user_ldap'] = 0;
                        }
    	        }
        }
    	return $result;
	}
	
	public static function get_directories($db,$user){
		$sql = "select u.id as user_id, u.username, d.id as dir_id, d.directory, d.do_not_bill from directories d left join users u on d.user_id=u.id where d.is_enabled=1 and u.is_enabled=1 ";
		$args = array();
		if(!$user->is_admin()){
			$sql .= "and u.id=:userid ";
			$args[':userid']=$user->get_user_id();
		}
		$sql .= "order by username asc";
		$result = $db->query($sql,$args);
		return $result;
	}
	
	public static function get_billable_directories($db){
		$sql = "select u.id as user_id, u.username, d.id as dir_id, d.directory from directories d left join users u on d.user_id=u.id where d.is_enabled=1 and u.is_enabled=1 and d.do_not_bill=0 order by username asc";
		$args = array();
		$result = $db->query($sql,$args);
		return $result;
	}
	
	public static function get_all_users($db){
		$sql = "select users.* from users where is_enabled=1 order by username";
		$result = $db->query($sql);
		return $result;
	}

	// Returns number of enabled users
	public static function get_num_users($db) {
        $sql = "SELECT count(1) as count FROM users ";
    	$sql .= "WHERE user_enabled=1";
        $result = $db->query($sql);
    	return $result[0]['count'];
	}

	// Returns list of all disabled users
	public static function get_disabled_users($db) {
    	$sql = "SELECT * FROM accounts WHERE is_enabled='0' ORDER BY username ASC";
        return $db->query($sql);
	}

}

?>
