<?php

chdir("../../../../../../../../"); // necessary for the include paths that are used within the classes to be tested
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/rooms/class.ilRoomSharingRooms.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");

/**
 * Class ilRoomSharingRoomsTest
 *
 * @author Alexander Keller <a.k3ll3r@gmail.com>
 * @author Christopher Marks <Deamp_dev@yahoo.de>
 * @group unit
 */
class ilRoomSharingRoomsTest extends PHPUnit_Framework_TestCase
{
	private static $rooms;
	private static $ilRoomSharingDatabaseStub;

	public function setUp()
	{
		$test = new ilRoomSharingRoomsTest();
		self::$ilRoomSharingDatabaseStub = $test->getMockBuilder('ilRoomSharingDatabase')
				->disableOriginalConstructor()->getMock();

		self::$rooms = new ilRoomSharingRooms(1, self::$ilRoomSharingDatabaseStub);
	}

	public function testGetRoomName()
	{
		$expected = "012";
		$key = "name";
		self::$ilRoomSharingDatabaseStub->method("getRoomName")->willReturn($expected);
		$actual = self::$rooms->getRoomName($key);
		$this->assertEquals($expected, $actual);
	}

	public function testGetRoomsBookedInDateTimeRange()
	{
		$expected[] = "1";
		$a_date_from = "2014-10-27 09:00:00";
		$a_date_to = "2014-10-27 10:20:00";
		self::$ilRoomSharingDatabaseStub->method("getRoomsBookedInDateTimeRange")->willReturn($expected);

		$actual = self::$rooms->getRoomsBookedInDateTimeRange($a_date_from, $a_date_to);
		$this->assertEquals($expected, $actual);
	}

	public function testGetRoomsBookedInDateTimeRangeWithRoomId()
	{
		$expected[] = "1";
		$a_date_from = "2014-10-27 09:00:00";
		$a_date_to = "2014-10-27 10:20:00";
		$a_room_id = "1";
		self::$ilRoomSharingDatabaseStub->method("getRoomsBookedInDateTimeRange")->willReturn($expected);

		$actual = self::$rooms->getRoomsBookedInDateTimeRange($a_date_from, $a_date_to, $a_room_id);
		$this->assertEquals($expected, $actual);
	}

	public function testGetMaxCountForAttribute()
	{
		$expected = 1;
		$a_attribute = "Beamer";
		self::$ilRoomSharingDatabaseStub->method("getMaxCountForAttribute")->willReturn($expected);

		$actual = self::$rooms->getMaxCountForAttribute($a_attribute);
		$this->assertEquals($expected, $actual);
	}

	public function testGetMaxSeatCount()
	{
		$expected = 100;
		self::$ilRoomSharingDatabaseStub->method("getMaxSeatCount")->willReturn($expected);

		$actual = self::$rooms->getMaxSeatCount();
		$this->assertEquals($expected, $actual);
	}

	public function testGetAllAttributes()
	{
		$beamer = "Beamer";
		$whiteboard = "Whiteboard";
		$expected = array($beamer, $whiteboard);
		self::$ilRoomSharingDatabaseStub->method("getAllAttributeNames")->willReturn($expected);

		$actual = self::$rooms->getAllAttributes();
		$this->assertEquals($expected, $actual);
	}

