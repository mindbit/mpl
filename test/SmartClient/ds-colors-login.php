<?
require_once "controller/ColorsRequest.php";
require_once "view/ExampleAuthForm.php";

$protector = new ExampleAuthForm();
$protector->write();

$request = new ColorsRequest();
$request->dispatch();
echo $request->getResponse()->jsonEncode();

?>
