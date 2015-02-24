<?php

require_once ("Services/Exceptions/classes/class.ilException.php");

/**
 * Exception Class for attributes administration.
 *
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 */
class ilRoomSharingAttributesException extends ilException
{
	/**
	 * Constructor of ilRoomSharingAttributesException.
	 * A message is not optional as in build in class ilException
	 *
	 * @param string $a_translation_key for the error-message
	 */
	public function __construct($a_translation_key)
	{
		parent::__construct($a_translation_key);
	}

}

?>
