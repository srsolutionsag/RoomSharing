<?php

chdir("../../../../../../../../"); // necessary for the include paths that are used within the classes to be tested
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingNumericUtils.php");

use ilRoomSharingNumericUtils as UTILS;

/**
 * Class ilRoomSharingNumericUtilsTest
 *
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 * @group unit
 */
class ilRoomSharingNumericUtilsTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @covers ilRoomSharingNumericUtils::isPositiveNumber
	 */
	public function testIsPositiveNumber()
	{
		$this->assertTrue(UTILS::isPositiveNumber(1));
		$this->assertTrue(UTILS::isPositiveNumber('1'));
		$this->assertTrue(UTILS::isPositiveNumber("1"));
		$this->assertTrue(UTILS::isPositiveNumber(PHP_INT_MAX));
		$this->assertTrue(UTILS::isPositiveNumber('0x4'));
		$this->assertTrue(UTILS::isPositiveNumber(0.01));
		$this->assertTrue(UTILS::isPositiveNumber(1.5));
		$this->assertTrue(UTILS::isPositiveNumber(PHP_INT_MAX + 0.234));

		$this->assertTrue(UTILS::isPositiveNumber(1, false));
		$this->assertTrue(UTILS::isPositiveNumber('1', false));
		$this->assertTrue(UTILS::isPositiveNumber("1", false));
		$this->assertTrue(UTILS::isPositiveNumber(PHP_INT_MAX, false));
		$this->assertTrue(UTILS::isPositiveNumber('0x4', false));
		$this->assertTrue(UTILS::isPositiveNumber(0.01), false);
		$this->assertTrue(UTILS::isPositiveNumber(1.5, false));
		$this->assertTrue(UTILS::isPositiveNumber(PHP_INT_MAX + 0.234, false));

		$this->assertTrue(UTILS::isPositiveNumber(0, true));
		$this->assertTrue(UTILS::isPositiveNumber(0.0, true));
		$this->assertTrue(UTILS::isPositiveNumber(1, true));
		$this->assertTrue(UTILS::isPositiveNumber('1', true));
		$this->assertTrue(UTILS::isPositiveNumber("1", true));
		$this->assertTrue(UTILS::isPositiveNumber(PHP_INT_MAX, true));
		$this->assertTrue(UTILS::isPositiveNumber('0x4', true));
		$this->assertTrue(UTILS::isPositiveNumber(0.01), true);
		$this->assertTrue(UTILS::isPositiveNumber(1.5, true));
		$this->assertTrue(UTILS::isPositiveNumber(PHP_INT_MAX + 0.234, true));
	}

	/**
	 * @covers ilRoomSharingNumericUtils::isPositiveNumber
	 */
	public function testNotPositiveNumber()
	{
		$this->assertFalse(UTILS::isPositiveNumber("a"));
		$this->assertFalse(UTILS::isPositiveNumber("a"), true);
		$this->assertFalse(UTILS::isPositiveNumber("a"), false);
		$this->assertFalse(UTILS::isPositiveNumber(-1));
		$this->assertFalse(UTILS::isPositiveNumber(-1), true);
		$this->assertFalse(UTILS::isPositiveNumber(-1), false);
		$this->assertFalse(UTILS::isPositiveNumber(-0.2323));
		$this->assertFalse(UTILS::isPositiveNumber(-0.2323), true);
		$this->assertFalse(UTILS::isPositiveNumber(-0.2323), false);
		$this->assertFalse(UTILS::isPositiveNumber(-0.00000000001));
		$this->assertFalse(UTILS::isPositiveNumber(-0.00000000001), true);
		$this->assertFalse(UTILS::isPositiveNumber(-0.00000000001), false);
		$this->assertFalse(UTILS::isPositiveNumber(PHP_INT_MAX * -1));
		$this->assertFalse(UTILS::isPositiveNumber(PHP_INT_MAX * -1), true);
		$this->assertFalse(UTILS::isPositiveNumber(PHP_INT_MAX * -1), false);
		$this->assertFalse(UTILS::isPositiveNumber((PHP_INT_MAX + 0.234) * -1));
		$this->assertFalse(UTILS::isPositiveNumber((PHP_INT_MAX + 0.234) * -1), true);
		$this->assertFalse(UTILS::isPositiveNumber((PHP_INT_MAX + 0.234) * -1), false);
	}

	/**
	 * @covers ilRoomSharingNumericUtils::allNumbersPositive
	 */
	public function testAllNumbersPositive()
	{
		$numbers = array(1, '1', "1", PHP_INT_MAX, '0x4', 0.01, 1.5, PHP_INT_MAX + 0.234);
		$this->assertTrue(UTILS::allNumbersPositive($numbers));
		$this->assertTrue(UTILS::allNumbersPositive($numbers, false));
		$this->assertTrue(UTILS::allNumbersPositive($numbers, true));

		$numbersWithZero = array(0, 0.0, 1, '1', "1", PHP_INT_MAX, '0x4', 0.01, 1.5, PHP_INT_MAX + 0.234);
		$this->assertTrue(UTILS::allNumbersPositive($numbersWithZero, true));
	}

	/**
	 * @covers ilRoomSharingNumericUtils::allNumbersPositive
	 */
	public function testNotAllNumbersPositive()
	{
		$ZERO1 = 0;
		$ZERO2 = 0.0;
		$NEG1 = -1;
		$NEG2 = -0.0342;

		$numbers1 = array(1, '1', "1", PHP_INT_MAX, $ZERO1, '0x4', 0.01, 1.5, PHP_INT_MAX + 0.234);
		$this->assertFalse(UTILS::allNumbersPositive($numbers1));
		$this->assertFalse(UTILS::allNumbersPositive($numbers1, false));

		$numbers2 = array(1, '1', "1", PHP_INT_MAX, '0x4', 0.01, $ZERO2, 1.5, PHP_INT_MAX + 0.234);
		$this->assertFalse(UTILS::allNumbersPositive($numbers2));
		$this->assertFalse(UTILS::allNumbersPositive($numbers2, false));

		$numbers3 = array(1, '1', $NEG1, "1", PHP_INT_MAX, '0x4', 0.01, 1.5, PHP_INT_MAX + 0.234);
		$this->assertFalse(UTILS::allNumbersPositive($numbers3));
		$this->assertFalse(UTILS::allNumbersPositive($numbers3, false));
		$this->assertFalse(UTILS::allNumbersPositive($numbers3, true));

		$numbers4 = array(1, '1', "1", PHP_INT_MAX, '0x4', 0.01, 1.5, $NEG2, PHP_INT_MAX + 0.234);
		$this->assertFalse(UTILS::allNumbersPositive($numbers4));
		$this->assertFalse(UTILS::allNumbersPositive($numbers4, false));
		$this->assertFalse(UTILS::allNumbersPositive($numbers4, true));
	}

}
