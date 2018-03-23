<?php
namespace getmeta;
/***
 * getmeta - method implementation for the getmeta message
 * 
 * @param object $params The parameters for getmeta which are passed as
 *                       an object. The following properties are
 *                       recognised
 *   string  query       EITHER the name of a query in a queries library
 *                       OR** a string of SQL
 *   string  querydata   an object with properties matching the 
 *                       placeholders in the query (whatever its source)
 * @returns object       an object which forms the 'result' property
 *                       of the server response. contains
 *   object resultFields object where the property name
 *                       is the column name and the value an object
 *                       with properties of the column.
 * @throws Exception     when an SQL statement is received but not 
 *                       permitted **
 * @throws Exception     when a stored SQL statement cannot be found
 * @throws Exception     when the query definition in the library is 
 *                       corrupt
 * @note                 **RAW SQL, and queries in the query library
 *                       that are marked as protected can only be run in
 *                       MaintenanceMode. To invoke MaintenanceMode you
 *                       must place a file of that name in the server
 *                       directory.
 * @throws PDOException  if any PDO call fails. (e.g. SQL is invalid)
 * @version	               0.1.1
 * @author                 Chris Jeffries
 * @copyright              2018 C G Jeffries
 * @licence https://www.gnu.org/licenses/gpl-3.0.en.html GPL, version 3
 ***/
function getmeta($params) {
		require_once('deriveMetaData.class.php');
		$dbh = getConnection('ID');
		$getMeta = new DeriveMetaData();
		$getMeta->setdbh($dbh);
		$getMeta->setSQL($params->query);
		$retval = new \stdClass();
		$retval->metaData = $getMeta->getPHPObject();
		return $retval;
}
		
		
function getConnection($database)
{
	$result = call_service('dbconnect', (object) ["database"=>$database]);
	return  $result->db;
}
