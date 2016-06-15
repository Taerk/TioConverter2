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
}
?>

<!-- Work below this line--><!-- Work below this line--><!-- Work below this line--><!-- Work below this line-->
<!-- Work below this line--><!-- Work below this line--><!-- Work below this line--><!-- Work below this line-->

<br>

<h1>Player: <?php echo(($tio->search(SEARCH))['term']) ?></h1>
<h2>Events Attended: <?php echo(($tio->search(SEARCH))['total_results']) ?></h2>

<h3>Events Attended</h3>

<ul>
<?php 
for($x = 0; $x <= 3; $x++) { ?>
	<li>
		<?php echo(($tio->search(SEARCH))['results']['brackets'][$x]['name']) ?>
	</li>
<?php } ?>

</ul>