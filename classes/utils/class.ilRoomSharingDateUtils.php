<?php

/**
 * Class for the Date-Utils of the Plugin
 *
 * @author  Robert Heimsoth
 * @version $Id$
 *
 */
class ilRoomSharingDateUtils {

	/**
	 * Converts a given DateTime to a printable version.
	 * Example: 2014-10-10 12:20:30 -> Fr., 07. Apr 2014, 12:20
	 *
	 * @param DateTime $a_datetime DateTime which should be converted
	 *
	 * @return String DateTime in the format of the description
	 */
	public static function getPrintedDateTime($a_datetime) {
		return (self::getPrintedDate($a_datetime) . ', ' . self::getPrintedTime($a_datetime));
	}


	/**
	 * Gets a date in format "Weekday, Day. Month Year" of a given DateTime
	 *
	 * @param DateTime $a_datetime DateTime of the searched date
	 *
	 * @return String Date of the given DateTime
	 */
	public static function getPrintedDate($a_datetime) {
		global $lng;
		//Dayname (e.g. Mo)
		$date = $lng->txt(substr($a_datetime->format('D'), 0, 2) . '_short') . '., ';
		//Day (e.g. 07)
		$date .= $a_datetime->format('d') . '. ';
		//Monthname (e.g. Apr)
		$date .= $lng->txt('month_' . $a_datetime->format('m') . '_short') . ' ';
		//Year (e.g. 2014)
		$date .= $a_datetime->format('Y');

		return $date;
	}


	/**
	 * Gets a time in format Hour:Minute of a given DateTime
	 *
	 * @param DateTime $a_datetime DateTime of the searched time
	 *
	 * @return String Time of the given DateTime
	 */
	public static function getPrintedTime($a_datetime) {
		return $a_datetime->format("H:i");
	}


	/**
	 * Checks whether two day are equal
	 *
	 * @param DateTime $a_datetime1 DateTime with Date 1
	 * @param DateTime $a_datetime2 DateTime with Date 2 which should be compared with Date 1
	 *
	 * @return boolean true if equal, else false
	 */
	public static function isEqualDay($a_datetime1, $a_datetime2) {
		return ($a_datetime1->format('dmY') == $a_datetime2->format('dmY'));
	}
}

?>