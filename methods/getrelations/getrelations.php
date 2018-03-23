<?php
namespace getrelations;
/***
 * getrelations - method impletation for the getrelations message
 * 
 * @param object $params This request has no parameters
 * @returns   object     an object which forms the 'result' property
 *                       of the server response. contains
 *    array   relations  
 *                childtable
 *                childfield
 *                parenttable
 *                parentfield
 *                composite   (childtable.childfield->parenttable.parentfield)
 * 
 * @throws Exception     when an SQL statement is received but not 
 *                       permitted **
 * @version	             0.1.1
 * @author               Chris Jeffries
 * @copyright            2018 C G Jeffries
 * @licence https://www.gnu.org/licenses/gpl-3.0.en.html GPL, version 3
 ***/
	function getrelations($params) {
		$sql = "
		 SELECT 
			TABLE_NAME as childtable,        
			COLUMN_NAME as childfield,
			REFERENCED_TABLE_NAME as parenttable,
			REFERENCED_COLUMN_NAME as parentfield,
			CONSTRAINT_NAME
			FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
			WHERE TABLE_SCHEMA = (SELECT DATABASE())
			AND REFERENCED_COLUMN_NAME IS NOT NULL;";
		$result = call_service('dbconnect', (object) []);
		$dbh = $result->db;
		$statement = $dbh->prepare($sql);
		$statement->execute();
		$response = new \stdClass();
		$response->relations = $statement->fetchAll();
		return $response;
	}
	
