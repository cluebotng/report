<?PHP

namespace ReportInterface;

class ListPage extends Page
{
    private $ids;
    private $current_page;

    public function __construct()
    {
        $entries_per_page = 50;
        $this->current_page = isset($_REQUEST['idx']) ? (int)$_REQUEST['idx'] : 0;

        global $mysql;
        if (!isset($_REQUEST['showall'])) {
            $where = ' WHERE `status` IN (0,2,3,5,6)';
            if ((isset($_SESSION['hide_anon'])) && ($_SESSION['hide_anon'])) {
                $where .= ' AND `reporter` NOT LIKE "%Anonymous%"';
            }
        } else {
            $where = '';
        }

        $limit = 'LIMIT ' . mysqli_real_escape_string($mysql, $this->current_page * $entries_per_page) . ',' . mysqli_real_escape_string($mysql, $entries_per_page);
        $result = mysqli_query($mysql, 'SELECT `revertid`, `reporter`, `status` FROM `reports`' . $where . ' ORDER BY `status`, `revertid` ASC ' . $limit);
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
        if ((isset($_SESSION['hide_anon'])) && ($_SESSION['hide_anon'])) {
            echo ' (Anonymous hidden)';
        }
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
        echo '<br />';
        echo '<p>';
        if ($this->current_page > 0) {
            $previous_page = $this->current_page - 1;
            if ($previous_page > 0) {
                echo '<a href="?page=List&idx=' . urlencode($previous_page) . '">&laquo; Previous Page</a>&nbsp;';
            } else {
                echo '<a href="?page=List">&laquo; Previous Page</a>&nbsp;';
            }
        }
        echo '<a href="?page=List&idx=' . urlencode($this->current_page + 1) . '">Next Page &raquo;</a>';
        echo '</p>';
    }
}

Page::registerPage('List', 'ListPage', 1);
