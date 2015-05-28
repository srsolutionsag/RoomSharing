<?php

include_once("./Services/Form/classes/class.ilTextInputGUI.php");

/**
 * This class is used for text inputs.
 *
 * @author  Alexander Keller <a.k3ll3r@gmail.com>
 * @version $Id$
 */
class ilRoomSharingTextInputGUI extends ilTextInputGUI {

	/**
	 * Overwritten method of ilFormPropertyGUI. Deserializes the given POST data
	 * and sets the input field with it.
	 *
	 * @param type $a_data data that needs to be deserialized
	 */
	public function unserializeData($a_data) {
		$data = unserialize($a_data);

		// accept 0 string values, that were used to be handled as false in the 
		// original implemenation of this method
		if ($data || $data === 0) {
			$this->setValue($data);
		} else {
			$this->setValue(false);
		}
	}
}
