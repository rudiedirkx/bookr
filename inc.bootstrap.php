<?php

use rdx\bookr\User;

require 'env.php';
require 'vendor/autoload.php';

define('FORMAT_DATETIME', "j M 'y H:i");
define('FORMAT_DATE', "j M 'y");

// db connection
$db = db_sqlite::open(array('database' => __DIR__ . '/db/bookr.sqlite3'));
if ( !$db ) {
	exit('No database connecto...');
}

db_generic_model::$_db = $db;

$db->ensureSchema(require 'inc.db-schema.php');

if ( !isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) ) {
	do_auth();
}

$g_user = User::fromAuth($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
if ( !$g_user ) {
	do_auth();
}
