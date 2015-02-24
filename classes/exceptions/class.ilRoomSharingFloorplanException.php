<?php

require_once('./Services/Exceptions/classes/class.ilException.php');

/**
 * Class ilRoomSharingFloorplanException
 *
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 */
class ilRoomSharingFloorplanException extends ilException {

	public function __construct($message) {
		parent::__construct($message);
	}
}

?>
