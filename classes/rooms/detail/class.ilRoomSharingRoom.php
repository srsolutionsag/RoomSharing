<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingNumericUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/exceptions/class.ilRoomSharingRoomException.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingMailer.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingPermissionUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivilegesConstants.php");

use ilRoomSharingPrivilegesConstants as PRIVC;

/**
 * Class ilRoomSharingRoom.
 * This class is responsible for acquiring information of a room with a given room_id.
 * It is also used for editing and saving room information.
 * If the second argument of the constructor is true (bool), new rooms can be created; otherwise
 * only the editing of rooms is possible.
 *
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 * @author Thomas Wolscht <twolscht@stud.hs-bremen.de>
 *
 * @property ilRoomsharingDatabase $ilRoomsharingDatabase
 * @property ilRoomSharingPermissionUtils $permission
 */
class ilRoomSharingRoom
{
	private $id;
	private $name;
	private $type;
	private $min_alloc;
	private $max_alloc;
	private $file_id;
	private $building_id;
	private $pool_id;
	// associative array for attributes of the  current room. Contains arrays with id, name, count.
	private $attributes = array();
	// associative array for the room allocations. Contains arrays with id, date_from, date_to...
	private $booked_times = array();
	// associative array for all available attributes. Contains arrays with id, name,
	private $all_available_attributes = array();
	private $ilRoomsharingDatabase;
	private $lng;
	private $permission;
	private $ctrl;

	/**
	 * Constructor for ilRoomSharingRoom.
	 * Reads room informatin from the database if a corresponding room_id is given.
	 * The constructor can also be used for creating a room. This is done by setting all informatin
	 * and calling the method create(), which then again returns the room_id of the newly created
	 * room.
	 *
	 * @param type $a_pool_id used for identifying the current pool
	 * @param int $a_room_id the id of the room from where the data should be read from
	 * @param bool $a_create if set to true, a new room can be created
	 */
	public function __construct($a_pool_id, $a_room_id, $a_create = false, ilRoomsharingDatabase $a_db = null)
	{
		global $lng, $rssPermission, $ilCtrl;

		$this->lng = $lng;
		$this->permission = $rssPermission;
		$this->pool_id = $a_pool_id;
		$this->ctrl = $ilCtrl;
		if ($a_db != null)
		{
			$this->ilRoomsharingDatabase = $a_db;
		}
		else
		{
			$this->ilRoomsharingDatabase = new ilRoomsharingDatabase($this->pool_id);
		}
		$this->all_available_attributes = $this->ilRoomsharingDatabase->getAllRoomAttributes();

		if (!$a_create)
		{
			$this->id = $a_room_id;
			$this->read();
		}
	}

	/**
	 * Reads all the relevant room information from the underlying database, if a valid room_id
	 * (non-negative, not empty) is given.
	 */
	public function read()
	{
		if ($this->hasValidId())
		{
			$row = $this->ilRoomsharingDatabase->getRoom($this->id);
			$this->setName($row['name']);
			$this->setType($row['type']);
			$this->setMinAlloc($row['min_alloc']);
			$this->setMaxAlloc($row['max_alloc']);
			$this->setFileId($row['file_id']);
			$this->setBuildingId($row['building_id']);
			$this->setPoolId($row['pool_id']);

			$this->loadAttributesFromDB();
			$this->loadBookedTimes();
		}
	}

	/**
	 * Saves the edited information of a room, but only if a valid room_id is present.
	 */
	public function save()
	{
		if (!$this->permission->checkPrivilege(PRIVC::EDIT_ROOMS))
		{
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_no_permission_for_action"));
			$this->ctrl->redirectByClass('ilinfoscreengui', 'showSummary', 'showSummary');
			return false;
		}
		$this->checkMinMaxAlloc();
		if (!ilRoomSharingNumericUtils::isPositiveNumber($this->min_alloc, true))
		{
			$this->min_alloc = 0;
		}
		$this->updateMainProperties();
		$this->updateAttributes();
		$this->sendChangeNotification();
	}

	/**
	 * Creates a room with the given information with the help of setter-methods.
	 * Make sure that the name, min_alloc, max_alloc and pool_id are set.
	 *
	 * @return integer the room id of the new room, if everything went fine
	 * @throws ilRoomSharingRoomException
	 */
	public function create()
	{
		$this->checkMinMaxAlloc();
		$this->checkNameIsFree();
		$this->checkRoomNameValid();
		$this->checkPoolId();

		if (!ilRoomSharingNumericUtils::isPositiveNumber($this->min_alloc, true))
		{
			$this->min_alloc = 0;
		}

		$this->id = $this->ilRoomsharingDatabase->insertRoom($this->name, $this->type, $this->min_alloc, $this->max_alloc, $this->file_id, $this->building_id);
		$this->insertAttributes();

		return $this->id;
	}

