<?php

require_once('config.php');

class DatabaseConnection {
	
	private $mysql_server;
	private $mysql_user;
	private $mysql_password;
	private $mysql_database;
	private $mysql_port;
	
	private $mysqli;
	
	function __construct($mysql_server, $mysql_user, $mysql_password, $mysql_database, $mysql_port) {
		$this->mysql_server = $mysql_server;
		$this->mysql_user = $mysql_user;
		$this->mysql_password = $mysql_password;
		$this->mysql_database = $mysql_database;
		$this->mysql_port = $mysql_port;
	}
	
	function __destruct() {
		if(isset($this->mysqli)) {
			$this->mysqli->commit();
			$this->mysqli->close();
			unset($this->mysqli);
		}
	}
	
	// Conntect to database when required.
	function connect_if_not_connected_yet() {
		if(!isset($this->mysqli)) {
			$this->mysqli = new mysqli(
				$this->mysql_server,
				$this->mysql_user,
				$this->mysql_password,
				$this->mysql_database,
				$this->mysql_port);
			
			if ($this->mysqli->connect_errno) {
				http_response_code(503);
				unset($this->mysqli);
				exit("Failed to connect to the MySQL-server (" . $this->mysqli->connect_errorno . ") " . $this->mysqli->connect_error);
			}
		}
	}
	
	// Check if an active session exists for a healthcare worker.
	function has_active_healthcare_worker_session($healthcare_worker_uuid, $session_token) {
		$this->connect_if_not_connected_yet();
		
		// Query to see if (uuid, session_id) combination exists.
		$return_values['query_succeeded'] = false;
		$return_values['session_valid'] = false;
		
		if(!($stmt = $this->mysqli->prepare("SELECT healthcare_worker_uuid, session_token FROM view_healthcare_workers_active_sessions WHERE healthcare_worker_uuid = ? AND session_token = ?"))) {
			// Prepare failed.
			$return_values['prepare_failed'] = true;
			return $return_values;
		}
		if(!$stmt->bind_param("ss", $healthcare_worker_uuid, $session_token)) {
			// Binding parameters failed.
			$return_values['bind_failed'] = true;
			return $return_values;
		}
		if(!$stmt->execute()) {
			// Execute failed.
			$return_values['execute_failed'] = true;
			return $return_values;
		}
		
		$return_values['query_succeeded'] = true;
		
		// Test if exactly one row was returned.
		$result = $stmt->get_result();
		if($result->num_rows != 1) {
			// Session invalid.
			return $return_values;
		} else {
			$return_values['session_valid'] = true;
		}
		
		$row = $result->fetch_assoc();
		
		$return_values['healthcare_worker_uuid'] = $row['healthcare_worker_uuid'];
		$return_values['session_token'] = $row['session_token'];
		
		return $return_values;
	}
	
	// Terminate all active sessions for a healthcare worker.
	function terminate_all_sessions_for_healtcare_worker($healthcare_worker_uuid) {
		$this->connect_if_not_connected_yet();
		
		// Query to see if (uuid, session_id) combination exists.
		$return_values['query_succeeded'] = false;
		
		if(!($stmt = $this->mysqli->prepare("UPDATE healthcare_worker_sessions SET active = FALSE WHERE healthcare_worker_uuid = ?"))) {
			// Prepare failed.
			$return_values['prepare_failed'] = true;
			return $return_values;
		}
		if(!$stmt->bind_param("s", $healthcare_worker_uuid)) {
			// Binding parameters failed.
			$return_values['bind_failed'] = true;
			return $return_values;
		}
		if(!$stmt->execute()) {
			// Execute failed.
			$return_values['execute_failed'] = true;
			return $return_values;
		}
		
		$return_values['query_succeeded'] = true;
		
		return $return_values;
	}
	
