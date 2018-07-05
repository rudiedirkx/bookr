<?php

namespace rdx\bookr;

class Book extends UserModel {

	static public $_table = 'books';

	static public $ratings = [5 => 'Great 5/5', 4 => 'Good 4/5', 3 => 'Okay 3/5', 2 => 'Bad 2/5', 1 => 'Horrible 1/5'];



	public function setLabels( array $ids ) {
		self::$_db->delete('books_labels', ['book_id' => $this->id]);
		foreach ( $ids as $id ) {
			self::$_db->insert('books_labels', ['book_id' => $this->id, 'label_id' => $id]);
		}
	}

	public function getLabelNamesForCategory( Category $category ) {
		return Label::names($this->label_ids, $category);
	}



	protected function get_label_names() {
		return Label::names($this->label_ids);
	}

	protected function get_label_ids() {
		return self::$_db->select_fields_numeric('books_labels', 'label_id', ['book_id' => $this->id]);
	}

	protected function get_finished_components() {
		return $this->finished ? array_map('intval', explode('-', $this->finished)) : array(0, 0, 0);
	}

	protected function get_finished_year() {
		return @$this->finished_components[0];
	}

	protected function get_finished_month() {
		return @$this->finished_components[1];
	}



	public function update( $data ) {
		isset($data['updated']) or $data['updated'] = time();

		$labelIds = $data['label_ids'] ?? null;
		unset($data['label_ids']);

		$saved = parent::update($data);

		if ( is_array($labelIds) ) {
			$this->setLabels($labelIds);
		}

		return $saved;
	}

	static function insert( array $data ) {
		isset($data['added']) or $data['added'] = time();

		$labelIds = $data['label_ids'] ?? null;
		unset($data['label_ids']);

		$id = parent::insert($data);

		if ( is_array($labelIds) ) {
			$book = self::find($id);
			$book->setLabels($labelIds);
		}

		return $id;
	}

}
