<?php
/**
 * TioConverter v2.0
 * 
 * By Gabriel Nieves (Taerk)
 */
 
require_once('settings.php');

switch (true) {
	// GET data
	case (isset($_GET['get'])):
		require_once(CONVERTER . '/tioconverter.class.php'); // Should already be included in settings.php
		
		// Output JSON
		if (!$tio->debug_mode) {
			header("Content-type: application/json; charset=utf-8");
		}
		
		switch (strtolower($_GET['get'])) {
			case "tournaments":
				$output = json_encode($tio->getTournaments(), JSON_PRETTY_PRINT);
				break;
				
			default:
				if (isset($_GET['tiotournament'])) {
					$tio->setTournament($tio->getTournamentId($_GET['tiotournament']));
					$tio->parseBracket();
					
					if ($tio->loaded) {
						$tio->setEvent((isset($_GET['tioevent']) && trim($_GET['tioevent']) != "" ? $_GET['tioevent'] : $tio->getDefaultEvent()));
					}
				}
				$output = json_encode($tio->parseBracket(), JSON_PRETTY_PRINT);
				break;
		}
		
		/* Output Result */
		if ($tio->debug_mode) {
			echo "<pre style=\"border: 1px solid #aaa; background-color: #eee; padding: 10px\">$output</pre>";
		} else {
			echo $output;
		}
		die;
		
		break;
	
	// Admin Panel
	case (isset($_GET['tiotournament']) && $_GET['tiotournament'] == 'admin'):
		if (isset($_GET['tioevent'])) {
			
			switch ($_GET['tioevent']) {
				case "debug":
					// Check for password_has function. If it doesn't exist, get it from bcrypt
					if (!function_exists('password_hash')) {
						try {
							require_once(BCRYPT);
						} catch (Exception $e) {
							die('bcrypt encryption missing');
						}
					}
					
					require_once(CONVERTER . '/tioconverter.debug.php');
					break;
					
				case "encrypt":
					require_once(CONVERTER . '/encrypt.php');
					break;
					
				case "download":
					require_once(CONVERTER . '/tioconverter.download.php');
					break;
					
				default:
					// Allow shortcuts to syles and js files
					if (isset($_SESSION['admin']) && file_exists(CONVERTER . '/' . $_GET['tioevent']) && !is_dir(CONVERTER . '/' . $_GET['tioevent'])) {
						
						// Correct file type
						$fileext = explode(".", $_GET['tioevent'])[count(explode(".", $_GET['tioevent'])) - 1];
						
						switch ($fileext) {
							case 'js':
								header("Content-type: text/javascript; charset=utf-8");
								break;
							case 'css':
								header("Content-type: text/css; charset=utf-8");
								break;
							default:
								header("HTTP/1.1 404 Not Found");
								break;
						}
						echo file_get_contents(CONVERTER . '/' . $_GET['tioevent']);
						
						die;
					} else {
						if (trim($_GET['tioevent']) == "" || !isset($_SESSION['admin'])) {
							require_once(CONVERTER . '/tioconverter.admin.php');
						} else {
							header("HTTP/1.1 404 Not Found");
							echo "404 Not Found";
						}
						die;
					}
					break;
			}
			break;
		} else {
			require_once(CONVERTER . '/tioconverter.admin.php');
		}
		
	// Front Page
	default:		
		if (isset($_GET['tiotournament'])) {
			$tio->setTournament($tio->getTournamentId($_GET['tiotournament']));
			$tio->parseBracket();
					
			if ($tio->loaded) {
				$tio->setEvent((isset($_GET['tioevent']) && trim($_GET['tioevent']) != "" ? $_GET['tioevent'] : $tio->getDefaultEvent()));
			}
		}
		require_once(CONVERTER . '/tioconverter.front.php');
		break;
}

?>