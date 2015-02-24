<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingNumericUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/exceptions/class.ilRoomSharingAttributesException.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/attributes/class.ilRoomSharingAttributesConstants.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingPermissionUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivilegesConstants.php");

use ilRoomSharingAttributesConstants as ATTRC;
use ilRoomSharingPrivilegesConstants as PRIVC;

/**
 * Class ilRoomSharingRoomAttributes for room attributes administration.
 *
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 *
 * @version $Id$
 * @property ilRoomsharingDatabase $ilRoomsharingDatabase
 * @property ilRoomSharingPermissionUtils $permission
 */
class ilRoomSharingRoomAttributes
{
	private $pool_id;
	private $allAvailableAttributes = array();
	private $ilRoomsharingDatabase;

	/**
	 * Constructor of ilRoomSharingRoomAttributes
	 *
	 * @param integer $a_pool_id
	 * @param ilRoomsharingDatabase $a_ilRoomsharingDatabase
	 */
	public function __construct($a_pool_id, $a_ilRoomsharingDatabase)
	{
		$this->pool_id = $a_pool_id;
		$this->ilRoomsharingDatabase = $a_ilRoomsharingDatabase;
		$this->allAvailableAttributes = $this->ilRoomsharingDatabase->getAllRoomAttributes();
	}

	/**
	 * Returns all available attributes as names.
	 *
	 * @return array with names
	 */
	public function getAllAvailableAttributesNames()
	{
		$roomAttributesNames = array();
		foreach ($this->allAvailableAttributes as $attribute)
		{
			$roomAttributesNames[] = $attribute['name'];
		}
		return $roomAttributesNames;
	}

	/**
	 * Returns all available attributes with ids and names.
	 *
	 * @return array with ids and names
	 */
	public function getAllAvailableAttributesWithIdAndName()
	{
		$idsWithNames = array();
		foreach ($this->allAvailableAttributes as $attribute)
		{
			$idsWithNames[$attribute['id']] = $attribute['name'];
		}
		return $idsWithNames;
	}

	/**
	 * Renames an attribute with given id.
	 *
	 * @throws ilRoomSharingAttributesException on any violations
	 *
	 * @param integer $a_attribute_id
	 * @param string $a_changed_attribute_name
	 */
	public function renameAttribute($a_attribute_id, $a_changed_attribute_name)
	{
		$this->checkUserPrivileges();
		$this->checkAttributeNameLength($a_changed_attribute_name);
		$this->checkAttributeNameIsFree($a_changed_attribute_name);

		if (!ilRoomSharingNumericUtils::isPositiveNumber($a_attribute_id, true))
		{
			throw new ilRoomSharingAttributesException('rep_robj_xrs_fake_attribute_id_provided');
		}

		$this->ilRoomsharingDatabase->renameRoomAttribute($a_attribute_id, $a_changed_attribute_name);
	}

	/**
	 * Deletes given attribute and associations/assignments to it (rooms - attributes).
	 *
	 * @throws ilRoomSharingAttributesException
	 *
	 * @param integer $a_attribute_id
	 * @return integer number of deleted assignments
	 */
	public function deleteAttribute($a_attribute_id)
	{
		$this->checkUserPrivileges();
		$deletedAssignments = 0;
		if (ilRoomSharingNumericUtils::isPositiveNumber($a_attribute_id, true))
		{
			$deletedAssignments += $this->ilRoomsharingDatabase->deleteAttributeRoomAssign($a_attribute_id);
			$this->ilRoomsharingDatabase->deleteRoomAttribute($a_attribute_id);
		}
		else
		{
			throw new ilRoomSharingAttributesException('rep_robj_xrs_fake_attribute_id_provided');
		}
		return $deletedAssignments;
	}

	/**
	 * Creates an new room attribute with given name.
	 *
	 * @param string $a_attribute_name
	 * @throws ilRoomSharingAttributesException
	 */
	public function createAttribute($a_attribute_name)
	{
		$this->checkUserPrivileges();
		$this->checkAttributeNameLength($a_attribute_name);
		$this->checkAttributeNameIsFree($a_attribute_name);

		$this->ilRoomsharingDatabase->insertRoomAttribute($a_attribute_name);
	}

	/**
	 * Throws an exception if the given attribute name is already used.
	 *
	 * @param string $a_attribute_name
	 * @throws ilRoomSharingAttributesException if the attribute already exists
	 */
	private function checkAttributeNameIsFree($a_attribute_name)
	{
		foreach ($this->getAllAvailableAttributesNames() as $existingName)
		{
			if (strcmp($a_attribute_name, $existingName) === 0)
			{
				throw new ilRoomSharingAttributesException('rep_robj_xrs_attribute_already_exists');
			}
		}
	}

	/**
	 * Throws an exception if the given attribute name is empty or to long.
	 *
	 * @param string $a_attribute_name
	 * @throws ilRoomSharingAttributesException if the attribute already exists
	 */
	private function checkAttributeNameLength($a_attribute_name)
	{
		$nameLength = strlen($a_attribute_name);

		if ($nameLength == 0 || $nameLength > ATTRC::MAX_NAME_LENGTH)
		{
			throw new ilRoomSharingAttributesException('rep_robj_xrs_wrong_attribute_name_provided');
		}
	}

	/**
	 * Checks privileges of the current user.
	 * If the user is not allowed to change room attributes, an exception will be thrown.
	 *
	 * @throws ilRoomSharingAttributesException
	 */
	private function checkUserPrivileges()
	{
		global $rssPermission;
		if (!$rssPermission->checkPrivilege(PRIVC::ADMIN_ROOM_ATTRIBUTES))
		{
			throw new ilRoomSharingAttributesException('rep_robj_xrs_attributes_change_not_allowed');
		}
	}

	/**
	 * Sets the pool id.
	 *
	 * @param integer $a_pool_id
	 */
	public function setPoolId($a_pool_id)
	{
		$this->pool_id = $a_pool_id;
	}

	/**
	 * Returns the pool id.
	 *
	 * @return integer pool id
	 */
	public function getPoolId()
	{
		return (int) $this->pool_id;
	}

}
