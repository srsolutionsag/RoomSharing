<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");

/**
 * Class ilRoomSharingRooms
 *
 * @author Alexander Keller <a.k3ll3r@gmail.com>
 * @author Dan Soergel <dansoergel@t-online.de>
 * @author Malte Ahlering <mahlering@stud.hs-bremen.de>
 * @author Bernd Hitzelberger <bhitzelberger@stud.hs-bremen.de>
 * @author Christopher Marks <Deamp_dev@yahoo.de>
 */
class ilRoomSharingRooms
{
	private $pool_id;
	private $ilRoomsharingDatabase;
	private $filter;
	private $roomsMatchingAttributeFilters;

	/**
	 * Constructor ilRoomSharingRooms
	 *
	 * @param integer $a_pool_id
	 * @param type $a_ilRoomsharingDatabase the roomsharing database
	 */
	public function __construct($a_pool_id, $a_ilRoomsharingDatabase)
	{
		$this->pool_id = $a_pool_id;
		$this->ilRoomsharingDatabase = $a_ilRoomsharingDatabase;
	}

	/**
	 * Gets the rooms for a given pool_id from database
	 *
	 * @param array $a_filter optional room-filter
	 * @return array Rooms and Attributes in the following format:
	 *         array (
	 *         array (
	 *         'room' => <string>, Name of the room
	 *         'seats' => <int>, Amout of seats
	 *         'beamer' => <bool>, true, if a beamer exists
	 *         'overhead_projector' => <bool>, true, if a overhead projector exists
	 *         'whiteboard' => <bool>, true, if a whiteboard exists
	 *         'sound_system' => <bool>, true, if a sound system exists
	 *         )
	 *         )
	 */
	public function getList(array $a_filter = null)
	{
		$this->filter = $a_filter;

		if (array_key_exists('attributes', $this->filter))
		{
			$this->roomsMatchingAttributeFilters = $this->getRoomsWithMatchingAttributes();
		}
		else
		{
			$this->roomsMatchingAttributeFilters = $this->getAllRooms();
		}

		if (array_key_exists("date", $this->filter) && array_key_exists("time_from", $this->filter) && array_key_exists("time_to",
				$this->filter))
		{
			$this->removeRoomsNotInTimeRange();
		}

		$this->roomsMatchingAttributeFilters = $this->removeRoomsNotMatchingNameAndSeats();

		if (!empty($this->roomsMatchingAttributeFilters[0]))
		{
			$res_attribute = $this->getAttributes($this->roomsMatchingAttributeFilters[0]);
			$res = $this->formatDataForGui($this->roomsMatchingAttributeFilters[1], $res_attribute);
		}
		else
		{
			$res = array();
		}

		return $res;
	}

	/**
	 * Gets Rooms with matching Attributes
	 *
	 * @param type $a_attribute_filter
	 * @return array()
	 */
	private function getRoomsWithMatchingAttributes()
	{
		$count = 0;
		$roomsWithAttrib = array();
		$roomsMatchingAttributeFilters = array();

		foreach ($this->filter ['attributes'] as $attribute => $attribute_count)
		{
			$count = $count + 1;
			$roomsWithAttrib = $this->getMatchingRoomsForAttributeAsArray($attribute, $attribute_count,
				$roomsWithAttrib);
		}
		foreach ($roomsWithAttrib as $room_id => $match_count)
		{
			if ($match_count == $count)
			{
				$roomsMatchingAttributeFilters [$room_id] = $match_count;
			}
		}
		return $roomsMatchingAttributeFilters;
	}

	/**
	 * Gets rooms matching an attribute
	 *
	 * @param type $a_attribute
	 * @param type $a_count
	 * @param type $a_roomsWithAttrib
	 * @return type
	 */
	private function getMatchingRoomsForAttributeAsArray($a_attribute, $a_count, $a_roomsWithAttrib)
	{
		$matching = $this->ilRoomsharingDatabase->
			getRoomIdsWithMatchingAttribute($a_attribute, $a_count);

		foreach ($matching as $key => $room_id)
		{
			if (!array_key_exists($room_id, $a_roomsWithAttrib))
			{
				$a_roomsWithAttrib [$room_id] = 1;
			}
			else
			{
				$a_roomsWithAttrib [$room_id] = $a_roomsWithAttrib [$room_id] + 1;
			}
		}

		return $a_roomsWithAttrib;
	}

	/**
	 * Gets all Rooms
	 */
	private function getAllRooms()
	{
		$rooms = array();
		$room_ids = $this->ilRoomsharingDatabase->getAllRoomIds();

		foreach ($room_ids as $room_id)
		{
			$rooms [$room_id] = 1;
		}
		return $rooms;
	}

	/**
	 * Removes all rooms that are booked in time range
	 */
	private function removeRoomsNotInTimeRange()
	{
		if (array_key_exists('priority', $this->filter))
		{
			$roomsBookedInTimeRange = $this->getRoomsBookedInDateTimeRange($this->filter['datetimes']['from'],
				$this->filter['datetimes']['to'], null, $this->filter['priority']);
		}
		else
		{
			$roomsBookedInTimeRange = $this->getRoomsBookedInDateTimeRange($this->filter['datetimes']['from'],
				$this->filter['datetimes']['to']);
		}
		$roomsMatchingAttributeFilters_Temp = $this->roomsMatchingAttributeFilters;
		$this->roomsMatchingAttributeFilters = array();

		foreach ($roomsMatchingAttributeFilters_Temp as $key => $value)
		{
			if (array_search($key, $roomsBookedInTimeRange) > -1)
			{
				// Room is allready booked
			}
			else
			{
				$this->roomsMatchingAttributeFilters [$key] = 1;
			}
		}
	}

