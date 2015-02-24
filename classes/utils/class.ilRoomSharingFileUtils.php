<?php

/**
 * Util-Class for files.
 *
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 */
class ilRoomSharingFileUtils {

	/**
	 * Returns true if the MimeType as an image type.
	 *
	 * @param string $a_mimeType
	 *
	 * @return boolean
	 */
	public static function isImageType($a_mimeType) {
		//Check for image format
		switch ($a_mimeType) {
			//Formats for type ".bmp"
			case "image/bmp":
			case "image/x-bmp":
			case "image/x-bitmap":
			case "image/x-xbitmap":
			case "image/x-win-bitmap":
			case "image/x-windows-bmp":
			case "image/x-ms-bmp":
			case "application/bmp":
			case "application/x-bmp":
			case "application/x-win-bitmap":
				//Formats for type ".png"
			case "image/png":
			case "application/png":
			case "application/x-png":
				//Formats for type ".jpg/.jpeg"
			case "image/jpeg":
			case "image/jpg":
			case "image/jp_":
			case "application/jpg":
			case "application/x-jpg":
			case "image/pjpeg":
			case "image/pipeg":
			case "image/vnd.swiftview-jpeg":
			case "image/x-xbitmap":
				//Formats for type ".gif"
			case "image/gif":
			case "image/x-xbitmap":
			case "image/gi_":
				return true;
			default:
				return false;
		}
	}


	/**
	 * Returns true if the MimeType as an PDF type.
	 *
	 * @param string $a_mimeType
	 *
	 * @return boolean
	 */
	public static function isPDFType($a_mimeType) {
		return "application/pdf" == $a_mimeType;
	}


	/**
	 * Returns true if the MimeType as an TXT type.
	 *
	 * @param string $a_mimeType
	 *
	 * @return boolean
	 */
	public static function isTXTType($a_mimeType) {
		switch ($a_mimeType) {
			case "text/plain":
			case "text/richtext":
			case "text/rtf":
				return true;
			default:
				return false;
		}
	}
}

?>