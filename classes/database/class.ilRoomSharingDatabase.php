<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDBConstants.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabaseFloorplan.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabasePrivileges.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabaseRoom.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabaseRoomAttribute.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabaseBooking.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabaseBookingAttribute.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabaseParticipants.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabaseCalender.php");

use ilRoomSharingDBConstants as dbc;

/**
 * Class for database queries.
 *
 * @author Malte Ahlering
 *
 * @property ilDB $ilDB
 */
class ilRoomSharingDatabase {

	private $pool_id;
	private $ilDB;
	private $ilRoomSharingDatabaseFloorplan;
	private $ilRoomSharingDatabaseCalender;
	private $ilRoomSharingDatabasePrivileges;
	private $ilRoomSharingDatabaseRoom;
	private $ilRoomSharingDatabaseRoomAttribute;
	private $ilRoomSharingDatabaseBooking;
	private $ilRoomSharingDatabaseBookingAttribute;
	private $ilRoomSharingDatabaseParticipants;


	/**
	 * constructor ilRoomsharingDatabase
	 *
	 * @param integer $a_pool_id
	 */
	public function __construct($a_pool_id) {
		global $ilDB; // Database-Access-Class
		$this->ilDB = $ilDB;
		$this->pool_id = $a_pool_id;
		$this->ilRoomSharingDatabaseFloorplan = new ilRoomSharingDatabaseFloorplan($a_pool_id);
		$this->ilRoomSharingDatabaseCalender = new ilRoomSharingDatabaseCalendar($a_pool_id, $this);
		$this->ilRoomSharingDatabasePrivileges = new ilRoomSharingDatabasePrivileges($a_pool_id);
		$this->ilRoomSharingDatabaseRoom = new ilRoomSharingDatabaseRoom($a_pool_id);
		$this->ilRoomSharingDatabaseRoomAttribute = new ilRoomSharingDatabaseRoomAttribute($a_pool_id);
		$this->ilRoomSharingDatabaseBooking = new ilRoomSharingDatabaseBooking($a_pool_id, $this);
		$this->ilRoomSharingDatabaseBookingAttribute = new ilRoomSharingDatabaseBookingAttribute($a_pool_id);
		$this->ilRoomSharingDatabaseParticipants = new ilRoomSharingDatabaseParticipants($a_pool_id, $this);
	}


	/**
	 * Deletes the db entry of the actual room sharing pool.
	 * If you are sure what you are doing, pass "SURE" as argument.
	 *
	 * @param string $a_confirmation pass "SURE"
	 */
	public function deletePoolEntry($a_confirmation) {
		if ($a_confirmation == "SURE") {
			$this->ilDB->manipulate("DELETE FROM " . dbc::POOLS_TABLE . " WHERE id = " . $this->ilDB->quote($this->pool_id, 'integer'));
		}
	}


	/**
	 * Returns the file id for the room agreement of a certain pool.
	 *
	 * @return type return of $ilDB->query
	 */
	public function getRoomAgreementId() {
		$set = $this->ilDB->query('SELECT * FROM ' . dbc::POOLS_TABLE . ' WHERE id = ' . $this->ilDB->quote($this->pool_id, 'integer')
			. ' order by rooms_agreement DESC');

		$row = $this->ilDB->fetchAssoc($set);

		return $row["rooms_agreement"];
	}


	/**
	 * Gets a user by its id.
	 *
	 * @param integer $a_user_id
	 *
	 * @return type return of $ilDB->query
	 */
	public function getUserById($a_user_id) {
		$set = $this->ilDB->query('SELECT firstname, lastname, login' . ' FROM usr_data' . ' WHERE usr_id = '
			. $this->ilDB->quote($a_user_id, 'integer'));

		return $this->ilDB->fetchAssoc($set);
	}


	/**
	 * Gets a user-id by its user-name.
	 *
	 * @param string $a_user_name
	 *
	 * @return type return of $ilDB->query
	 */
	public function getUserIdByUsername($a_user_name) {
		return ilObjUser::_lookupId($a_user_name);
	}


	/**
	 * Get the max book time from the pool.
	 * Format 1970-01-01 XX:XX:00
	 *
	 * @return string timestamp
	 */
	public function getMaxBookTime() {
		$set = $this->ilDB->query('SELECT max_book_time FROM ' . dbc::POOLS_TABLE . ' WHERE id = ' . $this->ilDB->quote($this->pool_id, 'integer'));
		$row = $this->ilDB->fetchAssoc($set);

		return $row['max_book_time'];
	}


	/**
	 * Insert a bboking into the database.
	 *
	 * @global type $this ->ilDB
	 * @global type $ilUser
	 *
	 * @param array $a_booking_values
	 *                    Array with the values of the booking
	 * @param array $a_booking_attr_values
	 *                    Array with the values of the booking-attributes
	 *
	 * @return integer 1 = successful, -1 not successful
	 */
	public function insertBooking($a_booking_attr_values, $a_booking_values, $a_booking_participants) {
		return $this->ilRoomSharingDatabaseBooking->insertBooking($a_booking_attr_values, $a_booking_values, $a_booking_participants);
	}


