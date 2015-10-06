<?php
	require_once 'includes/header.inc.php';
	
	$directories = $login_user->get_directories();
	$directory_id = 0;
	if(count($directories)>0){
		$directory_id = $directories[0]->get_id();
	}
	if(isset($_GET['directory_id']) && is_numeric($_GET['directory_id'])){
		$directory_id = $_GET['directory_id'];
	}
	// User list
	$user_list = array();
	$user_list = user_functions::get_directories($db,$login_user);
	if($directory_id==0){
		$directory_id = $user_list[0]['dir_id'];
	}
	
	$directory = new archive_directory($db);
	$directory->load_by_id($directory_id);
	$user_id = $directory->get_user_id();
	if(!$login_user->permission($user_id)){
		echo "Invalid Permissions";
		exit;
	}
	if(isset($_GET['month']) && isset($_GET['year'])){
		$year = $_GET['year'];
		$month = $_GET['month'];
	} else {
		$year = date('Y');
		$month = date('m');
	}
	$start_date = $year.$month."01";
	$end_date = date('Ymd',strtotime('-1 second',strtotime('+1 month',strtotime($start_date))));
	$month_name = date('F',strtotime($start_date));
	
	$user_list_html = "";
	if (count($user_list)) {
		$user_list_html = "<div class='form-group'><label>Directory: </label> <select class='form-control input-sm' name='directory_id'>";
		foreach ($user_list as $user) {
			if ($user['dir_id'] == $directory_id) {
				$user_list_html .= "<option value='" . $user['dir_id'] . "' selected='true'>" . $user['username'] ." - ".__ARCHIVE_DIR__.$user['directory'] . "</option>";
			} else {
				$user_list_html .= "<option value='" . $user['dir_id'] . "'>" . $user['username'] ." - ".__ARCHIVE_DIR__.$user['directory'] . "</option>";
			}
		}
		$user_list_html .= "</select></div>";
	}
	
	//////Year////////
	$year_html = "<select class='form-control input-sm' name='year'>";
	for ($i=2015; $i<=date("Y");$i++) {
		if ($i == $year) {
			$year_html .= "<option value='$i' selected='true'>$i</option>";
		}
		else { $year_html .= "<option value='$i'>$i</option>";
		}
	}
	$year_html .= "</select>";
	
	///////Month///////
	$month_array = array('01','02','03','04','05','06','07','08','09','10','11','12');
	$month_html = "<select class='form-control input-sm' name='month'>";
	foreach ($month_array as $month_number) {
		if ($month_number == $month) {
			$month_html .= "<option value='" . $month_number . "' selected='true'>" . $month_number . "</option>";
		}
		else { $month_html .= "<option value='" . $month_number . "'>" . $month_number . "</option>";
		}
	}
	$month_html .= "</select>";
	
	$user = new user($db,$ldap,$user_id);
	$usage = data_usage::latestUsage($db,$directory_id);
	$get_array = array("month"=>$month,"year"=>$year,"directory_id"=>$directory_id);

	$settings = new settings($db);
