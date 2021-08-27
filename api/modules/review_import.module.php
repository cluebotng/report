<?php

/*
 * Review export
 * - Returns edits requiring review
 */
 header('Content-Type: text/json');

/*
 * From db/status.go:
 *  const EDIT_CLASSIFICATION_VANDALISM = 0
 *  const EDIT_CLASSIFICATION_CONSTRUCTIVE = 1
 *  const EDIT_CLASSIFICATION_SKIPPED = 2
 *  const EDIT_CLASSIFICATION_UNKNOWN = 3
*/
$review_to_report_statuses = array(
    0 => statusNameToId('Reviewed - Included in dataset as Vandalism'),
    1 => statusNameToId('Reviewed - Included in dataset as Constructive'),
    2 => statusNameToId('Reviewed - Not included in dataset'),
    3 => statusNameToId('Partially reviewed'),
);

$edit_statuses = json_decode(file_get_contents('https://cluebotng-review.toolforge.org/api/report/export'));

$expected_statuses = array();
foreach ($edit_statuses as $diff_id => $review_status_id) {
    $report_status = $review_to_report_statuses[(int)$review_status_id];
    if ($report_status) {
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
    $query .= ' WHERE `status` NOT IN (0, 1, 3, 4)';
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
}

die(json_encode($updated_ids, JSON_PRETTY_PRINT));
