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
class ilRoomSharingDatabaseBookingAttribute
{
	private $pool_id;
	private $ilDB;

	/**
	 * constructor ilRoomsharingDatabaseBookingAttribute
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
	 * Method to insert booking attributes assign into the database.
	 *
	 * @param integer $a_insertedId
	 * @param array $a_booking_attr_values
	 *        	Array with the values of the booking-attributes
	 */
	public function insertBookingAttributes($a_insertedId, $a_booking_attr_values)
	{
		// Insert the attributes for the booking in the conjunction table
		foreach ($a_booking_attr_values as $booking_attr_key => $booking_attr_value)
		{
			// Only insert the attribute value, if a value was submitted by the user
			if ($booking_attr_value !== "")
			{
				$this->insertBookingAttributeAssign($a_insertedId, $booking_attr_key, $booking_attr_value);
			}
		}
	}

	/**
	 * Inserts a booking attribute into the database.
	 *
	 * @param integer $a_insertedId
	 * @param integer $a_booking_attr_key
	 * @param string $a_booking_attr_value
	 */
	public function insertBookingAttributeAssign($a_insertedId, $a_booking_attr_key, $a_booking_attr_value)
	{
		$this->ilDB->insert(dbc::BOOKING_TO_ATTRIBUTE_TABLE, array(
			'booking_id' => array('integer', $a_insertedId),
			'attr_id' => array('integer', $a_booking_attr_key),
			'value' => array('text', $a_booking_attr_value)
			)
		);
	}

	/**
	 * Gets all attributes of a booking.
	 *
	 * @param integer $a_booking_id
	 * @return type return of $this->ilDB->query
	 */
	public function getAttributesForBooking($a_booking_id)
	{
		$set = $this->ilDB->query('SELECT value, attr.name AS name' .
			' FROM ' . dbc::BOOKING_TO_ATTRIBUTE_TABLE . ' bta ' .
			' LEFT JOIN ' . dbc::BOOKING_ATTRIBUTES_TABLE . ' attr ' .
			' ON attr.id = bta.attr_id' . ' WHERE booking_id = ' .
			$this->ilDB->quote($a_booking_id, 'integer') .
			' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer'));

