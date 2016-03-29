<?php
class tioConverterLibrary {
	public $library;
	
	function __construct($config) {
		$this->library = json_decode(file_get_contents($config), true);
	}
	function addTournament($options) {
		$uniq = uniqid();
		$new_tourney_id			= $options['tio-tourney-id'];
		$new_tourney_name		= (isset($options['tio-tourney-name']) ? $options['tio-tourney-name'] : $uniq);
		$new_tourney_added_date	= (isset($options['tio-tourney-added-date']) ? $options['tio-tourney-added-date'] : date('m/d/Y H:i'));
		$new_tourney_added_by	= (isset($_SESSION['username']) ? $_SESSION['username'] : 'unknown');
		$new_tourney_permalink	= (isset($options['tio-tourney-permalink']) ? $options['tio-tourney-permalink'] : $options['tio-tourney-id']);
		$new_tourney_download	= (isset($options['tio-tourney-download']) ? $options['tio-tourney-download'] : '-1');
		$new_tourney_enabled	= (isset($options['tio-tourney-enabled']) ? $options['tio-tourney-enabled'] : true);
		$new_tourney_hidden		= (isset($options['tio-tourney-hidden']) ? $options['tio-tourney-hidden'] : false);
		$new_tourney_featured	= (isset($options['tio-tourney-featured']) && $options['tio-tourney-featured'] == 1 ? true : false);
		$new_tourney_default 	= (isset($options['tio-tourney-default']) ? $options['tio-tourney-default'] : 0);
		$new_tourney_update_int	= (isset($options['tio-update-interval']) ? intval($options['tio-update-interval']) : 60);
		$new_tourney_update_til	= (isset($options['tio-update-until']) ? $options['tio-update-until'] : date('m/d/Y H:00', strtotime('+1 day')));
		
		for ($i = 0, $exists = false, $exist_type = -1; $i < count($this->library['tournaments']) && $exists === false; $i++) {
			if ($this->library['tournaments'][$i]['id'] == $new_tourney_id) {
				$exist_type = 0;
				$exists = $i;
			} else if ($this->library['tournaments'][$i]['permalink'] == $new_tourney_permalink) {
				$exist_type = 1;
				$exists = $i;
			}
		}
		
		array_push($this->library['tournaments'], [
			'id' 				=> $new_tourney_id,
			'name' 				=> $new_tourney_name,
			'added' 			=> $new_tourney_added_date,
			'added_by' 			=> $new_tourney_added_by,
			'permalink' 		=> $new_tourney_permalink,
			'download' 			=> $new_tourney_download,
			'enabled' 			=> $new_tourney_enabled,
			'hidden' 			=> $new_tourney_hidden,
			'featured' 			=> $new_tourney_featured,
			'default_event'		=> $new_tourney_default,
			'update_interval'	=> $new_tourney_update_int,
			'update_until'		=> $new_tourney_update_til
		]);
		
		// Initial upload
		if (!file_exists(ARCHIVE . '/' . $new_tourney_id)) {
			mkdir(ARCHIVE . '/' . $new_tourney_id);
		}
		
		if ($exists === false) {
			file_put_contents(ARCHIVE . '/' . $new_tourney_id . '/' . $new_tourney_id . '.tio', file_get_contents($new_tourney_download));
			
			$meta = [
				'id' => $new_tourney_id,
				'md5' => md5_file(ARCHIVE . '/' . $new_tourney_id . '/' . $new_tourney_id . '.tio'),
				'last' => strtotime($new_tourney_update_til),
				'next' => strtotime($new_tourney_update_til),
				'players' => []
			];
			file_put_contents(ARCHIVE . '/' . $new_tourney_id . '/meta', json_encode($meta, JSON_PRETTY_PRINT));
			
			$prev_reporting = ini_get('error_reporting');
			error_reporting(0);
			$write = file_put_contents(LIBRARY, stripslashes(json_encode($this->library, JSON_PRETTY_PRINT)));
			if ($write) {
				echo json_encode(['result' => true, 'message' => 'Successfully updated']);
			} else {
				echo json_encode(['result' => false, 'message' => 'Failed to update']);
			}
			error_reporting($prev_reporting);
		} else {
			if ($exist_type == 0) {
				echo json_encode(['result' => false, 'message' => 'Tournament ID already exists at index '.$exists]);
			} else if ($exist_type == 1) {
				echo json_encode(['result' => false, 'message' => 'Permalink already exists at index '.$exists]);
			}
		}
		die;
	}
}

if (isset($_GET['action'])) {	
	// Make thing
	$lib = new tioConverterLibrary(LIBRARY);
	header("Content-type: application/json; charset=utf-8");
	
	if (!isset($_POST['data'])) {
		echo json_encode(['result' => false, 'message' => 'No data sent']);
		die;
	}
	
	parse_str(stripslashes($_POST['data']), $data);
	
	switch ($_GET['action']) {
		case 'add':			
			if (!isset($data['tio-tourney-id'])) {
				echo json_encode(['result' => false, 'message' => 'No tournament ID set']);
				die;
			}
			
			$lib->addTournament($data);
			break;
		case 'manage':
			break;
	}
	die;
}
?>