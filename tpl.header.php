<?php

header('Content-type: text/html; charset=utf-8');

?>
<!doctype html>
<html>

<head>
	<meta charset="utf-8" />
	<title>Bookr</title>
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="stylesheet" type="text/css" href="<?= html_asset('style.css') ?>" />
</head>

<body>

<p class="menu">
	<a href="index.php">Library</a> |
	<a href="form.php">Add</a> |
	<a href="authors.php">Authors</a> |
	<a href="import.php">Import</a> |
	<a href="settings.php">Settings</a>
</p>

<? if ($msg = get_message()): ?>
	<div class="message" onclick="this.remove()"><?= nl2br(html(trim($msg))) ?></div>
<? endif ?>
