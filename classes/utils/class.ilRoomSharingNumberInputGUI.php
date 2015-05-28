<?php

include_once("./Services/Form/classes/class.ilNumberInputGUI.php");

/**
 * This class is used for number inputs throughout the whole RoomSharing-Module.
 *
 * @author  Alexander Keller <a.k3ll3r@gmail.com>
 * @version $Id$
 */
class ilRoomSharingNumberInputGUI extends ilNumberInputGUI {

	/**
	 * Overwritten method from ilNumberInputGUI. This method is primarily used
	 * to make use of a different template.
	 *
	 * @global type $lng
	 * @return type
	 */
	public function render() {
		global $lng;

		// own template (the number is aligned left)
		$tpl = new ilTemplate("tpl.room_prop_number.html", true, true, "Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing");

		if (strlen($this->getValue())) {
			$tpl->setCurrentBlock("prop_number_propval");
			$tpl->setVariable("PROPERTY_VALUE", ilUtil::prepareFormOutput($this->getValue()));
			$tpl->parseCurrentBlock();
		}
		$tpl->setCurrentBlock("prop_number");
		$tpl->setVariable("POST_VAR", $this->getPostVar());
		$tpl->setVariable("ID", $this->getFieldId());
		$tpl->setVariable("SIZE", $this->getSize());
		$tpl->setVariable("MAXLENGTH", $this->getMaxLength());
		if (strlen($this->getSuffix())) {
			$tpl->setVariable("INPUT_SUFFIX", $this->getSuffix());
		}
		if ($this->getDisabled()) {
			$tpl->setVariable("DISABLED", " disabled=\"disabled\"");
		}

		// constraints
		if ($this->areDecimalsAllowed() && $this->getDecimals() > 0) {
			$constraints = $lng->txt("form_format") . ": ###." . str_repeat("#", $this->getDecimals());
			$delim = ", ";
		}
		if ($this->getMaxValue() !== false) {
			$constraints .= $delim . $lng->txt("rep_robj_xrs_at_most") . ": " . (($this->maxvalueShouldBeLess()) ? "&lt; " : "")
				. $this->getMaxValue();
			$delim = ", ";
		}

		// append the constraint-text at the end of the input, if given
		if ($constraints !== "") {
			$tpl->setVariable("TXT_NUMBER_CONSTRAINTS", $constraints);
		}

		$tpl->parseCurrentBlock();

		return $tpl->get();
	}


	/**
	 * This method overwrites the one found in ilNumberInputGUI. It is used
	 * to implement an own check algorithm for the number input.
	 *
	 * @return    boolean    true, if the input is ok; false otherwise
	 */
	public function checkInput() {
		global $lng;
		$_POST[$this->getPostVar()] = ilUtil::stripSlashes($_POST[$this->getPostVar()]);

		// Is an input required but the input itself empty?
		if ($this->getRequired() && trim($_POST[$this->getPostVar()]) === "") {
			$this->setAlert($lng->txt("msg_input_is_required"));

			return false;
		}

		// Is the input numeric?
		if (trim($_POST[$this->getPostVar()]) !== ""
			&& !is_numeric(str_replace(',', '.', $_POST[$this->getPostVar()]))
		) {
			$this->setAlert($lng->txt("form_msg_numeric_value_required"));

			return false;
		}

		// Check if the input is lower than the given minimum
		if (trim($_POST[$this->getPostVar()]) !== ""
			&& $this->getMinValue() !== false
			&& $_POST[$this->getPostVar()] < $this->getMinValue()
		) {
			$this->setAlert($lng->txt("form_msg_value_too_low"));

			return false;
		}

		// Check if the input is greater than the given maximum
		if (trim($_POST[$this->getPostVar()]) !== ""
			&& $this->getMaxValue() !== false
			&& $_POST[$this->getPostVar()] > $this->getMaxValue()
		) {
			$this->setAlert($lng->txt("form_msg_value_too_high"));

			return false;
		}

		// check subitems if present
		return $this->checkSubItemsInput();
	}


	/**
	 * Overwritten method of ilFormPropertyGUI. Deserializes the given POST data
	 * and sets the input field with it.
	 *
	 * @param type $a_data data that needs to be deserialized
	 */
	public function unserializeData($a_data) {
		$data = unserialize($a_data);

		// accept 0 float values, that were used to be handled as false in the
		// original implemenation of this method
		if ($data || $data === 0) {
			$this->setValue($data);
		} else {
			$this->setValue(false);
		}
	}
}
