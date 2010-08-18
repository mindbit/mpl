<?
require_once "SmartClientAuthForm.php";
require_once "controller/ExampleAuthRequest.php";

class ExampleAuthForm extends SmartClientAuthForm {
	function createRequest() {
		return new ExampleAuthRequest();
	}
}
?>