	/**
	 * This method inserts a booking recurrence by inserting a set of data.
	 *
	 * @param array $a_booking_values
	 *            Array with the values of the booking
	 * @param array $a_booking_attr_values
	 *            Array with the values of the booking-attributes
	 *
	 * @return integer 1 = successful, -1 not successful
	 */
	public function insertBookingRecurrence($a_booking_attr_values, $a_booking_values, $a_booking_participants) {
		return $this->ilRoomSharingDatabaseBooking->insertBookingRecurrence($a_booking_attr_values, $a_booking_values, $a_booking_participants);
	}


	/**
	 * Delete a booking from the database.
	 *
	 * @param integer $a_booking_id
	 */
	public function deleteBooking($a_booking_id) {
		$this->ilRoomSharingDatabaseBooking->deleteBooking($a_booking_id);
	}


	/**
	 * Delete bookings from the database.
	 *
	 * @param array $booking_ids
	 */
	public function deleteBookings($booking_ids) {
		$this->ilRoomSharingDatabaseBooking->deleteBookings($booking_ids);
	}


	/**
	 * Get all bookings related to a given sequence.
	 *
	 * @param integer $a_seq_id
	 *
	 * @return type return of $this->ilDB->query
	 */
	public function getAllBookingIdsForSequence($a_seq_id) {
		return $this->ilRoomSharingDatabaseBooking->getAllBookingIdsForSequence($a_seq_id);
	}


	/**
	 * Gets User and Sequence for a booking.
	 *
	 * @param integer $a_booking_id
	 *
	 * @return type return of $this->ilDB->query
	 */
	public function getSequenceAndUserForBooking($a_booking_id) {
		return $this->ilRoomSharingDatabaseBooking->getSequenceAndUserForBooking($a_booking_id);
	}


	/**
	 * Gets all bookings for a user.
	 *
	 * @param integer $a_user_id
	 *
	 * @return type return of $this->ilDB->query
	 */
	public function getBookingsForUser($a_user_id) {
		return $this->ilRoomSharingDatabaseBooking->getBookingsForUser($a_user_id);
	}


	/**
	 * Gets all bookings filtered by given criteria.
	 *
	 * @param array $filter filter criteria
	 *
	 * @return array
	 */
	public function getFilteredBookings(array $filter) {
		return $this->ilRoomSharingDatabaseBooking->getFilteredBookings($filter);
	}


	/**
	 * Gets a booking.
	 *
	 * @param integer $a_booking_id
	 *
	 * @return type return of $this->ilDB->query
	 */
	public function getBooking($a_booking_id) {
		return $this->ilRoomSharingDatabaseBooking->getBooking($a_booking_id);
	}


	/**
	 * Gets and returns all bookings that have been made; even the ones in the past.
	 *
	 * @param integer $a_room_id the id of the room for which the bookings should be returnd
	 *
	 * @return array an array containing information about bookings for the specified room
	 */
	public function getAllBookingsForRoom($a_room_id) {
		return $this->ilRoomSharingDatabaseBooking->getAllBookingsForRoom($a_room_id);
	}


	/**
	 * Gets all bookings for a room which are in present or future.
	 *
	 * @param integer $a_room_id the room id
	 *
	 * @return array list of bookings
	 */
	public function getBookingsForRoomThatAreValid($a_room_id) {
		return $this->ilRoomSharingDatabaseBooking->getBookingsForRoomThatAreValid($a_room_id);
	}


	/**
	 * Gets all actual bookings for a room.
	 *
	 * @return type return of $this->ilDB->query
	 */
	public function getAllBookingsIds() {
		return $this->ilRoomSharingDatabaseBooking->getAllBookingsIds();
	}


	/*
	 * Gets all booking ids for one room in given datetime ranges.
	 *
	 * @param integer $a_room_id
	 * @param array $a_datetimes_from
	 * @param array $a_datetimes_to
	 * @return array booking ids
	 */
	public function getBookingIdsForRoomInDateimeRanges($a_room_id, $a_datetimes_from, $a_datetimes_to) {
		return $this->ilRoomSharingDatabaseBooking->getBookingIdsForRoomInDateimeRanges($a_room_id, $a_datetimes_from, $a_datetimes_to);
	}


	/**
	 * Updating a booking in the database.
	 *
	 * @global type $ilUser
	 *
	 * @param type  $a_booking_id
	 * @param type  $a_booking_attr_values
	 * @param type  $a_booking_values
	 * @param type  $a_booking_participants
	 *
	 * @return int
	 */
	public function updateBooking($a_booking_id, $a_booking_attr_values, $a_old_booking_attr_values, $a_booking_values, $a_old_booking_values, $a_booking_participants, $a_old_booking_participants) {
		return $this->ilRoomSharingDatabaseBooking->updateBooking($a_booking_id, $a_booking_attr_values, $a_old_booking_attr_values, $a_booking_values, $a_old_booking_values, $a_booking_participants, $a_old_booking_participants);
	}


	/**
	 * Get all other the booking-ids for a room-id that in the given timerange.
	 *
	 * @param string $a_date_from
	 * @param string $a_date_to
	 * @param string $a_room_id
	 *
	 * @parm integer $a_booking_id
	 *
	 * @return array values = all other book ids for the given room ids in the time range
	 */
	public function getBookingIdForRoomInDateTimeRange($a_date_from, $a_date_to, $a_room_id, $a_booking_id) {
		return $this->ilRoomSharingDatabaseBooking->getBookingIdForRoomInDateTimeRange($a_date_from, $a_date_to, $a_room_id, $a_booking_id);
	}


