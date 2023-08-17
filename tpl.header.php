<?php

header('Content-type: text/html; charset=utf-8');

?>
<!doctype html>
<html>

<head>
	<meta charset="utf-8" />
	<meta name="theme-color" content="#333" />
	<meta name="referrer" content="no-referrer" />
	<title>Bookr</title>
	<link rel="icon" type="image/png" href="/favicon-128.png" sizes="128x128" />
	<link rel="icon" href="/favicon.ico" type="image/x-icon" />
	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="stylesheet" type="text/css" href="<?= html_asset('style.css') ?>" />
</head>

<body>

<? if ($g_user): ?>
	<p class="menu">
		<a href="index.php">Library</a> |
		<a href="form.php">Add</a> |
		<a href="authors.php">Authors</a> |
		<a href="import.php">Import</a> |
		<a href="settings.php">Settings</a> |
		<a href="logout.php">Log out</a>
	</p>
<? endif ?>

<? if ($msg = get_message()): ?>
	<div class="message <?= $msg['type'] ?>" onclick="this.remove()"><?= nl2br(html(trim($msg['text']))) ?></div>
<? endif ?>
