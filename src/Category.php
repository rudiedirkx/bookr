<?php

namespace rdx\bookr;

class Category extends UserModel {

	static public $_table = 'categories';

	static public function allSorted() {
		return self::all('1 ORDER BY weight');
	}

	public function __toString() {
		return (string) $this->name;
	}

}
