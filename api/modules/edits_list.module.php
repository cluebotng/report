<?php

namespace ReportApi;

/*
 * Edits list
 * - Returns edits in the database
 */
class ApiModuleEditsList extends ApiModule
{
    public function content()
    {
        global $mysql;

        $data = array();

        $conditions = array();
        if (!empty($_REQUEST['after_edit_id'])) {
            $escape = mysqli_real_escape_string($mysql, $_REQUEST['edit_id']);
            $conditions[] = "`id` >= '" . $escape . "'";
        }
        if (!empty($_REQUEST['user'])) {
            $escape = mysqli_real_escape_string($mysql, $_REQUEST['user']);
            $conditions[] = "`user` = '" . $escape . "'";
        }
        if (!empty($_REQUEST['article'])) {
            $escape = mysqli_real_escape_string($mysql, $_REQUEST['article']);
            $conditions[] = "`article` = '" . $escape . "'";
        }
        if (!empty($_REQUEST['heuristic'])) {
            $escape = mysqli_real_escape_string($mysql, $_REQUEST['heuristic']);
            $conditions[] = "`heuristic` = '" . $escape . "'";
        }
        if (!empty($_REQUEST['regex'])) {
            $escape = mysqli_real_escape_string($mysql, $_REQUEST['regex']);
            $conditions[] = "`regex` = '" . $escape . "'";
        }
        if (!empty($_REQUEST['old_id'])) {
            $escape = mysqli_real_escape_string($mysql, $_REQUEST['old_id']);
            $conditions[] = "`old_id` = '" . $escape . "'";
        }
        if (!empty($_REQUEST['new_id'])) {
            $escape = mysqli_real_escape_string($mysql, $_REQUEST['new_id']);
            $conditions[] = "`new_id` = '" . $escape . "'";
        }
        if (!empty($_REQUEST['reverted'])) {
            $escape = mysqli_real_escape_string($mysql, $_REQUEST['reverted']);
            $conditions[] = "`reverted` = '" . $escape . "'";
        }

        $query = "SELECT * FROM `vandalism`";
        if (count($conditions) > 0) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY " . ((isset($_REQUEST['random'])) ? "RAND()" : "id DESC");
        if (!empty($_REQUEST['limit'])) {
            $query .= " LIMIT 0, " . (int)$_REQUEST['limit'];
        }

        $result = mysqli_query($mysql, $query);
        while ($row = mysqli_fetch_assoc($result)) {
            $data['edit-' . $row['id']] = array(
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
                $data['edit-' . $row['id']]['score'] = (float)$matches[1];
            }

            $beaten_query = "SELECT * FROM `beaten` WHERE `diff` = ?";
            $beaten_stmt = mysqli_prepare($mysql, $beaten_query);
            mysqli_stmt_bind_param($beaten_stmt, 's', $row['diff']);
            mysqli_stmt_execute($beaten_stmt);
            $beaten_result = mysqli_stmt_get_result($beaten_stmt);
            if (mysqli_num_rows($beaten_result) > 0) {
                $data['edit-' . $row['id']]['beaten'] = true;

                $beaten_row = mysqli_fetch_assoc($beaten_result);
                $data['edit-' . $row['id']]['beaten_by'] = $beaten_row['user'];
            }
            mysqli_stmt_close($beaten_stmt);

            $report_query = "SELECT * FROM `reports` WHERE `revertid` = ?";
            $report_stmt = mysqli_prepare($mysql, $report_query);
            mysqli_stmt_bind_param($report_stmt, 's', $row['id']);
            mysqli_stmt_execute($report_stmt);
            $report_result = mysqli_stmt_get_result($report_stmt);
            if (mysqli_num_rows($report_result) > 0) {
                $report_row = mysqli_fetch_assoc($report_result);
                $data['edit-' . $row['id']]['report'] = array(
                    "timestamp" => strtotime($report_row['timestamp']),
                    "reporter" => $report_row['reporter'],
                    "status" => statusIdToName($report_row['status']),
                    "status_id" => $report_row['status'],
                );
            }
            mysqli_stmt_close($report_stmt);
        }

        mysqli_free_result($result);
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}
ApiModule::register('edits.list', 'ApiModuleEditsList');
