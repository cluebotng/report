<?php
/*
 * Help module
 * - Provides help about the modules we have
 */

$help_text = ("
ClueBot NG API

Options:
format - Output format, valid values are as follows:
- xml
- json
- php (serialize)
- debug (print_r)

Methods:
|-Reports:
| |- List all reports
| | `- http://api.cluebot.cluenet.org/?action=reports.list
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
|   `- http://api.cluebot.cluenet.org/?action=reports.get
|    `- Accepts the following arguments:
|       rid - Report ID, valid values are obtained from the reports.list method
|
|-Beaten: - These are not implimented yet
| |- List all beaten reverts
|   `- http://api.cluebot.cluenet.org/?action=beaten.list
|    `- Accepts the following arguments:
|       article - (Optional) article the edit was on
|       user - (Optional) user the edit was by
|
|-Users:
| |- List all users
| |`- http://api.cluebot.cluenet.org/?action=users.list
| | `- Accepts the following arguments:
| |    superadmin - (Optional) filter by superadmin status (1 for yes, 0 for no)
| |    admin - (Optional) filter by admin status (1 for yes, 0 for no)
| |
| |- Get specific user - Certain information like hashed passwords will not be returned
|  `- http://api.cluebot.cluenet.org/?action=users.get
|   `- Accepts the following arguments:
|      uid - User ID
|      username - Username
|
|-Edits:
| |- List all edits
| | `- http://api.cluebot.cluenet.org/?action=edits.list
| |  `- Accepts the following arguments:
| |     eid - (Optional) Get edits after this id
| |     user - (Optional) User the edit was by
| |     article - (Optional) Article that the edit was on
| |     heuristic - (Optional) Only return edits matching this heuristic
| |     regex - (Optional) Only return edits matching this regex
| |     reverted - (Optional) Edit reverted (1 for yes, 0 for no)
| |
| |- Get specific edit
|   `- http://api.cluebot.cluenet.org/?action=edits.get
|    `- Accepts the following arguments:
|       eid - CBNG edit ID (see talk page message comments)
|       diff - Diff URL
|       old_id - Old wikipedia revision ID
|       new_id - New wikipedia revision ID
|
|-Live: - NOTE THESE DO NOT CHECK PRE/POST PROCESSING STUFF JUST THE CORE OUTPUT
| |- Check a wikipedia id against the core
|   `- http://api.cluebot.cluenet.org/?action=live.edit
|    `- Accepts the following arguments:
|       article - Article the edit is in
|       diff - Diff ID of the edit
|
`-Help:
 |- Get module documentation
   `- http://api.cluebot.cluenet.org/?action=help
");

$data = array(
	"Help" => $help_text,
);

die(output_encoding($data));
?>
