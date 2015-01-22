<?php
/*
 * Users list
 * - Returns users in the database
 */

$data = array(
);

$query = "SELECT * FROM `users`";

if (isset($_REQUEST['admin'])) {
    if ($_REQUEST['admin'] === '1' || $_REQUEST['admin'] === '0') {
        $query .= " WHERE `admin` = '" . mysql_real_escape_string($_REQUEST['admin']) . "'";
    } else {
        $data = array(
      "error" => "argument_error",
      "error_message" => "Specified admin value was invalid",
    );
        die(output_encoding($data));
    }
} elseif (isset($_REQUEST['superadmin'])) {
    if ($_REQUEST['superadmin'] === '1' || $_REQUEST['superadmin'] === '0') {
        $query .= " WHERE `superadmin` = '" . mysql_real_escape_string($_REQUEST['superadmin']) . "'";
    } else {
        $data = array(
      "error" => "argument_error",
      "error_message" => "Specified superadmin value was invalid",
    );
        die(output_encoding($data));
    }
}

$result = mysql_query($query);
if (mysql_num_rows($result) > 0) {
    while ($row = mysql_fetch_assoc($result)) {
        $data['userid-' . $row['userid']] = array(
         "userid" => $row['userid'],
         "username" => $row['username'],
         "admin" => $row['admin'],
         "superadmin" => $row['superadmin'],
         "password" => '',
         "email" => '',
      );
    }
}

die(output_encoding($data));
