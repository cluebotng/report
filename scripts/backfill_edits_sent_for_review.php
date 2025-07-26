<?php

if (php_sapi_name() !== 'cli') {
    die();
}

require_once(dirname(__FILE__) . '/../vendor/autoload.php');
require_once(dirname(__FILE__) . '/../web-settings.php');
require_once(dirname(__FILE__) . '/../includes/dbFunctions.php');

$ignore_usernames = array("Review Interface");
$remap_usernames = array("Damian" => "DamianZaremba", "Rich" => "Rich Smith");

$mysql = mysqli_connect($cb_mysql_host, $cb_mysql_user, $cb_mysql_pass, $cb_mysql_schema, $cb_mysql_port);
if (!$mysql) {
    die('Failed to connect to MySQL: ' . mysqli_connect_error());
}

$valid_users = array();
$result = mysqli_query($mysql, 'SELECT `username`, `userid` FROM `users`');
while ($row = mysqli_fetch_assoc($result)) {
    $valid_users[$row['username']] = $row['userid'];
}

$result = mysqli_query(
    $mysql,
    'SELECT `revertid`, `comment` FROM `comments` WHERE `comment` LIKE "% has marked this report as \"Sending to Review Interface\"." AND `userid` = -2'
);

while ($row = mysqli_fetch_assoc($result)) {
    if (preg_match('/^(.+) has marked this report as "Sending to Review Interface"\.$/', $row['comment'], $matches)) {
        $username = $matches[1];
        if (in_array($username, $ignore_usernames)) {
            continue;
        }

        if (array_key_exists($username, $remap_usernames)) {
            $username = $remap_usernames[$username];
        }

        if (array_key_exists($username, $valid_users)) {
            recordUserSendingToReview($row['revertid'], $valid_users[$username]);
        }
    }
}

mysqli_close($mysql);
