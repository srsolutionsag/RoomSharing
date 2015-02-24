<?php

/**
 * Util-Class for string operations
 *
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 */
class ilRoomSharingStringUtils {

	/**
	 * Returns true if the given string has the given prefix.
	 *
	 * @param string $a_string
	 * @param string $a_prefix
	 *
	 * @return bool
	 */
	public static function startsWith($a_string, $a_prefix) {
		$rVal = false;
		if (!empty($a_string) && !empty($a_prefix)) {
			// is faster than 'strncmp'
			$rVal = substr($a_string, 0, strlen($a_prefix)) === $a_prefix;
		}

		return $rVal;
	}
}

?>