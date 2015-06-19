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

	$months = array_flip(array('foo', 'jan', 'feb', 'maa', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'));

	$importId = md5(time());

	$books = $errors = $stats = array();
	foreach ($data as $line => $row) {
		$book = array(
			'title' => trim($row['TITLE'], ' .'),
			'author' => trim($row['AUTHOR'], ' .'),
			'summary' => trim($row['REVIEW']),
			'created' => time(),
			'import' => $importId,
		);

		if ( !$book['title'] && !$book['author'] ) {
			continue;
		}

		if ( !$book['title'] ) {
			$errors[] = "[$line] This line has an author: <code>" . $book['author'] . "</code>, but no title.";
			continue;
		}

		$year = $month = 0;
		$date = preg_replace('# {2,}#', ' ', trim(preg_replace('#[^a-z0-9]#', ' ', strtolower($row['ENTRY DATE']))));
		if ( !$date ) {
			// No date is fine
			@$stats['dateless']++;
		}
		else if ( preg_match('#^\d+$#', $date) ) {
			$year = $date;
		}
		else if ( preg_match('#^([a-z]{3,}) (\d+)$#', $date, $match) ) {
			$year = $match[2];
			$mon = substr($match[1], 0, 3);
			if ( !isset($months[$mon]) ) {
				$errors[] = "[$line] I can't read the month in this date: <code>" . $row['ENTRY DATE'] . "</code>.";
				continue;
			}
			$month = $months[$mon];
		}
		else {
			$errors[] = "[$line] I can't read this date: <code>" . $row['ENTRY DATE'] . "</code>.";
			continue;
		}
		$book['read'] = str_pad($year, 4, '0', STR_PAD_LEFT) . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-00';

		@$stats['imported']++;

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
		$stats['dateless'] . " books without ENTRY DATE",
		"Import ID: " . $importId,
	);
	do_redirect('index', array('msg' => $msg));
	exit;
}

include 'tpl.header.php';

?>
<h1>Import books</h1>

<p>Upload a CSV with columns: <code>TITLE, AUTHOR, ENTRY DATE, REVIEW</code>.</p>

<form action method="post" enctype="multipart/form-data">
	<p>CSV: <input type="file" name="csv" /></p>
	<p><button>Import</button></p>
</form>

<?php

include 'tpl.footer.php';
