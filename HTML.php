<?
/*
 * Mindbit PHP Library
 * Copyright (C) 2009 Mindbit SRL
 *
 * This library is free software; you can redistribute it and/or modify
 * it under the terms of version 2.1 of the GNU Lesser General Public
 * License as published by the Free Software Foundation.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

class HTML {
	// tag closing styles
	const TAG_CLOSE_NONE	= false;
	const TAG_CLOSE_HTML	= 1;
	const TAG_CLOSE_XHTML	= true;

	const TH				= "th";
	const TD				= "td";

	static function entities($string, $quote_style = ENT_COMPAT) {
		return htmlentities($string, $quote_style, "UTF-8");
	}

	static function attr($attr) {
		$str = "";
		if ($attr === null)
			return $str;
		foreach ($attr as $name => $value)
			$str .= " " . $name .
			(null === $value ? "" : '="' . self::entities($value) . '"');
		return $str;
	}

	static function tag($name, $attr = array(), $close = HTML::TAG_CLOSE_NONE) {
		$ret = "<" . $name . self::attr($attr);

		if ($close === HTML::TAG_CLOSE_NONE)
			return $ret . ">";

		if ($close === HTML::TAG_CLOSE_HTML)
			return $ret . "></" . $name . ">";

		if ($close === HTML::TAG_CLOSE_XHTML)
			return $ret . ">";
	}

	static function select($name, $selectedKey, $options, $isMultiple = false, 
				$extraOption = null, $attr = array(), $optStyleList = array()) {
		// WARNING: we deliberately use *no newlines* between generated tags,
		// as the output of this function may be used as a JavaScript string
		$attr["name"] = $name;
		$attr["id"] = $name;
		if ($isMultiple) {
			$attr["multiple"] = null;
			$attr["name"] = $name . "[]";
		}
		$readOnly = false;
		if (array_key_exists("readonly", $attr)) {
			unset($attr["readonly"]);
			$readOnly = true;
		}
		$ret = "";
		$selOpts = array();
		$lastSelected = 0;
		if (null !== $extraOption) {
			if (is_array($extraOption)) {
				reset($extraOption);
				list($extraValue, $extraText) = each($extraOption);
			} else {
				$extraValue = $extraOption;
				$extraText = '---';
			};
			$selected = is_array($selectedKey) ?
				in_array($extraValue, $selectedKey) : ($extraValue == $selectedKey);
			$ret .= '<option value="' . self::entities($extraValue) . '"' .
				($selected ? ' selected' : '') . '>' .
				self::entities($extraText) . '</option>';
			if ($selected)
				$lastSelected = count($selOpts);
			$selOpts[] = $selected;
		}
		$oldGroup = null;
		for (reset($options); null !== ($k = key($options)); next($options)) {
			$selected = is_array($selectedKey) ?
				in_array($k, $selectedKey) : ($k == $selectedKey);
			$optStyle = $optStyleList ? $optStyleList[$k] : null;
			if (is_array($options[$k])) {
				$optLabel = $options[$k]["label"];
				if (isset($options[$k]["style"]))
					$optStyle = $options[$k]["style"];
				if (isset($options[$k]["group"]) && $options[$k]["group"] != $oldGroup) {
					$ret .= '</optgroup><optgroup label="' . self::entities($options[$k]["group"]) . '">';
					$oldGroup = $options[$k]["group"];
				}
				if (!isset($options[$k]["group"]) && $oldGroup !== null) {
					$ret .= '</optgroup>';
					$oldGroup = null;
				}
			} else
				$optLabel = $options[$k];
			$ret .=
				'<option value="' . self::entities($k) . '" ' .
				($optStyle !== null ? "style='".$optStyle."'" : '' ) .
				($selected ? ' selected' : '') . '>' .
				self::entities($optLabel) . '</option>';
			if ($selected)
				$lastSelected = count($selOpts);
			$selOpts[] = $selected;
		}
		if ($oldGroup !== null)
			$ret .= '</optgroup>';
		do {
			if (!$readOnly)
				break;
			if (!$isMultiple) {
				reset($selOpts);
				$attr["onChange"] = "this.selectedIndex=" . $lastSelected;
				break;
			}
			$tmp = "";
			foreach ($selOpts as $idx => $selected)
				$tmp .= "this.options[" . $idx . "].selected=" .
				($selected ? "true" : "false") .  ";";
			$attr["onChange"] = $tmp;
		} while (false);
		$ret = self::tag("select", $attr) . $ret . '</select>';
		return $ret;
	}

	static function hidden($name, $value, $id = null) {
		$attr = array(
				"type"		=> "hidden",
				"name"		=> $name,
				"value"		=> $value
				);
		if ($id !== null)
			$attr["id"] = $id;

		return self::tag("input", $attr);
	}

	static function submitButton($submit) {
		$name = $submit["name"];
		$submit["name"] .= "button";
		if(!isset($submit["type"]))
			$submit["type"] = "submit";
		$ret = self::tag("input", $submit) . self::hidden($name, "1");
		return $ret;
	}

	static function tableRow($colTag, $rowAttr, $colAttr, $data) {
		if (!is_array($data)) {
			$data = func_get_args();
			array_shift($data); // $colTag
			array_shift($data); // $rowAttr
			array_shift($data); // $colAttr
		}
		$ret = HTML::tag("tr", $rowAttr);
		$i = 0;
		$colAttrEach =
			$colAttr !== null && isset($colAttr[0]) && is_array($colAttr[0]);
		foreach ($data as $col) {
			$ret .=
				HTML::tag($colTag, $colAttrEach ? $colAttr[$i] : $colAttr) .
				(is_array($col) ? implode("", $col) : HTML::entities($col)) .
				"</" . $colTag . ">";
		}
		$ret .= "</tr>";
		return $ret;
	}

	static function link($href, $text, $attr = null, $enabled = true) {
		$text = is_array($text) ? implode("", $text) : HTML::entities($text);
		if (!$enabled)
			return '<font color="gray">' . $text . '</font>';
		if ($attr === null)
			$attr = array();
		$attr["href"] = $href;
		return HTML::tag("a", $attr) . $text . "</a>";
	}
}
