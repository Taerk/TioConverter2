var wel;
var lel;
var winl;
var losl;
var page_title;
var autoload;
var loading;
var tio;
var selected_event;
var selected_game;
var selected_display;
var default_event;
var default_game;
var default_display;
var md5;
var first_load;
var winners_round_count;
var losers_round_count;
var highlight;
var games;
var drag;
var mouse_x;
var mouse_y;
var start_pos_x;
var start_pos_y;
var start_mouse_x;
var start_mouse_y;

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

function fixGames() {
	games = [];
	$.each($('#event option'), function(key,el) {
		if ($.inArray($(this).attr('value'), games) == -1) {
			games.push($(this).attr('value'));
		} else {
			$(this).delete();
		}
	});
}

function getEvents() {
	$('#bracket').css('display', 'block'); 
	
	changeStatus('loading');
	if ((typeof is_change == 'undefined') || (is_change == true)) {
		// $('#event').html('<option value="-1">Select an event</option>');
		$('#event').html('<option value="-1">Select an event</option>');
		$('#event').prop('disabled', true);
	
		$.getJSON("?events",
			function(data,response) {
				$.each(data, function(id,name) {
					if ((id != '_default') && (id.trim() != "") && ($('#event option[value="' + id.trim() + '"]').length == 0)) {
						$('#event').append('<option value="' + id + '">' + name + '</option>');
					}
					$('#event').prop('disabled', false);
				});
				if (default_event == -1) { default_event = data['_default']['event']; }
				if (default_game == -1) { default_game = data['_default']['game']; }
			}
		).success(function() {
			changeStatus();
			if (first_load) {
				if (default_event.toString() == parseInt(default_event).toString()) {
					$('#event').val($('#event option:eq(' + default_event + ')').val());
				} else {
					$('#event').val(default_event);
				}
				$('#event').change()
			}
			return true;
		}).fail(function(e) {
			changeStatus('connect-failed');
			return false;
		});
	}
}

