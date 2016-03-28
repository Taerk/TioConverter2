<form action="?action=update" method="post" class="form-horizontal form-group" role="form" id="update_bracket">
	<div class="page-header"><h1>Manage Bracket</h1></div>
	
	<label for="basic-url">Select Bracket</label>
	<select size="8" class="form-control"><?php
	foreach ($tio->getTournaments() as $id=>$tournament) {
		echo '<option value="'.$tournament['id'].'">'.$tournament['name'].'</option>';
	}
	?></select>
	<h5><a href="#" class="text-danger pull-right">Delete Bracket</a></h5>
	
	<div id="update_bracket_confirm">
		<div class="page-header"><h3>Update Information</h3></div>
		<div class="input-group">
			<span class="input-group-addon" id="tio-tourney-id-tip" name="tio-tourney-id-tip">Tio ID</span>
			<input type="text" class="form-control" id="tio-tourney-id" name="tio-tourney-id" aria-describedby="tio-tourney-id-tip" readonly>
		</div>
		
		<input type="hidden" id="tio-tourney-download" name="tio-tourney-download" readonly>
		
		<br>
	
		<div class="form-group">
			<div class="col-xs-6">
				<label for="tio-tourney-name">Display Name</label>
				<div class="input-group">
					<input type="text" class="form-control" id="tio-tourney-name" name="tio-tourney-name">
					<span class="input-group-btn">
						<input id="tio-tourney-featured" name="tio-tourney-featured" type="hidden" value="0">
						<button class="btn btn-default" id="tio-tourney-featured-switch" name="tio-tourney-featured-switch" type="button" value="0" title="Featured Bracket"><span class="fa fa-star-o"></span></button>
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
		
		<button type="button" class="btn btn-primary btn-block" aria-haspopup="true" aria-expanded="true" disabled>Update Settings</button>
	</div>
	
	<div id="update_bracket_confirm">
		<div class="page-header"><h3>Manage Players</h3></div>
	</div>
	
</form>