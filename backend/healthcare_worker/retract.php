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

$status = 'Failed';

if(is_uuid($_POST['daily_tracing_key_uuid'])) {
	$daily_tracing_key_uuid = $_POST['daily_tracing_key_uuid'];
}

$result = $db->retract_single_key_if_allowed($session_result['healthcare_worker_uuid'], $daily_tracing_key_uuid);

if(isset($result) && isset($result['key_removed'])
	&& $result['key_removed']) {
	
	$status = 'Succes!';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" type="text/css" href="style.css">
<title>Retract a key</title>
</head>
<body>

<header>
	<div class="logo">Rijksoverheid-logo</div>
	<div class="title">Retract a key</div>
</header>

<div class="form-logout">
<form method="post" action="logout.php">
<div class="form-submit">
    <input type="submit" value="Logout">
</div>
</form>
</div>

<?php 
echo $status;
?>

<div class="form" id="return-to-portal">
<form method="post" action="portal.php">
	
<div class="form-submit">
    <input type="submit" value="Return to portal">
</div>

</form>
</div>

</body>
</html>
