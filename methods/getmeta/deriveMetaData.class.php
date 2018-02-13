<?php
namespace getmeta;
/***
 * Class DeriveMetaData
 * 
 * This class builds a set of metaData based on an SQL query. It 
 * requires access to the database in order to carry out its analysis
 ***/
class DeriveMetaData {
	private $dbh;
	private $sql;
	private $intmeta;
	
	function __construct() {
		$this->intmeta = false;
	}
	function setdbh($val) { $this->dbh = $val; }
	function setsql($val) {	$this->sql = getquery($val, $this->dbh); }
	function getjson() { return json_encode($this->analyse(), JSON_PRETTY_PRINT); }
	function getPHPObject() { return $this->analyse(); }
	
	private function analyse() {
		if ($this->intmeta !== false) {
			return $this->intmeta;
		}
		else {
			if(($x = strtoupper(substr(trim($this->intmeta),0,5))) !== 'SELECT') {
				$y = new \stdClass();
				$y->token = $x;
			}
			
			$tables = $this->getPDOMetaByTable($this->sql); //get the basic data
			$tables = $this->getMySQLMetaForTables($tables); //get better data if possible
			$this->intmeta = $this->makeDerivedMetaData($tables);
			return $this->intmeta;
		}
	}
		
	private function getPDOMetaByTable($sql) {

		$bowdlersql = $this->bowdleriseSQL($sql);
		$this->placeholders = $this->getPlaceHolders($sql);

		//make dummy querydata;
		$queryData = [];
		for ($k=0; $k<count($this->placeholders); $k++) {
			$queryData[$this->placeholders[$k]] = 1;
		}
		
		//test the data
		$statement = $this->dbh->prepare($bowdlersql);
		$result = $statement->execute($queryData);
		
		//Sort the data into tables, get PDO meta
		/***
		 * PDO META DATA:
		 * name				The name of this column as returned by the database.
		 * table			The name of this column's table as returned by the database.
		 * native_type		The PHP native type used to represent the column value.
		 * driver:decl_type	The SQL type used to represent the column value 
		 * 					in the database. If the column in the result set 
		 * 					is the result of a function, this value is not 
		 * 					returned by PDOStatement::getColumnMeta().
		 * flags			Any flags set for this column.
		 * len				The length of this column. Normally -1 for types 
		 * 					other than floating point decimals.
		 * precision		The numeric precision of this column. Normally 0 
		 * 					for types other than floating point decimals.
		 * pdo_type			The type of this column as represented by the 
		 * 					PDO::PARAM_* constants.
		 ***/
		$tables = new \stdClass();
		$k=0;
		
		while($metaData = $statement->getColumnMeta($k)) {
			$tableName = ($metaData['table']!='')?$metaData['table']: '[Computed]';
			$fieldName = $metaData['name'];
			
			if(!isset($tables->$tableName)) {
				$tables->$tableName = new \stdClass(); 
			}
			$tables->$tableName->$fieldName = new \stdClass();
			$tables->$tableName->$fieldName->PDO = (object) $metaData;
			$tables->$tableName->$fieldName->MySQL = new \stdClass();
			$k++;
		}
		
		return $tables;
	}

	function getMySQLMetaForTables($tables) {
		/***
		 * MySQL META DATA:
		 * Field		The name of this field in its table 
		 * Type         The type of this field. contains length and precision
		 *              information for number types.
		 * Collation    The sorting order for this field (by character set 
		 * Null         Whether or not this field can have a NULL value 
		 * Key          Whether this field is an index key (=PRI for primary index) 
		 * Default      What is the default value for this field when inserting
		 *              a new record
		 * Extra        auto-increment and possibly other properties
		 * Privileges   the actions the current user is permitted on this 
		 *              field
		 * Comment      comment added by the schema builder
		 * 
		 * NB table is implicit in the query so does not appear here
		 ***/
		foreach($tables as $tableName=>$dummy) {
			if($tableName != '[Computed]') {
				$MySQLMetaTable = $this->dbh->query("SHOW FULL COLUMNS FROM `$tableName` ")->fetchAll();
				foreach($MySQLMetaTable as $MySQLMetaField) {
					$fieldName = $MySQLMetaField->Field;
					if (isset($tables->$tableName->$fieldName->PDO)) { //if in the PDO list, it was a result field of the query
						$tables->$tableName->$fieldName->MySQL = $MySQLMetaField; //But we prefer the better MySQL metadata
					}
				}
			}
			else {
				$mySQLMetaTable = [];
			}
		}
		return $tables;
	}

