<?php

chdir("../../../../../../../../"); // necessary for the include paths that are used within the classes to be tested
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingStringUtils.php");

use ilRoomSharingStringUtils as UTILS;

/**
 * Class ilRoomSharingStringUtilsTest
 *
 * @author Christopher Marks <Deamp_dev@yahoo.de>
 * @group unit
 */
class ilRoomSharingStringUtilsTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @covers ilRoomSharingStringUtils::startsWith
	 */
	public function testStartsWith()
	{
		$this->assertTrue(UTILS::startsWith("testString", "test"));
		$this->assertTrue(UTILS::startsWith("0010String", "0010"));
		$this->assertTrue(UTILS::startsWith("_()String", "_()"));
		$this->assertTrue(UTILS::startsWith("!String", "!"));
		$this->assertTrue(UTILS::startsWith("TESTString", "TEST"));
		$this->assertTrue(UTILS::startsWith("__String", "__"));
		$this->assertTrue(UTILS::startsWith("\nString", "\n"));
		$this->assertTrue(UTILS::startsWith("0.2String", "0.2"));
		$this->assertTrue(UTILS::startsWith("0x5String", "0x5"));
		$this->assertTrue(UTILS::startsWith("testString", "testString"));
		$this->assertTrue(UTILS::startsWith("a", "a"));
		$this->assertTrue(UTILS::startsWith("a   ", "a"));
		$this->assertTrue(UTILS::startsWith(" a   ", " a"));
		$this->assertTrue(UTILS::startsWith(" a   ", " a "));

		$this->assertFalse(UTILS::startsWith("testString", "String"));
		$this->assertFalse(UTILS::startsWith("0010String", "010"));
		$this->assertFalse(UTILS::startsWith("_()String", "_)"));
		$this->assertFalse(UTILS::startsWith("TESTString", "TESTs"));
		$this->assertFalse(UTILS::startsWith("\nString", "St"));
		$this->assertFalse(UTILS::startsWith("", "String"));
		$this->assertFalse(UTILS::startsWith("String", ""));
		$this->assertFalse(UTILS::startsWith("", ""));


		$this->assertFalse(UTILS::startsWith("1String", 1));
		$this->assertFalse(UTILS::startsWith("0.2String", 0.2));
		$this->assertFalse(UTILS::startsWith("0x5String", 0x5));

		$this->assertFalse(UTILS::startsWith(123, 1));
		$this->assertFalse(UTILS::startsWith(0.2314, 0.2));
		$this->assertFalse(UTILS::startsWith(0x5, 0x5));

		$this->assertFalse(UTILS::startsWith(null, "0x5"));
		$this->assertFalse(UTILS::startsWith("null", "0x5"));
		$this->assertFalse(UTILS::startsWith("0x5", null));
		$this->assertFalse(UTILS::startsWith("0x5", "null"));
		$this->assertFalse(UTILS::startsWith(null, null));
		$this->assertFalse(UTILS::startsWith(111, 111));
	}

}
