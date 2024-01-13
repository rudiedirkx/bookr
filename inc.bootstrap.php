<?php

use rdx\bookr\Model;
use rdx\bookr\User;
use rdx\bookr\search\Provider;

require 'vendor/autoload.php';
require 'env.php';

const FORMAT_DATETIME = "j M 'y H:i";
const FORMAT_DATE = "j M 'y";

// db connection
$db = db_sqlite::open(array('database' => DB_FILE));
if ( !$db ) {
	exit('No database connecto...');
}

Model::$_db = $db;

$db->ensureSchema(require 'inc.db-schema.php');

session_name('bookrsession');
session_start();

$g_user = User::fromSession($_SESSION['login']['id'] ?? 0);
if ( !$g_user && !in_array(basename($_SERVER['SCRIPT_NAME']), ['login.php', 'logout.php']) ) {
	if ( basename($_SERVER['SCRIPT_NAME']) != 'index.php' ) {
		set_message("You must log in.", 'error');
	}
	do_redirect('login');
	exit;
}

/** @var Provider[] */
$g_searchers = array_map(function(array $args) {
	return new $args[0](...array_slice($args, 1));
}, BOOKR_SEARCHERS);

header('Access-Control-Expose-Headers: Location');
header('Content-type: text/plain; charset=utf-8');
