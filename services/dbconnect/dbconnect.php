<?php
namespace dbconnect;

	const CONFIG_DIR = __DIR__; //better choose a location outside 
	                         //the server web space
/***
 * dbconnect - connect to database service
 * 
 * Makes a connection to the database and returns it. Only one connection
 * is possible as the db handle is cached and returned on subsequent
 * calls
 * 
 * @param string @database  The database to select when opening the
 *                          connection
 * @throws Exception        if the config file is missing
 *                          if the config file is malformed
 * @throws PDOException     if the database connection fails.
 * @return object           has just one property, 'db' whose value is 
 *                          the handle to the database.
 * @version	               0.1.1
 * @author                 Chris Jeffries
 * @copyright              2018 C G Jeffries
 * @licence https://www.gnu.org/licenses/gpl-3.0.en.html GPL, version 3
 ***/
	function dbconnect($config_file) {
		static $db;
		$conf_file = CONFIG_DIR.DIRECTORY_SEPARATOR.'config.db';
		if(!isset($db)) {
			
			if(file_exists($conf_file)) {
				$params = json_decode(file_get_contents($conf_file));   
				 
				if (is_null($params)) {
					throw new \Exception(json_last_error_msg(), json_last_error() + 30000);
				}
				
			}
			else
			{
				throw new \Exception ("Database connector configuration file '{$conf_file}' missing. ", 31039);
			}

			//Connect

			$options = [
				\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
				\PDO::ATTR_EMULATE_PREPARES   => false,
			];

			$result = new \stdClass();
			$db = new \PDO(
				"mysql:host=$params->host;".
				"dbname=$params->database;".
				"charset=$params->charset", 
				$params->user, 
				$params->pass, 
				$options);
			return (object) 
			[
				"db" => $db
			];
		} else {
			return (object) 
			[
				"db" => $db
			];
		}
	}
