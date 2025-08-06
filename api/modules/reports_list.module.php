<?php

namespace ReportApi;

/*
 * Reports list
 * - Returns reports in the database
 */
class ApiModuleReportsList extends ApiModule
{
    public function content()
    {
        global $statuses, $mysql;

        $query = "SELECT * FROM `reports` JOIN `vandalism` ON `revertid` = `id`";
        if (isset($_REQUEST['status']) && (!empty($_REQUEST['status']) || $_REQUEST['status'] == '0')) {
            if (array_key_exists($_REQUEST['status'], $statuses)) {
                $query .= " WHERE `status` = '" . mysqli_real_escape_string($mysql, $_REQUEST['status']) . "'";
            } else {
                return json_encode(array(
                    "error" => "argument_error",
                    "error_message" => "Specified status value was invalid",
                ), JSON_PRETTY_PRINT);
            }
        }

        $query .= " ORDER BY " . ((isset($_REQUEST['random'])) ? "RAND()" : "id DESC");
        if (isset($_REQUEST['limit']) && !empty($_REQUEST['limit'])) {
            $query .= " LIMIT 0, " . (int)$_REQUEST['limit'];
        }

        $data = array();
        $report_results = mysqli_query($mysql, $query);
        while ($report_row = mysqli_fetch_assoc($report_results)) {
            $data['report-' . $report_row['revertid']] = array(
                "revertid" => (int)$report_row['revertid'],
                "revid" => (int)$report_row['new_id'],
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
            mysqli_free_result($comments_result);
        }
        mysqli_free_result($report_results);

        return json_encode($data, JSON_PRETTY_PRINT);
    }
}

ApiModule::register('reports.list', 'ApiModuleReportsList');
