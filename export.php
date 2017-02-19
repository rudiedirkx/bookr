<?php

require 'inc.bootstrap.php';

$books = $db->select('books', '1 ORDER BY id DESC')->all();

csv_file($books, ['title', 'author', 'read', 'summary', 'notes', 'isbn10', 'isbn13', 'added' => function($book) {
	return date('Y-m-d H:i:s', $book->created);
}], 'bookr-export.csv');
