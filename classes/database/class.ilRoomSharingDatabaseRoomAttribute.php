<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDBConstants.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingNumericUtils.php");
require_once("./Services/MediaObjects/classes/class.ilObjMediaObject.php");

use ilRoomSharingDBConstants as dbc;

/**
 * Class for database queries.
 *
 * @author Malte Ahlering
 *
 * @property ilDB $ilDB
 */
class ilRoomSharingDatabaseRoomAttribute
{
	private $pool_id;
	private $ilDB;

	/**
	 * constructor ilRoomsharingDatabaseRoomAttribute
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
	 * Gets all attributes referenced by the rooms given by the ids.
	 *
	 * @param array $a_room_ids
	 *        	ids of the rooms
	 * @return array room_id, att.name, count
	 */
	public function getAttributesForRooms($a_room_ids)
	{
		$st = $this->ilDB->prepare('SELECT room_id, att.name, count FROM ' .
			dbc::ROOM_TO_ATTRIBUTE_TABLE . ' as rta LEFT JOIN ' .
			dbc::ROOM_ATTRIBUTES_TABLE . ' as att ON att.id = rta.att_id WHERE '
			. $this->ilDB->in("room_id", $a_room_ids) . ' AND pool_id = ' .
			$this->ilDB->quote($this->pool_id, 'integer') . ' ORDER BY room_id, att.name');
		$set = $this->ilDB->execute($st, $a_room_ids);
		$res_attribute = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$res_attribute [] = $row;
		}
		return $res_attribute;
	}

	/**
	 * Returns all room attribute names
	 *
	 * @return array the attribute names
	 */
	public function getAllAttributeNames()
	{
		$set = $this->ilDB->query('SELECT name FROM ' . dbc::ROOM_ATTRIBUTES_TABLE . ' WHERE pool_id = ' .
			$this->ilDB->quote($this->pool_id, 'integer') . ' ORDER BY name');
		$attributes = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$attributes [] = $row ['name'];
		}
		return $attributes;
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
		// get the id of the attribute in this pool
		$attributIdSet = $this->ilDB->query('SELECT id FROM ' . dbc::ROOM_ATTRIBUTES_TABLE .
			' WHERE name =' . $this->ilDB->quote($a_room_attribute, 'text') . ' AND pool_id = ' .
			$this->ilDB->quote($this->pool_id, 'integer'));
		$attributIdRow = $this->ilDB->fetchAssoc($attributIdSet);
		$attributID = $attributIdRow ['id'];

		// get the max value of the attribut in this pool
		$valueSet = $this->ilDB->query('SELECT MAX(count) AS value FROM ' .
			dbc::ROOM_TO_ATTRIBUTE_TABLE . ' rta LEFT JOIN ' .
			dbc::ROOMS_TABLE . ' as room ON room.id = rta.room_id ' .
			' WHERE att_id =' . $this->ilDB->quote($attributID, 'integer') .
			' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer'));
		$valueRow = $this->ilDB->fetchAssoc($valueSet);
		$value = $valueRow ['value'];
		return $value;
	}

	/**
	 * Get a room attribute.
	 *
	 * @param integer $a_attribute_id
	 * @return type return of $this->ilDB->query
	 */
	public function getRoomAttribute($a_attribute_id)
	{
		$set = $this->ilDB->query('SELECT * FROM ' . dbc::ROOM_ATTRIBUTES_TABLE .
			' WHERE id = ' . $this->ilDB->quote($a_attribute_id, 'integer') .
			' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer'));
		return $this->ilDB->fetchAssoc($set);
	}

	/**
	 * Gets all attributes that are assigned to a room.
	 *
	 * @param integer $a_room_id the id of the room for which the attributes should be returned
	 * @return array an array containing information about the assigned room attributes
	 */
	public function getAttributesForRoom($a_room_id)
	{
		$set = $this->ilDB->query('SELECT id, att.name, count FROM ' .
			dbc::ROOM_TO_ATTRIBUTE_TABLE . ' rta LEFT JOIN ' .
			dbc::ROOM_ATTRIBUTES_TABLE . ' as att ON att.id = rta.att_id' .
			' WHERE room_id = ' . $this->ilDB->quote($a_room_id, 'integer') .
			' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer') . ' ORDER BY att.name');
		$attributes = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$attributes[] = $row;
		}
		return $attributes;
	}

	/**
	 * Deletes all attributes for a room.
	 *
	 * @param type $a_room_id the id of the room whose attributes should be deleted
	 * @return int the number of affected rows
	 */
	public function deleteAllAttributesForRoom($a_room_id)
	{
		return $this->ilDB->manipulate('DELETE FROM ' . dbc::ROOM_TO_ATTRIBUTE_TABLE .
				' WHERE room_id = ' . $this->ilDB->quote($a_room_id, 'integer'));
	}

	/**
	 * Inserts an attribute with its amount to a room specified room.
	 *
	 * @param integer $a_room_id the id room for which the attribute should be inserted
	 * @param integer $a_attribute_id the id of the attribute to be inserted
	 * @param integer $a_amount the amount specified for the attribute
	 */
	public function insertAttributeForRoom($a_room_id, $a_attribute_id, $a_amount)
	{
		$this->ilDB->insert(ilRoomSharingDBConstants::ROOM_TO_ATTRIBUTE_TABLE,
			array(
			'room_id' => array('integer', $a_room_id),
			'att_id' => array('integer', $a_attribute_id),
			'count' => array('integer', $a_amount)
		));
	}

	/**
	 * Gets all available attributes for rooms.
	 *
	 * @return array associative array for all available attributes with id, name
	 */
	public function getAllRoomAttributes()
	{
		$set = $this->ilDB->query('SELECT * FROM ' . dbc::ROOM_ATTRIBUTES_TABLE .
			' WHERE pool_id = ' . $this->ilDB->quote($this->pool_id, 'integer')
			. ' ORDER BY name ASC');

		$attributes_rows = array();
		while ($attributes_row = $this->ilDB->fetchAssoc($set))
		{
			$attributes_rows [] = $attributes_row;
		}

		return $attributes_rows;
	}

	/**
	 * Deletes an room attribute with given id.
	 *
	 * @param integer $a_attribute_id
	 * @return integer Affected rows
	 */
	public function deleteRoomAttribute($a_attribute_id)
	{
		return $this->ilDB->manipulate('DELETE FROM ' . dbc::ROOM_ATTRIBUTES_TABLE .
				' WHERE id = ' . $this->ilDB->quote($a_attribute_id, 'integer') .
				' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer'));
	}

	/**
	 * Inserts new room attribute.
	 *
	 * @param string $a_attribute_name
	 * @return integer id of the inserted attribute
	 */
	public function insertRoomAttribute($a_attribute_name)
	{
		$next_insert_id = $this->ilDB->nextID(dbc::ROOM_ATTRIBUTES_TABLE);
		$this->ilDB->insert(dbc::ROOM_ATTRIBUTES_TABLE,
			array(
			'id' => array('integer', $next_insert_id),
			'name' => array('text', $a_attribute_name),
			'pool_id' => array('integer', $this->pool_id),
			)
		);
		return $next_insert_id;
	}

	/**
	 * Deletes all assignments of an attribute to the rooms.
	 *
	 * @param integer $a_attribute_id
	 * @return integer Affected rows
	 */
	public function deleteAttributeRoomAssign($a_attribute_id)
	{
		return $this->ilDB->manipulate('DELETE FROM ' . dbc::ROOM_TO_ATTRIBUTE_TABLE .
				' WHERE att_id = ' . $this->ilDB->quote($a_attribute_id, 'integer'));
	}

	/**
	 * Renames an room attribute with given id.
	 *
	 * @param integer $a_attribute_id
	 * @param string $a_changed_attribute_name
	 */
	public function renameRoomAttribute($a_attribute_id, $a_changed_attribute_name)
	{
		$fields = array(
			'name' => array('text', $a_changed_attribute_name),
		);
		$where = array(
			'id' => array("integer", $a_attribute_id),
			'pool_id' => array("integer", $this->pool_id)
		);
		$this->ilDB->update(dbc::ROOM_ATTRIBUTES_TABLE, $fields, $where);
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
