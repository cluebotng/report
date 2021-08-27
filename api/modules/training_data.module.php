<?php

namespace ReportApi;

/*
 * Training data export
 * - Returns extended edit data for a specific change
 */
class ApiModuleTrainingData extends ApiModule
{
    /*
     * Helper functions
     */
    private function namespaceNameFromId($ns_id)
    {
        $namespaces = array(
            -1 => 'special',
            -2 => 'media',
            0 => 'main',
            1 => 'talk',
            2 => 'user',
            3 => 'user talk',
            4 => 'wikipedia',
            5 => 'wikipedia talk',
            6 => 'file',
            7 => 'file talk',
            8 => 'mediawiki',
            9 => 'mediawiki talk',
            10 => 'template',
            11 => 'template talk',
            12 => 'help',
            13 => 'help talk',
            14 => 'category',
            15 => 'category talk',
            100 => 'portal',
            101 => 'portal talk',
            108 => 'book',
            109 => 'book talk',
            118 => 'draft',
            119 => 'draft talk',
            710 => 'timedtext',
            711 => 'timedtext talk',
            828 => 'module',
            829 => 'module talk',
            2300 => 'gadget',
            2301 => 'gadget talk',
            2302 => 'gadget definition',
            2303 => 'gadget definition talk',
        );
        if (isset($namespaces[$ns_id])) {
            return $namespaces[$ns_id];
        }
    }

