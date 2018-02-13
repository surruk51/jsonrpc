/***
 * rpcjson() - promise to retrieve data from an RPC-JSON Server	
 * 
 * Designed specifically to work with rpcJSONserver.class.php. Expects a
 * request in RPC-JSON format and returns
 * the server response as a Javascript object to the function specified
 * in the .then method, or returns an error response in the .catch 
 * method.
 * 
 * @param   text     url 	The location of the server (typically index.php)
 * @param   text/int id 	the identity of the request. Either an 
 *                          integer or a string NOT starting with a 
 *                          number
 * @param   text     method the name of the method to be invoked as
 *                          filename.classname.methodname
 * @param   object   params The parameters to be passed to the method
 *
 * @returns promise         the .then call will receive the data 
 *                          returned by the method.
 *                          the .catch call will receive an error
 *                          object formatted according to the RPC-JSON
 *                          v2.0 specification.
 ***/
	function jsonrpcClient(url, id, method, parameters)
	{
		let key, request, p, k;
		request = {"jsonrpc":"2.0", "id":id, "method":method, "params":parameters};

		p = new Promise(function (resolve, reject){
			//retrieve data from server based on a spec using json format
			let xhttp = new XMLHttpRequest();
		
			xhttp.onreadystatechange = function() {
				if (this.readyState == 4) {
					if (this.status == 200) {
						let response = JSON.parse(xhttp.responseText);
						if (response.result != undefined) {
							resolve(response)
						} else {
							reject(response);
						}
					} else {
						let response = {};
						response.id = null;
						response.jsonrpc = "2.0";
						response.error = {
							"error":'XHTTP:'+ this.status + ":" + xhttp.responseText};
						response.errorNum = '-31099';
						reject(response);
					}
				} 
			};
			xhttp.open("POST", url, true);
			xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			xhttp.send('data='+JSON.stringify(request));
		});
		return p;
	}