	public function testGetListWithAllRooms()
	{
		$filter = array();

		$room1 = Array('room' => 012, 'room_id' => 1, 'seats' => 120, 'attributes' => Array('attribute1' => 1,
				'attribute2' => 1));
		$room2 = Array('room' => '032A', 'room_id' => 2, 'seats' => 60, 'attributes' => Array('attribute1' => 1,
				'attribute2' => 2));
		$expected = Array('0' => $room1, '1' => $room2);
		self::$ilRoomSharingDatabaseStub->method("getAllRoomIDs")->willReturn(Array('0' => 0, '1' => 1));
		self::$ilRoomSharingDatabaseStub->method("getMatchingRooms")->willReturn(Array('0' => Array('id' => 1,
				'name' => 012, 'max_alloc' => 120), '1' => Array('id' => 2, 'name' => '032A', 'max_alloc' => 60)));

		$roomAttr = Array('0' => Array('room_id' => 1, 'name' => 'attribute1', 'count' => 1), '1' => Array(
				'room_id' => 1, 'name' => 'attribute2', 'count' => 1), '2' => Array('room_id' => 2, 'name' => 'attribute1',
				'count' => 1), '3' => Array('room_id' => 2, 'name' => 'attribute2', 'count' => 2));
		self::$ilRoomSharingDatabaseStub->method("getAttributesForRooms")->willReturn($roomAttr);

		$actual = self::$rooms->getList($filter);
		$this->assertEquals($expected, $actual);
	}

	public function testGetListWithRoomsWithAttributeFilter()
	{
		$filter = Array('attributes' => Array('attribute2' => 2));

		$room2 = Array('room' => '032A', 'room_id' => 2, 'seats' => 60, 'attributes' => Array('attribute1' => 1,
				'attribute2' => 2));
		$expected = Array('0' => $room2);
		self::$ilRoomSharingDatabaseStub->method("getRoomIdsWithMatchingAttribute")->willReturn(Array('2' => 1));
		self::$ilRoomSharingDatabaseStub->method("getMatchingRooms")->willReturn(Array('0' => Array('id' => 2,
				'name' => '032A', 'max_alloc' => 60)));

		$roomAttr = Array('0' => Array('room_id' => 2, 'name' => 'attribute1',
				'count' => 1), '1' => Array('room_id' => 2, 'name' => 'attribute2', 'count' => 2));
		self::$ilRoomSharingDatabaseStub->method("getAttributesForRooms")->willReturn($roomAttr);

		$actual = self::$rooms->getList($filter);
		$this->assertEquals($expected, $actual);
	}

	public function testGetListWithRoomsWith2AttributeFilter()
	{
		$filter = Array('attributes' => Array('attribute1' => 1, 'attribute2' => 1));

		$room1 = Array('room' => 012, 'room_id' => 1, 'seats' => 120, 'attributes' => Array('attribute1' => 1,
				'attribute2' => 1));
		$room2 = Array('room' => '032A', 'room_id' => 2, 'seats' => 60, 'attributes' => Array('attribute1' => 1,
				'attribute2' => 2));
		$expected = Array('0' => $room1, '1' => $room2);
		self::$ilRoomSharingDatabaseStub->method("getRoomIdsWithMatchingAttribute")->willReturn(Array('0' => 2,
			'1' => 2));
		self::$ilRoomSharingDatabaseStub->method("getMatchingRooms")->willReturn(Array('0' => Array('id' => 1,
				'name' => 012, 'max_alloc' => 120), '1' => Array('id' => 2, 'name' => '032A', 'max_alloc' => 60)));

		$roomAttr = Array('0' => Array('room_id' => 1, 'name' => 'attribute1', 'count' => 1), '1' => Array(
				'room_id' => 1, 'name' => 'attribute2', 'count' => 1), '2' => Array('room_id' => 2, 'name' => 'attribute1',
				'count' => 1), '3' => Array('room_id' => 2, 'name' => 'attribute2', 'count' => 2));
		self::$ilRoomSharingDatabaseStub->method("getAttributesForRooms")->willReturn($roomAttr);

		$actual = self::$rooms->getList($filter);
		$this->assertEquals($expected, $actual);
	}

