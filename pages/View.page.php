<?PHP

namespace ReportInterface;

class ViewPage extends Page
{
    private $row;
    private $data;

    public function __construct()
    {
        global $mysql;

        if ((int)$_REQUEST['id']) {
            $result = mysqli_query($mysql, 'SELECT * FROM `vandalism` WHERE `id` = \'' . mysqli_real_escape_string($mysql, (int)$_REQUEST['id']) . '\'');
            if ($row = mysqli_fetch_assoc($result)) {
                $this->row = $row;
            }
        } elseif ((int)$_REQUEST['new_id']) {
            $result = mysqli_query($mysql, 'SELECT * FROM `vandalism` WHERE `new_id` = \'' . mysqli_real_escape_string($mysql, (int)$_REQUEST['new_id']) . '\'');
            if ($row = mysqli_fetch_assoc($result)) {
                $this->row = $row;
            }
        }

        if (!$this->row) {
            header('Location: ?page=List');
            die();
        }

        $this->data = getReport($this->row['id']);
        if ($this->data === null) {
            header('Location: ?page=Report&id=' . $this->row['id']);
            die();
        }

        if (isset($_POST['submit']) && isset($_SESSION['username'])) {
            if (trim($_POST['comment']) != '') {
                createComment($this->row['id'], $_SESSION['username'], $_POST['comment']);
                header('Location: ?page=View&id=' . $this->row['id']);
                die();
            }
        }

        if (isset($_REQUEST['status']) and isAdmin()) {
            updateStatus($this->row['id'], $_REQUEST['status'], $_SESSION['username'], $_SESSION['userid']);

            if (isset($_SESSION['next_on_review']) && $_SESSION['next_on_review'] === true) {
                if (isset($_SESSION['hide_anon']) && $_SESSION['hide_anon'] === true) {
                    $result = mysqli_query($mysql, "SELECT * FROM `reports` WHERE `status` = 0 AND `reporter` NOT LIKE '%Anonymous%' ORDER BY RAND() LIMIT 0, 1");
                } else {
                    $result = mysqli_query($mysql, "SELECT * FROM `reports` WHERE `status` = 0 ORDER BY RAND() LIMIT 0, 1");
                }
                if (mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_assoc($result);
                    header('Location: ?page=View&id=' . $row['revertid']);
                    die();
                }
            }

            header('Location: ?page=View&id=' . $this->row['id']);
            die();
        }
        if (isset($_REQUEST['deletecomment']) and isSAdmin()) {
            mysqli_query($mysql, 'DELETE FROM `comments` WHERE `commentid` = \'' . mysqli_real_escape_string($mysql, $_REQUEST['deletecomment']) . '\'');
            header('Location: ?page=View&id=' . $this->row['id']);
            die();
        }
    }

    public function writeHeader()
    {
        echo 'Viewing ' . htmlspecialchars($this->row['id']);
    }

    public function writeContent()
    {
        require 'pages/View.tpl.php';
    }
}

Page::registerPage('View', 'ViewPage', 0, false);
