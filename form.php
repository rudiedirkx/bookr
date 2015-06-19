<?php

require 'inc.bootstrap.php';

$id = @$_GET['id'];
$book = $id ? $db->select('books', compact('id'), array(), 'Book')->first() : null;

if ( isset($_POST['title'], $_POST['author'], $_POST['read']) ) {
	$data = array(
		'title' => trim($_POST['title']),
		'author' => trim($_POST['author']),
		'summary' => trim($_POST['summary']),
		'notes' => trim($_POST['notes']),
	);

	$data['read'] = implode('-', array(
		str_pad((int)$_POST['read']['year'] ?: '0', 4, '0', STR_PAD_LEFT),
		str_pad((int)$_POST['read']['month'] ?: '0', 2, '0', STR_PAD_LEFT),
		'00',
	));

	if ( $id ) {
		$data['updated'] = time();
		$db->update('books', $data, compact('id'));
	}
	else {
		$data['created'] = time();
		$db->insert('books', $data);
		$id = $db->insert_id();
	}

	if ( !empty($_POST['another']) ) {
		do_redirect('form');
		exit;
	}

	do_redirect('index', array('hilited' => $id));
	exit;
}

include 'tpl.header.php';

$authors = $db->select_fields('books', 'author, author', '1 ORDER BY author');

$years = array_combine(range(date('Y'), 1990), range(date('Y'), 1990));
$months = array_combine(range(1, 12), array_map(function($m) {
	return date('F', mktime(1, 1, 1, $m, 1, 2000));
}, range(1, 12)));

?>
<h1>Add/edit book</h1>

<style>
label {
	display: inline-block;
	min-width: 7em;
}
input:not([type="checkbox"]) {
	width: 30em;
}
textarea {
	width: 37.25em;
}
</style>

<form action method="post">
	<p>
		<label for="txt-title">Title:</label>
		<input name="title" value="<?= html(@$book->title) ?>" required autofocus placeholder="The holy bible" />
	</p>
	<p>
		<label for="txt-author">Author:</label>
		<input name="author" value="<?= html(@$book->author) ?>" required placeholder="Jesus Christ" list="authors" />
	</p>
	<p>
		<label>Finished on:</label>
		<select name="read[year]"><?= html_options($years, @$book->read_year, '--') ?></select>
		<select name="read[month]"><?= html_options($months, @$book->read_month, '--') ?></select>
	</p>
	<p>
		<label for="txt-summary">Summary:</label><br />
		<textarea name="summary" rows="6" placeholder="Jesus is born, then he dies, then he undies, now we wait."><?= html(@$book->summary) ?></textarea>
	</p>
	<p>
		<label for="txt-notes">Personal notes:</label><br />
		<textarea name="notes" rows="3" placeholder="A little long, but I liked the part where they drank the wine."><?= html(@$book->notes) ?></textarea>
	</p>

	<p>
		<button>Save</button>
		<? if (!$id): ?>
			&nbsp;
			<label><input type="checkbox" name="another" checked /> Add another book</label>
		<? endif ?>
	</p>
</form>

<datalist id="authors"><?= html_options($authors, null, '', true) ?></datalist>

<?php

include 'tpl.footer.php';
