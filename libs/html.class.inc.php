<?php

class html {

	//get_pages_html()
	//$url - url of page
	//$num_records - number of items
	//$start - start index of items
	//$count - number of items per page
	//returns pagenation to navigate between pages of devices
	public static function get_pages_html($url,$num_records,$start,$count) {

	        $num_pages = ceil($num_records/$count);
        	$current_page = $start / $count + 1;
	        if (strpos($url,"?")) {
        	        $url .= "&start=";
	        }
	        else {
        	        $url .= "?start=";
	        }

        	$pages_html = "<nav><ul class='pagination pagination-centered'>";

	        if ($current_page > 1) {
        	        $start_record = $start - $count;
                	$pages_html .= "<li><a href='" . $url . $start_record . "'>&laquo;</a></li> ";
	        }
        	else {
                	$pages_html .= "<li class='disabled'><a href='#'>&laquo;</a></li>";
	        }

        	for ($i=0; $i<$num_pages; $i++) {
                	$start_record = $count * $i;
	                if ($i == $current_page - 1) {
        	                $pages_html .= "<li class='active'>";
                	}
	                else {
        	                $pages_html .= "<li>";
                	}
	                $page_number = $i + 1;
        	        $pages_html .= "<a href='" . $url . $start_record . "'>" . $page_number . "</a></li>";
        	}

	        if ($current_page < $num_pages) {
        	        $start_record = $start + $count;
                	$pages_html .= "<li><a href='" . $url . $start_record . "'>&raquo;</a></li> ";
	        }
        	else {
                	$pages_html .= "<li class='disabled'><a href='#'>&raquo;</a></li>";
	        }
        	$pages_html .= "</ul></nav>";
	        return $pages_html;

	}

	public static function get_num_pages($numRecords,$count) {
	        $numPages = floor($numRecords/$count);
        	$remainder = $numRecords % $count;
	        if ($remainder > 0) {
        	        $numPages++;
	        }
        	return $numPages;
	}

	public static function get_url_navigation($url,$start_date,$end_date,$get_array = array()) {
	        $previous_end_date = date('Ymd',strtotime('-1 second', strtotime($start_date)));
        	$previous_start_date = substr($previous_end_date,0,4) . substr($previous_end_date,4,2) . "01";
	        $next_start_date = date('Ymd',strtotime('+1 day', strtotime($end_date)));
        	$next_end_date = date('Ymd',strtotime('-1 second',strtotime('+1 month',strtotime($next_start_date))));
	        $next_get_array = array_merge(array('start_date'=>$next_start_date,'end_date'=>$next_end_date),$get_array);
        	$previous_get_array = array_merge(array('start_date'=>$previous_start_date,'end_date'=>$previous_end_date),$get_array);
	        $back_url = $_SERVER['PHP_SELF'] . "?" . http_build_query($previous_get_array);
        	$forward_url = $_SERVER['PHP_SELF'] . "?" . http_build_query($next_get_array);
	        return array('back_url'=>$back_url,'forward_url'=>$forward_url);

	}

	public static function get_users_rows($users,$start = 0,$count = 0) {
		$i_start = 0;
		$i_count = count($users);
		if ($count) {
			$i_start = $start;
			$i_count = $start + $count;
		}
		$users_html = "";
		for ($i=$i_start;$i<$i_count;$i++) {
		        if (array_key_exists($i,$users)) {
                		if ($users[$i]['is_admin']) {
		                        $user_admin = "<span class='glyphicon glyphicon-ok'></span>";
                		}
	                	else {
        		                $user_admin = "<span class='glyphicon glyphicon-remove'></span>";
		                }
                		$users_html .= "<tr>";
	                	$users_html .= "<td><a href='user.php?user_id=" . $users[$i]['id'] . "'>";
						$users_html .= $users[$i]['username'] . "</a></td>";
		                $users_html .= "<td>" . $users[$i]['name']. "</td>";
		                $users_html .= "<td>".__ARCHIVE_DIR__.$users[$i]['archive_directory']."</td>";
						$users_html .= "<td>" . $user_admin . "</td>";
				
				if ($users[$i]['user_ldap']) {
					$users_html .= "<td><span class='glyphicon glyphicon-ok'></span></td>";
				}
				else {
					$users_html .= "<td><span class='glyphicon glyphicon-remove'></span></td>";
				}
                		$users_html .= "</tr>";
			}
        	}
		return $users_html;
	}




}

?>
