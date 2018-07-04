<?php

return [
	'version' => 3,
	'tables' => [
		'users' => [
			'id' => ['pk' => true],
			'username',
			'password',
			'settings',
		],
		'books' => [
			'id' => ['pk' => true],
			'user_id' => ['unsigned' => true, 'null' => false, 'references' => ['users', 'id']],
			'title',
			'author',
			'read' => ['type' => 'date', 'null' => true, 'default' => null],
			'summary',
			'notes',
			'created' => ['unsigned' => true],
			'updated' => ['unsigned' => true],
			'import',
			'isbn10',
			'isbn13',
			'rating' => ['unsigned' => true, 'null' => true, 'default' => null],
		],
	],
];