	function makeDerivedMetaData($tables) {
	//How MySQL types are treated by the field editor by default
	/***
	 * EDIT TYPES
	 * numeric		A typeable number field right aligned but with no
	 *              scroller
	 * number		Suitable for small integers. Can type or scroll
	 * text			One line input field, left-aligned, accepts anything
	 * date			Shows date and a date picker
	 * datetime		Shows a data AND a time picker
	 * time			Shows a time picker (three scrollable numbers)
	 * textarea     Multiline input field, left aligned accepts anything
	 * file			Allows the user to upload a file. May display the 
	 *              current binary content if appropriate
	 * select		Shows a drop down box so that the user may choose a
	 *              value
	 * hidden		the data is accessible by program but not visible to
	 *              or editable by the user
	 * color		shows a color picker (no field type uses this by 
	 *              default)
	 * email		allows a valid email address (no field type uses 
	 *              this by default)
	 * phone		allows a valid phone number (no field type uses this
	 *              by default)
	 ***/
	 //Map MySQL Type to EditType
		static	$EditTypeMap = [
		'int'=>'numeric',
		'bit'=>'bit',
		'char'=>'text',
		'varchar'=>'text', 
		'text'=>'textarea',
		'date'=>'date',
		'datetime'=>'datetime',
		'year'=>'year',
		'time'=>'time',
		'tinyint'=>'number',
		'smallint'=>'numeric',
		'mediumint'=>'numeric',
		'bigint'=>'numeric',
		'decimal'=>'numeric',
		'float'=>'numeric',
		'double'=>'numeric',
		'real'=>'numeric',
		'tinytext'=>'textarea',
		'mediumtex'=>'textarea',
		'mediumtext'=>'textarea',
		'longtext'=>'textarea',
		'binary'=>'file',
		'varbinary'=>'file',
		'blob'=>'file',
		'mediumblob'=>'file',
		'longblob'=>'file',
		'tinyblob'=>'file',
		'enum'=>'select',
		'set'=>'select',
		'timestamp'=>'hidden'];
		//Map PDO type (getColumnMeta) to MySQL type
		static $TypePDOMap = [
			"BIT"=> "bit",  
			"BLOB"=> "LEVEL_2",  
			"DATE"=> "date",  
			"DATETIME"=>"datetime",
			"TIMESTAMP"=>"timestamp",
			"TIME"=>"time",
			"DOUBLE"=> "double, double",  
			"FLOAT"=> "float",  
			"INT24"=> "mediumint",  
			"LONG"=> "int",  
			"LONGLONG"=> "bigint",  // if unsigned flag change to serial 
			"NEWDECIMAL"=> "decimal",  
			"SHORT"=> "smallint",  
			"STRING"=> "binary", 
			"TINY"=> "tinyint",  
			"VAR_STRING"=> "varchar",  // divide by 4!!! not possible to
									   // sense varbinary
			"YEAR"=> 'year'
			]; 
		static $TypePDOMap2 = [
			"BLOB:262140"=> "text",  
			"BLOB:1020"=> "tinytext",  
			"BLOB:67108860"=> "mediumtext",  
			"BLOB:4294967295"=> "longblob",  
			"BLOB:255"=> "tinyblob",  
			"BLOB:16777215"=> "mediumblob",  
			"BLOB:65535"=> "blob"];
			
		//List of fields we want to derive values for
		$desiredFields = ["field","type","null","key","extra","privileges","comment","table","len","precision","edittype","prompt","default"];

		$meta = new \stdClass();
		foreach($tables as $tableName=>$table) {
			foreach($table as $tableField) {
				$metaField = new \stdClass();

				//analyse the awkward MySQL type field
				if(isset($tableField->MySQL->Type)) {
					$temp = explode('(', $tableField->MySQL->Type);
					$tableField->MySQL->Type = $temp[0];
					if(isset($temp[1])) {
						$temp = explode(',', substr( trim( $temp[1] ),0,-1) );
						
						if ($tableField->MySQL->Type =='enum' || $tableField->MySQL->Type == 'set') {
							$metaField->EnumValues = $temp;
						} else {
							$tableField->MySQL->Len = $temp[0];
							$tableField->MySQL->Precision = (isset($temp[1]))? $temp[1]: 0;
						}
					}	
				}
				foreach($desiredFields as $desiredField) {


					switch($desiredField) {
						case 'field':
							$metaField->Field =
							 $tableField->PDO->name;
							break;
						case 'default':
							if(isset($tableField->MySQL->Default)) {
								$metaField->Default = $tableField->MySQL->Default;
							} else {
								$metaField->Default = '';
							}
						
						case "type":
							if(isset($tableField->MySQL->Type)) {						
								$metaField->Type =  $tableField->MySQL->Type; //parsed earlier
							} else {
								$metaField->Type = $TypePDOMap[$tableField->PDO->native_type];
								if ($metaField->Type=='LEVEL_2') {
									$metaField->Type = $TypePDOMap2[$tableField->PDO->native_type.':'.$tableField->PDO->len];
								}
								if (in_array(['unsigned','auto_increment'], $tableField->PDO->flags) && ($metaField->Type == 'int') ) {
									$metaField->Type = 'serial';
								}
							}
							break;

						case "null":
							if(isset($tableField->MySQL->Null)) {
								$metaField->Null = $tableField->MySQL->Null;
							} else {
								$metaField->Null =(in_array('not_null', $tableField->PDO->flags))? 'NO': 'YES';
							}
							break;

						case "key":
							if(isset($tableField->MySQL->Key)) {
								$metaField->Key = $tableField->MySQL->Key;
							} else {
								$metaField->Key = (in_array('primary_key', $tableField->PDO->flags))?'PRI':'';
							}
							break;

						case "extra":
							if(isset($tableField->MySQL->Extra)) {
								$metaField->Extra = $tableField->MySQL->Extra;
							} else {
								$metaField->Extra = (in_array('auto_increment', $tableField->PDO->flags))?'auto_increment':'';
							}
							break;

						case "privileges":
								if(isset($tableField->MySQL->Privileges)) {
								$metaField->Privileges = $tableField->MySQL->Privileges;
								} else {
									if($tableName == '[Computed]') {
										$metaField->Privileges = 'select';
									} else {
										$metaField->Privileges = 'select,insert,update,references'; //Cannot tell so assume all
								}
							}
							break;
	
						case "comment":
							if(isset($tableField->MySQL->Comment)) {
								$metaField->Comment = $tableField->MySQL->Comment;
							} else {
								$metaField->Comment = '';
							}

						case "table":
							$metaField->Table = $tableName;
							break;

						case "len":
							if(isset($tableField->MySQL->Len)) {
								$metaField->Len =  (int) $tableField->MySQL->Len; //parsed earlier
							} else {
								$metaField->Len = ($tableField->PDO->native_type == 'VAR_STRING')? ($tableField->PDO->len / 4): $tableField->PDO->len; 
							}
							break;

						case "precision";
							if(isset($tableField->MySQL->Precision)) {
								$metaField->Precision = (int) $tableField->MySQL->Precision; //parsed earlier
							} else {
								$metaField->Precision = $tableField->PDO->precision;
							}
							break;

						case "prompt":
							if(isset($tableField->MySQL->Comment)) {
								$metaField->Prompt = $this->capSpacer($tableField->MySQL->Field);
							} else {
								$metaField->Prompt = $this->capSpacer($tableField->PDO->name);
							}
							break;
					}
				}
				$metaField->EditType = $EditTypeMap[$metaField->Type];
				$meta->{$metaField->Field} = $metaField;
			}
		}
		$retval = new \stdClass();
		$retval->resultFields = $meta;
		$retval->requestFields = new \stdClass();
		
		foreach ($this->placeholders as $placeHolder){
			$temp = new \stdClass();
			$temp->Field = $placeHolder;
			$temp->EditType = 'text';
			$retval->requestFields = $temp;
		}
		return $retval;
	}
					

