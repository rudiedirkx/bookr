<doctype html>
<html>

<head>
	<meta charset="utf-8" />
	<title>Bookr</title>
</head>

<body>

<p class="menu">
	<a href="index.php">Your books</a> |
	<a href="form.php">Add books</a> |
	<a href="import.php">Import books</a>
</p>

<? if (@$_GET['msg']): ?>
	<? $msgs = (array)$_GET['msg'] ?>
	<ul class="messages"><li><?= implode('</li><li>', array_map('html', $msgs)) ?></li></ul>
<? endif ?>
