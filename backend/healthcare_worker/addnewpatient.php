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

// Checks if date has the correct format.
function is_date($candidate) {
	return preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $candidate);
}

// Prints the submission_code_table.
function print_submission_code_table($submission_code) {
	$result = '<table id="number-code-table">' . "\r\n";
	$result .= "<tr><th>Code</th><th>Left</th><th>Right</th></tr>\r\n";
	
	for($i=0; $i<6; $i++) {
		$result .= "<tr><td>" . strval($i+1) . "</td><td>" . substr($submission_code, 6*$i, 3) . "</td><td>" . substr($submission_code, 6*$i+3, 3) . "</td></tr>\r\n";
	}
	
	$result .= "</table>\r\n";
	return $result;
}

// Generate submission code.
$submission_code = generate_token_code();

if(isset($_POST['start_date']) && is_date($_POST['start_date'])) {
	$start_date = $_POST['start_date'];
} else {
	$start_date = NULL;
}

if(isset($_POST['end_date']) && is_date($_POST['end_date'])) {
	$end_date = $_POST['end_date'];
} else {
	$end_date = NULL;
}

$result = $db->add_new_submission_request($session_result['healthcare_worker_uuid'], $submission_code, $start_date, $end_date);

if(!isset($result) || !isset($result['submission_code_added'])
	|| !$result['submission_code_added']) {
	// Something went wrong.
	// Probably the generated code was a duplicate.
	// Redirect to portal.
	header("Location: portal.php", 303);
	exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" type="text/css" href="style.css">
<title>Show activation code</title>

<script type="text/javascript" src="qrcodejs/jquery.min.js"></script>
<script type="text/javascript" src="qrcodejs/qrcode.min.js"></script>

</head>
<body>

<div class="body" id="div-body">

<header>
	<div class="logo">Rijksoverheid-logo</div>
	<div class="title">Show activation code</div>
</header>

<div class="form-logout">
<form method="post" action="logout.php">
<div class="form-submit">
    <input type="submit" value="Logout">
</div>
</form>
</div>

You can scan the QR-code or enter the number code.

<div class="addnewpatient-qr-code">
<div class="addnewpatient-qr-code-title">
<h2>QR Code</h2>
</div>

<div id="qrcode" style="width:430px; height:430px; margin:15px;"></div>
<script type="text/javascript">
new QRCode("qrcode", {
    text: "<?php echo $submission_code ?>",
    width: 400,
    height: 400,
    colorDark : "#000000",
    colorLight : "#ffffff",
    correctLevel : QRCode.CorrectLevel.H
});
</script>
</div>

<div class="addnewpatient-number-code">

<div class="addnewpatient-number-code-title">
<h2>Number Code</h2>
</div>

<div class="addnewpatient-number-code-block">
<?php echo print_submission_code_table($submission_code); ?>
</div>

<div class="addnewpatient-number-code-numbers">
<?php echo $submission_code; ?>
</div>

</div>

<div class="form" id="return-to-portal">
<form method="post" action="portal.php">
	
<div class="form-submit">
    <input type="submit" value="Return to portal">
</div>

</form>
</div>

<div class="addnewpatient-sms-code">
<div class="addnewpatient-sms-code-title">
<h2>SMS Text Message Code</h2>
</div>

<form method="post">
<div class="phonenumber">
  <label for="phonenumber">Mobile phone number:</label><br>
  <input type="tel" id="phonenumber" name="phonenumber" placeholder="06-12345678" pattern="[0][6]-[0-9]{8}" title="Use a Dutch phone number"><br/>
</div>
<br/>
<div class="send-sms">
    <input type="submit" value="Send SMS code">
    (Not implemented yet)
</div>
</form>
</div>

</div>
</body>
</html>
