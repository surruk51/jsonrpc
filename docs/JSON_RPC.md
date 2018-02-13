JSON-RPC server & client

<span id="anchor"></span>Introduction
=====================================

This document describes

-   a PHP based JSON-RPC server class
-   a PHP based JSON-RPC client class and a
-   Javascript AJAX JSON-RPC library

The PHP server class responds to messages formatted using the
JSSON-RPCv2.0 specification. It supports the single request, batch
request and notification modes of the specification. It is tested with
PHP v7. It uses a pluggable framework for adding methods so that the
methods served are independent of the server code itself.

The PHP client class uses CURL to format and send a request to the
server and to retrieve the response, so your server needs to have the
PHP CURL module included. It operates synchronously. It is tested with
PHP v7

The Javascript AJAX library sends requests asynchronously using AJAX and
processes the response with a callback function. It is written for
ECMAScript 6 and tested with Chromium v64

The JSON-RPC v2.0 specification can be found at
http://www.jsonrpc.org/specification\#overview

<span id="anchor-1"></span>Set up
=================================

Copy all the files into a directory that is accessible to the server 
following the specification in filelayout.txt

The files are as follows:

- exampleViaAJAX.html

An example script which shows the Javascript AJAX client at work and how
to use it

- exampleViaPHP.php

An example script which shows the jsonrpcClient class at work and how 
to use it

- index.php

This file contains the HTTP Transport wrapper for the jsonrpcServer class

- jsonrpcClient.class.php

Use this class when you want to fire off JSON-RPC requests from a server 
side (PHP) script.

- jsonrpcClient.js

Use this library when you want to fire off JSON-RPC requests from an 
HTML page running in a modern browser.

- jsonrpcServer.class.php

This class implements the server processes. It is invoked by index.php 
for HTTP transported requests, but could be used for requests arriving 
via other methods (e.g. email)

- test.php

This file implements a method (“test”) which is used in the example 
scripts. It simply reflects back the parameters that are sent to it. 
It is an example of a pluggable service module. You can create 
additional pluggable method files and store them in the same directory. 
See the section on Pluggable Methods for more details.


<span id="anchor-2"></span>JSON-RPC Server for PHP
==================================================

The code for the server class is in the file ‘jsonrpcServer.class.php’.
The file ‘index.php’ is an example of how to invoke the server to
provide for requests sent by HTTP.

In addition there is an example service module ‘test.php’ which
implements the method ‘test’.

Although the server can be addressed directly it is more likely to be
called by using one of the client service modules that are described on
the following pages.

This section describes the use of the server via HTTP POST.

Typically, the server will be used either from PHP scripts running on
other servers or AJAX requests from browser clients. In either case, the
request should be formatted as an HTTP POST request with the payload
held in a ‘data’ variable. (Assuming you are using the supplied
‘index.php’ file)

The PHP and Javascript clients which can handle the request process for
you, are described later in this document.

A typical request would look like this:

{"jsonrpc": "2.0", "id": 1, "method": "test", "params": {"p1": "one",
"p2": "two"}}

which would result in the response:

{"jsonrpc": "2.0", "id": 1, "result": {"p1": "one", "p2": "two"}}

(“test” just reflects back the parameters you send it)

or, if there was an error in the request, e.g an unknown method, an
error response would be returned:

{"jsonrpc": "2.0", "id": 1, "error": {"code": -31001, "message": "File
wrong.php not found", "data": "" }}

You can also send a batch of requests together by wrapping them in an
array :

*\[{"jsonrpc":"2.0", "id":1, "method":"test", "params":{"p1": "one",
"p2": "two"}},{"jsonrpc":"2.0", "id":2, "method":"test", "params":{"p3":
"three", "p4": "four"}}\]*

and the response would be returned similarly in an array:

*\[{"jsonrpc": "2.0", "id": 1, "result": {"p1": "one", "p2": "two"}},
{"jsonrpc": "2.0", "id": 2, "result": {"p3": "three", "p4": "four"}}\]*

Errors are handled request by request:

\[{"jsonrpc":"2.0", "id":1, "method":"wrong", "params":{"p1": "one",
"p2": "two"}},{"jsonrpc":"2.0", "id":2, "method":"test", "params":{"p3":
"three", "p4": "four"}}\]

yields:

\[{"jsonrpc": "2.0","id": 1, "error": {"code": -31001,"message": "File
wrong.php not found","data": "" } }, {"jsonrpc": "2.0", "id": 2,
"result": {"p3": "three", "p4": "four"}}\]

However, if the error is in the JSON formatting (in this case the
closing square bracket is missing):

\[{"jsonrpc":"2.0", "id":1, "method":"test", "params":{"p1": "one",
"p2": "two"}},{"jsonrpc":"2.0", "id":2, "method":"test", "params":{"p3":
"three", "p4": "four"}}

You would not receive an array, even if your data contained an array
(with faulty formatting the server would not know). Instead you would
receive a single object like this:

{"jsonrpc": "2.0","id": null,"error": { "code": -32600, "message": "Not
a JSON-RPC v2.0 request","data": "" }}

<span id="anchor-3"></span>Methods and how to add them
======================================================

The server is designed to invoke methods that are stored in (PHP) files
separate to itself. In order to write a successful method, you need to
understand how the server looks for the methods it has been asked to
invoke.

<span id="anchor-4"></span>How the server locates method definitions
--------------------------------------------------------------------

The server uses a variety of means to find the code to execute the
method.

1.  Assuming the method name contains no dots:

    1.  First it looks for the method in the index.php file in case any
        internal methods have been defined (at the moment, none).
    2.  Next it looks for a file in the same directory with the same
        name as the method (e.g. method.php).
    3.  If it finds the file it will load it and look inside for a
        function with the same name as the method (
        function method(\$parms) ).
    4.  Otherwise it fails with a ‘File not found’ or ‘function not
        found’ error.

2.  You can also use a dotted notation. This allows you to collect a
    family of functions into a single file or class.

    1.  The method name ‘filename.funcname’ will look in ‘filename.php’
        for ‘function funcname()’.
    2.  This notation also allows you to collect a family of functions
        as static methods inside a class: The method name
        ‘filename.classname.methodname’ will look in ‘filename.php’ for
        class ‘className { function methodname()}’. This method helps
        with sub-classing method names to avoid name clashes.

3.  Here is an example. Where *‘filename.php’ *contains

-   *class myClass{\
    static function myFunc(\$params) {\
    return (object) \[‘question’:’this is my life?’\];\
    }\
    }*
-   You could call this method with :\
    *{"jsonrpc":"2.0", "id":1, "method":"filename.myClass.myFunc"}*
-   (params are optional) and the response would be:\
    *{"jsonrpc": "2.0", "id": 2, "result": {"question": "this is my
    life?"}}*

<span id="anchor-5"></span>How to write a method/function
---------------------------------------------------------

The method or function that receives the call will be passed a PHP
object containing the parameters passed in the ‘param’ parameter of the
RPC call, so in the above example, if you wrote

	function myFunc(\$params) {\
		if (\$params-&gt;question ==”is this my life?”) { //this would yield
															true\
			\$result = ‘yes it is.’;\
		} else {\
			\$result = ‘what do you mean?’;\
		}\
		return (object) \[“answer”=&gt;\$result\];\
	}

(note you must return the result as an object, not an array) and called
it with :

{*"*jsonrpc*"*:*"*2.0*"*, *"*id*"*:1, *"*method*"*:*"*myFunc*"*,
*"*params*"*:{*"*question*"*:*"*is this my life?*"*}

You would get the response:

{"jsonrpc": "2.0", "id": 2, "result": {"answer": "yes it is."}}

<span id="anchor-6"></span>JSON-RPC Client for Javascript
=========================================================

The JSON-RPC Client for Javascript is in the file *‘jsonrpc.js’. *Add
this file to your web page like this:

&lt;script src=*jsonrpc *.js&gt;&lt;/script&gt;

and use it like this:

*//First get the promise:*

*let p = jsonrpcClient("/pathTo/index.php", "test", {"p1":"one",
"p2":"two"});*

**

*//specify what happens if the promise is successfully resolved*

*p.then(function(response) { alert("ALL OK:" + JSON.stringify(response,
undefined,4); });*

**

*//Specify what happens if the promise fails to deliver*

*p.catch(function(response) { { alert("OH NO!:" +
JSON.stringify(response, undefined,4);});*

There is an example file *"exampleViaAJAX.html"* that lets you send
requests and receive responses. It appears as shown below. Type the RPC
code in the box on the left (there is a sample supplied) and see the
result in an alert box after you click the Send! button.

(Note: ‘test’ is an example method that just replies with the parameters
you send to it.)

Note that this is an asynchronous request. The function in .then() will
not be actioned in line but later, when the server responds.

<span id="anchor-7"></span>JSON-RPC Client for PHP
==================================================

The JSON-RPC Client for PHP is in the file *‘jsonrpc.class.php’. *Add
this file to your web page like this:

require\_once(‘jsonrpc.class.php’);

and use it like this:

\$cli = new jsonrpcClient();

Then, assuming you have already derived values for

- $server_url - the fully qualified name of the server e.g. 
‘http://localhost/test/JSON_RPC/index.php’

- $method - e.g. ‘test’

- $id - a number or a text string that does not start with a number. 
Note that if you do not supply an id, the request will be treated as a 
notification and there will be no reply.

- $params - an object that contains the parameters expected by the 
method

This will retrieve a result from the server:\
*\$result = \$cli-&gt;request(\$server\_url, \$method, \$id, \$params);*

There is an example file *‘exampleViaPHPCURL.php’* that lets you send
requests and receive responses. It appears as shown below. Type the RPC
code in the box on the left (there is a sample supplied) and see the
result in an alert box after you click the Send! button.

(Note: ‘test’ is an example method that just replies with the parameters
you send to it.)

This is a round trip transaction. The form is sent to the web server
which carries out the request on behalf of the client and then sends
back the page with the results.

**