	/**
	 * Returns the info for a booking
	 * (title, user, description, room, start, end)
	 *
	 * @param type $booking_id
	 *
	 * @return array the booking info
	 */
	public function getInfoForBooking($booking_id) {
		return $this->ilRoomSharingDatabaseBooking->getInfoForBooking($booking_id);
	}


	/**
	 * Gathers all current bookings that have been made for this room.
	 *
	 * @param integer $a_room_id the id of the room for which the current bookings should be returned
	 *
	 * @return array an array containing information regarding the bookings
	 */
	public function getCurrentBookingsForRoom($a_room_id) {
		return $this->ilRoomSharingDatabaseBooking->getCurrentBookingsForRoom($a_room_id);
	}


	/**
	 * Deletes all bookings that are assigned to a room.
	 *
	 * @param integer $a_room_id the id of the room for which the bookings should be deleted
	 *
	 * @return integer the amount of bookings that are affected by the deletion
	 */
	public function deleteAllBookingsAssignedToRoom($a_room_id) {
		return $this->ilRoomSharingDatabaseBooking->deleteAllBookingsAssignedToRoom($a_room_id);
	}


	/**
	 * Gets all bookings for a room in a time span
	 *
	 * @param $room_id
	 * @param $start
	 * @param $end
	 * @param $type
	 *
	 * @return array bookings
	 */
	public function getBookingsForRoomInTimeSpan($room_id, $start, $end, $type) {
		return $this->ilRoomSharingDatabaseBooking->getBookingsForRoomInTimeSpan($room_id, $start, $end, $type);
	}


	/**
	 * Gets the calendar-id of the current RoomSharing-Pool
	 *
	 * @return integer calendar-id
	 */
	public function getCalendarId() {
		return $this->ilRoomSharingDatabaseCalender->getCalendarId();
	}


	/**
	 * Updates rep_robj_xrs_pools with an new calendar-id.
	 *
	 * Typically only called once per pool.
	 *
	 * @param type $a_cal_id
	 *
	 * @return type
	 */
	public function setCalendarId($a_cal_id) {
		return $this->ilRoomSharingDatabaseCalender->setCalendarId($a_cal_id);
	}


	/**
	 * Delete calendar entries of a booking from the database.
	 *
	 * @param integer $a_booking_id
	 */
	public function deleteCalendarEntryOfBooking($a_booking_id) {
		$this->ilRoomSharingDatabaseCalender->deleteCalendarEntryOfBooking($a_booking_id);
	}


	/**
	 * Delete calendar entries of a booking from the database.
	 *
	 * @param integer $a_booking_id
	 */
	public function updatingCalendarEntryOfBooking($a_booking_id) {
		$this->ilRoomSharingDatabaseCalender->updatingCalendarEntryOfBooking($a_booking_id);
	}


	/** Delete calendar of current pool.
	 *
	 * @param int $cal_id
	 */
	public function deleteCalendar($cal_id) {
		$this->ilRoomSharingDatabaseCalender->deleteCalendar($cal_id);
	}


	/**
	 * Delete calendar entries of bookings from the database.
	 *
	 * @param array $a_booking_ids
	 */
	public function deleteCalendarEntriesOfBookings($a_booking_ids) {
		$this->ilRoomSharingDatabaseCalender->deleteCalendarEntriesOfBookings($a_booking_ids);
	}


	/**
	 * Update an appointment in the RoomSharing-Calendar and save id in booking-table.
	 * This methode delete first all existings calendarEntrys of the given booking id
	 * and then it create new one with the given booking id.
	 *
	 * @param type $a_booking_id
	 * @param type $a_booking_values
	 */
	public function updateBookingAppointment($a_booking_id, $a_booking_values) {
		$this->ilRoomSharingDatabaseCalender->updateBookingAppointment($a_booking_id, $a_booking_values);
	}


	/*
	 * Creates an appointment in the RoomSharing-Calendar and save id in booking-table.
	 *
	 * @param $title string appointment-title
	 * @param $time_start start-time
	 * @param $time_end end-time
	 */
	public function insertBookingAppointment($insertedId, $a_booking_values, $from, $to) {
		$this->ilRoomSharingDatabaseCalender->insertBookingAppointment($insertedId, $a_booking_values, $from, $to);
	}


	/**
	 * Gets all classes for the pool-id
	 *
	 * @return array Array with classes
	 */
	public function getClasses() {
		return $this->ilRoomSharingDatabasePrivileges->getClasses();
	}


	/**
	 * Gets all class names for the pool-id.
	 *
	 * @return array Array which contains the class names.
	 */
	public function getClassNames() {
		return $this->ilRoomSharingDatabasePrivileges->getClassNames();
	}


	/**
	 * Gets a specific class
	 *
	 * @param integer $a_class_id Class-ID
	 *
	 * @return array Array with the class data of the selected class
	 */
	public function getClassById($a_class_id) {
		return $this->ilRoomSharingDatabasePrivileges->getClassById($a_class_id);
	}


	/**
	 * Gets all privileges of a class
	 *
	 * @param integer $a_class_id Class-ID
	 *
	 * @return array Array with the setted privileges of the selectec class
	 */
	public function getPrivilegesOfClass($a_class_id) {
		return $this->ilRoomSharingDatabasePrivileges->getPrivilegesOfClass($a_class_id);
	}


