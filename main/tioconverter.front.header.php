
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
								<li><a href="/<?php echo $tio->getTournamentLibrary()['permalink']; ?>/<?php echo $tio->url_encode($event['name']); ?><?php if ($_GET['tiomatch'] != "") { echo '/'.$_GET['tiomatch']; } ?>"><?php echo $event['name']; ?></a></li>
								<?php } ?>
							</ul>
						</li>
						
						<!-- # of Entrants -->
						<li><a><?php echo $tio->parseBracket()[$tio->getTournamentId(false)]['events'][$tio->getLoadedEvent()['id']]['entrants']; ?> entrants</a></li>
						<?php } ?>
						
						<?php if ($tio->download_error) { ?>
						<li><a style="color: #d00; font-weight: bold">Download error!!</a></li>
						<?php } ?>
					</ul>
					
					<ul class="nav navbar-nav navbar-right">
						<?php if ($tio->loaded) { ?>					
						<!-- Download -->
						<li><a href="/download/<?php echo $tio->getTournamentLibrary()['permalink']; ?>.tio" id="download-bracket"><span class="glyphicon glyphicon-download-alt"></span></a></li>
						
						<!-- Refresh -->
						<li><a href="#" id="refresh-bracket"><span class="glyphicon glyphicon-refresh"></a></li>
						
						<!-- View -->
						<li class="dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">View <span class="caret"></span></a>
							<ul class="dropdown-menu" id="bracket_view">
								<li><a href="/<?php echo $tio->getTournamentLibrary()['permalink']; ?>/<?php echo $tio->url_encode($tio->getLoadedEvent()['name']); ?>/bracket">Bracket</a></li>
								<li><a href="/<?php echo $tio->getTournamentLibrary()['permalink']; ?>/<?php echo $tio->url_encode($tio->getLoadedEvent()['name']); ?>/results">Results</a></li>
								<li><a href="/<?php echo $tio->getTournamentLibrary()['permalink']; ?>/<?php echo $tio->url_encode($tio->getLoadedEvent()['name']); ?>/setups">Setups</a></li>
							</ul>
						</li>
						<?php } ?>
						
						<!-- Search for Player -->
						<li>
							<form class="navbar-form" role="search" id="tio-search">
								<div class="input-group">
									<input class="form-control" placeholder="Search" type="search">
									<div class="input-group-btn">
										<button class="btn btn-default" type="submit"><i class="glyphicon glyphicon-search"></i></button>
									</div>
								</div>
							</form>
						</li>
					</ul>
				</div><!-- /.navbar-collapse -->
			</div><!-- /.container-fluid -->
		</nav>