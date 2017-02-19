<?php

require 'inc.bootstrap.php';

if ( isset($_FILES['csv']) ) {
	$file = (object) $_FILES['csv'];

	header('Content-type: text/plain; charset=utf-8');

	if ( $file->error || !file_exists($file->tmp_name) ) {
		exit('Upload failed?');
	}

	$data = file_get_contents($file->tmp_name);
	$data = csv_read_doc($data);

	$months = array_flip(array('', 'jan', 'feb', 'maa', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'));

	$importId = md5(rand());

	$books = $errors = array();
	$stats = array('dateless' => 0, 'imported' => 0);
	foreach ($data as $line => $row) {
		$book = array(
			'title' => trim(@$row['title'], ' .'),
			'author' => trim(@$row['author'], ' .'),
			'notes' => trim(@$row['notes']),
			'created' => time(),
			'import' => $importId,
		);

		// VALIDATION
		if ( !$book['title'] && !$book['author'] ) {
			continue;
		}

		if ( !$book['title'] ) {
			$errors[] = "[$line] This line has an author: " . $book['author'] . ", but no title.";
			continue;
		}

		// AUTHOR
		if ( count($parts = explode(',', $book['author'])) == 2 ) {
			$book['author'] = trim($parts[1]) . ' ' . trim($parts[0]);
		}

		// ADDED
		$year = $month = 0;
		$date = preg_replace('# {2,}#', ' ', trim(preg_replace('#[^a-z0-9-]#', ' ', strtolower($row['added']))));
		if ( !$date ) {
			// No date is fine
			$stats['dateless']++;
		}
		else if ( preg_match('#^(\d{4})-(\d\d?)-\d\d?$#', $date, $match) ) {
			// Y-m-d is great
			$year = $match[1];
			$month = $match[2];
		}
		else if ( preg_match('#^\d+$#', $date) ) {
			// Just Year is good
			$year = $date;
		}
		else if ( preg_match('#^([a-z]{3,}) (\d+)$#', $date, $match) ) {
			// Year + DUTCH Month
			$year = $match[2];
			$mon = substr($match[1], 0, 3);
			if ( !isset($months[$mon]) ) {
				$errors[] = "[$line] I can't read the month in this date: " . $row['added'] . ".";
				continue;
			}
			$month = $months[$mon];
		}
		else {
			// Unsupported date format
			$errors[] = "[$line] I can't read this date: " . $row['added'] . ".";
			continue;
		}
		$book['read'] = str_pad($year, 4, '0', STR_PAD_LEFT) . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-00';

		$stats['imported']++;

		$books[] = $book;
	}

	if ( $errors ) {
		echo "I encountered errors, so I didn't save anything. Fix and try again.\n\n";
		echo implode("\n", $errors) . "\n";
		exit;
	}

	$db->begin();
	foreach ($books as $book) {
		$db->insert('books', $book);
	}
	$db->commit();

	$msg = array(
		$stats['imported'] . " books imported",
		$stats['dateless'] . " books without added",
		"Import ID: " . $importId,
	);
	do_redirect('index', array('msg' => $msg));
	exit;
}

else if ( isset($_GET['undo']) ) {
	$db->delete('books', array('import' => (string)$_GET['undo']));
	$num = $db->affected_rows();

	$msg = "Deleted $num books.";
	do_redirect('import', array('msg' => $msg));
	exit;
}

include 'tpl.header.php';

$imports = $db->fetch('
	SELECT import, COUNT(1) AS num, created
	FROM books
	GROUP BY import
	ORDER BY created DESC
')->all();

?>
<h1>Import books</h1>

<p>Upload a CSV with columns: <code>title, author, added, notes</code>.</p>

<form action method="post" enctype="multipart/form-data">
	<p>CSV: <input type="file" name="csv" /></p>

	<p><button class="submit">Import</button></p>
</form>

<h2>Previous imports</h2>

<table border="1" cellpadding="6">
	<thead>
		<tr>
			<th>ID</th>
			<th>Date</th>
			<th>Imported</th>
			<th>Undo</th>
		</tr>
	</thead>
	<tbody>
		<? foreach ($imports as $import): ?>
			<tr>
				<td><?= html($import->import) ?></td>
				<td><?= date(FORMAT_DATETIME, $import->created) ?></td>
				<td><?= $import->num ?> books</td>
				<td><a href="<?= get_url('import', array('undo' => $import->import)) ?>" onclick="return confirm('You sure?')">delete all</a></td>
			</tr>
		<? endforeach ?>
	</tbody>
</table>

<?php

include 'tpl.footer.php';
