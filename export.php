<?php

use rdx\bookr\Book;

require 'inc.bootstrap.php';

$books = Book::all('user_id = ? ORDER BY id DESC', [$g_user->id]);

csv_file($books, ['title', 'author', 'finished', 'rating', 'summary', 'notes', 'isbn10', 'isbn13', 'created' => function($book) {
	return date('Y-m-d', $book->created);
}], 'bookr-export.csv');
