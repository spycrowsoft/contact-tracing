<?php

require_once('config.php');

// Check if uuid has the correct format.
define('uuid_regex', '^[0-9a-zA-Z]{8}-[0-9a-zA-Z]{4}-[0-9a-zA-Z]{4}-[0-9a-zA-Z]{4}-[0-9a-zA-Z]{12}$');
function is_uuid($candidate) {
	return preg_match('/' . uuid_regex . '/', $candidate);
}

// Check if session-token has the correct format.
define('session_token_regex', '^[0-9a-fA-F]{36}$');
function is_session_token($candidate) {
	return preg_match('/' . session_token_regex . '/', $candidate);
}

// Regex and function to validate username format.
define('username_regex', '^[\.0-9a-zA-Z]{6,32}$');
function is_valid_username($candidate) {
	return preg_match('/' . username_regex . '/', $candidate);
}

// Regex and function to validate password format.
define('password_regex', '^[0-9a-zA-Z\ \.\?]{8,128}$');
function is_valid_password($candidate) {
	return preg_match('/' . password_regex . '/', $candidate);
}

// Regex and function to validate TOTP format.
define('totp_regex', '^([0-9]{6}|[0-9]{8})$');
function is_valid_totp($candidate) {
	return preg_match('/' . totp_regex . '/', $candidate);
}

// Regex and function to validate an email address.
define('email_regex', '[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$');
function is_valid_email($candidate) {
	return preg_match('/' . email_regex . '/', $candidate);
}

// Get a specified number of random bytes in hex-form.
function get_random_bytes_hex($length) {
	if(phpversion() >= 7) {
		return bin2hex(random_bytes($length));
	} else {
		return bin2hex(openssl_random_pseudo_bytes($length));
	}
}

// Create a human friendly random number code.
function generate_token_code() {
	$submission_code = '';
	for($i=0; $i<18; $i++) {
		$submission_code .= str_pad(strval(hexdec(get_random_bytes_hex(2)) % 100), 2, '0', STR_PAD_LEFT);
	}
	return $submission_code;
}

// Retrieve session information from database.
function get_session_information($db) {
	// Validate cookie input.
	if(isset($_COOKIE['healthcare_worker_uuid']) 
		&& isset($_COOKIE['session_token'])
		&& is_uuid($_COOKIE['healthcare_worker_uuid'])
		&& is_session_token($_COOKIE['session_token'])) {

		// Check if session is a valid session in the database.
		$result = $db->has_active_healthcare_worker_session($_COOKIE['healthcare_worker_uuid'], $_COOKIE['session_token']);
		
		return $result;
	}
}

// Condenses the output of get_session_information() to a simple boolean.
function has_valid_session($result) {	
	return isset($result) && isset($result['query_succeeded'])
		&& $result['query_succeeded'] && isset($result['session_valid'])
		&& $result['session_valid'];
}

?>
