<?php
namespace listsql;

function listsql() {
	require_once (__DIR__.DIRECTORY_SEPARATOR.'ListSQL.class.php');
	$l = new ListSQL();
	return $l->listSQL();
}
?>
