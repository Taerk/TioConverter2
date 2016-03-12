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
	public	$players = [];
	public	$teams = [];
	public	$enable_results = true;
	public	$debug_mode = true;
	
	public	$logging = true;
	private	$settings = null;
	private	$settings_file_md5 = '';
	private	$session_id = null;
	private	$error_reporting_save = null;
	private	$error_reporting_set = E_ALL;
	
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
		
		// Load the settings (typically library.json)
		$this->loadSettings();
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
					$this->settings = json_decode(file_get_contents($this->settings_file), true);
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
	
	/**
	 * Get tournament id from library using a permalink or id
	 */
	public function getTournamentId($search = "") {		
		foreach ($this->settings['tournaments'] as $key=>$entry) {
			if ($search == $entry['id'] || $search == $entry['permalink']) {
				return $entry['id'];
			}
		}
		
		return "";
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
	
	public function parseBracket() {
		if (!isset($this->active_file) || $this->active_file == "") {
			$this->log("No active file set");
			return false;
		}
		
		$tio = simplexml_load_file($this->archive_directory."/".$this->active_file);
		$players = [];
		$players['00000000-0000-0000-0000-000000000000'] = ['name' => NULL, 'tag' => '&nbsp;', 'location' => NULL, 'skill' => 0];
		$players['00000001-0001-0001-0101-010101010101'] = ['name' => NULL, 'tag' => 'Bye', 'location' => NULL, 'skill' => 0];
		$teams = $players; // Copy setup of default players to $team
		$events = [];

		//Players
		if (isset($tio->PlayerList->Players)) {
			foreach ($tio->PlayerList->Players->Player as $key=>$player) {
				$player_id = trim((string)$player->ID);
				$this->players[$player_id] = [];
				$this->players[$player_id]['name'] = trim((string)$player->Name);
				$this->players[$player_id]['tag'] = trim((string)$player->Nickname);
				$this->players[$player_id]['location'] = trim((string)$player->Location);
				$this->players[$player_id]['skill'] = intval(trim($player->Skill));
				// print_r($player);
			}
		}
	
		// Teams
		if (isset($tio->PlayerList->Teams)) {
			foreach ($tio->PlayerList->Teams->Player as $key=>$team) {
				$team_id = trim((string)$team->ID);
				$this->teams[$team_id] = [];
				$this->teams[$team_id]['name'] = trim((string)$team->Name);
				$this->teams[$team_id]['tag'] = trim((string)$team->Nickname);
				$this->teams[$team_id]['location'] = trim((string)$team->Location);
				$this->teams[$team_id]['skill'] = intval(trim($team->Skill));
				// var_dump($team);
			}
		}
		
		// Events
		foreach ($tio->EventList->Event as $key=>$event) {
			$event_id = trim((string)$event->ID);
			$event_name = trim((string)$event->Name);
			
			$events[$event_id] = [];
			$events[$event_id]['name'] = $event_name;
			$events[$event_id]['stations'] = [];
			$events[$event_id]['stations_ez'] = [];
			$events[$event_id]['games'] = [];
			
			// Stations
			foreach ($event->Stations->Station as $key=>$station) {
				array_push($events[$event_id]['stations'], [
					'number' =>trim((int)$station->Number),
					'name' =>trim((string)$station->Name),
					'match_event' => trim((string)$station->Queue->Match->EventID),
					'match_id' => trim((string)$station->Queue->Match->Number)
				]);
			}
			
			// Stations EZ (Uses match ID as key to match stream name)
			foreach ($event->Stations->Station as $key=>$station) {
				$station_event_id = trim((string)$station->Queue->Match->EventID);
				$station_match_num = trim((string)$station->Queue->Match->Number);
				
				if (($station_event_id != "") && ($station_match_num != "")) {
					$events[$event_id]['stations_ez'][$station_event_id.":".$station_match_num] = trim((string)$station->Name);
				}
			}
			
			// Events -> Games
			foreach ($event->Games->Game as $key=>$game) {
				$game_id = trim((string)$game->ID);
				$game_event_name = trim((string)$game->Name);
				$game_name = trim((string)$game->GameName);
				$events[$event_id]['games'][$game_id] = [];
				$events[$event_id]['games'][$game_id]['name'] = $game_event_name;
				$events[$event_id]['games'][$game_id]['game'] = $game_name;
				$events[$event_id]['games'][$game_id]['entrants'] = count($game->Entrants->Entrant);
				$events[$event_id]['games'][$game_id]['seeds'] = [];
				$events[$event_id]['games'][$game_id]['matches'] = [];
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
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['key'] = $this->numToLetters($match->Number);
					
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['p1'] = $this->getPlayerById(trim((string)$match->Player1));
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['p1']['id'] = trim((string)$match->Player1);
					if (isset($events[$event_id]['games'][$game_id]['seeds'][trim((string)$match->Player1)])) {
						$events[$event_id]['games'][$game_id]['matches'][$match_number]['p1']['seed'] = $events[$event_id]['games'][$game_id]['seeds'][trim((string)$match->Player1)];
					} else {
						$events[$event_id]['games'][$game_id]['matches'][$match_number]['p1']['seed'] = -1;
					}
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['s1'] = trim((string)$match->Score->Player1Wins);
					
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['p2'] = $this->getPlayerById(trim((string)$match->Player2));
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
					$events[$event_id]['games'][$game_id]['matches'][$match_number]['round'] = intval(trim($match->Round));
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
				if ($this->enable_results) {
					/* ======================
					 * == RESULT CALCULATION
					 * ======================
					 * - Losers round 1 starts at -1
					 * - Winners round 1 starts at 1
					 */
					if ($this->debug_mode) { echo "<h1>".$events[$event_id]['name']."</h1>"; }
					
					// Declare variables to be used later
					$all_matches = [];
					$round = -1; // Current round to check through
					$max_rounds_loser = -1; // The total number of winner rounds
					$max_rounds_winner = -1; // The total number of loser rounds
					$player_count = $events[$event_id]['games'][$game_id]['entrants'];
					$placing = ($player_count - 1); // Base to start with (this is the number of players)
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
					foreach ($events[$event_id]['games'][$game_id]['matches'] as $key=>$match) {
						$this_matches_round = $match['round'];
						if (!isset($all_matches[$round])) {
							$all_matches[$round] = [];
						}
						
						// Add tags for convenience
						$all_matches[$this_matches_round][$match['id']] = $match;
						
						// The highest number will be the max_rounds variable
						if ($this_matches_round > 0 && abs($this_matches_round) > $max_rounds_winner) {
							$max_rounds_winner = abs($this_matches_round);
						} else if ($this_matches_round < 0 && abs($this_matches_round) > $max_rounds_loser) {
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
						echo "<h2>".$events[$event_id]['games'][$game_id]['name']."</h2>";
						echo "<h3>Number of Players: $player_count</h3>";
						echo "<h3>Winner Rounds: $max_rounds_winner</h3>";
						echo "<h3>Loser Rounds: $max_rounds_loser</h3>";
					}
					
					if ($this->debug_mode) { echo "<div style='color: #fbf'>-- start auto --</div>"; }
					
					// Cycle through losers
					$prev_round = $max_rounds_loser * -1;
					$current_winner_placing = [];
					$current_loser_placing = [];
					$matches_in_this_round = -1;
					
					for ($i = 1; $i < $max_rounds_loser; $i++) {
						$mi = $i * -1;
						$matches_in_this_round = count($all_matches[$mi]);
						if ($this->debug_mode) { echo "<div style='color: #fbf'>-- $matches_in_this_round match(es) in this round --</div>"; }
						if (isset($all_matches[$mi])) {
							$checked_matches = 0;
							foreach ($all_matches[$mi] as $match) {
								$checked_matches++;
								
								if ($match['winner'] != "00000000-0000-0000-0000-000000000000") {								
									// Add winner to array of players who have already been assigned a placing
									/* if (!in_array(trim($match->Winner), $used_player) == -1) {
										if ($this->debug_mode) { echo "<div style='color: #bfb'>&raquo; ".trim($match->Winner)." -- ".$this->getPlayerById(trim($match->Winner))['tag']."</div>"; }
										array_push($used_player, trim($match->Winner));
										array_push($current_winner_placing, trim($match->Winner));
									} */
									
									// Add loser to array of players who have already been assigned a placing
									if (trim($match['winner']) == trim($match['p1']['id'])) {
										$loser = trim($match['p2']['id']);
									} else {
										$loser = trim($match['p1']['id']);
									}
									if (!in_array($loser, $used_player) == -1) {
										if ($this->debug_mode) { echo "<div style='color: #fbb'>&raquo; ".$loser." -- ".$this->getPlayerById($loser)['tag']." (eliminated by ".$this->getPlayerById(trim($match['winner']))['tag']." in round $mi)</div>"; }
										array_push($used_player, $loser);
										array_push($current_loser_placing, $loser);
									}
									
									if ($checked_matches == $matches_in_this_round) {
										// Add winners
										if (!isset($events[$event_id]['games'][$game_id]['results'][$placing])) {
											$events[$event_id]['games'][$game_id]['results'][$placing] = [];
										}
										foreach ($current_winner_placing as $key=>$cur) {
											if ($this->debug_mode) { echo "<div>$cur &rarr; ".$this->getPlayerById($cur)['tag']." &rarr; $placing</div>"; }
											$events[$event_id]['games'][$game_id]['results'][$placing][$this->getPlayerById($cur)['tag']] = $this->getPlayerById($cur);
										}
										$placing -= count($current_winner_placing);
										
										// Add Losers
										if (!isset($events[$event_id]['games'][$game_id]['results'][$placing])) {
											$events[$event_id]['games'][$game_id]['results'][$placing] = [];
										}
										foreach ($current_loser_placing as $key=>$cur) {
											if ($this->debug_mode) { echo "<div>$cur &rarr; ".$this->getPlayerById($cur)['tag']." &rarr; $placing</div>"; }
											$events[$event_id]['games'][$game_id]['results'][$placing][$this->getPlayerById($cur)['tag']] = $this->getPlayerById($cur);
										}
										$placing -= count($current_loser_placing);
										
										$prev_round = $mi;
										$current_winner_placing = [];
										$current_loser_placing = [];
									}
								}
								
							}
						}
					}
					
					/* // Check GF Set 1
					$round_last = $all_matches[$max_rounds_winner - 1];
					foreach ($round_last as $match) {
						if ($match->Winner != "00000000-0000-0000-0000-000000000000") {
							if (!in_array(trim($match->Winner), $used_player) == -1) {
								if (!isset($events[$event_id]['games'][$game_id]['results'][$placing])) {
									$events[$event_id]['games'][$game_id]['results'][$placing] = [];
								}
								array_push($used_player, trim($match->Winner));
								$events[$event_id]['games'][$game_id]['results'][$placing][$this->getPlayerById(trim($match->Winner))['tag']] = $this->getPlayerById(trim($match->Winner));
								
								if ($this->debug_mode) { echo "<div>".trim($match->Winner)." -- ".$this->getPlayerById(trim($match->Winner))['tag']." &rarr; $placing</div>"; }
								$placing--;
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
								if ($this->debug_mode) { echo "<div>".$loser." -- ".$this->getPlayerById($loser)['tag']." &rarr; $placing</div>"; }
								array_push($used_player, $loser);
								$events[$event_id]['games'][$game_id]['results'][$placing][$this->getPlayerById($loser)['tag']] = $this->getPlayerById($loser);
								$placing--;
							}
						}
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
								$events[$event_id]['games'][$game_id]['results'][$placing][$this->getPlayerById(trim($match->Winner))['tag']] = $this->getPlayerById(trim($match->Winner));
								
								if ($this->debug_mode) { echo "<div>".trim($match->Winner)." -- ".$this->getPlayerById(trim($match->Winner))['tag']." &rarr; $placing</div>"; }
								$placing--;
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
								if ($this->debug_mode) { echo "<div>".$loser." -- ".$this->getPlayerById($loser)['tag']." &rarr; $placing</div>"; }
								array_push($used_player, $loser);
								$events[$event_id]['games'][$game_id]['results'][$placing][$this->getPlayerById($loser)['tag']] = $this->getPlayerById($loser);
								$placing--;
							}
						}
					} */
					
					foreach ($events[$event_id]['games'][$game_id]['results'] as $key=>$placing) {
						ksort($events[$event_id]['games'][$game_id]['results'][$key]);
					}
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
		if (isset($this->players[$id])) {
			return $this->players[$id];
		} else if (isset($this->teams[$id])) {
			return $this->teams[$id];
		} else {
			return $this->players[$id] = ['name' => NULL, 'tag' => NULL, 'location' => NULL, 'skill' => 0];
		}
	}
}

$tio = new tioParser(['settings' => LIBRARY, 'archive' => ARCHIVE, 'cache' => CACHE]);

// $tio_cache = simplexml_load_file($cache_dir.$cache_file); 
// $archive_id = trim((string)$tio_cache->EventList->Event->ID);
// $archive_name = trim((string)$tio_cache->EventList->Event->Name);
?>