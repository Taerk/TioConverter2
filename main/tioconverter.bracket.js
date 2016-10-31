function tioConverterJS() {		
	_js = this;
	
	/* ======================
	 * = SET VARIABLE DEFAULTS
	 * ======================
	 */
	this.autoload = true;
	this.loading = false;
	this.tio_data = {};
	this.selected_tournament = "";
	this.selected_event = "";
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
	this.scroll_y = 0;
	this.scroll_y = 0;
	
	this.highlight = {};
	this.events = [];
	
	this.win_lines;
	this.lose_lines;
	this.win_ctx;
	this.lose_ctx;
	this.hidden_lines = 0;
	
	/* ======================
	 * = PAGE CONTROLS
	 * ======================
	 */
	this.changeStatus = function(status) {
		if (typeof status != 'undefined') {
			$('#status').css('display', 'block');
			$('#no-matches').remove();
			switch (status) {
				case 'loading':
					$('#status').css({
						'background-image': 'url(' + (navigator.userAgent.toLowerCase().indexOf('mobile') > -1 ? '/main/images/load_tio_light.gif' : '/main/images/load_tio.png') + ')'
					});
					$('#status img').css('display', 'inline');
					break;
				case 'connect-failed':
					$('#status').css({
						'background-image': 'url(/main/images/load_js_fail_connect.png)'
					});
					$('#status img').css('display', 'none');
					break;
				case 'parse-failed':
					$('#status').css({
						'background-image': 'url(/main/images/load_js_fail_parse.png)'
					});
					$('#status img').css('display', 'none');
					break;
				case 'no-matches':
					$('#status').css('display', 'none');
					$('#status img').css('display', 'none');
					$('#bracket').prepend('<h1 id="no-matches" style="text-align: center">This bracket has not been started</h1>');
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
	this.autoTio = function(file, tournament, interval) {		
		_js.selected_tournament = file;
		_js.selected_event = tournament;
		_js.loadTioFile(false);
		
		$('#refresh-bracket').click(function(e) {
			e.preventDefault();
			_js.loadTioFile(true);
		});
		
		// Set up auto-check
		if (typeof interval == 'undefined') {
			interval = 1; // Interval to update bracket (in seconds)
		}
		if (typeof auto_load == 'undefined') {
			console.log("Starting automatic updates");
			auto_load = setInterval(function() {
				_js.loadTioFile(true);
			}, interval * 1000);
		}
	}
	
	this.loadTioFile = function(reload) {
		console.log("Loading data");
		if ((typeof reload != 'undefined') && (reload == true)) {
			_js.loading = true;
		} else {
			_js.loading = false;
		}
		
		$.getJSON("?get=data", function(hashData) {
			if (hashData.md5 != _js.md5) {
				_js.md5 = hashData.md5;
				console.log("There was a change in md5 -- new md5 is: " + hashData.md5);
		
				if (!_js.first_load) {
					_js.win_ctx.clearRect(0, _js.win_lines.width, 0, _js.win_lines.height);
					_js.los_ctx.clearRect(0, _js.lose_lines.width, 0, _js.lose_lines.height);
					
					$('#bracket').css('display', 'none');
					_js.changeStatus();
					return false;
				}
				
				_js.changeStatus('loading');
				
				var succeeded = false;
				_js.loading = true;
				
				$.getJSON("?get", function(data) {
					try {
						_js.tio_data = data;
						
						// Count the number of rounds in the tournament
						var used_rounds = [];
						_js.winners_round_count = 0;
						_js.losers_round_count = 0;
						
						$.each(data[_js.selected_tournament]['events'][_js.selected_event]['matches'], function(key,_match) {
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
						
						if (data[_js.selected_tournament]['events'][_js.selected_event]['matches'].length === 0) {
							_js.changeStatus('no-matches');
						}
						
					} catch(e) {
						console.log(e.toString());
						_js.changeStatus('parse-failed');
					}
				})
				.always(function() {
					_js.loading = false;
				})
				.done(function() {
					_js.changeStatus();
					_js.drawBracket(reload);
					return true;
				})
				.fail(function() {
					_js.changeStatus('connect-failed');
					return false;
				});
			} else {
				console.log("No changes in md5");
			}
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
	
	this.getWinKey = function(_event, _match, player_port) {
		var wl;
		
		if (_match['p' + player_port + '_prev'] != -1) {
			switch (true) {
				// If it's a player that's going into losers from winners
				case (_match['round'] < 0 && _event['matches'][_match['p' + player_port + '_prev']]['round'] > 0):
					wl = 'Loser';
					break;
				default:
					wl = 'Winner';
					break;
			}
			return (wl + " of " + _event['matches'][_match['p' + player_port + '_prev']]['key']);
		} else {
			return "";
		}
	}
	
	this.drawBracket = function(reload) {
		var _data = _js.tio_data;
		var blanks = ['00000000-0000-0000-0000-000000000000', '00000001-0001-0001-0101-010101010101'];
		
		// $('#bracket, #results').css('display', 'none');
		if (_js.selected_tournament == "" || _js.selected_event == -"") {
			return false;
		}
		
		$('#loser_rounds, #loser_matches, #winner_rounds, #winner_matches').html('');
		
		if (typeof _data[_js.selected_tournament] != 'undefined') {
			var _tournament = _data[_js.selected_tournament];
			
			$('#to_title').html($('#tournament option:selected').text() + " - " + _data[_js.selected_tournament]['events'][_js.selected_event]['name']);
			$('#to_event').html(_data[_js.selected_tournament]['events'][_js.selected_event]['event']);
			$('#to_event').append(' - ' + _data[_js.selected_tournament]['events'][_js.selected_event]['entrants'] + ' entrants'); // Number of Players
			
			if (typeof _data[_js.selected_tournament]['events'][_js.selected_event] != 'undefined') {
				var _event = _data[_js.selected_tournament]['events'][_js.selected_event];
				
				// Go through each match
				$.each(_event['matches'], function(key,_match) {
					// Check if it's grand finals set 2 before outputting
					if (_match['round'] < _js.winners_round_count || (_match['round'] == _js.winners_round_count && blanks.indexOf(_match.winner) == -1)) {
						// Score corrections for byes and brackets that use checkmarks
						if (_match['p1']['id'] == "00000001-0001-0001-0101-010101010101" || ((_match['s1'] == "" && _match['s2'] == "") && (_match['winner'] == _match['p2']['id']))) {
							_match['s1'] = "";
							_match['s2'] = "&check;";
						}
						if (_match['p2']['id'] == "00000001-0001-0001-0101-010101010101" || ((_match['s1'] == "" && _match['s2'] == "") && (_match['winner'] == _match['p1']['id']))) {
							_match['s1'] = "&check;";
							_match['s2'] = "";
						}
						
						// Score correction for cancelled matches and unfinished matches
						if (_match['in_progress'] == true || _match['winner'] == "00000000-0000-0000-0000-000000000000" || _match['winner'] == "00000001-0001-0001-0101-010101010101") {
							_match['s1'] = "";
							_match['s2'] = "";
						}
						
						var m_side = (_match['round'] > 0 ? 'winner' : 'loser');
						
						// Add a column if it doesn't exist
						if ($('#bracket .round-' + _match['round']).length < 1) {
							console.log("adding column");
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
							if (blanks.indexOf(_match['p1']['id']) > -1 && _match['p1']['id'] != "00000001-0001-0001-0101-010101010101") {
								_match['p1']['tag'] = _js.getWinKey(_event, _match, 1);
							}
							
							if (blanks.indexOf(_match['p2']['id']) > -1 && _match['p2']['id'] != "00000001-0001-0001-0101-010101010101") {
								_match['p2']['tag'] = _js.getWinKey(_event, _match, 2);
							}
						}
						
						/**
						 * SET UP MATCH INFORMATION TO PRINT OUT
						 */					
						// Setup
						var m_setup = (typeof _tournament['stations_ez'][_js.selected_event + ':' + _match['id']] != 'undefined' ? _tournament['stations_ez'][_js.selected_event + ':' + _match['id']] : false);
						switch (true) {
							case (m_setup === false): // No setup
								m_setup_is = " none";
								break;
							case (m_setup.toLowerCase().indexOf("stream") > -1): // Stream setup
								m_setup_is = " stream";
								break;
							default: // Regular setup
								m_setup_is = "";
								break;
						}
						
						/**
						 * ADD KO Indicator
						 */
						ko_template = '<i class="ko">KO</i>';
						ko_p1 = "";
						ko_p2 = "";
						if (_match['loser_next'] == -1 && _match['p1']['id'] != "00000001-0001-0001-0101-010101010101" && _match['p2']['id'] != "00000001-0001-0001-0101-010101010101") {
							if (_match['winner'] == _match['p1']['id']) {
								ko_p2 = ko_template;
							} else if (_match['winner'] == _match['p2']['id']) {
								ko_p1 = ko_template;
							}
						}
						 
						/**
						 * OUTPUT TEXT
						 */
						$('#' + m_side + '_matches .match-column.round-' + _match['round']).append(
							"<div class=\"match\" match-id=\"0\" winner-id=\"" + _match["winner"] + "\" in-progress=\"false\">\n" +
							"		<div class=\"match-info\">\n" +
							"			<div class=\"tio-match-id\">" + _match["key"] + "</div>\n" +
							"			<a class=\"setup" + m_setup_is + "\">" + m_setup + "</a>\n" +
							"		</div>" +
							"		<div class=\"players\">\n" +
							"			<div class=\"player player1" + m_is_winner_p1 + m_is_system_p1 +"\" player-seed=\"" + _match["p1"]["seed"] + "\" player-id=\"" + _match["p1"]["id"] + "\">\n" +
							"				<div class=\"player-seed\">" + _match["p1"]["seed"] + "</div>\n" +
							"				<div class=\"player-tag\">" + _match["p1"]["tag"] + ko_p1 + "</div>\n" +
							"				<div class=\"player-score\">" + _match["s1"] + "</div>\n" +
							"			</div>\n" +
							"			<div class=\"sep\"></div>\n" +
							"			<div class=\"player player2" + m_is_winner_p2 + m_is_system_p2 +"\" player-seed=\"" + _match["p2"]["seed"] + "\" player-id=\"" + _match["p2"]["id"] + "\">\n" +
							"				<div class=\"player-seed\">" + _match["p2"]["seed"] + "</div>\n" +
							"				<div class=\"player-tag\">" + _match["p2"]["tag"] + ko_p2 + "</div>\n" +
							"				<div class=\"player-score\">" + _match["s2"] + "</div>\n" +
							"			</div>\n" +
							"		</div>\n" +
							"	</div>"
						);
					}
				});
				
				$('#bracket').css('min-width', $('#container').innerWidth());
				$('#bracket').css('width', (250 * _js.losers_round_count) + "px");
				
				_js.drawLines(reload);
				_js.setHeader();
				_js.setHover();
				_js.listResults();
				
				// Redraw bracket if there's something screwy going on
				if ($('.match').length < _event['matches'].length - 1) {
					console.log("Drew " + $('.match').length + " matches even though there are " + (_event['matches'].length - 1) + " listed");
					console.log("Drew " + $('.match').length + " matches even though there are " + (_event['matches'].length - 1) + " listed");
					_js.loadTioFile(true);
				}
			}
		}
	}
	
	this.listResults = function() {
		var _data = _js.tio_data;
		$('#results').html('');
		$('#results').append('<div class="heading">Top 8</div>');
		prev_key = -1;
		$.each(_data[_js.selected_tournament]['events'][_js.selected_event]['results'], function(placement,val) {
			if (Object.keys(val).length > 0) {
				if ((parseInt(placement) > 8) && (prev_key <= 8)) {
					$('#results').append('<div class="heading">The Rest</div>');
				}
				prev_key = parseInt(placement);
				
				$('#results').append('<div class="placing-row placing-row-' + placement + '"><span class="placing-number">' + placement + '</span><div class="placing-players"></div></div>');
				$.each(val, function(key2,player) {
					$('.placing-row-' + placement + ' .placing-players').last().append('<div class="player">' + player['tag'] + '</div>');
				});
			}
		});
		$.each($('.placing-players'), function(key,el) {
			if ($(this).find('.player').length > 1) {
				$(this).find('.player:even').addClass('odd');
			}
		});
	}
	
	this.setHover = function() {
		// Highlight color on mouseover
		$('.player').not('.hover-set').mouseover(function() {
			var get_pid = $(this).attr('player-id');
			if (get_pid != "00000001-0001-0001-0101-010101010101" && get_pid != "00000000-0000-0000-0000-000000000000") {
				$('[player-id="' + get_pid + '"]').addClass('hover');
			}
		});
		
		// Highlight color on click
		$('.player').not('.hover-set').dblclick(function() {
			var get_pid = $(this).attr('player-id');
			if (get_pid != "00000001-0001-0001-0101-010101010101" && get_pid != "00000000-0000-0000-0000-000000000000") {
				if ($('[player-id="' + get_pid + '"]:eq(0)').hasClass('selected')) {
					$('.player.selected').removeClass('selected');
				} else {
					$('.player.selected').removeClass('selected');
					$('[player-id="' + get_pid + '"]').addClass('selected');
				}
			}
			/* if ($(this).hasClass('hover')) {
				get_pid = $(this).find('.player-tag').attr('player-id');
				
				if ((typeof highlight[get_pid] == 'undefined') || (highlight[get_pid] == -1)) {
					if (get_pid.indexOf('0000000') != 0) {
						highlight[get_pid] = 0;
						$('[player-id="'+get_pid+'"]').closest('.player').addClass('highlight');
						$('[player-id="'+get_pid+'"]').closest('.player').addClass('highlight-'+highlight[get_pid]);
					}
				} else if (highlight[get_pid] > -1) {
					if (highlight[get_pid] < 5) { // Re-highlight
						$('[player-id="'+get_pid+'"]').closest('.player').removeClass('highlight-'+highlight[get_pid]);
						highlight[get_pid]++;
						$('[player-id="'+get_pid+'"]').closest('.player').addClass('highlight-'+highlight[get_pid]);
					} else { // Remove
						$('[player-id="'+get_pid+'"]').closest('.player').removeClass('highlight');
						$('[player-id="'+get_pid+'"]').closest('.player').removeClass('highlight-'+highlight[get_pid]);
						highlight[get_pid] = -1;
					}
				}
			} */
		});
		
		$('.player').not('.hover-set').mouseout(function() {
			$('.player.hover').removeClass('hover');
		});
		
		// Highlight column by round
		$('.round-column').not('.hover-set').mouseover(function() {
			$('[round="'+ $(this).attr('round') + '"], [round="'+ -$(this).attr('round') + '"]').addClass('hover');
		});
		$('.round-column').not('.hover-set').mouseout(function() {			
			$('.hover').not('.player').removeClass('hover');
		});
		
		
		$('.player, .round-column').addClass('hover-set');
	}
	
	this.resizeLines = function() {
		$.each($('canvas'), function(key,el) {
			$(el).attr({
				'width': $(el).outerWidth(),
				'height': $(el).outerHeight()
			});
		});
	};
	
	this.drawLines = function(reload) {
		console.log("Drawing line");
		_js.resizeLines();
		
		if (!reload) {
			console.log("Not a reload");
			_js.win_lines = document.getElementById('winner_lines');
			_js.lose_lines = document.getElementById('loser_lines');
			_js.win_ctx = _js.win_lines.getContext("2d");
			_js.lose_ctx = _js.lose_lines.getContext("2d");
			
			// Clear canvas
			_js.win_ctx.clearRect(0, _js.win_lines.width, 0, _js.win_lines.height);
			_js.lose_ctx.clearRect(0, _js.lose_lines.width, 0, _js.lose_lines.height);
			
			
			var s = 0;
			var e = 0;
			var ya1 = -1;
			var ya2 = -1;
			var adjust_yw = -2;
			var adjust_yl = -2;
			var debug_grid = false;
			var x_split = 15; // Set how far out to merge the splits
			var line_width = 2;
			var line_color = {winner: '#040', loser: '#400'};
			var winl = _js.win_ctx;
			var losl = _js.lose_ctx;
			var draw_gf2 = ($('.round-column:contains("Set 2")').length == 1);
			
			if (debug_grid) {
				for (i = 0; i < 100; i++) {
					if (i % 5 == 0) {
						winl.strokeStyle = '#f00';
						losl.strokeStyle = '#f00';
					} else {
						winl.strokeStyle = '#bdb';
						losl.strokeStyle = '#dbb';
					}
					losl.beginPath();
					losl.moveTo(100 * i, 0);
					losl.lineTo(100 * i, 6000);
					losl.stroke();
					
					losl.beginPath();
					losl.moveTo(0, 100 * i);
					losl.lineTo(6000, 100 * i);
					losl.stroke();
					
					winl.beginPath();
					winl.moveTo(100 * i, 0);
					winl.lineTo(100 * i, 6000);
					winl.stroke();
					
					winl.beginPath();
					winl.moveTo(0, 100 * i);
					winl.lineTo(6000, 100 * i);
					winl.stroke();
				}
			}
			
			winl.strokeStyle = line_color.winner;
			winl.lineWidth = line_width;
			
			$.each($('#winners .match-column'), function(key,el) {
				s = $(el).find('.match .sep').length;
				e = $('#winners .match-column:eq('+(key + 1)+')').find('.match .sep').length;
				
				if (((key + 2 == _js.winners_round_count) && (draw_gf2 == true)) || ((key + 2) < _js.winners_round_count)) {
					if (e > 0) {
						
						// Single to single
						if (s == e) {
							$.each($(el).find('.match'), function(key2,el2) {
								tx = parseInt($('#winners').position().left)
									+ parseInt($(el2).position().left)
									+ parseInt($(el2).width())
									+ $('#container').scrollLeft();
								ty = parseInt($(el2).position().top)
									+ parseInt($(el2).find('.player:eq(0)').outerHeight())
									+ parseInt($('#winner_rounds').outerHeight())
									+ adjust_yw;
									
								if (debug_grid) { winl.strokeStyle = '#55f'; }
								winl.beginPath();
								winl.moveTo(tx - 10, ty);
								winl.lineTo(tx + 200, ty);
								winl.stroke();
							});
						} else if (s > e) {
							$.each($(el).find('.match'), function(key2,el2) {
								if (key2 % 2 == 0) {
									tx = parseInt($('#winners').position().left)
										+ parseInt($(el2).position().left)
										+ parseInt($(el2).width())
										+ $('#container').scrollLeft();
									ty = parseInt($(el2).position().top)
										+ parseInt($(el2).find('.player:eq(0)').outerHeight())
										+ parseInt($('#winner_rounds').outerHeight())
										+ adjust_yw;
									ty2 = parseInt($(el).find('.match:eq('+(key2 + 1)+')').position().top)
										+ parseInt($(el).find('.match:eq('+(key2 + 1)+')').find('.player:eq(0)').outerHeight())
										+ parseInt($('#winner_rounds').outerHeight())
										+ adjust_yw;
									
									
									if (debug_grid) { winl.strokeStyle = '#5f5'; }
									winl.beginPath();
									winl.moveTo(tx - 10, ty);
									winl.lineTo(tx + x_split + (line_width / 2), ty);
									winl.stroke();
									
									if (debug_grid) { winl.strokeStyle = '#f55'; }
									winl.beginPath();
									winl.moveTo(tx - 10, ty2);
									winl.lineTo(tx + x_split +  (line_width / 2), ty2);
									winl.stroke();
									
									if (debug_grid) { winl.strokeStyle = '#ff5'; }
									winl.beginPath();
									winl.moveTo(tx + x_split, ty);
									winl.lineTo(tx + x_split, ty2);
									winl.stroke();
									
									if (debug_grid) { winl.strokeStyle = '#ff5'; }
									winl.beginPath();
									winl.moveTo(tx + x_split, (ty + ty2) / 2);
									winl.lineTo(tx + 100, (ty + ty2) / 2);
									winl.stroke();
								}
							});
						}
					}
				}
			});
			
			losl.strokeStyle = line_color.loser;
			losl.lineWidth = line_width;
			
			$.each($('#losers .match-column'), function(key,el) {
				s = $(el).find('.match').length;
				e = $('#losers .match-column:eq('+(key + 1)+')').find('.match').length;
				
				// alert(key + "/" + (losers_round_count - 1) + " -- " + s + " => " + e);
				if (e > 0) {
					if (s == e) {
						$.each($(el).find('.match'), function(key2,el2) {
							tx = parseInt($('#losers').position().left)
								+ parseInt($(el2).position().left)
								+ parseInt($(el2).width())
								+ $('#container').scrollLeft();
							ty = parseInt($(el2).position().top)
								+ parseInt($(el2).find('.player:eq(0)').outerHeight())
								+ parseInt($('#loser_rounds').outerHeight())
								+ adjust_yw;
								
							if (debug_grid) { losl.strokeStyle = '#55f'; }
							losl.beginPath();
							losl.moveTo(tx - 10, ty);
							losl.lineTo(tx + 200, ty);
							losl.stroke();
						});
					} else if (s > e) {
						$.each($(el).find('.match'), function(key2,el2) {
							if (key2 % 2 == 0) {
								tx = parseInt($('#losers').position().left)
									+ parseInt($(el2).position().left)
									+ parseInt($(el2).width())
									+ $('#container').scrollLeft();
								ty = parseInt($(el2).position().top)
									+ parseInt($(el2).find('.player:eq(0)').outerHeight())
									+ parseInt($('#loser_rounds').outerHeight())
									+ adjust_yw;
								ty2 = parseInt($(el).find('.match:eq('+(key2 + 1)+')').position().top)
									+ parseInt($(el).find('.match:eq('+(key2 + 1)+')').find('.player:eq(0)').outerHeight())
									+ parseInt($('#loser_rounds').outerHeight())
									+ adjust_yw;
								
								
								if (debug_grid) { losl.strokeStyle = '#5f5'; }
								losl.beginPath();
								losl.moveTo(tx - 10, ty);
								losl.lineTo(tx + x_split + (line_width / 2), ty);
								losl.stroke();
								
								if (debug_grid) { losl.strokeStyle = '#f55'; }
								losl.beginPath();
								losl.moveTo(tx - 10, ty2);
								losl.lineTo(tx + x_split + (line_width / 2), ty2);
								losl.stroke();
								
								if (debug_grid) { losl.strokeStyle = '#ff5'; }
								losl.beginPath();
								losl.moveTo(tx + x_split, ty);
								losl.lineTo(tx + x_split, ty2);
								losl.stroke();
								
								if (debug_grid) { losl.strokeStyle = '#ff5'; }
								losl.beginPath();
								losl.moveTo(tx + x_split, (ty + ty2) / 2);
								losl.lineTo(tx + 100, (ty + ty2) / 2);
								losl.stroke();
							}
						});
					}
				}
			});
		}
	}
	
	this.setHeader = function() {
		$('#winners .round-head').attr('init-y', $('#header').outerHeight());
		$('#losers .round-head').attr('init-y', $('#header').outerHeight() + $('#winners').outerHeight());
		
		_js.adjustHeader();
	}
	
	this.adjustHeader = function() {
		$('.round-clone').remove();
		
		if ($('.round-head').length > 0) {
			// Use switch case so losers will be on top rather than winners
			switch (true) {
				case ($('#container').scrollTop() > $('.round-head:eq(1)').attr('init-y') - $('.round-head:eq(0)').height()): // Losers
					$('.round-head:eq(1)').clone().appendTo('#bracket');
					break;
				case ($('#container').scrollTop() > 0): // Winners
					$('.round-head:eq(0)').clone().appendTo('#bracket');
					break;
			}
			
			$('.round-head:eq(2)').addClass('round-clone');
			$('.round-clone .round-column').removeClass('hover-set');
			$('.round-clone').css({
				'position': 'fixed',
				'top': $('#header').height() + 'px',
				'left': -$('#container').scrollLeft(),
				'width': $('#winner_matches').width()
			});
			
			_js.setHover();
		}
	}
	
	this.adjustHeader_orig = function() {
		// Reset position
		$('.round-head').css({
			'position': 'initial',
			'top': '0px'
		});
		$('#winner_matches, #loser_matches').css('padding-top', '0px');
			
		if ($('.round-head').length > 0) {			
			// Use switch case so losers will be on top rather than winners
			switch (true) {
				case ($(window).scrollTop() > $('.round-head:eq(1)').attr('init-y')): // Losers
					$('.round-head:eq(1)').css({
						'position': 'fixed',
						'top': $('#header').outerHeight() + 'px',
						'left': -$(window).scrollLeft()
					});
					$('#loser_matches').css('padding-top', $('.round-head:eq(1)').outerHeight() + 'px');
					break;
				case ($(window).scrollTop() > $('.round-head:eq(0)').attr('init-y')): // Winners
					$('.round-head:eq(0)').css({
						'position': 'fixed',
						'top': $('#header').outerHeight() + 'px',
						'left': -$(window).scrollLeft()
					});
					$('#winner_matches').css('padding-top', $('.round-head:eq(0)').outerHeight() + 'px');
					break;
			}
		}
	}
	
	this.changeView = function(view) {
		switch (view) {
			case 0:
				$('#bracket').css('display', 'block');
				$('#results').css('display', 'none');
				break;
			case 1:
				$('#bracket').css('display', 'none');
				$('#results').css('display', 'block');
				break;
		}
	}
	
	this.doDrag = function(e) {
		_js.mouse_x = e.pageX;
		_js.mouse_y = e.pageY;
		// _js.start_pos_x = $('#container').scrollLeft(); // Only necessary for $(window)
		// _js.start_pos_y = $('#container').scrollTop(); // Only necessary for $(window)
		$('#container').scrollLeft(_js.start_pos_x + (_js.start_mouse_x - _js.mouse_x));
		$('#container').scrollTop(_js.start_pos_y + (_js.start_mouse_y - _js.mouse_y));
	}
}

/* Set up functions after page load */
$(document).ready(function() {	
	
	$('#winner_matches, #loser_matches, #winner_lines, #loser_lines').not('.players').mousedown(function(e) {
		if (typeof _js != 'undefined') {
			if (e.which == 1) {
				_js.drag = true;
				_js.mouse_x = e.pageX;
				_js.mouse_y = e.pageY;
				_js.start_mouse_x = e.pageX;
				_js.start_mouse_y = e.pageY;
				_js.start_pos_x = $('#container').scrollLeft();
				_js.start_pos_y = $('#container').scrollTop();
			}
		}
	});

	$(window).mouseup(function(e) {
		if (typeof _js != 'undefined') {
			if (e.which == 1) {
				_js.drag = false;
			}
		}
	});

	$(window).mousemove(function(e) {
		if (typeof _js != 'undefined') {
			if (_js.drag) {
				_js.doDrag(e);
			}
		}
	});
	
	zoom = document.documentElement.clientWidth / window.innerWidth;
	$(window).on('resize scroll', function() {
		var zoomNew = document.documentElement.clientWidth / window.innerWidth;
		
		if (zoom != zoomNew) {	
		
			zoom = zoomNew;
			if (typeof _js != 'undefined') {
				_js.drawBracket();
			}
		}
	});
	
	// On scroll
	$('#container').scroll(function(e) {
		if (typeof _js != 'undefined') {
			_js.adjustHeader();
		}
		
		var new_hidden_lines = 0;
		
		// Hide left-side
		$('.match').css('display', 'block');
		$.each($('.column'), function(key, el) {
			if (($(el).offset().left + $(el).outerWidth()) < 0) {
				/* $(el).find('.match').animate({height: '0px'}, function() {
					$(el).find('.match').css('display', 'none');
				}) */;
				new_hidden_lines++;
			}
		});
		if (new_hidden_lines != _js.hidden_lines) {
			_js.resizeLines();
			_js.drawLines();
			_js.hidden_lines = new_hidden_lines;
		}
		
	});
	
	// Enable searching
	$('#tio-search').submit(function(e) {
		e.preventDefault();
		window.location = '/search/' + $('#tio-search [type="search"]').val();
	});
	
	/* $('#bracket_view a:eq(0)').click(function(e) {
		e.preventDefault();
		if (typeof _js != 'undefined') {
			_js.changeView(0);
		};
	});
	$('#bracket_view a:eq(1)').click(function(e) {
		e.preventDefault();
		if (typeof _js != 'undefined') {
			_js.changeView(1);
		};
	}); */
});