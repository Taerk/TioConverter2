<?php

// Enable the "<pre>" box for information
$tio->search_debug = false;

// Set search debug to true if ?debug is set
if (isset($_GET['debug'])) {
	$tio->search_debug = true;
}

// Change required matching level before a listing is considered "matched"
$tio->search_threshold = [
	"bracket" => 40,
	"tag" => 70,
	"name" => 70,
	"tag_team" => 50,
	"name_team" => 70
];

/**
 *
 *
 * The TioConverter2 search() function will make a string comparison between bracket names, player tags, and team tags (in addition to their names).
 * It will then take the score and if it's over a certain threshold, it adds it to the results. You can modify the % threshold with the search_threshold at the top of this page.
 * 
 *
 * $tio->search() output format:
 *
 * [
 * 		"term", // specifies what was searched
 *		"total_results", // the combined number of brackets, players, and teams found
 *		"results" { // all the results of the search
 *			"brackets" {
 *				[
 *					id,
 *					name,
 *					added,
 *					added_by,
 *					permalink,
 *					download,
 *					enabled,
 *					hidden,
 *					featured,
 *					default_event,
 *					update_interval,
 *					update_until,
 *				],
 *				...
 *			],
 *			"players" => [
 *				[
 *					id,
 *					name,
 *					tag,
 *					location,
 *					skill,
 *					tournament_id // The ID of the tournament the player was found in
 *				],
 *				...
 *			],
 *			"teams" => [
 *				[
 *					id,
 *					name,
 *					tag,
 *					location,
 *					skill,
 *					tournament_id // The ID of the tournament the team was found in
 *				],
 *				...
 *			]
 *		]
 * ]
 */

if ($tio->search_debug) { 
	$tio->search(SEARCH);
    die;
}

$tio->search(SEARCH);
?>

<!-- <pre><?php var_dump($tio->last_search_results); ?></pre> -->

<!-- Work below this line--><!-- Work below this line--><!-- Work below this line--><!-- Work below this line-->
<!-- Work below this line--><!-- Work below this line--><!-- Work below this line--><!-- Work below this line-->
<div style="padding: 10px">

    <h1>Searching for: <?php echo $tio->last_search_results['term'] ?></h1>
    <h2><?php echo $tio->last_search_results['total_results'] ?> results found</h2>

    <h3>Events Found (<?php echo count($tio->last_search_results['results']['brackets']); ?>)</h3>
    <ul>
    <?php
    foreach ($tio->last_search_results['results']['brackets'] as $key=>$tournament) {
        echo '<li><a href="/' . $tournament['permalink'] . '">' . $tournament['name'] . '</a></li>';
    }
    ?>
    </ul>
    
    <h3>Players Found (<?php echo count($tio->last_search_results['results']['players']); ?>)</h3>
    <ul>
    <?php
    foreach ($tio->last_search_results['results']['players'] as $key=>$player) {
        echo '<li><a href="/' . $player['tournament_info']['permalink'] . '">' . $player['tag'] . ' (' . $player['tournament_info']['name'] . ')</a></li>';
    }
    ?>
    </ul>
    
    <h3>Teams Found (<?php echo count($tio->last_search_results['results']['teams']); ?>)</h3>
    <ul>
    <?php
    foreach ($tio->last_search_results['results']['teams'] as $key=>$team) {
        echo '<li><a href="/' . $team['tournament_info']['permalink'] . '">' . $team['tag'] . ' (' . $team['tournament_info']['name'] . ')</a></li>';
    }
    ?>
    </ul>
    
</div>