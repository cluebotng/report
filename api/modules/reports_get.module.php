<?php

namespace ReportApi;

/*
 * Reports get
 * - Returns a specific report in the database
 */
class ApiModuleReportsGet extends ApiModule
{
    public function content()
    {
        global $statuses, $mysql;

        $query = "SELECT * FROM `reports`";
        if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
            $query .= " WHERE `revertid` = '" . mysqli_real_escape_string($mysql, $_REQUEST['id']) . "'";
        } else {
            return json_encode(array(
                "error" => "argument_error",
                "error_message" => "Specified id was in an invalid format",
            ));
        }

        $result = mysqli_query($mysql, $query);
        if (mysqli_num_rows($result) !== 1) {
            return json_encode(array(
                "error" => "argument_error",
                "error_message" => "Specified id was not found",
            ));
        }

        $report_row = mysqli_fetch_assoc($result);
        $data = array(
            "revertid" => (int)$report_row['revertid'],
            "timestamp" => strtotime($report_row['timestamp']),
            "reporter" => $report_row['reporter'],
            "status" => $statuses[$report_row['status']],
            "status_id" => (int)$report_row['status'],
            "comments" => array(),
        );
        mysqli_free_result($result);

        $comment_result = mysqli_query($mysql, "SELECT * FROM `comments` WHERE `revertid` = '" . mysqli_real_escape_string($mysql, $row['revertid']) . "'");
        while ($comment_row = mysqli_fetch_assoc($comment_result)) {
            array_push($data['comments'], array(
                "timestamp" => strtotime($comment_row['timestamp']),
                "user" => $comment_row['user'],
                "comment" => $comment_row['comment'],
            ));
        }
        mysqli_free_result($comment_result);

        return json_encode($data, JSON_PRETTY_PRINT);
    }
}

ApiModule::register('reports.get', 'ApiModuleReportsGet');
