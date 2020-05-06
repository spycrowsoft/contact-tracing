<?php

require_once('util.php');

// Check if the user has a valid session.
if(has_valid_session(get_session_information($db))) {
	// Valid session found.
	// Redirect to portal.
	header("Location: portal.php", 303);
	exit();
}

if(isset($_POST['username']) 
	&& isset($_POST['email'])
	&& is_valid_username($_POST['username'])
	&& is_valid_email($_POST['email'])) { // Validate data from input fields.
	
	$username = $_POST['username'];
	$email = $_POST['email'];
	$reset_code = generate_token_code();
	
	$reset_result = $db->set_reset_code($username, $email, $reset_code);
	if(isset($reset_result) && isset(reset_result['account_reset_requested']) && $reset_result['account_reset_requested']) {
		// Send an e-mail.
		
		$headers = array(
			'From' => $from_email,
			'Reply-To:' => $from_email
		);
		
		$subject = "Your reset codes.";
		$message = "Your reset codes are: \r\n\r\n";
		
		for($i=0; $i<6; $i++) {
			$result .= "Code " . strval($i+1) . ": " . substr($reset_code, 6*$i, 6) . "\r\n";
		}
		
		$message .= "\r\nPlease enter it into the reset page.";
		
		$message = wordwrap($message, 70, "\r\n");
		
		mail($email, $subject, $message, $headers);
	}
	
	header("Location: reset.php", 303);
	exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" type="text/css" href="style.css"/>
<title>Request password reset</title>
</head>
<body>

<header>
	<div class="logo">Rijksoverheid-logo</div>
	<div class="title">Request password reset</div>
</header>

<div class="form-reset-password">
<form method="post" action="requestreset.php">
	<div class="form-username">  
		<label for="username">Username:</label><br>
		<input type="text" id="username" name="username" required pattern="<?php echo username_regex; ?>" title="Enter your username."><br/>
	</div>
	<div class="form-email">  
		<label for="email">Email:</label><br>
		<input type="email" id="email" name="email" required pattern="<?php echo email_regex; ?>" title="Enter a valid email address."><br/>
	</div>
	<br/>
	<div class="form-submit">
		<input type="submit" value="Submit">
	</div>
</form>
</div>

</body>
</html>
