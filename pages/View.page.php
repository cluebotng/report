<?PHP
    class ViewPage extends Page
    {
        private $row;
        private $id;
        private $data;
        
        public function __construct()
        {
            global $recaptcha_privkey;
            $this->id = $_REQUEST[ 'id' ];
            $result = mysql_query('SELECT * FROM `vandalism` WHERE `id` = \'' . mysql_real_escape_string($this->id) . '\'');
            $this->row = mysql_fetch_assoc($result);
            $this->data = getReport($this->id);
            if ($this->data === null) {
                header('Location: ?page=Report&id=' . $this->id);
                die();
            }
            
            if (isset($_POST[ 'submit' ])) {
                if (trim($_POST[ 'comment' ]) != '') {
                    $this->bad_captca = false;
                    if (!isset($_SESSION[ 'username' ])) {
                        $resp = recaptcha_check_answer($recaptcha_privkey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
                        if (!$resp->is_valid) {
                            $this->bad_captca = true;
                        }
                    }
                    
                    $this->bad_comment = false;
                    if (strpos($_POST[ 'comment' ], '<a href') !== false) {
                        $this->bad_comment = true;
                    }

                    if ($this->bad_captca === false && $this->bad_comment === false) {
                        createComment($this->id, $_POST[ 'user' ], $_POST[ 'comment' ]);
                        header('Location: ?page=View&id=' . $this->id);
                        die();
                    }
                }
            }
                
            if (isset($_REQUEST[ 'status' ]) and isAdmin()) {
                updateStatus($this->id, $_REQUEST[ 'status' ], $_SESSION[ 'username' ]);

                if (isset($_SESSION[ 'next_on_review' ]) && $_SESSION[ 'next_on_review' ] === true) {
                    $result = mysql_query("SELECT * FROM `reports` WHERE `status` = 0 ORDER BY RAND() LIMIT 0, 1");
                    if (is_resource($result) && mysql_num_rows($result) > 0) {
                        $row = mysql_fetch_assoc($result);
                        header('Location: ?page=View&id=' . $row['revertid']);
                        die();
                    }
                }

                header('Location: ?page=View&id=' . $this->id);
                die();
            }
            if (isset($_REQUEST[ 'deletecomment' ]) and isSAdmin()) {
                mysql_query('DELETE FROM `comments` WHERE `commentid` = \'' . mysql_real_escape_string($_REQUEST[ 'deletecomment' ]) . '\'');
                header('Location: ?page=View&id=' . $this->id);
                die();
            }
        }
        
        public function writeHeader()
        {
            echo 'Viewing ' . $this->id;
        }
        
        public function writeContent()
        {
            global $recaptcha_pubkey;
            require 'pages/View.tpl.php';
        }
    }
    Page::registerPage('View', 'ViewPage', 0, false);
