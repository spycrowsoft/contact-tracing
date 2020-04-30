<?php

require_once('util.php');

// Check if the user has a valid session.
$session_result = get_session_information($db);
if(!has_valid_session($session_result)) {
	// No valid session found.
	// Redirect to portal.
	header("Location: login.php", 303);
	exit();
}

// Retrieve data from view_daily_tracing_key_submitted_by_healthcare_worker

$result = $db->get_active_daily_tracing_key_submitted_by_healthcare_worker($session_result['healthcare_worker_uuid']);

if(isset($result) && isset($result['result_obtained'])
	&& $result['result_obtained'] 
	&& isset($result['active_keys_table'])) {
	
	$active_keys = $result['active_keys_table'];
} else {
	$active_keys = array();
}

function print_active_keys_table_rows($table) {
	$result = '';
	foreach ($table as &$row) {
		$result .= '<tr>';
		
		// For retraction.
		$result .= '<td><form action="retract.php" method="post"><input type="hidden" name="daily_tracing_key_uuid" value="' . htmlentities($row['daily_tracing_key_uuid']) . '" /><button>Retract</button></form></td>';
		
		// Remaining data.
		$result .= '<td>' . htmlentities($row['request_creation_time']) . '</td>';
		$result .= '<td>' . htmlentities($row['submission_time']) . '</td>';
		$result .= '<td>' . htmlentities($row['interval_number']) . '</td>';
		$result .= '</tr>' . "\r\n";
	}
	return $result;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" type="text/css" href="style.css">
<title>Health-worker Home</title>
</head>
<body>

<header>
	<div class="logo">Rijksoverheid-logo</div>
	<div class="title">Healthcare-worker Home</div>
</header>

<div class="form-logout">
<form method="post" action="logout.php">
<div class="form-submit">
    <input type="submit" value="Logout">
</div>
</form>
</div>

<div class="reset-page-title">
<h2>Add new patient</h2>
</div>

<div class="form-addnewpatient-dates">
<form method="post" action="addnewpatient.php">
<div class="form-dates">  
  <label for="startdate">Start of incubation period:</label><br>
  <input type="date" id="start_date" name="start_date" title="Start of incubation period."><br/>
  
  <label for="enddate">End of infectuous period:</label><br>
  <input type="date" id="end_date" name="end_date" title="End of infectuous period."><br/>
</div>
<br/>
<div class="form-submit">
    <input type="submit" value="Add new positive patient">
</div>
</form>
</div>

<h2>Active daily tracing keys</h2>

<div class="Active-table">
 <table>
  <caption>Active daily tracing keys</caption>
  <tr>
    <th>Retract?</th>
    <th>Request creation time</th>
    <th>Submission time</th>
    <th>Day number</th>
  </tr>
<?php 
echo print_active_keys_table_rows($active_keys);
?>
</table> 
</div>

</body>
</html>
