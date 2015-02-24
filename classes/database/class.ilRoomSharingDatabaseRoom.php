<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDBConstants.php");

use ilRoomSharingDBConstants as dbc;

/**
 * Class for database queries.
 *
 * @author Malte Ahlering
 *
 * @property ilDB $ilDB
 */
class ilRoomSharingDatabaseRoom
{
	private $pool_id;
	private $ilDB;

	/**
	 * constructor ilRoomsharingDatabaseRoom
	 *
	 * @param integer $a_pool_id
	 */
	public function __construct($a_pool_id)
	{
		global $ilDB; // Database-Access-Class
		$this->ilDB = $ilDB;
		$this->pool_id = $a_pool_id;
	}

	/**
	 * Returns the maximum amount of seats of all available rooms in the current pool, so that the
	 * the user can be notified about it in the filter options.
	 *
	 * @return integer $value maximum seats
	 */
	public function getMaxSeatCount()
	{
		$valueSet = $this->ilDB->query('SELECT MAX(max_alloc) AS value FROM ' .
			dbc::ROOMS_TABLE . ' WHERE pool_id = ' .
			$this->ilDB->quote($this->pool_id, 'integer'));
		$valueRow = $this->ilDB->fetchAssoc($valueSet);
		$value = $valueRow ['value'];
		return $value;
	}

	/**
	 * Gets all the room ids for rooms mathcing the attribute given
	 *
	 * @param $a_attribute
	 * @param $a_count
	 * @return array the matching room ids
	 */
	public function getRoomIdsWithMatchingAttribute($a_attribute, $a_count)
	{
		$queryString = 'SELECT room_id FROM ' . dbc::ROOM_TO_ATTRIBUTE_TABLE . ' ra ' .
			'LEFT JOIN ' . dbc::ROOM_ATTRIBUTES_TABLE .
			' attr ON ra.att_id = attr.id WHERE name = ' . $this->ilDB->quote($a_attribute, 'text') .
			' AND count >= ' . $this->ilDB->quote($a_count, 'integer') .
			' AND pool_id = ' . $this->ilDB->quote($this->pool_id, 'integer');

		$matching = array();
		$resAttr = $this->ilDB->query($queryString);
		while ($row = $this->ilDB->fetchAssoc($resAttr))
		{
			$matching[] = $row['room_id'];
		}
		return $matching;
	}

	/**
	 * Returns all room ids
	 *
	 * @return array the room ids
	 */
	public function getAllRoomIds()
	{
		$resRoomIds = $this->ilDB->query('SELECT id FROM ' . dbc::ROOMS_TABLE . ' WHERE pool_id = ' .
			$this->ilDB->quote($this->pool_id, 'integer'));
		$room_ids = array();
		while ($row = $this->ilDB->fetchAssoc($resRoomIds))
		{
			$room_ids[] = $row['id'];
		}
		return $room_ids;
	}

	/**
	 * Returns all rooms assigned to the roomsharing pool.
	 *
	 * @return assoc array with all found rooms
	 */
	public function getAllRooms()
	{
		$resRooms = $this->ilDB->query('SELECT * FROM ' . dbc::ROOMS_TABLE . ' WHERE pool_id = ' .
			$this->ilDB->quote($this->pool_id, 'integer'));
		$rooms = array();
		$row = $this->ilDB->fetchAssoc($resRooms);
		while ($row)
		{
			$rooms[] = $row;
			$row = $this->ilDB->fetchAssoc($resRooms);
		}
		return $rooms;
	}

	/**
	 * Returns all room names of rooms which are assigned to the roomsharing pool.
	 *
	 * @return assoc array with all found room names
	 */
	public function getAllRoomNames()
	{
		$resRooms = $this->ilDB->query('SELECT name FROM ' . dbc::ROOMS_TABLE . ' WHERE pool_id = ' .
			$this->ilDB->quote($this->pool_id, 'integer'));
		$roomNames = array();
		$row = $this->ilDB->fetchAssoc($resRooms);
		while ($row)
		{
			$roomNames[] = $row['name'];
			$row = $this->ilDB->fetchAssoc($resRooms);
		}
		return $roomNames;
	}

