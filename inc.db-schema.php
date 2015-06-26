<?php

return array(
	'books' => array(
		'id' => array('pk' => true),
		'user_id' => array('unsigned' => true),
		'title',
		'author',
		'read' => array('type' => 'date'),
		'summary',
		'notes',
		'created' => array('unsigned' => true),
		'updated' => array('unsigned' => true),
		'import',
		'isbn10',
		'isbn13',
	),
);
