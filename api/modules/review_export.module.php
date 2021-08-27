<?php

/*
 * Review export
 * - Returns edits requiring review
 */
 header('Content-Type: text/json');

$result = mysqli_query($mysql, 'SELECT `new_id` FROM `reports` JOIN `vandalism` ON `revertid` = `id` WHERE `status` = 2');

$ids = array();

while ($row = mysqli_fetch_assoc($result)) {
    array_push($ids, (int)$row['new_id']);
}

die(json_encode($ids, JSON_PRETTY_PRINT));
