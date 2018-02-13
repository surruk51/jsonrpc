<?php
namespace getmeta;
function getmeta($params) {
		require_once('deriveMetaData.class.php');
		$dbh = getConnection('ID');
		$getMeta = new DeriveMetaData();
		$getMeta->setdbh($dbh);
		$getMeta->setSQL($params->query);
		return $getMeta->getPHPObject();
}
		
function getConnection($database)
{
	$result = call_service('dbconnect', (object) ["database"=>$database]);
	return  $result->db;
}