?>
	<h4>User File List - <?php echo $month_name . " " . $year; ?></h4>
	<form class="form-inline" action='<?php echo $_SERVER['PHP_SELF']; ?>' method="get">
		<?php echo $user_list_html;?>
		<div class="form-group">
			<label>Month: </label>
			<?php echo $month_html; ?>
		</div>
		<div class="form-group">
			<label>Year: </label>
			<?php echo $year_html; ?>
		</div>
		<input class="btn btn-primary btn-sm" type="submit" value="Get File List" />
	</form>
	<table class='table table-condensed table-striped table-bordered'>
	
		<tr>
			<td>Name:</td>
			<td><?php echo $user->get_name(); ?></td>
		</tr>
		<tr>
			<td>Username:</td>
			<td><?php echo $user->get_username(); ?></td>
		</tr>
		<tr>
			<td>Directory:</td>
			<td><?php echo __ARCHIVE_DIR__.$directory->get_directory(); ?></td>
		</tr>
		<tr>
			<td>Usage:</td>
			<td><?php echo number_format($usage->get_directory_size()/1048576.0,4); ?> TB</td>
		</tr>
		<tr>
			<td># of Small Files:</td>
			<td><?php echo $usage->get_smallfiles();?></td>
		</tr>
		<tr>
			<td>Billing Dates:</td>
			<td><?php echo html::get_pretty_date($start_date); ?> - <?php echo html::get_pretty_date($end_date); ?></td>
		</tr>
	</table>
	<style>
		.filelist{
						
		}	
		.filelist .filename, .filelist .filesize {
			display: inline
		}
		
		.filelist ul{
			list-style: none;
			margin-bottom:0;
		}
		.dirnode.closed,.dirnode.open{
			cursor: pointer;
		}
		.dirnode.closed>ul{
			display:none;
		}
		.dirnode.open>ul{
			display:block;
		}
		.dirnode .filesize {
			float: right;
		}
		.dirnode .filetime {
			float: right;
			margin-left: 10px;
		}
		
		.dirnode{
			border-bottom: 1px solid #ddd;
		}
		.filelist ul li:last-child .dirnode {
			border:none;
		}
		.filelist ul li:first-child .dirnode {
			border-top: 1px solid #ddd;
		}

		.filelist ul li:nth-child(even){
			background-color: #f9f9f9;
			color: black;
		}
		.filelist ul li:nth-child(odd){
			background-color: #ffffff;
			color: black;
		}
		
		.dirnode:hover{
			background-color:#f0f0f0;
			color: black;
		}

		.dirindicator {
			padding-right: 4px;
		}
		.smallwarning {
			color: #f0ac4c;
		}
	</style>
	<div class="row">
		<div class="col-sm-6">
			<div id="size_chart_div"></div>
		</div>
		<div class="col-sm-6">
			<div id="count_chart_div"></div>
		</div>
	</div>
	<div class="filelist">
		<div class="row">
			<div class="col-sm-8 col-sm-offset-2" style="padding:50px 0">
				<div class="progress">
					<div class="progress-bar progress-bar-striped active" style="width:100%"> </div>
				</div>
			</div>
		</div>
	</div>
	<script type="text/javascript">
		google.load('visualization', '1.1', {'packages':['corechart']});
		window.onload = function(){
			var basedir = '<?php echo __ARCHIVE_DIR__.$directory->get_directory();?>';
			var root = {"filename":"<?php echo $directory->get_directory();?>","filesize":0,children:[]};
			var smallfilesize = <?php echo $settings->get_setting('small_file_size'); ?>;
			var digest = {'size':{},'count':{}};

			// Helpers			
			Array.prototype.findin = function(field,value){
				for(var i=0; i<this.length; i++){
					if(this[i][field] == value){
						return i;
					}
				}
				return -1;
			}
			function padDigits(number, digits) {
			    return Array(Math.max(digits - String(number).length + 1, 0)).join(0) + number;
			}
			
			// Data structure setup
			var makeDirs;
			makeDirs = function(path,root,file){
				if(path.length == 1){
					root.children.push({"filename":path[0],"filesize":file.filesize,'smallfile':file.smallfile,'date':file.file_time,children:[]});
				} else {
					// Check if dir exists, create if it doesn't, recurse
					var childIndex = root.children.findin('filename',path[0]);
					if (childIndex == -1){
						childIndex = root.children.push({'filename':path[0],"filesize":0,children:[]}) - 1;
					}
					path.shift();
					makeDirs(path,root.children[childIndex],file);
				}
			}
			
			function addFile(file){
				var filename = file.filename.substr(basedir.length+1);
				var path = filename.split("/");
				makeDirs(path,root,file);
			}
			
			var sortDir;
			sortDir = function(root){
				root.children.sort(
					function compare(a, b) {
						return a.filename.localeCompare(b.filename);
					}
				);
				for(var i=0; i<root.children.length; i++){
					sortDir(root.children[i]);
				}
			}
			
			// HTML setup
			var dirNode;
			dirNode = function(root){
				var $node = $('<div class="dirnode"></div>')
					.append('<div class="filename">'+root.filename+'</div>');
				if(root.children.length == 0){
					// Leaf node; File
					// Format date
					var date = new Date(root.date);
					var dateStr = padDigits(date.getMonth()+1,2)+'/'+padDigits(date.getDate(),2)+'/'+date.getFullYear(); //Date.getMonth() returns Jan=0, Feb=1, etc. Because of course it does.
					
					$node.append(' <div class="filetime">'+dateStr+'</div>');
					
					//Format file size
					var filesize = pretty_filesize(root.filesize);
					if(root.smallfile)filesize = "<span class='smallwarning glyphicon glyphicon-alert'></span> "+filesize;
					
					$node.append(' <div class="filesize">'+filesize+'</div>');
					
					$node.addClass('file');
					$node.prepend('<span class="dirindicator glyphicon glyphicon-file"></span> ');
					
					// Add digest info
					var split = root.filename.split('.');
					var extension = split[split.length-1].toLowerCase();
					if(! (extension in digest.size) ){
						digest.size[extension] = 0;
						digest.count[extension] = 0;
					}
					digest.size[extension] += parseInt(root.filesize);
					digest.count[extension] += 1;
				} else {
					// Directory
					$node.addClass('open');
					$node.prepend('<span class="dirindicator glyphicon glyphicon-folder-open"></span> ');
				}
				var $children = $('<ul class="children"></ul>');
				for(var i=0; i<root.children.length; i++){
					var $child = $('<li></li>')
						.append(dirNode(root.children[i]));
					$children.append($child);
				}
				$node.append($children);
				return $node;
			}
			
			function initGraphs(){
				
				
				var sizeGraph  = {'cols':[{'label':'Extension','type':'string'},{'label':'Usage','type':'number'}],rows:[]};
				var countGraph = {'cols':[{'label':'Extension','type':'string'},{'label':'Count','type':'number'}],rows:[]};
				var keys = Object.keys(digest.size);
				for(var ext in digest.size){
					sizeGraph.rows.push({'c':[{'v':'.'+ext},{'v':digest.size[ext],'f':pretty_filesize(digest.size[ext])}]});
				}
				var sizeData = new google.visualization.DataTable(sizeGraph);
				var sizeChart = new google.visualization.PieChart(document.getElementById('size_chart_div'));
				var sizeOptions = {height:400,title:'Size by extension',pieHole:0.5,tooltip:{trigger:'selection'}};
				sizeChart.draw(sizeData,sizeOptions);
				google.visualization.events.addListener(sizeChart,'onmouseover',function(entry){
					sizeChart.setSelection([{row:entry.row}]);
				});
				google.visualization.events.addListener(sizeChart, 'onmouseout', function(entry) {
					sizeChart.setSelection([]);
				});
				
				for(var ext in digest.count){
					countGraph.rows.push({'c':[{'v':'.'+ext},{'v':digest.count[ext]}]});
				}
				var countData = new google.visualization.DataTable(countGraph);
				var countChart = new google.visualization.PieChart(document.getElementById('count_chart_div'));
				var countOptions = {height:400,title:'Count by extension',pieHole:0.5,tooltip:{trigger:'selection'}};
				countChart.draw(countData,countOptions);
				google.visualization.events.addListener(countChart,'onmouseover',function(entry){
					countChart.setSelection([{row:entry.row}]);
				});
				google.visualization.events.addListener(countChart, 'onmouseout', function(entry) {
					countChart.setSelection([]);
				});
				
				$(window).resize(function(){
			    	sizeChart.draw(sizeData,sizeOptions);
			    	countChart.draw(countData,countOptions);
			    });
			}
			
			// Initializer
			function displayFileList(jsonData){
				if(jsonData.length==0){
					$('.filelist').html('<p class="alert alert-warning">No files during this time period</p>');
				} else {
					for(var i=0; i<jsonData.length; i++){
						addFile(jsonData[i]);
					}
					sortDir(root);
					var $root = dirNode(root);
					$('.filelist').html($root);
					initGraphs();
				}
			}
			
			// Click handler
			$('.filelist').on('click','.dirnode',function(e){
				e.stopPropagation();
				var $this = $(this);
				if($this.hasClass('open')){
					$this.removeClass('open');
					$this.addClass('closed');
					$this.children('.dirindicator').removeClass('glyphicon-folder-open');
					$this.children('.dirindicator').addClass('glyphicon-folder-close');
				} else if($this.hasClass('closed')){
					$this.removeClass('closed');
					$this.addClass('open');
					$this.children('.dirindicator').removeClass('glyphicon-folder-close');
					$this.children('.dirindicator').addClass('glyphicon-folder-open');
				}
			});
			
			$.ajax({
				url:"file_list.php?<?php echo http_build_query($get_array);?>",
				dataType: "json",
				success: displayFileList,
				error: function(jsonData){
					$('.filelist').html(jsonData.responseText);
				}
			});
		};
	</script>
<?php
	require_once 'includes/footer.inc.php';