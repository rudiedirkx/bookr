<?php

use rdx\bookr\Book;
use rdx\bookr\Label;

require 'inc.bootstrap.php';

$keepCols = ['title', 'author', 'isbn', 'added', 'finished', 'rating', 'notes', 'labels'];

if ( isset($_FILES['csv']) ) {
	$file = (object) $_FILES['csv'];

	header('Content-type: text/plain; charset=utf-8');

	if ( $file->error || !file_exists($file->tmp_name) ) {
		exit('Upload failed?');
	}

	$data = file_get_contents($file->tmp_name);
	$data = csv_read_doc($data, true, $keepCols);

	$months = array_flip(array('', 'jan', 'feb', 'maa', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'));

	$labels = Label::all('1');
	$labelMapping = array_reduce($labels, function(array $list, Label $label) {
		return $list + [$label->name => $label->id];
	}, []);

	$importId = time();

	$books = $errors = array();
	$stats = array('dateless' => 0, 'imported' => 0);
	foreach ($data as $line => $row) {
		$book = array(
			'user_id' => $g_user->id,
			'title' => trim(@$row['title'], ' .'),
			'author' => trim(@$row['author'], ' .'),
			'notes' => trim(@$row['notes']),
			'rating' => (int) trim(@$row['rating']) ?: null,
			'added' => time(),
			'import' => $importId,
		);

		// VALIDATION
		if ( !$book['title'] && !$book['author'] ) {
			continue;
		}

		if ( !$book['title'] ) {
			$errors[] = "[$line] This line has an author: '" . $book['author'] . "', but no title.";
			continue;
		}

		// AUTHOR
		if ( count($parts = explode(',', $book['author'])) == 2 ) {
			$book['author'] = trim($parts[1]) . ' ' . trim($parts[0]);
		}

		// ISBN
		if ( $isbn = trim(@$row['isbn']) ) {
			if ( strlen($isbn) >= 12 ) {
				$book['isbn13'] = $isbn;
			}
			else {
				$book['isbn10'] = $isbn;
			}
		}

		// ADDED
		if ( $added = trim(@$row['added']) ) {
			if ( $utc = strtotime($added) ) {
				$book['added'] = $utc;
			}
		}

		// FINISHED
		$year = $month = 0;
		$date = preg_replace('# {2,}#', ' ', trim(preg_replace('#[^a-z0-9-]#', ' ', strtolower(@$row['finished']))));
		if ( !$date ) {
			// No date is fine
			$stats['dateless']++;
		}
		elseif ( preg_match('#^(\d{4})-(\d\d?)-\d\d?$#', $date, $match) ) {
			// Y-m-d is great
			$year = $match[1];
			$month = $match[2];
		}
		elseif ( preg_match('#^\d+$#', $date) ) {
			// Just Year is good
			$year = $date;
		}
		elseif ( preg_match('#^([a-z]{3,}) (\d+)$#', $date, $match) ) {
			// Year + DUTCH Month
			$year = $match[2];
			$mon = substr($match[1], 0, 3);
			if ( !isset($months[$mon]) ) {
				$errors[] = "[$line] I can't read the month in this date: '" . $row['finished'] . "'.";
				continue;
			}
			$month = $months[$mon];
		}
		else {
			// Unsupported date format
			$errors[] = "[$line] I can't read this date: '" . $row['finished'] . "'.";
			continue;
		}
		$book['finished'] = $year ? str_pad($year, 4, '0', STR_PAD_LEFT) . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-00' : null;

		// LABELS
		if ( $labels = array_filter(array_map('trim', explode(',', @$row['labels']))) ) {
			$book['label_ids'] = [];

			foreach ( $labels as $label ) {
				if ( !isset($labelMapping[$label]) ) {
					// Unknown label
					$errors[] = "[$line] Unknown label: '" . $label . "'.";
					continue 2;
				}

				$book['label_ids'][] = $labelMapping[$label];
			}
		}

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
		Book::insert($book);
	}
	$db->commit();

	$msg = implode("\n", array(
		$stats['imported'] . " books imported",
		$stats['dateless'] . " books without finished",
		"Import ID: " . $importId,
	));
	set_message($msg);
	do_redirect('index');
	exit;
}

elseif ( isset($_GET['undo']) ) {
	$db->delete('books', ['user_id' => $g_user->id, 'import' => $_GET['undo']]);
	$num = $db->affected_rows();

	set_message("Deleted $num books.");
	do_redirect('import');
	exit;
}

include 'tpl.header.php';

$imports = $db->fetch("
	SELECT import, COUNT(1) AS num
	FROM books
	WHERE user_id = ? AND import IS NOT NULL
	GROUP BY import
	ORDER BY import DESC
", [$g_user->id])->all();

?>
<h1>Import books</h1>

<p><a href="<?= get_url('labels') ?>">manage labels</a></p>

<p>Upload a CSV with columns: <code><?= implode(', ', $keepCols) ?></code>.</p>

<form action method="post" enctype="multipart/form-data">
	<p>CSV: <input type="file" name="csv" /></p>

	<p><button class="submit">Import</button></p>
</form>

<h2>Previous imports</h2>

<table border="1" cellpadding="6">
	<thead>
		<tr>
			<th>Date</th>
			<th>Imported</th>
			<th>Undo</th>
		</tr>
	</thead>
	<tbody>
		<? foreach ($imports as $import): ?>
			<tr>
				<td><?= date(FORMAT_DATETIME, $import->import) ?></td>
				<td><?= $import->num ?> books</td>
				<td><a href="<?= get_url('import', array('undo' => $import->import)) ?>" onclick="return confirm('You sure?')">delete all</a></td>
			</tr>
		<? endforeach ?>
	</tbody>
</table>

<h2>Export books</h2>

<button onclick="location = '<?= get_url('export') ?>'">Download all <?= Book::count('1') ?></button>

<?php

include 'tpl.footer.php';
