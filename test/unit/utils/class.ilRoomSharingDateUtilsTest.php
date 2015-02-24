<?php

chdir("../../../../../../../../"); // necessary for the include paths that are used within the classes to be tested
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingDateUtils.php");

use ilRoomSharingDateUtils as UTILS;

/**
 * Class ilRoomSharingDateUtilsTest
 *
 * @author Christopher Marks <Deamp_dev@yahoo.de>
 * @group unit
 */
class ilRoomSharingDateUtilsTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Sets up the fixture
	 * This method is called before a test is executed.
	 */
	public function setUp()
	{
		global $lng;

		$lng = $this->getMockBuilder("lng")->setMethods(array("txt"))->getMock();
		$lng->method("txt")->willReturn("test");
	}

	/**
	 * @covers ilRoomSharingDateUtils::getPrintedDateTime
	 */
	public function testGetPrintedDateTime()
	{
		$expected1 = "test., 31. test 2010, 14:02";
		$actual1 = UTILS::getPrintedDateTime(new DateTime("2010-12-31 14:02:02"));
		$this->assertEquals($expected1, $actual1);

		$expected2 = "test., 01. test 2015, 15:02";
		$actual2 = UTILS::getPrintedDateTime(new DateTime("2015-02-29 15:02:02"));
		$this->assertEquals($expected2, $actual2);

		$expected3 = "test., 29. test 2016, 16:02";
		$actual3 = UTILS::getPrintedDateTime(new DateTime("2016-02-29 16:02:02"));
		$this->assertEquals($expected3, $actual3);
	}

	/**
	 * @covers ilRoomSharingDateUtils::getPrintedDate
	 */
	public function testGetPrintedDate()
	{
		$expected1 = "test., 31. test 2010";
		$actual1 = UTILS::getPrintedDate(new DateTime("2010-12-31 14:02:02"));
		$this->assertEquals($expected1, $actual1);

		$expected2 = "test., 01. test 2015";
		$actual2 = UTILS::getPrintedDate(new DateTime("2015-02-29 14:02:02"));
		$this->assertEquals($expected2, $actual2);

		$expected3 = "test., 29. test 2016";
		$actual3 = UTILS::getPrintedDate(new DateTime("2016-02-29 14:02:02"));
		$this->assertEquals($expected3, $actual3);
	}

	/**
	 * @covers ilRoomSharingDateUtils::getPrintedTime
	 */
	public function testGetPrintedTime()
	{
		$expected1 = "14:02";
		$actual1 = UTILS::getPrintedTime(new DateTime("2010-12-31 14:02:02"));
		$this->assertEquals($expected1, $actual1);

		$expected2 = "15:02";
		$actual2 = UTILS::getPrintedTime(new DateTime("2015-02-29 15:02:02"));
		$this->assertEquals($expected2, $actual2);

		$expected3 = "16:02";
		$actual3 = UTILS::getPrintedTime(new DateTime("2016-02-29 16:02:02"));
		$this->assertEquals($expected3, $actual3);
	}

	/**
	 * @covers ilRoomSharingDateUtils::isEqualDay
	 */
	public function testIsEqualDay()
	{
		$this->assertTrue(UTILS::isEqualDay(new DateTime("2010-12-31 14:02:02"),
				new DateTime("2010-12-31 14:02:02")));


		$this->assertFalse(UTILS::isEqualDay(new DateTime("2010-11-30 14:02:02"),
				new DateTime("2011-12-30 14:02:02")));
		$this->assertFalse(UTILS::isEqualDay(new DateTime("2010-11-30 14:02:02"),
				new DateTime("2010-12-30 14:02:02")));
		$this->assertFalse(UTILS::isEqualDay(new DateTime("2010-12-30 14:02:02"),
				new DateTime("2010-12-31 14:02:02")));



		$this->assertTrue(UTILS::isEqualDay(new DateTime("2016-02-29 14:02:02"),
				new DateTime("2016-02-29 14:02:02")));
		$this->assertTrue(UTILS::isEqualDay(new DateTime("2014-02-29 14:02:02"),
				new DateTime("2014-02-29 14:02:02")));

		$this->assertFalse(UTILS::isEqualDay(new DateTime("2015-02-29 14:02:02"),
				new DateTime("2016-02-29 14:02:02")));
	}

}
