<?PHP

$statuses = array(
    'Reported',
    'Invalid',
    'Sending to Review Interface',
    'Bug',
    'Resolved',
    'Queued to be reviewed',
    'Partially reviewed',
    'Reviewed - Included in dataset as Constructive',
    'Reviewed - Included in dataset as Vandalism',
    'Reviewed - Not included in dataset'
);

function statusIdToName($status)
{
    global $statuses;
    return $statuses[$status];
}

function statusNameToId($status)
{
    global $statuses;
    return array_search($status, $statuses);
}

function createReport($id, $user)
{
    global $mysql;
    if (isset($_SESSION['userid'])) {
        $userid = $_SESSION['userid'];
        $user = $_SESSION['username'];
    } else {
        $userid = -1;
    }

    $query = "INSERT INTO `reports` (`revertid`, `reporterid`, `reporter`, `status`) VALUES (?, ?, ?, 0)";

    if ($stmt = mysqli_prepare($mysql, $query)) {
        mysqli_stmt_bind_param($stmt, "sis", $id, $userid, $user);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return true;
    } else {
        return false;
    }
}

function createComment($id, $user, $comment, $forceUser = false)
{
    global $mysql;
    if (!$forceUser) {
        if (isset($_SESSION['userid'])) {
            $userid = $_SESSION['userid'];
            $user = $_SESSION['username'];
        } else {
            $userid = -1;
        }
    } else {
        $userid = -2;
    }

    $query = "INSERT INTO `comments` (`revertid`, `userid`, `user`, `comment`) VALUES (?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($mysql, $query)) {
        mysqli_stmt_bind_param($stmt, "siss", $id, $userid, $user, $comment);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return true;
    } else {
        return false;
    }
}

function updateStatus($id, $statusId, $username, $userId = null)
{
    global $mysql;
    $query = "UPDATE `reports` SET `status` = ? WHERE `revertid` = ?";
    if ($stmt = mysqli_prepare($mysql, $query)) {
        mysqli_stmt_bind_param($stmt, "ss", $statusId, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        return false;
    }

    createComment(
        $id,
        'System',
        $username . ' has marked this report as "' . statusIdToName($statusId) . '".',
        true
    );

    // Track which user performed this in a side table
    if ($statusId == 2 && $userId) {
        recordUserSendingToReview($id, $userId);
    }

    return true;
}

function recordUserSendingToReview($id, $userId)
{
    global $mysql;
    $query = "INSERT IGNORE INTO `edits_sent_for_review` (`revertid`, `userid`) VALUES (?, ?)";
    if ($stmt = mysqli_prepare($mysql, $query)) {
        mysqli_stmt_bind_param($stmt, "ss", $id, $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function getReport($id)
{
    global $mysql;

    $query = "SELECT `revertid`, UNIX_TIMESTAMP(`timestamp`) AS `time`, `reporterid`, `reporter`, `status`
              FROM `reports`
              WHERE `revertid` = ?";
    if ($stmt = mysqli_prepare($mysql, $query)) {
        mysqli_stmt_bind_param($stmt, "s", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 0) {
            mysqli_stmt_close($stmt);
            return null;
        }
        $reportData = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    } else {
        return null;
    }

    $data = array(
        'id' => $reportData['revertid'],
        'timestamp' => $reportData['time'],
        'anonymous' => ($reportData['reporterid'] == -1),
        'username' => $reportData['reporter'],
        'status' => statusIdToName($reportData['status']),
        'comments' => array()
    );

    // Second: Get the comments for this report
    $queryComments = "SELECT `commentid`, UNIX_TIMESTAMP(`timestamp`) AS `time`, `userid`, `user`, `comment`
                      FROM `comments`
                      WHERE `revertid` = ?";
    if ($stmtComments = mysqli_prepare($mysql, $queryComments)) {
        mysqli_stmt_bind_param($stmtComments, "s", $id);
        mysqli_stmt_execute($stmtComments);
        $resultComments = mysqli_stmt_get_result($stmtComments);
        while ($row = mysqli_fetch_assoc($resultComments)) {
            $data['comments'][] = array(
                'id' => $row['commentid'],
                'timestamp' => $row['time'],
                'userid' => $row['userid'],
                'anonymous' => ($row['userid'] == -1),
                'username' => $row['user'],
                'comment' => $row['comment']
            );
        }
        mysqli_stmt_close($stmtComments);
    }

    $queryUser = "SELECT `admin`, `superadmin` FROM `users` WHERE `userid` = ?";
    if ($stmtUser = mysqli_prepare($mysql, $queryUser)) {
        foreach ($data['comments'] as &$comment) {
            if ($comment['userid'] != -1) {
                mysqli_stmt_bind_param($stmtUser, "i", $comment['userid']);
                mysqli_stmt_execute($stmtUser);
                $resultUser = mysqli_stmt_get_result($stmtUser);
                $row = mysqli_fetch_assoc($resultUser);
                $comment['admin'] = ($row && $row['admin'] == 1);
                $comment['sadmin'] = ($row && $row['superadmin'] == 1);
            } else {
                $comment['admin'] = false;
                $comment['sadmin'] = false;
            }
        }
        mysqli_stmt_close($stmtUser);
    }
    return $data;
}


function isAdmin()
{
    if (isSAdmin()) {
        return true;
    }
    if (!isset($_SESSION['admin'])) {
        return false;
    }
    if ($_SESSION['admin'] === true) {
        return true;
    }
    return false;
}

function isSAdmin()
{
    if (!isset($_SESSION['sadmin'])) {
        return false;
    }
    if ($_SESSION['sadmin'] === true) {
        return true;
    }
    return false;
}
