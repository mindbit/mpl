<?
function get_official_list() {
	//return file_get_contents("list-en1-semic-3.txt");
	$ch = curl_init("http://www.iso.org/iso/list-en1-semic-3.txt");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$res = curl_exec($ch);
	curl_close($ch);
	$str = iconv("iso-8859-1", "utf-8", $res);
	//file_put_contents("list-en1-semic-3.txt", $str);
	return $str;
}

function main() {
	echo "<" . "?\n\nclass ISO3166 {\n";
	$list = explode("\n", get_official_list());
	array_shift($list);

	echo "\tstatic function getCodeName() {\n\t\treturn array(\n";
	foreach ($list as $row) {
		$row = explode(";", trim($row));
		if (sizeof($row) < 2)
			continue;
		echo "\t\t\t\t" . '"' . $row[1] . '" => "' . mb_convert_case($row[0], MB_CASE_TITLE, "utf-8") . '",' . "\n";
	}
	echo "\t\t\t\t);\n\t}\n\n";

	echo "}\n\n?" . ">\n";
}

main();
?>
