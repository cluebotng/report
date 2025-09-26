<?php

if (php_sapi_name() !== 'cli') {
    die();
}

require_once(dirname(__FILE__) . '/../vendor/autoload.php');
require_once(dirname(__FILE__) . '/../web-settings.php');
require_once(dirname(__FILE__) . '/../includes/dbFunctions.php');

$context  = stream_context_create(array('http' => array('user_agent' => 'ClueBot NG Report Interface')));
$raw = @file_get_contents('https://huggle.bena.rocks/?action=read&wp=en.wikipedia.org', false, $context);
if ($raw == null) {
    die('Failed to download whitelist');
}
$whitelist = array_slice(explode('|', $raw), 0, -1);

$mysql = mysqli_connect($cb_mysql_host, $cb_mysql_user, $cb_mysql_pass, $cb_mysql_schema, $cb_mysql_port);
if (!$mysql) {
    die('Failed to connect to MySQL: ' . mysqli_connect_error());
}

$result = mysqli_query($mysql, 'SELECT `revertid`, `user` FROM `reports` INNER JOIN `vandalism` on `vandalism`.`id` = `reports`.`revertid` WHERE `status` = 0');
while ($row = mysqli_fetch_assoc($result)) {
    if (in_array($row['user'], $whitelist) || userHasWikiRights($row['user'])) {
        echo 'Sending ' . $row['revertid'] . ' (' . $row['user'] . ') to review interface\n';
        updateStatus($row['revertid'], 2, 'Report Interface', -2);
    }
}

mysqli_close($mysql);
