
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
			<h2>Featured</h2>
			<table border="1" class="tournament-list">
				<thead>
					<tr>
						<th class="col-0"></th>
						<th class="col-1">Name</th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($sorted_tournaments as $key=>$library) {
						if ($library['featured']) { ?>
					<tr>
						<td class="col-0" rowspan="2" valign="top"><span class="fa fa-fw fa-plus-square-o"></span></td>
						<td class="col-1">
							<a href="<?php echo $library['permalink'] ?>"><?php echo $library['name'] ?></a>
							<span style="float: right"><?php echo $library['added'] ?></span>
						</td>
					</tr>
					<tr class="expansion">
						<td colspan="4" style="margin: 0; padding: 0">
							
							<table style="width: 100%; margin: 0; padding: 0" border="0">
								<?php foreach ($library['events'] as $key=>$event) { ?>
								<tr<?php if ($key < count($library['events']) - 1) echo ' class="bottom-border"' ?>>
									<td style="width: 20px; background-color: #000"></td>
									<td>
										<a href="<?php echo $library['permalink'] ?>/<?php echo $tio->url_encode($event['name']) ?>"><?php echo $event['name'] ?></a>
									</td>
									<td style="text-align: right">AAA</td>
								</tr>
								<?php } ?>
							</table>
							
						</td>
					</tr>
					<?php
						}
					}
					?>
				</tbody>
			</table>
			
			<h2>Monthlies</h2>
			<table border="1" class="tournament-list">
				<thead>
					<tr>
						<th class="col-1">Name</th>
						<th class="col-2">Entrants</th>
						<th class="col-3">Status</th>
						<th class="col-4">Date</th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($sorted_tournaments as $key=>$library) {
						if (!$library['hidden'] && stripos($library['name'], 'Monthly') > -1) {
							array_push($exclude_from_other, $library['permalink']);
							echo '<tr>';
							echo '<td class="col-1"><a href="/' . $library['permalink'] . '">' . $library['name'] . '</a></td>';
							// echo '<td class="col-2">' . date('m/d/Y H:i', filemtime(ARCHIVE . "/" . $library['id'] . "/" . $library['id'] . ".tio")) . '</td>';
							echo '<td class="col-2"></td>';
							echo '<td class="col-3"></td>';
							echo '<td class="col-4"></td>';
							echo '</tr>';
						}
					}
					?>
				</tbody>
			</table>
			
			<h2>Other</h2>
			<table border="1" class="tournament-list">
				<thead>
					<tr>
						<th class="col-1">Name</th>
						<th class="col-2">Entrants</th>
						<th class="col-3">Status</th>
						<th class="col-4">Date</th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ($sorted_tournaments as $key=>$library) {
						if (!$library['hidden'] && in_array($library['permalink'], $exclude_from_other) === false) {
							echo '<tr>';
							echo '<td class="col-1"><a href="/' . $library['permalink'] . '">' . $library['name'] . '</a></td>';
							// echo '<td class="col-2">' . date('m/d/Y H:i', filemtime(ARCHIVE . "/" . $library['id'] . "/" . $library['id'] . ".tio")) . '</td>';
							echo '<td class="col-2"></td>';
							echo '<td class="col-3"></td>';
							echo '<td class="col-4"></td>';
							echo '</tr>';
						}
					}
					?>
				</tbody>
			</table>
			</ul>
		</div>