<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingDateUtils.php");

/**
 * Util-Class for bookings
 *
 * @author Robert Heimsoth <rheimsoth@stud.hs-bremen.de>
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 */
class ilRoomSharingBookingUtils {

	/**
	 * Reads the date of the booking and converts it into a printed version.
	 *
	 * @param array $a_bookingData
	 *
	 * @return string Date
	 */
	public static function readBookingDate($a_bookingData) {
		$date_from = DateTime::createFromFormat("Y-m-d H:i:s", $a_bookingData['date_from']);
		$date_to = DateTime::createFromFormat("Y-m-d H:i:s", $a_bookingData['date_to']);

		$date = ilRoomSharingDateUtils::getPrintedDateTime($date_from);
		$date .= " - ";

		// Check whether the date_from differs from the date_to
		if (!ilRoomSharingDateUtils::isEqualDay($date_from, $date_to)) {
			//Display the date_to in the next line
			$date .= '<br>';
			$date .= ilRoomSharingDateUtils::getPrintedDate($date_to);
			$date .= ', ';
		}
		$date .= ilRoomSharingDateUtils::getPrintedTime($date_to);

		return $date;
	}
}

?>