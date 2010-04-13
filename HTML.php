<?
class HTML {
	public static function entities($string, $quote_style = ENT_COMPAT) {
		return htmlentities($string, $quote_style, "UTF-8");
	}

	public static function attr($attr) {
		$str = "";
		foreach ($attr as $name => $value)
			$str .= " " . $name .
			(null === $value ? "" : '="' . self::entities($value) . '"');
		return $str;
	}

	public static function tag($name, $attr = array(), $void = false) {
		$ret = "<" . $name . self::attr($attr) . ($void ? "/>" : ">");
		return $ret;
	}
}
