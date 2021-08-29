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
        $result = mysqli_query($mysql, 'SELECT `new_id` FROM `reports` JOIN `vandalism` ON `revertid` = `id` WHERE `status` = 2');

        $ids = array();
        while ($row = mysqli_fetch_assoc($result)) {
            array_push($ids, (int)$row['new_id']);
        }
        mysqli_free_result($result);

        return json_encode($ids, JSON_PRETTY_PRINT);
    }
}

ApiModule::register('review.export', 'ApiModuleReviewExport');
