<?php

namespace rdx\bookr;

use db_generic_model;

abstract class Model extends db_generic_model {

	static public function options( array $models ) {
		return array_map(function(Model $model) {
			return (string) $model;
		}, $models);
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