	public function testGetListWithRoomsFilteredNotInTimeRange()
	{
		$datetimes = Array('from' => '2014-11-30 01:00:00', 'to' => '2014-11-30 02:00:00');
		$filter = Array('date' => '2014-11-30', 'time_from' => '01:00:00', 'time_to' => '02:00:00', 'datetimes' => $datetimes);

		$room2 = Array('room' => '032A', 'room_id' => 2, 'seats' => 60, 'attributes' => Array('attribute1' => 1,
				'attribute2' => 2));
		$expected = Array('0' => $room2);
		self::$ilRoomSharingDatabaseStub->method("getAllRoomIDs")->willReturn(Array('0' => 0, '1' => 1));
		self::$ilRoomSharingDatabaseStub->method("getRoomsBookedInDateTimeRange")->willReturn(Array('0' => 1));
		self::$ilRoomSharingDatabaseStub->method("getMatchingRooms")->willReturn(Array('0' => Array('id' => 2,
				'name' => '032A', 'max_alloc' => 60)));

		$roomAttr = Array('0' => Array('room_id' => 2, 'name' => 'attribute1',
				'count' => 1), '1' => Array('room_id' => 2, 'name' => 'attribute2', 'count' => 2));
		self::$ilRoomSharingDatabaseStub->method("getAttributesForRooms")->willReturn($roomAttr);

		$actual = self::$rooms->getList($filter);
		$this->assertEquals($expected, $actual);
	}

	public function testGetListWithRoomsMatchingRoomName()
	{
		$filter = Array('room_name' => 012);

		$room1 = Array('room' => 012, 'room_id' => 1, 'seats' => 120, 'attributes' => Array('attribute1' => 1,
				'attribute2' => 1));
		$expected = Array('0' => $room1);
		self::$ilRoomSharingDatabaseStub->method("getAllRoomIDs")->willReturn(Array('0' => 0, '1' => 1));
		self::$ilRoomSharingDatabaseStub->method("getMatchingRooms")->willReturn(Array('0' => Array('id' => 1,
				'name' => 012, 'max_alloc' => 120)));

		$roomAttr = Array('0' => Array('room_id' => 1, 'name' => 'attribute1', 'count' => 1), '1' => Array(
				'room_id' => 1, 'name' => 'attribute2', 'count' => 1));
		self::$ilRoomSharingDatabaseStub->method("getAttributesForRooms")->willReturn($roomAttr);

		$actual = self::$rooms->getList($filter);
		$this->assertEquals($expected, $actual);
	}

	public function testGetListWithRoomsWithMinSeatCount()
	{
		$filter = Array('room_seats' => 120);

		$room1 = Array('room' => 012, 'room_id' => 1, 'seats' => 120, 'attributes' => Array('attribute1' => 1,
				'attribute2' => 1));
		$expected = Array('0' => $room1);
		self::$ilRoomSharingDatabaseStub->method("getAllRoomIDs")->willReturn(Array('0' => 0, '1' => 1));
		self::$ilRoomSharingDatabaseStub->method("getMatchingRooms")->willReturn(Array('0' => Array('id' => 1,
				'name' => 012, 'max_alloc' => 120)));

		$roomAttr = Array('0' => Array('room_id' => 1, 'name' => 'attribute1', 'count' => 1), '1' => Array(
				'room_id' => 1, 'name' => 'attribute2', 'count' => 1));
		self::$ilRoomSharingDatabaseStub->method("getAttributesForRooms")->willReturn($roomAttr);

		$actual = self::$rooms->getList($filter);
		$this->assertEquals($expected, $actual);
	}

	public function testGetListWithNoRooms()
	{
		$filter = Array('room_name' => 111);

		$expected = Array();
		self::$ilRoomSharingDatabaseStub->method("getAllRoomIDs")->willReturn(Array('0' => 0, '1' => 1));
		self::$ilRoomSharingDatabaseStub->method("getMatchingRooms")->willReturn(Array());

		$roomAttr = Array();
		self::$ilRoomSharingDatabaseStub->method("getAttributesForRooms")->willReturn($roomAttr);

		$actual = self::$rooms->getList($filter);
		$this->assertEquals($expected, $actual);
	}

}

?>