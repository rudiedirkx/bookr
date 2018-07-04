<?php

namespace rdx\bookr;

abstract class UserModel extends Model {

	static function insert( array $data ) {
		global $g_user;

		isset($data['user_id']) || $data['user_id'] = $g_user->id;

		return parent::insert($data);
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

}
