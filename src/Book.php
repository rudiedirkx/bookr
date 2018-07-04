<?php

namespace rdx\bookr;

class Book extends Model {

	static public $_table = 'books';

	public function get_read_components() {
		return $this->read ? array_map('intval', explode('-', $this->read)) : array(0, 0, 0);
	}

	public function get_read_year() {
		return @$this->read_components[0];
	}

	public function get_read_month() {
		return @$this->read_components[1];
	}

}
