<?PHP
    class CreateAccountPage extends Page
    {
        public function __construct() {
            $this->bad_captca = false;
            if (isset($_POST[ 'submit' ])) {
                if (!recaptca_is_valid()) {
                    $this->bad_captca = true;
                } else {
                    $query = 'INSERT INTO `users` (`username`,`password`,`email`,`admin`) VALUES (';
                    $query.= '\'' . mysql_real_escape_string($_POST[ 'username' ]) . '\',';
                    $query.= 'PASSWORD(\'' . mysql_real_escape_string($_POST[ 'password' ]) . '\'),';
                    $query.= '\'' . mysql_real_escape_string($_POST[ 'email' ]) . '\',';
                    $query.= '0)';
        
                    mysql_query($query);
                
                    rc('[[report:Special:NewUser]] new //' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'PHP_SELF' ] . '?page=User+Admin * ' . $_POST[ 'username' ] . ' * New User');
                
                    header('Location: ?page=Sign+In');
                    die();
                }
            }
        }
        
        public function writeHeader()
        {
            echo 'Create Account';
        }
        
        public function writeContent()
        {
            echo '<form method="post">';
            echo '<table>';
            if ($this->bad_captca === true) {
                echo '<tr><th>BAD CAPTCHA!</th><td>GO AWAY!</td></tr>';
            }
            echo '<tr><th>Username:</th><td><input type="text" name="username" /></td></tr>';
            echo '<tr><th>Password:</th><td><input type="password" name="password" /></td></tr>';
            echo '<tr><th>E-mail:</th><td><input type="text" name="email" /></td></tr>';
            echo '<tr><th>Captcha:</th><td><div class="g-recaptcha" data-sitekey="6LdYiFQUAAAAAATBndPVw4OeBIHrW-1zKUOodoYZ"></div></td></tr>';
            echo '<tr><td colspan=2><input type="submit" name="submit" value="Register" /></td></tr>';
            echo '</table>';
            echo '</form>';
        }
    }
    if (!isset($_SESSION[ 'username' ])) {
        Page::registerPage('Create Account', 'CreateAccountPage', 3);
    }
