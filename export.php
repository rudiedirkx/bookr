<?php

use rdx\bookr\Book;

require 'inc.bootstrap.php';

$books = Book::all('user_id = ? ORDER BY id DESC', [$g_user->id]);

csv_file($books, ['title', 'author', 'read', 'rating', 'summary', 'notes', 'isbn10', 'isbn13', 'added' => function($book) {
	return date('Y-m-d H:i:s', $book->created);
}], 'bookr-export.csv');
