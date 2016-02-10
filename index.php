<?php
/**
 * TioConverter v2.0
 * 
 * By Gabriel Nieves (Taerk)
 */

require_once('settings.php');

switch (true) {
	case (isset($_GET['tioevent']) && $_GET['tioevent'] == 'admin'):
		switch (true) {
			case (isset($_GET['tiogame']) && $_GET['tiogame'] == "debug"):
			
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
			default:
				require_once(PATH . CONVERTER . '/tioconverter.admin.php');
				break;
		}
		break;
	default:
		require_once(PATH . CONVERTER . '/tioconverter.front.php');
		break;
}

?>