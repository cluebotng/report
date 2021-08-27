<?php

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

/*
 * Try and connect to mysql - we need this for pretty much everything
 */
$mysql = @mysqli_connect($dbHost, $dbUser, $dbPass);
if (!$mysql) {
    header('Content-Type: text/json');
    die(json_encode(array(
        'error' => 'db_error',
        'error_message' => 'Could not connect to database server',
    ), JSON_PRETTY_PRINT));
}

/*
 * Try and select the db
 */
if (!@mysqli_select_db($mysql, $dbSchema)) {
    header('Content-Type: text/json');
    die(json_encode(array(
        'error' => 'db_error',
        'error_message' => 'Could not access database scheme',
    ), JSON_PRETTY_PRINT));
}

/*
 * Set the action to help - this is the default
 */
$action = 'help';

/*
 * If an action was requested then overwrite the default $action var
 */
if (isset($_REQUEST['action']) && !empty($_REQUEST['action'])) {
    $action = (string)strtolower($_REQUEST['action']);
}

/*
 * Convert dots to _ and lower the action string
 */
$action = strtolower(str_replace('.', '_', $action));

/*
 * Build the full path to the action
 */
$action_module = realpath('modules/' . $action . '.module.php');

/*
 * Build the base dirs to compare later
 */
$base_dir = realpath('modules/');
$action_dir = dirname(realpath($action_module));

/*
 * Check the the action_module is valid (missing files return false from realpath)
 */
if ($action_module === false || !is_file($action_module) || $action_dir !== $base_dir) {
    die(json_encode(array(
        'error' => 'unknown_action',
        'error_message' => 'The requested action is unknown',
    )));
} else {
    require_once($action_module);
}
