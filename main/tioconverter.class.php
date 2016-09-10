<?php
class tioParser {
	/* Options */
	public	$debug_mode = false;
	public  $search_debug = false;
	public	$enable_results = true;
	public	$logging = true;
	public	$minimal_logging = true;
	private	$error_level = E_ALL;
	public $search_threshold = [
		"bracket" => 40,
		"tag" => 70,
		"name" => 70,
		"tag_team" => 50,
		"name_team" => 70
	];
	
	/* Directories */
	public 	$archive_directory = "archive";
	public 	$cache_directory = "cache";
	public 	$library_file = "library.json";

	/* Active Information */
	public 	$active_file = "";
	public 	$cache_file = "";
	public 	$tio_file = "";
	public 	$tio_hash = "";
	public	$loaded = false;
	private	$library_file_md5 = "";
	private	$session_id = null;
	private	$error_level_save = null;
	public $download_error = false;
	
	/* Content Storage */
	private $tournaments = [];
	private $events = [];
	private $files = [];
	private $library = [];
	private $players = [];
	private $teams = [];
	private $info = [];
	private $lost_to = [];
	private $elim_by = [];
    public $last_search_results = NULL;
	
	/**
	 * =====================
	 * == Tio Converter 2 ==
	 * =====================
	 */
	function __construct($options) {
		// Give a random session ID
		$this->session_id = uniqid('');
		
		// Set custom level of error reporting
		$this->error_level_save = ini_get("error_reporting");
		error_reporting($this->error_level);
		
		// Set up file locations
		if (isset($options['library'])) {
			$this->library_file = $options['library'];
		}
		if (isset($options['archive'])) {
			$this->archive_directory = $options['archive'];
		}
		if (isset($options['cache'])) {
			$this->cache_directory = $options['cache'];
		}
		
		// Set up currently loaded information
		$this->info = [
			'tournament' => [
				'id' => NULL,
				'name' => NULL,
				'permalink' => NULL
			],
			'event' => [
				'id' => NULL,
				'name' => NULL,
				'permalink' => NULL
			]
		];
		
		// Set up file structure
		$this->setup();
		
		// Scans for files
		$this->scan();
		
		// Load the library (typically library.json)
		$this->loadlibrary();
	}
	
	function __destruct() {
		// Reset error reporting to previous level
		error_reporting($this->error_level_save);
	}
	
	/**
	 * Logs input to $this->log_file
	 */
	public function log($input = null) {	
		if ($this->logging) {
			if (function_exists('custom_log')) {
				custom_log($input, $this->session_id, $this->minimal_logging);
			} else {
				// error_log($input);
			}
		}
	}
	
	/**
	 * Used to generate a filename from the current date
	 */
	public function today() {
		return date('mdy-Hi');
	}
	
	/**
	 * Sets the filename of the cache file
	 */
	public function setCacheName($file = null) {
		if ($file == null) {
			$this->cache_file = uniqid('');
			return true;
		} else {
			$this->cache_file = $file;
			return true;
		}
	}
	
	/**
	 * Enables debug mode and runs through
	 */
	public function debug() {
		ini_set('xdebug.var_display_max_children', 5000);
		ini_set('xdebug.var_display_max_depth', 7);
		error_reporting(E_ALL);
		ini_set('error_reporting', E_ALL);
		$this->debug_mode_mode = true;
		$this->parseBracket();
		die;
	}
	
	/**
	 * Sets up directories if they're not already
	 */
	public function setup() {
		if (!file_exists($this->archive_directory)) {
			mkdir($this->archive_directory);
		}
		if (!file_exists($this->cache_directory)) {
			mkdir($this->cache_directory);
		}
	}
	
	/**
	 * List the events into $event_list
	 */
	public function scan() {
		// Reset db
		$this->files = [];
		
		// Get information from library
		$this->library = json_decode(file_get_contents($this->library_file), true);
		
		// Get files from Archive
		$archives = scandir($this->archive_directory);
		foreach ($archives as $archive) {
			if (is_dir($this->archive_directory . '/' . $archive) && $archive != "." && $archive != "..") {
				$this->events[$archive] = [];
					
				// Default archive formatting
				$this->files[$archive] = [
					'bracket' => NULL,
					'info' => NULL,
					'other' => []
				];
				
				foreach (scandir($this->archive_directory . '/' . $archive) as $archived_file) {
					
					// Input file into array
					switch (true) {
						case ($archived_file == "."):
						case ($archived_file == ".."):
							// Skip . and .. file
							break;
						case (strpos($archived_file, $archive.".tio") !== false):
							$this->files[$archive]['bracket'] = $this->archive_directory . '/' . $archive . '/' . $archived_file;
							break;
						case ($archived_file == 'meta'):
							$this->files[$archive]['info'] =  $this->archive_directory . '/' . $archive . '/' . $archived_file;
							break;
						default:
							array_push($this->files[$archive]['other'], $this->archive_directory . '/' . $archive . '/' . $archived_file);
							break;
					}
				}
			}
		}
		asort($this->files);
	}
	
	/**
	 * Verifies the file is a valid XML file
	 */
	public function validate($fileIdentifier) {
		if (isset($this->getFiles()[$fileIdentifier])) {
			// Loads file when an ID is set instead of a file path
			$file_contents = file_get_contents($this->getFiles()[$fileIdentifier]['bracket']);
		} else {
			$file_contents = file_get_contents($fileIdentifier);
		}
		
		echo stripos($file_contents, '<?xml version="1.0" encoding="utf-8"?>');
		
		if (stripos($file_contents, '<?xml version="1.0" encoding="utf-8"?>') != -1) {
		}
	}
	
