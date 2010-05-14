<?php

abstract class Q {
	static $Database = null;

	final private function __construct() {
	}
}

function Q($tableName) {
		return new QueryExpression(Q::$Database, $tableName);
}

if(!function_exists('at')):
	function at($array, $key, $defaultValue = null) {
		return isset($array[$key]) ? $array[$key] : $defaultValue;
	}
endif;
