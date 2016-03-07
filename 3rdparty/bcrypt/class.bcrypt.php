<?php
class Bcrypt {
	private $rounds;
	public function __construct($rounds = 12) {
		if (CRYPT_BLOWFISH != 1) {
			throw new Exception("bcrypt not supported in this installation. See http://php.net/crypt");
		}

		$this->rounds = $rounds;
	}

	public function hash($input) {
		$hash = crypt($input, $this->getSalt());

		if (strlen($hash) > 13)
		return $hash;

		return false;
	}

	public function verify($input, $existingHash) {
		$hash = crypt($input, $existingHash);

		// return $hash === $existingHash;
		if ($hash === $existingHash) {
			return 1;
		} else {
			return 0;
		}
	}

	private function getSalt() {
		$salt = sprintf('$2a$%02d$', $this->rounds);

		$bytes = $this->getRandomBytes(16);

		$salt .= $this->encodeBytes($bytes);

		return $salt;
	}

	private $randomState;
	public function getRandomBytes($count) {
		$bytes = '';

		if (function_exists('openssl_random_pseudo_bytes') &&
				(strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')) { // OpenSSL slow on Win
			$bytes = openssl_random_pseudo_bytes($count);
		}

		if ($bytes === '' && is_readable('/dev/urandom') &&
				($hRand = @fopen('/dev/urandom', 'rb')) !== FALSE) {
			$bytes = fread($hRand, $count);
			fclose($hRand);
		}

		if (strlen($bytes) < $count) {
			$bytes = '';

			if ($this->randomState === null) {
				$this->randomState = microtime();
				if (function_exists('getmypid')) {
					$this->randomState .= getmypid();
				}
			}

			for($i = 0; $i < $count; $i += 16) {
				$this->randomState = md5(microtime() . $this->randomState);

				if (PHP_VERSION >= '5') {
					$bytes .= md5($this->randomState, true);
				} else {
					$bytes .= pack('H*', md5($this->randomState));
				}
			}

			$bytes = substr($bytes, 0, $count);
		}

		return $bytes;
	}

	private function encodeBytes($input) {
		// The following is code from the PHP Password Hashing Framework
		$itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

		$output = '';
		$i = 0;
		do {
			$c1 = ord($input[$i++]);
			$output .= $itoa64[$c1 >> 2];
			$c1 = ($c1 & 0x03) << 4;
			if ($i >= 16) {
				$output .= $itoa64[$c1];
				break;
			}

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 4;
			$output .= $itoa64[$c1];
			$c1 = ($c2 & 0x0f) << 2;

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 6;
			$output .= $itoa64[$c1];
			$output .= $itoa64[$c2 & 0x3f];
		} while (1);

		return $output;

	}
}
$bcrypt = new Bcrypt();

function password_hash($password) {
	$bcrypt->hash($password);
}
function password_verify($password, $hash) {
	$bcrypt->verify($password, $hash);
}

if (isset($_GET['debug'])) {
	$debug = 1;
	$hash = $bcrypt->hash($_GET['debug']);
} else {
	$debug = 0;
}
if ($debug) {

	echo "<fieldset><legend>Should return 1</legend>";
	echo $hash."<br>";
	$isGood = $bcrypt->verify($_GET['debug'], $hash);
	echo "&rarr; ".$isGood;
	echo "</fieldset>";

	echo "<fieldset><legend>Should return 0</legend>";
	echo $hash."<br>";
	$hash = "sdfsdf";
	$isGood = $bcrypt->verify($_GET['debug'], $hash);
	echo "&rarr; ".$isGood;
	echo "</fieldset>";
	
	if (isset($_GET['compare'])) {
		$hash = $_POST['compare'];
	
		echo "<fieldset><legend>Manual comparison</legend>";
		echo '<form action="" method="post">';
		echo "<input type='text' name='compare' />";
		echo "<input type='submit'/>";
		echo '</form>';
		if (isset($_POST['compare'])) {
			echo "Comparing <span style='color: green'>".$_GET['debug']."</span> to <span style='color: blue'>".$_POST['compare']."</span><br>";
			$isGood = $bcrypt->verify($_GET['debug'], $hash);
			echo "&rarr; ".$isGood;
		}
		echo "</fieldset>";
	}
}
?>