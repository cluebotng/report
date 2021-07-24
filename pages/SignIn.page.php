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
        $query = 'SELECT `userid`, `username`, `admin`, `superadmin`, `next_on_review`, `email` FROM `users` WHERE `username` = ';
        $query .= '\'' . mysqli_real_escape_string($mysql, $username) . '\'';
        return mysqli_fetch_assoc(mysqli_query($mysql, $query));
    }

    private function createUser($username)
    {
        global $mysql;
        $query = 'INSERT INTO `users` (`username`,`admin`) VALUES (';
        $query .= '\'' . mysqli_real_escape_string($mysql, $username) . '\',';
        $query .= '0)';
        mysqli_query($mysql, $query);
    }

    public function __construct()
    {
        global $oauthConsumerKey, $oauthConsumerSecret;
        $conf = new ClientConfig('https://en.wikipedia.org/w/index.php?title=Special:OAuth');
        $conf->setConsumer(new Consumer($oauthConsumerKey, $oauthConsumerSecret));
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
