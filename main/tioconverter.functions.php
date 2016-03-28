<?php
function custom_log($input, $session = -1, $minimal = false) {
	$text = "[TioConverter - ".$session."][".date('M t Y H:i:s O')."] ";
	$handle = fopen(LOGFILE, 'a+');
	if (!$minimal) {
		$trace = debug_backtrace()[1];
		if ($input == null) {
			$text .= "logging";
		} else {
			$text .= $input;
		}
		$text .= " -- ".$trace['function']."() in ".$trace['file'].":".$trace['line']." -- ";
		if ($_SERVER['REQUEST_URI'][strlen($_SERVER['REQUEST_URI']) - 1] == "/") {
			$text .= substr($_SERVER['REQUEST_URI'], 0, strlen($_SERVER['REQUEST_URI']) - 1);
		} else {
			$text .= $_SERVER['REQUEST_URI'];
		}
		if ($_SERVER['QUERY_STRING'] != "") {
			$text .= "?".$_SERVER['QUERY_STRING'];
		}
		$text .= " -- ".$_SERVER['REMOTE_ADDR']." -- ".$_SERVER['HTTP_USER_AGENT'];
	} else {
		$text = $input;
	}
	fwrite($handle, $text."\n");
	fclose($handle);
}
?>