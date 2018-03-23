<?php
	require_once('jsonrpcServer.class.php');
	try {
		$s = new jsonrpcServer();
	
		if(!isset($_POST['data'])) {
			throw new Exception("No data supplied in POST 'data' parameter ", 31008);
		}
		$s->process($_POST['data']);
		$s->despatch();
	}
	catch (Exception $e) {
		$msg = $e->getMessage();
		$code = $e->getCode();
		$retval = new stdClass();
		$retval->error = new stdClass();
		$filepath = explode('/', $e->getFile());
		if(file_exists('MaintenanceMode')) {
			$meth = array_search('methods', $filepath);
			if($meth !== false) {
				$prefix = 'Method: '.$filepath[$meth+1].' - ';
			} else {
				$serv = array_search('services', $filepath);
				$prefix = 'Service: '.$filepath[$serv+1].' - ';
			}
			$suffix =  ' (line:'.$e->getLine() . ' in:'.$e->getFile().')';
		} else {
			$suffix = '';
			$prefix = '';
		}
		//catch any current statedata.
		global $statedata;
		if(isset($statedata)) {
			foreach($statedata as $property=>$value) {
				$retval->error->$property = $value;
			}
		}
		$retval->error->code = $code;
		$retval->error->message = $prefix . $msg . $suffix;
		header('Content-type: application/json');
		echo json_encode($retval, JSON_PRETTY_PRINT);
	}
