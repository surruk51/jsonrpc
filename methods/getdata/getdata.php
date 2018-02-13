<?php
namespace getdata;
/***
 * getdata - method impletation for the getdata message
 * 
 * @param object $params The parameters for getdata which are passed as
 *                       an object. The following properties are
 *                       recognised
 *    string  query      EITHER the name of a query in a queries library
 *                       OR** a string of SQL
 *    string  querydata  an object with properties matching the 
 *                       placeholders in the query (whatever its source)
 *    integer pagelength the number of rows to send in one response.
 *                       (optional - default infinite)
 *    integer pageno     the page offset to start at.
 *                       (optional - default 0)
 * @returns   object     an object which forms the 'result' property
 *                       of the server response. contains
 *    array   resultData each array element represents a retrieved row
 *                       expressed as an object where the property name
 *                       is the column name and the value is its value
 *                       for the current row.
 *    integer rowsAffected the number of rows retrieved by a SELECT or
 *                       altered by an UPDATE, INSERT or DELETE
 * @throws Exception     when an SQL statement is received but not 
 *                       permitted **
 * @throws Exception     when a stored SQL statement cannot be found
 * @throws Exception     when the query definition in the library is 
 *                       corrupt
 * @throws PDOException  if any PDO call fails. (e.g. SQL is invalid)
 * @note                 **RAW SQL, and queries in the query library
 *                       that are marked as protected can only be run in
 *                       MaintenanceMode. To invoke MaintenanceMode you
 *                       must place a file of that name in the server
 *                       directory.
 * @version	             0.1.1
 * @author               Chris Jeffries
 * @copyright            2018 C G Jeffries
 * @licence https://www.gnu.org/licenses/gpl-3.0.en.html GPL, version 3
 ***/
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
