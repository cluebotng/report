<?PHP

namespace ReportInterface;

class ListPage extends Page
{
    private $ids;

    public function __construct()
    {
        global $mysql;
        if (!isset($_REQUEST['showall'])) {
            $where = ' WHERE `status` IN (0,2,3,5,6)';
            if ((isset($_SESSION['hide_anon'])) && ($_SESSION['hide_anon'])) {
                $where .= ' AND `reporter` NOT LIKE "%Anonymous%"';
            }
        } else {
            $where = '';
        }
        $result = mysqli_query($mysql, 'SELECT `revertid`, `reporter`, `status` FROM `reports`' . $where . ' ORDER BY `status` ASC');
        $this->ids = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $this->ids[] = array(
                'id' => $row['revertid'],
                'user' => $row['reporter'],
                'status' => statusIdToName($row['status'])
            );
        }
    }

    public function writeHeader()
    {
        echo 'List';
    }

    public function writeContent()
    {
        echo '<table>';
        echo '<tr><th>ID</th><th>Reporter</th><th>Status</th></tr>';
        foreach ($this->ids as $entry) {
            echo '<tr>'
                . '<td><a href="?page=View&id=' . urlencode($entry['id']) . '">' . htmlentities($entry['id']) . '</a></td>'
                . '<td>' . htmlentities($entry['user']) . '</td>'
                . '<td>' . htmlentities($entry['status']) . '</td>'
                . '</tr>';
        }
        echo '</table>';
    }
}

Page::registerPage('List', 'ListPage', 1);
