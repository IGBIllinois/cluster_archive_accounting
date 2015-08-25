<?php require_once 'includes/header.inc.php';
	$graph_type_array[0]['type'] = 'user_usage_per_month';
	$graph_type_array[0]['title'] = 'Archive Usage per Month';
	$graph_type_array[1]['type'] = 'user_delta_usage_per_month';
	$graph_type_array[1]['title'] = '&Delta; Archive Usage per Month';
	$graph_type_array[2]['type'] = 'user_smallfiles_per_month';
	$graph_type_array[2]['title'] = 'Small Files per Month';
	$graph_type_array[3]['type'] = 'user_cost_per_month';
	$graph_type_array[3]['title'] = 'Cost per Month';
	$graph_type_array[4]['type'] = 'user_balance_per_month';
	$graph_type_array[4]['title'] = 'Balance per Month';
	
	// Default graph settings
	$user_id = $login_user->get_user_id();
	$year = date('Y');
	$graph_type = $graph_type_array[0]['type'];
	if(isset($_POST['get_job_graph'])){
		$year = $_POST['year'];
		$user_id = $_POST['user_id'];
		$graph_type = $_POST['graph_type'];
	}
	
	if (!$login_user->permission($user_id)) {
        echo "<div class='alert alert-error'>Iinvalid Permissions</div>";
        exit;
	}
	
	$start_date = $year . "0101";
	$end_date = $year . "1231";
	
	$get_array = array('year'=>$year,'graph_type'=>$graph_type,'user_id'=>$user_id);
	$graph_image = "<img src='graph.php?" . http_build_query($get_array) . "'>";
	
	$graph_form = "<select name='graph_type' class='form-control input-sm'>";

	foreach ($graph_type_array as $graph) {
        $graph_form .= "<option value='" . $graph['type'] . "' ";
        if ($graph_type == $graph['type']) {
            $graph_form .= "selected='selected'";
        }
        $graph_form .= ">" . $graph['title'] . "</option>\n";
	
	
	}
	
	$graph_form .= "</select>";
	
	$year_form = "<select name='year' class='form-control input-sm'>";
	for ($i=2010;$i<=date('Y');$i++) {
		if ($i == $year) {
			$year_form .= "<option value='" . $i . "' selected='selected'>" . $i . "</option>";
		}
		else {
			$year_form .= "<option value='" . $i . "'>" . $i . "</option>";
		}
	
	}
	$year_form .= "</select>";
	//list of users to select from
	$user_list = array();
	if ($login_user->is_admin()) {
	    $user_list = user_functions::get_graph_users($db);
	}
	$user_list_html = "";
	if (count($user_list)) {
        $user_list_html = "<div class='form-group'><label class='inline'>Username:</label> <select class='form-control input-sm' name='user_id'>";
        if ((!isset($_GET['user_id'])) || ($_GET['user_id'] == $login_user->get_user_id())) {
            $user_list_html .= "<option value='" . $login_user->get_user_id(). "' selected='selected'>";
            $user_list_html .= $login_user->get_username() . "</option>";
        }
        else {
            $user_list_html .= "<option value='" . $login_user->get_user_id() . "'>";
            $user_list_html .= $login_user->get_username() . "</option>";
        }

        foreach ($user_list as $user) {
            if ($user['id'] == $user_id) {
                $user_list_html .= "<option value='" . $user['id'] . "' selected='true'>" . $user['username'] . "</option>";
            }
            else {
                $user_list_html .= "<option value='" . $user['id'] . "'>" . $user['username'] . "</option>";
            }
        }
        $user_list_html .= "</select></div>";
	}
	
	?>
<h3>Yearly Stats - <?php echo $year;?></h3>
<form class="form-inline" method="post" action='<?php echo $_SERVER['PHP_SELF'];?>'>
	<?php
		if($login_user->is_admin()){
			echo $user_list_html;
		} else {
			echo '<input type="hidden" name="user_id" value="'.$user_id."'>";
		}
	?>
	<div class="form-group">
		<label>Year:</label>
		<?php echo $year_form; ?>
	</div>
	<div class="form-group">
		<label>Graph:</label>
		<?php echo $graph_form; ?>
	</div>
	<input class="btn btn-primary btn-sm" type="submit" name="get_job_graph" value="Get Graph" />
</form>
<div class="row">
	<div class="col-sm-12">
		<script type="text/javascript">
			// Load visualization API
			google.load('visualization', '1.0', {'packages':['corechart']});
			google.setOnLoadCallback(drawChart);
			
			function drawChart(){
				$.ajax({
					url: "graph.php?<?php echo http_build_query($get_array);?>",
					dataType: "json",
					success: function(jsonData){
						var data = new google.visualization.DataTable(jsonData);
				
						var chart = new google.visualization.SteppedAreaChart(document.getElementById('chart_div'));
						chart.draw(data,{width: 900,height: 600,hAxis:{showTextEvery:1,slantedText:true}});
					},
					error: function(jsonData){
						console.log(jsonData.responseText);
					}
				});
			}			
		</script>
		<div id="chart_div"></div>
	</div>
</div>

<?php
	require_once 'includes/footer.inc.php';