	/**
	 * Sets the locked classes
	 *
	 * @param array $a_class_ids Array with the class ids which should be locked. Classes which are not in the array will be unlocked
	 */
	public function setLockedClasses($a_class_ids) {
		$this->ilRoomSharingDatabasePrivileges->setLockedClasses($a_class_ids);
	}


	/**
	 * Get all assigned classes (directly or over role-assignment) for a user
	 *
	 * @param integer $a_user_id       User-ID
	 * @param array   $a_user_role_ids Role-Ids which the user is assigned to
	 *
	 * @return array Array with class ids the user is assigned to
	 */
	public function getAssignedClassesForUser($a_user_id, $a_user_role_ids) {
		return $this->ilRoomSharingDatabasePrivileges->getAssignedClassesForUser($a_user_id, $a_user_role_ids);
	}


	/**
	 * Gets all classes that are currently locked
	 *
	 * @return array Array with class ids currently locked
	 */
	public function getLockedClasses() {
		return $this->ilRoomSharingDatabasePrivileges->getLockedClasses();
	}


	/**
	 * Gets all classes that are currently unlocked
	 *
	 * @return array Array with class ids currently unlocked
	 */
	public function getUnlockedClasses() {
		return $this->ilRoomSharingDatabasePrivileges->getUnlockedClasses();
	}


	/**
	 * Gets the priority of a specific class
	 *
	 * @param integer $a_class_id Class-ID
	 *
	 * @return integer Priority of the class
	 */
	public function getPriorityOfClass($a_class_id) {
		return $this->ilRoomSharingDatabasePrivileges->getPriorityOfClass($a_class_id);
	}


	/**
	 * Sets every privilege of a specific class
	 *
	 * @param integer $a_class_id      Class-ID
	 * @param array   $a_privileges    Array with privileges which should be assigned
	 * @param array   $a_no_privileges Array with privileges that are deassigned
	 */
	public function setPrivilegesForClass($a_class_id, $a_privileges, $a_no_privileges) {
		$this->ilRoomSharingDatabasePrivileges->setPrivilegesForClass($a_class_id, $a_privileges, $a_no_privileges);
	}


	/**
	 * Adds a new class to the database
	 *
	 * @param string  $a_name          Name of the class
	 * @param string  $a_description   Description of the class
	 * @param integer $a_role_id       Role-ID of a possible assigned role
	 * @param integer $a_priority      Priority of the class
	 * @param integer $a_copy_class_id Possible class-ID of which the privileges should be copied
	 *
	 * @return integer New ID of the inserted class
	 */
	public function insertClass($a_name, $a_description, $a_role_id, $a_priority, $a_copy_class_id) {
		return $this->ilRoomSharingDatabasePrivileges->insertClass($a_name, $a_description, $a_role_id, $a_priority, $a_copy_class_id);
	}


	/**
	 * Edits the values of an already created class
	 *
	 * @param integer $a_class_id    Class-ID which should be edited
	 * @param string  $a_name        New name
	 * @param string  $a_description New description
	 * @param string  $a_role_id     New role-id of the possible assigned role
	 * @param integer $a_priority    New priority
	 */
	public function updateClass($a_class_id, $a_name, $a_description, $a_role_id, $a_priority) {
		$this->ilRoomSharingDatabasePrivileges->updateClass($a_class_id, $a_name, $a_description, $a_role_id, $a_priority);
	}


	/**
	 * Assign a user directly to a class
	 *
	 * @param integer $a_class_id Class-ID
	 * @param integer $a_user_id  User-ID of the user which should be assigned
	 */
	public function assignUserToClass($a_class_id, $a_user_id) {
		$this->ilRoomSharingDatabasePrivileges->assignUserToClass($a_class_id, $a_user_id);
	}


	/**
	 * Gets all users directly assigned to a class
	 *
	 * @param integer $a_class_id Class-ID
	 *
	 * @return array Array with the assigned user-ids
	 */
	public function getUsersForClass($a_class_id) {
		return $this->ilRoomSharingDatabasePrivileges->getUsersForClass($a_class_id);
	}


	/**
	 * Deassign a user from a class
	 *
	 * @param integer $a_class_id Class-ID
	 * @param integer $a_user_id  User-ID which should be deassigned from the class
	 */
	public function deassignUserFromClass($a_class_id, $a_user_id) {
		$this->ilRoomSharingDatabasePrivileges->deassignUserFromClass($a_class_id, $a_user_id);
	}


	/**
	 * Deassign all directly assigned users from a class
	 *
	 * @param integer $a_class_id Class-ID
	 */
	public function clearUsersInClass($a_class_id) {
		$this->ilRoomSharingDatabasePrivileges->clearUsersInClass($a_class_id);
	}


	/**
	 * Delete all privileges of a specific class
	 *
	 * @param integer $a_class_id Class-ID of which the privileges should be deleted
	 */
	public function deleteClassPrivileges($a_class_id) {
		$this->ilRoomSharingDatabasePrivileges->deleteClassPrivileges($a_class_id);
	}


	/**
	 * Deletes a class with all its privileges and assignments
	 *
	 * @param integer $a_class_id Class-ID of the class which should be deleted
	 */
	public function deleteClass($a_class_id) {
		$this->ilRoomSharingDatabasePrivileges->deleteClass($a_class_id);
	}