    // Note: This is not time bound to history
    // Apparently it's far too slow to query the revision index with that limit
    private function getUserEditCounts($username)
    {
        /* Anonymous user count */
        if (
            filter_var($username, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ||
            filter_var($username, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
        ) {
            $user_edit_count_result = mysqli_query(
                $this->mw_mysql,
                'SELECT COUNT(*) AS `user_editcount` FROM `revision_userindex` ' .
                ' JOIN `actor` ON `actor_id` = `rev_actor`' .
                ' WHERE `actor_name` = "' .
                mysqli_real_escape_string($this->mw_mysql, $username) .
                '"'
            );
            if ($user_edit_count_row = mysqli_fetch_assoc($user_edit_count_result)) {
                $user_edit_count = (int)$user_edit_count_row['user_editcount'];
                mysqli_free_result($user_edit_count_result);
                return array(true, $user_edit_count);
            } else {
                return array(false, json_encode(array(
                    "error" => "db_error",
                    "error_message" => "Failed to calculate user_edit_count.",
                )));
            }
        }

        /* Registered user count */
        $user_edit_count_result = mysqli_query(
            $this->mw_mysql,
            'SELECT `user_editcount` FROM `user` WHERE `user_name` =  "' .
            mysqli_real_escape_string($this->mw_mysql, $username) .
            '"'
        );
        if ($user_edit_count_row = mysqli_fetch_assoc($user_edit_count_result)) {
            $user_edit_count = (int)$user_edit_count_row['user_editcount'];
            mysqli_free_result($user_edit_count_result);
            return array(true, $user_edit_count);
        } else {
            return array(false, json_encode(array(
                "error" => "db_error",
                "error_message" => "Failed to calculate user_edit_count.",
            )));
        }
    }

    private function getUserRegistrationTime($username, $default_registration_time)
    {
        /* Anonymous user registration */
        if (
            filter_var($username, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ||
            filter_var($username, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
        ) {
            return $default_registration_time;
        }

        // Hopefully there is a record in the user table
        $user_registration_result = mysqli_query(
            $this->mw_mysql,
            'SELECT `user_registration` FROM `user` WHERE `user_name` = "' .
            mysqli_real_escape_string($this->mw_mysql, $username) .
            '"'
        );
        if ($user_registration_row = mysqli_fetch_assoc($user_registration_result)) {
            $registration_time = $user_registration_row['user_registration'];
            mysqli_free_result($user_registration_result);

            return gmmktime(
                (int)substr($registration_time, 8, 2),
                (int)substr($registration_time, 10, 2),
                (int)substr($registration_time, 12, 2),
                (int)substr($registration_time, 4, 2),
                (int)substr($registration_time, 6, 2),
                (int)substr($registration_time, 0, 4)
            );
        }
        mysqli_free_result($user_registration_result);

        // If the user got deleted or something then look for the first edit from the user
        $user_registration_result = mysqli_query(
            $this->mw_mysql,
            'SELECT `rev_timestamp` FROM `revision_userindex` ' .
            ' JOIN `actor` ON `actor_id` = `rev_actor`' .
            ' WHERE `actor_name` = "' .
            mysqli_real_escape_string($this->mw_mysql, $username) .
            '" ORDER BY `rev_timestamp` LIMIT 1'
        );
        if ($user_registration_row = mysqli_fetch_assoc($user_registration_result)) {
            $registration_time = $user_registration_row['rev_timestamp'];
            mysqli_free_result($user_registration_result);

            return gmmktime(
                (int)substr($registration_time, 8, 2),
                (int)substr($registration_time, 10, 2),
                (int)substr($registration_time, 12, 2),
                (int)substr($registration_time, 4, 2),
                (int)substr($registration_time, 6, 2),
                (int)substr($registration_time, 0, 4)
            );
        }
    }

    // Note: This is not time bound to history
    // Apparently it's far too slow to query the revision index with that limit
    private function getUserData($username, $base_revision_time)
    {
        /* User edit count */
        $start_time = time();

        /* User warnings count */
        $user_warning_count_result = mysqli_query(
            $this->mw_mysql,
            'SELECT COUNT(*) as count FROM `page`' .
            ' JOIN `revision` ON `rev_page` = `page_id`' .
            ' JOIN `comment` ON `rev_comment_id` = `comment_id`' .
            " WHERE `page_namespace` = 3 AND `page_title` = '" .
            mysqli_real_escape_string($this->mw_mysql, str_replace(' ', '_', $username)) .
            "' AND (`comment_text` LIKE '%warning%' OR `comment_text`" .
            " LIKE 'General note: Nonconstructive%')"
        );
        if ($user_warning_count_row = mysqli_fetch_assoc($user_warning_count_result)) {
            $user_warning_count = (int)$user_warning_count_row['count'];
            mysqli_free_result($user_warning_count_result);
        } else {
            return array(false, json_encode(array(
                "error" => "db_error",
                "error_message" => "Failed to calculate user_warns.",
            )));
        }

        /* User distinct pages count */
        $user_distinct_count_result = mysqli_query(
            $this->mw_mysql,
            'SELECT count(distinct rev_page) AS count FROM' .
            ' `revision_userindex` JOIN `actor` ON `actor_id` = `rev_actor`' .
            ' WHERE `actor_name` = "' .
            mysqli_real_escape_string($this->mw_mysql, str_replace(' ', '_', $username)) .
            '"'
        );
        if ($user_distinct_count_row = mysqli_fetch_assoc($user_distinct_count_result)) {
            $user_distinct_pages_count = (int)$user_distinct_count_row['count'];
            mysqli_free_result($user_distinct_count_result);
        } else {
            return array(false, json_encode(array(
                "error" => "db_error",
                "error_message" => "Failed to calculate user_distinct_pages.",
            )));
        }

        $user_registration_time = $this->getUserRegistrationTime($username, $base_revision_time);
        if (!$user_registration_time) {
            return array(false, json_encode(array(
                "error" => "db_error",
                "error_message" => "Failed to calculate user_reg_time.",
            )));
        }

        $edit_count = $this->getUserEditCounts($username);
        if (!$edit_count[0]) {
            return array(false, $edit_count[1]);
        }

        return array(true, array(
            'name' => $username,
            'registration_time' => $user_registration_time,
            'warning_count' => $user_warning_count,
            'distinct_pages_count' => $user_distinct_pages_count,
            'edit_count' => $edit_count[1],
        ));
    }

    public function header()
    {
        global $mw_mysql_host, $mw_mysql_user, $mw_mysql_pass, $mw_mysql_schema, $mw_mysql_port;
        header('Content-Type: application/json');

        /*
         * Try and connect to mediawiki mysql - we need this for the edit data
         */
        $this->mw_mysql = @mysqli_connect($mw_mysql_host, $mw_mysql_user, $mw_mysql_pass, $mw_mysql_schema, $mw_mysql_port);
        if (!$this->mw_mysql) {
            die(json_encode(array(
                'error' => 'db_error',
                'error_message' => 'Could not connect to the wiki database server',
            ), JSON_PRETTY_PRINT));
        }
    }

    public function footer()
    {
        @mysqli_close($this->mw_mysql);
    }

    public function content()
    {
        /*
         * Ensure we have a target id
         */
        if (!isset($_REQUEST['rev_id']) || empty($_REQUEST['rev_id']) || (int)$_REQUEST['rev_id'] === 0) {
            return json_encode(array(
                "error" => "argument_error",
                "error_message" => "Specified rev_id was in an invalid format",
            ));
        }

        /*
         * Get the specified revision data & the parent (i.e. previous) revision data
         */
        $revision_result = mysqli_query(
            $this->mw_mysql,
            'SELECT * FROM `page`' .
            ' JOIN `revision` ON `rev_page` = `page_id`' .
            ' JOIN `comment` ON `comment_id` = `rev_comment_id`' .
            ' JOIN `actor` ON `actor_id` = `rev_actor`' .
            ' WHERE `rev_id` = "' .
            mysqli_real_escape_string($this->mw_mysql, (int)$_REQUEST['rev_id']) .
            '"'
        );
        if ($revision_row = mysqli_fetch_assoc($revision_result)) {
            mysqli_free_result($revision_result);
        } else {
            return json_encode(array(
                "error" => "argument_error",
                "error_message" => "The specified rev_id was not found.",
            ));
        }

        $previous_revision_result = mysqli_query(
            $this->mw_mysql,
            'SELECT * FROM `page`' .
            ' JOIN `revision` ON `rev_page` = `page_id`' .
            ' JOIN `comment` ON `comment_id` = `rev_comment_id`' .
            ' JOIN `actor` ON `actor_id` = `rev_actor`' .
            ' WHERE `rev_id` = "' .
            mysqli_real_escape_string($this->mw_mysql, $revision_row['rev_parent_id']) .
            '"'
        );
        if ($previous_revision_row = mysqli_fetch_assoc($previous_revision_result)) {
            mysqli_free_result($previous_revision_result);
        } else {
            return json_encode(array(
                "error" => "argument_error",
                "error_message" => "Failed to find previous revision.",
            ));
        }

        $revision_user_data = $this->getUserData($revision_row['actor_name'], $revision_row['rev_timestamp']);
        if (!$revision_user_data[0]) {
            return $revision_user_data[1];
        }
        $revision_user = $revision_user_data[1];

        $data = array(
            'current' => array(
                'id' => (int)$revision_row['rev_id'],
                'minor' => (bool)$revision_row['rev_minor_edit'],
                'timestamp' => gmmktime(
                    (int)substr($revision_row['rev_timestamp'], 8, 2),
                    (int)substr($revision_row['rev_timestamp'], 10, 2),
                    (int)substr($revision_row['rev_timestamp'], 12, 2),
                    (int)substr($revision_row['rev_timestamp'], 4, 2),
                    (int)substr($revision_row['rev_timestamp'], 6, 2),
                    (int)substr($revision_row['rev_timestamp'], 0, 4)
                ),
                'comment' => $revision_row['comment_text'],
                'user' => $revision_user,
            ),
            'previous' => array(
                'id' => (int)$previous_revision_row['rev_id'],
                'minor' => (bool)$previous_revision_row['rev_minor_edit'],
                'timestamp' => gmmktime(
                    (int)substr($previous_revision_row['rev_timestamp'], 8, 2),
                    (int)substr($previous_revision_row['rev_timestamp'], 10, 2),
                    (int)substr($previous_revision_row['rev_timestamp'], 12, 2),
                    (int)substr($previous_revision_row['rev_timestamp'], 4, 2),
                    (int)substr($previous_revision_row['rev_timestamp'], 6, 2),
                    (int)substr($previous_revision_row['rev_timestamp'], 0, 4)
                ),
                'comment' => $previous_revision_row['comment_text'],
                'user' => array(
                    'name' => $previous_revision_row['actor_name'],
                )
            ),
        );

        /*
         * The following logic is similar to https://github.com/cluebotng/bot/blob/main/mysql_functions.php
         * However it differs in using an explict diff id & time spans
         */

        /* Get first page revision */
        $page_result = mysqli_query(
            $this->mw_mysql,
            'SELECT * FROM `page`' .
            ' JOIN `revision` ON `rev_page` = `page_id`' .
            ' JOIN `actor` ON `actor_id` = `rev_actor`' .
            ' WHERE `page_id` = "' .
            mysqli_real_escape_string($this->mw_mysql, $revision_row['page_id']) . '"' .
            ' ORDER BY `rev_id` ASC LIMIT 1'
        );
        if ($page_row = mysqli_fetch_assoc($page_result)) {
            mysqli_free_result($page_result);
        } else {
            return json_encode(array(
                "error" => "argument_error",
                "error_message" => "The specified rev_id was not found.",
            ));
        }

        $data['page'] = array(
            'id' => $page_row['page_id'],
            'title' => $page_row['page_title'],
            'namespace' => $this->namespaceNameFromId($page_row['page_namespace']),
            'namespace_id' => $page_row['page_namespace'],
            'creator' => $page_row['actor_name'],
            'creation_time' => gmmktime(
                (int)substr($page_row['rev_timestamp'], 8, 2),
                (int)substr($page_row['rev_timestamp'], 10, 2),
                (int)substr($page_row['rev_timestamp'], 12, 2),
                (int)substr($page_row['rev_timestamp'], 4, 2),
                (int)substr($page_row['rev_timestamp'], 6, 2),
                (int)substr($page_row['rev_timestamp'], 0, 4)
            )
        );

        /* Recent page edits */
        $recent_edit_results = mysqli_query(
            $this->mw_mysql,
            'SELECT COUNT(*) as count FROM `page`' .
            ' JOIN `revision` ON `rev_page` = `page_id`' .
            ' JOIN `actor` ON `actor_id` = `rev_actor`' .
            ' WHERE `page_namespace` = "' .
            mysqli_real_escape_string($this->mw_mysql, $page_row['page_namespace']) .
            '" AND `page_title` = "' .
            mysqli_real_escape_string($this->mw_mysql, $page_row['page_title']) .
            '" AND `rev_timestamp` > "' .
            mysqli_real_escape_string($this->mw_mysql, $revision_row['rev_timestamp'] - 60) .
            '" AND `rev_timestamp` < "' .
            mysqli_real_escape_string($this->mw_mysql, $revision_row['rev_timestamp']) .
            '"'
        );
        if ($recent_edit_row = mysqli_fetch_assoc($recent_edit_results)) {
            $data['page']['recent_edit_count'] = (int)$recent_edit_row['count'];
            mysqli_free_result($recent_edit_results);
        } else {
            return json_encode(array(
                "error" => "db_error",
                "error_message" => "Failed to calculate num_recent_edits.",
            ));
        }

        /* Recent page reverts */
        $recent_reversions_results = mysqli_query(
            $this->mw_mysql,
            'SELECT COUNT(*) as count FROM `page`' .
            ' JOIN `revision` ON `rev_page` = `page_id`' .
            ' JOIN `comment` ON `rev_comment_id` = `comment_id`' .
            ' WHERE `page_namespace` = "' .
            mysqli_real_escape_string($this->mw_mysql, $revision_row['page_namespace']) .
            '" AND `page_title` = "' .
            mysqli_real_escape_string($this->mw_mysql, $revision_row['page_title']) .
            '" AND `rev_timestamp` > "' .
            mysqli_real_escape_string($this->mw_mysql, $revision_row['rev_timestamp'] - 60) .
            '" AND `rev_timestamp` < "' .
            mysqli_real_escape_string($this->mw_mysql, $revision_row['rev_timestamp']) .
            '" AND `comment_text` LIKE "Revert%"'
        );
        if ($recent_reversions_row = mysqli_fetch_assoc($recent_reversions_results)) {
            $data['page']['recent_reversion_count'] = (int)$recent_reversions_row['count'];
            mysqli_free_result($recent_reversions_results);
        } else {
            return json_encode(array(
                "error" => "db_error",
                "error_message" => "Failed to calculate num_recent_reversions.",
            ));
        }

        return json_encode($data, JSON_PRETTY_PRINT);
    }
}

ApiModule::register('training.data', 'ApiModuleTrainingData');
