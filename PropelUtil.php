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

class PropelUtil {
	const PROTECTED_MAGIC = "\000*\000";

	static function getOmPkey($om) {
		$pkCriteriaMap = $om->buildPkeyCriteria()->getMap();
		reset($pkCriteriaMap);
		return key($pkCriteriaMap);
	}

	static function omListToArray($list, $fields, $type = BasePeer::TYPE_COLNAME) {
		$ret = array();
		if (empty($list))
			return $ret;

		reset($list);
		$om = current($list);
		$omPeer = $om->getPeer();
		$pk = $omPeer->translateFieldName(self::getOmPkey($om), BasePeer::TYPE_COLNAME, BasePeer::TYPE_FIELDNAME);

		$__fields = is_array($fields) ? $fields : array($fields);
		$fields = array();
		foreach ($__fields as $f)
			$fields[] = $omPeer->translateFieldName($f, $type, BasePeer::TYPE_FIELDNAME);

		foreach ($list as $om) {
			$om = (array)$om;
			if (count($fields) == 1) {
				$ret[$om[self::PROTECTED_MAGIC . $pk]] = $om[self::PROTECTED_MAGIC . $fields[0]];
				continue;
			}
			$tmp = array();
			foreach ($fields as $f)
				$tmp[$f] = $om[self::PROTECTED_MAGIC . $f];
			$ret[$om[self::PROTECTED_MAGIC . $pk]] = $tmp;
		}

		return $ret;
	}
}
?>