		$attributes = array();
		while ($attributesRow = $this->ilDB->fetchAssoc($set))
		{
			$attributes[] = $attributesRow;
		}
		return $attributes;
	}

	/**
	 * Gets all booking attributes.
	 *
	 * @return type return of $this->ilDB->query
	 */
	public function getAllBookingAttributes()
	{
		$set = $this->ilDB->query('SELECT * FROM ' . dbc::BOOKING_ATTRIBUTES_TABLE .
			' WHERE pool_id = ' . $this->ilDB->quote($this->pool_id, 'integer')
			. ' ORDER BY name ASC');

		$attributesRows = array();
		while ($attributesRow = $this->ilDB->fetchAssoc($set))
		{
			$attributesRows [] = $attributesRow;
		}

		return $attributesRows;
	}

	/**
	 * Returns all booking attribute names
	 *
	 * @return array the attribute names
	 */
	public function getAllBookingAttributeNames()
	{
		$set = $this->ilDB->query('SELECT name FROM ' . dbc::BOOKING_ATTRIBUTES_TABLE .
			' WHERE pool_id = ' . $this->ilDB->quote($this->pool_id, 'integer') . ' ORDER BY name');
		$attributes = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$attributes [] = $row ['name'];
		}
		return $attributes;
	}

	/**
	 * Returns the values for the booking attribute
	 *
	 * @param type $a_booking_id
	 * @return array attribute values
	 */
	public function getBookingAttributeValues($a_booking_id)
	{
		$set = $this->ilDB->query('SELECT *' . ' FROM ' . dbc::BOOKING_TO_ATTRIBUTE_TABLE . ' WHERE booking_id = ' .
			$this->ilDB->quote($a_booking_id, 'integer') . ' ORDER BY attr_id ASC');

		$booking = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$booking[] = $row;
		}
		return $booking;
	}

	/**
	 * Update a booking attribute assign in the database.
	 *
	 * @param type $a_booking_id
	 * @param type $a_booking_attr_key
	 * @param type $a_booking_attr_value
	 */
	public function updateBookingAttributeAssign($a_booking_id, $a_booking_attr_key, $a_booking_attr_value)
	{
		$this->ilDB->update(dbc::BOOKING_TO_ATTRIBUTE_TABLE, array(
			'value' => array('text', $a_booking_attr_value)
			), array(
			'booking_id' => array('integer', $a_booking_id),
			'attr_id' => array('integer', $a_booking_attr_key)
			)
		);
	}

	/**
	 * Method to update the booking attributes assigned in the database.
	 *
	 * @param int $a_booking_id
	 * @param array $a_booking_attr_values
	 * 				Array with the values of the booking-attributes
	 * @param array $a_old_booking_attr_values needed for comparison sakes
	 */
	public function updateBookingAttributes($a_booking_id, $a_booking_attr_values, $a_old_booking_attr_values)
	{
		foreach ($a_booking_attr_values as $booking_attr_key => $booking_attr_value)
		{
			$this->updateSingleBookingAttributeValue($a_booking_id, $booking_attr_key, $booking_attr_value, $a_old_booking_attr_values);
		}
	}

	/**
	 * Updates a single booking attribute value.
	 *
	 * @param type $a_booking_id
	 * @param type $a_booking_attr_key the key of the attribute that needs to be updated
	 * @param type $a_booking_attr_value the new value for the key
	 * @param type $a_old_booking_attr_values the old attribute values needed for comparison
	 */
	private function updateSingleBookingAttributeValue($a_booking_id, $a_booking_attr_key, $a_booking_attr_value, $a_old_booking_attr_values)
	{
		if (!empty($a_booking_attr_value))
		{
			if ($this->hasAttributeValue($a_booking_attr_key, $a_old_booking_attr_values))
			{
				$this->updateBookingAttributeAssign($a_booking_id, $a_booking_attr_key, $a_booking_attr_value);
			}
			else
			{
				$this->insertBookingAttributeAssign($a_booking_id, $a_booking_attr_key, $a_booking_attr_value);
			}
		}
		// Or update the attribute value with an empty string, if no value was submitted by the user
		else
		{
			$this->updateDelBookingAttributeAssign($a_booking_id, $a_booking_attr_key);
		}
	}

	/**
	 * Checks whether or not a booking attribute currently holds a value. This needed for
	 * determining if an update needs to be made (a value exists) or if the attribute has to be
	 * newly assigned with a value (no value exists).
	 *
	 * @param type $a_booking_attr_key the key of the attribute that needs to be checked
	 * @param type $a_old_booking_attr_values the old values
	 * @return boolean true if a value existed beforehand; false otherwise
	 */
	private function hasAttributeValue($a_booking_attr_key, $a_old_booking_attr_values)
	{
		$value_exists = false;
		foreach ($a_old_booking_attr_values as $old_booking_array)
		{
			if ($old_booking_array[attr_id] == $a_booking_attr_key)
			{
				$value_exists = true;
				break;
			}
		}
		return $value_exists;
	}

	/**
	 * Delete a booking attribute assign in the database, by updating the value with an
	 * empty String.
	 *
	 * @param type $a_booking_id
	 * @param type $a_booking_attr_key
	 */
	public function updateDelBookingAttributeAssign($a_booking_id, $a_booking_attr_key)
	{
		$this->ilDB->query('DELETE FROM ' . dbc::BOOKING_TO_ATTRIBUTE_TABLE . ' WHERE booking_id = '
			. $this->ilDB->quote($a_booking_id, 'integer') . ' AND attr_id = '
			. $this->ilDB->quote($a_booking_attr_key, 'integer'));
	}

	/**
	 * Deletes all assignments of an attribute to the bookings.
	 *
	 * @param integer $a_attribute_id
	 * @return integer Affected rows
	 */
	public function deleteAttributeBookingAssign($a_attribute_id)
	{
		return $this->ilDB->manipulate('DELETE FROM ' . dbc::BOOKING_TO_ATTRIBUTE_TABLE .
				' WHERE attr_id = ' . $this->ilDB->quote($a_attribute_id, 'integer'));
	}

	/**
	 * Deletes an booking attribute with given id.
	 *
	 * @param integer $a_attribute_id
	 * @return integer Affected rows
	 */
	public function deleteBookingAttribute($a_attribute_id)
	{
		return $this->ilDB->manipulate('DELETE FROM ' . dbc::BOOKING_ATTRIBUTES_TABLE .
				' WHERE id = ' . $this->ilDB->quote($a_attribute_id, 'integer'));
	}

	/**
	 * Inserts new booking attribute.
	 *
	 * @param string $a_attribute_name
	 */
	public function insertBookingAttribute($a_attribute_name)
	{
		$this->ilDB->insert(dbc::BOOKING_ATTRIBUTES_TABLE, array(
			'id' => array('integer', $this->ilDB->nextID(dbc::BOOKING_ATTRIBUTES_TABLE)),
			'name' => array('text', $a_attribute_name),
			'pool_id' => array('integer', $this->pool_id),
			)
		);
	}

	/**
	 * Renames an booking attribute with given id.
	 *
	 * @param integer $a_attribute_id
	 * @param string $a_changed_attribute_name
	 */
	public function renameBookingAttribute($a_attribute_id, $a_changed_attribute_name)
	{
		$fields = array(
			'name' => array('text', $a_changed_attribute_name),
		);
		$where = array(
			'id' => array("integer", $a_attribute_id),
			'pool_id' => array("integer", $this->pool_id)
		);
		$this->ilDB->update(dbc::BOOKING_ATTRIBUTES_TABLE, $fields, $where);
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
