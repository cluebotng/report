<?php

namespace ReportApi;

/*
 * Review export
 * - Returns edits requiring review
 */
class ApiModuleReviewImport extends ApiModule
{
    public function content()
    {
        global $statuses, $mysql;

        /*
         * Needs to match the ids used in the API endpoint `_calculate_report_status`
        */
        $review_to_report_statuses = array(
            0 => statusNameToId('Queued to be reviewed'),
            1 => statusNameToId('Partially reviewed'),
            2 => statusNameToId('Reviewed - Included in dataset as Vandalism'),
            3 => statusNameToId('Reviewed - Included in dataset as Constructive'),
            4 => statusNameToId('Reviewed - Not included in dataset'),
            5 => statusNameToId('Edit Data Has Been Removed'),
        );

        $expected_statuses = array();

        // Legacy Report Interface Import
        $edit_statuses = json_decode(file_get_contents('https://cluebotng-review.toolforge.org/api/v1/edit-groups/1/dump-report-status/'));
        foreach ($edit_statuses as $diff_id => $review_status_id) {
            if ($report_status = $review_to_report_statuses[(int)$review_status_id]) {
                $expected_statuses[(int)$diff_id] = $report_status;
            }
        }

        // Report Interface Import
        $edit_statuses = json_decode(file_get_contents('https://cluebotng-review.toolforge.org/api/v1/edit-groups/2/dump-report-status/'));
        foreach ($edit_statuses as $diff_id => $review_status_id) {
            if ($report_status = $review_to_report_statuses[(int)$review_status_id]) {
                $expected_statuses[(int)$diff_id] = $report_status;
            }
        }

        $diff_ids_sql = '';
        foreach (array_keys($expected_statuses) as $diff_id) {
            if (strlen($diff_ids_sql) > 0) {
                $diff_ids_sql .= ', ';
            }
            $diff_ids_sql .= '"' . mysqli_real_escape_string($mysql, (int)$diff_id) . '"';
        }

        $updated_ids = array();
        if (strlen($diff_ids_sql) > 0) {
            // Load the edits we might want to update
            $query = 'SELECT `revertid`, `new_id`, `status` FROM `reports`';
            $query .= ' INNER JOIN `vandalism` ON (`vandalism`.`id` = `reports`.`revertid`)';
            $query .= ' WHERE `status` NOT IN (0, 1, 3, 4, 5)';
            $query .= ' AND `new_id` IN (' . $diff_ids_sql . ')';

            // Update them if required
            $results = mysqli_query($mysql, $query);
            while ($row = mysqli_fetch_assoc($results)) {
                $expected_status = $expected_statuses[(int)$row['new_id']];
                if ($expected_status && (int)$row['status'] !== $expected_status) {
                    array_push($updated_ids, (int)$row['new_id']);
                    updateStatus($row['revertid'], $expected_status, 'Review Interface');
                }
            }
            mysqli_free_result($results);
        }

        return json_encode($updated_ids, JSON_PRETTY_PRINT);
    }
}

ApiModule::register('review.import', 'ApiModuleReviewImport');
