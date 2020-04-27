<?php

require_once('util.php');

// Check if the user has a valid session.
if(!has_valid_session($db)) {
	// Valid session found.
	// Redirect to portal.
	header("Location: login.php", 303);
	exit();
}



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
<form method="post" action="addnewpatient.html">

<div class="form-submit">
    <input type="submit" value="Add new positive COVID-19 patient">
</div>
</form>
</div>

<div class="retraction-table">
 <table>
  <tr>
    <th>Retract?</th>
    <th>Timestamp start</th>
    <th>Timestamp end</th>
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
