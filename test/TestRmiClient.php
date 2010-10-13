<?
$basePath = realpath(dirname(__FILE__) . "/..");
set_include_path($basePath);

require_once("Env.php");
Env::setup();

require_once("Rmi.php");

function myloader($classname) {
}

$shellBasePath = escapeshellarg($basePath);
$cmd = "/usr/bin/php -q -d include_path=" . $shellBasePath . " " .
	$shellBasePath . "/test/TestRmiServer.php";

echo "Starting server (" . $cmd . ") ... ";
$client = new ProcOpenRmiClient($cmd);

echo "OK\n";

echo "\nCreating instance... ";
$test = $client->createInstance("TestServer");
echo "OK - rmi id is " . $test->getRmiId() . "\n";

echo "\nTesting foo(2,3)... ";
$ret = $test->foo(2, 3);
echo "OK - result is " . ErrorHandler::varDump($ret) . "\n";

ini_set('unserialize_callback_func', 'myloader');

echo "\nTesting doError()... ";
try {
	$ret = $test->doError();
	echo "FAIL - nothing happened\n";
} catch (Exception $e) {
	echo "Caught exception " . $e->getMessage();
	for ($tmp = $e->getPrevious(); $tmp !== null; $tmp = $tmp->getPrevious())
		echo "; Nested exception: " . $tmp->getMessage();
	echo "\n";
	//var_dump($e);
}

echo "\nDone\n";
?>
