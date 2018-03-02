<?php

require 'env.php';
require 'vendor/autoload.php';

define('FORMAT_DATETIME', "j M 'y H:i");
define('FORMAT_DATE', "j M 'y");

// db connection
$db = db_sqlite::open(array('database' => __DIR__ . '/db/bookr.sqlite3'));
if ( !$db ) {
	exit('No database connecto...');
}

$db->ensureSchema(require 'inc.db-schema.php');