	/**
	 * Checks to see if there are any additional updates to the
	 * library file.
	 *
	 * Will return true if there is, false if there
	 * isn't or the file doesn't exit.
	 */
	public function loadlibrary($file = "") {
		// Fail if the file is not found
		if (!file_exists($this->library_file)) {
			$this->log($this->library_file." not found");
			return 1;
		} else {
			$current_md5 = md5_file($this->library_file);
			if ($current_md5 != $this->library_file_md5) {
				try {
					$this->library = json_decode(file_get_contents($this->library_file), true);
					$this->library_file_md5 = $current_md5;
					$this->log("Loaded ".$this->library_file);
					return 0;
				} catch(Exception $e) {
					$this->log($e);
					return 2;
				}
			} else {
				return 3;
			}
		}
	}
	
	/**
	 * Get library file
	 */
	public function getlibrary() {
		return $this->library;
	}
	
	/**
	 * Returns an array of all tournaments
	 */
	public function getTournaments() {
		return $this->library['tournaments'];
	}
	
	/**
	 * Returns an array of a tournament's information
	 */
	public function getTournament($searchId = NULL) {
		foreach ($this->getTournaments() as $id=>$info) {
			if ($info['id'] == $searchId) {
				return $info;
			}
		}
		return [];
	}
	
	/**
	 * Set tournament based on id/name
	 */
	public function setTournament($set = "") {
		$this->info['tournament']['id'] = $this->getTournamentId($set);
		$this->info['tournament']['name'] = $this->getTournamentName($set);
		$this->info['tournament']['permalink'] = $this->getTournamentPermalink($set);
		
		return true;
	}
	
	/**
	 * Get tournament id from library using a permalink or id
	 */
	public function getTournamentId($search = "") {
		if ($search === false) {
			return $this->info['tournament']['id'];
		} else {
			foreach ($this->library['tournaments'] as $key=>$entry) {
				if ($search == $entry['id'] || $search == $entry['permalink']) {
					return $entry['id'];
				}
			}
		}
		
		return "";
	}
	
	/**
	 * Get tournament id from library using a permalink or id
	 */
	public function getTournamentPermalink($search = "") {
		if ($search === false) {
			return $this->info['tournament']['permalink'];
		} else {
			foreach ($this->library['tournaments'] as $key=>$entry) {
				if ($search == $entry['id'] || $search == $entry['permalink']) {
					return $entry['permalink'];
				}
			}
		}
		
		return "";
	}
	
	/**
	 * Get tournament id from library using a permalink or id
	 */
	public function getTournamentName($search = "") {
		if ($search === false) {
			return $this->info['tournament']['name'];
		} else {
			foreach ($this->library['tournaments'] as $key=>$entry) {
				if ($search == $entry['id'] || $search == $entry['permalink']) {
					return $entry['name'];
				}
			}
		}
		
		return "";
	}
	
	/**
	 * Gets info regarding current tournament
	 */
	public function getActiveTournament() {
		return $this->info['tournament'];
	}
	
	/**
	 * Gets info regarding current tournament
	 */
	public function getActiveEvent() {
		if ($this->info['event']['id'] != NULL) {
			return $this->info['event'];
		} else {
			$this->info['event']['id'] = $this->getDefaultEvent($this->getTournamentId(false));
		}
		return $this->info['event'];
	}
	
	/**
	 * Returns an array of events
	 *
	 * Array (
	 * 	'$tournamentId' => '$tournamentName'
	 * )
	 */
	public function getEvents($tournament_id = "") {
		if (file_exists($this->archive_directory . "/" . $tournament_id . "/" . $tournament_id . ".tio")) { // Try getting the event lists from a file
			if (strpos(file_get_contents($this->archive_directory . "/" . $tournament_id."/" . $tournament_id . ".tio"), '<?xml version="1.0" encoding="utf-8"?>') > -1) {
				$tio = simplexml_load_file($this->archive_directory . "/" . $tournament_id."/" . $tournament_id . ".tio");
				
				$event_array = [];
				foreach ($tio->EventList->Event as $key=>$tournament) {
					foreach ($tournament->Games->Game as $key=>$event) {
						$event_array[trim((string)$event->ID)] = trim((string)$event->Name);
					}
				}
				
				return $event_array;
			} else {
				return [$this->getDefaultEvent($tournament_id) => '[ Current ]'];
			}
		} else if ($this->loaded) {
			$this->log('No bracket has been parsed');
			return $this->events;
		} else {
			return [];
		}
	}
	
	/**
	 * Returns an array of archived tournament files
	 *
	 * Array (
	 * 	$id => '$fileName'
	 * )
	 */
	public function getFiles() {
		return $this->files;
	}
	
	/**
	 * Get default event
	 */
	public function getDefaultEvent($search = "") {
		if (!$this->loaded) {
			foreach ($this->library['tournaments'] as $key=>$entry) {
				if ($search == $entry['id'] || $search == $entry['permalink']) {
					return $entry['default_event'];
				}
			}
		} else {
			foreach ($this->library['tournaments'] as $key=>$entry) {
				if ($search == $entry['id'] || $search == $entry['permalink']) {
					return $entry['default_event'];
				}
			}
			
			// Returns first event in tournament
			return array_keys($this->parseBracket())[0];
		}
		
		return "";
	}
	
