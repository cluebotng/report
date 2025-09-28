<?PHP

namespace ReportInterface;

class OptionsPage extends Page
{
    public function __construct()
    {
        global $mysql;
        if (isset($_POST['submit'])) {
            if (isset($_POST['next_on_review']) && $_POST['next_on_review'] === "Yes") {
                $next_on_review = 1;
            } else {
                $next_on_review = 0;
            }

            if (isset($_POST['keyboard_shortcuts']) && $_POST['keyboard_shortcuts'] === "Yes") {
                $keyboard_shortcuts = 1;
            } else {
                $keyboard_shortcuts = 0;
            }

            if (isset($_POST['hide_anon']) && $_POST['hide_anon'] === "Yes") {
                $hide_anon = 1;
            } else {
                $hide_anon = 0;
            }

            $_SESSION['next_on_review'] = (bool)$next_on_review;
            $_SESSION['keyboard_shortcuts'] = (bool)$keyboard_shortcuts;
            $_SESSION['hide_anon'] = (bool)$hide_anon;

            $query = "UPDATE `users` SET";
            $query .= " `next_on_review` = '" . mysqli_real_escape_string($mysql, $next_on_review) . "',";
            $query .= " `keyboard_shortcuts` = '" . mysqli_real_escape_string($mysql, $keyboard_shortcuts) . "',";
            $query .= " `hide_anon` = '" . mysqli_real_escape_string($mysql, $hide_anon) . "'";
            $query .= " WHERE `userid` = '" . mysqli_real_escape_string($mysql, $_SESSION['userid']) . "'";
            mysqli_query($mysql, $query);

            header('Location: ?page=Options&done');
            die();
        }
    }

    public function writeHeader()
    {
        echo 'Options';
    }

    public function writeContent()
    {
        if (isset($_GET['done'])) {
            echo '<p>Saved!</p>';
        }
        echo '<form action="" method="post">';

        echo '<h3>General options</h3>';
        echo '<p>Redirect on review: <input type="checkbox" id="next_on_review" name="next_on_review" value="Yes"';
        echo ($_SESSION['next_on_review']) ? ' checked=checked' : '';
        echo ' /></p>';

        echo '<p>Review keyboard shortcuts: ';
        echo '<input type="checkbox" id="keyboard_shortcuts" name="keyboard_shortcuts" value="Yes"';
        echo ($_SESSION['keyboard_shortcuts']) ? ' checked=checked' : '';
        echo ' /></p>';

        echo '<p>Hide Anon: <input type="checkbox" id="hide_anon" name="hide_anon" value="Yes"';
        echo ($_SESSION['hide_anon']) ? ' checked=checked' : '';
        echo ' /></p>';

        echo '<p><input id="submit" name="submit" type="submit" value="Save" /></p>';
        echo '</form>';
    }
}

if (isset($_SESSION['username'])) {
    Page::registerPage('Options', 'OptionsPage', 5, true, false);
}
