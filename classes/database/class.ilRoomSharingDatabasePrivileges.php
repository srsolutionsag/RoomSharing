<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDBConstants.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingNumericUtils.php");

use ilRoomSharingDBConstants as dbc;

/**
 * Class for database queries.
 *
 * @author Malte Ahlering
 *
 * @property ilDB $ilDB
 */
class ilRoomSharingDatabasePrivileges
{
	private $pool_id;
	private $ilDB;

	/**
	 * constructor ilRoomsharingDatabasePrivileges
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
	 * Gets all classes for the pool-id
	 *
	 * @return array Array with classes
	 */
	public function getClasses()
	{
		$set = $this->ilDB->query('SELECT * FROM ' . dbc::CLASSES_TABLE .
			' WHERE pool_id = ' . $this->ilDB->quote($this->pool_id, 'integer'));
		$classes = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$classes[] = $row;
		}
		return $classes;
	}

	/**
	 * Gets all class names for the pool-id.
	 *
	 * @return array Array which contains the class names.
	 */
	public function getClassNames()
	{
		$set = $this->ilDB->query('SELECT name FROM ' . dbc::CLASSES_TABLE .
			' WHERE pool_id = ' . $this->ilDB->quote($this->pool_id, 'integer'));
		$class_names = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$class_names[] = $row['name'];
		}
		return $class_names;
	}

	/**
	 * Gets a specific class
	 *
	 * @param integer $a_class_id Class-ID
	 * @return array Array with the class data of the selected class
	 */
	public function getClassById($a_class_id)
	{
		$set = $this->ilDB->query('SELECT * FROM ' . dbc::CLASSES_TABLE .
			' WHERE id = ' . $this->ilDB->quote($a_class_id, 'integer') .
			' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer'));
		$row = $this->ilDB->fetchAssoc($set);

		return $row;
	}

	/**
	 * Gets all privileges of a class
	 *
	 * @param integer $a_class_id Class-ID
	 * @return array Array with the setted privileges of the selectec class
	 */
	public function getPrivilegesOfClass($a_class_id)
	{
		$set = $this->ilDB->query('SELECT * FROM ' . dbc::CLASS_PRIVILEGES_TABLE .
			' WHERE class_id = ' . $this->ilDB->quote($a_class_id, 'integer'));

		$row = $this->ilDB->fetchAssoc($set);
		//Remove class_id from resultlist, so that only the privileges are in the array
		unset($row['class_id']);
		return $row;
	}

	/**
	 * Sets the locked classes
	 *
	 * @param array $a_class_ids Array with the class ids which should be locked. Classes which are not in the array will be unlocked
	 */
	public function setLockedClasses($a_class_ids)
	{
		if (ilRoomSharingNumericUtils::isPositiveNumber(count($a_class_ids)))
		{
			$st = $this->ilDB->prepareManip('UPDATE ' . dbc::CLASSES_TABLE .
				' SET locked = 1 WHERE ' . $this->ilDB->in('id', $a_class_ids));

			$this->ilDB->execute($st, array_keys($a_class_ids));

			$st2 = $this->ilDB->prepareManip('UPDATE ' . dbc::CLASSES_TABLE .
				' SET locked = 0 WHERE ' . $this->ilDB->in('id NOT', $a_class_ids));

			$this->ilDB->execute($st2, array_keys($a_class_ids));
		}
		else
		{
			$this->ilDB->manipulate('UPDATE ' . dbc::CLASSES_TABLE .
				' SET locked = 0');
		}
	}

	/**
	 * Get all assigned classes (directly or over role-assignment) for a user
	 *
	 * @param integer $a_user_id User-ID
	 * @param array $a_user_role_ids Role-Ids which the user is assigned to
	 * @return array Array with class ids the user is assigned to
	 */
	public function getAssignedClassesForUser($a_user_id, $a_user_role_ids)
	{
		$class_ids = array();
		$st = $this->ilDB->prepare('SELECT id FROM ' . dbc::CLASSES_TABLE . ' LEFT JOIN ' .
			dbc::CLASS_USER_TABLE . ' ON id = class_id WHERE pool_id = ' .
			$this->ilDB->quote($this->pool_id, 'integer') . ' AND (user_id = ' .
			$this->ilDB->quote($a_user_id, 'integer') . ' OR ' .
			$this->ilDB->in("role_id", $a_user_role_ids) . ')');

		$set = $this->ilDB->execute($st, $a_user_role_ids);

		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$class_ids[] = $row['id'];
		}

		return array_unique($class_ids);
	}

	/**
	 * Gets all classes that are currently locked
	 *
	 * @return array Array with class ids currently locked
	 */
	public function getLockedClasses()
	{
		$set = $this->ilDB->query('SELECT id FROM ' . dbc::CLASSES_TABLE .
			' WHERE pool_id = ' . $this->ilDB->quote($this->pool_id, 'integer') .
			' AND locked = 1');
		$locked_class_ids = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$locked_class_ids[] = $row['id'];
		}
		return $locked_class_ids;
	}

	/**
	 * Gets all classes that are currently unlocked
	 *
	 * @return array Array with class ids currently unlocked
	 */
	public function getUnlockedClasses()
	{
		$set = $this->ilDB->query('SELECT id FROM ' . dbc::CLASSES_TABLE .
			' WHERE pool_id = ' . $this->ilDB->quote($this->pool_id, 'integer') .
			' AND locked = 0');
		$unlocked_class_ids = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$unlocked_class_ids[] = $row['id'];
		}
		return $unlocked_class_ids;
	}

	/**
	 * Gets the priority of a specific class
	 *
	 * @param integer $a_class_id Class-ID
	 * @return integer Priority of the class
	 */
	public function getPriorityOfClass($a_class_id)
	{
		$set = $this->ilDB->query('SELECT priority FROM ' . dbc::CLASSES_TABLE .
			' WHERE id = ' . $this->ilDB->quote($a_class_id, 'integer'));
		$row = $this->ilDB->fetchAssoc($set);
		return $row['priority'];
	}

	/**
	 * Sets every privilege of a specific class
	 *
	 * @param integer $a_class_id Class-ID
	 * @param array $a_privileges Array with privileges which should be assigned
	 * @param array $a_no_privileges Array with privileges that are deassigned
	 */
	public function setPrivilegesForClass($a_class_id, $a_privileges, $a_no_privileges)
	{
		if (ilRoomSharingNumericUtils::isPositiveNumber(count($a_class_id)))
		{
			$positive_set = "";
			$negative_set = "";
			foreach ($a_privileges as $privilege)
			{
				$positive_set .= "," . strtolower($privilege) . " = 1";
			}
			foreach ($a_no_privileges as $no_privilege)
			{
				$negative_set .= "," . strtolower($no_privilege) . " = 0";
			}
			if (strlen($positive_set) > 0)
			{
				$positive_set = substr($positive_set, 1);
			}
			if (strlen($negative_set) > 0 && strlen($positive_set) == 0)
			{
				$negative_set = substr($negative_set, 1);
			}
			$this->ilDB->manipulate('UPDATE ' . dbc::CLASS_PRIVILEGES_TABLE .
				' SET ' . $positive_set . $negative_set . ' WHERE class_id = ' . $this->ilDB->quote($a_class_id,
					'integer'));
		}
	}

	/**
	 * Adds a new class to the database
	 *
	 * @param string $a_name Name of the class
	 * @param string $a_description Description of the class
	 * @param integer $a_role_id Role-ID of a possible assigned role
	 * @param integer $a_priority Priority of the class
	 * @param integer $a_copy_class_id Possible class-ID of which the privileges should be copied
	 * @return integer New ID of the inserted class
	 */
	public function insertClass($a_name, $a_description, $a_role_id, $a_priority, $a_copy_class_id)
	{
		$this->ilDB->insert(dbc::CLASSES_TABLE,
			array(
			'id' => array('integer', $this->ilDB->nextId(dbc::CLASSES_TABLE)),
			'name' => array('text', $a_name),
			'description' => array('text', $a_description),
			'priority' => array('integer', $a_priority),
			'role_id' => array('integer', $a_role_id),
			'pool_id' => array('integer', $this->pool_id)
		));
		$insertedID = $this->ilDB->getLastInsertId();

		if (ilRoomSharingNumericUtils::isPositiveNumber($insertedID))
		{
			//Should privileges of another class should be copied?
			if (ilRoomSharingNumericUtils::isPositiveNumber($a_copy_class_id))
			{
				$privilege_array = array('class_id' => array('integer', $insertedID));

				//Get privileges of the class, which should be copied
				$copied_privileges = $this->getPrivilegesOfClass($a_copy_class_id);
				foreach ($copied_privileges as $privilege_key => $privilege_value)
				{
					$privilege_array[$privilege_key] = array('integer', $privilege_value);
				}
				$this->ilDB->insert(dbc::CLASS_PRIVILEGES_TABLE, $privilege_array);
			}
			//else add empty privileges
			else
			{
				$this->ilDB->insert(dbc::CLASS_PRIVILEGES_TABLE,
					array('class_id' => array('integer', $insertedID)));
			}
		}

		return $insertedID;
	}

	/**
	 * Edits the values of an already created class
	 *
	 * @param integer $a_class_id Class-ID which should be edited
	 * @param string $a_name New name
	 * @param string $a_description New description
	 * @param string $a_role_id New role-id of the possible assigned role
	 * @param integer $a_priority New priority
	 */
	public function updateClass($a_class_id, $a_name, $a_description, $a_role_id, $a_priority)
	{
		$fields = array('name' => array('text', $a_name),
			'description' => array('text', $a_description),
			'priority' => array('integer', $a_priority),
			'role_id' => array('integer', $a_role_id),
			'pool_id' => array('integer', $this->pool_id));
		$where = array('id' => array('integer', $a_class_id));
		$this->ilDB->update(dbc::CLASSES_TABLE, $fields, $where);
	}

	/**
	 * Assign a user directly to a class
	 *
	 * @param integer $a_class_id Class-ID
	 * @param integer $a_user_id User-ID of the user which should be assigned
	 */
	public function assignUserToClass($a_class_id, $a_user_id)
	{
		if (!$this->isUserInClass($a_class_id, $a_user_id))
		{
			$this->ilDB->insert(dbc::CLASS_USER_TABLE,
				array(
				'class_id' => array('integer', $a_class_id),
				'user_id' => array('integer', $a_user_id)
			));
		}
	}

	/**
	 * Gets all users directly assigned to a class
	 *
	 * @param integer $a_class_id Class-ID
	 * @return array Array with the assigned user-ids
	 */
	public function getUsersForClass($a_class_id)
	{
		$set = $this->ilDB->query('SELECT user_id FROM ' . dbc::CLASS_USER_TABLE .
			' WHERE class_id = ' . $this->ilDB->quote($a_class_id, 'integer'));
		$assigned_user_ids = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$assigned_user_ids[] = $row['user_id'];
		}
		return $assigned_user_ids;
	}

	/**
	 * Deassign a user from a class
	 *
	 * @param integer $a_class_id Class-ID
	 * @param integer $a_user_id User-ID which should be deassigned from the class
	 */
	public function deassignUserFromClass($a_class_id, $a_user_id)
	{
		$this->ilDB->manipulate("DELETE FROM " . dbc::CLASS_USER_TABLE .
			" WHERE class_id = " . $this->ilDB->quote($a_class_id, 'integer') .
			" AND user_id = " . $this->ilDB->quote($a_user_id, 'integer'));
	}

	/**
	 * Deassign all directly assigned users from a class
	 *
	 * @param integer $a_class_id Class-ID
	 */
	public function clearUsersInClass($a_class_id)
	{
		$this->ilDB->manipulate("DELETE FROM " . dbc::CLASS_USER_TABLE .
			" WHERE class_id = " . $this->ilDB->quote($a_class_id, 'integer'));
	}

	/**
	 * Delete all privileges of a specific class
	 *
	 * @param integer $a_class_id Class-ID of which the privileges should be deleted
	 */
	public function deleteClassPrivileges($a_class_id)
	{
		$this->ilDB->manipulate("DELETE FROM " . dbc::CLASS_PRIVILEGES_TABLE .
			" WHERE class_id = " . $this->ilDB->quote($a_class_id, 'integer'));
	}

	/**
	 * Deletes a class with all its privileges and assignments
	 *
	 * @param integer $a_class_id Class-ID of the class which should be deleted
	 */
	public function deleteClass($a_class_id)
	{
		$this->clearUsersInClass($a_class_id);
		$this->deleteClassPrivileges($a_class_id);
		$this->ilDB->manipulate("DELETE FROM " . dbc::CLASSES_TABLE .
			" WHERE id = " . $this->ilDB->quote($a_class_id, 'integer') .
			' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer'));
	}

	/**
	 * Checks if a specific user is in a specific class
	 *
	 * @param integer $a_class_id Class-ID
	 * @param integer $a_user_id User-ID
	 *
	 * @return boolean true if user is in class, false otherwise
	 */
	public function isUserInClass($a_class_id, $a_user_id)
	{
		$set = $this->ilDB->query('SELECT * FROM ' . dbc::CLASS_USER_TABLE .
			' WHERE class_id = ' . $this->ilDB->quote($a_class_id, 'integer') .
			' AND user_id = ' . $this->ilDB->quote($a_user_id, 'integer'));
		return ($this->ilDB->numRows($set) > 0);
	}

	/**
	 * Gets a priority of a specific user
	 *
	 * @param integer $a_user_id User-ID
	 * @return integer Priority of the user
	 */
	public function getUserPriority($a_user_id)
	{
		$set = $this->ilDB->query('SELECT MAX(priority) AS max_priority FROM ' .
			dbc::CLASSES_TABLE . ' JOIN ' . dbc::CLASS_USER_TABLE .
			' ON id = class_id WHERE user_id = ' . $this->ilDB->quote($a_user_id, 'integer'));

		$userPriorityRow = $this->ilDB->fetchAssoc($set);

		return $userPriorityRow ['max_priority'];
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