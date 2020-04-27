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
}

$db = new DatabaseConnection($db_server, $db_user, $db_password, $db_database, $db_port);

?>
