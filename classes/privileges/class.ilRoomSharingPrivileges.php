<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/exceptions/class.ilRoomSharingPrivilegesException.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingNumericUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivilegesConstants.php");
require_once("Services/AccessControl/classes/class.ilObjRole.php");

use ilRoomSharingPrivilegesConstants as PRIVC;

/**
 * Class ilRoomSharingPrivileges
 *
 * @author Robert Heimsoth <rheimsoth@stud.hs-bremen.de>
 * @author Alexander Keller <a.k3ll3r@gmail.com>
 */
class ilRoomSharingPrivileges
{
	private $pool_id;
	private $ilRoomsharingDatabase;
	private $classes_privileges;
	private $lng;
	private $rbacreview;
	private $rssPermission;

	/**
	 * Constructor of ilRoomSharingPrivileges
	 *
	 * @param integer $a_pool_id
	 */
	public function __construct($a_pool_id)
	{
		global $lng, $rbacreview, $rssPermission;

		$this->pool_id = $a_pool_id;
		$this->lng = $lng;
		$this->rbacreview = $rbacreview;
		$this->rssPermission = $rssPermission;
		$this->ilRoomsharingDatabase = new ilRoomSharingDatabase($this->pool_id);
		//$this->classes_privileges = $this->getAllClassPrivileges();
	}

	public static function withDatabase($a_pool_id, $database)
	{
		$instance = new self($a_pool_id);
		$instance->ilRoomsharingDatabase = $database;
		return $instance;
	}

