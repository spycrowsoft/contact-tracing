<?php

require_once('util.php');

// Check if the user has a valid session.
$result = get_session_information($db);
if(!has_valid_session($result)) {
	// Valid session found.
	// Redirect to portal.
	header("Location: login.php", 303);
	exit();
}

// Retrieve data from view_daily_tracing_key_submitted_by_healthcare_worker



?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" type="text/css" href="style.css">
<title>Health-worker Home</title>
</head>
<body>

<div class="healthworker-home-title">
<h1>Health-worker Home</h1>
</div>

<div class="form" id="login-form">
<form method="post" action="addnewpatient.php">

<div class="form-submit">
    <input type="submit" value="Add new positive COVID-19 patient">
</div>
</form>
</div>

<div class="Active-table">
 <table>
  <tr>
    <th>Retract?</th>
    <th>Request time</th>
    <th>Start date</th>
    <th>Expiration date</th>
    <th>Day number</th>
  </tr>
  <tr>
    <td>...</td>
    <td>YESTERDAY</td>
    <td>NOW</td>
    <td>number1</td>
  </tr>
  <tr>
    <td>...</td>
    <td>YESTERDAY</td>
    <td>NOW</td>
    <td>number2</td>
  </tr>
  <tr>
    <td>...</td>
    <td>YESTERDAY</td>
    <td>NOW</td>
    <td>number3</td>
  </tr>
</table> 
</div>

</body>
</html>