	/**
	 * Checks if a specific user is in a specific class
	 *
	 * @param integer $a_class_id Class-ID
	 * @param integer $a_user_id  User-ID
	 *
	 * @return boolean true if user is in class, false otherwise
	 */
	public function isUserInClass($a_class_id, $a_user_id) {
		return $this->ilRoomSharingDatabasePrivileges->isUserInClass($a_class_id, $a_user_id);
	}


	/**
	 * Gets a priority of a specific user
	 *
	 * @param integer $a_user_id User-ID
	 *
	 * @return integer Priority of the user
	 */
	public function getUserPriority($a_user_id) {
		return $this->ilRoomSharingDatabasePrivileges->getUserPriority($a_user_id);
	}


	/**
	 * Returns the maximum amount of seats of all available rooms in the current pool, so that the
	 * the user can be notified about it in the filter options.
	 *
	 * @return integer $value maximum seats
	 */
	public function getMaxSeatCount() {
		return $this->ilRoomSharingDatabaseRoom->getMaxSeatCount();
	}


	/**
	 * Gets all the room ids for rooms mathcing the attribute given
	 *
	 * @param $a_attribute
	 * @param $a_count
	 *
	 * @return array the matching room ids
	 */
	public function getRoomIdsWithMatchingAttribute($a_attribute, $a_count) {
		return $this->ilRoomSharingDatabaseRoom->getRoomIdsWithMatchingAttribute($a_attribute, $a_count);
	}


	/**
	 * Returns all room ids
	 *
	 * @return array the room ids
	 */
	public function getAllRoomIds() {
		return $this->ilRoomSharingDatabaseRoom->getAllRoomIds();
	}


	/**
	 * Returns all rooms assigned to the roomsharing pool.
	 *
	 * @return assoc array with all found rooms
	 */
	public function getAllRooms() {
		return $this->ilRoomSharingDatabaseRoom->getAllRooms();
	}


	/**
	 * Returns all room names of rooms which are assigned to the roomsharing pool.
	 *
	 * @return assoc array with all found room names
	 */
	public function getAllRoomNames() {
		return $this->ilRoomSharingDatabaseRoom->getAllRoomNames();
	}


	/**
	 * Gets all rooms matching the room name and seats
	 *
	 * @param $a_roomsToCheck
	 * @param $a_room_name
	 * @param $a_room_seats
	 *
	 * @return array the matching rooms
	 */
	public function getMatchingRooms($a_roomsToCheck, $a_room_name, $a_room_seats) {
		return $this->ilRoomSharingDatabaseRoom->getMatchingRooms($a_roomsToCheck, $a_room_name, $a_room_seats);
	}


	/**
	 * Get's the room name by a given room id
	 *
	 * @param integer $a_room_id
	 *            Room id of the room which name is unknown
	 *
	 * @return Room Name
	 */
	public function getRoomName($a_room_id) {
		return $this->ilRoomSharingDatabaseRoom->getRoomName($a_room_id);
	}


	/**
	 * Gets the room with a given name
	 *
	 * @param type $a_room_name
	 *
	 * @return array room
	 */
	public function getRoomWithName($a_room_name) {
		return $this->ilRoomSharingDatabaseRoom->getRoomWithName($a_room_name);
	}


	/**
	 * Get the room-ids from all rooms that are booked in the given timerange.
	 * A specific room_id can be given if a single room should be queried (used for bookings).
	 *
	 * @param string $a_date_from
	 * @param string $a_date_to
	 * @param string $a_room_id
	 *            (optional)
	 *
	 * @return array values = room ids booked in given range
	 */
	public function getRoomsBookedInDateTimeRange($a_datetimes_from, $a_datetimes_to, $a_room_id = NULL, $a_priority = NULL) {
		return $this->ilRoomSharingDatabaseRoom->getRoomsBookedInDateTimeRange($a_datetimes_from, $a_datetimes_to, $a_room_id, $a_priority);
	}


	/**
	 * Insert a booking into the database.
	 * Gets all room ids where enough seats are available.
	 *
	 * @param integer $a_min_seats number of seats needed
	 *
	 * @return array of integer room ids
	 */
	public function getAllRoomIdsWhereSeatsAvailable($a_min_seats) {
		return $this->ilRoomSharingDatabaseRoom->getAllRoomIdsWhereSeatsAvailable($a_min_seats);
	}


	/**
	 * Returns all rooms matching the floorplan id
	 *
	 * @param type $a_file_id
	 *
	 * @return array the rooms
	 */
	public function getRoomsWithFloorplan($a_file_id) {
		return $this->ilRoomSharingDatabaseRoom->getRoomsWithFloorplan($a_file_id);
		//return $this->ilDB->fetchAssoc($set);
	}


	/**
	 * Gets room information by id.
	 *
	 * @param integer $a_room_id the id for the room whose information should be returne
	 *
	 * @return array room information consisting of name, type, min allocation, ...
	 */
	public function getRoom($a_room_id) {
		return $this->ilRoomSharingDatabaseRoom->getRoom($a_room_id);
	}


