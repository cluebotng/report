<?PHP

namespace ReportInterface;

use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\ClientConfig;
use MediaWiki\OAuthClient\Consumer;
use MediaWiki\OAuthClient\Token;

class SignInPage extends Page
{
    private function lookupUser($username)
    {
        global $mysql;
        $query = "SELECT `userid`, `username`, `admin`, `superadmin`, `next_on_review`, `hide_anon`
              FROM `users`
              WHERE `username` = ?";
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
        } else {
            return false;
        }
    }

    public function __construct()
    {
        global $oauth_consumer_key, $oauth_consumer_secret;
        $conf = new ClientConfig('https://en.wikipedia.org/w/index.php?title=Special:OAuth');
        $conf->setConsumer(new Consumer($oauth_consumer_key, $oauth_consumer_secret));
        $client = new Client($conf);

        if (isset($_GET['oauth_verifier'])) {
            // Callback URL - verify
            $requestToken = new Token($_SESSION['request_key'], $_SESSION['request_secret']);
            $accessToken = $client->complete($requestToken, $_GET['oauth_verifier']);
            $identity = $client->identify($accessToken);

            // We are done with these
            unset($_SESSION['request_key']);
            unset($_SESSION['request_secret']);

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

            // If we managed to do the dance above, then we are logged in
            if ($user) {
                $_SESSION['userid'] = $user['userid'];
                $_SESSION['next_on_review'] = $user['next_on_review'] ? true : false;
                $_SESSION['username'] = $user['username'];
                $_SESSION['admin'] = $user['admin'] ? true : false;
                $_SESSION['sadmin'] = $user['superadmin'] ? true : false;
                $_SESSION['hide_anon'] = $user['hide_anon'] ? true : false;

                header('Location: ?page=List');
                die();
            }

            // Else go through the process again
            header('Location: ?page=Sign+In');
            die();
        } else {
            // SignIn URl - redirect
            list($authUrl, $token) = $client->initiate();
            $_SESSION['request_key'] = $token->key;
            $_SESSION['request_secret'] = $token->secret;

            header('Location: ' . $authUrl);
            die();
        }
    }

    public function writeHeader()
    {
        echo 'Sign In';
    }
}

if (!isset($_SESSION['username'])) {
    Page::registerPage('Sign In', 'SignInPage', 3);
}
