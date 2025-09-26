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
            $user = $_POST['user'];
            createReport($id, $user);
            if (trim($_POST['comment']) != '') {
                createComment($id, $user, $_POST['comment']);
            }
        }

        $result = mysqli_query($mysql, 'SELECT * FROM `vandalism` WHERE `id` = \'' . mysqli_real_escape_string($mysql, $_REQUEST['id']) . '\'');
        $this->row = mysqli_fetch_assoc($result);

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
        echo file_get_contents('https://en.wikipedia.org/w/index.php?diffonly=1&action=render&diff=' . urlencode($this->row['new_id']), false, $context);

        echo '</td></tr>';
        echo '<tr><th>Reason:</th><td>' . $this->row['reason'] . '</td></tr>';
        $user = 'Anonymous';
        if (isset($_SESSION['username'])) {
            $user = $_SESSION['username'];
        }
        echo '<tr><th>Your username:</th><td><input type="text" name="user" value="' . $user . '"></td></tr>';
        echo '<tr><th>Reverted:</th><td>' . (($this->row['reverted'] == 1) ? 'Yes' : '<b><u><font color="red">No</font></u></b>') . '</td></tr>';
        if ($this->row['reverted'] == 1) {
            echo '<tr><th>Comment<br />(optional):</th><td><textarea name="comment" cols=80 rows=3></textarea><br /><small><em>Note</em>: Comments are completely optional.  You do not have to justify your edit.<br />If this is a false positive, then you\'re right, and the bot is wrong - you don\'t need to explain why.</small></td></tr>';
            echo '<tr><td colspan=2><input type="submit" name="submit" value="Report false positive" /></td></tr>';
        }
        echo '</table>';

        echo '</form>';
    }
}

Page::registerPage('Report', 'ReportPage', 0, false);
