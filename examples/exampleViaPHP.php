<?php
function process() {
	if(isset($_POST['btn'])) {
		require_once('jsonrpcClient.class.php');
		$request = json_decode($_POST['txt']);

		if (json_last_error() > 0) {
			echo '<p style="color:red;">'.json_last_error_msg() . '</p>';
		} else {
			$cli = new jsonrpcClient();
			if(!isset($request->params )) {
				$request->params = (object) [];
			}
			$result = $cli->request('http://localhost/test/JSON_RPC/index.php', $request->method, $request->id, json_encode($request->params));
			//$result = json_encode($result, JSON_PRETTY_PRINT);
			echo "
		<script>
			alert(`{$result}`);
		</script>
		";
		}
	}
}
?>		
<!DOCTYPE HTML>
<html>
	<head>
		<title>JSON-RPC tester via PHP CURL</title>
	</head>
	<body>

		<h1>JSON-RPC tester via PHP CURL</h1>
		<p>This tester sends data back to the server which then uses
		the JSON-RPC PHP CLient to fire off a request to the server.
		It inserts the server response in the page that is sent back
		to the browser.
		</p>
		<form method=post>
			<textarea id=txt name=txt cols=50 rows=10>{
    "id":1,
    "method":"test",
    "params":{
	    "p1":"one",
	    "p2":"two"
    }
}
			</textarea>
			<br />
			<input type=submit name=btn id=btn value = 'Send!'>
		</form>
		<pre>
		<?php process(); ?>
		</pre>
	</body>
</html>