	/**
	 * Set event based on id/name
	 */
	public function setEvent($set = "") {
		$event_id = NULL;
		$event_name = NULL;
		$event_permalink = NULL;
		
		// Get event id from bracket
		foreach ($this->parseBracket()[$this->getTournamentId(false)]['events'] as $key=>$entry) {
			if ($set == $entry['id'] || $set == $this->url_encode($entry['name'])) {
				$event_id = $entry['id'];
				$event_name = $entry['name'];
				$event_permalink = $this->url_encode($entry['name']);
			}
		}
		
		$this->info['event']['id'] = $event_id;
		$this->info['event']['name'] = $event_name;
		$this->info['event']['permalink'] = $event_permalink;
		
		return true;
	}
	
	/**
	 * Get event id from bracket using a permalink or id
	 */
	public function getEventId($search = "") {
		if ($search === false) {
			if ($this->info['event']['id'] == NULL) {
				return $this->getDefaultEvent($this->getTournamentId(false));
			} else {
				return $this->info['event']['id'];
			}
		}
		
		return "";
	}
	
	/**
	 * Get event permalink from library using a permalink or id
	 */
	public function getEventPermalink($search = "") {
		foreach ($this->library['tournaments'] as $key=>$entry) {
			if ($search == $entry['id'] || $search == $entry['permalink']) {
				return $entry['default_event'];
			}
		}
		
		return "";
	}
	
	/**
	 * Get a particular event section from the bracket conversion
	 */
	public function getLoadedTournament() {
		if ($this->loaded) {
			return array_merge($this->parseBracket()[$this->info['event']['id']], ['id' => $this->info['event']['id']]);
		}
		
		return [];
	}
	
	/**
	 * Get a particular game section from the bracket conversion
	 */
	public function getLoadedEvent($search = "") {
		if ($this->loaded) {
			return array_merge(
				$this->parseBracket()[$this->getTournamentId(false)]['events'][$this->getEventId(false)],
				['id' => $this->getEventId(false)]
			);
		}
		
		return [];
	}
	
	/**
	 * Encode site for URL use
	 */
	public function url_encode($text) {
		$r_i = [':', ' '];
		$r_o = ['', '-'];
		return urlencode(strtolower(str_replace($r_i, $r_o, $text)));
	}
	
	/**
	 * Gets the library section for a given tournament
	 */
	public function getTournamentLibrary() {
		for ($i = 0; $i < count($this->library['tournaments']); $i++) {
			if ($this->library['tournaments'][$i]['id'] == $this->getTournamentId(false)) {
				return $this->library['tournaments'][$i];
			}
		}
		
		return [];
	}
	
	/**
	 * Gets the meta section for a given tournament
	 */
	public function getTournamentMeta() {
		return json_decode(file_get_contents($this->files[$this->getTournamentId(false)]['info']), true);
	}
	
	/**
	 * Check for an update in the bracket file. If there's a change, update it
	 */
	public function updateFile() {
		$this->log($this->getTournamentId(false) . " is looking for updates");
		if ($this->getTournamentLibrary()['enabled']) {
			// Check to see if the time span is now past the required update interval
			// Also check to see if it's within the allowed "update_until" time
			
			$meta_block = $this->getTournamentMeta();
			
			if (true) {
				
				// Update meta file
				$next_update_time = strtotime('now + ' . $this->getTournamentLibrary()['update_interval'] . ' seconds');
				$meta_block['md5'] = md5_file($this->files[$this->getTournamentId(false)]['bracket']);
				$meta_block['last'] = strtotime('now');
				$meta_block['next'] = $next_update_time;
				
				file_put_contents($this->files[$this->getTournamentId(false)]['info'], json_encode($meta_block, JSON_PRETTY_PRINT));
				
				// Get MD5 of .tio file and the new file
				$new_contents = file_get_contents($this->getTournamentLibrary()['download']);
				if (!$new_contents) {
					$this->download_error = true;
				}
				
				$md5_records = [
					"old" => md5_file($this->files[$this->getTournamentId(false)]['bracket']),
					"new" => md5($new_contents)
				];
                $this->log("Matching content from " . $this->getTournamentLibrary()['download'] . " with " . $this->files[$this->getTournamentId(false)]['bracket']);
                $this->log("MD5 hash " . $md5_records['old'] . " => " . $md5_records['new']);
				
				// If new md5 doesn't match with the old one, update it
				if ($md5_records['old'] != $md5_records['new']) {
					if (stripos($new_contents, '<?xml version="1.0" encoding="utf-8"?>') > -1) {
						
                        file_put_contents($this->files[$this->getTournamentId(false)]['bracket'], $new_contents);
                        
                        // Save a revision version as well
                        if (!file_exists(dirname($this->files[$this->getTournamentId(false)]['bracket']) . "/revisions")) {
                            mkdir(dirname($this->files[$this->getTournamentId(false)]['bracket']) . "/revisions");
                        }
                        file_put_contents(dirname($this->files[$this->getTournamentId(false)]['bracket']) . "/revisions/" . $this->getTournamentId(false) . "_" . time() . ".tio", $new_contents);
                        
						$this->log($this->getTournamentId(false) . " bracket was updated");
					} else {
						$this->log($this->getTournamentId(false) . " bracket not updated -- not a bracket");
					}
				} else {
					$this->log($this->getTournamentId(false) . " had no md5 change");
				}
			} else {
				$this->log($this->getTournamentId(false) . " is not ready to update -- time is " . strtotime('now') . " -- update requirement is " . $meta_block['next']);
			}
		}
	}
	
