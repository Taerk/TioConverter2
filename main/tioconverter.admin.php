<?php
error_reporting(-1);

require_once(PATH . '/settings.php');

if (isset($_GET['login'])) {
	if (file_exists(PASSWD)) {
		if (is_readable(PASSWD)) {
			$load_passwords = file_get_contents(PASSWD);
			$load_passwords = str_replace("\r", "", $load_passwords);
			$password_lines = explode("\n", $load_passwords);
			foreach ($password_lines as $key=>$line) {
				$tmp = explode("::", $line);
				$password_lines[$key] = ['user' => $tmp[0], 'password' => $tmp[1]];
			}
			
			$user = -1;
			for ($i = 0, $pass_found = false; $i < count($password_lines) && !$pass_found; $i++) {
				if (password_verify($_POST['secret'], $password_lines[$i]['password'])) {
					$pass_found = true;
					$user = $password_lines[$i]['user'];
				}
			}
			
			if ($user == -1) {
				$error = 902; // invalid password (user not found)
			} else {
				$error = 990; // ** VALID LOGIN **
				
				
				$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
				$_SESSION['username'] = $user;
			}
		} else {
			$error = 901; // passwd is not readable
		}
	} else {
		$error = 900; // passwd does not exist
	}
}

if (!isset($_SESSION['admin'])) {
	require_once(PATH . CONVERTER . '/tioconverter.admin.login.php');
	die;
}

// if (isset($_POST['update'])) {
	// var_dump($_POST);
	
	// $text = "";
	// $text .= str_replace("\r", "", str_replace("\n", "|", $_POST['bracket_url']))."\n";
	// $text .= $_POST['default_event']."\n";
	// $text .= $_POST['default_game']."\n";
	// if (isset($_POST['enable_download'])) { $text .= "true"; } else { $text .= "false"; }
	// $text .= "\n";
	// if (isset($_POST['enable_results'])) { $text .= "true"; } else { $text .= "false"; }
	// $text .= "\n";
	
	// $handle = fopen('defaults.txt', 'w+');
	// fwrite($handle, $text);
	// fclose($handle);
// }

// $get_defaults = file_get_contents('defaults.txt');
// $get_defaults_split = explode("\n", str_replace("\r", "", $get_defaults));
// $all_dropbox_links = explode("|", trim(str_replace("dl=0", "dl=1", $get_defaults_split[0])));
// $dropbox_link = '';
// foreach ($all_dropbox_links as $drop_link) {
	// $dropbox_link .= $drop_link."\n";
