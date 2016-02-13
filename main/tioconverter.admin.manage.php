<form action="" method="post" class="form-horizontal form-group" role="form" id="manage_bracket">
	<div class="page-header"><h1>Manage Bracket</h1></div>
	
	<label for="basic-url">Select Bracket</label>
	<select size="5" class="form-control"><?php
	foreach ($tio->getEvents() as $id=>$event) {
		echo '<option value="'.$id.'">'.$event.'</option>';
	}
	?></select>
	<h5><a href="#" class="text-danger pull-right">Delete Bracket</a></h5>
	
	<div class="page-header"><h3>Update Information</h3></div>
	
	<label for="basic-url">Bracket URL</label>
	<div class="input-group">
		<span class="input-group-btn">
			<button class="btn btn-success" type="button">Enabled</button>
		</span>
		<input type="text" class="form-control" id="basic-url">
		<span class="input-group-btn">
			<button class="btn btn-default" type="button">Check bracket link</button>
		</span>
	</div>
	
	<br>
	
	<div class="input-group">
		<span class="input-group-addon" id="basic-addon2">tio ID</span>
		<input type="text" class="form-control" id="basic-url" aria-describedby="basic-addon2" readonly>
	</div>
	
	<br>
	
	<div class="form-group">
		<div class="col-xs-5">
			<label for="tio-tourney-name">Tournament Name</label>
			<input type="text" class="form-control" id="tio-tourney-name">
		</div>
		
		<div class="col-xs-3">
			<label for="tio-tourney-name">Check Interval</label>
			<div class="input-group">
				<input type="text" class="form-control" id="tio-tourney-name" value="5">
				<span class="input-group-addon" id="basic-addon">min.</span>
			</div>
		</div>
		
		<div class="col-xs-4">
			<label for="tio-tourney-name">Update Until</label>
			<div class="input-group">
				<span class="input-group-addon" id="basic-addon"><span class="glyphicon glyphicon-calendar"></span></span>
				<input type="text" class="form-control" id="tio-update-until">
			</div>
		</div>
	</div>
	
	<hr>
	
	<button type="button" class="btn btn-primary btn-block" aria-haspopup="true" aria-expanded="true">Update Settings</button>
</form>