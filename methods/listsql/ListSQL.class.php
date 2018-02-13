<?php	
namespace listsql;	
	class ListSQL {
	
	private $dbh;
	private $response;
	
	function __construct() {
		$this->getConnection('ID');
		$this->response = (object) [];
	}
	
	/***
	 * listSQL - retrieves a list of the objects in the queries database
	 * 
	 * @return object  response object with resultdata
	 ***/
	public function listSQL() 
	{
		$sql = (file_exists('MaintenanceMode'))? 
			'SELECT `queryName` FROM `ID_Queries` ORDER BY `queryName`':
			'SELECT `queryName` FROM `ID_Queries` WHERE `protected` = false ORDER BY `queryName`';
		$this->response->resultdata = $this->dbh->query($sql)->fetchAll();
		return $this->response;
	}
	
	/***
	 * getConnection - connect to database
	 * 
	 * Makes a connection to the database and stores it in the object
	 * 
	 * @param string @database  The database to select when opening the
	 *                          connection
	 * @return void
	 ***/
	function getConnection($database) {
		$result = call_service('dbconnect', (object) ["database"=>$database]);
		$this->dbh = $result->db;
		return ;
	}
}
?>
