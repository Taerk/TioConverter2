<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Polarity Bracket Manager v2.0</title>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
		<script type="text/javascript" src="https://netdna.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
		<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
		<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet" type="text/css">
		<link href="main/tioconverter.panel.index.css" rel="stylesheet" type="text/css">
	</head>
	<body>
		<form action="?login" method="post" id="container">
			<div>
				<div id="login">
					<a href=".."><button class="btn" type="button"><span class="fa fa-arrow-left"></span></button></a>
					<input name="secret" type="password" class="form-control">
					<button class="btn" type="submit"><span class="fa fa-key"></span></button>
				</div>
				<?php
				if (isset($error)) {
					switch ($error) {
						case 900:
							$type = "error";
							$message = 'Permanent authentication failure';
							custom_log("Error $error - Permanent authentication failure - Unable to find passwd file at " . PASSWD);
							break;
						case 901:
							$type = "error";
							$message = 'Permanent authentication failure';
							custom_log("Error $error - Permanent authentication failure - " . PASSWD ." is not readable");
							break;
						case 902:
							$type = "error";
							$message = 'Invalid Credentials';
							custom_log("Error $error - Invalid Credentials - No user found with matching password");
							break;
						case 990:
							$type = "good";
							if (isset($user)) {
								$message = 'Logged in as ' . $user;
								custom_log("Logged in as $user");
							}
							break;
						default:
							$type = "";
							$message = "";
							break;
					}
					echo '<div id="message" class="'.$type.'">'.$error.' - '.$message.'</div>';
				}
				?>
			</div>
		</form>
	</body>
</html>