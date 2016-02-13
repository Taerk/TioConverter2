<?php
/**
 * TioConverter v2.0
 * 
 * By Gabriel Nieves (Taerk)
 */

require_once('settings.php');

switch (true) {
	// Admin Panel
	case (isset($_GET['tioevent']) && $_GET['tioevent'] == 'admin'):
		if (isset($_GET['tiogame'])) {
			switch ($_GET['tiogame']) {
				case "debug":
					// Check for password_has function. If it doesn't exist, get it from bcrypt
					if (!function_exists('password_hash')) {
						try {
							require_once(PATH . THIRDPARTY . '/bcrypt.class.php');
						} catch (Exception $e) {
							die('bcrypt encryption missing');
						}
					}
					
					require_once(PATH . CONVERTER . '/tioconverter.debug.php');
					break;
					
				case "encrypt":
					require_once(PATH . CONVERTER . '/encrypt.php');
					break;
					
				case "download":
					require_once(PATH . CONVERTER . '/tioconverter.download.php');
					break;
					
				default:
					// Allow shortcuts to syles and js files					
					if (isset($_SESSION['admin']) && file_exists(PATH . CONVERTER . '/' . $_GET['tiogame']) && !is_dir(PATH . CONVERTER . '/' . $_GET['tiogame'])) {
						
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
								header("Content-type: text/plain; charset=utf-8");
								break;
						}
						echo file_get_contents(PATH . CONVERTER . '/' . $_GET['tiogame']);
						
						die;
					} else {
						require_once(PATH . CONVERTER . '/tioconverter.admin.php');
					}
					break;
			}
			break;
		} else {
			require_once(PATH . CONVERTER . '/tioconverter.admin.php');
		}
		
	// Front Page
	default:
		require_once(PATH . CONVERTER . '/tioconverter.front.php');
		break;
}

?>