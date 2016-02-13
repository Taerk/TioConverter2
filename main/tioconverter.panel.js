/**
 * TioConverter2
 */
 
/**
 * Add a Tio File
 */

var file;
var xmlfile;
var download_url;

$(document).ready(function() {
	function checkResponse() {
		$('#add_bracket_error_result').val('');
		// Get file
		$.post('download', {'file': $('#tio-check-url').val()},
			function(response) {
				if (typeof response.data != 'undefined') {
					$('#add_bracket_error_result').val(response.data);
				} else {
					$('#add_bracket_error_result').val(response);
				}
				
				switch (response.response) {
					case 200:
						$('#add_bracket_progress .check:eq(0)').animate({'background-color': '#090'}, 200);
						file = response.data.trim();
						checkXmlFormat();
						break;
					case 403:
						$('#add_bracket_progress .check:eq(0)').animate({'background-color': '#900'}, 200);
						$('#add_bracket_error').animate({'opacity': 1}, 300);
						$('#add_bracket_error').html('Error: 403 Forbidden');
						break;
					case 404:
						$('#add_bracket_progress .check:eq(0)').animate({'background-color': '#900'}, 200);
						$('#add_bracket_error').animate({'opacity': 1}, 300);
						$('#add_bracket_error').html('Error: 404 File Not Found');
						break;
					case 410:
						$('#add_bracket_progress .check:eq(0)').animate({'background-color': '#900'}, 200);
						$('#add_bracket_error').animate({'opacity': 1}, 300);
						$('#add_bracket_error').html('Error: File has been removed');
						break;
					case 500:
						$('#add_bracket_progress .check:eq(0)').animate({'background-color': '#900'}, 200);
						$('#add_bracket_error').animate({'opacity': 1}, 300);
						$('#add_bracket_error').html('Error: 500 Internal Server Error');
						break;
					case -1:
						$('#add_bracket_progress .check:eq(0)').animate({'background-color': '#900'}, 200);
						$('#add_bracket_error').animate({'opacity': 1}, 300);
						$('#add_bracket_error').html('Error: No URL set');
						break;
					default:
						$('#add_bracket_progress .check:eq(0)').animate({'background-color': '#900'}, 200);
						$('#add_bracket_error').animate({'opacity': 1}, 300);
						$('#add_bracket_error').html('Error: Returned HTTP code ' + response.response);
						break;
				}
			},
			'json'
		);
	}
	
	function checkXmlFormat() {
		$('#add_bracket_progress .check:eq(1)').animate({
			'opacity': 1,
			'background-color': '#aaa',
			'color': '#fff'
		});
		
		if (file != "") {
			try {
				xmlfile = $.parseXML(file);
				$('#add_bracket_progress .check:eq(1)').animate({'background-color': '#090'}, 200);
				checkTioFormat();
			} catch(e) {
				$('#add_bracket_error').animate({'opacity': 1}, 300);
				if (file.indexOf("<?xml") != 0) {
					$('#add_bracket_error').html('Error: Not an XML file');
				} else {
					$('#add_bracket_error').html('Error: Invalid XML');
				}
				$('#add_bracket_progress .check:eq(1)').animate({'background-color': '#900'}, 200);
			}
		} else {
			$('#add_bracket_error').animate({'opacity': 1}, 300);
			$('#add_bracket_error').html('Error: File is empty');
		}
	}
	
	function checkTioFormat() {
		$('#add_bracket_progress .check:eq(2)').animate({
			'opacity': 1,
			'background-color': '#aaa',
			'color': '#fff'
		});
		
		try {
			if ($(xmlfile).find(":root")[0].tagName == "AppData"
				&& $(xmlfile).find(":root").children()[2].tagName == "EventList"
				&& $(xmlfile).find('AppData:eq(0) EventList:eq(0) Event:eq(0) ID:eq(0)').length == 1) {
				$('#add_bracket_progress .check:eq(2)').animate({'background-color': '#090'}, 200, function() {
					allDone();
				});				
			} else {
				$('#add_bracket_error').animate({'opacity': 1}, 300);
				$('#add_bracket_error').html('Error: Invalid tio file');
				$('#add_bracket_progress .check:eq(2)').animate({'background-color': '#900'}, 200);
			}
		} catch(e) {
			$('#add_bracket_error').animate({'opacity': 1}, 300);
			$('#add_bracket_error').html('Error: ' + e);
			$('#add_bracket_progress .check:eq(2)').animate({'background-color': '#900'}, 200);
		}
	}
	
	function allDone() {
		$('#tio-tourney-id').val($(xmlfile).find('AppData:eq(0) EventList:eq(0) Event:eq(0) ID:eq(0)').text());
		$('#tio-tourney-name').val($(xmlfile).find('AppData:eq(0) EventList:eq(0) Event:eq(0) Name:eq(0)').text());		
		$('#tio-tourney-permalink').val($('#tio-tourney-name').val().toLowerCase().replace().replace(/\W+/g, " ").replace(/\s+/g, '-'));
		
		// Populate Events
		$('#tio-tourney-default').html('');
		$.each($(xmlfile).find('Games:eq(0) Game'), function(key,game) {
			$('#tio-tourney-default').append('<option value="' + $(game).find('ID:eq(0)').text() + '">' + $(game).find('Name:eq(0)').text() + '</option>');
		});
		
		$('#add_bracket').css('display', 'block');
		$('#add_bracket').animate({'opacity': 1}, 500);
		
		$('#tio-tourney-download').val(download_url);
		
	}
	
	$('#check_bracket').submit(function(e) {
		e.preventDefault();
		
		download_url = $('#tio-check-url').val()
		
		// Reset appearance
		$('#add_bracket, h1.result').animate({'opacity': 0}, 500, function() {
			$('#add_bracket').css('display', 'none');
			$('h1.result').remove();
		});
				
		$('#add_bracket_error').animate({'opacity': 0}, 300);
		$('#add_bracket_error').html('&nbsp;');
		$('#add_bracket_progress .check:gt(0)').animate({
			'background-color': 'transparent',
			'color': '#222'
		}, 200);
		$('#add_bracket_progress .check:eq(0)').animate({
			'opacity': 1,
			'background-color': '#aaa',
			'color': '#fff'
		}, 300, function() {
			checkResponse();
		});
	});
	
	$('#add_bracket').submit(function(e) {
		e.preventDefault();
		$.post('?action=add', {'data': $('#add_bracket').serialize()},
			function(response) {
				if (response.result) {
					$('#add_bracket_error').css({
						'opacity': 0
					});
					$('#add_bracket').animate({
						'opacity': 0
					}, 500, function() {
						if ($('h1.result').length == 0) {
							$('#add_bracket').before('<h1 class="result">Successfully Added!</h1>');
						}
						$('h1.result').animate({'opacity': 1}, 500);
					});
				} else {
					$('#add_bracket_error').css({
						'opacity': 1
					});
					$('#add_bracket_error').html(response.message);
				}
			},
			'json'
		);
	});
	
	/**
	 * Modify a Tio File
	 */
	
	/**
	 * Validation Checks
	 */
	
	// Auto-permalink when typing in tournament name
	$('#tio-tourney-name').on('keyup focus', function() {
		if ($('#tio-tourney-permalink-auto').val() == 1) {
			$('#tio-tourney-permalink').val($('#tio-tourney-name').val().toLowerCase().replace().replace(/\W+/g, " ").replace(/\s+/g, '-'));
		}
	});
	
	// Disable auto-permalink when typing in permalink field
	$('#tio-tourney-permalink').keydown(function() {
		$('#tio-tourney-permalink-auto').val(0);
		$('#tio-tourney-permalink-auto').removeClass('btn-primary');
		$('#tio-tourney-permalink-auto').addClass('btn-danger');
	});
	
	// Filter permalink when auto
	$('#tio-tourney-permalink').keyup(function() {
		var original = $('#tio-tourney-permalink').val();
		$('#tio-tourney-permalink').val(original.toLowerCase().replace().replace(/\W+/g, " ").replace(/\s+/g, '-'));
	});
	
	// Toggle auto-permalink
	$('#tio-tourney-permalink-auto').click(function() {
		if ($('#tio-tourney-permalink-auto').val() == 0) {
			$('#tio-tourney-permalink-auto').val(1);
			$('#tio-tourney-permalink-auto').removeClass('btn-danger');
			$('#tio-tourney-permalink-auto').addClass('btn-primary');
		} else {
			$('#tio-tourney-permalink-auto').val(0);
			$('#tio-tourney-permalink-auto').removeClass('btn-primary');
			$('#tio-tourney-permalink-auto').addClass('btn-danger');
		}
	});
});
