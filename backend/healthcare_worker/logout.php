<?php

require_once('util.php');

// Check if the user has a valid session.
$session_result = get_session_information($db);
if(has_valid_session($session_result)) {
	$db->terminate_all_sessions_for_healtcare_worker($session_result['healthcare_worker_uuid']);
}

header("Location: login.php", 303);
exit();

?>
