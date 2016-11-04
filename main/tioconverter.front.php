<?php
$title_extension = ($tio->loaded ? " - " . $tio->getTournamentName(false) . ' - ' . $tio->getLoadedEvent()['name'] : "");
?><!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<link rel="shortcut icon" type="image/png" href="/etc/images/polarity_compass.png">
		<title>Polarity - Bracket<?php echo $title_extension; ?></title>
		
		<!-- Libraries -->
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
		<!-- <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-mobile/1.4.1/jquery.mobile.min.js"></script> -->
		<script type="text/javascript" src="/3rdparty/jquery/jscolor/jquery.color.plus-names-2.1.2.min.js"></script>
		<script type="text/javascript" src="https://netdna.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
		<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
		<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" type="text/css">
		
		<!-- In-house -->
		<script type="text/javascript" src="/main/tioconverter.bracket.js"></script>
		<?php if ($tio->loaded) {?><script type="text/javascript">
		$(document).ready(function() {
			tioJS = new tioConverterJS();
			tioJS.autoTio(
				'<?php echo $tio->getTournamentId(false); ?>',
				'<?php echo $tio->getEventId(false); ?>',
				<?php if (isset($tio->getTournamentLibrary()['update_interval'])) { echo $tio->getTournamentLibrary()['update_interval'];  } else { echo "60"; } ?>
				
			);
		});
		</script><?php echo "\n"; } ?>
		<link rel="stylesheet" type="text/css" href="/main/tioconverter.bracket.front.css">
	</head>
	<body>
		<?php
        // Load the header
        require_once(CONVERTER . '/tioconverter.front.header.php');
		
        if ($tio->loaded) {
			/**
			 * Handle bracket pages
			 */
            require_once(CONVERTER . '/tioconverter.front.bracket.php');
            
		} else if (defined('SEARCH')) {
			/**
			 * Handle select bracket page
			 */
			require_once(CONVERTER . '/tioconverter.front.search.php');
		
		} else {
			/**
			 * Handle select bracket page
			 */
            require_once(CONVERTER . '/tioconverter.front.list.php');
		
        }
		
        // Load the footer
        require_once(CONVERTER . '/tioconverter.front.footer.php'); ?>
	</body>
</html>