	/**
	 * Checking whether the pool id of the room is valid.
	 *
	 * @throws ilRoomSharingRoomException
	 */
	private function checkPoolId()
	{
		if (!ilRoomSharingNumericUtils::isPositiveNumber($this->pool_id, true))
		{
			throw new ilRoomSharingRoomException('rep_robj_xrs_room_create_failed');
		}
	}

	/**
	 * Checking whether the name of the room is valid.
	 *
	 * @throws ilRoomSharingRoomException
	 */
	private function checkRoomNameValid()
	{
		$valid = !empty($this->name) && strlen($this->name) > 0;
		if (!$valid)
		{
			throw new ilRoomSharingRoomException('rep_robj_xrs_room_create_failed');
		}
	}

	/**
	 * Checks the the room name is free.
	 * Throws an exception if the name is already occupied.
	 *
	 * @throws ilRoomSharingRoomException
	 */
	private function checkNameIsFree()
	{
		$existing_room_names = $this->ilRoomsharingDatabase->getAllRoomNames();

		foreach ($existing_room_names as $existing_room_name)
		{
			if ($this->name == $existing_room_name)
			{
				throw new ilRoomSharingRoomException('rep_robj_xrs_room_name_occupied');
			}
		}
	}

	/**
	 * Deletes a room and all associated information with it.
	 *
	 * @return integer amount of deleted bookings
	 * @throws ilRoomSharingRoomException
	 */
	public function delete()
	{
		if (!$this->permission->checkPrivilege(PRIVC::DELETE_ROOMS))
		{
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_deletion_not_allowed"));
			$this->ctrl->redirectByClass('ilinfoscreengui', 'showSummary', 'showSummary');
			return false;
		}
		$this->ilRoomsharingDatabase->deleteRoom($this->id);
		$this->ilRoomsharingDatabase->deleteAllAttributesForRoom($this->id);
		$number_of_deleted_bookings = $this->ilRoomsharingDatabase->deleteAllBookingsAssignedToRoom($this->id);

		return $number_of_deleted_bookings;
	}

	/**
	 * Returns the amount of bookings the room is assigned to.
	 *
	 * @return integer amount of bookings for the room
	 */
	public function getAmountOfBookings()
	{
		return count($this->ilRoomsharingDatabase->getCurrentBookingsForRoom($this->id));
	}

	/**
	 * Adds an attribute to the room with a specified amount.
	 *
	 * @param int $a_attr_id id of the attribute to be added
	 * @param int $a_amount the amount for the attribute that should be added
	 *
	 * @throws ilRoomSharingRoomException
	 */
	public function addAttribute($a_attr_id, $a_amount)
	{
		if (!ilRoomSharingNumericUtils::isPositiveNumber($a_attr_id, true) || !$this->isAttributeExisting($a_attr_id) || $this->isAttributeDefined($a_attr_id))
		{
			throw new ilRoomSharingRoomException('rep_robj_xrs_add_wrong_attribute');
		}
		if (!ilRoomSharingNumericUtils::isPositiveNumber($a_amount, true))
		{
			throw new ilRoomSharingRoomException('rep_robj_xrs_add_wrong_attribute_count');
		}

		$this->attributes[] = array(
			'id' => $a_attr_id,
			'name' => $this->getAttributeNameById($a_attr_id),
			'count' => $a_amount
		);
	}

	/**
	 * Returns true if the given attribute exists in the database.
	 *
	 * @param integer $a_attr_id the id of the attribute that should be checked.
	 * @return boolean true if the attribute exists; false otherwise
	 */
	private function isAttributeExisting($a_attr_id)
	{
		$attribute_exists = FALSE;
		foreach ($this->all_available_attributes as $available_attribute)
		{
			if ($available_attribute['id'] == $a_attr_id)
			{
				$attribute_exists = TRUE;
				break;
			}
		}
		return $attribute_exists;
	}

	/**
	 * Returns true if the attribute with given id is already defined for the room.
	 *
	 * @param integer $a_attr_id
	 * @return boolean existance of the attribute
	 */
	private function isAttributeDefined($a_attr_id)
	{
		$rVal = false;
		foreach ($this->attributes as $attribute)
		{
			if ($attribute['id'] == $a_attr_id)
			{
				$rVal = true;
				break;
			}
		}
		return $rVal;
	}

