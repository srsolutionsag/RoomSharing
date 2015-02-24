<?php

chdir("../../../../../../../../"); // necessary for the include paths that are used within the classes to be tested
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/attributes/room/class.ilRoomSharingRoomAttributes.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingPermissionUtils.php");

use ilRoomSharingAttributesConstants as ATTRC;

/**
 * Class ilRoomSharingRoomAttributesTest
 *
 * @group unit
 */
class ilRoomSharingRoomAttributesTest extends PHPUnit_Framework_TestCase
{
	private static $ilRoomSharingDatabaseStub;
	private static $ilRoomSharingRoomAttributes;

	/**
	 * Sets up the fixture
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		$test = new ilRoomSharingRoomAttributesTest;

		global $rssPermission;
		$rssPermission = $test->getMockBuilder('ilRoomSharingPermissionUtils')->disableOriginalConstructor()->getMock();
		$rssPermission->method('checkPrivilege')->willReturn(True);

		self::$ilRoomSharingDatabaseStub = $test->getMockBuilder('ilRoomSharingDatabase')
				->disableOriginalConstructor()->getMock();

		$attributes = Array(
			0 => Array("id" => 8, "name" => "test", "pool_id" => 1),
			1 => Array("id" => 9, "name" => "test2", "pool_id" => 1));

		self::$ilRoomSharingDatabaseStub->method("getAllRoomAttributes")->willReturn($attributes);
		self::$ilRoomSharingDatabaseStub->method("deleteAttributeRoomAssign")->willReturn(2);
		self::$ilRoomSharingDatabaseStub->method("deleteRoomAttribute")->willReturn(1);
		self::$ilRoomSharingDatabaseStub->method("insertRoomAttribute")->willReturn(1);

		self::$ilRoomSharingRoomAttributes = new ilRoomSharingRoomAttributes(1,
			self::$ilRoomSharingDatabaseStub);
	}

	/**
	 * @covers ilRoomSharingRoomAttributes::getAllAvailableAttributesNames
	 */
	public function testGetAllAvailableAttributesNames()
	{
		$expected = Array(0 => "test", 1 => "test2");
		$actual = self::$ilRoomSharingRoomAttributes->getAllAvailableAttributesNames();
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @covers ilRoomSharingRoomAttributes::getAllAvailableAttributesWithIdAndName
	 */
	public function testGetAllAvailableAttributesWithIdAndName()
	{
		$expected = Array(8 => "test", 9 => "test2");
		$actual = self::$ilRoomSharingRoomAttributes->getAllAvailableAttributesWithIdAndName();
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @covers ilRoomSharingRoomAttributes::renameAttribute
	 */
	public function testRenameAttribute()
	{
		self::$ilRoomSharingRoomAttributes->renameAttribute(9, "test3");
	}

	/**
	 * @expectedException ilRoomSharingAttributesException
	 * @expectedExceptionMessage rep_robj_xrs_attributes_change_not_allowed
	 * @covers ilRoomSharingRoomAttributes::renameAttribute
	 */
	public function testRenameAttributeInvalidPermission()
	{
		global $rssPermission;
		$rssPermission = $this->getMockBuilder('ilRoomSharingPermissionUtils')->disableOriginalConstructor()->getMock();
		$rssPermission->method('checkPrivilege')->willReturn(False);

		self::$ilRoomSharingRoomAttributes->renameAttribute(9, "test3");
	}

	/**
	 * @expectedException ilRoomSharingAttributesException
	 * @expectedExceptionMessage rep_robj_xrs_attribute_already_exists
	 * @covers ilRoomSharingRoomAttributes::renameAttribute
	 */
	public function testRenameAttributeNameNotFree()
	{
		self::$ilRoomSharingRoomAttributes->renameAttribute(8, "test2");
	}

	/**
	 * @expectedException ilRoomSharingAttributesException
	 * @expectedExceptionMessage rep_robj_xrs_wrong_attribute_name_provided
	 * @covers ilRoomSharingRoomAttributes::renameAttribute
	 */
	public function testRenameAttributeNameTooLong()
	{
		$name = "";
		for ($i = 0; $i <= ATTRC::MAX_NAME_LENGTH; $i++)
		{
			$name .= "1";
		}
		self::$ilRoomSharingRoomAttributes->renameAttribute(9, $name);
	}

	/**
	 * @expectedException ilRoomSharingAttributesException
	 * @expectedExceptionMessage rep_robj_xrs_fake_attribute_id_provided
	 * @covers ilRoomSharingRoomAttributes::renameAttribute
	 */
	public function testRenameAttributeIDnotPositive()
	{
		self::$ilRoomSharingRoomAttributes->renameAttribute(-1, "test3");
	}

	/**
	 * @covers ilRoomSharingRoomAttributes::deleteAttribute
	 */
	public function testDeleteAttribute()
	{
		$expected = 2;
		$actual = self::$ilRoomSharingRoomAttributes->deleteAttribute(8);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @expectedException ilRoomSharingAttributesException
	 * @expectedExceptionMessage rep_robj_xrs_fake_attribute_id_provided
	 * @covers ilRoomSharingRoomAttributes::deleteAttribute
	 */
	public function testDeleteAttributeIDnotPositive()
	{
		self::$ilRoomSharingRoomAttributes->deleteAttribute(-1);
	}

	/**
	 * @expectedException ilRoomSharingAttributesException
	 * @expectedExceptionMessage rep_robj_xrs_attributes_change_not_allowed
	 * @covers ilRoomSharingRoomAttributes::deleteAttribute
	 */
	public function testDeleteAttributeInvalidPermission()
	{
		global $rssPermission;
		$rssPermission = $this->getMockBuilder('ilRoomSharingPermissionUtils')->disableOriginalConstructor()->getMock();
		$rssPermission->method('checkPrivilege')->willReturn(False);

		self::$ilRoomSharingRoomAttributes->deleteAttribute(8);
	}

	/**
	 * @covers ilRoomSharingRoomAttributes::createAttribute
	 */
	public function testCreateAttribute()
	{
		self::$ilRoomSharingRoomAttributes->createAttribute("test3");
	}

	/**
	 * @expectedException ilRoomSharingAttributesException
	 * @expectedExceptionMessage rep_robj_xrs_attributes_change_not_allowed
	 * @covers ilRoomSharingRoomAttributes::createAttribute
	 */
	public function testCreateAttributeInvalidPermission()
	{
		global $rssPermission;
		$rssPermission = $this->getMockBuilder('ilRoomSharingPermissionUtils')->disableOriginalConstructor()->getMock();
		$rssPermission->method('checkPrivilege')->willReturn(False);

		self::$ilRoomSharingRoomAttributes->createAttribute("test");
	}

	/**
	 * @expectedException ilRoomSharingAttributesException
	 * @expectedExceptionMessage rep_robj_xrs_wrong_attribute_name_provided
	 * @covers ilRoomSharingRoomAttributes::createAttribute
	 */
	public function testCreateAttributeNameTooLong()
	{
		$name = "";
		for ($i = 0; $i <= ATTRC::MAX_NAME_LENGTH; $i++)
		{
			$name .= "1";
		}

		self::$ilRoomSharingRoomAttributes->createAttribute($name);
	}

	/**
	 * @expectedException ilRoomSharingAttributesException
	 * @expectedExceptionMessage rep_robj_xrs_attribute_already_exists
	 * @covers ilRoomSharingRoomAttributes::createAttribute
	 */
	public function testCreateAttributenameNotFree()
	{
		self::$ilRoomSharingRoomAttributes->createAttribute("test");
	}

}
