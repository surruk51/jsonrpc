<?php

class jsonrpcServer {
	public $response;
	private $error_state;
	private $registry;
	function __construct()
	{
		$this->response = [];
		$error_state = false;
	}
	
	/***
	 * process - main processor for JSON-RPC requests
	 * 
	 * Senses whether the JSON received consists of an array. If so, it 
	 * calls  batch_process() to process the requests.
	 * If, however, it senses it has received a single object, it passes
	 * it to single_process() to process the request.
	 * 
	 * @param string  $request the request object or array of objects as a
	 *                         JSON literal string.
	 * @return        none
	 ***/
	function process($request) {
		$requests = json_decode($request);

		if(json_last_error() != JSON_ERROR_NONE) {
			$error = $this->call_error(-32700, json_last_error(), json_last_error_msg());
			$this->response = $this->format_result($request, $error);
		}
		if(is_array($requests)) {
			$this->response = $this->batch_process($requests);
		}
		else
		{
			$this->response = $this->single_process($requests);
		}
	}
		
	/***
	 * batch_process - process a batch of JSON-RPC requests		
	 * 
	 * treats each element of the array as a separate request which it
	 * passes to single_process. The results of the calls are gathered into
	 * an array
	 * 
	 * @note Whilst the batch processor normally returns an array of JSON-RPC
	 *       result objects (some of which may be error objects), if the
	 *       array holding the batch itself cannot be parsed into array 
	 *       elements a single error object, not in an array is returned.
	 * 
	 * @param string json ARRAY literal representing a collection of
	 *               JSON-RPC requests to be processed    
	 ***/
	function batch_process ($requests) {

		$responses = [];
		foreach($requests as $onerequest) {
			$responses[] = $this->single_process($onerequest);
		}
		
		return $responses;
	}
	
	/***
	 * single_process - processes a single JSON-RPC request
	 * 
	 * Validates the request. If invalid returns an error response object.
	 * If valid, invokes the method specified and returns the response
	 * provided by that function. This function provides the unwrapping and
	 * re-wrapping for the JSON-RPC syntax. The called function receives and
	 * returns a PHP object.
	 * 
	 * @param string $request JSON-RPC v2.0
	 ***/
	function single_process($request) {

		if(!isset($request->jsonrpc) || $request->jsonrpc !== '2.0') {
			$result = $this->call_error(-32600, 0, "Not a JSON-RPC v2.0 request");
			$result = $this->format_result($request, $result);
			return $result;
		}
		
		if(isset($request->params) && !(is_array($request->params) || is_object($request->params))) {
			$result = $this->call_error(-32602, 0, "Invalid params.");
			$result = $this->format_result($request, $result);
			return $result;
		}
		
		if (!isset($request->params)) {
			$request->params = new stdClass();
		}
		
		if (isset($request->id)) {
			$result = $this->call_method($request->method, $request->params);
			$result = $this->format_result($request, $result);
			return $result;
		} else {
			$this->call_method($request->method, $request->params);
			return null;
		}
	}
	
	function despatch() {
		if($this->response != null) {
			header('Content-type: application/json');
			$response = json_encode($this->response, JSON_PRETTY_PRINT);
			logmsg("RESPONSE: " . $response);
			echo $response;
		}
	}
	
	function format_result($request, $result) {
		$response = new stdClass();
		$response->jsonrpc = '2.0';
		$response->id = $request->id;
		
		if($this->error_state) {
			$response->error = $result;
			$this->error_state = false;
		} else {
			$response->result = $result;
		}
		
		return $response;
	}
	
	static function call_error($rpcErrorCode, $userErrorCode, $userErrorMessage, $userExtra = '') {
		
		if($rpcErrorCode == 0 && $userErrorCode < 32000) {
			$code = -$userErrorCode;
		} else {
			$code = $rpcErrorCode;
		}
		
		return (object) 
		[
			'error'=>(object) 
			[
				"message"=> $userErrorMessage,
				"code"=> $code
			]
		];
		
	}
	function call_method($user_method, $params) {
		return $this->call_func('method', $user_method, $params);
	}
	
	function call_service($user_method, $params) {
		return $this->call_func('service', $user_method, $params);
	}
	
	/***
	 * call_func - ensure functon exist and loaded then call
	 * 
	 * Check the directory, then the file and finally the function in
	 * the file exist. If not return error, otherwise return result of
	 * call.
	 * In every case either a result object of the successful call is 
	 * returned or an error object if something is wrong e.g. missing
	 * file. The file is assumed to have a .php extension which should
	 * NOT be supplied. 
	 * 
	 * The file will only be looked for in the 'methods' or 'services'
	 * directory, depeneding on the $type spcified.
	 * 
	 * @param string $type  either 'method' or 'service'
	 * @param string method the function to be called as described above
	 * @param object params  the parameter object to be passed to the
	 *                      function/method
	 * @return object       with an error or a result property
	 ***/ 

	static function call_func($type, $user_method, $params) {
		
		if(!function_exists($user_method.DIRECTORY_SEPARATOR.$user_method)) {
			if (!is_dir($x = __DIR__.DIRECTORY_SEPARATOR.$type.'s'.DIRECTORY_SEPARATOR.$user_method))
				return self::call_error(0,31001, "Unknown {$type} (dir not found in {$type}s) --- $x");
			}
		
			if (!is_file(__DIR__.DIRECTORY_SEPARATOR.$type.'s'.DIRECTORY_SEPARATOR.$user_method.DIRECTORY_SEPARATOR.$user_method.'.php')) {
				return self::call_error(0,31001, "Unknown method ({$user_method}.php not found in {$type}s/{$user_method})");
			}
			
			require_once(__DIR__.DIRECTORY_SEPARATOR.$type.'s'.DIRECTORY_SEPARATOR.$user_method.DIRECTORY_SEPARATOR.$user_method.'.php');
//			return get_defined_functions()["user"];
			
			if(!function_exists($user_method.'\\'.$user_method)) {
				return self::call_error(0,31001, "Implementation function {$user_method}(\$params) not found in {$user_method}.php - ".$user_method."\\".$user_method);
		}
	
		return call_user_func($user_method.'\\'.$user_method, $params);
    }
}

function call_method($user_method, $params) {
	return jsonrpcServer::call_func('method', $user_method, $params);
}

function call_service($user_method, $params) {
	return jsonrpcServer::call_func('service', $user_method, $params);
}

function logmsg($t) {
	file_put_contents("/home/chris/Desktop/log.txt", "\n".$t, FILE_APPEND);
	chmod("/home/chris/Desktop/log.txt", 0777);
}
