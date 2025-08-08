<?php

    static $cb_mysql_host = 'tools-db';
    static $cb_mysql_port = 3306;
    $cb_mysql_user = getenv('TOOL_TOOLSDB_USER');
    $cb_mysql_pass = getenv('TOOL_TOOLSDB_PASSWORD');
    $cb_mysql_schema = getenv('TOOL_TOOLSDB_SCHEMA');

    $oauth_consumer_key = getenv('OAUTH_KEY');
    $oauth_consumer_secret = getenv('OAUTH_SECRET');
