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
        global $mysql;
        if (empty($_REQUEST['id'])) {
            return json_encode(array(
                "error" => "argument_error",
                "error_message" => "Specified id was in an invalid format",
            ));
        }
        $query = "SELECT * FROM `reports` WHERE `revertid` = ?";
        $stmt = mysqli_prepare($mysql, $query);
        if (!$stmt) {
            return json_encode(array(
                "error" => "database_error",
                "error_message" => "Failed to prepare statement",
            ));
        }
        mysqli_stmt_bind_param($stmt, 's', $_REQUEST['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);

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
            "status" => statusIdToName($report_row['status']),
            "status_id" => (int)$report_row['status'],
            "comments" => array(),
        );
        mysqli_free_result($result);

        $comment_query = "SELECT * FROM `comments` WHERE `revertid` = ?";
        $comment_stmt = mysqli_prepare($mysql, $comment_query);
        if (!$stmt) {
            return json_encode(array(
                "error" => "database_error",
                "error_message" => "Failed to prepare statement",
            ));
        }
        mysqli_stmt_bind_param($comment_stmt, 's', $report_row['revertid']);
        mysqli_stmt_execute($comment_stmt);
        $comment_result = mysqli_stmt_get_result($comment_stmt);
        while ($comment_row = mysqli_fetch_assoc($comment_result)) {
            $data['comments'][] = array(
                "timestamp" => strtotime($comment_row['timestamp']),
                "user" => $comment_row['user'],
                "comment" => $comment_row['comment'],
            );
        }
        mysqli_stmt_close($comment_stmt);

        return json_encode($data, JSON_PRETTY_PRINT);
    }
}

ApiModule::register('reports.get', 'ApiModuleReportsGet');
