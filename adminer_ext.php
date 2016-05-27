<?php
$adminer = $_SERVER['argv'][1] ?? 'adminer-in.php';
$adminer = file_get_contents($adminer);

$adminer = str_replace('<div id="help"', file_get_contents('custom_css.inc') . '<div id="help"', $adminer);

$compiled = fopen('adminer.php', 'w');

file_exists(__DIR__ . '/adminer.css') && fwrite($compiled, '<?php if($_GET["file"]=="adminer.css"){header("Content-Type: text/css; charset=utf-8");echo lzw_decompress(\'' . add_apo_slashes(minify_css_file(__DIR__ . '/adminer.css')) . '\');die;}?>');

$extension = file_get_contents('extension.php');

fwrite($compiled, $extension);
fwrite($compiled, $adminer);
fclose($compiled);

$adminer = file_get_contents('adminer.php');
$adminer = gzcompress($adminer);
$start = '<?php
$fp = fopen(__FILE__, \'r\');
fseek($fp, __COMPILER_HALT_OFFSET__);
$payload = gzuncompress(stream_get_contents($fp));
fclose($fp);
eval(\'?>\'.$payload);
__halt_compiler();';

file_put_contents('adminer.php', $start . $adminer);

function lzw_compress($string) {
	// compression
	$dictionary = array_flip(range("\0", "\xFF"));
	$word = "";
	$codes = array();
	for ($i=0; $i <= strlen($string); $i++) {
		$x = $string[$i];
		if (strlen($x) && isset($dictionary[$word . $x])) {
			$word .= $x;
		} elseif ($i) {
			$codes[] = $dictionary[$word];
			$dictionary[$word . $x] = count($dictionary);
			$word = $x;
		}
	}
	// convert codes to binary string
	$dictionary_count = 256;
	$bits = 8; // ceil(log($dictionary_count, 2))
	$return = "";
	$rest = 0;
	$rest_length = 0;
	foreach ($codes as $code) {
		$rest = ($rest << $bits) + $code;
		$rest_length += $bits;
		$dictionary_count++;
		if ($dictionary_count >> $bits) {
			$bits++;
		}
		while ($rest_length > 7) {
			$rest_length -= 8;
			$return .= chr($rest >> $rest_length);
			$rest &= (1 << $rest_length) - 1;
		}
	}
	return $return . ($rest_length ? chr($rest << (8 - $rest_length)) : "");
}

function lzw_compress_file($file) {
	return lzw_compress(file_get_contents($file));
}

function minify_css($file) {
	return lzw_compress(preg_replace('~\\s*([:;{},])\\s*~', '\\1', preg_replace('~/\\*.*\\*/~sU', '', $file)));
}

function minify_css_file($file) {
	return minify_css(file_get_contents($file));
}

function add_apo_slashes($s) {
	return addcslashes($s, "\\'");
}