	/**
	 * Gets all rooms matching the room name and seats
	 *
	 * @param $a_roomsToCheck
	 * @param $a_room_name
	 * @param $a_room_seats
	 * @return array the matching rooms
	 */
	public function getMatchingRooms($a_roomsToCheck, $a_room_name, $a_room_seats)
	{
		$where_part = ' AND room.pool_id = ' . $this->ilDB->quote($this->pool_id, 'integer') . ' ';

		if ($a_room_name || $a_room_name === "0")
		{
			$where_part = $where_part . ' AND name LIKE ' .
				$this->ilDB->quote('%' . $a_room_name . '%', 'text') . ' ';
		}
		if ($a_room_seats || $a_room_seats === 0.0)
		{
			$where_part = $where_part . ' AND max_alloc >= ' .
				$this->ilDB->quote($a_room_seats, 'integer') . ' ';
		}

		$st = $this->ilDB->prepare('SELECT room.id, name, max_alloc FROM ' .
			dbc::ROOMS_TABLE . ' room WHERE ' .
			$this->ilDB->in("room.id", array_keys($a_roomsToCheck)) . $where_part .
			' ORDER BY name');
		$set = $this->ilDB->execute($st, array_keys($a_roomsToCheck));

		$res_room = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$res_room [] = $row;
		}
		return $res_room;
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
		$roomNameSet = $this->ilDB->query('SELECT name FROM ' . dbc::ROOMS_TABLE .
			' WHERE id = ' . $this->ilDB->quote($a_room_id, 'integer') .
			' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer'));
		$roomNameRow = $this->ilDB->fetchAssoc($roomNameSet);
		return $roomNameRow['name'];
	}

	/**
	 * Gets the room with a given name
	 *
	 * @param type $a_room_name
	 * @return array room
	 */
	public function getRoomWithName($a_room_name)
	{
		$roomSet = $this->ilDB->query('SELECT * FROM ' . dbc::ROOMS_TABLE .
			' WHERE name = ' . $this->ilDB->quote($a_room_name, 'text') .
			' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer'));
		$rooms = array();
		while ($namerow = $this->ilDB->fetchAssoc($roomSet))
		{
			$rooms[] = $namerow;
		}
		return $rooms;
	}

	/**
	 * Get the room-ids from all rooms that are booked in the given timerange.
	 * A specific room_id can be given if a single room should be queried (used for bookings).
	 *
	 * @param string $a_date_from
	 * @param string $a_date_to
	 * @param string $a_room_id
	 *        	(optional)
	 * @return array values = room ids booked in given range
	 */
	public function getRoomsBookedInDateTimeRange($a_datetimes_from, $a_datetimes_to,
		$a_room_id = null, $a_priority = null)
	{
		$roomQuery = '';
		if ($a_room_id)
		{
			$roomQuery = ' room_id = ' .
				$this->ilDB->quote($a_room_id, 'text') . ' AND ';
		}

		$priorityQuery = '';
		$join_part = '';
		if ($a_priority)
		{
			$priorityQuery = ' priority < ' . $this->ilDB->quote($a_priority, 'integer');
			$join_part = ' JOIN ' . dbc::CLASS_USER_TABLE . ' u ON b.user_id = u.user_id JOIN ' .
				dbc::CLASSES_TABLE . ' c ON c.id = u.class_id';
		}

		$query = 'SELECT DISTINCT room_id FROM ' . dbc::BOOKINGS_TABLE . ' b ' . $join_part .
			' WHERE b.pool_id = ' . $this->ilDB->quote($this->pool_id, 'integer');


		$count = count($a_datetimes_from);
		if ($count == count($a_datetimes_to))
		{
			// throw exception if arrays not same size?
			for ($i = 0; $i < $count; $i++)
			{
				if ($i == 0) $query .= ' AND ';
				$a_datetime_from = $a_datetimes_from[$i];
				$a_datetime_to = $a_datetimes_to[$i];

				$query .= ' (' . $this->ilDB->quote($a_datetime_to, 'timestamp') . ' > date_from' .
					' AND ' . $this->ilDB->quote($a_datetime_from, 'timestamp') . ' < date_to) OR';
				if ($i == $count)
				{
					$query .= ')';
				}
			}

			$query = substr($query, 0, -2);
		}

		if (!empty($roomQuery) || !empty($priorityQuery))
		{
			$query .= ' AND ' . $roomQuery . $priorityQuery;
		}


		$set = $this->ilDB->query($query);
		$res_room = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$res_room [] = $row ['room_id'];
		}
		return $res_room;
	}

	/**
	 * Insert a booking into the database.
	 * Gets all room ids where enough seats are available.
	 *
	 * @param integer $a_min_seats number of seats needed
	 * @return array of integer room ids
	 */
	public function getAllRoomIdsWhereSeatsAvailable($a_min_seats)
	{
		$resRoomIds = $this->ilDB->query('SELECT id FROM ' . dbc::ROOMS_TABLE . ' WHERE pool_id = ' .
			$this->ilDB->quote($this->pool_id, 'integer') . ' AND max_alloc >= ' .
			$this->ilDB->quote($a_min_seats, 'integer'));
		$room_ids = array();
		while ($row = $this->ilDB->fetchAssoc($resRoomIds))
		{
			$room_ids[] = $row['id'];
		}
		return $room_ids;
	}

	/**
	 * Returns all rooms matching the floorplan id
	 *
	 * @param type $a_file_id
	 * @return array the rooms
	 */
	public function getRoomsWithFloorplan($a_file_id)
	{
		$set = $this->ilDB->query('SELECT name FROM ' . dbc::ROOMS_TABLE .
			' WHERE file_id = ' . $this->ilDB->quote($a_file_id, 'integer') .
			' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer'));
		$rooms = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$rooms[] = $row;
		}
		return $rooms;
		//return $this->ilDB->fetchAssoc($set);
	}

	/**
	 * Gets room information by id.
	 *
	 * @param integer $a_room_id the id for the room whose information should be returne
	 * @return array room information consisting of name, type, min allocation, ...
	 */
	public function getRoom($a_room_id)
	{
		$set = $this->ilDB->query('SELECT * FROM ' . dbc::ROOMS_TABLE . ' WHERE id = ' .
			$this->ilDB->quote($a_room_id, 'integer') .
			' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer'));

		return $this->ilDB->fetchAssoc($set);
	}

	/**
	 * Inserts room information into the database.
	 *
	 * @param string $a_name
	 * @param string $a_type
	 * @param integer $a_min_alloc
	 * @param integer $a_max_alloc
	 * @param integer $a_file_id
	 * @param integer $a_building_id
	 * @return integer the id of the room for which the information has been inserted
	 */
	public function insertRoom($a_name, $a_type, $a_min_alloc, $a_max_alloc, $a_file_id, $a_building_id)
	{
		$this->ilDB->insert(dbc::ROOMS_TABLE,
			array(
			'id' => array('integer', $this->ilDB->nextId(dbc::ROOMS_TABLE)),
			'name' => array('text', $a_name),
			'type' => array('text', $a_type),
			'min_alloc' => array('integer', $a_min_alloc),
			'max_alloc' => array('integer', $a_max_alloc),
			'file_id' => array('integer', $a_file_id),
			'building_id' => array('integer', $a_building_id),
			'pool_id' => array('integer', $this->pool_id)
		));
		return $this->ilDB->getLastInsertId();
	}

	/**
	 * Updates room properties in the database.
	 *
	 * @param integer $a_id
	 * @param text $a_name
	 * @param text $a_type
	 * @param integer $a_min_alloc
	 * @param integer $a_max_alloc
	 * @param integer $a_file_id
	 * @param integer $a_building_id
	 * @return integer number of affected rows
	 */
	public function updateRoomProperties($a_id, $a_name, $a_type, $a_min_alloc, $a_max_alloc,
		$a_file_id, $a_building_id)
	{
		$fields = array(
			"name" => array("text", $a_name),
			"type" => array("text", $a_type),
			"min_alloc" => array("integer", $a_min_alloc),
			"max_alloc" => array("integer", $a_max_alloc),
			"file_id" => array("integer", $a_file_id),
			"building_id" => array("integer", $a_building_id)
		);
		$where = array(
			"id" => array("integer", $a_id),
			"pool_id" => array("integer", $this->pool_id)
		);
		return $this->ilDB->update(dbc::ROOMS_TABLE, $fields, $where);
	}

	/**
	 * Deletes a room with given room id.
	 *
	 * @param integer $a_room_id the id of the room that should be deleted
	 * @return integer affected rows
	 */
	public function deleteRoom($a_room_id)
	{
		return $this->ilDB->manipulate('DELETE FROM ' . dbc::ROOMS_TABLE .
				' WHERE id = ' . $this->ilDB->quote($a_room_id, 'integer') .
				' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer'));
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
