<form action="" method="post" class="form-horizontal" role="form" id="check_bracket">
	<div class="page-header"><h1>Add a New Bracket</h1></div>
	
	<label for="tio-check-url">Input bracket download URL</label>
	<div class="input-group">
		<input type="text" class="form-control" id="tio-check-url" name="tio-check-url">
		<span class="input-group-btn">
			<button class="btn btn-default" type="submit" id="check_bracket_button">Check bracket link</button>
		</span>
	</div>
	
	<?php if (file_exists(PATH . '/uploads') && is_dir(PATH . '/uploads')) { ?>
	<div class="input-group" style="width: 100%; text-align: center; margin-top: 5px">
		<label for="tio_available_uploads" style="text-align: left; width: 100%">Or select an uploaded file</label>
		<select class="form-control" id="tio_available_uploads" name="tio_available_uploads">
			<option value="">-</option><?php
		foreach (scandir(PATH . '/uploads') as $key=>$file) {
			if (stripos($file, '.tio') > -1) {
				echo '<option value="' . PATH . "uploads/" . $file . '">' . $file . '</option>';
			}
		}
		?></select>
	</div>
	<?php } ?>
	
	<div id="add_bracket_check">
		<div id="add_bracket_progress">
			<div class="check">Download Check</div>
			<div class="sep"></div>
			<div class="check">XML Parse Check</div>
			<div class="sep"></div>
			<div class="check">Tio Format Check</div>
		</div>
	</div>
	
	<div id="add_bracket_error">&nbsp;</div>
	
	<textarea id="add_bracket_error_result" style="width: 100%; resize: none" rows="10"></textarea>
</form>
	
<form action="?action=add" method="post" class="form-horizontal" role="form" id="add_bracket">
	
	<div id="add_bracket_confirm">
		<div class="page-header"><h3>Check Information</h3></div>
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
					<input type="text" class="form-control" id="tio-update-until" name="tio-update-until" value="<?php echo date('m/d/Y H:00', strtotime('+1 day')); ?>">
				</div>
			</div>
		</div>
		
		<hr>
		
		<button type="submit" class="btn btn-primary btn-block" aria-haspopup="true" aria-expanded="true">Add Bracket</button>
			
	</div>
</form>