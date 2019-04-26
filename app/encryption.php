<?php
// The IV field will store the initialisation vector used for encryption. The storage requirements depend on the cipher and mode used. The password field will be hashed using a one-way password hash.
if (!function_exists('openEncrypt')) {
	function openEncrypt(string $string) {
		$method = 'AES-256-CBC';
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
		$key = substr(hash('sha256', $iv), 0, 16);

		return [
			'string' => openssl_encrypt($string, $method, $key, $options = 0, $iv),
			'method' => $method,
			'key' => $key,
			'iv' => $iv
		];
	}
}

if (!function_exists('openDecrypt')) {
	function openDecrypt(string $string, string $key, string $iv) {
		$method = 'AES-256-CBC';
		return openssl_decrypt($string, $method, $key, $options = 0, $iv);
	}
}