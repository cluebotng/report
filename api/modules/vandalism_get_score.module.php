<?php

namespace ReportApi;

/*
 * Vandalism get score
 * - Returns the score for a specific vandalism entry
 */

class ApiModuleVandalismGetScore extends ApiModule
{
    public function content()
    {
        global $mysql;

        if (isset($_REQUEST['id'])) {
            $stmt = mysqli_prepare($mysql, "SELECT * FROM `vandalism` WHERE `id` = ?");
            mysqli_stmt_bind_param($stmt, 's', $_REQUEST['id']);
        } else if (isset($_REQUEST['new_id'])) {
            $stmt = mysqli_prepare($mysql, "SELECT * FROM `vandalism` WHERE `new_id` = ?");
            mysqli_stmt_bind_param($stmt, 's', $_REQUEST['new_id']);
        } else {
            return json_encode(["error" => "No id or new_id specified"]);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);

        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);

        if (!$row) {
            return json_encode(["error" => "No entry found in database"]);
        }

        if (!preg_match('/^ANN scored at ([0-9.]+)$/', $row['reason'], $matches, PREG_OFFSET_CAPTURE)) {
            return json_encode(["error" => "Could not extract score from vandalism entry"]);
        }

        return json_encode([
            "id" => $row['id'],
            "new_id" => $row['new_id'],
            "score" => (float)$matches[1][0],
        ]);
    }
}

ApiModule::register('vandalism.get.score', 'ApiModuleVandalismGetScore');
