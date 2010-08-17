<?
require_once "RestDataSource.php";

/**
 * Simulate a RestDataSource request that is not actually bound to a
 * Propel object model.
 */
class ColorsRequest extends RestRequest {
	protected $staticData = array(
			array(
				"id"		=> "1",
				"color"		=> "Red",
				"html"		=> "#FF0000"
				),
			array(
				"id"		=> "2",
				"color"		=> "Green",
				"html"		=> "#00FF00"
				),
			array(
				"id"		=> "3",
				"color"		=> "Blue",
				"html"		=> "#0000FF"
				)
			);

	function createOm() {
		return null;
	}

	protected function init() {
		$this->err = array();
		$this->response = new RestResponse();
	}

	function doFetch() {
		foreach ($this->staticData as $obj)
			$this->response->addData($obj);
	}
}
?>
