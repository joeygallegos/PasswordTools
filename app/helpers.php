<?php
if (!function_exists('slugify')) {
	function slugify($text) {
		// replace non letter or digits by -
		$text = preg_replace('~[^\pL\d]+~u', '-', $text);

		// transliterate
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

		// remove unwanted characters
		$text = preg_replace('~[^-\w]+~', '', $text);

		// trim
		$text = trim($text, '-');

		// remove duplicate -
		$text = preg_replace('~-+~', '-', $text);

		// lowercase
		$text = strtolower($text);

		if (empty($text)) {
			return 'n-a';
		}
		return $text;
	}
}

if (!function_exists('truncateText')) {
	function truncateText($text, $chars = 25) {
		$text = $text . ' ';
		$text = substr($text, 0, $chars);
		$text = substr($text, 0, strrpos($text, ' '));
		return $text . '...';
	}
}

if (!function_exists('sanitize')) {
	function sanitize($string) {
		return htmlspecialchars(strip_tags($string));
	}
}

if (!function_exists('isNullOrEmptyString')) {
	function isNullOrEmptyString($string) {
		return (!isset($string) || trim($string) === '');
	}
}

if (!function_exists('getBaseDirectory')) {
	function getBaseDirectory() {
		return dirname(__DIR__);
	}
}

if (!function_exists('directoryExists')) {
	function directoryExists($dir) {
		return file_exists($dir);
	}
}
if (!function_exists('createDirectory')) {
	function createDirectory($dir) {
		if (!file_exists($dir)) {
			mkdir($dir, 0777, true);
			return true;
		}
		return false;
	}
}

if (!function_exists('createFile')) {
	function createFile($dir, $filename, $ext, $data) {
		if (!file_exists($dir)) {
			mkdir($dir, 0777, true);
		}
		$file = fopen($dir . $filename . '.' . $ext, 'w');
		fwrite($file, $data);
		fclose($file);
	}
}

if (!function_exists('getRequestAddress')) {
	function getRequestAddress() {
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			return $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			return $_SERVER['REMOTE_ADDR'];
		}
	}
}

if (!function_exists('errorCodeToMessage')) {
	function errorCodeToMessage($code) {
		switch ($code) {
			case UPLOAD_ERR_INI_SIZE:
				$message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$message = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
				break;
			case UPLOAD_ERR_PARTIAL:
				$message = 'The uploaded file was only partially uploaded';
				break;
			case UPLOAD_ERR_NO_FILE:
				$message = 'No file was uploaded';
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$message = 'Missing a temporary folder';
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$message = 'Failed to write file to disk';
				break;
			case UPLOAD_ERR_EXTENSION:
				$message = 'File upload stopped by extension';
				break;
			default:
				$message = 'Unknown upload error';
				break;
		}
		return $message;
	}
}

/**
 * Get keys from a multi-dimension array
 * @param  Array $haystack multi-dimension array
 * @return Array of keys from the multi-dimension array
 */
if (!function_exists('getKeysFromArray')) {
	function getKeysFromArray(array $haystack) {
		$keys = [];
		foreach ($haystack as $key => $value) {
			array_push($keys, $key);
		}
		return $keys;
	}
}