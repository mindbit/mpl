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

class Locale {
	private static $directory;
	private static $current = array();
	private static $data = array();
	private static $escapeMap = array(
			'\\'	=> '\\\\',
			'\''	=> '\\\''
			);

	static function load($category, $locale) {
		assert(self::$directory !== null);
		assert(strspn($locale, "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_") == strlen($locale));
		if (!isset(self::$data[$category]))
			self::$data[$category] = array();
		switch ($category) {
		case LC_MESSAGES:
			$path = self::$directory . "/" . $locale . "/LC_MESSAGES/messages.pd";
			assert(is_readable($path));
			self::$data[LC_MESSAGES][$locale] = include($path);
		}
	}

	static function setDirectory($directory) {
		self::$directory = $directory;
	}

	static function set($category, $locale) {
		if (!isset(self::$data[$category][$locale]))
			self::load($category, $locale);
		self::$current[$category] = $locale;
	}

	static function getText($message) {
		assert(isset(self::$current[LC_MESSAGES]));
		if (isset(self::$data[LC_MESSAGES][self::$current[LC_MESSAGES]][$message]))
			return self::$data[LC_MESSAGES][self::$current[LC_MESSAGES]][$message];
		return $message;
	}

	static function moToPhp($stream) {
		$ret = "";
		$stream->seek(0);
		
		// check magic
		if ($stream->readIntLE() != ((0x9504 << 16) | (0x12de)))
			return null;
		
		// check revision
		if ($stream->readIntLE() !== 0)
			return null;

		// number of strings
		$n = $stream->readIntLE();

		// offset of table with original strings
		$o = $stream->readIntLE();

		// offset of table with translation strings
		$t = $stream->readIntLE();

		// actual conversion
		$sep = "<" . "?\nreturn array(\n\t\t";

		while ($n) {
			$stream->seek($o);
			$len = $stream->readIntLE();
			$off = $stream->readIntLE();
			$stream->seek($off);
			$txt1 = $stream->read($len);

			$stream->seek($t);
			$len = $stream->readIntLE();
			$off = $stream->readIntLE();
			$stream->seek($off);
			$txt2 = $stream->read($len);

			$ret .= $sep . "'" . strtr($txt1, self::$escapeMap) . "' => '" .
				strtr($txt2, self::$escapeMap) . "'";

			$sep = ",\n\t\t";
			$o += 8;
			$t += 8;
			$n--;
		}
		$ret .= "\n\t\t);\n?" . ">\n";

		return $ret;
	}
}

function __($message) {
	return Locale::getText($message);
}
?>