	/**
	 * Returns the name of the given attribute id.
	 *
	 * @param integer $a_attr_id the id of the attribute or which the name should be retrieved
	 * @return string the name of the attribute
	 */
	private function getAttributeNameById($a_attr_id)
	{
		foreach ($this->all_available_attributes as $available_attribute)
		{
			if ($available_attribute['id'] == $a_attr_id)
			{
				return $available_attribute['name'];
			}
		}
	}

	/**
	 * Resets the attributes of the room internally.
	 * This method does not affect the database.
	 */
	public function resetAttributes()
	{
		$this->attributes = array();
	}

	/**
	 * Loads all attributes referenced by the room.
	 * If the room id is not set, an empty array will be returned.
	 */
	private function loadAttributesFromDB()
	{
		if ($this->hasValidId())
		{
			$this->attributes = $this->ilRoomsharingDatabase->getAttributesForRoom($this->id);
		}
	}

	/**
	 * Loads booking times of the given room.
	 */
	private function loadBookedTimes()
	{
		if ($this->hasValidId())
		{
			$this->booked_times = $this->ilRoomsharingDatabase->getAllBookingsForRoom($this->id);
		}
	}

	/**
	 * Returns all available attributes, that can be added to a room.
	 *
	 * @return array all available attributes as an associative array
	 */
	public function getAllAvailableAttributes()
	{
		return $this->all_available_attributes;
	}

	/**
	 * Updates the main properties of a room database-wise.
	 */
	private function updateMainProperties()
	{
		if ($this->hasValidId())
		{
			$this->ilRoomsharingDatabase->updateRoomProperties($this->getId(), $this->getName(), $this->getType(), $this->getMinAlloc(), $this->getMaxAlloc(), $this->getFileId(), $this->getBuildingId());
		}
	}

	/**
	 * Updates the attributes of a room database-wise.
	 */
	private function updateAttributes()
	{
		if ($this->hasValidId())
		{
			// delete old attribute associations
			$this->ilRoomsharingDatabase->deleteAllAttributesForRoom($this->id);
			// insert the new associations
			$this->insertAttributes();
		}
	}

	/**
	 * Insert the room attributes into the database.
	 */
	private function insertAttributes()
	{
		if ($this->hasValidId())
		{
			foreach ($this->attributes as $attr)
			{
				$this->ilRoomsharingDatabase->insertAttributeForRoom($this->id, $attr['id'], $attr['count']);
			}
		}
	}

	/**
	 * Checks whether the room id is valid.
	 *
	 * @return bool true if the room id is set and a non-negative number including zero
	 */
	private function hasValidId()
	{
		return ilRoomSharingNumericUtils::isPositiveNumber($this->id, true);
	}

	/**
	 * Checks the min and max allocation fields of the room for validity.
	 * Throws an exception if illegal values are present.
	 *
	 * @throws ilRoomSharingRoomException
	 */
	private function checkMinMaxAlloc()
	{
		$min_alloc_given = !empty($this->min_alloc);
		$min_alloc_valid = ilRoomSharingNumericUtils::isPositiveNumber($this->min_alloc, true);
		$max_alloc_valid = ilRoomSharingNumericUtils::isPositiveNumber($this->max_alloc, true);
		if (($min_alloc_given && !$min_alloc_valid) || !$max_alloc_valid)
		{
			throw new ilRoomSharingRoomException('rep_robj_xrs_illegal_room_min_max_alloc');
		}

		if ($min_alloc_given && $min_alloc_valid && (((int) $this->min_alloc) > ((int) $this->max_alloc)))
		{
			throw new ilRoomSharingRoomException('rep_robj_xrs_illegal_room_min_max_alloc');
		}
	}

	/**
	 * Gets and returns all floorplan titles which are used for the assignment to a room.
	 * The floorplans are presented in dropdown-selection box, with the default value being a
	 * "no assigment".
	 *
	 * @return array array which includes the names of all floorplans and a standard value
	 */
	public function getAllFloorplans()
	{
		$options = array();
		$options["title"] = " - " . $this->lng->txt("rep_robj_xrs_room_no_assign") . " - ";

		foreach ($this->ilRoomsharingDatabase->getAllFloorplans() as $fplans)
		{
			$options[$fplans["file_id"]] = $fplans["title"];
		}
		return $options;
	}

	/**
	 * This method returns the amount of an attribute that is assigned to a room via the id.
	 *
	 * @param integer $a_attribute_id the attribute id for which the amount should be returned
	 * @return integer amount the attribute amount
	 */
	public function getAttributeAmountById($a_attribute_id)
	{
		foreach ($this->getAttributes() as $attr)
		{
			if ($attr['id'] == $a_attribute_id)
			{
				$amount = $attr['count'];
				break;
			}
		}
		return $amount;
	}

