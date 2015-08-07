<?php

class user_functions {

	//get_users()
	//returns array of users
	public static function get_users($db,$ldap = "",$search = "") {
    	$search = strtolower(trim(rtrim($search)));
        $where_sql = array();

    	$sql = "SELECT * FROM accounts ";
    	$args = array();
        array_push($where_sql,"is_enabled='1'");

    	if ($search != "" ) {
        	$terms = explode(" ",$search);
        	$termcount=0;
            foreach ($terms as $term) {
	                $search_sql = "(LOWER(username) LIKE :term".$termcount." OR ";
        	        $search_sql .= "LOWER(name) LIKE :term".$termcount.") ";
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
    	$sql .= " ORDER BY username ASC ";
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

	public static function get_num_users($db) {
        $sql = "SELECT count(1) as count FROM users ";
    	$sql .= "WHERE user_enabled=1";
        $result = $db->query($sql);
    	return $result[0]['count'];
	}

	public static function get_disabled_users($db) {
    	$sql = "SELECT * FROM accounts WHERE is_enabled='0' ORDER BY username ASC";
        return $db->query($sql);
	}

}

?>
