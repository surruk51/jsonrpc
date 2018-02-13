<?php
namespace listsql;

/***
 * getdata - method impletation for the getdata message
 * 
 * @param     none
 * @returns   array      which forms the 'result' property
 *                       of the server response. Each element contains
 *                       an object with a single property whose value 
 *                       is the name of a query in the library.
 * @throws PDOException  if any PDO call fails. (e.g. SQL is invalid)
 * @version	             0.1.1
 * @author               Chris Jeffries
 * @copyright            2018 C G Jeffries
 * @licence https://www.gnu.org/licenses/gpl-3.0.en.html GPL, version 3
 ***/
function listsql() {
	require_once (__DIR__.DIRECTORY_SEPARATOR.'ListSQL.class.php');
	$l = new ListSQL();
	return $l->listSQL();
}
?>
