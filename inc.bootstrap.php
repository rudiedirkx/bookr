<?php

use rdx\bookr\User;
use rdx\bookr\search\Provider;

require 'vendor/autoload.php';
require 'env.php';

const FORMAT_DATETIME = "j M 'y H:i";
const FORMAT_DATE = "j M 'y";

header('Content-type: text/plain; charset=utf-8');

// db connection
$db = db_sqlite::open(array('database' => __DIR__ . '/db/bookr.sqlite3'));
if ( !$db ) {
	exit('No database connecto...');
}

db_generic_model::$_db = $db;

$db->ensureSchema(require 'inc.db-schema.php');

$g_user = User::fromAuth(@$_SERVER['PHP_AUTH_USER'], @$_SERVER['PHP_AUTH_PW']);
if ( !$g_user ) {
	do_auth();
}

/** @var Provider */
$g_searchers = $g_searchers ?? [];
