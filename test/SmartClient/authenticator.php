<?

require_once "SmartClientRPCResponse.php";
require_once "SmartClientAuthenticator.php";
require_once "controller/ExampleAuthRequest.php";

class ExampleAuthenticator extends SmartClientAuthenticator {
	function createRequest() {
		return new ExampleAuthRequest();
	}

	function getSessionData() {
		$user = MplSession::getUser();
		return array(
				"username" => $user->getUsername(),
				"realName" => $user->getRealName()
				);
	}
}

$obj = new ExampleAuthenticator();
$obj->write();
?>
