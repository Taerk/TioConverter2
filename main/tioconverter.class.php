<?php

if (strpos($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']) !== false) {
	require_once('../settings.php');
}

class tioParser {
	public 	$archive_directory = "archive";
	public 	$cache_directory = "cache";
	public 	$settings_file = "settings.json";
	public 	$active_file;
	public 	$cache_file;
	public 	$tio_file;
	public 	$events = [];
	public 	$files = [];
	public	$library = [];
	
	private $debug_mode = false;
	private $logging = true;
	private	$settings = null;
	private $settings_file_md5 = '';
	private $session_id = null;
	private $error_reporting_save = null;
	private $error_reporting_set = E_ALL;
	
	function __construct($options) {
		// Give a random session ID
		$this->session_id = uniqid('');
		
		// Set custom level of error reporting
		$this->error_reporting_save = ini_get("error_reporting");
		error_reporting($this->error_reporting_set);
		
		// Set up file locations
		if (isset($options['settings'])) {
			$this->settings_file = $options['settings'];
		}
		if (isset($options['archive'])) {
			$this->archive_directory = $options['archive'];
		}
		if (isset($options['cache'])) {
			$this->cache_directory = $options['cache'];
		}
		
		// Set up file structure
		$this->setup();
		
		// Scans for files
		$this->scan();
	}
	
	function __destruct() {
		// Reset error reporting to previous level
		error_reporting($this->error_reporting_save);
	}
	
	/**
	 * Logs input to $this->log_file
	 */
	public function log($input = null) {
		if ($this->logging) {
			if (function_exists('custom_log')) {
				custom_log($this->session_id, $input);
			} else {
				error_log($input);
			}
		}
	}
	
	/**
	 * Used to generate a filename from the current date
	 */
	public function getDate() {
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
		$this->debug_mode = true;
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
		$this->library = json_decode(file_get_contents($this->settings_file), true);
		
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
						case (strpos($archived_file, ".tio") !== false):
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
	 * Returns an array of events
	 *
	 * Array (
	 * 	'$tournamentId' => '$tournamentName'
	 * )
	 */
	public function getEvents() {
		return $this->events;
	}
	
	/**
	 * Returns an array of archived tournament files
	 *
	 * Array (
	 * 	$id => '$fileName'
	 * )
	 */
	public function getArchive() {
		return $this->files;
	}
	
	/**
	 * Alias for getArchive()
	 */
	public function getArchives() {
		return $this->getArchive();
	}
	
	/**
	 * Returns an array of cached tournament files
	 *
	 * Array (
	 * 	$id => '$fileName'
	 * )
	 */
	public function getCache() {
		return false;
	}
	
	/**
	 * Checks to see if there are any additional updates to the
	 * settings file.
	 * 
	 * Will return true if there is, false if there
	 * isn't or the file doesn't exit.
	 */
	public function loadSettings($file = "") {
		// Fail if the file is not found
		if (!file_exists($this->settings_file)) {
			$this->log($this->settings_file." not found");
			return 1;
		} else {
			$current_md5 = md5_file($this->settings_file);
			if ($current_md5 != $this->settings_file_md5) {
				try {
					$this->settings = json_decode(file_get_contents($this->settings_file));
					$this->settings_file_md5 = $current_md5;
					$this->log("Loaded ".$this->settings_file);
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
	 * Get settings file
	 */
	public function getSettings() {
		return $this->settings;
	}
	
	public function parseBracket() {		
		if (!isset($this->active_file) || $this->active_file == "") {
			$this->log("No active file set");
			return false;
		}
		
		$tio = simplexml_load_file($this->archive_directory."/".$this->active_file);
		$players = [];
		$players['00000000-0000-0000-0000-000000000000'] = ['name' => NULL, 'tag' => '&nbsp;', 'location' => NULL, 'skill' => NULL];
		$players['00000001-0001-0001-0101-010101010101'] = ['name' => NULL, 'tag' => 'Bye', 'location' => NULL, 'skill' => NULL];
		$events = [];

		//Players
		foreach ($tio->PlayerList->Players->Player as $key=>$player) {
			$player_id = trim((string)$player->ID);
			$players[$player_id] = [];
			$players[$player_id]['name'] = trim((string)$player->Name);
			$players[$player_id]['tag'] = trim((string)$player->Nickname);
			$players[$player_id]['location'] = trim((string)$player->Location);
			$players[$player_id]['skill'] = trim((string)$player->Skill);
			// print_r($player);
		}
		
		// Events
		foreach ($tio->EventList->Event as $key=>$event) {
			$event_id = trim((string)$event->ID);
			$event_name = trim((string)$event->Name);
			
			$events[$event_id] = [];
			$events[$event_id]['name'] = $event_name;
			$events[$event_id]['games'] = [];
			$events[$event_id]['stations'] = [];
			$events[$event_id]['stations_ez'] = [];
			
			// Events -> Games
			foreach ($event->Games->Game as $key=>$game) {
				$game_id = trim((string)$game->ID);
				$game_event_name = trim((string)$game->Name);
				$game_name = trim((string)$game->GameName);
				$events[$event_id]['games'][$game_id] = [];
				$events[$event_id]['games'][$game_id]['name'] = $game_event_name;
				$events[$event_id]['games'][$game_id]['game'] = $game_name;
				$events[$event_id]['games'][$game_id]['entrants'] = count($game->Entrants->Entrant);
				$events[$event_id]['games'][$game_id]['matches'] = [];
				$events[$event_id]['games'][$game_id]['seeds'] = [];
				$events[$event_id]['games'][$game_id]['results'] = [];
				
				foreach ($game->Entrants->Entrant as $key=>$seed) {
					$events[$event_id]['games'][$game_id]['seeds'][trim((string)$seed->PlayerID)] = (int)$seed->Seed;
				}
				asort($events[$event_id]['games'][$game_id]['seeds']);
				
				// Events -> Games -> Bracket
				foreach ($game->Bracket->Matches->Match as $key=>$match) {
					$match_number = (int)$match->Number;
					$events[$event_id]['games'][$game_id]['matches'][$match_number] = [];
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['id'] = (int)$match->Number;
					
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['p1'] = getPlayerById(trim((string)$match->Player1));
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['p1']['id'] = trim((string)$match->Player1);
					if (isset($events[$event_id]['games'][$game_id]['seeds'][trim((string)$match->Player1)])) {
						$events[$event_id]['games'][$game_id]['matches'][$match_number]['p1']['seed'] = $events[$event_id]['games'][$game_id]['seeds'][trim((string)$match->Player1)];
					} else {
						$events[$event_id]['games'][$game_id]['matches'][$match_number]['p1']['seed'] = -1;
					}
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['s1'] = trim((string)$match->Score->Player1Wins);
					
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['p2'] = getPlayerById(trim((string)$match->Player2));
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['p2']['id'] = trim((string)$match->Player2);
					if (isset($events[$event_id]['games'][$game_id]['seeds'][trim((string)$match->Player2)])) {
						$events[$event_id]['games'][$game_id]['matches'][$match_number]['p2']['seed'] = $events[$event_id]['games'][$game_id]['seeds'][trim((string)$match->Player2)];
					} else {
						$events[$event_id]['games'][$game_id]['matches'][$match_number]['p2']['seed'] = -1;
					}
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['s2'] = trim($match->Score->Player2Wins);
					
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['winner'] = trim(($match->Winner));
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['p1_prev'] = intval(trim($match->Player1PrevMatch));
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['p1_next'] = intval(trim($match->Player2PrevMatch));
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['winner_next'] = intval(trim($match->WinnerNextMatch));
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['loser_next'] = intval(trim($match->LoserNextMatch));
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['nextsibiling'] = intval(trim($match->NextSiblingMatch));
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['prevsibling'] = intval(trim($match->PrevSiblingMatch));
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['round'] = trim($match->Round);
					if (trim((string)$match->InProgress) == "True") {
						$events[$event_id]['games'][$game_id]['matches'][$match_number]['in_progress'] = true;
					} else {
						$events[$event_id]['games'][$game_id]['matches'][$match_number]['in_progress'] = false;
					}
					if (trim((string)$match->IsWinners) == "True") {
						$events[$event_id]['games'][$game_id]['matches'][$match_number]['winners'] = true;
					} else {
						$events[$event_id]['games'][$game_id]['matches'][$match_number]['winners'] = false;
					}
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['label'] = trim((string)$match->Label);
					if (trim((string)$match->IsChampionship) == "True") {
						$events[$event_id]['games'][$game_id]['matches'][$match_number]['is_championship'] = true;
					} else {
						$events[$event_id]['games'][$game_id]['matches'][$match_number]['is_championship'] = false;
					}
					if (trim((string)$match->IsSecondChampionship) == "True") {
						$events[$event_id]['games'][$game_id]['matches'][$match_number]['is_second_championship'] = true;
					} else {
						$events[$event_id]['games'][$game_id]['matches'][$match_number]['is_second_championship'] = false;
					}
				}
				
				
				// Events -> Results
				if ($enable_results) {
					if ($debug) { echo "<h1>".$events[$event_id]['name']."</h1>"; }
					
					$all_matches = [];
					$round = -1;
					$max_rounds_loser = -1;
					$max_rounds_winner = -1;
					foreach ($game->Bracket->Matches->Match as $key=>$match) {
						$round = (int)trim((string)$match->Round);
						if (!isset($all_matches[$round])) {
							$all_matches[$round] = [];
						}
						$all_matches[$round][(int)$match->Number] = $match;
						
						if (($round > 0) && (abs($round) > $max_rounds_winner)) {
							$max_rounds_winner = abs($round);
						} else if (($round < 0) && (abs($round) > $max_rounds_loser)) {
							$max_rounds_loser = abs($round);
						}
					}
					ksort($all_matches);
					
					$placing = 1;
					$prev_round = 0;
					$used_player = ['00000000-0000-0000-0000-000000000000', '00000001-0001-0001-0101-010101010101'];
					if ($debug) {
						echo "<h2>".$events[$event_id]['games'][$game_id]['name']."</h2>";
						echo "<h3>Winner Rounds: $max_rounds_winner</h3>";
						echo "<h3>Loser Rounds: $max_rounds_loser</h3>";
					}
					
					// Check GF Set 2
					$round_last = $all_matches[$max_rounds_winner];
					foreach ($round_last as $match) {
						if ($match->Winner != "00000000-0000-0000-0000-000000000000") {
							if (!in_array(trim($match->Winner), $used_player) == -1) {
								if (!isset($events[$event_id]['games'][$game_id]['results'][$placing])) {
									$events[$event_id]['games'][$game_id]['results'][$placing] = [];
								}
								array_push($used_player, trim($match->Winner));
								$events[$event_id]['games'][$game_id]['results'][$placing][getPlayerById(trim($match->Winner))['tag']] = getPlayerById(trim($match->Winner));
								
								if ($debug) { echo "<div>".trim($match->Winner)." -- ".getPlayerById(trim($match->Winner))['tag']." &rarr; $placing</div>"; }
								$placing++;
							}
							if (!isset($events[$event_id]['games'][$game_id]['results'][$placing])) {
								$events[$event_id]['games'][$game_id]['results'][$placing] = [];
							}
							if (trim($match->Winner) == trim($match->Player1)) {
								$loser = trim($match->Player2);
							} else {
								$loser = trim($match->Player1);
							}
							
							if (!in_array($loser, $used_player) == -1) {
								if ($debug) { echo "<div>".$loser." -- ".getPlayerById($loser)['tag']." &rarr; $placing</div>"; }
								array_push($used_player, $loser);
								$events[$event_id]['games'][$game_id]['results'][$placing][getPlayerById($loser)['tag']] = getPlayerById($loser);
								$placing++;
							}
						}
					}
					
					// Check GF Set 1
					$round_last = $all_matches[$max_rounds_winner - 1];
					foreach ($round_last as $match) {
						if ($match->Winner != "00000000-0000-0000-0000-000000000000") {
							if (!in_array(trim($match->Winner), $used_player) == -1) {
								if (!isset($events[$event_id]['games'][$game_id]['results'][$placing])) {
									$events[$event_id]['games'][$game_id]['results'][$placing] = [];
								}
								array_push($used_player, trim($match->Winner));
								$events[$event_id]['games'][$game_id]['results'][$placing][getPlayerById(trim($match->Winner))['tag']] = getPlayerById(trim($match->Winner));
								
								if ($debug) { echo "<div>".trim($match->Winner)." -- ".getPlayerById(trim($match->Winner))['tag']." &rarr; $placing</div>"; }
								$placing++;
							}
							if (!isset($events[$event_id]['games'][$game_id]['results'][$placing])) {
								$events[$event_id]['games'][$game_id]['results'][$placing] = [];
							}
							if (trim($match->Winner) == trim($match->Player1)) {
								$loser = trim($match->Player2);
							} else {
								$loser = trim($match->Player1);
							}
							
							if (!in_array($loser, $used_player) == -1) {
								if ($debug) { echo "<div>".$loser." -- ".getPlayerById($loser)['tag']." &rarr; $placing</div>"; }
								array_push($used_player, $loser);
								$events[$event_id]['games'][$game_id]['results'][$placing][getPlayerById($loser)['tag']] = getPlayerById($loser);
								$placing++;
							}
						}
					}
					
					if ($debug) { echo "<div style='color: #fbf'>-- start auto --</div>"; }
					
					// Cycle through losers
					$prev_round = $max_rounds_loser * -1;
					$current_winner_placing = [];
					$current_loser_placing = [];
					$matches_in_this_round = -1;
					for ($i = $max_rounds_loser; $i > 0; $i--) {
						$mi = $i * -1;
						$matches_in_this_round = count($all_matches[$mi]);
						if ($debug) { echo "<div style='color: #fbf'>-- $matches_in_this_round match(es) in this round --</div>"; }
						if (isset($all_matches[$mi])) {
							$checked_matches = 0;
							foreach ($all_matches[$mi] as $match) {
								$checked_matches++;
								
								if ($match->Winner != "00000000-0000-0000-0000-000000000000") {								
									// Add Winner to Array
									/* if (!in_array(trim($match->Winner), $used_player) == -1) {
										if ($debug) { echo "<div style='color: #bfb'>&raquo; ".trim($match->Winner)." -- ".getPlayerById(trim($match->Winner))['tag']."</div>"; }
										array_push($used_player, trim($match->Winner));
										array_push($current_winner_placing, trim($match->Winner));
									} */
									
									// Add Loser to Array
									if (trim($match->Winner) == trim($match->Player1)) {
										$loser = trim($match->Player2);
									} else {
										$loser = trim($match->Player1);
									}
									if (!in_array($loser, $used_player) == -1) {
										if ($debug) { echo "<div style='color: #fbb'>&raquo; ".$loser." -- ".getPlayerById($loser)['tag']." (eliminated by ".getPlayerById(trim($match->Winner))['tag']." in round $mi)</div>"; }
										array_push($used_player, $loser);
										array_push($current_loser_placing, $loser);
									}
									
									if ($checked_matches == $matches_in_this_round) {
										// Add winners
										if (!isset($events[$event_id]['games'][$game_id]['results'][$placing])) {
											$events[$event_id]['games'][$game_id]['results'][$placing] = [];
										}
										foreach ($current_winner_placing as $key=>$cur) {
											if ($debug) { echo "<div>$cur &rarr; ".getPlayerById($cur)['tag']." &rarr; $placing</div>"; }
											$events[$event_id]['games'][$game_id]['results'][$placing][getPlayerById($cur)['tag']] = getPlayerById($cur);
										}
										$placing += count($current_winner_placing);
										
										// Add Losers
										if (!isset($events[$event_id]['games'][$game_id]['results'][$placing])) {
											$events[$event_id]['games'][$game_id]['results'][$placing] = [];
										}
										foreach ($current_loser_placing as $key=>$cur) {
											if ($debug) { echo "<div>$cur &rarr; ".getPlayerById($cur)['tag']." &rarr; $placing</div>"; }
											$events[$event_id]['games'][$game_id]['results'][$placing][getPlayerById($cur)['tag']] = getPlayerById($cur);
										}
										$placing += count($current_loser_placing);
										
										$prev_round = $mi;
										$current_winner_placing = [];
										$current_loser_placing = [];
									}
								}
								
							}
						}
					}
					foreach ($events[$event_id]['games'][$game_id]['results'] as $key=>$placing) {
						ksort($events[$event_id]['games'][$game_id]['results'][$key]);
					}
				}
			}
			
			foreach ($event->Stations->Station as $key=>$station) {
				$station_number = (int)$station->Number;
				$events[$event_id]['stations'][$station_number] = [];
				$events[$event_id]['stations'][$station_number]['name'] = trim((string)$station->Name);
				$events[$event_id]['stations'][$station_number]['match_event'] = trim((string)$station->Queue->Match->EventID);
				$events[$event_id]['stations'][$station_number]['match_id'] = trim((string)$station->Queue->Match->Number);
			}
			
			foreach ($event->Stations->Station as $key=>$station) {
				$station_event_id = trim((string)$station->Queue->Match->EventID);
				$station_match_num = trim((string)$station->Queue->Match->Number);
				
				if (($station_event_id != "") && ($station_match_num != "")) {
					$events[$event_id]['stations_ez'][$station_event_id.":".$station_match_num] = trim((string)$station->Name);
				}
			}
		}
		
		return $events;
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
	
	public function cmp($a, $b) {
		return strcmp($a["tag"], $b["tag"]);
	}

	public function getPlayerById($id = '00000000-0000-0000-0000-000000000000') {
		global $players;
		if (isset($players[$id])) {
			return $players[$id];
		} else {
			return $players[$id] = ['name' => NULL, 'tag' => NULL, 'location' => NULL, 'skill' => NULL];
		}
	}
}

$tio = new tioParser(['settings' => LIBRARY, 'archive' => ARCHIVE, 'cache' => CACHE]);

// $tio_cache = simplexml_load_file($cache_dir.$cache_file); 
// $archive_id = trim((string)$tio_cache->EventList->Event->ID);
// $archive_name = trim((string)$tio_cache->EventList->Event->Name);
?>