<?php

$validate_session = true;

// Set xdebug view
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_depth', 12);

// URLs
define('HTTP',			'polarity.test'); // Unused
define('HTTPS',			'polarity.test'); // Unused

// Directories
define('PATH', 			dirname(__FILE__) . '/');
define('CACHE', 		PATH . 'cache');
define('ARCHIVE',	 	PATH . 'archive');
define('CONVERTER', 	PATH . 'main');
define('THIRDPARTY',	PATH . '3rdparty');
define('VARIABLE',		PATH . 'etc');

// Files
define('LIBRARY', 		VARIABLE . '/library.json');
define('LOGFILE',	 	VARIABLE . '/tioconverter.log');
define('PASSWD', 		VARIABLE . '/passwd');
define('BCRYPT', 		THIRDPARTY . '/bcrypt/class.bcrypt.php');

// Authentication
define('SALT', 'XOuKbA6EcdJ2f5dde0X7Xq3yK@PamFs4f*GO');

// When enabled, disables file writing
define('MAINTENANCE_MODE', false);

// Enable downloading of .tio files
define('ALLOW_DOWNLOAD', true);

// Requires & Includes
require_once(CONVERTER . '/tioconverter.class.php');
require_once(CONVERTER . '/tioconverter.functions.php');

if (file_exists(BCRYPT) && !function_exists('password_hash')) {
	include_once(BCRYPT);
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