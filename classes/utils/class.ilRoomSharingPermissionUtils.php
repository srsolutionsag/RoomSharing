<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivileges.php");

/**
 * Util-Class for permission checking
 *
 * @author Robert Heimsoth <rheimsoth@stud.hs-bremen.de>
 */
class ilRoomSharingPermissionUtils {

	private $pool_id;
	private $ilRoomsharingDatabase;
	private $privileges;
	private $owner;
	private $allUserPrivileges;


	/**
	 * Constructor of ilRoomSharingPermissionUtils.
	 *
	 * @global ilObjUser $ilUser
	 *
	 * @param integer    $a_pool_id
	 * @param integer    $a_owner_id
	 */
	public function __construct($a_pool_id, $a_owner_id, ilRoomsharingPrivileges $a_privileges = NULL) {
		global $ilUser;

		$this->pool_id = $a_pool_id;
		$this->ilRoomsharingDatabase = new ilRoomSharingDatabase($this->pool_id);
		if ($a_privileges != NULL) {
			$this->privileges = $a_privileges;
		} else {
			$this->privileges = new ilRoomsharingPrivileges($this->pool_id);
		}
		$this->owner = $a_owner_id;
		$this->user_id = $ilUser->getId();
		$this->allUserPrivileges = $this->getAllUserPrivileges();
	}


	/**
	 * Checks permissions configurated by the roles in the privileges-tab
	 *
	 * @param string  $a_privilege privilege name e.g. "addBooking"
	 * @param integer $a_pool_id   Pool ID
	 *
	 * @return boolean true if this user has the permission, false otherwise
	 */
	public function checkPrivilege($a_privilege) {

		return in_array(strtolower($a_privilege), $this->allUserPrivileges);
	}


	/**
	 * Gets the priority of a user
	 *
	 * @param integer $a_user_id optional user-id
	 *
	 * @return integer user-priority of current logged in user (if parameter was not set) or user-priority of the user with the id given in the param
	 */
	public function getUserPriority($a_user_id = NULL) {
		if ($a_user_id === NULL) {
			$a_user_id = $this->user_id;
		}

		$priority = $this->privileges->getPriorityOfUser($a_user_id);

		if ($this->owner === $a_user_id) {
			$priority = 10;
		}

		return $priority;
	}


	/**
	 * Checks if a user has a higher priority than another user
	 *
	 * @param integer $a_user_id1 user-id 1
	 * @param integer $a_user_id2 user-id 2
	 *
	 * @return boolean true if user with user-id 1 has a higher priority
	 */
	public function checkForHigherPriority($a_user_id1, $a_user_id2) {
		if ($a_user_id1 === NULL || $a_user_id2 === NULL) {
			return false;
		}

		return ($this->getUserPriority($a_user_id1) > $this->getUserPriority($a_user_id2));
	}


	/**
	 * Gets all privileges that the logged in user has
	 *
	 * @param integer $a_pool_id Pool ID
	 *
	 * @return array Array with the privileges
	 */
	public function getAllUserPrivileges() {
		if ($this->owner === $this->user_id) {
			$privileges = $this->privileges->getAllPrivileges();
		} else {
			$privileges = $this->privileges->getPrivilegesForUser($this->user_id);
		}
		$privileges = array_map('strtolower', $privileges);

		return $privileges;
	}


	/**
	 * Set the poolID of bookings
	 *
	 * @param integer $pool_id
	 *            poolID
	 */
	public function setPoolId($pool_id) {
		$this->pool_id = $pool_id;
	}


	/**
	 * Get the PoolID of bookings
	 *
	 * @return integer PoolID
	 */
	public function getPoolId() {
		return (int)$this->pool_id;
	}
}
