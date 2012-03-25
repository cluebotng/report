<?PHP
	class CreateAccountPage extends Page {
		public function __construct() {
		     	global $recaptcha_privkey;
			$this->bad_captca = False;
			if( isset( $_POST[ 'submit' ] ) ) {
				$resp = recaptcha_check_answer ($recaptcha_privkey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
				if (!$resp->is_valid) {
					$this->bad_captca = True;
				} else {
					$query = 'INSERT INTO `users` (`username`,`password`,`email`,`admin`) VALUES (';
					$query.= '\'' . mysql_real_escape_string( $_POST[ 'username' ] ) . '\',';
					$query.= 'PASSWORD(\'' . mysql_real_escape_string( $_POST[ 'password' ] ) . '\'),';
					$query.= '\'' . mysql_real_escape_string( $_POST[ 'email' ] ) . '\',';
					$query.= '0)';
		
					mysql_query( $query );
				
					rc( '[[report:Special:NewUser]] new http://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'PHP_SELF' ] . '?page=User+Admin * ' . $_POST[ 'username' ] . ' * New User' );
				
					header( 'Location: ?page=Sign+In' );
					die();
				}
			}
		}
		
		public function writeHeader() {
			echo 'Create Account';
		}
		
		public function writeContent() {
			global $recaptcha_pubkey;
			echo '<form method="post">';
			echo '<table>';
			if($this->bad_captca === True) {
			echo '<tr><th>BAD CAPTCHA!</th><td>GO AWAY!</td></tr>';
			}
			echo '<tr><th>Username:</th><td><input type="text" name="username" /></td></tr>';
			echo '<tr><th>Password:</th><td><input type="password" name="password" /></td></tr>';
			echo '<tr><th>E-mail:</th><td><input type="text" name="email" /></td></tr>';
                     echo recaptcha_get_html($recaptcha_pubkey);
			echo '<tr><td colspan=2><input type="submit" name="submit" value="Register" /></td></tr>';
			echo '</table>';
			echo '</form>';
		}
	}
	if( !isset( $_SESSION[ 'username' ] ) )
		Page::registerPage( 'Create Account', 'CreateAccountPage', 3 );
?>