	/**
	 * Gets the privileges matrix with classes and their privileges set
	 *
	 * @return array Privileges matrix.
	 */
	public function getPrivilegesMatrix()
	{

		$privilegesMatrix = array();

		// Only fill the array if there are more than 0 classes
		if (ilRoomSharingNumericUtils::isPositiveNumber(count($this->getClasses())))
		{

			if ($this->rssPermission->checkPrivilege(PRIVC::LOCK_PRIVILEGES))
			{
				// Locked classes
				$privilegesMatrix[] = array("show_lock_row" => "lock", "locked_classes" => $this->getLockedClasses());
			}

			// ### Appointments ###
			$privilegesMatrix[] = $this->addNewSection("rep_robj_xrs_appointments",
				"rep_robj_xrs_appointments_privileges_description");
			$privilegesMatrix[] = $this->addPrivilege("accessAppointments",
				"rep_robj_xrs_access_appointments", "rep_robj_xrs_access_appointments_description");
			$privilegesMatrix[] = $this->addPrivilege("accessSearch", "rep_robj_xrs_access_search",
				"rep_robj_xrs_access_search_description");
			$privilegesMatrix[] = $this->addPrivilege("addOwnBookings", "rep_robj_xrs_create_edit_delete",
				"rep_robj_xrs_create_edit_delete_description");
			$privilegesMatrix[] = $this->addPrivilege("addParticipants", "rep_robj_xrs_add_participant",
				"rep_robj_xrs_add_participant_description");
			$privilegesMatrix[] = $this->addPrivilege("addSequenceBookings",
				"rep_robj_xrs_sequence_bookings", "rep_robj_xrs_sequence_bookings_addable");
			$privilegesMatrix[] = $this->addPrivilege("addUnlimitedBookings",
				"rep_robj_xrs_add_unlimited_bookings", "rep_robj_xrs_add_unlimited_bookings_description");
			$privilegesMatrix[] = $this->addPrivilege("seeNonPublicBookingInformation",
				"rep_robj_xrs_see_non_public_booking_information",
				"rep_robj_xrs_see_non_public_booking_information_description");
			$privilegesMatrix[] = $this->addPrivilege("accessImport",
				"rep_robj_xrs_import_bookings_from_external_file",
				"rep_robj_xrs_import_bookings_from_external_file_description");
			$privilegesMatrix[] = $this->addPrivilege("adminBookingAttributes",
				"rep_robj_xrs_create_edit_delete_booking_attributes",
				"rep_robj_xrs_create_edit_delete_booking_attributes_description");
			$privilegesMatrix[] = $this->addPrivilege("cancelBookingLowerPriority",
				"rep_robj_xrs_cancel_lower_priority", "rep_robj_xrs_cancel_lower_priority_description");
			$privilegesMatrix[] = $this->addPrivilege("notificationSettings",
				"rep_robj_xrs_notification_tunable", "rep_robj_xrs_notification_tunable_description");
			$privilegesMatrix[] = $this->addSelectMultipleCheckbox("bookings",
				array("accessAppointments", "accessSearch", "addParticipants", "accessImport",
				"addOwnBookings", "addSequenceBookings", "addUnlimitedBookings", "adminBookingAttributes", "cancelBookingLowerPriority",
				"notificationSettings", "seeNonPublicBookingInformation"));

			// ### Rooms ###
			$privilegesMatrix[] = $this->addNewSection("rep_robj_xrs_rooms",
				"rep_robj_xrs_rooms_privileges_description");
			$privilegesMatrix[] = $this->addPrivilege("accessRooms", "rep_robj_xrs_access_rooms",
				"rep_robj_xrs_access_rooms_description");
			$privilegesMatrix[] = $this->addPrivilege("seeBookingsOfRooms",
				"rep_robj_xrs_see_booking_of_rooms", "rep_robj_xrs_see_booking_of_rooms_description");
			$privilegesMatrix[] = $this->addPrivilege("addRooms", "rep_robj_xrs_create",
				"rep_robj_xrs_create_rooms_description");
			$privilegesMatrix[] = $this->addPrivilege("editRooms", "rep_robj_xrs_edit",
				"rep_robj_xrs_create_edit_rooms_description");
			$privilegesMatrix[] = $this->addPrivilege("deleteRooms", "rep_robj_xrs_delete",
				"rep_robj_xrs_create_delete_rooms_description");
			$privilegesMatrix[] = $this->addPrivilege("adminRoomAttributes",
				"rep_robj_xrs_create_edit_delete_room_attributes",
				"rep_robj_xrs_create_edit_delete_room_attributes_description");
			$privilegesMatrix[] = $this->addSelectMultipleCheckbox("rooms",
				array("accessRooms", "seeBookingsOfRooms",
				"addRooms", "editRooms", "deleteRooms", "adminRoomAttributes"));

			// ### Floorplans ###
			$privilegesMatrix[] = $this->addNewSection("rep_robj_xrs_floorplans",
				"rep_robj_xrs_floorplans_privileges_description");
			$privilegesMatrix[] = $this->addPrivilege("accessFloorplans", "rep_robj_xrs_access_floorplans",
				"rep_robj_xrs_access_floorplans_description");
			$privilegesMatrix[] = $this->addPrivilege("addFloorplans", "rep_robj_xrs_create",
				"rep_robj_xrs_create_floorplans_description");
			$privilegesMatrix[] = $this->addPrivilege("editFloorplans", "rep_robj_xrs_edit",
				"rep_robj_xrs_edit_floorplans_description");
			$privilegesMatrix[] = $this->addPrivilege("deleteFloorplans", "rep_robj_xrs_delete",
				"rep_robj_xrs_delete_floorplans_description");
			$privilegesMatrix[] = $this->addSelectMultipleCheckbox("floorplans",
				array("accessFloorplans",
				"addFloorplans", "editFloorplans", "deleteFloorplans"));

			// ### Privileges ###
			$privilegesMatrix[] = $this->addNewSection("rep_robj_xrs_general_privileges",
				"rep_robj_xrs_general_privileges_description");
			$privilegesMatrix[] = $this->addPrivilege("accessSettings", "rep_robj_xrs_access_settings",
				"rep_robj_xrs_access_settings_description");
			$privilegesMatrix[] = $this->addPrivilege("accessPrivileges", "rep_robj_xrs_access_privileges",
				"rep_robj_xrs_access_privileges_description");
			$privilegesMatrix[] = $this->addPrivilege("addClass", "rep_robj_xrs_create_class",
				"rep_robj_xrs_create_class_description");
			$privilegesMatrix[] = $this->addPrivilege("editClass", "rep_robj_xrs_edit_class",
				"rep_robj_xrs_edit_class_description");
			$privilegesMatrix[] = $this->addPrivilege("deleteClass", "rep_robj_xrs_delete_class",
				"rep_robj_xrs_delete_class_description");
			$privilegesMatrix[] = $this->addPrivilege("editPrivileges", "rep_robj_xrs_edit_privileges",
				"rep_robj_xrs_edit_privileges_description");
			$privilegesMatrix[] = $this->addPrivilege("lockPrivileges", "rep_robj_xrs_lock_privileges",
				"rep_robj_xrs_lock_privileges_description");
			$privilegesMatrix[] = $this->addSelectMultipleCheckbox("privileges",
				array("accessSettings",
				"accessPrivileges", "addClass", "editClass", "deleteClass", "editPrivileges", "lockPrivileges"));
		}
		return $privilegesMatrix;
	}