	/**
	 * Removes the rooms not matching name and seats
	 *
	 * @return type array with room_ids, $res_room
	 */
	private function removeRoomsNotMatchingNameAndSeats()
	{
		if (array_key_exists("room_name", $this->filter) && array_key_exists("room_seats", $this->filter))
		{
			$res_rooms = $this->ilRoomsharingDatabase->getMatchingRooms($this->roomsMatchingAttributeFilters,
				$this->filter ["room_name"], $this->filter ["room_seats"]);
		}
		elseif (array_key_exists("room_name", $this->filter))
		{
			$res_rooms = $this->ilRoomsharingDatabase->getMatchingRooms($this->roomsMatchingAttributeFilters,
				$this->filter ["room_name"], null);
		}
		elseif (array_key_exists("room_seats", $this->filter))
		{
			$res_rooms = $this->ilRoomsharingDatabase->getMatchingRooms($this->roomsMatchingAttributeFilters,
				null, $this->filter ["room_seats"]);
		}
		else
		{
			$res_rooms = $this->ilRoomsharingDatabase->getMatchingRooms($this->roomsMatchingAttributeFilters,
				null, null);
		}

		$room_ids = array();

		foreach ($res_rooms as $res_room)
		{
			$room_ids [] = $res_room ['id'];
		}

		return array(
			$room_ids,
			$res_rooms
		);
	}

	/**
	 * Returns all available room attributes that appear in the optional
	 * filter list.
	 *
	 * @return string
	 */
	public function getAllAttributes()
	{
		return $this->ilRoomsharingDatabase->getAllAttributeNames();
	}

	/**
	 * Gets all attributes referenced by the rooms given by the ids.
	 *
	 * @param array $room_ids
	 *        	ids of the rooms
	 * @return array room_id, att.name, count
	 */
	protected function getAttributes(array $room_ids = null)
	{
		return $this->ilRoomsharingDatabase->getAttributesForRooms($room_ids);
	}

	/**
	 * Formats the loaded data for the gui.
	 *
	 *
	 * @param array $a_res_room
	 *        	list of rooms
	 * @param array $a_res_attribute
	 *        	list of attributes
	 * @return array Rooms and Attributes in the following format:
	 *         array (
	 *         array (
	 *         'room' => <string>, Name of the room
	 *         'seats' => <int>, Amout of seats
	 *         'beamer' => <bool>, true, if a beamer exists
	 *         'overhead_projector' => <bool>, true, if a overhead projector exists
	 *         'whiteboard' => <bool>, true, if a whiteboard exists
	 *         'sound_system' => <bool>, true, if a sound system exists
	 *         )
	 *         )
	 */
	protected function formatDataForGui(array $a_res_room, array $a_res_attribute)
	{
		$res = array();
		foreach ($a_res_room as $room)
		{
			$attr = array();
			foreach ($a_res_attribute as $attribute)
			{
				if ($attribute ['room_id'] == $room ['id'])
				{
					$attr [$attribute ['name']] = $attribute ['count'];
				}
			}

			$row = array(
				'room' => $room ['name'],
				'room_id' => $room ['id'],
				'seats' => $room ['max_alloc'],
				'attributes' => $attr
			);
			$res [] = $row;
		}
		return $res;
	}

	/**
	 * Returns the maximum amount of seats of all available rooms in the current pool, so that the
	 * the user can be notified about it in the filter options.
	 *
	 * @return integer $value maximum seats
	 */
	public function getMaxSeatCount()
	{
		return $this->ilRoomsharingDatabase->getMaxSeatCount();
	}

	/**
	 * Determines the maximum amount of a given room attribute and returns it.
	 *
	 * @param type $a_room_attribute
	 *        	the attribute for which the max count
	 *        	should be determined
	 * @return type the max value of the attribute
	 */
	public function getMaxCountForAttribute($a_room_attribute)
	{
		return $this->ilRoomsharingDatabase->getMaxCountForAttribute($a_room_attribute);
	}

	/**
	 * Get's the room name by a given room id
	 *
	 * @param integer $a_room_id
	 *        	Room id of the room which name is unknown
	 *
	 * @return Room Name
	 */
	public function getRoomName($a_room_id)
	{
		return $this->ilRoomsharingDatabase->getRoomName($a_room_id);
	}

	/**
	 * Get the room-ids from all rooms that are booked in the given timerange.
	 * A specific room_id can be given if a single room should be queried (used for bookings).
	 *
	 * @param string $date_from
	 * @param string $date_to
	 * @param string $room_id
	 *        	(optional)
	 * @return array values = room ids booked in given range
	 */
	public function getRoomsBookedInDateTimeRange($a_datetimes_from, $a_datetimes_to,
		$a_room_id = null, $a_priority = null)
	{
		return $this->ilRoomsharingDatabase->getRoomsBookedInDateTimeRange($a_datetimes_from,
				$a_datetimes_to, $a_room_id, $a_priority);
	}

	/**
	 * Set the poolID of bookings
	 *
	 * @param integer $pool_id
	 *        	poolID
	 */
	public function setPoolId($pool_id)
	{
		$this->pool_id = $pool_id;
	}

	/**
	 * Get the PoolID of bookings
	 *
	 * @return integer PoolID
	 */
	public function getPoolId()
	{
		return (int) $this->pool_id;
	}

}

?>