	/**
	 * Get the id of the room.
	 *
	 * @return int RoomID
	 */
	public function getId()
	{
		return (int) $this->id;
	}

	/**
	 * Set the room id
	 *
	 * @param int $a_id ID which should be set
	 */
	public function setId($a_id)
	{
		$this->id = $a_id;
	}

	/**
	 * Get the name of the room.
	 *
	 * @return string the name of the room
	 */
	public function getName()
	{
		return (string) $this->name;
	}

	/**
	 * Sets the name of the room
	 *
	 * @param string $a_name the new name for the room
	 */
	public function setName($a_name)
	{
		$this->name = $a_name;
	}

	/**
	 * Gets the type of the room
	 *
	 * @return string the room type
	 */
	public function getType()
	{
		return (string) $this->type;
	}

	/**
	 * Sets the type of the room
	 *
	 * @param string $a_type the room type to be set
	 */
	public function setType($a_type)
	{
		$this->type = $a_type;
	}

	/**
	 * Get the minimum allocation for the room.
	 *
	 * @return int the minimum room allocation
	 */
	public function getMinAlloc()
	{
		return (int) $this->min_alloc;
	}

	/**
	 * Sets the minimum allocation for the room.
	 *
	 * @param integer $a_min_alloc the minimum allocation
	 */
	public function setMinAlloc($a_min_alloc)
	{
		$this->min_alloc = $a_min_alloc;
	}

	/**
	 * Gets the maximum allocation for the room.
	 *
	 * @return integer the maximum allocation.
	 */
	public function getMaxAlloc()
	{
		return (int) $this->max_alloc;
	}

	/**
	 * Sets the maximum allocation of the room
	 *
	 * @param integer $a_max_alloc the maximum allocation
	 */
	public function setMaxAlloc($a_max_alloc)
	{
		$this->max_alloc = $a_max_alloc;
	}

	/**
	 * Gets the file id of the room.
	 *
	 * @return the file id of the room.
	 */
	public function getFileId()
	{
		return (int) $this->file_id;
	}

	/**
	 * Sets the file id of the room
	 *
	 * @param int $a_fileId the new file id
	 */
	public function setFileId($a_fileId)
	{
		$this->file_id = $a_fileId;
	}

	/**
	 * Gets the building id of the room.
	 *
	 * @return integer the building id
	 */
	public function getBuildingId()
	{
		return (int) $this->building_id;
	}

	/**
	 * Sets the buidling of the room.
	 *
	 * @param int $a_buildingId the new buidling id
	 */
	public function setBuildingId($a_buildingId)
	{
		$this->building_id = $a_buildingId;
	}

	/**
	 * Gets the pool id of the room
	 *
	 * @return integer the pool id of the room
	 */
	public function getPoolId()
	{
		return (int) $this->pool_id;
	}

	/**
	 * Sets the pool id of the room
	 *
	 * @param integer $a_poolId the new pool id
	 */
	public function setPoolId($a_poolId)
	{
		$this->pool_id = $a_poolId;
		$this->ilRoomsharingDatabase->setPoolId($a_poolId);
	}

	/**
	 * Gets the attributes of the room.
	 * The array which is returned is an associative one, which includes ids, names, counts.
	 *
	 * @return array attributes of the room as associative array
	 */
	public function getAttributes()
	{
		return (array) $this->attributes;
	}

	/**
	 * Sets attributes of the room
	 *
	 * @param array $a_attributes associative array with the new attributes
	 */
	public function setAttributes($a_attributes)
	{
		$this->attributes = $a_attributes;
	}

	/**
	 * Gets all the booked times that were made for this room.
	 * The return value consists of an associative array with id, date_from, date_to...
	 *
	 * @return array associative array with the booked times
	 */
	public function getBookedTimes()
	{
		return $this->booked_times;
	}

	/**
	 * Sets the booked times for a room.
	 *
	 * @param array $a_booked_times the booked times as an associative array
	 */
	public function setBookedTimes($a_booked_times)
	{
		$this->booked_times = $a_booked_times;
	}

	/**
	 * Send a notification for everyone who has booked this room if
	 * room has changed.
	 * (Not the participants)
	 */
	private function sendChangeNotification()
	{
		global $rssObjectName;
		if (!isset($rssObjectName))
		{
			$mailer = new ilRoomSharingMailer($this->lng, $this->pool_id);
			$mailer->sendRoomChangeMail($this->id);
		}
	}

}
?>
