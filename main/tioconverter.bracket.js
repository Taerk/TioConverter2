function tioConverterJS() {		
	/* ======================
	 * = SET VARIABLE DEFAULTS
	 * ======================
	 */
	this.autoload = true;
	this.loading = false;
	this.tio_data = {};
	this.selected_event = "";
	this.selected_game = "";
	this.selected_display = "";
	this.md5 = "";
	this.first_load = true;
	this.winners_round_count = 0;
	this.losers_round_count = 0;
	
	this.drag = false;
	this.mouse_x = 0;
	this.mouse_y = 0;
	this.start_pos_x = 0;
	this.start_pos_y = 0;
	this.start_mouse_x = 0;
	this.start_mouse_y = 0;
	
	this.highlight = {};
	this.games = [];
	
	this.win_lines = document.getElementById('winner_lines');
	this.los_lines = document.getElementById('loser_lines');
	this.win_ctx = this.win_lines.getContext("2d");
	this.los_ctx = this.los_lines.getContext("2d");
	
	/* ======================
	 * = PAGE CONTROLS
	 * ======================
	 */
	this.changeStatus = function(status) {
		if (typeof status != 'undefined') {
			$('#status').css('display', 'block');
			switch (status) {
				case 'this.loading':
					$('#status').css({
						'background-image': 'none'
					});
					$('#status img').css('display', 'inline');
					break;
				case 'connect-failed':
					$('#status').css({
						'background-image': 'url(load_js_fail_connect.png)'
					});
					$('#status img').css('display', 'none');
					break;
				case 'parse-failed':
					$('#status').css({
						'background-image': 'url(load_js_fail_parse.png)'
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
	 * = BRACKET this.loading
	 * ======================
	 */
	this.autoTio = function(file, event) {
		_js = this;
		
		_js.selected_event = file;
		_js.selected_game = event;
		_js.loadTioFile(false);
	}
	
	this.loadTioFile = function(reload) {
		_js = this;
		
		if ((typeof reload != 'undefined') && (reload == true)) {
			_js.loading = true;
		} else {
			_js.loading = false;
		}
		
		if (!_js.first_load) {
			_js.win_ctx.clearRect(0, _js.win_lines.width, 0, _js.win_lines.height);
			_js.los_ctx.clearRect(0, _js.lose_lines.width, 0, _js.lose_lines.height);
			
			$('#bracket').css('display', 'none');
			_js.changeStatus();
			return false;
		}
		this.changeStatus('loading');
		
		var succeeded = false;
		_js.loading = true;
		
		$.getJSON("?get", 
			function(data) {
				_js.tio_data = data;
				
				// Count the number of rounds in the tournament
				var used_rounds = [];
				_js.winners_round_count = 0;
				_js.losers_round_count = 0;
				
				$.each(data[_js.selected_event]['games'][_js.selected_game]['matches'], function(key,_match) {
					if ($.inArray(_match['round'], used_rounds) == -1) {
						if (parseInt(_match['round']) > 0) {
							_js.winners_round_count++;
						}
						if (parseInt(_match['round']) < 0) {
							_js.losers_round_count++;
						}	
						used_rounds.push(_match['round']);
					}
				});
			}
		).always(function() {
			_js.loading = false;
		}).fail(function() {
			_js.changeStatus('connect-failed');
			return false;
		}).success(function() {
			_js.changeStatus();
			_js.drawBracket();
			return true;
		});
	}

	this.getRound = function(round) {
		if (round >= 0) {
			if (round == _js.winners_round_count) {
				return "Grand Finals Set 2";
			} else if (round == _js.winners_round_count - 1) {
				return "Grand Finals";
			} else if (round == _js.winners_round_count - 2) {
				return "Winners Finals";
			} else if (round == _js.winners_round_count - 3) {
				return "Winners Semis";
			} else if (round == _js.winners_round_count - 4) {
				return "Winners Quarters";
			} else {
				return "Round " + round;
			}
		} else {
			round = round * -1;
			if (round == _js.losers_round_count) {
				return "Losers Finals";
			} else if (round == _js.losers_round_count - 1) {
				return "Losers Semis";
			} else if (round == _js.losers_round_count - 2) {
				return "Losers Quarters";
			} else {
				return "Losers Round " + round;
			}
		}
	}
	
	this.getWinKey = function(_game, _match, player_port) {
		var wl;
		
		
		if (_match['p' + player_port + '_prev'] != -1) {
			switch (true) {
				// If it's a player that's going into losers from winners
				case (_match['round'] < 0 && _game['matches'][_match['p' + player_port + '_prev']]['round'] > 0):
					wl = 'Loser';
					break;
				default:
					wl = 'Winner';
					break;
			}
			return (wl + " of " + _game['matches'][_match['p' + player_port + '_prev']]['key']);
		} else {
			return "";
		}
	}
	
	this.drawBracket = function() {
		var _js = this;
		var _data = _js.tio_data;
		var blanks = ['00000000-0000-0000-0000-000000000000', '00000001-0001-0001-0101-010101010101'];
		
		// $('#bracket, #results').css('display', 'none');
		if (_js.selected_event == "" || _js.selected_game == -"") {
			return false;
		}
		
		$('#loser_rounds').html('');
		$('#loser_matches').html('');
		$('#winner_rounds').html('');
		$('#winner_matches').html('');
		
		if (typeof _data[_js.selected_event] != 'undefined') {
			var _event = _data[_js.selected_event];
			
			$('#to_title').html($('#event option:selected').text() + " - " + _data[_js.selected_event]['games'][_js.selected_game]['name']);
			$('#to_game').html(_data[_js.selected_event]['games'][_js.selected_game]['game']);
			$('#to_game').append(' - ' + _data[_js.selected_event]['games'][_js.selected_game]['entrants'] + ' entrants'); // Number of Players
			
			if (typeof _data[_js.selected_event]['games'][_js.selected_game] != 'undefined') {
				var _game = _data[_js.selected_event]['games'][_js.selected_game];
				
				// Go through each match
				$.each(_game['matches'], function(key,_match) {
					// Check if it's grand finals set 2 before outputting
					if (_match['round'] < _js.winners_round_count || (_match['round'] == _js.winners_round_count && blanks.indexOf(_match.winner) == -1)) {
						// Score correction for cancelled matches
						if (_match['in_progress'] == true || _match['winner'] == "00000000-0000-0000-0000-000000000000") {
							_match['s1'] = "";
							_match['s2'] = "";
						}
						
						// Score corrections for byes
						if (_match['p1']['id'] == "00000001-0001-0001-0101-010101010101") {
							_match['s1'] = "";
							_match['s2'] = "&check;";
						}
						if (_match['p2']['id'] == "00000001-0001-0001-0101-010101010101") {
							_match['s1'] = "&check;";
							_match['s2'] = "";
						}
						
						var m_side = (_match['round'] > 0 ? 'winner' : 'loser');
						
						// Add a column if it doesn't exist
						if ($('#bracket .round-' + _match['round']).length < 1) {
							var new_round_column = '<div class="column round-column round-' + _match['round'] + '" round="' + _match['round'] + '">' + _js.getRound(_match['round']) + '</div>';
							var new_match_column = '<div class="column match-column round-' + _match['round'] + '" round="' + _match['round'] + '"></div>';
							$('#' + m_side + '_rounds').append(new_round_column);
							$('#' + m_side + '_matches').append(new_match_column);
						}
						
						var m_is_winner_p1 = (blanks.indexOf(_match['winner']) > -1 || blanks.indexOf(_match['p1']['id']) > -1 ? '' : (_match['p1']['id'] == _match['winner'] ? ' winner' : ' loser'));
						var m_is_winner_p2 = (blanks.indexOf(_match['winner']) > -1 || blanks.indexOf(_match['p2']['id']) > -1 ? '' : (_match['p2']['id'] == _match['winner'] ? ' winner' : ' loser'));
						
						var m_is_system_p1 = (blanks.indexOf(_match['p1']['id']) > -1 ? ' no-result' : '');
						var m_is_system_p2 = (blanks.indexOf(_match['p2']['id']) > -1 ? ' no-result' : '');
						
						// Set up "Winner of $key" and "loser of $key"
						if (blanks.indexOf(_match['winner']) > -1) {
							if (blanks.indexOf(_match['p1']['id']) > -1) {
								_match['p1']['tag'] = _js.getWinKey(_game, _match, 1);
							}
							
							if (blanks.indexOf(_match['p2']['id']) > -1) {
								_match['p2']['tag'] = _js.getWinKey(_game, _match, 2);
							}
						}
						
						/**
						 * SET UP MATCH INFORMATION TO PRINT OUT
						 */					
						// Setup
						var m_setup = (typeof _event['stations_ez'][_match['id']] != 'undefined' ? _event['stations_ez'][_match['id']] : '');
						var m_setup_is = (m_setup == '' ? ' none' : '');
						
						/**
						 * OUTPUT TEXT
						 */
						$('#' + m_side + '_matches .match-column.round-' + _match['round']).append(`
							<div class="match" match-id="0" winner-id="6a466465-7798-4d1f-b537-e4e84518f679" in-progress="false">
								<div class="match-info">
									<div class="tio-match-id">` + _match['key'] + `</div>
									<a class="setup` + m_setup_is + `">` + m_setup + `</a>
								</div>
								<div class="players">
									<div class="player player1` + m_is_winner_p1 + m_is_system_p1 +`" player-seed="` + _match['p1']['seed'] + `" player-id="` + _match['p1']['id'] + `">
										<div class="player-seed">` + _match['p1']['seed'] + `</div>
										<div class="player-tag">` + _match['p1']['tag'] + `</div>
										<div class="player-score">` + _match['s1'] + `</div>
									</div>
									<div class="sep"></div>
									<div class="player player2` + m_is_winner_p2 + m_is_system_p2 +`" player-seed="` + _match['p2']['seed'] + `" player-id="` + _match['p2']['id'] + `">
										<div class="player-seed">` + _match['p2']['seed'] + `</div>
										<div class="player-tag">` + _match['p2']['tag'] + `</div>
										<div class="player-score">` + _match['s2'] + `</div>
									</div>
								</div>
							</div>
						`);
					}
				});
			}
		}
	}
	
	this.drawLines = function() {
		$('#loser_lines, #winner_lines').css('display', 'none');
		
		// Clear canvas
		this.win_ctx.clearRect(0, this.win_lines.width, 0, this.win_lines.height);
		this.los_ctx.clearRect(0, this.lose_lines.width, 0, this.lose_lines.height);
	}
}

var zoom = document.documentElement.clientWidth / window.innerWidth;
$(window).resize(function() {
	var zoomNew = document.documentElement.clientWidth / window.innerWidth;
	if (zoom != zoomNew) {
		drawBracket();
	}
});