	public function getAllPrivileges()
	{
		$priv = array();
		$priv[] = 'accessAppointments';
		$priv[] = 'accessSearch';
		$priv[] = 'addOwnBookings';
		$priv[] = 'addParticipants';
		$priv[] = 'addSequenceBookings';
		$priv[] = 'addUnlimitedBookings';
		$priv[] = 'seeNonPublicBookingInformation';
		$priv[] = 'notificationSettings';
		$priv[] = 'adminBookingAttributes';
		$priv[] = 'cancelBookingLowerPriority';
		$priv[] = 'accessRooms';
		$priv[] = 'seeBookingsOfRooms';
		$priv[] = 'addRooms';
		$priv[] = 'editRooms';
		$priv[] = 'deleteRooms';
		$priv[] = 'adminRoomAttributes';
		$priv[] = 'accessFloorplans';
		$priv[] = 'addFloorplans';
		$priv[] = 'editFloorplans';
		$priv[] = 'deleteFloorplans';
		$priv[] = 'accessSettings';
		$priv[] = 'accessPrivileges';
		$priv[] = 'addClass';
		$priv[] = 'editClass';
		$priv[] = 'deleteClass';
		$priv[] = 'editPrivileges';
		$priv[] = 'lockPrivileges';
		$priv[] = 'accessImport';

		return $priv;
	}

	/**
	 * Gets all classes assigned to this pool
	 *
	 * @return array Array with classes and their data plus their assigned role name
	 */
	public function getClasses()
	{
		$cls = array();
		$classes = $this->ilRoomsharingDatabase->getClasses();
		foreach ($classes as $class)
		{
			$cls_values = $class;
			$cls_values['role'] = $this->getParentRoleTitle($class['role_id']);
			$cls[] = $cls_values;
		}

		return $cls;
	}

	/**
	 * Returns the names of all existing classes for this pool.
	 *
	 * @return array an array with class names
	 */
	public function getClassNames()
	{
		return $this->ilRoomsharingDatabase->getClassNames();
	}

	/**
	 * Gets a specific class values by it's id
	 *
	 * @param integer $a_class_id Class-ID
	 * @return array Array with the values of the selected class
	 */
	public function getClassById($a_class_id)
	{
		return $this->ilRoomsharingDatabase->getClassById($a_class_id);
	}

	/**
	 * Gets all assigned classes (direct- or role-assignment) for a user
	 *
	 * @param integer $a_user_id User-ID
	 * @return array Array with the assigned classes
	 */
	public function getAssignedClassesForUser($a_user_id)
	{
		$user_roles = $this->rbacreview->assignedRoles($a_user_id);
		return $this->ilRoomsharingDatabase->getAssignedClassesForUser($a_user_id, $user_roles);
	}

	/**
	 * Gets the priority of a user
	 *
	 * @param integer $a_user_id User-ID
	 * @return integer Priority of User or 0 if not set
	 */
	public function getPriorityOfUser($a_user_id)
	{
		return $this->ilRoomsharingDatabase->getUserPriority($a_user_id);
	}

	/**
	 * Gets all privileges for a user
	 *
	 * @param integer $a_user_id User-ID
	 * @return array Array with setted user privileges, unsetted are not in this array
	 */
	public function getPrivilegesForUser($a_user_id)
	{
		$user_classes = $this->getAssignedClassesForUser($a_user_id);
		$user_privileges = array();
		$this->classes_privileges = $this->getAllClassPrivileges();
		foreach ($user_classes as $user_class)
		{
			if ($this->getClassById($user_class)['locked'] == 1)
			{
				continue;
			}

			foreach ($this->classes_privileges[$user_class] as $class_privilege)
			{
				if (!in_array($class_privilege, $user_privileges))
				{
					$user_privileges[] = $class_privilege;
				}
			}
		}
		return $user_privileges;
	}

	/**
	 * Gets all users assigned (directly or over role-assignment) of a class
	 *
	 * @param integer $a_class_id Class-ID
	 * @return array Array with user-data of the users, assigned to the class
	 */
	public function getAssignedUsersForClass($a_class_id)
	{
		$assigned_user_ids = $this->ilRoomsharingDatabase->getUsersForClass($a_class_id);
		$assigned_users = array();
		foreach ($assigned_user_ids as $assigned_user_id)
		{
			$user_name = ilObjUser::_lookupName($assigned_user_id);

			$user_data = array();
			$user_data['login'] = ilObjUser::_lookupLogin($assigned_user_id);
			$user_data['firstname'] = $user_name['firstname'];
			$user_data['lastname'] = $user_name['lastname'];
			$user_data['id'] = $assigned_user_id;

			$assigned_users[] = $user_data;
		}

		return $assigned_users;
	}