	/**
	 * Inserts room information into the database.
	 *
	 * @param string  $a_name
	 * @param string  $a_type
	 * @param integer $a_min_alloc
	 * @param integer $a_max_alloc
	 * @param integer $a_file_id
	 * @param integer $a_building_id
	 *
	 * @return integer the id of the room for which the information has been inserted
	 */
	public function insertRoom($a_name, $a_type, $a_min_alloc, $a_max_alloc, $a_file_id, $a_building_id) {
		return $this->ilRoomSharingDatabaseRoom->insertRoom($a_name, $a_type, $a_min_alloc, $a_max_alloc, $a_file_id, $a_building_id);
	}


	/**
	 * Updates room properties in the database.
	 *
	 * @param integer $a_id
	 * @param text    $a_name
	 * @param text    $a_type
	 * @param integer $a_min_alloc
	 * @param integer $a_max_alloc
	 * @param integer $a_file_id
	 * @param integer $a_building_id
	 *
	 * @return integer number of affected rows
	 */
	public function updateRoomProperties($a_id, $a_name, $a_type, $a_min_alloc, $a_max_alloc, $a_file_id, $a_building_id) {
		return $this->ilRoomSharingDatabaseRoom->updateRoomProperties($a_id, $a_name, $a_type, $a_min_alloc, $a_max_alloc, $a_file_id, $a_building_id);
	}


	/**
	 * Deletes a room with given room id.
	 *
	 * @param integer $a_room_id the id of the room that should be deleted
	 *
	 * @return integer affected rows
	 */
	public function deleteRoom($a_room_id) {
		return $this->ilRoomSharingDatabaseRoom->deleteRoom($a_room_id);
	}


	/**
	 * Gets all attributes referenced by the rooms given by the ids.
	 *
	 * @param array $a_room_ids
	 *            ids of the rooms
	 *
	 * @return array room_id, att.name, count
	 */
	public function getAttributesForRooms($a_room_ids) {
		return $this->ilRoomSharingDatabaseRoomAttribute->getAttributesForRooms($a_room_ids);
	}


	/**
	 * Returns all room attribute names
	 *
	 * @return array the attribute names
	 */
	public function getAllAttributeNames() {
		return $this->ilRoomSharingDatabaseRoomAttribute->getAllAttributeNames();
	}


	/**
	 * Determines the maximum amount of a given room attribute and returns it.
	 *
	 * @param type $a_room_attribute
	 *            the attribute for which the max count
	 *            should be determined
	 *
	 * @return type the max value of the attribute
	 */
	public function getMaxCountForAttribute($a_room_attribute) {
		return $this->ilRoomSharingDatabaseRoomAttribute->getMaxCountForAttribute($a_room_attribute);
	}


	/**
	 * Get a room attribute.
	 *
	 * @param integer $a_attribute_id
	 *
	 * @return type return of $this->ilDB->query
	 */
	public function getRoomAttribute($a_attribute_id) {
		return $this->ilRoomSharingDatabaseRoomAttribute->getRoomAttribute($a_attribute_id);
	}


	/**
	 * Gets all attributes that are assigned to a room.
	 *
	 * @param integer $a_room_id the id of the room for which the attributes should be returned
	 *
	 * @return array an array containing information about the assigned room attributes
	 */
	public function getAttributesForRoom($a_room_id) {
		return $this->ilRoomSharingDatabaseRoomAttribute->getAttributesForRoom($a_room_id);
	}


	/**
	 * Deletes all attributes for a room.
	 *
	 * @param type $a_room_id the id of the room whose attributes should be deleted
	 *
	 * @return int the number of affected rows
	 */
	public function deleteAllAttributesForRoom($a_room_id) {
		return $this->ilRoomSharingDatabaseRoomAttribute->deleteAllAttributesForRoom($a_room_id);
	}


	/**
	 * Inserts an attribute with its amount to a room specified room.
	 *
	 * @param integer $a_room_id      the id room for which the attribute should be inserted
	 * @param integer $a_attribute_id the id of the attribute to be inserted
	 * @param integer $a_amount       the amount specified for the attribute
	 */
	public function insertAttributeForRoom($a_room_id, $a_attribute_id, $a_amount) {
		$this->ilRoomSharingDatabaseRoomAttribute->insertAttributeForRoom($a_room_id, $a_attribute_id, $a_amount);
	}


	/**
	 * Gets all available attributes for rooms.
	 *
	 * @return array associative array for all available attributes with id, name
	 */
	public function getAllRoomAttributes() {
		return $this->ilRoomSharingDatabaseRoomAttribute->getAllRoomAttributes();
	}


	/**
	 * Deletes an room attribute with given id.
	 *
	 * @param integer $a_attribute_id
	 *
	 * @return integer Affected rows
	 */
	public function deleteRoomAttribute($a_attribute_id) {
		return $this->ilRoomSharingDatabaseRoomAttribute->deleteRoomAttribute($a_attribute_id);
	}


	/**
	 * Inserts new room attribute.
	 *
	 * @param string $a_attribute_name
	 *
	 * @return integer id of the inserted attribute
	 */
	public function insertRoomAttribute($a_attribute_name) {
		return $this->ilRoomSharingDatabaseRoomAttribute->insertRoomAttribute($a_attribute_name);
	}


	/**
	 * Deletes all assignments of an attribute to the rooms.
	 *
	 * @param integer $a_attribute_id
	 *
	 * @return integer Affected rows
	 */
	public function deleteAttributeRoomAssign($a_attribute_id) {
		return $this->ilRoomSharingDatabaseRoomAttribute->deleteAttributeRoomAssign($a_attribute_id);
	}


