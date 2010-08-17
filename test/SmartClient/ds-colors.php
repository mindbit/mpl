<?
require_once "controller/ColorsRequest.php";

$request = new ColorsRequest();
$request->dispatch();
echo $request->getResponse()->jsonEncode();

?>
