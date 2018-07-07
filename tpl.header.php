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

<? if (@$_GET['msg']): ?>
	<? $msgs = (array)$_GET['msg'] ?>
	<ul class="messages"><li><?= implode('</li><li>', array_map('html', $msgs)) ?></li></ul>
<? endif ?>
