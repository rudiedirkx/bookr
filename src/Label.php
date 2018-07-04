<?php

namespace rdx\bookr;

class Label extends UserModel {

	static public $_table = 'labels';

	static protected $allSorted;

	static public function names( array $ids ) {
		$all = self::allSorted();
		$enabled = array_intersect_key($all, array_flip($ids));
		return self::options($enabled);
	}

	protected function get_num_books() {
		return self::$_db->count('books_labels', ['label_id' => $this->id]);
	}

	static public function allSorted() {
		if ( self::$allSorted === null ) {
			self::$allSorted = self::all('1 ORDER BY (SELECT weight FROM categories WHERE id = category_id), weight');
		}

		return self::$allSorted;
	}

	public function __toString() {
		return (string) $this->name;
	}

}
