<?php
	require_once 'includes/header.inc.php';
	if(!$login_user->is_admin()){
		exit;
	}
	$year = date('Y');
	if(isset($_GET['year'])){
		$year = $_GET['year'];
	}
	$previous_year = $year - 1;
	$next_year = $year + 1;
	$forward_url = $_SERVER['PHP_SELF']."?year=".$next_year;
	$back_url = $_SERVER['PHP_SELF']."?year=".$previous_year;
	
	$graph_type_array[0]['type'] = 'usage_per_month';
	$graph_type_array[0]['title'] = 'Archive Usage per Month';
	$graph_type_array[1]['type'] = 'delta_usage_per_month';
	$graph_type_array[1]['title'] = '&Delta; Archive Usage per Month';
	$graph_type_array[2]['type'] = 'smallfiles_per_month';
	$graph_type_array[2]['title'] = 'Small Files per Month';
	$graph_type_array[3]['type'] = 'cost_per_month';
	$graph_type_array[3]['title'] = 'Cost per Month';
	
	$graph_type = $graph_type_array[0]['type'];
	if(isset($_POST['graph_type'])){
		$graph_type = $_POST['graph_type'];
	}
	
	$get_array = array('year'=>$year,'graph_type'=>$graph_type);
	$graph_image = "<img src='graph.php?".http_build_query($get_array)."'>";
	
	$graph_form = "<select name='graph_type' class='form-control' onChange='document.select_graph.submit();'>";

	foreach ($graph_type_array as $graph) {
        $graph_form .= "<option value='" . $graph['type'] . "' ";
        if ($graph_type == $graph['type']) {
            $graph_form .= "selected='selected'";
        }
        $graph_form .= ">" . $graph['title'] . "</option>\n";
	
	
	}
	
	$graph_form .= "</select>";
?>
<h3>Accumulated Stats - <?php echo $year; ?></h3>
<ul class="pager">
	<li class="previous"><a href="<?php echo $back_url;?>">Previous Year</a></li>
	<?php
		$this_year = date("Y");
        if ($next_year > $this_year) {
            echo "<li class='next disabled'><a href='#'>Next Year</a></li>";
        } else {
            echo "<li class='next'><a href='" . $forward_url . "'>Next Year</a></li>";
        }
    ?>
</ul>
<form class="form-inline" name="select_graph" method="post" action='<?php echo $_SERVER['PHP_SELF'];?>'>
	<div class="form-group">
		<label>Graph:</label>
		<?php echo $graph_form;?>
	</div>
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