<?php

chdir("../../../../../../../../"); // necessary for the include paths that are used within the classes to be tested
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivileges.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");

/**
 * Class ilRoomSharingPrivilegesTest
 *
 * @group unit
 * @author Albert Koch <akoch@stud.hs-bremen.de>
 */
class ilRoomSharingPrivilegesTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var ilRoomSharingPrivileges
	 */
	protected $object;
	private static $ilRoomSharingDatabaseStub;
	private static $privileges;

	/**
	 * Sets up the fixture.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		$test = new ilRoomSharingPrivilegesTest();
		self::$ilRoomSharingDatabaseStub = $test->getMockBuilder('ilRoomSharingDatabase')->disableOriginalConstructor()->getMock();
		self::$privileges = ilRoomSharingPrivileges::withDatabase(1, self::$ilRoomSharingDatabaseStub);
	}

	/**
	 * @covers ilRoomSharingPrivileges::getPrivilegesMatrix
	 * @todo   Implement testGetPrivilegesMatrix().
	 */
	public function testGetPrivilegesMatrix()
	{
		// if there's no class, the function must return an empty array
		self::$ilRoomSharingDatabaseStub->method('getClasses')->willreturn(array());
		$returnarray = self::$privileges->getPrivilegesMatrix();
		$this->assertEquals(0, count($returnarray));
	}

	/**
	 * @covers ilRoomSharingPrivileges::getAllPrivileges
	 * @todo   Implement testGetAllPrivileges().
	 */
	public function testGetAllPrivileges()
	{
		$priv = array();
		$priv[] = 'accessAppointments';
		$priv[] = 'accessSearch';
		$priv[] = 'addOwnBookings';
		$priv[] = 'addParticipants';
		$priv[] = 'addSequenceBookings';
		$priv[] = 'addUnlimitedBookings';
		$priv[] = 'seeNonPublicBookingInformation';
		$priv[] = 'notificationSettings';
		$priv[] = 'adminBookingAttributes';
		$priv[] = 'cancelBookingLowerPriority';
		$priv[] = 'accessRooms';
		$priv[] = 'seeBookingsOfRooms';
		$priv[] = 'addRooms';
		$priv[] = 'editRooms';
		$priv[] = 'deleteRooms';
		$priv[] = 'adminRoomAttributes';
		$priv[] = 'accessFloorplans';
		$priv[] = 'addFloorplans';
		$priv[] = 'editFloorplans';
		$priv[] = 'deleteFloorplans';
		$priv[] = 'accessSettings';
		$priv[] = 'accessPrivileges';
		$priv[] = 'addClass';
		$priv[] = 'editClass';
		$priv[] = 'deleteClass';
		$priv[] = 'editPrivileges';
		$priv[] = 'lockPrivileges';
		$priv[] = 'accessImport';

		$this->assertEquals($priv, self::$privileges->getAllPrivileges());
	}

	/**
	 * @covers ilRoomSharingPrivileges::getClasses
	 * @todo   Implement testGetClasses().
	 */
	public function testGetClasses()
	{
		// if there are no classes, return must be null
		self::$ilRoomSharingDatabaseStub->method('getClasses')->willReturn(array());
		$this->assertEquals(array(), self::$privileges->getClasses());
	}

	/**
	 * @covers ilRoomSharingPrivileges::getPriorityOfUser
	 * @todo   Implement testGetPriorityOfUser().
	 */
	public function testGetPriorityOfUser()
	{
		// set nine users with nine priorities below 10 and the ID of the owner who has a priority of 10
		$map = array(
			array('42', '1'),
			array('43', '2'),
			array('44', '3'),
			array('45', '4'),
			array('46', '5'),
			array('47', '6'),
			array('48', '7'),
			array('49', '8'),
			array('50', '9'),
			array('51', '10'),
		);
		self::$ilRoomSharingDatabaseStub->method('getUserPriority')
			->will($this->returnValueMap($map));
		$this->assertEquals('1', self::$privileges->GetPriorityOfUser('42'));
		$this->assertEquals('2', self::$privileges->GetPriorityOfUser('43'));
		$this->assertEquals('3', self::$privileges->GetPriorityOfUser('44'));
		$this->assertEquals('4', self::$privileges->GetPriorityOfUser('45'));
		$this->assertEquals('5', self::$privileges->GetPriorityOfUser('46'));
		$this->assertEquals('6', self::$privileges->GetPriorityOfUser('47'));
		$this->assertEquals('7', self::$privileges->GetPriorityOfUser('48'));
		$this->assertEquals('8', self::$privileges->GetPriorityOfUser('49'));
		$this->assertEquals('9', self::$privileges->GetPriorityOfUser('50'));
		$this->assertEquals('10', self::$privileges->GetPriorityOfUser('51'));
	}

	/**
	 * @covers ilRoomSharingPrivileges::getLockedClasses
	 */
	public function testGetLockedClasses()
	{
		$expected = array(1, 2, 3, 4, 5, 6);
		self::$ilRoomSharingDatabaseStub->method('getLockedClasses')->willreturn($expected);
		$this->assertEquals($expected, self::$privileges->getLockedClasses());
	}

	/**
	 * @covers ilRoomSharingPrivileges::getUnlockedClasses
	 */
	public function testGetUnlockedClasses()
	{
		$expected = array(1, 2, 3, 4, 5, 6);
		self::$ilRoomSharingDatabaseStub->method('getUnlockedClasses')->willreturn($expected);
		$this->assertEquals($expected, self::$privileges->getUnlockedClasses());
	}

}