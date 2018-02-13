<?php
namespace test;
/***
 * test - to illustrate the writing of a method
 * 
 * This stub illustrates how to create a method file. 
 * Notice especially the 'namespace' declaration above. It should appear
 * in all your scripts that support the method. The namespace name must 
 * be the same as the method name. 
 * *
 * The 'main' function must be in a file with the same name as the 
 * method and contain a function with the same name as the method. 
 * In this case the method is 'test'. 
 * *
 * This file must be in a sub-directory of the methods directory. The
 * sub-directory must also have the method name as its name. 
 * *
 * Note also that method names are case-sensitive. 'Test' is NOT the 
 * same as 'test'. 
 * *
 * Notice also that on a Linux server you could have two methods
 * 'Test' and 'test', but on a Windows server this is not possible
 * since Windows will not you have sub-directories (or files) with the 
 * names 'Test' and 'test' in the same directory.
 * *
 * It is a good idea, though not enforced, to give function and methods
 * in your code a header like this one (See PHPDocumenter for more
 * details)
 * 
 * @param  object $params- PHP encoded version of the 'params' property 
 *                         of the call request. Note that dates will 
 *                         arrive as strings
 * 
 * @throws Exception       Any error should be reported using throw. 
 *                         The server will catch it and format a 
 *                         JSON-RPC v2.0 compliant response to return to
 *                         the client. Your error should report a 
 *                         message and a code number.
 *                         Errors are more verbose when the server is in
 *                         MaintenanceMode, giving the method name and 
 *                         the file and line number where the error
 *                         occurred.
 * 
 * @return object          This should be a php object with properties
 *                         for each of the items you wish to send to 
 *                         your client. Note that arrays with 
 *                         non-numeric keys will be returned as objects
 * @version	               0.1.1
 * @author                 Chris Jeffries
 * @copyright              2018 C G Jeffries
 * @licence https://www.gnu.org/licenses/gpl-3.0.en.html GPL, version 3
 ***/
function test($params)
{
	if(false) {
		//Getting data from some other method
		$methodResult = call_method($methodname, $params); 
		//Getting data back from a service
		$serviceResult = call_service($servicename, $params);
		throw new Exception("Error message", $errorCode);
	}
	return $params;
}
?>
