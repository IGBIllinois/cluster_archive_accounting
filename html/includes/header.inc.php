<?php
	require_once 'includes/main.inc.php';
	require_once 'includes/session.inc.php';
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php echo __TITLE__; ?></title>
		<link rel="stylesheet" href="includes/bootstrap/css/bootstrap.min.css" type="text/css"/>
		<link rel="stylesheet" href="includes/main.inc.css" type="text/css"/>
		<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon"/>
		<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
		<script type="text/javascript" src="https://www.google.com/jsapi"></script>
		<script type="text/javascript" src="includes/main.inc.js"></script>
	</head>
	<body>
		<nav class="navbar navbar-inverse navbar-static-top">
			<div class="container-fluid">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#archive-accounting-nav-collapse" aria-expanded="false">
				        <span class="sr-only">Toggle navigation</span>
				        <span class="icon-bar"></span>
				        <span class="icon-bar"></span>
						<span class="icon-bar"></span>
				    </button>
					<div class="navbar-brand">
						<?php echo __TITLE__; ?>
					</div>
				</div>
				<div class="collapse navbar-collapse" id="archive-accounting-nav-collapse">
					<a type="button" class="btn btn-danger btn-sm navbar-btn navbar-right hidden-xs" style="margin-right:0" href="logout.php">Logout</a>
					<a type="button" class="btn btn-danger btn-sm btn-block visible-xs" style="margin-bottom:7px" href="logout.php">Logout</a>
				</div>
			</div>
		</nav>
		
		<div class="container-fluid">
			<div class="row">
				<div class="col-md-10 col-md-push-2">