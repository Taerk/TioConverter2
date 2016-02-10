<?php

$validate_session = true;


// Directories
define('PATH', 			dirname(__FILE__) . '/');
define('CACHE', 		'cache');
define('ARCHIVE',	 	'archive');
define('CONVERTER', 	'main');
define('THIRDPARTY', 	'3rdparty');

// Files
define('CONFIG', 		PATH . 'library.json');
define('LOGFILE',	 	PATH . 'tioconverter.log');
define('BCRYPT', 		PATH . 'brypt.php');
define('PASSWD', 		PATH . ARCHIVE . '/passwd');

require_once(PATH . CONVERTER . '/tioconverter.class.php');

// Authentication
define('SALT', 'XOuKbA6EcdJ2f5dde0X7Xq3yK@PamFs4f*GO');

// When enabled, disables file writing
define('MAINTENANCE_MODE', false);

// Enable downloading of .tio files
define('ALLOW_DOWNLOAD', true);

if (file_exists(PATH . CONVERTER . '/tioconverter.functions.php')) {
	require_once(PATH . CONVERTER . '/tioconverter.functions.php');
}

// Expire session if invalid
session_start();
if ($validate_session) {
	if (isset($_SESSION['ip'])) {
		if ($_SESSION['ip'] != $_SERVER['REMOTE_ADDR']) {
			session_destroy();
		}
	}
}
?>