	/**
	 * For bracket use -- Converts a number into a two-letter key
	 */
	public function numToLetters($number) {
		$original = $number;
		$alpha = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V'];
		$number = dechex($number);
		$text_return = "";
		for ($i = 0; $i < strlen($number); $i++) {
			// Reduce letter by one if it's a multi-letter
			$reduce = (strlen($number) > 1 && $i == 0 ? 1 : 0);
			switch ($number[$i]) {
				case 'a':
				case 'b':
				case 'c':
				case 'd':
				case 'e':
				case 'f':
					$text_return .= $alpha[array_search(strtoupper($number[$i]), $alpha) + 10 - $reduce];
					break;
				default:
					$text_return .= $alpha[$number[intval($i)] - $reduce];
					// $text_return .= "?";
					break;
			}
		}
		// return $original . " => " . $number . " => " . $text_return;
		return $text_return;
	}
	
	/**
	 * - Parses a bracket once and returns outpout
	 *
	 * - Returns previous output if the bracket has already been parsed
	 */
	public function parseBracket($return_as = '') {
		if ($this->getTournamentId(false) !== NULL && $this->active_file != ($this->getTournamentId(false) . '.tio')) {
			$check_file = $this->archive_directory . '/' . $this->getTournamentId(false) . '/' . $this->getTournamentId(false) . '.tio';
			if (file_exists($check_file)) {
				$this->log($check_file . " is now active file");
				$this->active_file = ($this->getTournamentId(false) . '/' .$this->getTournamentId(false) . '.tio');
			} else  {
				foreach ($this->getTournaments() as $key=>$to) {
					if ($to['id'] == $this->getTournamentId(false)) {
						$this->log($check_file . " tried to be set but the file was missing");
						return [];
					}
				}
			}
		}
		
		if (!isset($this->active_file) || $this->active_file == "") {
			$this->log("No active file set");
			return [];
			die;
		}
		
		$this->updateFile();
		
		if (!file_exists($this->archive_directory."/".$this->active_file)) {
			$this->log("File not found: " . $this->archive_directory."/".$this->active_file);
			return [];
			die;
		}
		
		// Check file hash; if it's different, then return cached bracket
		if (md5_file($this->archive_directory."/".$this->active_file) == $this->tio_hash) {
			return $this->tournaments;
		} else {
			$this->tio_hash = md5_file($this->archive_directory."/".$this->active_file);
		}
		
		// Set loaded to true, so you can confirm that a file was set
		$this->loaded = true;
		
		$tio = simplexml_load_file($this->archive_directory."/".$this->active_file);
		
		// Reset tournaments
		$this->tournaments = [];
		
		$this->players = [
			'00000000-0000-0000-0000-000000000000' => ['id' => '00000000-0000-0000-0000-000000000000', 'name' => NULL, 'tag' => '&nbsp;', 'location' => NULL, 'skill' => 0, 'seed' => 0],
			'00000001-0001-0001-0101-010101010101' => ['id' => '00000001-0001-0001-0101-010101010101', 'name' => NULL, 'tag' => 'Bye', 'location' => NULL, 'skill' => 0, 'seed' => 0]
		];
		$this->teams = [
			'00000000-0000-0000-0000-000000000000' => ['id' => '00000000-0000-0000-0000-000000000000', 'name' => NULL, 'tag' => '&nbsp;', 'location' => NULL, 'skill' => 0, 'seed' => 0],
			'00000001-0001-0001-0101-010101010101' => ['id' => '00000001-0001-0001-0101-010101010101', 'name' => NULL, 'tag' => 'Bye', 'location' => NULL, 'skill' => 0, 'seed' => 0]
		];
		

		//Players
		if (isset($tio->PlayerList->Players)) {
			foreach ($tio->PlayerList->Players->Player as $key=>$player) {
				// Add to the team roster if it has ' + ' in it
				if (strpos(trim((string)$player->Nickname), ' + ') > -1 || strpos(trim((string)$player->Name), ' + ') > -1) {
					$player_id = trim((string)$player->ID);
					$this->teams[$player_id] = [];
					$this->teams[$player_id]['id'] = $player_id;
					$this->teams[$player_id]['name'] = trim((string)$player->Name);
					$this->teams[$player_id]['tag'] = trim((string)$player->Nickname);
					$this->teams[$player_id]['location'] = trim((string)$player->Location);
					$this->teams[$player_id]['skill'] = intval(trim($player->Skill));
				} else {
					$player_id = trim((string)$player->ID);
					$this->players[$player_id] = [];
					$this->players[$player_id]['id'] = $player_id;
					$this->players[$player_id]['name'] = trim((string)$player->Name);
					$this->players[$player_id]['tag'] = trim((string)$player->Nickname);
					$this->players[$player_id]['location'] = trim((string)$player->Location);
					$this->players[$player_id]['skill'] = intval(trim($player->Skill));
				}
				// print_r($player);
			}
		}
	
		// Teams
		if (isset($tio->PlayerList->Teams)) {
			foreach ($tio->PlayerList->Teams->Player as $key=>$team) {
				$team_id = trim((string)$team->ID);
				$this->teams[$team_id] = [];
				$this->teams[$team_id]['id'] = $team_id;
				$this->teams[$team_id]['name'] = trim((string)$team->Name);
				$this->teams[$team_id]['tag'] = trim((string)$team->Nickname);
				$this->teams[$team_id]['location'] = trim((string)$team->Location);
				$this->teams[$team_id]['skill'] = intval(trim($team->Skill));
				// var_dump($team);
			}
		}
		
		// Events
		foreach ($tio->EventList->Event as $key=>$tournament) {
			$tournament_id = trim((string)$tournament->ID);
			$tournament_name = trim((string)$tournament->Name);
			
			$this->tournaments[$tournament_id] = [];
			$this->tournaments[$tournament_id]['name'] = $tournament_name;
			$this->tournaments[$tournament_id]['stations'] = [];
			$this->tournaments[$tournament_id]['stations_ez'] = [];
			$this->tournaments[$tournament_id]['events'] = [];
			
			// Stations
			foreach ($tournament->Stations->Station as $key=>$station) {
				array_push($this->tournaments[$tournament_id]['stations'], [
					'number' =>trim((int)$station->Number),
					'name' =>trim((string)$station->Name),
					'match_tournament' => trim((string)$station->Queue->Match->EventID),
					'match_id' => trim((string)$station->Queue->Match->Number)
				]);
			}
			
			// Stations EZ (Uses match ID as key to match stream name)
			foreach ($tournament->Stations->Station as $key=>$station) {
				$station_tournament_id = trim((string)$station->Queue->Match->EventID);
				$station_match_num = trim((string)$station->Queue->Match->Number);
				
				if (($station_tournament_id != "") && ($station_match_num != "")) {
					$this->tournaments[$tournament_id]['stations_ez'][$station_tournament_id.":".$station_match_num] = trim((string)$station->Name);
				}
			}
			
			// Events -> Games
			foreach ($tournament->Games->Game as $key=>$event) {
				
				// Generic variables
				$event_id = trim((string)$event->ID);
				$event_tournament_name = trim((string)$event->Name);
				$event_name = trim((string)$event->eventName);
				
				/**
				 * START SETTING UP THE BULK
				 */
				$this->tournaments[$tournament_id]['events'][$event_id] = [
					'id' => $event_id,
					'name' => $event_tournament_name,
					'event' => $event_name,
					'entrants' => count($event->Entrants->Entrant),
					'seeds' => [],
					'matches' => [],
					'results' => []
				];
				
				// Assign players their seeds
				foreach ($event->Entrants->Entrant as $key=>$seed) {
					$this->tournaments[$tournament_id]['events'][$event_id]['seeds'][trim((string)$seed->PlayerID)] = (int)$seed->Seed;
				}
				asort($this->tournaments[$tournament_id]['events'][$event_id]['seeds']);
				
				// tournaments -> events -> Bracket
				foreach ($event->Bracket->Matches->Match as $key=>$match) {
					$match_number = (int)$match->Number;
					$this->tournaments[$tournament_id]['events'][$event_id]['matches'][$match_number] = [
						'id' => (int)$match->Number,
						'key' => $this->numToLetters($match->Number),
						'p1' => $this->getPlayerById(trim((string)$match->Player1), (isset($this->tournaments[$tournament_id]['events'][$event_id]['seeds'][trim((string)$match->Player1)]) ? (int)$this->tournaments[$tournament_id]['events'][$event_id]['seeds'][trim((string)$match->Player1)] : -1)),
						's1' => trim((string)$match->Score->Player1Wins),
						'p2' => $this->getPlayerById(trim((string)$match->Player2), (isset($this->tournaments[$tournament_id]['events'][$event_id]['seeds'][trim((string)$match->Player2)]) ? (int)$this->tournaments[$tournament_id]['events'][$event_id]['seeds'][trim((string)$match->Player2)] : -1)),
						's2' => trim($match->Score->Player2Wins),
						'winner' => trim(($match->Winner)),
						'p1_prev' => intval(trim($match->Player1PrevMatch)),
						'p2_prev' => intval(trim($match->Player2PrevMatch)),
						'winner_next' => intval(trim($match->WinnerNextMatch)),
						'loser_next' => intval(trim($match->LoserNextMatch)),
						'nextsibiling' => intval(trim($match->NextSiblingMatch)),
						'prevsibling' => intval(trim($match->PrevSiblingMatch)),
						'round' => intval(trim($match->Round)),
						'in_progress' => (trim((string)$match->InProgress) == "True"),
						'winners' => (trim((string)$match->IsWinners) == "True"),
						'label' => trim((string)$match->Label),
						'is_championship' => (trim((string)$match->IsChampionship) == "True" ),
						'is_second_championship' => (trim((string)$match->IsSecondChampionship) == "True")
					];
				}
				
				// Events -> Results
				if ($this->enable_results) {
					/* ======================
					 * == RESULT CALCULATION
					 * ======================
					 * - Losers round 1 starts at -1
					 * - Winners round 1 starts at 1
					 */
					if ($this->debug_mode && isset($this->events[$tournament_id]['name'])) { echo "<h1>".$this->events[$tournament_id]['name']."</h1>"; }
					
					// Declare variables to be used later
					$all_matches = [];
					$round = -1; // Current round to check through
					$max_rounds_loser = -1; // The total number of winner rounds
					$max_rounds_winner = -1; // The total number of loser rounds
					$player_count = $this->tournaments[$tournament_id]['events'][$event_id]['entrants'];
					$placing = ($player_count + 1); // Base to start with (this is the number of players)
					$prev_round = 0; // Placeholder variable for previous round number
					$used_player = ['00000000-0000-0000-0000-000000000000', '00000001-0001-0001-0101-010101010101']; // Stores IDs of players that have already been given a placing
					
					/* Creates arrays with a key the same as the round number and places matches in it
					 * Example:
					 * $all_matches => [
					 *    0 => [
					 *       $match1,
					 *	     $match2
					 *    ]
					 * ];
					 */
					// foreach ($game->Bracket->Matches->Match as $key=>$match) {
					foreach ($this->tournaments[$tournament_id]['events'][$event_id]['matches'] as $key=>$match) {
						$this_matches_round = $match['round'];
						
						// Add tags for convenience
						$all_matches[$this_matches_round][$match['id']] = $match;
						
						// Get the number of rounds
						if ($this_matches_round > 0 && abs($this_matches_round) > $max_rounds_winner) {
							$max_rounds_winner = abs($this_matches_round);
						}
						if ($this_matches_round < 0 && abs($this_matches_round) > $max_rounds_loser) {
							$max_rounds_loser = abs($this_matches_round);
						}
					}
					ksort($all_matches);
					// var_dump($all_matches); die;
					
					if ($this->debug_mode) {
						echo "<style type='text/css'>
						h1, h2, h3 { margin: 3px 0; }
						h2 { border-left: 5px solid black; padding-left: 5px }
						h3 { border-left: 3px solid black; padding-left: 5px; margin-left: 8px }
						</style>";
						echo "<h2>".$this->tournaments[$tournament_id]['events'][$event_id]['name']."</h2>";
						echo "<h3>Number of Players: $player_count</h3>";
						echo "<h3>Winner Rounds: $max_rounds_winner</h3>";
						echo "<h3>Loser Rounds: $max_rounds_loser</h3>";
					}
					
					if ($this->debug_mode) { echo "<div style='color: #aaa'>-- start auto --</div>"; }
					
					$this->lost_to = [];
					
					// Cycle through losers
					$prev_round = $max_rounds_loser * -1;
					$current_winner_placing = [];
					$current_loser_placing = [];
					$matches_in_this_round = -1;
					
					$search_multiplier = -1; // Set to go through either losers or winners (losers first)
					$scan_complete = false; // Set to true to end scan
					$i = 1;
					
					while ($scan_complete == false) {
						
						// If it's done going through losers, send it back through winners
						if (abs($i) >= $max_rounds_loser && $search_multiplier == -1) {
							$search_multiplier = 1;
							$i = 1;
						}
						
						// If it's done going through winners, finish
						if ($i >= $max_rounds_winner && !isset($all_matches[$i]) && $search_multiplier == 1) {
							$scan_complete = true;
						}
						
						if (!$scan_complete) {
							// Check whether to go from losers side or winners -- this will allow it to grab matches from WF and GF
							$mi = $i * $search_multiplier;
							if ($this->debug_mode) { echo "<div style='color: #8aa'>-- Checking round $mi --</div>"; }
							
							if (isset($all_matches[$mi])) {
								// Count number of matches in this round
								$matches_in_this_round = count($all_matches[$mi]);
								if ($this->debug_mode) { echo "<div style='color: #d6d'>-- $matches_in_this_round match(es) in this round --</div>"; }
								
								$checked_matches = 0;
								foreach ($all_matches[$mi] as $match) {
									$checked_matches++;
									
									// Fixed
									if ($match['winner'] != "00000000-0000-0000-0000-000000000000" && isset($all_matches[($mi >= 0 ? $mi + 1 : $mi - 1)]) && $placing > 1) {
										
										// Add loser to array of players who have already been assigned a placing
										if (trim($match['winner']) == trim($match['p1']['id'])) {
											$loser = trim($match['p2']['id']);
											$winner = trim($match['p1']['id']);
										} else {
											$loser = trim($match['p1']['id']);
											$winner = trim($match['p2']['id']);
										}
										
										// Set up array of players an entrant lost to
										$just_added = false;
										if (!isset($this->elim_by[$loser])) { $this->elim_by[$loser] = $this->getPlayerById($winner); $just_added = true; }
										if (isset($this->elim_by[$loser]) && !$just_added) { $this->lost_to[$loser] = $this->getPlayerById($winner); }
										
										if (!in_array($loser, $used_player) == -1) {
											if ($this->debug_mode) { echo "<div style='color: #d66'>&raquo; ".$loser." -- ".$this->getPlayerById($loser)['tag']." (eliminated by ".$this->getPlayerById(trim($match['winner']))['tag']." in round $mi)</div>"; }
											array_push($used_player, $loser);
											array_push($current_loser_placing, $loser);
										}
										
										// Output results once all matches have been checked
										if ($checked_matches == $matches_in_this_round) {
											// Subtract placing from number of losses there were
											$placing -= count($current_loser_placing);
											
											// Create placing array if it doesn't exist
											if (!isset($this->tournaments[$tournament_id]['events'][$event_id]['results'][$placing])) {
												$this->tournaments[$tournament_id]['events'][$event_id]['results'][$placing] = [];
											}
											
											// Set placing for each player
											foreach ($current_loser_placing as $key=>$cur) {
												if ($this->debug_mode) { echo "<div>$cur &rarr; ".$this->getPlayerById($cur)['tag']." &rarr; $placing</div>"; }
												$this->tournaments[$tournament_id]['events'][$event_id]['results'][$placing][$this->getPlayerById($cur)['tag']] = array_merge(
													$this->getPlayerById($cur),
													[
														'lost_to' => (isset($this->lost_to[$cur]) ? $this->lost_to[$cur] : ['tag' => '???']),
														'elim_by' => (isset($this->elim_by[$cur]) ? $this->elim_by[$cur] : ['tag' => '???'])
													]
												);
											}
											
											$prev_round = $mi;
											$current_winner_placing = [];
											$current_loser_placing = [];
										}
									}
											
									// Set placing for 3rd-1st place
									if ($placing == 3 || $placing == 2) {
										$placing = 3;
										$mi = $max_rounds_loser * -1;
										$match = $all_matches[$mi][array_keys($all_matches[$mi])[0]];
										// Add loser to array of players who have already been assigned a placing
										if (trim($match['winner']) == trim($match['p1']['id'])) {
											$loser = trim($match['p2']['id']);
											$winner = trim($match['p1']['id']);
										} else {
											$loser = trim($match['p1']['id']);
											$winner = trim($match['p2']['id']);
										}
										if ($this->debug_mode) { echo "<div style='color: #d66'>&raquo; <b>(Corrected)</b> ".$loser." -- ".$this->getPlayerById($loser)['tag']." (eliminated by ".$this->getPlayerById(trim($match['winner']))['tag']." in round $mi)</div>"; }
										array_push($used_player, $loser);
										$this->tournaments[$tournament_id]['events'][$event_id]['results'][$placing] = [];
										$this->tournaments[$tournament_id]['events'][$event_id]['results'][$placing][$this->getPlayerById($loser)['tag']] = array_merge(
											$this->getPlayerById($loser),
											[
												'lost_to' => ['tag' => '???'],
												'elim_by' => ['tag' => '???']
											]
										);
										if ($this->debug_mode) { echo "<div><b>(Corrected)</b> $cur &rarr; ".$this->getPlayerById($loser)['tag']." &rarr; $placing</div>"; }
										
										$placing = 2;
										$mi = $max_rounds_winner - 1;
										$match = $all_matches[$mi][array_keys($all_matches[$mi])[0]];
										// Add loser to array of players who have already been assigned a placing
										if (trim($match['winner']) == trim($match['p1']['id'])) {
											$loser = trim($match['p2']['id']);
											$winner = trim($match['p1']['id']);
										} else {
											$loser = trim($match['p1']['id']);
											$winner = trim($match['p2']['id']);
										}
										if ($this->debug_mode) { echo "<div style='color: #d66'>&raquo; <b>(Corrected)</b> ".$loser." -- ".$this->getPlayerById($loser)['tag']." (eliminated by ".$this->getPlayerById(trim($match['winner']))['tag']." in round $mi)</div>"; }
										array_push($used_player, $loser);
										$this->tournaments[$tournament_id]['events'][$event_id]['results'][$placing] = [];
										$this->tournaments[$tournament_id]['events'][$event_id]['results'][$placing][$this->getPlayerById($loser)['tag']] = array_merge(
											$this->getPlayerById($loser),
											[
												'lost_to' => ['tag' => '???'],
												'elim_by' => ['tag' => '???']
											]
										);
										if ($this->debug_mode) { echo "<div><b>(Corrected)</b> $cur &rarr; ".$this->getPlayerById($loser)['tag']." &rarr; $placing</div>"; }
										
										$placing = 1;
										if ($this->debug_mode) { echo "<div style='color: #6d6'>&raquo; <b>(Corrected)</b> ".$winner." -- ".$this->getPlayerById($winner)['tag']." (won the tournament in round $mi)</div>"; }
										if ($this->debug_mode) { echo "<div><b>(Corrected)</b> $cur &rarr; ".$this->getPlayerById($winner)['tag']." &rarr; $placing</div>"; }
										$this->tournaments[$tournament_id]['events'][$event_id]['results'][$placing][$this->getPlayerById($winner)['tag']] = array_merge(
											$this->getPlayerById($winner),
											[
												'lost_to' => ['tag' => '???'],
												'elim_by' => ['tag' => '???']
											]
										);
									}
									
								}
							}
						}
						
						$i++;
					}
					
					foreach ($this->tournaments[$tournament_id]['events'][$event_id]['results'] as $key=>$placing) {
						ksort($this->tournaments[$tournament_id]['events'][$event_id]['results'][$key]);
					}
				}
			}
		}
		
		return $this->tournaments;
	}

