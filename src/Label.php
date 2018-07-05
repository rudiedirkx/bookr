<?php

namespace rdx\bookr;

class Label extends UserModel {

	static public $_table = 'labels';

	static protected $allSorted;



	protected function get_category() {
		return Category::find($this->category_id);
	}

	protected function get_num_books() {
		return self::$_db->count('books_labels', ['label_id' => $this->id]);
	}



	static public function names( array $ids, Category $category = null ) {
		$all = self::allSorted();
		if ( $category ) {
			$all = array_filter($all, function(Label $label) use ($category) {
				return $label->category_id == $category->id;
			});
		}
		$enabled = array_intersect_key($all, array_flip($ids));
		return self::options($enabled);
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
