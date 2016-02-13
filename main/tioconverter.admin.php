<?php
error_reporting(-1);

require_once(PATH . '/settings.php');

if (isset($_GET['login'])) {
	if (!isset($_POST['secret'])) {
		if ($_GET['login'] != '0') {
			$error = 903; // auth token missing
		}
		session_destroy();
		session_start();
	} else if (trim($_POST['secret']) != "") {
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
					
					$_SESSION['admin'] = true;
					$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
					$_SESSION['username'] = $user;
					
					header("location:admin");
				}
			} else {
				$error = 901; // passwd is not readable
			}
		} else {
			$error = 900; // passwd does not exist
		}
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

require_once('tioconverter.admin.library.php');

if (isset($_GET['page'])) {
	switch ($_GET['page']) {
		case 'manage':
			$page = 'manage';
			break;
		default:
			$page = 'add';
			break;
	}
} else {
	$page = 'add';
}

?><!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
		<script type="text/javascript" src="http://netdna.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
		<script type="text/javascript" src="../3rdparty/jquery/jscolor/jquery.color.plus-names-2.1.2.min.js"></script>
		<script type="text/javascript" src="../3rdparty/jquery/datetimepicker/build/jquery.datetimepicker.full.min.js"></script>
		<!-- <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.5.0/js/bootstrap-datepicker.min.js"></script> -->
		<script type="text/javascript" src="tioconverter.panel.js"></script>
		<link href="http://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
		<link href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet" type="text/css">
		<!-- <link href="http://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.5.0/css/bootstrap-datepicker.standalone.min.css" rel="stylesheet" type="text/css"> -->
		<link href="../3rdparty/jquery/datetimepicker/jquery.datetimepicker.css" rel="stylesheet" type="text/css">
		<link href="tioconverter.panel.css" rel="stylesheet" type="text/css">
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
			$('#tio-update-until').datetimepicker({
				format: 'm/d/Y H:i',
				startDate: '<?php echo date('m/d/Y H:00', strtotime('+1 hour')); ?>'
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
						<li<?php if ($page == 'add') { ?> class="active" <?php } ?>><a href="?page=add">Add a Bracket</a></li>
						<li<?php if ($page == 'manage') { ?> class="active" <?php } ?>><a href="?page=manage">Manage Bracket</a></li>
					</ul>
					<ul class="nav navbar-nav navbar-right">
						<li><a><?php echo $_SESSION['username']; ?></a></li>
						<li class="pull-right"><a href="?login=0">Log Out</a></li>
					</ul>
				</div><!-- /.navbar-collapse -->
			</div><!-- /.container-fluid -->
		</nav>
		
		<?php
		switch ($page) {
			case 'manage':
				require_once('tioconverter.admin.manage.php');
				break;
			case 'add':
				require_once('tioconverter.admin.add.php');
				break;
		} ?>
	</body>
</html>