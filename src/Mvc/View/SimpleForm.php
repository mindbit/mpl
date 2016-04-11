<?php
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

namespace Mindbit\Mpl\Mvc\View;

use Mindbit\Mpl\Mvc\View\BaseForm;
use Mindbit\Mpl\Locale\Locale;

abstract class SimpleForm extends BaseForm {
	const ACTION_SELF_CLOSE		= 0x01;
	const ACTION_REFRESH_PARENT	= 0x02;
	const ACTION_DEFAULT		= 0x03; // ACTION_SELF_CLOSE | ACTION_REFRESH_PARENT

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

	function getWindowOpener($obj) {
		return null;
	}
	function refreshParent($id = false, $class = false) {
		if ($id === false)
			$id = $this->om === null ? null: PropelUtil::getOmPkeyValue($this->om);
		if ($class === false)
			$class = get_class($this);
		?>
		<script type="text/javascript">
		var parentForm;

		if (window.opener && window.opener.document.forms[0]) {
			parentForm = window.opener.document.forms[0];
			if (parentForm.__refreshId)
				parentForm.__refreshId.value = "<?= $id?>";
			if (parentForm.__refreshClass)
				parentForm.__refreshClass.value = "<?= $class?>";
			parentForm.submit();
		}
		</script>
		<?php
	}

	function selfClose() {
		?>
		<script type="text/javascript">
		self.close();
		</script>
		<?php
	}

	function refreshTags() {
		echo HTML::hidden("__refreshId", "");
		echo HTML::hidden("__refreshClass", "");
	}

	function onSuccessfulOperation($operation, $action) {
		$err = $this->request->getErrors();
		if ($operation != $this->request->getOperationType() || !empty($err))
			return false;
		if ($action & self::ACTION_REFRESH_PARENT)
			$this->refreshParent();
		if ($action & self::ACTION_SELF_CLOSE)
			$this->selfClose();
		return true;
	}

	function onSuccessfulAdd($action = self::ACTION_DEFAULT) {
		return $this->onSuccessfulOperation(OmRequest::OPERATION_ADD, $action);
	}

	function onSuccessfulUpdate($action = self::ACTION_DEFAULT) {
		return $this->onSuccessfulOperation(OmRequest::OPERATION_UPDATE, $action);
	}

	function onSuccessfulRemove($action = self::ACTION_DEFAULT) {
		return $this->onSuccessfulOperation(OmRequest::OPERATION_REMOVE, $action);
	}
}
