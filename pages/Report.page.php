<?PHP

namespace ReportInterface;

class ReportPage extends Page
{
    private $row;

    public function __construct()
    {
        global $mysql;
        $id = $_REQUEST['id'];

        if (isset($_POST['submit'])) {
            createReport($id, 'Anonymous');
            if (trim($_POST['comment']) != '') {
                createComment($id, 'Anonymous', $_POST['comment']);
            }
        }

        $stmt = mysqli_prepare($mysql, 'SELECT * FROM `vandalism` WHERE `id` = ?');
        mysqli_stmt_bind_param($stmt, 's', $_REQUEST['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $this->row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        $report = getReport($id);
        if ($report !== null) {
            // If the edit user has elevated wiki rights, or is whitelisted by Huggle, then
            // treat them as 'semi-trusted' and send directly to the review interface.
            if (userHasWikiRights($this->row['user'])) {
                updateStatus($report['id'], 2, 'Report Interface', -2);
            }

            header('Location: ?page=View&id=' . $id);
            die();
        }
    }

    public function writeHeader()
    {
        echo 'Report';
    }

    public function writeContent()
    {
        echo '<form action="?page=Report" method="post">';

        echo '<table class="reporttable">';
        echo '<tr><th>ID:</th><td><input type="hidden" name="id" value="' . $this->row['id'] . '" />' . $this->row['id'] . '</td></tr>';
        echo '<tr><th>User:</th><td>' . $this->row['user'] . '</td></tr>';
        echo '<tr><th>Article:</th><td>' . $this->row['article'] . '</td></tr>';
        echo '<tr><th>Diff:</th><td style="border: 1px dashed #000000">';

        $context = stream_context_create(array('http' => array('user_agent' => 'ClueBot NG Report Interface')));
        echo file_get_contents(
            'https://en.wikipedia.org/w/index.php?diffonly=1&action=render&diff=' . urlencode($this->row['new_id']),
            false,
            $context
        );

        echo '</td></tr>';
        echo '<tr><th>Reason:</th><td>' . $this->row['reason'] . '</td></tr>';
        if (isset($_SESSION['username'])) {
            echo '<tr><th>Username:</th><td>' . htmlentities($_SESSION['username']) . '</td></tr>';
        } else {
            echo '<tr><th>Username:</th><td>Anonymous <i><a href="?page=Sign+In">sign in</a></i></td></tr>';
        }
        echo '<tr><th>Reverted:</th><td>' . (($this->row['reverted'] == 1) ? 'Yes' : '<b><u><span style="color:red">No</span></u></b>') . '</td></tr>';
        if ($this->row['reverted'] == 1) {
            echo '<tr><th>Comment<br />(optional):</th><td><textarea name="comment" cols=80 rows=3></textarea><br />';
            echo '<small><em>Note</em>: Comments are completely optional. You do not have to justify your edit.<br />';
            echo 'If this is a false positive, then you\'re right, and the bot is wrong - you don\'t need to explain why.';
            echo '</small></td></tr>';
            echo '<tr><td colspan=2><input type="submit" name="submit" value="Report false positive" /></td></tr>';
        }
        echo '</table>';

        echo '</form>';
    }
}

Page::registerPage('Report', 'ReportPage', 0, false);
