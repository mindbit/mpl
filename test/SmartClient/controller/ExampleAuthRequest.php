<?

require_once "model/ExampleUser.php";
require_once "BaseAuthRequest.php";

class ExampleAuthRequest extends BaseAuthRequest {
	function authenticateUser($username, $password) {
		return ExampleUser::authenticate($username, $password);
	}
}

?>
