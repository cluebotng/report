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
        global $statuses, $mysql;

        $data = array();

        $conditions = array();
        if (isset($_REQUEST['after_edit_id']) && !empty($_REQUEST['after_edit_id'])) {
            array_push($conditions, "`id` >= '" . mysqli_real_escape_string($mysql, $_REQUEST['edit_id']) . "'");
        }
        if (isset($_REQUEST['user']) && !empty($_REQUEST['user'])) {
            array_push($conditions, "`user` = '" . mysqli_real_escape_string($mysql, $_REQUEST['user']) . "'");
        }
        if (isset($_REQUEST['article']) && !empty($_REQUEST['article'])) {
            array_push($conditions, "`article` = '" . mysqli_real_escape_string($mysql, $_REQUEST['article']) . "'");
        }
        if (isset($_REQUEST['heuristic']) && !empty($_REQUEST['heuristic'])) {
            array_push($conditions, "`heuristic` = '" . mysqli_real_escape_string($mysql, $_REQUEST['heuristic']) . "'");
        }
        if (isset($_REQUEST['regex']) && !empty($_REQUEST['regex'])) {
            array_push($conditions, "`regex` = '" . mysqli_real_escape_string($mysql, $_REQUEST['regex']) . "'");
        }
        if (isset($_REQUEST['old_id']) && !empty($_REQUEST['old_id'])) {
            array_push($conditions, "`old_id` = '" . mysqli_real_escape_string($mysql, $_REQUEST['old_id']) . "'");
        }
        if (isset($_REQUEST['new_id']) && !empty($_REQUEST['new_id'])) {
            array_push($conditions, "`new_id` = '" . mysqli_real_escape_string($mysql, $_REQUEST['new_id']) . "'");
        }
        if (isset($_REQUEST['reverted']) && !empty($_REQUEST['reverted'])) {
            array_push($conditions, "`reverted` = '" . mysqli_real_escape_string($mysql, $_REQUEST['reverted']) . "'");
        }

        $query = "SELECT * FROM `vandalism`";
        if (count($conditions) > 0) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY " . ((isset($_REQUEST['random'])) ? "RAND()" : "id DESC");
        if (isset($_REQUEST['limit']) && !empty($_REQUEST['limit'])) {
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

            $beaten_result = mysqli_query($mysql, "SELECT * FROM `beaten` WHERE `diff` = '" . mysqli_real_escape_string($mysql, $row['diff']) . "'");
            if (mysqli_num_rows($beaten_result) > 0) {
                $data['edit-' . $row['id']]['beaten'] = true;

                $beaten_row = mysqli_fetch_assoc($beaten_result);
                $data['edit-' . $row['id']]['beaten_by'] = $beaten_row['user'];
            }

            $report_result = mysqli_query($mysql, "SELECT * FROM `reports` WHERE `revertid` = '" . mysqli_real_escape_string($mysql, $row['id']) . "'");
            if (mysqli_num_rows($report_result) > 0) {
                $report_row = mysqli_fetch_assoc($report_result);
                $data['edit-' . $row['id']]['report'] = array(
                    "timestamp" => strtotime($report_row['timestamp']),
                    "reporter" => $report_row['reporter'],
                    "status" => $statuses[$report_row['status']],
                    "status_id" => $report_row['status'],
                );
            }
        }

        mysqli_free_result($result);
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}
ApiModule::register('edits.list', 'ApiModuleEditsList');