	/**
	 * Renames an room attribute with given id.
	 *
	 * @param integer $a_attribute_id
	 * @param string  $a_changed_attribute_name
	 */
	public function renameRoomAttribute($a_attribute_id, $a_changed_attribute_name) {
		$this->ilRoomSharingDatabaseRoomAttribute->renameRoomAttribute($a_attribute_id, $a_changed_attribute_name);
	}


	/**
	 * Method to insert booking attributes assign into the database.
	 *
	 * @param integer $a_insertedId
	 * @param array   $a_booking_attr_values
	 *            Array with the values of the booking-attributes
	 */
	public function insertBookingAttributes($a_insertedId, $a_booking_attr_values) {
		$this->ilRoomSharingDatabaseBookingAttribute->insertBookingAttributes($a_insertedId, $a_booking_attr_values);
	}


	/**
	 * Inserts a booking attribute into the database.
	 *
	 * @param integer $a_insertedId
	 * @param integer $a_booking_attr_key
	 * @param string  $a_booking_attr_value
	 */
	public function insertBookingAttributeAssign($a_insertedId, $a_booking_attr_key, $a_booking_attr_value) {
		$this->ilRoomSharingDatabaseBookingAttribute->insertBookingAttributeAssign($a_insertedId, $a_booking_attr_key, $a_booking_attr_value);
	}


	/**
	 * Gets all attributes of a booking.
	 *
	 * @param integer $a_booking_id
	 *
	 * @return type return of $this->ilDB->query
	 */
	public function getAttributesForBooking($a_booking_id) {
		return $this->ilRoomSharingDatabaseBookingAttribute->getAttributesForBooking($a_booking_id);
	}


	/**
	 * Gets all booking attributes.
	 *
	 * @return type return of $this->ilDB->query
	 */
	public function getAllBookingAttributes() {
		return $this->ilRoomSharingDatabaseBookingAttribute->getAllBookingAttributes();
	}


	/**
	 * Returns all booking attribute names
	 *
	 * @return array the attribute names
	 */
	public function getAllBookingAttributeNames() {
		return $this->ilRoomSharingDatabaseBookingAttribute->getAllBookingAttributeNames();
	}


	/**
	 * Returns the values for the booking attribute
	 *
	 * @param type $a_booking_id
	 *
	 * @return array attribute values
	 */
	public function getBookingAttributeValues($a_booking_id) {
		return $this->ilRoomSharingDatabaseBookingAttribute->getBookingAttributeValues($a_booking_id);
	}


	/**
	 * Update a booking attribute assign in the database.
	 *
	 * @param type $a_booking_id
	 * @param type $a_booking_attr_key
	 * @param type $a_booking_attr_value
	 */
	public function updateBookingAttributeAssign($a_booking_id, $a_booking_attr_key, $a_booking_attr_value) {
		$this->ilRoomSharingDatabaseBookingAttribute->updateBookingAttributeAssign($a_booking_id, $a_booking_attr_key, $a_booking_attr_value);
	}


	/**
	 * Method to update the booking attributes assign in the database.
	 *
	 * @param type $a_booking_id
	 * @param type $a_booking_attr_values
	 *                Array with the values of the booking-attributes
	 */
	public function updateBookingAttributes($a_booking_id, $a_booking_attr_values, $a_old_booking_attr_values) {
		$this->ilRoomSharingDatabaseBookingAttribute->updateBookingAttributes($a_booking_id, $a_booking_attr_values, $a_old_booking_attr_values);
	}


	/**
	 * Delete a booking attribute assign in the database, by updating the value with a 0.
	 *
	 * @param type $a_booking_id
	 * @param type $a_booking_attr_key
	 */
	public function updateDelBookingAttributeAssign($a_booking_id, $a_booking_attr_key) {
		$this->ilRoomSharingDatabaseBookingAttribute->updateDelBookingAttributeAssign($a_booking_id, $a_booking_attr_key);
	}


	/**
	 * Deletes all assignments of an attribute to the bookings.
	 *
	 * @param integer $a_attribute_id
	 *
	 * @return integer Affected rows
	 */
	public function deleteAttributeBookingAssign($a_attribute_id) {
		return $this->ilRoomSharingDatabaseBookingAttribute->deleteAttributeBookingAssign($a_attribute_id);
	}


	/**
	 * Deletes an booking attribute with given id.
	 *
	 * @param integer $a_attribute_id
	 *
	 * @return integer Affected rows
	 */
	public function deleteBookingAttribute($a_attribute_id) {
		return $this->ilRoomSharingDatabaseBookingAttribute->deleteBookingAttribute($a_attribute_id);
	}


	/**
	 * Inserts new booking attribute.
	 *
	 * @param string $a_attribute_name
	 */
	public function insertBookingAttribute($a_attribute_name) {
		$this->ilRoomSharingDatabaseBookingAttribute->insertBookingAttribute($a_attribute_name);
	}


	/**
	 * Renames an booking attribute with given id.
	 *
	 * @param integer $a_attribute_id
	 * @param string  $a_changed_attribute_name
	 */
	public function renameBookingAttribute($a_attribute_id, $a_changed_attribute_name) {
		$this->ilRoomSharingDatabaseBookingAttribute->renameBookingAttribute($a_attribute_id, $a_changed_attribute_name);
	}


