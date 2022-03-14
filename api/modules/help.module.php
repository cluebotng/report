<?php

namespace ReportApi;

/*
 * Help module
 * - Provides human information about the API endpoints
 */

class ApiModuleHelp extends ApiModule
{
    public function header()
    {
        header('Content-Type: text/plain');
    }

    public function content()
    {
        return "ClueBot NG Report API

Methods:
|-Reports:
| |- List all reports
| | `- https://cluebotng.toolforge.org/api/?action=reports.list
| |  `- Accepts the following arguments:
| |     status - (optional) Report status, valid values are as follows:
| |       1 - Reported
| |       2 - Invalid
| |       3 - Sending to Review Interface
| |       4 - Bug
| |       5 - Resolved
| |       6 - Queued to be reviewed
| |       7 - Partially reviewed
| |       8 - Reviewed (Included in dataset as Constructive)
| |       8 - Reviewed (Included in dataset as Vandalism)
| |       10 - Reviewed (Not included in dataset)
| |
| |- Get specific report
|   `- https://cluebotng.toolforge.org/api/?action=reports.get
|    `- Accepts the following arguments:
|       id - Revert ID, valid values are obtained from the reports.list method
|
|-Edits:
| |- List all edits
| | `- https://cluebotng.toolforge.org/api/?action=edits.list
| |  `- Accepts the following arguments:
| |     after_edit_id - (Optional) Get edits after this id
| |     user - (Optional) User the edit was by
| |     article - (Optional) Article that the edit was on
| |     heuristic - (Optional) Only return edits matching this heuristic
| |     regex - (Optional) Only return edits matching this regex
| |     reverted - (Optional) Edit reverted (1 for yes, 0 for no)
| |
| |- Get specific edit
|   `- https://cluebotng.toolforge.org/api/?action=edits.get
|    `- Accepts the following arguments:
|       edit_id - CBNG edit ID
|       old_id - Old wikipedia revision ID
|       new_id - New wikipedia revision ID
|
|-Internal Review Endpoints:
| |- List all edits to be imported for review
| | `- https://cluebotng.toolforge.org/api/?action=review.export
| |
| |- Update all edits in review
| | `- https://cluebotng.toolforge.org/api/?action=review.import
|
`-Help:
 |- Get module documentation
   `- https://cluebotng.toolforge.org/api/?action=help";
    }
}

ApiModule::register('help', 'ApiModuleHelp');
