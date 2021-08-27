<?php

/*
 * Reports list
 * - Returns reports in the database
 */
header('Content-Type: text/json');

$query = "SELECT * FROM `reports`";
if (isset($_REQUEST['status']) && !empty($_REQUEST['status'])) {
    if (array_key_exists($_REQUEST['status'], $statuses)) {
        $query .= " WHERE `status` = '" . mysqli_real_escape_string($mysql, $_REQUEST['status']) . "'";
    } else {
        die(json_encode(array(
            "error" => "argument_error",
            "error_message" => "Specified status value was invalid",
        ), JSON_PRETTY_PRINT));
    }
}

$data = array();
$report_results = mysqli_query($mysql, $query);
while ($report_row = mysqli_fetch_assoc($report_results)) {
    $data['report-' . $report_row['revertid']] = array(
        "revertid" => (int)$report_row['revertid'],
        "timestamp" => strtotime($report_row['timestamp']),
        "reporter" => $report_row['reporter'],
        "status" => $statuses[$report_row['status']],
        "status_id" => (int)$report_row['status'],
        "comments" => array(),
    );

    $comments_result = mysqli_query($mysql, "SELECT * FROM `comments` WHERE `revertid` = '" . mysqli_real_escape_string($mysql, $report_row['id']) . "'");
    while ($comment_row = mysqli_fetch_assoc($comments_result)) {
        array_push($data['report-' . $report_row['revertid']]['comments'], array(
            "timestamp" => strtotime($comment_row['timestamp']),
            "user" => $comment_row['user'],
            "comment" => $comment_row['comment'],
        ));
    }
}

die(json_encode($data, JSON_PRETTY_PRINT));
