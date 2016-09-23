
		<div id="regular-container">
			<?php
			function my_sort($a, $b) {
				if ($a['name'] == $b['name']) {
					return 0;
				} else {
					return ($a['name'] > $b['name']) ? -1 : 1;
				}
			}
			
			$sorted_tournaments = $tio->getLibrary()['tournaments'];
			usort($sorted_tournaments, "my_sort");
			
			$exclude_from_other = [];
			
			// Output tournaments
			?>
			<h1>Featured</h1>
			<ul>
			<?php
			foreach ($sorted_tournaments as $key=>$library) {
				if ($library['featured']) {
					echo '<li>' . ($library['featured'] ? '<span class="fa fa-star"></span> ' : '') . '<a href="/' . $library['permalink'] . '">' . $library['name'] . '</a></li>';
				}
			}
			?>
			</ul>
			
			<h1>Weeklies</h1>
			<ul>
			<?php
			foreach ($sorted_tournaments as $key=>$library) {
				$weekly_match = preg_match('/CFL Smackdown [0-9]+/i', $library['name'], $matches);
				if (!$library['hidden'] && count($matches) > 0) {
					array_push($exclude_from_other, $library['permalink']);
					echo '<li><a href="/' . $library['permalink'] . '">' . $library['name'] . '</a></li>';
				}
			}
			?>
			</ul>
			
			<h1>Monthlies</h1>
			<ul>
			<?php
			foreach ($sorted_tournaments as $key=>$library) {
				if (!$library['hidden'] && stripos($library['name'], 'Monthly') > -1) {
					array_push($exclude_from_other, $library['permalink']);
					echo '<li><a href="/' . $library['permalink'] . '">' . $library['name'] . '</a></li>';
				}
			}
			?>
			</ul>
			
			<h1>Other</h1>
			<ul>
			<?php
			foreach ($sorted_tournaments as $key=>$library) {
				if (!$library['hidden'] && in_array($library['permalink'], $exclude_from_other) === false) {
					echo '<li><a href="/' . $library['permalink'] . '">' . $library['name'] . '</a></li>';
				}
			}
			?>
			</ul>
		</div>