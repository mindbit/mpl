<?
require_once "mpl-propel-init.php";
require_once "RestDataSource.php";

class PersonsRequest extends RestRequest {
	function createOm() {
		return new Person();
	}
}

$request = new PersonsRequest();
$request->dispatch();
echo $request->getresponse()->jsonencode();
?>
