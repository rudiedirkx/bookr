<!doctype html>
<html>

<head>
	<meta charset="utf-8" />
	<title>Bookr</title>
	<style>
	* { box-sizing: border-box; }
	html, body {
		margin: 0;
		padding: 0;
	}
	body {
		margin: 10px;
	}
	button.submit {
		font-weight: bold;
		color: green;
	}
	button.delete {
		color: #c00;
	}
	.messages {
		font-weight: bold;
		color: green;
	}
	</style>
</head>

<body>

<p class="menu">
	<a href="index.php">Your books</a> |
	<a href="export.php">Export books</a> |
	<a href="form.php">Add book</a> |
	<a href="authors.php">Authors</a> |
	<a href="import.php">Import books</a>
</p>

<? if (@$_GET['msg']): ?>
	<? $msgs = (array)$_GET['msg'] ?>
	<ul class="messages"><li><?= implode('</li><li>', array_map('html', $msgs)) ?></li></ul>
<? endif ?>
