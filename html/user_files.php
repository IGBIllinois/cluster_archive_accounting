<?php
	require_once 'includes/header.inc.php';
	
	$directory_id = $login_user->get_directories()[0]->get_id();
	if(isset($_GET['directory_id']) && is_numeric($_GET['directory_id'])){
		$directory_id = $_GET['directory_id'];
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
	
	// User list
	$user_list = array();
	if ($login_user->is_admin()) {
		$user_list = user_functions::get_graph_users($db);
	}
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
		<?php if ($login_user->is_admin()){
			echo $user_list_html;
		} ?>
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
			background-color:#337ab7;
			color: white;
		}

		.dirindicator {
			padding-right: 4px;
		}
		.smallwarning {
			color: #f0ac4c;
		}
	</style>
	<div class="filelist">

	</div>
	<script type="text/javascript">
		window.onload = function(){
			var basedir = '<?php echo __ARCHIVE_DIR__.$directory->get_directory();?>';
			var root = {"filename":"<?php echo $directory->get_directory();?>","filesize":0,children:[]};
			var smallfilesize = <?php echo $settings->get_setting('small_file_size'); ?>;

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
					root.children.push({"filename":path[0],"filesize":file.filesize,'date':file.file_time,children:[]});
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
					// Format date
					var date = new Date(root.date);
					var dateStr = padDigits(date.getMonth(),2)+'/'+padDigits(date.getDate(),2)+'/'+date.getFullYear();
					
					$node.append(' <div class="filetime">'+dateStr+'</div>');
					
					//Format file size
					var filesize = root.filesize+' KB';
					if(root.filesize>1024)filesize=(root.filesize/1024.0).toFixed(2)+' MB';
					if(root.filesize>1024*1024)filesize=(root.filesize/1024.0/1024.0).toFixed(2)+' GB';
					if(root.filesize>1024*1024*1024)filesize=(root.filesize/1024.0/1024.0/1024.0).toFixed(2)+' TB';
					if(root.filesize < smallfilesize)filesize = "<span class='smallwarning glyphicon glyphicon-alert'></span> "+filesize;
					
					$node.append(' <div class="filesize">'+filesize+'</div>');
					
					$node.addClass('file');
					$node.prepend('<span class="dirindicator glyphicon glyphicon-file"></span> ');
				} else {
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
			
			// Initializer
			function displayFileList(jsonData){
				console.log(jsonData);
				for(var i=0; i<jsonData.length; i++){
					addFile(jsonData[i]);
				}
				sortDir(root);
				var $root = dirNode(root);
				$('.filelist').append($root);
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