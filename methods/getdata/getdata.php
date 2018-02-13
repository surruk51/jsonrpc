<?php
namespace getdata;

	function getdata($params) {
		if(!isset($params->query)) {
			throw new Exception("No query supplied",30000);
		}
		if(!isset($params->querydata)) {
			$params->querydata = (object) [];
		}
		
		$sql = $params->query;
		$data = (array) $params->querydata;
		$result = call_service('dbconnect', (object) []);
		$dbh = $result->db;
		$result = new \stdClass();
		$sql = getquery($sql, $dbh);
		$hasparms = (strpos($sql, ' :') !== false);

		if(!$hasparms && count($data) > 0) {
			throw new \Exception("querydata supplied when none needed", 31045);
		}
		
		$statement = $dbh->prepare($sql);
		$result = $statement->execute($data);
		$response = new \stdClass();
		$response->data = $statement->fetchAll();
		$response->rowsAffected = $statement->rowCount();
		return $response;
	}
	
	function getQuery($query, $dbh, $querytable = 'ID_Queries') {
	
		$super = (file_exists('./MaintenanceMode'));

		if (count(explode(' ', trim($query))) > 1) {
			if ($super) {
				return $query;
			} else {
				throw new \Exception("SQL query not permitted, use a stored query", 31043);
			}
		} else {
			$statement = $dbh->prepare("SELECT `queryDefinition`, `protected` FROM `{$querytable}` WHERE `queryName` = :queryName");
			$statement->execute(["queryName"=>$query]);
			$data = $statement->fetch();
			if($data === false) 					 { throw new \Exception ("Query '{$query}' not found in library", 31040); }
			if($super === false && $data->protected == true) { throw new \Exception ("Query not permitted except in maintenance mode", 31041);}
			$queryDefinition = json_decode($data->queryDefinition);
			if (json_last_error() != JSON_ERROR_NONE) {throw new \Exception (json_last_error_message(), json_last_error()+30000);}
			return $queryDefinition->query;
		}
	}
