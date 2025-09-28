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
        global $mysql;

        // Build the base query with placeholders
        $query = "SELECT * FROM `reports` JOIN `vandalism` ON `revertid` = `id`";
        $params = [];
        $types = '';
        
        if (isset($_REQUEST['status']) && (!empty($_REQUEST['status']) || $_REQUEST['status'] == '0')) {
            if (isValidStatusId($_REQUEST['status'])) {
                $query .= " WHERE `status` = ?";
                $params[] = &$_REQUEST['status'];
                $types .= 'i';
            } else {
                return json_encode(array(
                    "error" => "argument_error",
                    "error_message" => "Specified status value was invalid",
                ), JSON_PRETTY_PRINT);
            }
        }

        $query .= " ORDER BY " . ((isset($_REQUEST['random'])) ? "RAND()" : "id DESC");
        if (!empty($_REQUEST['limit'])) {
            $limit = (int)$_REQUEST['limit'];
            $query .= " LIMIT 0, ?";
            $params[] = &$limit;
            $types .= 'i';
        }

        $data = array();
        $stmt = mysqli_prepare($mysql, $query);
        
        // Bind parameters if any
        if (!empty($params)) {
            $bindParams = array_merge([$stmt, $types], $params);
            call_user_func_array('mysqli_stmt_bind_param', $bindParams);
        }
        
        mysqli_stmt_execute($stmt);
        $report_results = mysqli_stmt_get_result($stmt);
        
        while ($report_row = mysqli_fetch_assoc($report_results)) {
            $data['report-' . $report_row['revertid']] = array(
                "revertid" => (int)$report_row['revertid'],
                "revid" => (int)$report_row['new_id'],
                "timestamp" => strtotime($report_row['timestamp']),
                "reporter" => $report_row['reporter'],
                "status" => statusIdToName($report_row['status']),
                "status_id" => (int)$report_row['status'],
                "comments" => array(),
            );

            // Prepare statement for comments query
            $comment_query = "SELECT * FROM `comments` WHERE `revertid` = ?";
            $comment_stmt = mysqli_prepare($mysql, $comment_query);
            $revertId = $report_row['id'];
            mysqli_stmt_bind_param($comment_stmt, 'i', $revertId);
            mysqli_stmt_execute($comment_stmt);
            $comments_result = mysqli_stmt_get_result($comment_stmt);
            
            while ($comment_row = mysqli_fetch_assoc($comments_result)) {
                $data['report-' . $report_row['revertid']]['comments'][] = array(
                    "timestamp" => strtotime($comment_row['timestamp']),
                    "user" => $comment_row['user'],
                    "comment" => $comment_row['comment'],
                );
            }
            mysqli_stmt_close($comment_stmt);
        }
        mysqli_stmt_close($stmt);

        return json_encode($data, JSON_PRETTY_PRINT);
    }
}

ApiModule::register('reports.list', 'ApiModuleReportsList');
