<?PHP

namespace ReportInterface;

use Exception;
use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\ClientConfig;
use MediaWiki\OAuthClient\Consumer;
use MediaWiki\OAuthClient\Token;

class SignInPage extends Page
{
    private function lookupUser($username)
    {
        global $mysql;
        $query = "SELECT `userid`, `username`, `admin`, `superadmin`, `next_on_review`, 
                  `hide_anon`, `keyboard_shortcuts`
                  FROM `users` WHERE `username` = ?";
        if ($stmt = mysqli_prepare($mysql, $query)) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $user;
        } else {
            return false;
        }
    }

    private function createUser($username)
    {
        global $mysql;
        $query = "INSERT INTO `users` (`username`, `admin`) VALUES (?, 0)";
        if ($stmt = mysqli_prepare($mysql, $query)) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return true;
        } else {
            return false;
        }
    }

    private function makeUserAdmin($username)
    {
        global $mysql;
        $query = "UPDATE `users` SET `admin` = 1 WHERE `username` = ?";
        if ($stmt = mysqli_prepare($mysql, $query)) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    public function __construct()
    {
        global $oauth_consumer_key, $oauth_consumer_secret;
        $conf = new ClientConfig('https://en.wikipedia.org/w/index.php?title=Special:OAuth');
        $conf->setConsumer(new Consumer($oauth_consumer_key, $oauth_consumer_secret));
        $client = new Client($conf);

        if (isset($_GET['oauth_verifier'])) {
            try {
                // Callback URL - verify
                if (!isset($_SESSION['request_key']) || !isset($_SESSION['request_secret'])) {
                    throw new Exception('OAuth request token not found in session. Please try signing in again.');
                }
                
                $requestToken = new Token($_SESSION['request_key'], $_SESSION['request_secret']);
                $accessToken = $client->complete($requestToken, $_GET['oauth_verifier']);
                $identity = $client->identify($accessToken);

                // We are done with these
                unset($_SESSION['request_key']);
                unset($_SESSION['request_secret']);
            } catch (Exception $e) {
                error_log('OAuth Error: ' . $e->getMessage());
                unset($_SESSION['request_key']);
                unset($_SESSION['request_secret']);
                echo '<div class="error">Error during sign in: ' . htmlspecialchars($e->getMessage()) . '</div>';
                return;
            }

            if (!$identity) {
                header('Location: ?page=Sign+In');
                die();
            }

            if ($identity->blocked) {
                print('Access blocked');
                die();
            }

            // This is a bit odd, but basically we lazy create users
            $user = $this->lookupUser($identity->username);
            if (!$user) {
                $this->createUser($identity->username);
                $user = $this->lookupUser($identity->username);
            }

            // Auto grant access if the user has wiki rights
            if (userHasWikiRights($identity->username)) {
                $this->makeUserAdmin($identity->username);
                $user = $this->lookupUser($identity->username);
            }

            // If we managed to do the dance above, then we are logged in
            if ($user) {
                $_SESSION['userid'] = $user['userid'];
                $_SESSION['next_on_review'] = (bool)$user['next_on_review'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['admin'] = (bool)$user['admin'];
                $_SESSION['sadmin'] = (bool)$user['superadmin'];
                $_SESSION['hide_anon'] = (bool)$user['hide_anon'];
                $_SESSION['keyboard_shortcuts'] = (bool)$user['keyboard_shortcuts'];

                header('Location: ?page=List');
                die();
            }

            // Else go through the process again
            header('Location: ?page=Sign+In');
        } else {
            try {
                list($authUrl, $token) = $client->initiate();
                $_SESSION['request_key'] = $token->key;
                $_SESSION['request_secret'] = $token->secret;
                header('Location: ' . $authUrl);
            } catch (\MediaWiki\OAuthClient\Exception $e) {
                error_log('OAuth Initiation Error: ' . $e->getMessage());
                header('Location: ?page=Sign+In');
            }
        }
        die();
    }

    public function writeHeader()
    {
        echo 'Sign In';
    }
}

if (!isset($_SESSION['username'])) {
    Page::registerPage('Sign In', 'SignInPage', 3);
}