	function bowdleriseSQL($sql) {
		$pos = stripos($sql, ' LIMIT ');
		$bowdlersql = ($pos > 0)? substr($sql, 0, $pos): $sql;
		$bowdlersql .= ' LIMIT 0 ';
		return $bowdlersql;
	}
	
	/***
	 * getPlaceHolders - find named placeholders (form is :placeholder )
	 *                   within SQL statement
	 * 
	 * @param  string $sql  the SQL to be analysed
	 * @return array        an array of strings each of which is a placeholder
	 *                      witihn the SQL statement
	 ***/
	function getPlaceHolders($sql)
	{
		//find the placeholders
		$tokens = array();
		preg_match_all('/[\W]:(\w*)\W/', $sql . ' ', $tokens); 
		//unset($tokens[0]); //we do not need the concatenated string of tokens
		return $tokens[1];
	}
	/***
	 * Takes text in CamelCase or underscore separated 
	 * 
	 * turns thisKindOfText 
	 * and   this_kind_of_text 
	 * into  This Kind Of Text
	 * 
	 * @param  string  $x the text to modify
	 * @return string  modified text
	 ***/
	function capSpacer($x)
	{
		$words = explode('_', $x);
		
		foreach($words as $key=>$word) {
			$words[$key] = strtoupper(substr($word,0,1)) . substr($word,1);
		}
		
		$x = implode('', $words);
		
		for($k=0, $y=''; $k < strlen($x); $k++) {
			$y.= (ctype_upper($x[$k]))? ' ' . $x[$k]: $x[$k]; 
		}
		
		return trim($y);
	}

}

	function getQuery($query, $dbh, $querytable = 'ID_Queries') {
	
		$super = (file_exists('./MaintenanceMode'));

		if (count(explode(' ', trim($query))) > 1) {
			if ($super) {
				return $query;
			} else {
				throw new \Exception("SQL query not permitted, use stored query", 31043);
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
