<?php

include_once("./Services/Form/classes/class.ilDateTimeInputGUI.php");
include_once("./Services/Table/interfaces/interface.ilTableFilterItem.php");

/**
 * Class ilRoomSharingTimeInputGUI
 *
 * @author  Alexander Keller <a.k3ll3r@gmail.com>
 * @version $Id$
 *
 */
class ilRoomSharingTimeInputGUI extends ilDateTimeInputGUI {

	/**
	 * Constructor of ilRoomSharingTimeInputGUI; an own take on of the
	 * ilDateTimeInputGUI. It is solely used for time inputs.
	 *
	 * @param    string $a_title   Title
	 * @param    string $a_postvar Post Variable
	 */
	function __construct($a_title = "", $a_postvar = "") {
		parent::__construct($a_title, $a_postvar);
	}


	/**
	 * Set the Display Mode. This class can only be operated in MODE_SELECT
	 * which is why this method will constantly set the Display Mode to
	 * MODE_SELECT.
	 *
	 * @param    int $mode Display Mode
	 */
	function setMode($mode) {
		// ignore the $mode parameter
		$this->mode = self::MODE_SELECT;
	}


	/**
	 * Check input, strip slashes etc. set alert, if input is not ok.
	 *
	 * @return    boolean        Input ok, true/false
	 */
	function checkInput() {
		global $ilUser;

		// for the date use the UNIX time stamp "0", since we don't care about it
		$dt['mday'] = 1;
		$dt['mon'] = 1;
		$dt['year'] = 1970;

		$post = $_POST[$this->getPostVar()];

		// empty date valid with input field
		if (!$this->getRequired() && $this->getMode() === self::MODE_INPUT && $post["date"] === "") {
			return true;
		}

		$post["time"]["h"] = ilUtil::stripSlashes($post["time"]["h"]);
		$post["time"]["m"] = ilUtil::stripSlashes($post["time"]["m"]);
		$post["time"]["s"] = ilUtil::stripSlashes($post["time"]["s"]);
		$dt['hours'] = (int)$post['time']['h'];
		$dt['minutes'] = (int)$post['time']['m'];
		$dt['seconds'] = (int)$post['time']['s'];

		// very basic validation
		if (($dt['hours'] > 23 || $dt['minutes'] > 59 || $dt['seconds'] > 59)) {
			$dt = false;
		}

		$date = new ilDateTime($dt, IL_CAL_FKT_GETDATE, $ilUser->getTimeZone());
		$this->setDate($date);

		// post values used to be overwritten anyways - cannot change behaviour
		$_POST[$this->getPostVar()]['date'] = $date->get(IL_CAL_FKT_DATE, 'Y-m-d', $ilUser->getTimeZone());
		$_POST[$this->getPostVar()]['time'] = $date->get(IL_CAL_FKT_DATE, 'H:i:s', $ilUser->getTimeZone());

		return (bool)$dt;
	}
}

?>