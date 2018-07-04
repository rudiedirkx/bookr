<?php

return [
	'version' => 3,
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
			'notes' => ['default' => ''],
			'created' => ['unsigned' => true],
			'updated' => ['unsigned' => true],
			'import' => ['unsigned' => true, 'null' => true, 'default' => null],
			'isbn10' => ['null' => true, 'default' => null],
			'isbn13' => ['null' => true, 'default' => null],
			'rating' => ['unsigned' => true, 'null' => true, 'default' => null],
		],
	],
];
