<?php
if (isset($_SESSION['admin'])) {
	if (isset($_POST['file'])) {
		$fileurl = trim($_POST['file']);
		$source = "post";
	} else if (isset($_GET['file'])) {
		$fileurl = trim($_GET['file']);
		$source = "get";
	}
	
	if (isset($fileurl)) {
		if ($fileurl == "") {
			echo json_encode(['url' => "", 'method' => $source, 'response' => -1, 'data' => ""]);
			die;
		}
		
		if (isset($_GET['response'])) {
			$ch = curl_init($fileurl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			$c = curl_exec($ch);
			echo curl_getinfo($ch, CURLINFO_HTTP_CODE);
		} else {			
			// Return default cURL
			
			header("Content-type: application/json; charset=utf-8");
				
			// Get cURL resource
			$curl = curl_init();
			
			// Set some options - we are passing in a useragent too here
			curl_setopt_array($curl, [
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER => false,
				CURLOPT_URL => $fileurl,
				CURLOPT_USERAGENT => 'TioConverter2',
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_FOLLOWLOCATION => true
			]);
			
			$data = curl_exec($curl);
			
			$return = ['url' => $fileurl, 'method' => $source, 'response' => curl_getinfo($curl, CURLINFO_HTTP_CODE), 'data' => $data];
			
			// Close request to clear up some resources
			curl_close($curl);
			
			echo json_encode($return);
			die;
		}
	}
} else {
	session_destroy();
}
?>