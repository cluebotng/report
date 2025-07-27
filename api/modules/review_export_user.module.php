<?php

namespace ReportApi;

/*
 * Review export
 * - Returns users who marked edits as requiring review
 */

class ApiModuleReviewUsersExport extends ApiModule
{
    public function content()
    {
        global $mysql;
        $result = mysqli_query(
            $mysql,
            'SELECT `new_id`, `username` FROM `edits_sent_for_review` ' .
            'JOIN `users` ON `users`.`userid` = `edits_sent_for_review`.`userid` ' .
            'JOIN `vandalism` ON `vandalism`.`id` = `edits_sent_for_review`.`revertid`'
        );

        $edits = array();
        while ($row = mysqli_fetch_assoc($result)) {
            if (!array_key_exists($row['revertid'], $edits)) {
                $edits[$row['revertid']] = array();
            }
            array_push($edits[$row['revertid']], $row['username']);
        }
        mysqli_free_result($result);

        return json_encode($edits, JSON_PRETTY_PRINT);
    }
}

ApiModule::register('review.export.users', 'ApiModuleReviewUsersExport');
