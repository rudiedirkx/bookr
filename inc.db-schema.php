<?php

return [
	'version' => 1,
	'tables' => [
		'books' => [
			'id' => ['pk' => true],
			'user_id' => ['unsigned' => true],
			'title',
			'author',
			'read' => ['type' => 'date'],
			'summary',
			'notes',
			'created' => ['unsigned' => true],
			'updated' => ['unsigned' => true],
			'import',
			'isbn10',
			'isbn13',
		],
	],
];
