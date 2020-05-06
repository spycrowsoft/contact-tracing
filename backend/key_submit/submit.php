<?php

require_once('config.php');

define('submission_code_regex', '^[0-9]{36}$');
function is_submission_code($candidate) {
	return preg_match('/' . submission_code_regex . '/', $candidate);
}

define('dummy_token_code_regex', '^[0]{36}$');
function is_dummy_code($candidate) {
	return preg_match('/' . dummy_token_code_regex . '/', $candidate);
}

// Get a specified number of random bytes in hex-form.
function get_random_bytes_hex($length) {
	if(phpversion() >= 7) {
		return bin2hex(random_bytes($length));
	} else {
		return bin2hex(openssl_random_pseudo_bytes($length));
	}
}

define('keys_input_regex', '^([0-9a-fA-F]{40}){1,30}$');

function parse_submitted_keys($hex_input_string) {
	$result = array();
	
	$keys = strlen($hex_input_string)/40; // 40 characters in hex per 20 byte-key.
	
	for($i=0; $i<$keys; $i++) {
		$result[$i]['day_number'] = hexdec(substr($hex_input_string, $i*40, 8));
		$result[$i]['tracing_key'] = hex2bin(substr($hex_input_string, $i*40+8, 36));
	}
	return $result;
}

if(isset($_POST['submission_code']) && is_submission_code($_POST['submission_code'])) {
	if(is_dummy_code($_POST['submission_code'])) {
		// Sleep a random amount of time.
		usleep(((hexdec(get_random_bytes_hex(2)) % 500)) * 1000);
		// Send 200 OK.
		http_response_code(200);
		exit();
	} elseif(preg_match('/' . keys_input_regex . '/', $_POST['keys_input_hex'])) {
		// submission_code has the right format and looks legit, so check input.
		$keys = parse_submitted_keys($_POST['keys_input_hex']);
		
		$result = $db->store_keys_if_request_is_valid($_POST['submission_code'], $keys);
		
		if(isset($result) && isset($result['submission_succeeded']) && $result['submission_succeeded']) {
			// Submission succesfull.
			http_response_code(200);
			exit();
		} else {
			// Submission failed.
			http_response_code(400);
			exit();
		}
		
	} else {
		// Send 400 bad request.
		http_response_code(400);
		exit();
	}
}

?>

