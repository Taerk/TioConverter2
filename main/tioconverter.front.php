<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		
		<title>Polarity - Bracket</title>
		
		<!-- Libraries -->
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
		<script type="text/javascript" src="3rdparty/jscolor/jquery.color.plus-names-2.1.2.min.js"></script>
		<script type="text/javascript" src="https://netdna.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
		<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
		<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet" type="text/css">
		
		<!-- In-house -->
		<!-- <script type="text/javascript" src="bracket.js"></script> -->
		<link rel="stylesheet" type="text/css" href="/main/tioconverter.bracket.front.css">
	</head>
	<body>
		<nav class="navbar navbar-default navbar-fixed-top">
			<div class="container-fluid">
				<!-- Brand and toggle get grouped for better mobile display -->
				<div class="navbar-header">
				  <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				  </button>
				  <a class="navbar-brand" href="/">Polarity Brackets</a>
				</div>

				<!-- Collect the nav links, forms, and other content for toggling -->
				<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
					<ul class="nav navbar-nav">
						<li class="dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Featured Brackets<span class="caret"></span></a>
							<ul class="dropdown-menu">
								<li><a href="#"></a></li>
							</ul>
						</li>
						<li><a href="archive">View All Brackets</a></li>
						<li><a href="#"><span class="glyphicon glyphicon-download-alt"></span></a></li>
						<li><a href="#"><span class="glyphicon glyphicon-refresh"></a></li>
					</ul>
					
					<ul class="nav navbar-nav navbar-right">
						<!-- View -->
						<li class="dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">View <span class="caret"></span></a>
							<ul class="dropdown-menu">
								<li><a href="#">Bracket</a></li>
								<li><a href="#">Results</a></li>
							</ul>
						</li>
						
						<!-- Search for Player -->
						<li>
							<form class="navbar-form" role="search" id="search-player">
								<div class="input-group">
									<input class="form-control" placeholder="Search for Player" type="search">
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
	</body>
</html>