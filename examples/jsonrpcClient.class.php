<?php
class jsonrpcClient {
	public $result;
	public $server_url;


	/***
	 * request - formats a request and despatches it.
	 * 
	 * This function only allows to make a single request at a time.
	 * You can also send multiple requests by formatting with
	 * makerequest() and listing them in an array which you pass to send()
	 * 
	 * @param string $server the URL of the server to be used. This may be on
	 *                       a different machine.
	 * @param string $method the name of the method to be used.
	 * @param mixed  $parameters
	 *                      the parameters to be passed to the object. This
	 *                      may be a PHP object containing the parameters of
	 *                      the request OR a JSON formatted string
	 * @param mixed $response
	 *                      This is a variable in which the result of the 
	 *                      request will be placed, or a lambda which will
	 *                      be called with the result.
	 ****/
	function request($server, $method, $id, $parameters, $response=false) {
		$request = $this->makerequest($method, $id, $parameters, $response);
		return $this->send($server, $request);
	}
	
	 
	/***
	 * makerequest - formats a request for sending but does not actually send it
	 * @param string $method the name of the method to be used.
	 * @param mixed  $parameters
	 *                      the parameters to be passed to the object. This
	 *                      may be a PHP object containing the parameters of
	 *                      the request OR a JSON formatted string
	 * @param mixed $response
	 *                      This is a variable in which the result of the 
	 *                      request will be placed, or a lambda which will
	 *                      be called with the result.
	 * @returns    object   formatted request ready for sending or batching
	 *                      into an array with other requests.
	 ***/
	function makerequest($method, $id,  $parameters, &$response) {
		$request = new stdClass();
		$request->jsonrpc = "2.0";
		if(!is_null($id)) {
			$request->id = $id;
		}
		$request->method = $method;
		$request->params = (is_object($parameters))?$parameters: json_decode($parameters);
		return $request;
	}
	
	/***
	 * send - despatch a prepared JSON-RPC request and pass back the
	 *        result;
	 * 
	 * $param string $server_url The URL of the server to send the 
	 *                           request to.
	 * $param object $request_object
	 *                           PHP object or array of objects formatted
	 *                           as JSON RPC request(s)
	 * @param lambda $response_action
	 *                           false, or a function to call when the
	 *                           repsonse is received from the server.
	 ***/
	

	function send ($server_url, $request_object, $response_action=false) {
		$request = $request_object;
		$request = json_encode($request);
		$ch = curl_init();

		curl_setopt_array( $ch,
			array( 
				CURLOPT_URL => $server_url, 
				CURLOPT_HEADER => 0, 
				CURLOPT_POST => 1,
				CURLOPT_POSTFIELDS => "data=" . $request,
				CURLOPT_RETURNTRANSFER => 1 
			) 
		);
		$response = curl_exec( $ch );

		
		if ( curl_errno( $ch ) )
		{
			$error = new stdClass();
			$error->code = -3200 + curl_errorno($ch);
			$error->message = curl_err($ch);
			$response = new stdClass();
			$response->jsonrpc="2.0";
			$response->error = $error;
			curl_close( $ch );
			return $response;
		}

		if ( curl_getinfo( $ch, CURLINFO_HTTP_CODE ) != 200 )
		{
			$error = new stdClass();
			$error->code = -3200 + curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			$error->message = "Server responded " . curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			$response = new stdClass();
			$response->jsonrpc="2.0";
			$response->error = $error;
			curl_close( $ch );
			return $response;
		}
		switch(true) {
			case ($response_action === false) :
				return $response;
				break;
			case (is_function($response_action)):
				return $response_action($response);
				break;
			default:
				throw new Exception ("Invalid response action");
		}
	}
}

