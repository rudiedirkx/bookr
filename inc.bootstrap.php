<?php

require 'env.php';
require 'inc.functions.php';

require WHERE_DB_GENERIC_AT . '/db_sqlite.php';

define('FORMAT_DATETIME', "j M 'y H:i");
define('FORMAT_DATE', "j M 'y");

// db connection
$db = db_sqlite::open(array('database' => __DIR__ . '/db/bookr.sqlite3'));
if ( !$db ) {
	exit('No database connecto...');
}

// db schema
$schema = require 'inc.db-schema.php';
require 'inc.ensure-db-schema.php';

// classes
require 'inc.book.php';
