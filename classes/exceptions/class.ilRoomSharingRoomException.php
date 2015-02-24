<?php

require_once ('./Services/Exceptions/classes/class.ilException.php');

/**
 * Class ilRoomSharingRoomException
 *
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 */
class ilRoomSharingRoomException extends ilException
{
	public function __construct($message)
	{
		parent::__construct($message);
	}

}

?>
