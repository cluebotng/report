<?php
/*
 * Users get
 * - Returns a specific user in the database
 */

$data = array(
);

$query = "SELECT * FROM `users`";

if (isset($_REQUEST['uid'])) {
    $query .= " WHERE `userid` = '" . mysql_real_escape_string($_REQUEST['uid']) . "'";
} elseif (isset($_REQUEST['username'])) {
    $query .= " WHERE `username` = '" . mysql_real_escape_string($_REQUEST['username']) . "'";
} else {
    $data = array(
    "error" => "argument_error",
    "error_message" => "Neither uid or username was specified.",
  );
    die(output_encoding($data));
}

$result = mysql_query($query);
if (mysql_num_rows($result) === 1) {
    $row = mysql_fetch_assoc($result);
    $data = array(
      "userid" => $row['userid'],
      "username" => $row['username'],
      "admin" => $row['admin'],
      "superadmin" => $row['superadmin'],
      "password" => '',
      "email" => '',
   );
} else {
    $data = array(
    "error" => "argument_error",
    "error_message" => "Specified uid or username was not found.",
  );
    die(output_encoding($data));
}

die(output_encoding($data));
