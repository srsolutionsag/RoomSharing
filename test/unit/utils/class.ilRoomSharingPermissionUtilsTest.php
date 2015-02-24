<?php

chdir("../../../../../../../../"); // necessary for the include paths that are used within the classes to be tested
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingPermissionUtils.php");
require_once("./Services/User/classes/class.ilObjUser.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivileges.php");

/**
 * Class ilRoomSharingPermissionUtilsTest
 *
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 * @group unit
 */
class ilRoomSharingPermissionUtilsTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var ilRoomSharingPermissionUtils
	 */
	private static $perm;
	private static $PrivMock;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		$test = new self();

		self::$PrivMock = $test->getMockBuilder('ilRoomsharingPrivileges')->disableOriginalConstructor()->getMock();

		global $ilUser;
		$ilUser = $test->getMockBuilder('ilObjUser')->disableOriginalConstructor()->getMock();
		$ilUser->method("getId")->willReturn(11);

		$priv = array();
		$priv[] = 'adminBookingAttributes';
		$priv[] = 'cancelBookingLowerPriority';
		$priv[] = 'accessRooms';
		$priv[] = 'seeBookingsOfRooms';
		$priv[] = 'addRooms';
		$priv[] = 'editRooms';
		self::$PrivMock->method("getPrivilegesForUser")->willReturn($priv);

		$allPriv = array();
		$allPriv[] = 'adminBookingAttributes';
		$allPriv[] = 'cancelBookingLowerPriority';
		$allPriv[] = 'accessRooms';
		$allPriv[] = 'seeBookingsOfRooms';
		$allPriv[] = 'addRooms';
		$allPriv[] = 'editRooms';
		$allPriv[] = 'removeRooms';
		$allPriv[] = 'removePool';
		$allPriv[] = 'removeBookings';
		$allPriv[] = 'editBookings';
		self::$PrivMock->method("getAllPrivileges")->willReturn($allPriv);

		self::$perm = new ilRoomSharingPermissionUtils(1, 1, self::$PrivMock);
	}

	/**
	 * @covers ilRoomSharingPermissionUtils::checkPrivilege
	 */
	public function testCheckPrivilege()
	{
		$this->assertTrue(self::$perm->checkPrivilege('adminBookingAttributes'));
		$this->assertTrue(self::$perm->checkPrivilege('cancelBookingLowerPriority'));
		$this->assertTrue(self::$perm->checkPrivilege('accessRooms'));
		$this->assertTrue(self::$perm->checkPrivilege('seeBookingsOfRooms'));
		$this->assertTrue(self::$perm->checkPrivilege('addRooms'));
		$this->assertTrue(self::$perm->checkPrivilege('editRooms'));

		$this->assertFalse(self::$perm->checkPrivilege('addClass'));
		$this->assertFalse(self::$perm->checkPrivilege('deleteFloorplans'));
		$this->assertFalse(self::$perm->checkPrivilege('addFloorplans'));
		$this->assertFalse(self::$perm->checkPrivilege('editFloorplans'));
		$this->assertFalse(self::$perm->checkPrivilege('editPrivileges'));
		$this->assertFalse(self::$perm->checkPrivilege('editClass'));
		$this->assertFalse(self::$perm->checkPrivilege('deleteILIAS'));
		$this->assertFalse(self::$perm->checkPrivilege('refreshILIAS'));
	}

	/**
	 * @covers ilRoomSharingPermissionUtils::getUserPriority
	 */
	public function testGetUserPriority()
	{
		global $ilUser;
		$ilUser = $this->getMockBuilder('ilObjUser')->disableOriginalConstructor()->getMock();
		$ilUser->method("getId")->willReturn(1);
		self::$PrivMock->method("getAllPrivileges")->willReturn(array('priv1', 'priv2'));
		self::$PrivMock->method("getPriorityOfUser")->willReturn(4);

		self::$perm = new ilRoomSharingPermissionUtils(1, 1, self::$PrivMock);

		// Godmode
		$this->assertEquals(10, self::$perm->getUserPriority());
		$this->assertEquals(10, self::$perm->getUserPriority(1));
		$this->assertEquals(4, self::$perm->getUserPriority(12354));

		$ilUser = $this->getMockBuilder('ilObjUser')->disableOriginalConstructor()->getMock();
		$ilUser->method("getId")->willReturn(145);
		self::$perm = new ilRoomSharingPermissionUtils(1, 1, self::$PrivMock);

		// Normal request
		$this->assertEquals(4, self::$perm->getUserPriority());
		$this->assertEquals(4, self::$perm->getUserPriority(2232));
	}

	/**
	 * @covers ilRoomSharingPermissionUtils::checkForHigherPriority
	 */
	public function testCheckForHigherPriorityTrue()
	{
		self::$PrivMock->method("getPriorityOfUser")->will($this->onConsecutiveCalls(8, 7));

		$this->assertTrue(self::$perm->checkForHigherPriority(1, 2));
	}

	/**
	 * @covers ilRoomSharingPermissionUtils::checkForHigherPriority
	 */
	public function testCheckForHigherPriorityFalse()
	{
		$this->assertFalse(self::$perm->checkForHigherPriority(null, null));
		$this->assertFalse(self::$perm->checkForHigherPriority(2, null));
		$this->assertFalse(self::$perm->checkForHigherPriority(null, 3));

		self::$PrivMock->method("getPriorityOfUser")->will($this->onConsecutiveCalls(4, 10));

		$this->assertFalse(self::$perm->checkForHigherPriority(1, 2));
	}

	/**
	 * @covers ilRoomSharingPermissionUtils::getAllUserPrivileges
	 */
	public function testGetAllUserPrivilegesOwner()
	{
		global $ilUser;
		$ilUser = $this->getMockBuilder('ilObjUser')->disableOriginalConstructor()->getMock();
		$ilUser->method("getId")->willReturn(1);

		self::$perm = new ilRoomSharingPermissionUtils(1, 1, self::$PrivMock);

		$allUserPrivileges = self::$perm->getAllUserPrivileges();

		$this->assertEquals(10, count($allUserPrivileges));

		// All lower cased!!!
		$this->assertContains('adminbookingattributes', $allUserPrivileges);
		$this->assertContains('cancelbookinglowerpriority', $allUserPrivileges);
		$this->assertContains('accessrooms', $allUserPrivileges);
		$this->assertContains('seebookingsofrooms', $allUserPrivileges);
		$this->assertContains('addrooms', $allUserPrivileges);
		$this->assertContains('editrooms', $allUserPrivileges);
		$this->assertContains('removerooms', $allUserPrivileges);
		$this->assertContains('removepool', $allUserPrivileges);
		$this->assertContains('removebookings', $allUserPrivileges);
		$this->assertContains('editbookings', $allUserPrivileges);
	}

	/**
	 * @covers ilRoomSharingPermissionUtils::getAllUserPrivileges
	 */
	public function testGetAllUserPrivilegesUser()
	{
		global $ilUser;
		$ilUser = $this->getMockBuilder('ilObjUser')->disableOriginalConstructor()->getMock();
		$ilUser->method("getId")->willReturn(149);

		self::$perm = new ilRoomSharingPermissionUtils(1, 1, self::$PrivMock);

		$allUserPrivileges = self::$perm->getAllUserPrivileges();

		$this->assertEquals(6, count($allUserPrivileges));

		$this->assertContains('adminbookingattributes', $allUserPrivileges);
		$this->assertContains('cancelbookinglowerpriority', $allUserPrivileges);
		$this->assertContains('accessrooms', $allUserPrivileges);
		$this->assertContains('seebookingsofrooms', $allUserPrivileges);
		$this->assertContains('addrooms', $allUserPrivileges);
		$this->assertContains('editrooms', $allUserPrivileges);
	}

}
