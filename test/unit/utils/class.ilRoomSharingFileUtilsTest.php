<?php

chdir("../../../../../../../../"); // necessary for the include paths that are used within the classes to be tested
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingFileUtils.php");

use ilRoomSharingFileUtils as UTILS;

/**
 * Class ilRoomSharingFileUtilsTest
 *
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 * @group unit
 */
class ilRoomSharingFileUtilsTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @covers ilRoomSharingFileUtils::isImageType
	 */
	public function testIsImageType()
	{
		self::assertTrue(UTILS::isImageType("image/bmp"));
		self::assertTrue(UTILS::isImageType("image/x-bmp"));
		self::assertTrue(UTILS::isImageType("image/x-bitmap"));
		self::assertTrue(UTILS::isImageType("image/x-xbitmap"));
		self::assertTrue(UTILS::isImageType("image/x-win-bitmap"));
		self::assertTrue(UTILS::isImageType("image/x-windows-bmp"));
		self::assertTrue(UTILS::isImageType("image/x-ms-bmp"));
		self::assertTrue(UTILS::isImageType("application/bmp"));
		self::assertTrue(UTILS::isImageType("application/x-bmp"));
		self::assertTrue(UTILS::isImageType("application/x-win-bitmap"));
		//Formats for type ".png"
		self::assertTrue(UTILS::isImageType("image/png"));
		self::assertTrue(UTILS::isImageType("application/png"));
		self::assertTrue(UTILS::isImageType("application/x-png"));
		//Formats for type ".jpg/.jpeg"
		self::assertTrue(UTILS::isImageType("image/jpeg"));
		self::assertTrue(UTILS::isImageType("image/jpg"));
		self::assertTrue(UTILS::isImageType("image/jp_"));
		self::assertTrue(UTILS::isImageType("application/jpg"));
		self::assertTrue(UTILS::isImageType("application/x-jpg"));
		self::assertTrue(UTILS::isImageType("image/pjpeg"));
		self::assertTrue(UTILS::isImageType("image/pipeg"));
		self::assertTrue(UTILS::isImageType("image/vnd.swiftview-jpeg"));
		self::assertTrue(UTILS::isImageType("image/x-xbitmap"));
		//Formats for type ".gif"
		self::assertTrue(UTILS::isImageType("image/gif"));
		self::assertTrue(UTILS::isImageType("image/x-xbitmap"));
		self::assertTrue(UTILS::isImageType("image/gi_"));

		// Negative cases
		self::assertFalse(UTILS::isImageType("file/pdf"));
		self::assertFalse(UTILS::isImageType(""));
		self::assertFalse(UTILS::isImageType(12));
		self::assertFalse(UTILS::isImageType(NULL));
		self::assertFalse(UTILS::isImageType(1));
	}

	/**
	 * @covers ilRoomSharingFileUtils::isPDFType
	 */
	public function testIsPDFType()
	{
		self::assertTrue(UTILS::isPDFType("application/pdf"));

		// Negative cases
		self::assertFalse(UTILS::isPDFType("file/podf"));
		self::assertFalse(UTILS::isPDFType(""));
		self::assertFalse(UTILS::isPDFType(12));
		self::assertFalse(UTILS::isPDFType(NULL));
		self::assertFalse(UTILS::isPDFType(1));
	}

	/**
	 * @covers ilRoomSharingFileUtils::isTXTType
	 */
	public function testIsTXTType()
	{
		self::assertTrue(UTILS::isTXTType("text/plain"));
		self::assertTrue(UTILS::isTXTType("text/richtext"));
		self::assertTrue(UTILS::isTXTType("text/rtf"));

		// Negative cases
		self::assertFalse(UTILS::isTXTType("file/podf"));
		self::assertFalse(UTILS::isTXTType(""));
		self::assertFalse(UTILS::isTXTType(12));
		self::assertFalse(UTILS::isTXTType(NULL));
		self::assertFalse(UTILS::isTXTType(1));
	}

}
