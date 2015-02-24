<?php

chdir("../../../../../../../../"); // necessary for the include paths that are used within the classes to be tested
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/rooms/detail/class.ilRoomSharingRoom.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");

/**
 * Class ilRoomSharingRoomTest
 *
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 * @group unit
 */
class ilRoomSharingRoomTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var ilRoomSharingRoom
	 */
	private static $room;
	private static $DBMock;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		$test = new self();
		self::$DBMock = $test->getMockBuilder('ilRoomSharingDatabase')->disableOriginalConstructor()->getMock();

		global $lng;
		$lng = $test->getMock('lng', array('txt'), array(), '', false);
		$lng->method("txt")->willReturn("translation");

		// We assume that we have all privileges.
		global $rssPermission;
		$rssPermission = $test->getMockBuilder('ilRoomSharingPermissionUtils')->disableOriginalConstructor()->getMock();
		$rssPermission->method("checkPrivilege")->willReturn(true);

		$existingRoomNames = array(
			'I012 A', 'I035', 'Dojo X', 'Pinguin'
		);
		self::$DBMock->method("getAllRoomNames")->willReturn($existingRoomNames);

		$allRoomAttributes = array(
			array('id' => 1,
				'name' => 'Beamer',
				'pool_id' => 1),
			array('id' => 2,
				'name' => 'Whiteboard',
				'pool_id' => 1),
			array('id' => 3,
				'name' => 'Tafelstifte',
				'pool_id' => 1),
			array('id' => 4,
				'name' => 'Fernseher',
				'pool_id' => 1),
			array('id' => 5,
				'name' => 'Desktop PCs',
				'pool_id' => 1),
			array('id' => 6,
				'name' => 'Schwämme',
				'pool_id' => 1)
		);
		self::$DBMock->method("getAllRoomAttributes")->willReturn($allRoomAttributes);

		$roomProperties = array(
			'id' => 1,
			'name' => 'I012 A',
			'type' => 'Saal',
			'min_alloc' => 20,
			'max_alloc' => 100,
			'file_id' => 450,
			'building_id' => 230,
			'pool_id' => 1
		);
		self::$DBMock->method("getRoom")->willReturn($roomProperties);

		$roomAttributes = array(
			array(
				'id' => 1,
				'name' => 'Beamer',
				'count' => 3
			),
			array(
				'id' => 4,
				'name' => 'Fernseher',
				'count' => 1
			),
			array(
				'id' => 2,
				'name' => 'Whiteboard',
				'count' => 2
			)
		);
		self::$DBMock->method("getAttributesForRoom")->willReturn($roomAttributes);

		$bookings = array(
			array(
				'id' => 12,
				'date_from' => '2014-12-09 15:00:00.000000',
				'date_to' => '2014-12-09 18:00:00.000000',
				'seq_id' => null,
				'room_id' => 1,
				'pool_id' => 1,
				'user_id' => 3,
				'subject' => 'Test',
				'public_booking' => 0,
				'bookingcomment' => 'Ist eine Testbuchung',
				'calendar_entry_id' => 23
			)
		);
		self::$DBMock->method("getAllBookingsForRoom")->willReturn($bookings);

		self::$room = new ilRoomSharingRoom(1, 1, false, self::$DBMock);
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown()
	{

	}

	/**
	 * @covers ilRoomSharingRoom::read
	 * @covers ilRoomSharingRoom::getId
	 * @covers ilRoomSharingRoom::getName
	 * @covers ilRoomSharingRoom::getType
	 * @covers ilRoomSharingRoom::getMinAlloc
	 * @covers ilRoomSharingRoom::getMaxAlloc
	 * @covers ilRoomSharingRoom::getFileId
	 * @covers ilRoomSharingRoom::getBuildingId
	 * @covers ilRoomSharingRoom::getPoolId
	 * @covers ilRoomSharingRoom::getAttributes
	 * @covers ilRoomSharingRoom::getAllAvailableAttributes
	 * @covers ilRoomSharingRoom::getBookedTimes
	 */
	public function testRead()
	{
		$allAvailableAttributes = self::$room->getAllAvailableAttributes();

		$this->assertEquals(6, count($allAvailableAttributes));

		$this->assertEquals(1, self::$room->getId());
		$this->assertEquals('I012 A', self::$room->getName());
		$this->assertEquals('Saal', self::$room->getType());
		$this->assertEquals(20, self::$room->getMinAlloc());
		$this->assertEquals(100, self::$room->getMaxAlloc());
		$this->assertEquals(450, self::$room->getFileId());
		$this->assertEquals(230, self::$room->getBuildingId());
		$this->assertEquals(1, self::$room->getPoolId());

		$roomAttributes = self::$room->getAttributes();

		$this->assertEquals(3, count($roomAttributes));

		$firstAttr = $roomAttributes[0];

		$this->assertEquals(1, $firstAttr['id']);
		$this->assertEquals('Beamer', $firstAttr['name']);
		$this->assertEquals(3, $firstAttr['count']);

		$secondAttr = $roomAttributes[1];

		$this->assertEquals(4, $secondAttr['id']);
		$this->assertEquals('Fernseher', $secondAttr['name']);
		$this->assertEquals(1, $secondAttr['count']);

		$thirdAttr = $roomAttributes[2];

		$this->assertEquals(2, $thirdAttr['id']);
		$this->assertEquals('Whiteboard', $thirdAttr['name']);
		$this->assertEquals(2, $thirdAttr['count']);

		$expectedBookings = array(
			array(
				'id' => 12,
				'date_from' => '2014-12-09 15:00:00.000000',
				'date_to' => '2014-12-09 18:00:00.000000',
				'seq_id' => null,
				'room_id' => 1,
				'pool_id' => 1,
				'user_id' => 3,
				'subject' => 'Test',
				'public_booking' => 0,
				'bookingcomment' => 'Ist eine Testbuchung',
				'calendar_entry_id' => 23
			)
		);
		$this->assertEquals($expectedBookings, self::$room->getBookedTimes());
	}

	/**
	 * @covers ilRoomSharingRoom::save
	 * @expectedException ilRoomSharingRoomException
	 * @expectedExceptionMessage rep_robj_xrs_illegal_room_min_max_alloc
	 */
	public function testSaveNegativeMaxAllocGiven()
	{
		self::$room->setMaxAlloc(-20);
		self::$room->save();
	}

	/**
	 * @covers ilRoomSharingRoom::save
	 * @expectedException ilRoomSharingRoomException
	 * @expectedExceptionMessage rep_robj_xrs_illegal_room_min_max_alloc
	 */
	public function testSaveNegativeMinAllocGiven()
	{
		self::$room->setMinAlloc(-20);
		self::$room->save();
	}

	/**
	 * @covers ilRoomSharingRoom::save
	 * @expectedException ilRoomSharingRoomException
	 * @expectedExceptionMessage rep_robj_xrs_illegal_room_min_max_alloc
	 */
	public function testSaveMinAllocGreaterMax()
	{
		self::$room->setMaxAlloc(20);
		self::$room->setMinAlloc(21);
		self::$room->save();
	}

	/**
	 * @covers ilRoomSharingRoom::save
	 */
	public function testSave()
	{

		self::$room->setId(10);
		self::$room->setName('CoolRoom');
		self::$room->setType('Party');
		self::$room->setMinAlloc(10);
		self::$room->setMaxAlloc(20);
		self::$room->setFileId(310);
		self::$room->setBuildingId(730);

		self::$room->setAttributes(array(
			array(
				'id' => 12,
				'count' => 3
			),
			array(
				'id' => 7,
				'count' => 8
			)
		));

		self::$DBMock->expects($this->once())->method('updateRoomProperties')->with(
			$this->equalTo(10), $this->equalTo('CoolRoom'), $this->equalTo('Party'), $this->equalTo(10),
			$this->equalTo(20), $this->equalTo(310), $this->equalTo(730)
		);
		self::$DBMock->expects($this->once())->method('deleteAllAttributesForRoom');
		self::$DBMock->expects($this->exactly(2))->method('insertAttributeForRoom')->withConsecutive(
			array($this->equalTo(10), $this->equalTo(12), $this->equalTo(3)),
			array($this->equalTo(10), $this->equalTo(7), $this->equalTo(8))
		);

		self::$room->save();
	}

	/**
	 * @covers ilRoomSharingRoom::create
	 * @expectedException ilRoomSharingRoomException
	 * @expectedExceptionMessage rep_robj_xrs_illegal_room_min_max_alloc
	 */
	public function testCreateNegativeMaxAllocGiven()
	{
		self::$room->setMaxAlloc(-20);
		self::$room->create();
	}

	/**
	 * @covers ilRoomSharingRoom::create
	 * @expectedException ilRoomSharingRoomException
	 * @expectedExceptionMessage rep_robj_xrs_illegal_room_min_max_alloc
	 */
	public function testCreateNegativeMinAllocGiven()
	{
		self::$room->setMinAlloc(-20);
		self::$room->create();
	}

	/**
	 * @covers ilRoomSharingRoom::create
	 * @expectedException ilRoomSharingRoomException
	 * @expectedExceptionMessage rep_robj_xrs_illegal_room_min_max_alloc
	 */
	public function testCreateMinAllocGreaterMax()
	{
		self::$room->setMaxAlloc(20);
		self::$room->setMinAlloc(21);
		self::$room->create();
	}

	/**
	 * @covers ilRoomSharingRoom::create
	 * @expectedException ilRoomSharingRoomException
	 * @expectedExceptionMessage rep_robj_xrs_room_name_occupied
	 */
	public function testCreateNameNotFree()
	{
		self::$room->setName('Pinguin');
		self::$room->create();
	}

	/**
	 * @covers ilRoomSharingRoom::create
	 * @expectedException ilRoomSharingRoomException
	 * @expectedExceptionMessage rep_robj_xrs_room_create_failed
	 */
	public function testCreateNameNotValid()
	{
		self::$room->setName('');
		self::$room->create();
	}

	/**
	 * @covers ilRoomSharingRoom::create
	 */
	public function testCreate()
	{
		self::$DBMock->method("insertRoom")->willReturn(999);

		$newRoom = new ilRoomSharingRoom(1, 23, true, self::$DBMock);

		$newRoom->setName('Joga');
		$newRoom->setType('Fitness');
		// Min Alloc not given
		$newRoom->setMaxAlloc(20);
		$newRoom->setFileId(300);
		$newRoom->setBuildingId(809);

		$newRoom->addAttribute(1, 3);
		$newRoom->addAttribute(4, 1);

		self::$DBMock->expects($this->once())->method('insertRoom')->with(
			$this->equalTo('Joga'), $this->equalTo('Fitness'), $this->equalTo(0), $this->equalTo(20),
			$this->equalTo(300), $this->equalTo(809)
		);
		self::$DBMock->expects($this->exactly(2))->method('insertAttributeForRoom')->withConsecutive(
			array($this->equalTo(999), $this->equalTo(1), $this->equalTo(3)),
			array($this->equalTo(999), $this->equalTo(4), $this->equalTo(1))
		);

		$newRoom->create();
	}

	/**
	 * @covers ilRoomSharingRoom::delete
	 */
	public function testDelete()
	{
		self::$DBMock->expects($this->once())->method('deleteRoom')->with($this->equalTo(1));
		self::$DBMock->expects($this->once())->method('deleteAllAttributesForRoom')->with($this->equalTo(1));
		self::$DBMock->expects($this->once())->method('deleteAllBookingsAssignedToRoom')->with($this->equalTo(1));
		self::$room->delete();
	}

	/**
	 * @covers ilRoomSharingRoom::getAmountOfBookings
	 */
	public function testGetAmountOfBookings()
	{
		$bookings = array(
			array('book_id' => 2, 'comment' => 'psss'),
			array('book_id' => 4, 'comment' => 'woohaa'));
		self::$DBMock->method("getCurrentBookingsForRoom")->willReturn($bookings);

		$this->assertEquals(2, self::$room->getAmountOfBookings());
	}

	/**
	 * @covers ilRoomSharingRoom::addAttribute
	 * @expectedException ilRoomSharingRoomException
	 * @expectedExceptionMessage rep_robj_xrs_add_wrong_attribute
	 */
	public function testAddAttributeNotExists()
	{
		self::$room->addAttribute(7, 10);
	}

	/**
	 * @covers ilRoomSharingRoom::addAttribute
	 * @expectedException ilRoomSharingRoomException
	 * @expectedExceptionMessage rep_robj_xrs_add_wrong_attribute
	 */
	public function testAddAttributeAlreadyDefined()
	{
		self::$room->addAttribute(1, 10);
	}

	/**
	 * @covers ilRoomSharingRoom::addAttribute
	 * @expectedException ilRoomSharingRoomException
	 * @expectedExceptionMessage rep_robj_xrs_add_wrong_attribute_count
	 */
	public function testAddAttributeWrongAmount()
	{
		self::$room->addAttribute(6, -1);
	}

	/**
	 * @covers ilRoomSharingRoom::addAttribute
	 */
	public function testAddAttribute()
	{
		self::$room->addAttribute(3, 4);
		$this->assertEquals(4, count(self::$room->getAttributes()));
		$needle = array('id' => 3, 'name' => 'Tafelstifte', 'count' => 4);
		$this->assertContains($needle, self::$room->getAttributes());
	}

	/**
	 * @covers ilRoomSharingRoom::resetAttributes
	 */
	public function testResetAttributes()
	{
		self::$room->resetAttributes();

		$this->assertEquals(0, count(self::$room->getAttributes()));
	}

	/**
	 * @covers ilRoomSharingRoom::getAllFloorplans
	 * @todo   Implement testGetAllFloorplans().
	 */
	public function testGetAllFloorplans()
	{
		$floorPlans = array(
			array('file_id' => '100', 'title' => 'Reddy'),
			array('file_id' => '102', 'title' => 'Floofy'),
		);
		self::$DBMock->method("getAllFloorplans")->willReturn($floorPlans); #

		$expected = array(
			'title' => " - translation - ",
			'100' => 'Reddy',
			'102' => 'Floofy'
		);
		self::assertEquals($expected, self::$room->getAllFloorplans());
	}

	/**
	 * @covers ilRoomSharingRoom::getAttributeAmountById
	 */
	public function testGetAttributeAmountById()
	{
		self::assertEquals(3, self::$room->getAttributeAmountById(1));
		self::assertEquals(1, self::$room->getAttributeAmountById(4));
		self::assertEquals(2, self::$room->getAttributeAmountById(2));
	}

	/**
	 * @covers ilRoomSharingRoom::setId
	 */
	public function testSetId()
	{
		self::$room->setId(200);
		$this->assertEquals(200, self::$room->getId());
	}

	/**
	 * @covers ilRoomSharingRoom::setName
	 */
	public function testSetName()
	{
		self::$room->setName('Montreal');
		$this->assertEquals('Montreal', self::$room->getName());
	}

	/**
	 * @covers ilRoomSharingRoom::setType
	 */
	public function testSetType()
	{
		self::$room->setType('Montreal2');
		$this->assertEquals('Montreal2', self::$room->getType());
	}

	/**
	 * @covers ilRoomSharingRoom::setMinAlloc
	 */
	public function testSetMinAlloc()
	{
		self::$room->setMinAlloc(100);
		$this->assertEquals(100, self::$room->getMinAlloc());
	}

	/**
	 * @covers ilRoomSharingRoom::setMaxAlloc
	 */
	public function testSetMaxAlloc()
	{
		self::$room->setMaxAlloc(1200);
		$this->assertEquals(1200, self::$room->getMaxAlloc());
	}

	/**
	 * @covers ilRoomSharingRoom::setFileId
	 */
	public function testSetFileId()
	{
		self::$room->setId(33);
		$this->assertEquals(33, self::$room->getId());
	}

	/**
	 * @covers ilRoomSharingRoom::setBuildingId
	 */
	public function testSetBuildingId()
	{
		self::$room->setBuildingId(400);
		$this->assertEquals(400, self::$room->getBuildingId());
	}

	/**
	 * @covers ilRoomSharingRoom::setPoolId
	 */
	public function testSetPoolId()
	{
		self::$room->setPoolId(80);
		$this->assertEquals(80, self::$room->getPoolId());
	}

	/**
	 * @covers ilRoomSharingRoom::setAttributes
	 */
	public function testSetAttributes()
	{
		$attributes = array(array(1), array(2), array(3));
		self::$room->setAttributes($attributes);
		$this->assertEquals($attributes, self::$room->getAttributes());
	}

	/**
	 * @covers ilRoomSharingRoom::setBookedTimes
	 */
	public function testSetBookedTimes()
	{
		$bookedTimes = array(array(1), array(2), array(3));
		self::$room->setBookedTimes($bookedTimes);
		$this->assertEquals($bookedTimes, self::$room->getBookedTimes());
	}

}