	// Create a new session for a healthcare worker.
	function create_new_session($healthcare_worker_uuid, $session_token) {
		$this->connect_if_not_connected_yet();
		
		// Query to see if (uuid, session_id) combination exists.
		$return_values['query_succeeded'] = false;
		
		if(!($stmt = $this->mysqli->prepare("INSERT INTO healthcare_worker_sessions(healthcare_worker_uuid, session_token, active) VALUES (?, ?, TRUE)"))) {
			// Prepare failed.
			$return_values['prepare_failed'] = true;
			return $return_values;
		}
		if(!$stmt->bind_param("ss", $healthcare_worker_uuid, $session_token)) {
			// Binding parameters failed.
			$return_values['bind_failed'] = true;
			return $return_values;
		}
		if(!$stmt->execute()) {
			// Execute failed.
			$return_values['execute_failed'] = true;
			exit("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
			return $return_values;
		}
		
		$return_values['query_succeeded'] = true;
		
		return $return_values;
	}
	
	// Get login data for a healthcare worker.
	function get_login_data($username) {
		$this->connect_if_not_connected_yet();
		
		$return_values['query_succeeded'] = false;
		$return_values['login_valid'] = false;
		
		if(!($stmt = $this->mysqli->prepare("SELECT healthcare_worker_uuid, hashed_password, salt, totp_seed FROM view_healthcare_workers_logins WHERE username = ?"))) {
			// Prepare failed.
			$return_values['prepare_failed'] = true;
			exit("Prepare failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
			return $return_values;
		}
		if(!$stmt->bind_param("s", $username)) {
			// Binding parameters failed.
			$return_values['bind_failed'] = true;
			return $return_values;
		}
		if(!$stmt->execute()) {
			// Execute failed.
			$return_values['execute_failed'] = true;
			return $return_values;
		}
		
		$return_values['query_succeeded'] = true;
		
		// Test if exactly one row was returned.
		$result = $stmt->get_result();
		if($result->num_rows != 1) {
			// Login invalid.
			return $return_values;
		} else {
			$return_values['login_in_database'] = true;
		}
		
		$row = $result->fetch_assoc();
		
		$return_values['healthcare_worker_uuid'] = $row['healthcare_worker_uuid'];
		$return_values['hashed_password'] = $row['hashed_password'];
		$return_values['salt'] = $row['salt'];
		$return_values['totp_seed'] = $row['totp_seed'];
		
		return $return_values;
	}
	
	function get_active_daily_tracing_key_submitted_by_healthcare_worker($healthcare_worker_uuid) {
		
		$this->connect_if_not_connected_yet();
		
		$return_values['result_obtained'] = false;
		
		if(!($stmt = $this->mysqli->prepare("SELECT daily_tracing_key_uuid, request_creation_time, submission_time, interval_number FROM view_daily_tracing_key_submitted_by_healthcare_worker WHERE healthcare_worker_uuid = ? ORDER BY request_creation_time DESC, interval_number DESC"))) {
			// Prepare failed.
			$return_values['prepare_failed'] = true;
			exit("Prepare failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
			return $return_values;
		}
		if(!$stmt->bind_param("s", $healthcare_worker_uuid)) {
			// Binding parameters failed.
			$return_values['bind_failed'] = true;
			return $return_values;
		}
		if(!$stmt->execute()) {
			// Execute failed.
			$return_values['execute_failed'] = true;
			return $return_values;
		}
		
		$return_values['result_obtained'] = true;
		
		
		$result_set = $stmt->get_result();
		$active_keys_table = $result_set->fetch_all(MYSQLI_ASSOC);
		
		$return_values['active_keys_table'] = $active_keys_table;
				
		return $return_values;
	}
	
	function retract_single_key_if_allowed($healthcare_worker_uuid, $daily_tracing_key_uuid) {
		
		$this->connect_if_not_connected_yet();
		
		$return_values['key_removed'] = false;
		
		if(!($stmt = $this->mysqli->prepare("SELECT daily_tracing_key_uuid FROM view_daily_tracing_key_submitted_by_healthcare_worker WHERE healthcare_worker_uuid = ? AND daily_tracing_key_uuid = ?"))) {
			// Prepare failed.
			$return_values['prepare_failed'] = true;
			exit("Prepare failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
			return $return_values;
		}
		if(!$stmt->bind_param("ss", $healthcare_worker_uuid, $daily_tracing_key_uuid)) {
			// Binding parameters failed.
			$return_values['bind_failed'] = true;
			return $return_values;
		}
		if(!$stmt->execute()) {
			// Execute failed.
			$return_values['execute_failed'] = true;
			return $return_values;
		}
				
		// Test if exactly one row was returned.
		$result = $stmt->get_result();
		if($result->num_rows != 1) {
			// Login invalid.
			return $return_values;
		}
		
		// Set retraction time.
		if(!($stmt = $this->mysqli->prepare("UPDATE active_daily_tracing_keys SET retraction_time = NOW() WHERE daily_tracing_key_uuid = ?"))) {
			// Prepare failed.
			$return_values['prepare_failed'] = true;
			return $return_values;
		}
		if(!$stmt->bind_param("s", $daily_tracing_key_uuid)) {
			// Binding parameters failed.
			$return_values['bind_failed'] = true;
			return $return_values;
		}
		if(!$stmt->execute()) {
			// Execute failed.
			$return_values['execute_failed'] = true;
			return $return_values;
		}
		
		$return_values['key_removed'] = true;
		
		return $return_values;
	}
	
	function add_new_submission_request($healthcare_worker_uuid, $submission_code, $start_date, $end_date) {
		$this->connect_if_not_connected_yet();
		
		$return_values['submission_code_added'] = false;
		
		// Build type, column and value strings and parameters array.
		
		$types = "ss";
		$columns = "healthcare_worker_uuid, submission_code";
		$values = "?, ?";
		
		$parameters = array($healthcare_worker_uuid, $submission_code);
		
		if(isset($start_date)) {
			$types .= 's';
			$columns .= ', start_date';
			$values .= ', ?';
			array_push($parameters, $start_date);
		}
		
		if(isset($end_date)) {
			$types .= 's';
			$columns .= ', end_date';
			$values .= ', ?';
			array_push($parameters, $end_date);
		}
		
		$query = "INSERT INTO daily_tracing_key_submission_requests (" . 
			$columns . ") VALUES ( " . $values . " )";
		
		if(!($stmt = $this->mysqli->prepare($query))) {
			// Prepare failed.
			$return_values['prepare_failed'] = true;
			exit("Prepare failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
			return $return_values;
		}
		if(!$stmt->bind_param($types, ...$parameters)) {
			// Binding parameters failed.
			$return_values['bind_failed'] = true;
			return $return_values;
		}
		if(!$stmt->execute()) {
			// Execute failed.
			$return_values['execute_failed'] = true;
			return $return_values;
		}
		
		$return_values['submission_code_added'] = true;
		
		return $return_values;
	}
	
	function set_reset_code($username, $email, $token_code) {
		$this->connect_if_not_connected_yet();
		
		// Query to see if (uuid, session_id) combination exists.
		$return_values['account_reset_requested'] = false;
		
		if(!($stmt = $this->mysqli->prepare("UPDATE healthcare_workers SET reset_code = ? WHERE username = ? AND email = ?"))) {
			// Prepare failed.
			$return_values['prepare_failed'] = true;
			return $return_values;
		}
		if(!$stmt->bind_param("sss", $token_code, $username, $email)) {
			// Binding parameters failed.
			$return_values['bind_failed'] = true;
			return $return_values;
		}
		if(!$stmt->execute()) {
			// Execute failed.
			$return_values['execute_failed'] = true;
			return $return_values;
		}
		
		$return_values['account_reset_requested'] = true;
		
		return $return_values;
	}
}

$db = new DatabaseConnection($db_server, $db_user, $db_password, $db_database, $db_port);

?>