	/**
	 * Downloads and saves file to $cache_directory
	 */
	public function saveToCache() {
		try {
			$get_file = file_get_contents('');
		} catch(Exception $e) {
		}
	}
	
	public function saveToArchive() {
	}

	public function getPlayerById($id = '00000000-0000-0000-0000-000000000000', $seed = 0) {
		if (isset($this->players[$id])) {
			return array_merge($this->players[$id], ['seed' => $seed]);
		} else if (isset($this->teams[$id])) {
			// return $this->teams[$id];
			return array_merge($this->teams[$id], ['seed' => $seed]);
		} else {
			return $this->players[$id] = ['id' => $id, 'name' => NULL, 'tag' => NULL, 'location' => NULL, 'skill' => 0, 'seed' => $seed];
		}
	}
	
	public function getPlayers($key = -1) {
		if ($key !== -1) {
			$return_value = [];
			foreach ($this->players as $id=>$info) {
				array_push($return_value, $info[$key]);
			}
			return $return_value;
		} else {
			return $this->players;
		}
	}
	
	public function getTeams($key = -1) {
		if ($key !== -1) {
			$return_value = [];
			foreach ($this->teams as $id=>$info) {
				array_push($return_value, $info[$key]);
			}
			return $return_value;
		} else {
			return $this->teams;
		}
	}
	
