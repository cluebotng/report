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
        global $mysql;

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
        $context  = stream_context_create(array('http' => array('user_agent' => 'ClueBot NG Report Interface')));

        // We have edits split up e.g. "Legacy Report Interface Import" & "Report Interface Import"
        // Find all groups that are "Reported False Positives" and load them
        $raw = @file_get_contents(
            'https://cluebotng-review.toolforge.org/api/v1/edit-groups/',
            false,
            $context
        );
        if ($raw == null) {
            return json_encode(array("error" => "Failed to retrieve edit groups"), JSON_PRETTY_PRINT);
        }
        $edit_groups = json_decode($raw, true);
        $processed_groups = array();
        foreach ($edit_groups as $edit_group) {
            if ($edit_group["type"] == "Reported False Positives") {
                $raw = @file_get_contents(
                    'https://cluebotng-review.toolforge.org/api/v1/edit-groups/' . $edit_group["id"] . '/dump-report-status/',
                    false,
                    $context);
                if ($raw != null) {
                    $processed_groups[] = $edit_group["id"];
                    foreach (json_decode($raw) as $diff_id => $review_status_id) {
                        if ($report_status = $review_to_report_statuses[(int)$review_status_id]) {
                            $expected_statuses[(int)$diff_id] = $report_status;
                        }
                    }
                }
            }
        }

        $diff_ids_sql = '';
        foreach (array_keys($expected_statuses) as $diff_id) {
            if (strlen($diff_ids_sql) > 0) {
                $diff_ids_sql .= ', ';
            }
            $diff_ids_sql .= '"' . mysqli_real_escape_string($mysql, (int)$diff_id) . '"';
        }

        $exclude_status_ids = [
            statusNameToId('Reported'),
            statusNameToId('Invalid'),
            statusNameToId('Bug'),
            statusNameToId('Resolved'),
            statusNameToId('Edit Data Has Been Removed'),
        ];

        $updated_ids = array();
        if (strlen($diff_ids_sql) > 0) {
            // Load the edits we might want to update
            $query = 'SELECT `revertid`, `new_id`, `status` FROM `reports`';
            $query .= ' INNER JOIN `vandalism` ON (`vandalism`.`id` = `reports`.`revertid`)';
            $query .= ' WHERE `status` NOT IN (' . join(", ", $exclude_status_ids) . ')';
            $query .= ' AND `new_id` IN (' . $diff_ids_sql . ')';

            // Update them if required
            $results = mysqli_query($mysql, $query);
            while ($row = mysqli_fetch_assoc($results)) {
                $expected_status = $expected_statuses[(int)$row['new_id']];

                if ($expected_status && (int)$row['status'] !== $expected_status) {
                    $updated_ids[] = (int)$row['new_id'];
                    updateStatus($row['revertid'], $expected_status, 'Review Interface', -3);
                }
            }
            mysqli_free_result($results);
        }

        return json_encode([
            "groups" => [
                "processed" => $processed_groups
            ],
            "edits" => [
                "processed" => $expected_statuses,
                "updated" => $updated_ids
            ]
        ], JSON_PRETTY_PRINT);
    }
}

ApiModule::register('review.import', 'ApiModuleReviewImport');
