<?php

namespace Flurry;

class DB {

	protected static $handles = array();

	public static $config = array(
		'connections' => array()
	);

	public static function get($name) {

		if(!array_key_exists($name, self::$handles)) {
			
			if(!array_key_exists($name, self::$config['connections'])) {
				throw new \Exception("Database configuration for $name does not exist.");
			}


			$handles[$name] = new \PDO(
				self::$config['connections'][$name]['driver'].':dbname=//'.
				self::$config['connections'][$name]['host'].':'.
				self::$config['connections'][$name]['port'].'/'.
				self::$config['connections'][$name]['database'],
				self::$config['connections'][$name]['username'],
				self::$config['connections'][$name]['password']
			);
		}

		return $handles[$name];
	}
}
