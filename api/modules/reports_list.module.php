<?php
/*
 * Reports list
 * - Returns reports in the database
 */

$data = array();
$statuses = array(
	'Reported',
	'Invalid',
	'Sending to Review Interface',
	'Bug',
	'Resolved',
	'Queued to be reviewed',
	'Partially reviewed',
	'Reviewed - Included in dataset as Constructive',
	'Reviewed - Included in dataset as Vandalism',
	'Reviewed - Not included in dataset'
);

$query = "SELECT * FROM `reports`";
if(isset($_REQUEST['status']) && !empty($_REQUEST['status'])) {
	if(array_key_exists($_REQUEST['status'], $statuses)) {
		$query .= " WHERE `status` = '" . mysql_real_escape_string($_REQUEST['status']) . "'";
	} else {
		$data = array(
			"error" => "argument_error",
			"error_message" => "Specified status value was invalid",
		 );
		die(output_encoding($data));
	}
}

$result = mysql_query($query);
if(mysql_num_rows($result) > 0) {
	 while($row = mysql_fetch_assoc($result)) {
		$data['revertid-' . $row['revertid']] = array(
				"revertid" => $row['revertid'],
				"timestamp" => strtotime($row['timestamp']),
				"reporter" => $row['reporter'],
				"status" => $row['status'],
				"friendly_status" => $statuses[$row['status']],
				"comments" => array(),
		 );

		$cresult = mysql_query("SELECT * FROM `comments` WHERE `revertid` = '" . mysql_real_escape_string($row['revertid']) . "'");
		if(mysql_num_rows($cresult) > 0) {
				while($crow = mysql_fetch_assoc($cresult)) {
					$data['revertid-' . $row['revertid']]['comments']['commmentid-' . $crow['commentid']] = array(
							"commentid" => $crow['commentid'],
							"timestamp" => strtotime($crow['timestamp']),
							"user" => $crow['user'],
							"comment" => $crow['comment'],
					);
				}
		}
	}
}

die(output_encoding($data));
?>