	/**
	 * Get all roles that are available in the pool
	 *
	 * @return array Array with the roles and their id's and title's
	 */
	public function getParentRoles()
	{
		$roles = $this->rbacreview->getParentRoleIds($_GET['ref_id']);
		$global_roles = array();
		foreach ($roles as $role)
		{
			$role_id = $role['rol_id'];
			$role_type = $role['role_type'];
			$role_title = $role['title'];

			if ($role_type == "local")
			{
				$transl_role_title = ilObjRole::_getTranslation($role['title']);
				$object_id_of_role = $this->rbacreview->getObjectOfRole($role_id);
				$object_title_of_role = ilObject::_lookupTitle($object_id_of_role);
				$role_and_group_title = $transl_role_title . " von \"" . $object_title_of_role . "\"";
				$global_roles[] = array('id' => $role_id, 'title' => $role_and_group_title);
			}
			else
			{
				$global_roles[] = array('id' => $role_id, 'title' => $role_title);
			}
		}
		return $global_roles;
	}

	/**
	 * Gets a title of a role
	 *
	 * @param integer $a_role_id Role-ID of which the title is unknown
	 * @return string Role-Title
	 */
	public function getParentRoleTitle($a_role_id)
	{
		$roles = $this->getParentRoles();
		$roleName = null;
		foreach ($roles as $role)
		{
			if ($role['id'] == $a_role_id)
			{
				$roleName = $role['title'];
			}
		}
		return $roleName;
	}

	/**
	 * Adds a new class to the pool
	 *
	 * @param array $a_classData Array with class data submitted by the frontend
	 * @throws ilRoomSharingPrivilegesException
	 */
	public function addClass($a_classData)
	{
		$insertedID = $this->ilRoomsharingDatabase->insertClass($a_classData['name'],
			$a_classData['description'], $a_classData['role_id'], $a_classData['priority'],
			$a_classData['copied_class_privileges']);
		if (!ilRoomSharingNumericUtils::isPositiveNumber($insertedID))
		{
			throw new ilRoomSharingPrivilegesException("rep_robj_xrs_class_not_created");
		}
	}

	/**
	 * Edits a already created class values
	 *
	 * @param array $a_classData Array with class data submitted by the frontend
	 * @throws ilRoomSharingPrivilegesException
	 */
	public function editClass($a_classData)
	{
		if (!ilRoomSharingNumericUtils::isPositiveNumber($a_classData['id']))
		{
			throw new ilRoomSharingPrivilegesException("rep_robj_xrs_class_id_incorrect");
		}
		$this->ilRoomsharingDatabase->updateClass($a_classData['id'], $a_classData['name'],
			$a_classData['description'], $a_classData['role_id'], $a_classData['priority']);
	}

	/**
	 * Deletes a class of the pool
	 *
	 * @param integer $a_class_id Class-ID of the class which should be deleted
	 */
	public function deleteClass($a_class_id)
	{
		$this->ilRoomsharingDatabase->deleteClass($a_class_id);
	}

	/**
	 * Assign users directly to a class
	 *
	 * @param integer $a_class_id Class-ID of the class where the users should be assigned
	 * @param array $a_user_ids Array with user ids which should be assigned to the class
	 */
	public function assignUsersToClass($a_class_id, $a_user_ids)
	{
		foreach ($a_user_ids as $user_id)
		{
			$this->ilRoomsharingDatabase->assignUserToClass($a_class_id, $user_id);
		}
	}

	/**
	 * Deassign specific directly assigned users from a class
	 *
	 * @param integer $a_class_id Class-ID of the class where the users should be deassigned from
	 * @param array $a_users_ids Array with user ids which should be deassigned from the class
	 */
	public function deassignUsersFromClass($a_class_id, $a_users_ids)
	{
		foreach ($a_users_ids as $user_id)
		{
			$this->ilRoomsharingDatabase->deassignUserFromClass($a_class_id, $user_id);
		}
	}

	/**
	 * Sets all privileges for each class submitted by the frontend
	 *
	 * @param array $a_privileges Array with the classes which each contains an array with their setted privileges
	 */
	public function setPrivileges($a_privileges)
	{
		if (empty($a_privileges))
		{
			$this->unsetAllPrivileges();
		}
		else
		{
			foreach ($a_privileges as $class_id => $given_privileges)
			{
				$privileges = array();
				foreach ($given_privileges as $given_privilege_key => $val)
				{
					$privileges[] = $given_privilege_key;
				}
				$no_privileges = array_diff($this->getAllPrivileges(), $privileges);
				$this->ilRoomsharingDatabase->setPrivilegesForClass($class_id, $privileges, $no_privileges);
			}
		}
	}