	public function search($search) {
		$added_tournament_ids = [];
		$results = [
			'term' => $search,
			'total_results' => 0,
			'results' => [
				'brackets' => [],
				'players' => [],
				'teams' => []
			]
		];
		
		if ($this->search_debug) {
			echo '<pre style="background-color: #eef">';
			echo "Searching for: $search\n";
		}
		
		// Search for tournament names
		foreach ($this->getTournaments() as $id=>$tournament) {
			if ($this->search_debug) {
				echo "\n<div>Using {$tournament['id']}</div>";
			}
			
			similar_text($search, $tournament['name'], $similarity);
			
			if ($this->search_debug) {
				echo "<div> \\_ bracket::{$tournament['name']} => $similarity</div>";
			}
			
			if ($similarity >= $this->search_threshold['bracket']) {
				array_push($added_tournament_ids, $tournament['id']);
				array_push($results['results']['brackets'], $tournament);
			}
		}
		
		// Search for players/teams
		foreach ($this->getFiles() as $tid=>$files) {
			if (file_exists($files['info'])) {
				if ($this->search_debug) {
					echo "\n<div>Using $tid</div>";
				}
				
				$meta_json = json_decode(file_get_contents($files['info']), true);
				
                if (isset($meta_json['players'])) {
                    $cached_players = $meta_json['players'];
                }
                if (isset($meta_json['teams'])) {
                    $cached_teams = $meta_json['teams'];
                }
				
				// Search for a player
				foreach($cached_players as $pid=>$player) {
                    $has_clan_tag = (strpos($player['tag'], "|") > -1);
                    if ($has_clan_tag) $player['tag2'] = trim(explode("|", $player['tag'])[1]);
                    
                    $similarity_tag2 = 0;
					similar_text(strtolower($search), strtolower($player['tag']), $similarity_tag);
                    // Try it without a clan tag
                    if ($has_clan_tag) similar_text(strtolower($search), strtolower($player['tag2']), $similarity_tag2);
					similar_text(strtolower($search), strtolower($player['name']), $similarity_name);
					
					if ($this->search_debug) {
						echo "<div> \\_ tag::{$player['tag']} => $similarity_tag</div>";
                        if ($has_clan_tag) echo "<div> \\_ tag2::{$player['tag2']} => $similarity_tag2</div>";
						echo "<div> \\_ name::{$player['name']} => $similarity_name</div>";
					}
					
					if ($similarity_tag >= $this->search_threshold['tag'] || $similarity_tag2 >= $this->search_threshold['tag'] || $similarity_name >= $this->search_threshold['name']) {
						
						// Add the tournament they were in if it wasn't already added
						/* if (in_array($tid, $added_tournament_ids) === false) {
							array_push($added_tournament_ids, $tid);
							array_push($results['results']['brackets'], $this->getTournament($tid));
						} */
						
						$player_to_add = $player;
                        $player_to_add['tournament_info'] = $this->getTournament($tid);
						array_push($results['results']['players'], $player_to_add);
					}
				}
				
				// Search for a team
				foreach($cached_teams as $pid=>$team) {
					similar_text($search, $team['tag'], $similarity_tag);
					similar_text($search, $team['name'], $similarity_name);
					
					if ($this->search_debug) {
						echo "<div> \\_ tag_team::{$team['tag']} => $similarity_tag</div>";
						echo "<div> \\_ name_team::{$team['name']} => $similarity_name</div>";
					}
					
					if ($similarity_tag >= $this->search_threshold['tag_team'] || $similarity_name >= $this->search_threshold['name_team']) {
						
						// Add the tournament they were in if it wasn't already added
						/* if (in_array($tid, $added_tournament_ids) === false) {
							array_push($added_tournament_ids, $tid);
							array_push($results['results']['brackets'], $this->getTournament($tid));
						} */
						
						$team_to_add = $team;
                        $team_to_add['tournament_info'] = $this->getTournament($tid);
						array_push($results['results']['teams'], $team_to_add);
					}
				}
			}
		}
		
		$results['total_results'] = count($results['results']['brackets']) + count($results['results']['players']) + count($results['results']['teams']);
		
		if ($this->search_debug) {
			echo '</pre>';
			echo '<pre style="background-color: #efe">';
			var_dump($results);
			echo '</pre>';
			die;
		}
        
        $this->last_search_results = $results;
		return $results;
	}
	
