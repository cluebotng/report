<?php

    static $cb_mysql_host = 'tools-db';
    static $cb_mysql_port = 3306;
    static $cb_mysql_user = getenv('TOOL_TOOLSDB_USER');
    static $cb_mysql_pass = getenv('TOOL_TOOLSDB_PASSWORD');
    static $cb_mysql_schema = getenv('TOOL_TOOLSDB_SCHEMA');

    static $oauth_consumer_key = getenv('OAUTH_KEY');
    static $oauth_consumer_secret = getenv('OAUTH_SECRET');

