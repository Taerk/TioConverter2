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
			case "events":
				$output = json_encode($tio->getEvents(), JSON_PRETTY_PRINT);
				break;
				
			default:
				if (isset($_GET['tioevent'])) {
					$tournamentId = $tio->getTournamentId($_GET['tioevent']);
					$tio->active_file = $tournamentId . '/' . $tournamentId . '.tio';
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
	case (isset($_GET['tioevent']) && $_GET['tioevent'] == 'admin'):
		if (isset($_GET['tiogame'])) {
			
			switch ($_GET['tiogame']) {
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
					if (isset($_SESSION['admin']) && file_exists(CONVERTER . '/' . $_GET['tiogame']) && !is_dir(CONVERTER . '/' . $_GET['tiogame'])) {
						
						// Correct file type
						$fileext = explode(".", $_GET['tiogame'])[count(explode(".", $_GET['tiogame'])) - 1];
						
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
						echo file_get_contents(CONVERTER . '/' . $_GET['tiogame']);
						
						die;
					} else {
						if (trim($_GET['tiogame']) == "" || !isset($_SESSION['admin'])) {
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
		require_once(CONVERTER . '/tioconverter.front.php');
		break;
}

?>