	/**
	 * -----------
	 * -- Crons --
	 * -----------
	 */
	
	public function cleanBracketCron() {
		foreach ($this->getFiles() as $id=>$files) {
			$open_bracket = file_get_contents($files['bracket']);
			if ($this->validate()) {
				
			}
		}
	}
	
	public function playerCron() {
		try {
			foreach($this->getFiles() as $id=>$files) {
				// Open file
				$this->setTournament($id);
				$this->parseBracket();
				
				// Add players to meta
				$meta_json = json_decode(file_get_contents($files['info']), true);
				
				// Get players and teams
				$meta_get_players = $this->getPlayers();
				array_shift($meta_get_players);
				array_shift($meta_get_players);
				
				$meta_get_teams = $this->getTeams();
				array_shift($meta_get_teams);
				array_shift($meta_get_teams);
				
				// Add the info to the meta block
				$meta_json['players'] = $meta_get_players;
				$meta_json['teams'] = $meta_get_teams;
				
				file_put_contents($files['info'], json_encode($meta_json, JSON_PRETTY_PRINT));
				echo "$id => Found " . count($meta_get_players) . " and " . count($meta_get_teams) . " teams\n";
			 }
		} catch (Exception $e) {
			echo "Failed - $e";
		}
	}
}

$tio = new tioParser(['library' => LIBRARY, 'archive' => ARCHIVE, 'cache' => CACHE]);
if (isset($_GET['debug'])) {
	$tio->debug_mode = true;
}
?>