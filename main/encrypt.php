<?php
include('../settings.php');
?>

<style type="text/css">
input { width: 500px }
label, input[type="submit"] {
	display: block;
	margin-top: 5px;
}
input[type="submit"] { width: auto }
input[readonly] { background-color: #ddd; border: 1px solid #666 }
</style>

<fieldset>
	<legend>Encrypt</legend>
	<form action="" method="get">
		<div>
			<label>Password</label>
			<input type="text" name="encrypt"<?php if (isset($_GET['encrypt'])) { echo ' value="'.$_GET['encrypt'].'"'; } ?>>
		</div>
		
		<input type="submit">
	</form>
	
	<input type="text" value="<?php if (isset($_GET['encrypt'])) { echo password_hash($_GET['encrypt'], PASSWORD_BCRYPT); } else { echo '&nbsp;'; } ?>" readonly>
</fieldset>

<fieldset>
	<legend>Decrypt</legend>
	<form action="" method="get">
		<div>
			<label>Password</label>
			<input type="text" name="decrypt"<?php if (isset($_GET['decrypt'])) { echo ' value="'.$_GET['decrypt'].'"'; } ?>>
		</div>
		
		<div>
			<label>Hash</label>
			<input type="text" name="hash"<?php if (isset($_GET['hash'])) { echo ' value="'.$_GET['hash'].'"'; } ?>>
		</div>
		
		<input type="submit">
	</form>
	
	<input type="text" value="<?php if (isset($_GET['decrypt'])) { if (password_verify($_GET['decrypt'], $_GET['hash'])) { echo 'true'; } else { echo 'false'; } } else { echo '&nbsp;'; } ?>" readonly>
</fieldset>