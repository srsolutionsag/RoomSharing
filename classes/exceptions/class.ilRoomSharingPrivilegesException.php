<?php

require_once("Services/Exceptions/classes/class.ilException.php");

/**
 * Exception Class for privileges
 *
 * @author Robert Heimsoth <rheimsoth@stud.hs-bremen.de>
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 */
class ilRoomSharingPrivilegesException extends ilException {

	/**
	 * Constructor
	 * A message is not optional as in build in class Exception
	 *
	 * @param string $a_message Error-message
	 */
	public function __construct($a_message) {
		//$this->message = $a_message;
		parent::__construct($a_message);
	}
}

?>