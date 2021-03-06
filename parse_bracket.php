<?php
require_once('class.tioconverter.php');

if (isset($_GET['debug'])) {
	$debug = true;
} else {
	$debug = false;
}

ini_set('xdebug.var_display_max_children', 5000);
ini_set('xdebug.var_display_max_depth', 7);
error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);

$cache_dir = "cache/";
$cache_file = date('mdy-Hi').".tio";

$get_defaults = file_get_contents('defaults.txt');
$get_defaults_split = explode("\n", str_replace("\r", "", $get_defaults));
$dropbox_link = trim(str_replace("dl=0", "dl=1", $get_defaults_split[0]));
$default_event = (isset($get_defaults_split[1])) ? (int)$get_defaults_split[1] : 0;
$default_game  = (isset($get_defaults_split[2])) ? (int)$get_defaults_split[2] : 0;
$download_from_dropbox = ($get_defaults_split[3] == "true") ? true : false;
$enable_results = ($get_defaults_split[4] == "true") ? true : false;

$default = (int)$get_defaults_split[1];

$event_list = [];
$archives = scandir('archive');
foreach ($archives as $archive) {
	if (strpos($archive, "]_") > -1) {
		$t = explode("]_", $archive);
		$event_list[str_replace("[", "", $t[0])] = trim(str_replace(".tio", "", str_replace("_", " ", $t[1])));
	}
}
$event_list['_default'] = ['event' => $default_event, 'game' => $default_game];
asort($event_list);

$i = 1;
$default_event = "";
foreach ($event_list as $key=>$event) {
	// echo $i." vs ".$default."\n";
	if ($i == $default) {
		$default_event = $key;
	}
	$i++;
}

if (isset($_GET['events'])) {
	echo json_encode($event_list);
	die;
}

if ((!file_exists($cache_file)) && ($download_from_dropbox)) {
	$dropbox_tio_file = file_get_contents($dropbox_link);
	file_put_contents($cache_dir.$cache_file, $dropbox_tio_file);
	chmod($cache_dir.$cache_file, 0644);
	
	$tio_cache = simplexml_load_file($cache_dir.$cache_file);
	$archive_id = trim((string)$tio_cache->EventList->Event->ID);
	$archive_name = trim((string)$tio_cache->EventList->Event->Name);
	
	// Save as archive when md5 is different
	$archive_path = str_replace(" ", "_", "archive/[$archive_id]_$archive_name.tio");
	if ((trim($archive_id) != "") && (!file_exists($archive_path)) || (md5_file($archive_path) != md5_file($cache_dir.$cache_file))) {
		if (file_exists($archive_path)) {
			unlink($archive_path);
		}
		file_put_contents($archive_path, $dropbox_tio_file);
		chmod($archive_path, 0644);
	}
	
	// Clear cache
	$deleted = 0;
	$cache = scandir('cache/');
	foreach ($cache as $scan_cached_file) {
		if (($scan_cached_file != ".") && ($scan_cached_file != "..") && ($scan_cached_file != $cache_file)) {
			$deleted++;
			unlink($cache_dir.$scan_cached_file);
		}
	}
}

function cmp($a, $b) {
    return strcmp($a["tag"], $b["tag"]);
}

function getPlayerById($id = '00000000-0000-0000-0000-000000000000') {
	global $players;
	if (isset($players[$id])) {
		return $players[$id];
	} else {
		return $players[$id] = ['name' => NULL, 'tag' => NULL, 'location' => NULL, 'skill' => NULL];
	}
}

if (isset($_GET['get']) || isset($_GET['agna']) || $debug) {	
	$archived_files = scandir("archive/");
	$selected_file = "";
	foreach ($archived_files as $file) {
		if (isset($_GET['get'])) {
			if (strpos($file, $_GET['get']) > -1) {
				$selected_file = $file;
			}
		} else {
			if (strpos($file, $default_event) > -1) {
				$selected_file = $file;
			}
		}
	}
	
	if (isset($_GET['md5'])) {
		header('Content-Type: text/plain');
		if ($selected_file == "") {
			echo "-1";
		} else {
			echo md5_file("archive/".$selected_file);
		}
		die;
	}
	
	if ($selected_file == "") {
		$empty = [];
		echo json_encode($empty);
		die;
	}
	
	$tio = simplexml_load_file("archive/".$selected_file);
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
				$events[$event_id]['games'][$game_id]['matches'][$match_number]['s2'] = trim((string)$match->Score->Player2Wins);
				
				$events[$event_id]['games'][$game_id]['matches'][$match_number]['winner'] = trim((string)$match->Winner);
				$events[$event_id]['games'][$game_id]['matches'][$match_number]['p1_prev'] = (int)trim((string)$match->Player1PrevMatch);
				$events[$event_id]['games'][$game_id]['matches'][$match_number]['p1_next'] = (int)trim((string)$match->Player2PrevMatch);
				$events[$event_id]['games'][$game_id]['matches'][$match_number]['winner_next'] = (int)trim((string)$match->WinnerNextMatch);
				$events[$event_id]['games'][$game_id]['matches'][$match_number]['loser_next'] = (int)trim((string)$match->LoserNextMatch);
				$events[$event_id]['games'][$game_id]['matches'][$match_number]['nextsibiling'] = (int)trim((string)$match->NextSiblingMatch);
				$events[$event_id]['games'][$game_id]['matches'][$match_number]['prevsibling'] = (int)trim((string)$match->PrevSiblingMatch);
				$events[$event_id]['games'][$game_id]['matches'][$match_number]['round'] = trim((string)$match->Round);
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
	
	if (isset($_GET['agna'])) {
		require('agna-parse.php');
		die;
	}
	
	if (!$debug) {
		header('Content-Type: application/json');
		echo json_encode($events);
		die;
	}
}

?>