	/**
	 * Gets all floorplans.
	 *
	 * @return type return of $this->ilDB->query
	 */
	public function getAllFloorplans() {
		return $this->ilRoomSharingDatabaseFloorplan->getAllFloorplans();
	}


	/**
	 * Gets a floorplan.
	 *
	 * @param integer $a_file_id
	 *
	 * @return type return of $this->ilDB->query
	 */
	public function getFloorplan($a_file_id) {
		return $this->ilRoomSharingDatabaseFloorplan->getFloorplan($a_file_id);
	}


	/**
	 * Gets a floorplans ids.
	 *
	 * @return type return of $this->ilDB->query
	 */
	public function getAllFloorplanIds() {
		return $this->ilRoomSharingDatabaseFloorplan->getAllFloorplanIds();
	}


	/**
	 * Inserts a floorplan into the database.
	 *
	 * @param integer $a_file_id
	 *
	 * @return type return of $this->ilDB->manipulate
	 */
	public function insertFloorplan($a_file_id) {
		return $this->ilRoomSharingDatabaseFloorplan->insertFloorplan($a_file_id);
	}


	/**
	 * Deletes a floorplan from the database.
	 *
	 * @param integer $a_file_id
	 *
	 * @return type return of $this->ilDB->manipulate
	 */
	public function deleteFloorplan($a_file_id) {
		return $this->ilRoomSharingDatabaseFloorplan->deleteFloorplan($a_file_id);
	}


	/**
	 * Delete floorplan - room association if floorplan will be deleted.
	 *
	 * @param integer floorplan_id
	 *
	 * @return integer amount of affected rows
	 */
	public function deleteFloorplanRoomAssociation($a_file_id) {
		return $this->ilRoomSharingDatabaseFloorplan->deleteFloorplanRoomAssociation($a_file_id);
	}


	/**
	 * Method to insert booking participants into the database.
	 *
	 * @global ilUser $ilUser
	 *
	 * @param integer $a_insertedId
	 * @param array   $a_booking_participants
	 *            Array with the values of the booking-participants
	 */
	public function insertBookingParticipants($a_insertedId, $a_booking_participants) {
		$this->ilRoomSharingDatabaseParticipants->insertBookingParticipants($a_insertedId, $a_booking_participants);
	}


	/**
	 * Inserts a booking participant into the database.
	 *
	 * @param integer $a_insertedId
	 * @param integer $a_participantId
	 */
	public function insertBookingParticipant($a_insertedId, $a_participantId) {
		$this->ilRoomSharingDatabaseParticipants->insertBookingParticipant($a_insertedId, $a_participantId);
	}


	/**
	 * Update a booking participant in the database.
	 *
	 * @param integer $a_insertedId
	 * @param integer $a_participantId
	 */
	public function updateBookingParticipant($a_insertedId, $a_participantId) {
		$this->ilRoomSharingDatabaseParticipants->updateBookingParticipant($a_insertedId, $a_participantId);
	}


	/**
	 * Gets all Participants of a booking.
	 *
	 * @param integer $a_booking_id
	 *
	 * @return type return of $this->ilDB->query
	 */
	public function getParticipantsForBooking($a_booking_id) {
		return $this->ilRoomSharingDatabaseParticipants->getParticipantsForBooking($a_booking_id);
	}


	/*
	 * Gets only the usernames of the participants of a booking.
	 *
	 * @param integer $a_booking_id
	 * @return type return of $this->ilDB->query
	 */
	public function getParticipantsForBookingShort($a_booking_id) {
		return $this->ilRoomSharingDatabaseParticipants->getParticipantsForBookingShort($a_booking_id);
	}


	/**
	 * Deletes a participation from the database.
	 *
	 * @param integer $a_user_id
	 * @param integer $a_booking_id
	 *
	 * @return type return of $this->ilDB->manipulate
	 */
	public function deleteParticipation($a_user_id, $a_booking_id) {
		return $this->ilRoomSharingDatabaseParticipants->deleteParticipation($a_user_id, $a_booking_id);
	}


	/**
	 * Deletes a participation from the database.
	 *
	 * @param integer $a_user_id
	 * @param array   $a_booking_ids
	 */
	public function deleteParticipations($a_user_id, $a_booking_ids) {
		$this->ilRoomSharingDatabaseParticipants->deleteParticipations($a_user_id, $a_booking_ids);
	}


	/**
	 * Gets participation for a user.
	 *
	 * @param integer $a_user_id
	 *
	 * @return type return of $this->ilDB->query
	 */
	public function getParticipationsForUser($a_user_id) {
		return $this->ilRoomSharingDatabaseParticipants->getParticipationsForUser($a_user_id);
	}


	/**
	 * Method to update the booking participants in the database.
	 *
	 * @global type   $ilUser
	 *
	 * @param integer $a_booking_id
	 * @param array   $a_booking_participants
	 *                Array with the values of the booking-participants
	 * @param array   $a_old_booking_participants
	 *                Array with the old values of the booking-participants
	 */
	public function updateBookingParticipants($a_booking_id, $a_booking_participants, $a_old_booking_participants) {
		$this->ilRoomSharingDatabaseParticipants->updateBookingParticipants($a_booking_id, $a_booking_participants, $a_old_booking_participants);
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

?>
