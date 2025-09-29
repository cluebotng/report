<?PHP

ini_set('display_errors', 'Off');
error_reporting(E_ALL);
session_start();

require_once 'vendor/autoload.php';
require_once 'includes/Page.php';
require_once 'web-settings.php';
require_once 'includes/dbFunctions.php';

foreach (glob('pages/*.page.php') as $page) {
    require_once $page;
}

$mysql = mysqli_connect($cb_mysql_host, $cb_mysql_user, $cb_mysql_pass);
if (!$mysql) {
    die('Error.  Could not connect to database.');
}

if (!mysqli_select_db($mysql, $cb_mysql_schema)) {
    die('Error.  Database has insufficient permissions.');
}

date_default_timezone_set('America/New_York');

ini_set('user_agent', 'ClueBot/2.0 (ClueBot NG Report Interface)');
