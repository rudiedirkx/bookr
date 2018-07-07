<?php

return [
	'version' => 12,
	'tables' => [
		'users' => [
			'id' => ['pk' => true],
			'username' => ['null' => false],
			'password' => ['null' => false],
			'settings',
		],
		'books' => [
			'id' => ['pk' => true],
			'user_id' => ['unsigned' => true, 'null' => false, 'references' => ['users', 'id']],
			'title' => ['null' => false],
			'author' => ['null' => false],
			'finished' => ['type' => 'date', 'null' => true, 'default' => null],
			'summary' => ['default' => ''],
			'pubyear' => ['type' => 'number', 'null' => true, 'default' => null],
			'notes' => ['default' => ''],
			'added' => ['unsigned' => true],
			'updated' => ['unsigned' => true],
			'import' => ['unsigned' => true, 'null' => true, 'default' => null],
			'isbn10' => ['null' => true, 'default' => null],
			'isbn13' => ['null' => true, 'default' => null],
			'rating' => ['unsigned' => true, 'null' => true, 'default' => null],
		],
		'categories' => [
			'id' => ['pk' => true],
			'user_id' => ['unsigned' => true, 'null' => false, 'references' => ['users', 'id']],
			'name' => ['null' => false],
			'show_in_list' => ['unsigned' => true, 'null' => false, 'default' => 0],
			'required' => ['unsigned' => true, 'null' => false, 'default' => 0],
			'multiple' => ['unsigned' => true, 'null' => false, 'default' => 0],
			'weight' => ['type' => 'int', 'null' => false, 'default' => 0],
		],
		'labels' => [
			'id' => ['pk' => true],
			'user_id' => ['unsigned' => true, 'null' => false, 'references' => ['users', 'id']],
			'enabled' => ['unsigned' => true, 'null' => false, 'default' => 1],
			'name' => ['null' => false],
			'category_id' => ['unsigned' => true, 'null' => false, 'references' => ['categories', 'id']],
			'default_on' => ['unsigned' => true, 'null' => false, 'default' => 0],
			'not_filter' => ['unsigned' => true, 'null' => false, 'default' => 0],
			'weight' => ['type' => 'int'],
		],
		'books_labels' => [
			'book_id' => ['unsigned' => true, 'null' => false, 'references' => ['books', 'id', 'cascade']],
			'label_id' => ['unsigned' => true, 'null' => false, 'references' => ['labels', 'id', 'cascade']],
		],
	],
];
