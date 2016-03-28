<?php
$title_extension = ($tio->loaded ? " - " . $tio->getTournamentName(false) . ' - ' . $tio->getLoadedEvent()['name'] : "");
?><!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<link rel="shortcut icon" type="image/png" href="/etc/images/polarity_compass.png">
		
		<title>Polarity - Bracket<?php echo $title_extension; ?></title>
		
		<!-- Libraries -->
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
		<!-- <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-mobile/1.4.1/jquery.mobile.min.js"></script> -->
		<script type="text/javascript" src="/3rdparty/jquery/jscolor/jquery.color.plus-names-2.1.2.min.js"></script>
		<script type="text/javascript" src="https://netdna.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
		<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
		<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet" type="text/css">
		
		<!-- In-house -->
		<script type="text/javascript" src="/main/tioconverter.bracket.js"></script>
		<?php if (isset($_GET['tioevent'])) {?><script type="text/javascript">
		$(document).ready(function() {
			tioJS = new tioConverterJS();
			tioJS.autoTio(
				'<?php echo $tio->getTournamentId(false); ?>',
				'<?php echo $tio->getEventId(false); ?>',
				<?php echo $tio->getTournamentLibrary()['update_interval']; ?>
				
			);
		});
		</script><?php echo "\n"; } ?>
		<link rel="stylesheet" type="text/css" href="/main/tioconverter.bracket.front.css">
	</head>
	<body>
		<nav class="navbar navbar-default navbar-fixed-top" id="header">
			<div class="container-fluid">
				<!-- Brand and toggle get grouped for better mobile display -->
				<div class="navbar-header">
				  <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				  </button>
				  <a class="navbar-brand pull-left" href="/">Polarity Brackets</a>
				</div>

				<!-- Collect the nav links, forms, and other content for toggling -->
				<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
					<ul class="nav navbar-nav">
						<?php if (isset($_SESSION['admin'])) {?>
						<!-- Admin -->
						<li class="dropdown admin">
							<a href="/admin" role="button">Admin Panel</a>
						</li>
						
						<?php } ?>
						<!-- Featured Tournaments -->
						<li class="dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?php echo ($tio->loaded ? $tio->getTournamentName(false) : 'Featured Tournaments' ); ?> <span class="caret"></span></a>
							<ul class="dropdown-menu">
								<?php
								foreach ($tio->getTournaments() as $key=>$to) {
									if ($to['featured']) {
								?>
								<li><a href="<?php echo "/" . $to['permalink']; ?>"><?php echo $to['name']; ?></a></li>
								<?php
									}
								}
								?>
							</ul>
						</li>
						
						<?php if ($tio->loaded) { ?>
						<!-- Events -->
						<li class="dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?php echo $tio->getLoadedEvent()['name'] ?> <span class="caret"></span></a>
							<ul class="dropdown-menu">
								<?php foreach($tio->parseBracket()[$tio->getTournamentId(false)]['events'] as $key=>$event) { ?>
								<li><a href="/<?php echo $tio->url_encode($tio->getTournamentName(false)); ?>/<?php echo $tio->url_encode($event['name']); ?>"><?php echo $event['name']; ?></a></li>
								<?php } ?>
							</ul>
						</li>
						
						<?php } ?>
					</ul>
					
					<ul class="nav navbar-nav navbar-right">
						<?php if ($tio->loaded) { ?>					
						<!-- Download -->
						<li><a href="/download/<?php echo $tio->url_encode($tio->getTournamentName(false)); ?>.tio" id="download-bracket"><span class="glyphicon glyphicon-download-alt"></span></a></li>
						
						<!-- Refresh -->
						<li><a href="#" id="refresh-bracket"><span class="glyphicon glyphicon-refresh"></a></li>
						
						<!-- View -->
						<!-- <li class="dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">View <span class="caret"></span></a>
							<ul class="dropdown-menu">
								<li><a href="#">Bracket</a></li>
								<li><a href="#">Results</a></li>
							</ul>
						</li> -->
						<?php } ?>
						
						<!-- Search for Player -->
						<!-- <li>
							<form class="navbar-form" role="search" id="search-player">
								<div class="input-group">
									<input class="form-control" placeholder="Search for Player" type="search">
									<div class="input-group-btn">
										<button class="btn btn-default" type="submit"><i class="glyphicon glyphicon-search"></i></button>
									</div>
								</div>
							</form>
						</li> -->
					</ul>
				</div><!-- /.navbar-collapse -->
			</div><!-- /.container-fluid -->
		</nav>
		
		<?php if ($tio->loaded) { ?>
		<div id="container">
			<div class="h-split">
				<div class="v-split">
					<div id="bracket">
						<div id="winners" class="big-section">
							<div class="round-head"><div id="winner_rounds"></div></div>
							<canvas id="winner_lines"></canvas>
							<div id="winner_matches"></div>
						</div>
						<div id="losers" class="big-section">
							<div class="round-head"><div id="loser_rounds"></div></div>
							<canvas id="loser_lines"></canvas>
							<div id="loser_matches"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php } else { ?>
		<div id="regular-container">
			<h1>Select a bracket</h1>
			<ul>
			<?php
			foreach ($tio->getLibrary()['tournaments'] as $key=>$library) {
				echo '<li>' . ($library['featured'] ? '<span class="fa fa-star"></span> ' : '') . '<a href="/' . $library['permalink'] . '">' . $library['name'] . '</a></li>';
			}
			?>
			</ul>
		</div>
		<?php } ?>
		
		<div id="status"></div>
		
		<nav class="navbar navbar-default navbar-fixed-bottom" id="footer">
			<div class="container-fluid">
				Powered by <a href="https://github.com/Taerk/TioConverter2" target="_blank">TioConverter 2.0</a>
			</div>
		</nav>
	</body>
</html>