// }
// $default_event = (isset($get_defaults_split[1])) ? (int)$get_defaults_split[1] : 0;
// $default_game  = (isset($get_defaults_split[2])) ? (int)$get_defaults_split[2] : 0;
// $download_from_dropbox = ($get_defaults_split[3] == "true") ? true : false;
// $enable_results = ($get_defaults_split[4] == "true") ? true : false;
?><!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
		<script type="text/javascript" src="js/jquery.color.plus-names-2.1.2.min.js"></script>
		<script type="text/javascript" src="http://netdna.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.5.0/js/bootstrap-datepicker.min.js"></script>
		<link href="http://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
		<link href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet" type="text/css">
		<link href="http://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.5.0/css/bootstrap-datepicker.standalone.min.css" rel="stylesheet" type="text/css">
		<title>Polarity - Bracket Panel</title>
		<style type="text/css">
		form {
			margin: auto;
			width: 98%;
			max-width: 600px;
		}
		</style>
		<script type="text/javascript">
		$(document).ready(function() {
			$('#tio-update-until').datepicker({
				format: 'yyyy-mm-dd',
				startDate: '+3d'
			});
		});
		</script>
	</head>
	<body>
		<nav class="navbar navbar-default">
			<div class="container-fluid">
				<!-- Brand and toggle get grouped for better mobile display -->
				<div class="navbar-header">
				  <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				  </button>
				  <span class="navbar-brand">Bracket Manager</span>
				</div>

				<!-- Collect the nav links, forms, and other content for toggling -->
				<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
					<ul class="nav navbar-nav">
						<li<?php if (!isset($_GET['add'])) { ?> class="active" <?php } ?>><a href="?manage">Manage Bracket</a></li>
						<li<?php if (isset($_GET['add'])) { ?> class="active" <?php } ?>><a href="?add">Add a New Bracket</a></li>
					</ul>
				</div><!-- /.navbar-collapse -->
			</div><!-- /.container-fluid -->
		</nav>
		<?php if (!isset($_GET['add'])) { ?>
		<form action="" method="post" class="form-horizontal form-group" role="form" id="manage_bracket">
			<div class="page-header"><h1>Manage Bracket</h1></div>
			
			<label for="basic-url">Select Bracket</label>
			<select size="5" class="form-control"><?php
			foreach ($tio->getEvents() as $id=>$event) {
				echo '<option value="'.$id.'">'.$event.'</option>';
			}
			?></select>
			<h5><a href="#" class="text-danger pull-right">Delete Bracket</a></h5>
			
			<div class="page-header"><h3>Update Information</h3></div>
			
			<label for="basic-url">Bracket URL</label>
			<div class="input-group">
				<span class="input-group-btn">
					<button class="btn btn-success" type="button">Enabled</button>
				</span>
				<input type="text" class="form-control" id="basic-url">
				<span class="input-group-btn">
					<button class="btn btn-default" type="button">Check bracket link</button>
				</span>
			</div>
			
			<br>
			
			<div class="input-group">
				<span class="input-group-addon" id="basic-addon2">tio ID</span>
				<input type="text" class="form-control" id="basic-url" aria-describedby="basic-addon2" readonly>
			</div>
			
			<br>
			
			<div class="form-group">
				<div class="col-xs-5">
					<label for="tio-tourney-name">Tournament Name</label>
					<input type="text" class="form-control" id="tio-tourney-name">
				</div>
				
				<div class="col-xs-3">
					<label for="tio-tourney-name">Check Interval</label>
					<div class="input-group">
						<input type="text" class="form-control" id="tio-tourney-name" value="5">
						<span class="input-group-addon" id="basic-addon">min.</span>
					</div>
				</div>
				
				<div class="col-xs-4">
					<label for="tio-tourney-name">Update Until</label>
					<div class="input-group">
						<span class="input-group-addon" id="basic-addon"><span class="glyphicon glyphicon-calendar"></span></span>
						<input type="text" class="form-control" id="tio-update-until">
					</div>
				</div>
			</div>
			
			<hr>
			
			<button type="button" class="btn btn-primary btn-block" aria-haspopup="true" aria-expanded="true">Update Settings</button>
		</form>
		<?php } else { ?>
		<form action="" method="post" class="form-horizontal" role="form" id="add_bracket">
			<div class="page-header"><h1>Add a New Bracket</h1></div>
			
			<label for="basic-url">Input bracket download URL</label>
			<div class="input-group">
				<input type="text" class="form-control" id="basic-url">
				<span class="input-group-btn">
					<button class="btn btn-default" type="button">Check bracket link</button>
				</span>
			</div>
			
			<div class="page-header"><h3>Check Information</h3></div>
			<div class="input-group">
				<span class="input-group-addon" id="basic-addon2">tio ID</span>
				<input type="text" class="form-control" id="basic-url" aria-describedby="basic-addon2" readonly>
			</div>
			
			<br>
			
			<div class="form-group">
				<div class="col-xs-5">
					<label for="tio-tourney-name">Tournament Name</label>
					<input type="text" class="form-control" id="tio-tourney-name">
				</div>
				
				<div class="col-xs-3">
					<label for="tio-tourney-name">Check Interval</label>
					<div class="input-group">
						<input type="text" class="form-control" id="tio-tourney-name" value="5">
						<span class="input-group-addon" id="basic-addon">min.</span>
					</div>
				</div>
				
				<div class="col-xs-4">
					<label for="tio-tourney-name">Update Until</label>
					<div class="input-group">
						<span class="input-group-addon" id="basic-addon"><span class="glyphicon glyphicon-calendar"></span></span>
						<input type="text" class="form-control" id="tio-update-until">
					</div>
				</div>
			</div>
			
			<hr>
			
			<button type="button" class="btn btn-primary btn-block" aria-haspopup="true" aria-expanded="true">Add Bracket</button>
				
			</div>
		</form>
		<?php } ?>
	</body>
</html>