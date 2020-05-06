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
	
	// Get the required data for submission of a key.
	function store_keys_if_request_is_valid($submission_code, $keys) {
		$this->connect_if_not_connected_yet();
		
		$return_values['submission_succeeded'] = false;
		
		if(!($stmt = $this->mysqli->prepare("SELECT request_uuid FROM view_key_submission_allowed WHERE submission_code = ?"))) {
			// Prepare failed.
			$return_values['prepare_failed'] = true;
			exit("Prepare failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
			return $return_values;
		}
		if(!$stmt->bind_param("s", $submission_code)) {
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
			$return_values['submission_valid'] = true;
		}
		
		$row = $result->fetch_assoc();
		$request_uuid = $row['request_uuid'];
		
		$stmt->close();
		
		if(!($stmt = $this->mysqli->prepare("INSERT IGNORE INTO active_daily_tracing_keys (request_uuid, interval_number, daily_tracing_key) VALUES (?,?,?);"))) {
			// Prepare failed.
			$return_values['prepare_failed'] = true;
			exit("Prepare failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error);
			return $return_values;
		}
			
		if(!$stmt->bind_param("sib", $request_uuid, $day_number, $tracing_key)) {
			// Binding parameters failed.
			$return_values['bind_failed'] = true;
			return $return_values;
		}
				
		foreach($keys as $index => $value) {
			$day_number = $value['day_number'];
			$tracing_key = $value['tracing_key'];

			if(!$stmt->execute()) {
				// Execute failed.
				$return_values['execute_failed'] = true;
				return $return_values;
			}
		}
		
		$stmt->close();
		
		$return_values['submission_succeeded'] = true;
		
		return $return_values;
	}
}

$db = new DatabaseConnection($db_server, $db_user, $db_password, $db_database, $db_port);

?>
