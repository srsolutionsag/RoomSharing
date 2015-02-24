<?php

chdir("../../../../../../../../"); // necessary for the include paths that are used within the classes to be tested
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/attributes/booking/class.ilRoomSharingBookingAttributes.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingPermissionUtils.php");

use ilRoomSharingAttributesConstants as ATTRC;

/**
 * Class ilRoomSharingBookingAttributesTest
 *
 * @group unit
 */
class ilRoomSharingBookingAttributesTest extends PHPUnit_Framework_TestCase
{
	private static $ilRoomSharingDatabaseStub;
	private static $ilRoomSharingBookingAttributes;

	/**
	 * Sets up the fixture
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		$test = new ilRoomSharingBookingAttributesTest;

		global $rssPermission;
		$rssPermission = $test->getMockBuilder('ilRoomSharingPermissionUtils')->disableOriginalConstructor()->getMock();
		$rssPermission->method('checkPrivilege')->willReturn(True);

		self::$ilRoomSharingDatabaseStub = $test->getMockBuilder('ilRoomSharingDatabase')
				->disableOriginalConstructor()->getMock();

		$attributes = Array(
			0 => Array("id" => 8, "name" => "test", "pool_id" => 1),
			1 => Array("id" => 9, "name" => "test2", "pool_id" => 1));

		self::$ilRoomSharingDatabaseStub->method("getAllBookingAttributes")->willReturn($attributes);
		self::$ilRoomSharingDatabaseStub->method("deleteAttributeBookingAssign")->willReturn(2);
		self::$ilRoomSharingDatabaseStub->method("deleteBookingAttribute")->willReturn(1);
		self::$ilRoomSharingDatabaseStub->method("insertBookingAttribute")->willReturn(1);

		self::$ilRoomSharingBookingAttributes = new ilRoomSharingBookingAttributes(1,
			self::$ilRoomSharingDatabaseStub);
	}

	/**
	 * @covers ilRoomSharingBookingAttributes::getAllAvailableAttributesNames
	 */
	public function testGetAllAvailableAttributesNames()
	{
		$expected = Array(0 => "test", 1 => "test2");
		$actual = self::$ilRoomSharingBookingAttributes->getAllAvailableAttributesNames();
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @covers ilRoomSharingBookingAttributes::getAllAvailableAttributesWithIdAndName
	 */
	public function testGetAllAvailableAttributesWithIdAndName()
	{
		$expected = Array(8 => "test", 9 => "test2");
		$actual = self::$ilRoomSharingBookingAttributes->getAllAvailableAttributesWithIdAndName();
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @covers ilRoomSharingBookingAttributes::renameAttribute
	 */
	public function testRenameAttribute()
	{
		self::$ilRoomSharingBookingAttributes->renameAttribute(9, "test3");
	}

	/**
	 * @expectedException ilRoomSharingAttributesException
	 * @expectedExceptionMessage rep_robj_xrs_attributes_change_not_allowed
	 * @covers ilRoomSharingBookingAttributes::renameAttribute
	 */
	public function testRenameAttributeInvalidPermission()
	{
		global $rssPermission;
		$rssPermission = $this->getMockBuilder('ilRoomSharingPermissionUtils')->disableOriginalConstructor()->getMock();
		$rssPermission->method('checkPrivilege')->willReturn(False);

		self::$ilRoomSharingBookingAttributes->renameAttribute(9, "test3");
	}

	/**
	 * @expectedException ilRoomSharingAttributesException
	 * @expectedExceptionMessage rep_robj_xrs_attribute_already_exists
	 * @covers ilRoomSharingBookingAttributes::renameAttribute
	 */
	public function testRenameAttributeNameNotFree()
	{
		self::$ilRoomSharingBookingAttributes->renameAttribute(8, "test2");
	}

	/**
	 * @expectedException ilRoomSharingAttributesException
	 * @expectedExceptionMessage rep_robj_xrs_wrong_attribute_name_provided
	 * @covers ilRoomSharingBookingAttributes::renameAttribute
	 */
	public function testRenameAttributeNameTooLong()
	{
		$name = "";
		for ($i = 0; $i <= ATTRC::MAX_NAME_LENGTH; $i++)
		{
			$name .= "1";
		}
		self::$ilRoomSharingBookingAttributes->renameAttribute(9, $name);
	}

	/**
	 * @expectedException ilRoomSharingAttributesException
	 * @expectedExceptionMessage rep_robj_xrs_fake_attribute_id_provided
	 * @covers ilRoomSharingBookingAttributes::renameAttribute
	 */
	public function testRenameAttributeIDnotPositive()
	{
		self::$ilRoomSharingBookingAttributes->renameAttribute(-1, "test3");
	}

	/**
	 * @covers ilRoomSharingBookingAttributes::deleteAttribute
	 */
	public function testDeleteAttribute()
	{
		$expected = 2;
		$actual = self::$ilRoomSharingBookingAttributes->deleteAttribute(8);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @expectedException ilRoomSharingAttributesException
	 * @expectedExceptionMessage rep_robj_xrs_fake_attribute_id_provided
	 * @covers ilRoomSharingBookingAttributes::deleteAttribute
	 */
	public function testDeleteAttributeIDnotPositive()
	{
		self::$ilRoomSharingBookingAttributes->deleteAttribute(-1);
	}

	/**
	 * @expectedException ilRoomSharingAttributesException
	 * @expectedExceptionMessage rep_robj_xrs_attributes_change_not_allowed
	 * @covers ilRoomSharingBookingAttributes::deleteAttribute
	 */
	public function testDeleteAttributeInvalidPermission()
	{
		global $rssPermission;
		$rssPermission = $this->getMockBuilder('ilRoomSharingPermissionUtils')->disableOriginalConstructor()->getMock();
		$rssPermission->method('checkPrivilege')->willReturn(False);

		self::$ilRoomSharingBookingAttributes->deleteAttribute(8);
	}

	/**
	 * @covers ilRoomSharingBookingAttributes::createAttribute
	 */
	public function testCreateAttribute()
	{
		self::$ilRoomSharingBookingAttributes->createAttribute("test3");
	}

	/**
	 * @expectedException ilRoomSharingAttributesException
	 * @expectedExceptionMessage rep_robj_xrs_attributes_change_not_allowed
	 * @covers ilRoomSharingBookingAttributes::createAttribute
	 */
	public function testCreateAttributeInvalidPermission()
	{
		global $rssPermission;
		$rssPermission = $this->getMockBuilder('ilRoomSharingPermissionUtils')->disableOriginalConstructor()->getMock();
		$rssPermission->method('checkPrivilege')->willReturn(False);

		self::$ilRoomSharingBookingAttributes->createAttribute("test");
	}

	/**
	 * @expectedException ilRoomSharingAttributesException
	 * @expectedExceptionMessage rep_robj_xrs_wrong_attribute_name_provided
	 * @covers ilRoomSharingBookingAttributes::createAttribute
	 */
	public function testCreateAttributeNameTooLong()
	{
		$name = "";
		for ($i = 0; $i <= ATTRC::MAX_NAME_LENGTH; $i++)
		{
			$name .= "1";
		}

		self::$ilRoomSharingBookingAttributes->createAttribute($name);
	}

	/**
	 * @expectedException ilRoomSharingAttributesException
	 * @expectedExceptionMessage rep_robj_xrs_attribute_already_exists
	 * @covers ilRoomSharingBookingAttributes::createAttribute
	 */
	public function testCreateAttributenameNotFree()
	{
		self::$ilRoomSharingBookingAttributes->createAttribute("test");
	}

}
