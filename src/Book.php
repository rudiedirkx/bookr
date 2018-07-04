<?php

namespace rdx\bookr;

class Book extends Model {

	static public $_table = 'books';

	static public $ratings = [5 => 'Great 5/5', 4 => 'Good 4/5', 3 => 'Okay 3/5', 2 => 'Bad 2/5', 1 => 'Horrible 1/5'];



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
		isset($data['updated']) || $data['updated'] = time();

		return parent::update($data);
	}

	static function insert( array $data ) {
		global $g_user;

		isset($data['user_id']) || $data['user_id'] = $g_user->id;
		isset($data['created']) || $data['created'] = time();

		return parent::insert($data);
	}



	static protected function _extendUserConditions( &$conditions, array &$params ) {
		global $g_user;

		if ( is_array($conditions) ) {
			$conditions['user_id'] = $g_user->id;
		}
		else {
			$conditions = "user_id = ? AND $conditions";
			array_unshift($params, $g_user->id);
		}
	}

	static function count( $conditions, array $params = array() ) {
		self::_extendUserConditions($conditions, $params);
		return parent::count($conditions, $params);
	}

	static function first( $conditions, array $params = [] ) {
		self::_extendUserConditions($conditions, $params);
		return parent::first($conditions, $params);
	}

	static function all( $conditions, array $params = [], array $options = [] ) {
		self::_extendUserConditions($conditions, $params);
		return parent::all($conditions, $params, $options);
	}

}
