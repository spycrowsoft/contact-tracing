<?php 

require_once('util.php');

// Regex and function to validate username format.
define('username_regex', '^[\.0-9a-zA-Z]{6,32}$');

function is_valid_username($candidate) {
	return preg_match('/' . username_regex . '/', $candidate);
}

// Regex and function to validate password format.
define('password_regex', '^[0-9a-zA-Z\ \.\?]{8,128}$');

function is_valid_password($candidate) {
	return preg_match('/' . password_regex . '/', $candidate);
}

// Regex and function to validate TOTP format.
define('totp_regex', '^([0-9]{6}|[0-9]{8})$');

function is_valid_totp($candidate) {
	return preg_match('/' . totp_regex . '/', $candidate);
}

// Check if the user has a valid session.
if(has_valid_session(get_session_information($db))) {
	// Valid session found.
	// Redirect to portal.
	header("Location: portal.php", 303);
	exit();
}

// Validate login data.
if(isset($_POST['username']) 
	&& isset($_POST['password'])
	&& isset($_POST['totp'])
	&& is_valid_username($_POST['username'])
	&& is_valid_password($_POST['password'])
	&& is_valid_totp($_POST['totp'])) { // Validate data from input fields.
	
	$username = strtolower($_POST['username']);
	
	$result = $db->get_login_data($username);
		
	if($result['query_succeeded'] && $result['login_in_database']) { 
		// Test if healthcare_worker's creditials are valid
		
		$hashed_password = hash('sha256', hash('sha256', $_POST['password']) . $result['salt']);
				
		if($hashed_password === $result['hashed_password']) {
			// Terminate all other sessions.
		
			$db->terminate_all_sessions_for_healtcare_worker($result['healthcare_worker_uuid']);
			
			// Create new session.
			$session_token = get_random_bytes_hex(18);
			$db->create_new_session($result['healthcare_worker_uuid'], $session_token);
			
			// Set cookies.
			setcookie('healthcare_worker_uuid', $result['healthcare_worker_uuid']);
			setcookie('session_token', $session_token);
			
			// Redirect user to portal.
			header("Location: portal.php", 303);
			exit();
		}
	}
}

// Our session was invalid, display the login-page.

?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" type="text/css" href="style.css">
<title>Login Contact Tracing Health-worker Portal</title>
</head>
<body>

<div class="login-page-title">
<h1>Login Contact Tracing Health-worker Portal</h1>
</div>

<div class="form" id="login-form">
<form method="post" action="login.php">

<div class="form-username">
  <label for="username">Username:</label><br>
  <input type="text" id="username" name="username" pattern="<?php echo username_regex; ?>" required><br/>
</div>

<div class="form-password">  
  <label for="password">Password:</label><br>
  <input type="password" id="password" name="password" pattern="<?php echo password_regex; ?>" required><br/>
</div>
  
<div class="form-totp">
  <label for="totp">TOTP:</label><br>
  <input type="text" id="totp" name="totp" maxlength="6" pattern="<?php echo totp_regex; ?>" title="Six numbers" required><br/>
  (not implemented yet)
</div>  
<br/>

<div class="form-submit">
    <input type="submit" value="Submit">
</div>
  
</form>
</div>
<p>

<div class="form-resetaccess">
<form method="post" action="resetaccess.html">
<div class="form-reset-submit">
    <input type="submit" value="I forgot my password or Authenticator code."><br/>
     (not implemented yet, accepts any 6 digit code)
</div>

</form>
</div>

</body>
</html>
