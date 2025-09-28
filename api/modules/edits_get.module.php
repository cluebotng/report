<?php

namespace ReportApi;

/*
 * Edits get
 * - Returns a specific edit in the database
 */
class ApiModuleEditsGet extends ApiModule
{
    public function content()
    {
        global $mysql;

        $conditions = array();
        if (!empty($_REQUEST['edit_id'])) {
            $conditions[] = "`id` = ?";
        }
        if (!empty($_REQUEST['old_id'])) {
            $conditions[] = "`old_id` = ?";
        }
        if (!empty($_REQUEST['new_id'])) {
            $conditions[] = "`new_id` = ?";
        }

        if (count($conditions) === 0) {
            return json_encode(array(
                "error" => "argument_error",
                "error_message" => "You must specify edit_id, old_id or new_id for this method.",
            ));
        }

        $query = "SELECT * FROM `vandalism` WHERE " . implode(" AND ", $conditions);
        $stmt = mysqli_prepare($mysql, $query);
        $params = array();
        foreach ($conditions as $condition) {
            if (str_contains($condition, 'old_id')) {
                $params[] = $_REQUEST['old_id'];
            } elseif (str_contains($condition, 'new_id')) {
                $params[] = $_REQUEST['new_id'];
            } else {
                $params[] = $_REQUEST['edit_id'];
            }
        }
        $types = str_repeat('s', count($params));
        $stmt_params = array($stmt, $types);
        foreach ($params as $param) {
            $stmt_params[] = $param;
        }
        call_user_func_array('mysqli_stmt_bind_param', $stmt_params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) !== 1) {
            return json_encode(array(
                "error" => "argument_error",
                "error_message" => "The specified edit_id, diff, old_id or new_id was not found.",
            ));
        }

        while ($row = mysqli_fetch_assoc($result)) {
            $data = array(
                "id" => (int)$row['id'],
                "timestamp" => strtotime($row['timestamp']),
                "user" => $row['user'],
                "article" => $row['article'],
                "heuristic" => (strlen($row['heuristic']) > 0 ? $row['heuristic'] : null),
                "regex" => $row['regex'],
                "reason" => $row['reason'],
                "diff_url" => $row['diff'],
                "old_id" => $row['old_id'],
                "new_id" => $row['new_id'],
                "reverted" => (bool)$row['reverted'],
                "beaten" => false,
                "beaten_by" => null,
                "score" => null,
                "report" => null,
            );

            if (preg_match("/ANN scored at ([0-9.]+)/", $row['reason'], $matches) === 1) {
                $data['score'] = (float)$matches[1];
            }

            $beaten_query = "SELECT * FROM `beaten` WHERE `diff` = ?";
            $beaten_stmt = mysqli_prepare($mysql, $beaten_query);
            mysqli_stmt_bind_param($beaten_stmt, 's', $row['diff']);
            mysqli_stmt_execute($beaten_stmt);
            $beaten_result = mysqli_stmt_get_result($beaten_stmt);
            if (mysqli_num_rows($beaten_result) > 0) {
                $data['beaten'] = true;

                $beaten_row = mysqli_fetch_assoc($beaten_result);
                $data['beaten_by'] = $beaten_row['user'];
            }
            mysqli_stmt_close($beaten_stmt);

            $idEscaped = $row['id'];
            $report_query = "SELECT * FROM `reports` WHERE `revertid` = ?";
            $report_stmt = mysqli_prepare($mysql, $report_query);
            mysqli_stmt_bind_param($report_stmt, 's', $idEscaped);
            mysqli_stmt_execute($report_stmt);
            $report_result = mysqli_stmt_get_result($report_stmt);
            if (mysqli_num_rows($report_result) > 0) {
                $report_row = mysqli_fetch_assoc($report_result);
                $data['report'] = array(
                    "timestamp" => strtotime($report_row['timestamp']),
                    "reporter" => $report_row['reporter'],
                    "status" => statusIdToName($report_row['status']),
                    "status_id" => (int)$report_row['status'],
                );
            }
            mysqli_stmt_close($report_stmt);
        }

        mysqli_stmt_close($stmt);
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}

ApiModule::register('edits.get', 'ApiModuleEditsGet');