function loadTioFile(reload) {
	// History
	page_title = "tio → HTML -- " + $('#event option:selected').html();
	document.title = page_title;
	if (!first_load) {
		history.pushState(null, null, window.location.toString().split("?")[0] + "?event=" + selected_event + "&game=" + selected_game + "&display=" + $('#display').val());
	}
	
	if ((typeof reload != 'undefined') && (reload == true)) {
		reloading = true;
	} else {
		reloading = false;
	}
	
	if (selected_event == "-1") {
		$('#game').html('<option value="-1">Select a game</option>');
		$('#game').prop('disabled', true);
	}
	
	if ((!first_load) && (selected_event == -1)) {
		winl.clearRect(0,wel.width,0,wel.height);
		losl.clearRect(0,lel.width,0,lel.height);
		
		$('#bracket').css('display', 'none');
		changeStatus();
		return false;
	}
	
	if (reloading) {
		$('#game').html('<option value="-1">Select a game</option>');
		$('#game').prop('disabled', false);
		$('#game').val(selected_game);
	}
	changeStatus('loading');
	
	succeeded = false;
	loading = true;
	
	$.getJSON("?get="+selected_event,
		function(data) {
			tio = data;
			
			if (typeof tio[selected_event] != 'undefined') {
				// $('#game').prop('disabled', true);
				$('#game').html('<option value="-1">Select a game</option>');
				$.each(tio[selected_event].games, function(game,game_array) {
					$('#game').append('<option value="' + game + '">' + game_array.name + '</option>');
				});
				$('#game').prop('disabled', false);
				changeStatus();
			} else {
				$('#game').prop('disabled', true);
				changeStatus('loading');
			}
			
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

function drawBracket() {
	// History
	page_title = "tio → HTML -- " + $('#event option:selected').html() + "/" + $('#game option:selected').html();
	document.title = page_title;
	if (!first_load) {
		history.pushState(null, null, window.location.toString().split("?")[0] + "?event=" + selected_event + "&game=" + selected_game + "&display=" + $('#display').val());
	}
	
	$('#loser_lines, #winner_lines').css('display', 'none');
	winl.clearRect(0,wel.width,0,wel.height);
	losl.clearRect(0,lel.width,0,lel.height);
	
	$('#bracket, #results').css('display', 'none');
	if ((selected_event == -1) || (selected_game == -1)) {
		return false;
	}
	
	$('#to_title').html($('#event option:selected').text() + " - " + tio[selected_event]['games'][selected_game]['name']);
	$('#to_game').html(tio[selected_event]['games'][selected_game]['game']);
	$('#to_game').append(' - ' + tio[selected_event]['games'][selected_game]['entrants'] + ' entrants'); // Number of Players
	
	$('#loser_rounds').html('');
	$('#loser_matches').html('');
	$('#winner_rounds').html('');
	$('#winner_matches').html('');
	
	$('body').css('width', 'auto');
	switch (selected_display) {
		case 1: // results
			$('#listings').html('');
			$('#results').css('display', 'block');
			$('#listings').append('<div class="heading">Top 8</div>');
			prev_key = -1;
			$.each(tio[selected_event]['games'][selected_game]['results'], function(key,val) {
				if  (Object.keys(val).length > 0) {
					if ((parseInt(key) > 8) && (prev_key <= 8)) {
						$('#listings').append('<div class="heading">The Rest</div>');
					}
					prev_key = parseInt(key);
					
					$('#listings').append('<div class="placing-row placing-'+key+'"><div class="placing-number">' + key + '</div><div class="placing-players"></div></div>');
					$.each(val, function(key2,val2) {
						$('.placing-players').last().append('<div class="player">'+val2['tag']+'</div>');
					});
				}
			});
			$.each($('.placing-players'), function(key,el) {
				if ($(this).find('.player').length > 1) {
					$(this).find('.player:even').addClass('odd');
				}
			});
			break;
		default:
			$('#bracket').css('display', 'block');
			used_rounds = [];
			winners_round_count = 0;
			losers_round_count = 0;
			$.each(tio[selected_event]['games'][selected_game]['matches'], function(key,match) {
				if ($.inArray(match['round'], used_rounds) == -1) {
					if (parseInt(match['round']) > 0) {
						winners_round_count++;
					}
					if (parseInt(match['round']) < 0) {
						losers_round_count++;
					}	
					used_rounds.push(match['round']);
				}
			});
			$('#loser_lines, #winner_lines').css('display', 'block');
			$.each(tio[selected_event]['games'][selected_game]['matches'], function(key,match) {
				if ($('#bracket .round-' + match['round']).length == 0) {
					new_round_row = '<div class="column match-column round-'+match['round']+'" round="'+match['round']+'"></div>';
					if (parseInt(match['round']) > 0) {
						$('#winner_rounds').append('<div class="column round-column" round="'+match['round']+'">'+getRound(match['round'])+'</div>');
						$('#winner_matches').append(new_round_row);
					} else {
						$('#loser_rounds').append('<div class="column round-column" round="'+match['round']+'">'+getRound(match['round'])+'</div>');
						$('#loser_matches').append(new_round_row);
					}
				}
				
				// Score correction for cancelled matches
				if ((match['in_progress']) ||
					(
						((match['s1'] != 0) || (match['s2'] != 0))
						&& ((match['winner'] != "00000000-0000-0000-0000-000000000000") || (match['winner'] != "00000001-0001-0001-0101-010101010101"))
					))			{
					match['s1'] = "";
					match['s2'] = "";
				}
				
				new_match  = '<div class="match" match-id="'+match['id']+'" winner-id="'+match['winner']+'" in-progress="'+match['in_progress']+'">';
				if (typeof tio[selected_event]['stations_ez'][selected_game + ":" + key] != 'undefined') {
					setup_name = tio[selected_event]['stations_ez'][selected_game + ":" + key];
					if (setup_name.toLowerCase().indexOf("stream") > -1) {
						new_match += '<div class="setup stream">'+setup_name+'</div>';
					} else {
						new_match += '<div class="setup">'+setup_name+'</div>';
					}
				} else {
					new_match += '<div class="setup none">&nbsp;</div>';
				}
				
				// Player Seeding
				if (match['p1'].seed == -1) {
					seed_p1 = "";
				} else {
					seed_p1 = parseInt(match['p1'].seed) + 1;
				}
				
				if (match['p2'].seed == -1) {
					seed_p2 = "";
				} else {
					seed_p2 = parseInt(match['p2'].seed) + 1;
				}
				
				new_match += '<div class="players">';
				new_match += '<div class="player player1" player-seed="'+seed_p1+'"><div class="player-seed">'+seed_p1+'</div><div class="player-tag" player-id="'+match['p1'].id+'">'+match['p1'].tag+'</div><div class="player-id">'+match['p1'].id+'</div><div class="player-score">'+match['s1']+'</div></div>';
				new_match += '<div class="sep"></div>';
				new_match += '<div class="player player2" player-seed="'+seed_p2+'"><div class="player-seed">'+seed_p2+'</div><div class="player-tag" player-id="'+match['p2'].id+'">'+match['p2'].tag+'</div><div class="player-id">'+match['p2'].id+'</div><div class="player-score">'+match['s2']+'</div></div>';
				new_match += '</div>';
				new_match += '</div>';
				
				if (parseInt(match['round']) > 0) {
					$('#winner_matches .round-'+match['round']).append(new_match);
				} else {
					$('#loser_matches .round-'+match['round']).append(new_match);
				}
				if ((match['winner'] != "00000000-0000-0000-0000-000000000000") && (match['winner'] != "00000001-0001-0001-0101-010101010101")) {
					if ((match['s1'] == "") || (match['s2'] == "")) {
						// $('[player-id="'+match['winner']+'"]').last().closest('.player').find('.player-score').html('<div class="win-circle"></div>');
						$('[match-id="'+match['id']+'"]').find('[player-id="'+match['winner']+'"]').closest('.player').addClass('winner');
						$('[match-id="'+match['id']+'"]').find('[player-id="'+match['winner']+'"]').closest('.player').find('.player-score').html('&check;');
						$('[match-id="'+match['id']+'"]').find('[player-id="'+match['winner']+'"]').closest('.player').find('.player-score').addClass('check');
					} else {
						$('[match-id="'+match['id']+'"]').find('[player-id="'+match['winner']+'"]').closest('.player').addClass('winner');
					}
				}
			});
			
			$.each(highlight, function(id,hi) {
				if (hi != -1) {
					$('[player-id="'+id+'"]').closest('.player').addClass('highlight');
					$('[player-id="'+id+'"]').closest('.player').addClass('highlight-'+hi);
				}
			});
			
			$('.player-tag:contains("&nbsp;")').closest('.player').css({
				'font-style': 'italic',
				'color': 'rgba(255,255,255,0)'
			});
			$('.player-tag:contains("&nbsp;")').closest('.player').find('.player-id').css({
				'font-style': 'italic',
				'color': 'rgba(255,255,255,0.5)'
			});
			$('.player-tag:contains(Bye)').closest('.player').css({
				'font-style': 'italic',
				'color': 'rgba(255,255,255,0.5)'
			});
			
			$('body').css('width', losers_round_count * 262);
			$('canvas').css({
				'width': $('#losers').outerWidth(),
				'height': $('#losers').outerHeight()
			});
			$('#winner_lines').css('height', $('#winner_matches').height());
			$('#loser_lines').css('height', $('#loser_matches').height());
			
			if (
				($('.match-column.round-'+winners_round_count+' .player1 .player-tag').html() == "&nbsp;") && ($('.match-column.round-'+winners_round_count+' .player2 .player-tag').html().trim() == "&nbsp;")
				|| ($('.match-column.round-'+winners_round_count+' .player1 .player-tag').attr('player-id') == "00000000-0000-0000-0000-000000000000") && ($('.match-column.round-'+winners_round_count+' .player2 .player-tag').attr('player-id') == "00000000-0000-0000-0000-000000000000")
				|| (typeof $('.match-column.round-'+winners_round_count+' .player1 .player-tag').attr('player-id') == "undefined") && (typeof $('.match-column.round-'+winners_round_count+' .player2 .player-tag').attr('player-id') == "undefined")
				) {
				draw_gf2 = false;
				$('#winner_rounds .round-column').last().css('visibility', 'hidden');
				$('#winner_matches .match-column').last().css('visibility', 'hidden');
			} else {
				draw_gf2 = true;
			}
			
			$.each($('canvas'), function(key,el) {
				$(el).attr({
					'width': $(el).css('width'),
					'height': $(el).css('height')
				});
			});
			
			s = 0;
			e = 0;
			ya1 = -1;
			ya2 = -1;
			adjust_yw = 0;
			adjust_yl = 0;
			debug_grid = false;
			x_split = 26;
			line_width = 6;
			
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
			
			winl.strokeStyle = '#ccc';
			winl.lineWidth = line_width;
			
			$.each($('#winners .match-column'), function(key,el) {
				s = $(el).find('.match .sep').length;
				e = $('#winners .match-column:eq('+(key + 1)+')').find('.match .sep').length;
				
				if (((key + 2 == winners_round_count) && (draw_gf2 == true)) || ((key + 2) < winners_round_count)) {
					if (e > 0) {
						if (s == e) {
							$.each($(el).find('.match'), function(key2,el2) {
								tx = parseInt($('#winners').position().left)
									+ parseInt($(el2).position().left)
									+ parseInt($(el2).width());
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
										+ parseInt($(el2).width());
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
			
			losl.strokeStyle = '#ccc';
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
								+ parseInt($(el2).width());
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
									+ parseInt($(el2).width());
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
			
			round_count = 0;
			
			// Highlight color on mouseover
			$('.player').mouseover(function(key,el) {
				get_pid = $(this).find('.player-tag').attr('player-id');
				if (get_pid.indexOf('0000000') != 0) {
					$('[player-id="'+get_pid+'"]').closest('.player').addClass('hover');
				}
			});
			// Highlight color on click
			$('.player').click(function(key,el) {
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
			$('.player').mouseout(function(key,el) {
				$('.player.hover').removeClass('hover');
			});
			
			$('.round-column').mouseover(function(key,el) {
				$('[round="'+ $(this).attr('round') + '"]').addClass('hover');
			});
			$('.round-column').mouseout(function(key,el) {
				$('.hover').not('.player').removeClass('hover');
			});
			
			fixGames();
			break;
	}
}

function reloadBracket() {
	$('#title').css('color', '#09F');
	if (!loading) {
		$('#title').css('color', '#F90');
		loading = true;
		$.ajax('?get='+selected_event+'&md5', {
			data: 'text',
			success: function(data) {
				// $('#md5_tooltip').html(md5 + "<br>" + data);
				if (md5 == data) {
					$('#title').css('color', '#F00');
					$('#title').animate({'color': '#FFF'}, 300);
					loading = false;
				} else {
					$('#title').css('color', '#0F0');					
					$('#title').animate({'color': '#FFF'}, 300);
					md5 = data;
					
					$('#event').val(selected_event);
					loadTioFile(true);
				}
			}
		});
	}
}

function startAuto() {
	if (typeof autoload_timer == 'undefined') {
		autoload_timer = setInterval("reloadBracket()", 10000);
	}
}

function clearAuto() {
	if (typeof autoload_timer != 'undefined') {
		clearInterval(autoload_timer);
	}
}

function doDrag(e) {
	mouse_x = e.pageX;
	mouse_y = e.pageY;
	start_pos_x = $(document).scrollLeft();
	start_pos_y = $(document).scrollTop();
	window.scrollTo(start_pos_x + (start_mouse_x - mouse_x), start_pos_y + (start_mouse_y - mouse_y));
}

$(document).ready(function() {
	autoload = true;
	loading = false;
	tio = -1;
	selected_event = -1;
	selected_game = -1;
	selected_display = -1;
	default_event = -1;
	default_game = -1;
	default_display = -1;
	md5 = -1;
	first_load = true;
	winners_round_count = 0;
	losers_round_count = 0;
	
	drag = false;
	mouse_x = 0;
	mouse_y = 0;
	start_pos_x = 0;
	start_pos_y = 0;
	start_mouse_x = 0;
	start_mouse_y = 0;
	
	highlight = {};
	games = [];
	
	wel = document.getElementById('winner_lines');
	lel = document.getElementById('loser_lines');
	winl = wel.getContext("2d");
	losl = lel.getContext("2d");
	md5 = -1;
	
	if ((window.location.toString().indexOf("?") > -1) && (window.location.toString().indexOf("&") > -1)) {
		url_split = window.location.toString().split("&");
		url_split[0] = url_split[0].split("?")[1];
		get = {};
		$.each(url_split, function(key, val) {
			get[val.split("=")[0]] = val.split("=")[1];
		});
		
		// $_GET
		if (typeof get['event'] != 'undefined') {
			default_event = get['event'];
			selected_event = get['event'];
		}
		if (typeof get['game'] != 'undefined') {
			default_game = get['game'];
			selected_game = get['game'];
		}
		if (typeof get['display'] != 'undefined') {
			default_display = parseInt(get['display']);
			selected_display = parseInt(get['display']);
		}
	}
	
	$('#event').prop('disabled', true);
	$('#game').prop('disabled', true);

	$('#event').change(function() {
		selected_event = $('#event').val();
		loadTioFile();
		clearAuto();
	});
	$('#game').change(function() {
		selected_game = $('#game').val();
		drawBracket();
		startAuto();
	});
	$('#display').change(function() {
		selected_display = parseInt($('#display').val());
		drawBracket();
	});
	
	if (autoload) {
		getEvents();
	}
	
	$('#title').on('mouseover mouseout', function() {
		if ($('#md5_tooltip').css('display') == 'none') {
			$('#md5_tooltip').css('display', 'inline-block');
		} else {
			$('#md5_tooltip').css('display', 'none');
		}
	});
	
	$('#title').click(function() {
		reloadBracket();
		drawBracket();
	});
	
	$('#winner_lines, #loser_lines').mousedown(function(e) {
		if (e.which == 1) {
			drag = true;
			mouse_x = e.pageX;
			mouse_y = e.pageY;
			start_pos_x = $(document).scrollLeft();
			start_pos_y = $(document).scrollTop();
			start_mouse_x = e.pageX;
			start_mouse_y = e.pageY;
		}
	});
	$(window).mouseup(function(e) {
		if (e.which == 1) {
			drag = false;
		}
	});
	$(window).mousemove(function(e) {
		if (drag) {
			doDrag(e);
		}
	});
});

var zoom = document.documentElement.clientWidth / window.innerWidth;
$(window).resize(function() {
	var zoomNew = document.documentElement.clientWidth / window.innerWidth;
	if (zoom != zoomNew) {
		drawBracket();
	}
});