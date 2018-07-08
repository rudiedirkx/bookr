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
	<script src="https://rawgit.com/turbolinks/turbolinks/master/dist/turbolinks.js"></script>
</head>

<body>

<p class="menu">
	<a href="index.php">Library</a> |
	<a href="form.php">Add</a> |
	<a href="authors.php">Authors</a> |
	<a href="import.php">Import</a> |
	<a href="settings.php">Settings</a>
</p>

<? if (!empty($_GET['msg'])): ?>
	<div class="message" onclick="this.remove()"><?= html($_GET['msg']) ?></div>
<? endif ?>
