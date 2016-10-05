<!DOCTYPE>
<html>
	<head>
		<title>TioConverter2 - Under Maintenance</title>
		<link rel="stylesheet" type="text/css" href="/main/tioconverter.bracket.front.css">
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
		<style type="text/css">
		body {
			display: flex;
			justify-content: center;
			align-items: center;
			align-content: center;
			margin: 0;
			padding: 0;
		}
		h1 {
			font-size: 6em;
			margin: 0;
			padding: 0;
			text-align: center;
		}
		</style>
		<script type="text/javascript">
		$(document).ready(function() {
			x = 0;
			
			function moveBg() {
				console.log('tick');
				x += 0.5;
				$('body').css('background-position', -x + 'px 0px')
			}
			
			setInterval(function() { moveBg(); }, 30);
		});
		</script>
	</head>
	<body>
		<h1>Site is under maintenance</h1>
	</body>
</html>