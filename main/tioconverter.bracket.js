$(document).ready(function() {
	var tio_id = "";
	
	/* ======================
	 * = PAGE CONTROLS
	 * ======================
	 */
	function changeStatus(status) {
		if (typeof status != 'undefined') {
			$('#status').css('display', 'block');
			switch (status) {
				case 'loading':
					$('#status').css({
						'background-image': 'none'
					});
					$('#status img').css('display', 'inline');
					break;
				case 'connect-failed':
					$('#status').css({
						'background-image': 'url(load_tio_fail_connect.png)'
					});
					$('#status img').css('display', 'none');
					break;
				case 'parse-failed':
					$('#status').css({
						'background-image': 'url(load_tio_fail_parse.png)'
					});
					$('#status img').css('display', 'none');
					break;
				default:
					$('#status').css('display', 'none');
					break;
			}
		} else {
			$('#status').css('display', 'none');
		}
	}
	 
	/* ======================
	 * = BRACKET LOADING
	 * ======================
	 */
	function tio(id) {
	}
	
	function loadTioFile(reload) {		
		if ((typeof reload != 'undefined') && (reload == true)) {
			reloading = true;
		} else {
			reloading = false;
		}
		
		if (!first_load) {
			winl.clearRect(0,wel.width,0,wel.height);
			losl.clearRect(0,lel.width,0,lel.height);
			
			$('#bracket').css('display', 'none');
			changeStatus();
			return false;
		}
		changeStatus('loading');
		
		succeeded = false;
		loading = true;
		
		$.getJSON("?get",
			function(data) {
				tio = data;
				
				if ((first_load) || (reloading)) {
					$.ajax('?get='+selected_event+'&md5', {
						data: 'text',
						success: function(data) {
							md5 = data;
						}
					});
					
					if (default_game.toString() == parseInt(default_game).toString()) {
						$('#game').val($('#game option:eq(' + default_game + ')').val());
					} else {
						$('#game').val(default_game);
					}
					$('#game').change();
					$('#display').val(default_display);
					$('#display').change();
					first_load = false;
				}
			}
		).always(function() {
			changeStatus();
			loading = false;
		}).fail(function() {
			changeStatus('connect-failed');
			loading = false;
			succeeded = false;
		}).success(function() {
			succeeded = true;
		});
		return succeeded;
	}

	function getRound(round) {
		if (round >= 0) {
			if (round == winners_round_count) {
				return "Grand Finals Set 2";
			} else if (round == winners_round_count - 1) {
				return "Grand Finals";
			} else if (round == winners_round_count - 2) {
				return "Winners Finals";
			} else if (round == winners_round_count - 3) {
				return "Winners Semis";
			} else if (round == winners_round_count - 4) {
				return "Winners Quarters";
			} else {
				return "Round " + round;
			}
		} else {
			round = round * -1;
			if (round == losers_round_count) {
				return "Losers Finals";
			} else if (round == losers_round_count - 1) {
				return "Losers Semis";
			} else if (round == losers_round_count - 2) {
				return "Losers Quarters";
			} else {
				return "Losers Round " + round;
			}
		}
	}
});