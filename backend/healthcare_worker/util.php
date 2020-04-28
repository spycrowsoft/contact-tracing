<?php

require_once('config.php');

// Check if uuid has the correct format.
function is_uuid($candidate) {
	return preg_match("/^[0-9a-zA-Z]{8}-[0-9a-zA-Z]{4}-[0-9a-zA-Z]{4}-[0-9a-zA-Z]{4}-[0-9a-zA-Z]{12}$/", $candidate);
}

// Check if session-token has the correct format.
function is_session_token($candidate) {
	return preg_match("/^[0-9a-fA-F]{36}$/", $candidate);
}

// Get a specified number of random bytes in hex-form.
function get_random_bytes_hex($length) {
	if(phpversion() >= 7) {
		return bin2hex(random_bytes($length));
	} else {
		return bin2hex(openssl_random_pseudo_bytes($length));
	}
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
