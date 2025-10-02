<?PHP

namespace ReportInterface;

class UserAdminPage extends Page
{
    private $users;

    public function __construct()
    {
        global $mysql;
        if (!isSAdmin()) {
            die('*sigh*');
        }

        if (isset($_REQUEST['action'])) {
            $set = null;
            $uid = mysqli_real_escape_string($mysql, $_REQUEST['uid']);
            switch ($_REQUEST['action']) {
                case 'delete':
                    mysqli_query(
                        $mysql,
                        'UPDATE `comments` SET `userid` = -1 WHERE `userid` = \'' . $uid . '\''
                    );
                    mysqli_query(
                        $mysql,
                        'UPDATE `reports` SET `reporterid` = -1 WHERE `reporterid` = \'' . $uid . '\''
                    );
                    mysqli_query(
                        $mysql,
                        'DELETE FROM `users` WHERE `userid` = \'' . $uid . '\''
                    );
                    break;
                case 'superadmin':
                    $set = '`superadmin` = 1';
                    break;
                case 'admin':
                    $set = '`admin` = 1';
                    break;
                case 'deadmin':
                    $set = '`admin` = 0';
                    break;
            }

            if ($set !== null) {
                mysqli_query($mysql, 'UPDATE `users` SET ' . $set . ' WHERE `userid` = \'' . $uid . '\'');
            }

            header('Location: ?page=User+Admin');
            die();
        }

        $result = mysqli_query($mysql, 'SELECT `userid`, `username`, `admin`, `superadmin` FROM `users`');
        $this->users = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $this->users[] = array(
                'id' => $row['userid'],
                'user' => $row['username'],
                'admin' => $row['superadmin'] ? 'super' : ($row['admin'] ? 'yes' : 'no')
            );
        }
    }

    public function writeHeader()
    {
        echo 'User Admin';
    }

    public function writeContent()
    {
        echo '<table style="border: 1px solid black; border-collapse: collapse;">';
        echo '<tr><th>Actions</th><th>User ID</th><th>Username</th><th>Admin</th></tr>';
        foreach ($this->users as $user) {
            echo '<tr>';
            echo '<td>';
            if ($user['admin'] != 'super') {
                $uid = $user['id'];
                $uname = urlencode($user['user']);
                echo '<a href="?page=User+Admin&action=delete&uid=' . $uid . '&user=' . $uname . '">X</a> &middot; ';
                echo '<a href="?page=User+Admin&action=superadmin&uid=' . $uid . '&user='. $uname .'">++</a> &middot; ';
                echo '<a href="?page=User+Admin&action=admin&uid=' . $uid . '&user=' . $uname . '">+</a> &middot; ';
                echo '<a href="?page=User+Admin&action=deadmin&uid=' . $uid . '&user=' . $uname . '">-</a> ';
            } else {
                echo 'None';
            }
            echo '</td>';
            echo '<td>' . $user['id'] . '</td>';
            echo '<td>' . htmlentities($user['user']) . '</td>';
            echo '<td>' . htmlentities($user['admin']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}

Page::registerPage('User Admin', 'UserAdminPage', 5, true, true);
