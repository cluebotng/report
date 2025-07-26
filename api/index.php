<?php

namespace ReportApi;

/*
 * Setup base env stuff
 */
ini_set('user_agent', 'ClueBot/2.0 (ClueBot NG API)');
date_default_timezone_set('America/New_York');

/*
 * Include our functions and settings
 */
require_once('../vendor/autoload.php');
require_once('../web-settings.php');
require_once('../includes/dbFunctions.php');
require_once('includes/Module.php');

foreach (glob('modules/*.module.php') as $module) {
    require_once($module);
}

/*
 * Try and connect to mysql - we need this for pretty much everything
 */
$mysql = @mysqli_connect($cb_mysql_host, $cb_mysql_user, $cb_mysql_pass, $cb_mysql_schema, $cb_mysql_port);
if (!$mysql) {
    header('Content-Type: application/json');
    die(json_encode(array(
        'error' => 'db_error',
        'error_message' => 'Could not connect to database server',
    ), JSON_PRETTY_PRINT));
}

if (array_key_exists('action', $_REQUEST)) {
    if ($module = ApiModule::find($_REQUEST['action'])) {
        $module->header();
        echo($module->content());
        $module->footer();
    }
}

@mysqli_close($mysql);
