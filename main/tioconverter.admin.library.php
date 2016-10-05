<?php
if (isset($_GET['action'])) {	
	// Make thing
	header("Content-type: application/json; charset=utf-8");
	
	if (!isset($_POST['data'])) {
		echo json_encode(['result' => false, 'message' => 'No data sent']);
		die;
	}
	
	parse_str(stripslashes($_POST['data']), $data);
	
	if (!isset($data['tio-tourney-id'])) {
		echo json_encode(['result' => false, 'message' => 'No tournament ID set']);
		die;
	} else if (trim($data['tio-tourney-id']) == "") {
		echo json_encode(['result' => false, 'message' => 'Tournament ID is blank']);
		die;
	}
	
	switch ($_GET['action']) {
		case 'add':
			echo $tio->addTournament($data);
			break;
		case 'update':
			echo $lib->updateTournament($data);
			break;
	}
	die;
}
?>