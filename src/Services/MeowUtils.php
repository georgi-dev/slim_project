<?php
namespace Services;

class MeowUtils
{
	/**
	 * Outputs debugging information in a text file "dump.txt"
	 * {@link http://www.digitalvoivode.com Digital Voivode}
	 * @access public
	 * @author Anastas Dolushanov <adolushanov@gmail.com>
	 * @copyright GPL 2010
	 * @package DV Core
	 * @version 4.0
	 */
	static function dump($item) {
		$fh = fopen(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'dump.txt', "ab");

		switch (true) {
			case is_object($item) === true:
				fputs($fh, "[" . \DateTime::createFromFormat("U.u", microtime(true))->format("Y-m-d H:i:s.u") . "] " . var_export($item, true) . "\r\n");
				break;
			case is_array($item) === true:
				fputs($fh, "[" . \DateTime::createFromFormat("U.u", microtime(true))->format("Y-m-d H:i:s.u") . "] " . print_r($item, true) . "\r\n");
				break;
			default:
				fputs($fh, "[" . \DateTime::createFromFormat("U.u", microtime(true))->format("Y-m-d H:i:s.u") . "] " . (string) $item . "\r\n");
				break;
		}
		fclose ($fh);
	}

	/**
	 * Outputs message and tag in a TSV file "log.tsv"
	 * {@link http://www.digitalvoivode.com Digital Voivode}
	 * @access public
	 * @author Anastas Dolushanov <adolushanov@gmail.com>
	 * @copyright GPL 2016
	 * @package DV Core
	 * @version 4.0
	 */
	static function log($message, $tag = null) {
		$fh = fopen(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'log.tsv', "ab");
		fputs ($fh, \DateTime::createFromFormat("U.u", microtime(true))->format("Y-m-d H:i:s.u") . "\t" . $tag . "\t" . $message . "\r\n");
		fclose ($fh);
	}
	
	
	static function rand_string($length, $punctuation = false) {
		$result = "";
		if($punctuation) {
			$chars = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ~!@#$%^&*_+=');
		} else {
			$chars = str_split('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_=');
		}

		$rounds = ceil($length / 7);

		for($i = 0;$i < $rounds;$i++) {
			$num = hexdec(bin2hex(openssl_random_pseudo_bytes(6)));
			while($num > 1) {
				$result .= $chars[($num % 64)];
				$num /= 64;
			}
		}

		return substr($result, 0, $length);
	}
	
	static function parse_csv($csv_string, $delimiter = ",", $skip_empty_lines = true, $trim_fields = true) {
		$enc = preg_replace('/(?<!")""/', '!!Q!!', $csv_string);
		$enc = preg_replace_callback(
			'/"(.*?)"/s',
			function ($field) {
				return urlencode(utf8_encode($field[1]));
			},
			$enc
		);
		$lines = preg_split($skip_empty_lines ? ($trim_fields ? '/( *\R)+/s' : '/\R+/s') : '/\R/s', $enc);
		return array_map(
			function ($line) use ($delimiter, $trim_fields) {
				$fields = $trim_fields ? array_map('trim', explode($delimiter, $line)) : explode($delimiter, $line);
				return array_map(
					function ($field) {
						return str_replace('!!Q!!', '"', utf8_decode(urldecode($field)));
					},
					$fields
				);
			},
			$lines
		);
	}
}
?>
