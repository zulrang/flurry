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

			$concfg = self::$config['connections'][$name];
			$server = false;
			if(isset($concfg['host'])) {
				$server = '//' . $concfg['host'];
				if(isset($concfg['port'])) {
					$server .= ':'.$concfg['port'];
				}
			}

			$dsn = $concfg['driver'].':dbname=';
			$dsn .= $server ? '/'.$concfg['database'] : $concfg['database'];

			$user = $concfg['username'];
			$pass = $concfg['password'];

			try {
				$pdo = new \PDO($dsn, $user, $pass);
			} catch (PDOException $e) {
				echo $e->getMessage() . " : " . $dsn;
				exit();
			}

			$pdo->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);
			$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
			$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

			$handles[$name] = $pdo;
		}

		return $handles[$name];
	}
}
