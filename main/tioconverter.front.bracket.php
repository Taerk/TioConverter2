        <?php
        switch ($_GET['tiomatch']) {
            case "results":
            case "setups":
                $load_page = $_GET['tiomatch'];
                break;
            case "brackets":
            default:
                $load_page = "bracket";
                break;
        }
		?><div id="container">
			<div id="bracket"<?php if ($load_page != "bracket") { echo ' class="hidden"'; } ?>>
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
			<div id="results"<?php if ($load_page != "results") { echo ' class="hidden"'; } ?>></div>
			<div id="setups"<?php if ($load_page != "setups") { echo ' class="hidden"'; } ?>></div>
		</div>