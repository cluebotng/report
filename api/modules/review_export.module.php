<?php

namespace ReportApi;

/*
 * Review export
 * - Returns edits requiring review
 */
class ApiModuleReviewExport extends ApiModule
{
    public function content()
    {
        global $mysql;
        $status_ids = (isset($_GET['include_in_progress'])) ? array(2, 5, 6) : array(2);

        $status_ids_sql = '';
        foreach ($status_ids as $status_id) {
            if (strlen($status_ids_sql) > 0) {
                $status_ids_sql .= ', ';
            }
            $status_ids_sql .= '"' . mysqli_real_escape_string($mysql, $status_id) . '"';
        }

        $result = mysqli_query(
            $mysql,
            'SELECT `new_id` FROM `reports` 
                   JOIN `vandalism` ON `revertid` = `id` 
                   WHERE `status` IN (' . $status_ids_sql . ')'
        );

        $ids = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $ids[] = (int)$row['new_id'];
        }
        mysqli_free_result($result);

        return json_encode($ids, JSON_PRETTY_PRINT);
    }
}

ApiModule::register('review.export', 'ApiModuleReviewExport');