	/**
	 * Unsets all privileges of each classes
	 */
	private function unsetAllPrivileges()
	{
		$privileges = $this->getAllPrivileges();
		$classes = $this->getClasses();
		foreach ($classes as $class)
		{
			$this->ilRoomsharingDatabase->setPrivilegesForClass($class["id"], array(), $privileges);
		}
	}

	/**
	 * Sets the locked classes
	 *
	 * @param array $a_class_ids Class-IDs which should be locked. Classes not in this array will be unlocked
	 */
	public function setLockedClasses($a_class_ids)
	{
		$this->ilRoomsharingDatabase->setLockedClasses($a_class_ids);
	}

	/**
	 * Gets all classes that are currently locked
	 *
	 * @return array All currently locked classes
	 */
	public function getLockedClasses()
	{
		return $this->ilRoomsharingDatabase->getLockedClasses();
	}

	/**
	 * Gets all classes that are currently not locked
	 *
	 * @return array All currently not locked classes
	 */
	public function getUnlockedClasses()
	{
		return $this->ilRoomsharingDatabase->getUnlockedClasses();
	}

	/**
	 * Adds a new Table-Section Header
	 *
	 * @param string $a_section_title_lng_key Section-Title language key
	 * @param string $a_section_description_lng_key Optional Section-Description language key
	 *
	 * @return array Array with the new section-information for privilege matrix
	 */
	private function addNewSection($a_section_title_lng_key, $a_section_description_lng_key = null)
	{
		return array("show_section_info" => 1, "section" =>
			array("title" => $this->lng->txt($a_section_title_lng_key),
				"description" => $this->lng->txt($a_section_description_lng_key)
			)
		);
	}

	/**
	 * Adds a new privilege
	 *
	 * @param string $a_id Privilege-ID
	 * @param string $a_name_lng_key Privilege-Name Language Key
	 * @param string $a_description_lng_key Privilege-Description Language Key
	 *
	 * @return array Array with the new privilege information for privilege matrix
	 */
	private function addPrivilege($a_id, $a_name_lng_key, $a_description_lng_key)
	{
		return array("privilege" => array(
				"id" => $a_id,
				"name" => $this->lng->txt($a_name_lng_key),
				"description" => $this->lng->txt($a_description_lng_key)),
			"classes" => $this->getClassPrivilegeValue($a_id)
		);
	}

	/**
	 * Get each privilege for every class
	 *
	 * @return array Array with classes which contains an array with their privileges
	 */
	public function getAllClassPrivileges()
	{
		$privileges = array();
		$classes = $this->ilRoomsharingDatabase->getClasses();
		if (is_array($classes))
		{
			foreach ($classes as $class)
			{
				$privileges[$class['id']] = array();
				$cls_privileges = $this->ilRoomsharingDatabase->getPrivilegesOfClass($class['id']);
				if (is_array($cls_privileges))
				{
					foreach ($cls_privileges as $privilege_id => $privilege_value)
					{
						if ($privilege_value == 1)
						{
							$privileges[$class['id']][] = $privilege_id;
						}
					}
				}
			}
		}
		return $privileges;
	}

	/**
	 * Get each class values to the specific privilege
	 *
	 * @param string $a_privilege_id Privilege ID
	 *
	 * @return array Array with the class-ids and if this privilege is set or not
	 */
	private function getClassPrivilegeValue($a_privilege_id)
	{
		$privilegesArray = array();
		$this->classes_privileges = $this->getAllClassPrivileges();
		foreach ($this->classes_privileges as $class_id => $class_privileges_ids)
		{
			if (in_array(strtolower($a_privilege_id), $class_privileges_ids))
			{
				$privilegesArray[] = array("id" => $class_id, "privilege_set" => true);
			}
			else
			{
				$privilegesArray[] = array("id" => $class_id, "privilege_set" => false);
			}
		}
		return $privilegesArray;
	}

	/**
	 * Add the checkbox values for a multiple select checkbox to select more checkboxes with one
	 *
	 * @param string $a_type Used for ID of the checkbox
	 * @param array $a_privilege_ids Privilege IDs of privileges which should be checked by checking this checkbox
	 *
	 * @return array Checkbox Values for privelege matrix
	 */
	private function addSelectMultipleCheckbox($a_type, $a_privilege_ids)
	{
		return array("show_select_all" => 1, "type" => $a_type, "privileges" => $a_privilege_ids);
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
