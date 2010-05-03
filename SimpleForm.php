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

require_once "BaseForm.php";
require_once "Locale.php";

abstract class SimpleForm extends BaseForm {
	function getSubmitAddText() {
		return str_pad(__("Add"), 25, " ", STR_PAD_BOTH);
	}

	function getSubmitUpdateText() {
		return str_pad(__("Update"), 25, " ", STR_PAD_BOTH);
	}

	function getSubmitAttr() {
		return array();
	}

	function getSubmitButton() {
		if (null === $this->request)
			return null;

		if (false === ($this->request instanceof SimpleFormRequest))
			return null;

		$submit = $this->getSubmitAttr();

		switch ($this->request->getState()) {
		case SimpleFormRequest::STATE_ADD:
			$submit['name'] = '__add';
			$submit['value'] = $this->getSubmitAddText();
			break;
		case SimpleFormRequest::STATE_UPDATE:
			$submit['name'] = '__update';
			$submit['value'] = $this->getSubmitUpdateText();
			break;
		default:
			throw new Exception("Undefined request state!");
		}
		return $submit;
	}
}

?>
