<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>.tio Parser</title>
		<style type="text/css">
		body { font-family: sans-serif }
		.bar { color: #ccc }
		#drawing {
			width: 100%;
			height: 700px;
			border: 1px solid black;
			background-color: #EEE;
		}
		.section {
			background-color: #333;
			color: white;
			padding: 5px;
			margin: 10px 0;
		}
		.section a { color: #09f; text-decoration: none }
		.section a:hover { #fff; }
		.section div {
			color: black;
			padding: 5px;
		}
		.show { display: block }
		.hide { display: none }
		h4 {
			margin: 5px 2px;
			padding: 0;
		}
		</style>
	</head>
	<body>
		<h1>.tio Parser Debug Page</h1>
		<div id="debug_init" class="section show">
			<h4>Class Initilization</h4>
			<div style="background-color: #fff4ff">
				<?php
				if (class_exists('tioParser')) {
					$tio_debug = new tioParser; ?>
					<h5 style='padding: 0; margin: 2px'>loadSettings()</h5>
					<?php var_dump($tio_debug->loadSettings()); ?>
					<h5 style='padding: 0; margin: 2px'>getSettings()</h5>
					<?php var_dump($tio_debug->getSettings()); ?>
					<h5 style='padding: 0; margin: 2px'>getEvents()</h5>
					<?php var_dump($tio_debug->getEvents()); ?>
					<h5 style='padding: 0; margin: 2px'>getArchive()</h5>
					<?php var_dump($tio_debug->getArchive()); ?>
					<h5 style='padding: 0; margin: 2px'>getCache()</h5>
					<?php var_dump($tio_debug->getCache());
				} else {
					die('tioParser class not declared');
				}
				?>
			</div>
		</div>
		<div id="debug_draw" class="section show">
			<h4>Drawing Test <a href="/brackets">&rarr;</a></h4>
			<iframe id="drawing" src="/brackets"></iframe>
		</div>
		<!-- <div><img src="sample_bracket.png" style="max-width: 100%; height: auto"></div>
		<div><img src="sample_bracket2.png" style="max-width: 100%; height: auto"></div> -->
		<hr>
		<div id="debug_events" class="section show">
			<h4>Events/Matches</h4>
			<div style="background-color: #fff4f4">
				<pre><?php
				$i = ['string(', 'int(', 'bool(', 'array(', 'NULL'];
				$o = ['  string(', '  int(', '  bool(', '  array(', '  NULL'];
				ob_start();
				var_dump($events);
				$result = ob_get_clean();
				echo str_replace('  ', '<span class="bar">|</span>  ',
					str_replace("\n\n", "\n",
						str_replace($i, $o,
							$result
						)
					)
				); ?></pre>
			</div>
		</div>
		<div id="debug_players" class="section show">
			<h4>Players</h4>
			<div style="background-color: #f4fff4">
				<pre><?php
				ob_start();
				var_dump($players);
				$result = ob_get_clean();
				echo str_replace('    ', '<span class="bar">|</span>  ',
					str_replace("\n\n", "\n",
						$result
					)
				); ?></pre>
			</div>
		</div>
		<div id="debug_stations" class="section show">
			<h4>Stations</h4>
			<div style="background-color: #f4f4ff">
				<pre><?php
				ob_start();
				var_dump($players);
				$result = ob_get_clean();
				echo str_replace('    ', '<span class="bar">|</span>  ',
					str_replace("\n\n", "\n",
						$result
					)
				); ?></pre>
			</div>
		</div>
		
		<div id="debug_server" class="section show">
			<h4>$_SERVER</h4>
			<div style="overflow-x: scroll; border: 1px solid #666; padding: 3px 8px; background-color: #f4f4f4">
				<pre style="margin: 5px; color: black"><?php print_r($_SERVER); ?></pre>
			</div>
		</div>
	</body>
</html>