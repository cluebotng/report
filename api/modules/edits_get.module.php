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
        if (isset($_REQUEST['edit_id']) && !empty($_REQUEST['edit_id'])) {
            array_push($conditions, "`id` = '" . mysqli_real_escape_string($mysql, $_REQUEST['edit_id']) . "'");
        }
        if (isset($_REQUEST['old_id']) && !empty($_REQUEST['old_id'])) {
            array_push($conditions, "`old_id` = '" . mysqli_real_escape_string($mysql, $_REQUEST['old_id']) . "'");
        }
        if (isset($_REQUEST['new_id']) && !empty($_REQUEST['new_id'])) {
            array_push($conditions, "`new_id` = '" . mysqli_real_escape_string($mysql, $_REQUEST['new_id']) . "'");
        }

        if (count($conditions) === 0) {
            return json_encode(array(
                "error" => "argument_error",
                "error_message" => "You must specify edit_id, old_id or new_id for this method.",
            ));
        }

        $query = "SELECT * FROM `vandalism` WHERE " . implode(" AND ", $conditions);
        $result = mysqli_query($mysql, $query);
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

            $diffEscaped = mysqli_real_escape_string($mysql, $row['diff']);
            $beaten_query = "SELECT * FROM `beaten` WHERE `diff` = '" . $diffEscaped . "'";
            $beaten_result = mysqli_query($mysql, $beaten_query);
            if (mysqli_num_rows($beaten_result) > 0) {
                $data['beaten'] = true;

                $beaten_row = mysqli_fetch_assoc($beaten_result);
                $data['beaten_by'] = $beaten_row['user'];
            }

            $idEscaped = mysqli_real_escape_string($mysql, $row['id']);
            $report_query = "SELECT * FROM `reports` WHERE `revertid` = '" . $idEscaped . "'";
            $report_result = mysqli_query($mysql, $report_query);
            if (mysqli_num_rows($report_result) > 0) {
                $report_row = mysqli_fetch_assoc($report_result);
                $data['report'] = array(
                    "timestamp" => strtotime($report_row['timestamp']),
                    "reporter" => $report_row['reporter'],
                    "status" => STATUSES[$report_row['status']] ?? null,
                    "status_id" => (int)$report_row['status'],
                );
            }
        }

        mysqli_free_result($result);
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}

ApiModule::register('edits.get', 'ApiModuleEditsGet');
