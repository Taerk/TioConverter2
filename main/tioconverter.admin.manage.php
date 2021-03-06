<form action="?action=update" method="post" class="form-horizontal form-group" role="form" id="update_bracket">
	<div class="page-header"><h1>Manage Bracket</h1></div>
	
	<label for="basic-url">Select Bracket</label>
	<?php
	function sortTournaments($a, $b) {
		// Remove Smash 4 from the name
		$a['name'] = str_replace('Smash 4', '', $a['name']);
		$b['name'] = str_replace('Smash 4', '', $b['name']);
		
		if ($a['name'] == $b['name']) {
			return 0;
		} else {
			// Sort by full number (windows style)
			preg_match('/[0-9]+/', $a['name'], $matches_a);
			preg_match('/[0-9]+/', $b['name'], $matches_b);
			
			if (isset($matches_a[0]) && isset($matches_b[0])) {
				return (intval($matches_a[0]) > intval($matches_b[0]) ? -1 : 1);
			}
			
			return ($a['name'] > $b['name'] ? -1 : 1);
		}
	}
	
	$sorted_tournaments = $tio->getTournaments();
	usort($sorted_tournaments, "sortTournaments");
	?>
	<select size="8" class="form-control" id="update_bracket_select">
	<?php
	foreach ($sorted_tournaments as $id=>$tournament) {
		$tournament['events'] = $tio->getEvents($tournament['id']);
		echo '<option value="'.$tournament['id'].'" tournament-info=\'' . json_encode($tournament) . '\'>'.$tournament['name'].'</option>';
	}
	?></select>
	<h5><a class="text-danger pull-right" href="../main/tioconverter.cron.cleaner.php" style="text-decoration: none">Remove Invalid Brackets</a></h5>
	
	<div id="update_bracket_confirm">
		<div class="page-header"><h3>Update Information</h3></div>
		<div class="input-group">
			<span class="input-group-addon" id="tio-tourney-id-tip" name="tio-tourney-id-tip">Tio ID</span>
			<input type="text" class="form-control" id="tio-tourney-id" name="tio-tourney-id" aria-describedby="tio-tourney-id-tip" readonly>
		</div>
		
		<br>
		
		<div class="input-group">
			<span class="input-group-addon" id="tio-tourney-download-tip" name="tio-tourney-id-tip">Download URL</span>
			<input type="text" class="form-control" id="tio-tourney-download" name="tio-tourney-download" aria-describedby="tio-tourney-download-tip">
		</div>
		
		<br>
	
		<div class="form-group">
			<div class="col-xs-6">
				<label for="tio-tourney-name">Display Name</label>
				<div class="input-group">
					<span class="input-group-btn">
						<input id="tio-tourney-hidden" name="tio-tourney-hidden" type="hidden" value="0">
						<button class="btn btn-success" id="tio-tourney-hidden-switch" name="tio-tourney-hidden-switch" type="button" value="0" title="Display Bracket"><span class="fa fa-check fa-fw"></span></button>
					</span>
					
					<input type="text" class="form-control" id="tio-tourney-name" name="tio-tourney-name">
					
					<span class="input-group-btn">
						<input id="tio-tourney-featured" name="tio-tourney-featured" type="hidden" value="0">
						<button class="btn btn-default" id="tio-tourney-featured-switch" name="tio-tourney-featured-switch" type="button" value="0" title="Featured Bracket"><span class="fa fa-star-o fa-fw"></span></button>
					</span>
				</div>
			</div>
			
			<div class="col-xs-6">
				<label for="tio-tourney-permalink">Permalink</label>
				<div class="input-group">
					<input type="text" class="form-control" id="tio-tourney-permalink" name="tio-tourney-permalink">
					<span class="input-group-btn">
						<button class="btn btn-success" id="tio-tourney-permalink-auto" name="tio-tourney-permalink-auto" type="button" value="1">Auto</button>
					</span>
				</div>
			</div>
		</div>
			
		<div class="form-group">
			<div class="col-xs-5">
				<label for="tio-tourney-default">Default Event</label>
				<select class="form-control" id="tio-tourney-default" name="tio-tourney-default"></select>
			</div>
			
			<div class="col-xs-3">
				<label for="tio-update-interval">Check Interval</label>
				<div class="input-group">
					<input type="text" class="form-control" id="tio-update-interval" name="tio-update-interval" value="60">
					<span class="input-group-addon" id="tio-update-interval-addon" name="tio-update-interval-addon">sec.</span>
				</div>
			</div>
			
			<div class="col-xs-4">
				<label for="tio-update-until">Update Until</label>
				<div class="input-group">
					<span class="input-group-addon" id="tio-update-until-addon" name="tio-update-until-addon"><span class="glyphicon glyphicon-calendar"></span></span>
					<input type="text" class="form-control" id="tio-update-until" name="tio-update-until" value="<?php echo date('m/d/Y H:00', strtotime('+1 hour')); ?>">
				</div>
			</div>
		</div>
		
		<hr>
		
		<button type="submit" class="btn btn-primary btn-block" aria-haspopup="true" aria-expanded="true">Update Settings</button>
	</div>
	
	<!-- <div id="update_bracket_players">
		<div class="page-header"><h3>Manage Players</h3></div>
	</div> -->
	
</form>