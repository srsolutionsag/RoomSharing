<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/exceptions/class.ilRoomSharingBookException.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingMailer.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/appointments/bookings/class.ilRoomSharingBookings.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingNumericUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingSequenceBookingUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivilegesConstants.php");

use ilRoomSharingSequenceBookingUtils as seqUtils;
use ilRoomSharingPrivilegesConstants as PRIVC;

/**
 * Backend-Class for the booking form.
 *
 * @author Robert Heimsoth <rheimsoth@stud.hs-bremen.de>
 * @author Christopher Marks <deamp_marks@yahoo.de>
 * @author Alexander Keller <a.k3ll3r@gmail.com>
 */
class ilRoomSharingBook {

	private $pool_id;
	private $ilRoomsharingDatabase;
	private $date_from;
	private $date_to;
	private $lng;
	private $room_id;
	private $participants;
	private $permission;
	private $recurrence;
	private $booking_id;
	private $date_from_old;
	private $date_to_old;


	/**
	 * Constructor
	 *
	 * @global type $lng
	 * @global type $ilUser
	 *
	 * @param type  $a_pool_id
	 */
	public function __construct($a_pool_id) {
		global $lng, $ilUser, $rssPermission;

		$this->permission = $rssPermission;
		$this->lng = $lng;
		$this->user = $ilUser;
		$this->pool_id = $a_pool_id;
		$this->permission = $rssPermission;
		$this->ilRoomsharingDatabase = new ilRoomsharingDatabase($this->pool_id);
	}


	/**
	 * Method to add a new booking into the database
	 *
	 * @param type    $a_booking_values       Array with the values of the booking
	 * @param type    $a_booking_attr_values  Array with the values of the booking-attributes
	 * @param type    $a_booking_participants Array with the values of the participants
	 * @param type    $a_recurrence_entries   Array with recurrence information
	 * @param boolean $sendmessage            Send message
	 *
	 * @throws ilRoomSharingBookException
	 * @return array Booking-IDs which are canceled
	 */
	public function addBooking($a_booking_values, $a_booking_attr_values, $a_booking_participants, $a_recurrence_entries, $sendmessage = true) {
		if ($this->permission->checkPrivilege(PRIVC::ADD_OWN_BOOKINGS)) {
			$this->date_from = $a_booking_values ['from'] ['date'] . " " . $a_booking_values ['from'] ['time'];
			$this->date_to = $a_booking_values ['to'] ['date'] . " " . $a_booking_values ['to'] ['time'];
			$this->room_id = $a_booking_values ['room'];
			$this->participants = $a_booking_participants;
			$this->recurrence = $a_recurrence_entries;
			$datetimes = $this->generateDatetimesForBooking();

			$this->validateBookingInput($datetimes['from'], $datetimes['to']);

			$a_booking_values['from'] = $datetimes['from'];
			$a_booking_values['to'] = $datetimes['to'];

			$booking_ids_of_bookings_to_be_canceled = $this->ilRoomsharingDatabase->getBookingIdsForRoomInDateimeRanges($this->room_id, $a_booking_values['from'], $a_booking_values['to']);

			if (ilRoomSharingNumericUtils::isPositiveNumber(count($booking_ids_of_bookings_to_be_canceled))) {
				$bookings = new ilRoomSharingBookings($this->pool_id);
				try {
					$bookings->removeMultipleBookings($booking_ids_of_bookings_to_be_canceled, true);
				} catch (ilRoomSharingBookingsException $ex) {
					throw new ilRoomSharingBookException($ex->getMessage());
				}
			}

			$success = $this->ilRoomsharingDatabase->insertBookingRecurrence($a_booking_attr_values, $a_booking_values, $a_booking_participants);

			if ($success) {
				if ($sendmessage) {
					$this->sendBookingNotification();
				}

				return count($booking_ids_of_bookings_to_be_canceled);
			} else {
				throw new ilRoomSharingBookException($this->lng->txt('rep_robj_xrs_booking_add_error'));
			}
		} else {
			throw new ilRoomSharingBookException($this->lng->txt('rep_robj_xrs_no_permission_for_action'));
		}

		return 0;
	}


	/**
	 * Method to edit a booking and update database entry
	 *
	 * @param type    $a_booking_values
	 * @param type    $a_booking_attr_values
	 * @param type    $a_booking_participants
	 * @param boolean $sendmessage Send message
	 *
	 * @throws ilRoomSharingBookException
	 */
	public function updateEditBooking($a_booking_id, $a_old_booking_values, $a_old_booking_attr_values, $a_old_booking_participants, $a_booking_values, $a_booking_attr_values, $a_booking_participants, $sendmessage = true) {
		if ($this->permission->checkPrivilege(PRIVC::ADD_OWN_BOOKINGS)) {
			$this->date_from = $a_booking_values ['from'] ['date'] . " " . $a_booking_values ['from'] ['time'];
			$this->date_to = $a_booking_values ['to'] ['date'] . " " . $a_booking_values ['to'] ['time'];
			$this->room_id = $a_booking_values ['room'];
			$booking_participants = $this->deleteEmptyUser($a_booking_participants);
			$newFromDate = $a_booking_values['from'] ['date'] . " " . $a_booking_values ['from'] ['time'];
			$newToDate = $a_booking_values['to'] ['date'] . " " . $a_booking_values ['to'] ['time'];
			$oldFromDate = $a_old_booking_values['date_from'];
			$oldToDate = $a_old_booking_values['date_to'];
			$this->participants = $booking_participants;
			$this->booking_id = $a_booking_id;
			$this->date_from_old = $oldFromDate;
			$this->date_to_old = $oldToDate;

			$this->validateEditBookingInput();
			$success = $this->updateBooking($a_booking_id, $a_booking_attr_values, $a_old_booking_attr_values, $a_booking_values, $a_old_booking_values, $booking_participants, $a_old_booking_participants);

			$dateChange = $oldFromDate != $newFromDate || $oldToDate != $newToDate;
			$participantsChange = $a_old_booking_participants != $booking_participants;
			if ($success) {
				$deletedUser = $this->getDeletedUser($booking_participants, $a_old_booking_participants);
				$newUser = $this->getNewUser($booking_participants, $a_old_booking_participants);

				if ($sendmessage) {
					if ($participantsChange && $dateChange) {
						$this->sendBookingUpdatedNotification($booking_participants);
						if ($deletedUser != array()) {
							$this->sendBookingUpdatedNotificationToCanceldUser($deletedUser);
						}
						if ($newUser != array()) {
							$this->sendBookingNotificationToNewUser($newUser);
						}
					} else {
						if ($participantsChange) {
							if ($deletedUser != array()) {
								$this->sendBookingUpdatedNotificationToCanceldUser($deletedUser);
							}
							if ($newUser != array()) {
								$this->sendBookingNotificationToNewUser($newUser);
							}
						} else {
							if ($dateChange) {
								//Send a email notifications to the participants
								$this->sendBookingUpdatedNotification($booking_participants);
							}
						}
					}
				}
			} else {
				throw new ilRoomSharingBookException($this->lng->txt('rep_robj_xrs_booking_add_error'));
			}
		}
	}


	/**
	 * Return all new User from the $a_booking_participants array.
	 *
	 * @param array $a_booking_participants     with the new participants
	 * @param array $a_old_booking_participants with the old participants
	 *
	 * @return array
	 *            If there are no new user, return a empty array if no new user found.
	 */
	private function getNewUser($a_booking_participants, $a_old_booking_participants) {
		$newUser = array();
		foreach ($a_booking_participants as $user) {
			if (!in_array($user, $a_old_booking_participants)) {
				$newUser[] = $user;
			}
		}

		return $newUser;
	}


	/**
	 * Generates datetimes for the (recurrence) booking
	 *
	 * @return array ("from" => array(DATETIMES_FROM...),
	 *                  "to" => array(DATETIMES_TO...))
	 */
	private function generateDatetimesForBooking() {
		$time_from = date('H:i:s', strtotime($this->date_from));
		$time_to = date('H:i:s', strtotime($this->date_to));
		$date_from = date('Y-m-d', strtotime($this->date_from));
		$date_to = date('Y-m-d', strtotime($this->date_to));
		if ($date_from != $date_to) {
			$date1 = strtotime($date_from);
			$date2 = strtotime($date_to);
			$day_difference = ceil(abs($date1 - $date2) / 86400);
		} else {
			$day_difference = NULL;
		}

		$datetimes_from = array();
		$datetimes_to = array();
		switch ($this->recurrence['frequence']) {
			case "DAILY":
				$days = seqUtils::getDailyFilteredData($date_from, $this->recurrence['repeat_type'], $this->recurrence['repeat_amount'], $this->recurrence['repeat_until'], $time_from, $time_to, $day_difference);
				$datetimes_from = $days['from'];
				$datetimes_to = $days['to'];
				break;
			case "WEEKLY":
				if (empty($this->recurrence['weekdays'])) {
					$datetimes_from = array( $this->date_from );
					$datetimes_to = array( $this->date_to );
					throw new ilRoomSharingBookException($this->lng->txt("rep_robj_xrs_error_in_selected_sequencebooking"));
				}

				$days = seqUtils::getWeeklyFilteredData($date_from, $this->recurrence['repeat_type'], $this->recurrence['repeat_amount'], $this->recurrence['repeat_until'], $this->recurrence['weekdays'], $time_from, $time_to, $day_difference);

				$datetimes_from = $days['from'];
				$datetimes_to = $days['to'];
				break;
			case "MONTHLY":
				if (empty($this->recurrence['repeat_type']) || empty($this->recurrence['start_type'])) {
					$datetimes_from = array( $this->date_from );
					$datetimes_to = array( $this->date_to );
					throw new ilRoomSharingBookException($this->lng->txt("rep_robj_xrs_error_in_selected_sequencebooking"));
				}
				$days = seqUtils::getMonthlyFilteredData($date_from, $this->recurrence['repeat_type'], $this->recurrence['repeat_amount'], $this->recurrence['repeat_until'], $this->recurrence['start_type'], $this->recurrence['monthday'], $this->recurrence['weekday_1'], $this->recurrence['weekday_2'], $time_from, $time_to, $day_difference);
				$datetimes_from = $days['from'];
				$datetimes_to = $days['to'];
				break;
			default:
				$datetimes_from[] = $date_from . " " . $time_from;
				$datetimes_to[] = $date_to . " " . $time_to;
				break;
		}

		return array( "from" => $datetimes_from, "to" => $datetimes_to );
	}


	/**
	 * Checks if the given booking input is valid (e.g. valid dates, already booked rooms, ...)
	 *
	 * @throws ilRoomSharingBookException
	 */
	private function validateBookingInput($a_datetimes_from, $a_datetimes_to) {
		if ($this->isBookingInPast()) {
			throw new ilRoomSharingBookException($this->lng->txt("rep_robj_xrs_datefrom_is_earlier_than_now"));
		}
		if ($this->checkForInvalidDateConditions()) {
			throw new ilRoomSharingBookException($this->lng->txt("rep_robj_xrs_datefrom_bigger_dateto"));
		}
		if ($this->isAlreadyBooked($a_datetimes_from, $a_datetimes_to)) {
			throw new ilRoomSharingBookException($this->lng->txt("rep_robj_xrs_room_already_booked"));
		}
		if ($this->isRoomOverbooked()) {
			throw new ilRoomSharingBookException($this->lng->txt("rep_robj_xrs_room_max_allocation_exceeded"));
		}
		if ($this->isRoomUnderbooked()) {
			throw new ilRoomSharingBookException($this->lng->txt("rep_robj_xrs_room_min_allocation_not_reached"));
		}
		if ($this->isBookingReachHigherThenMaxBookTime()) {
			throw new ilRoomSharingBookException($this->lng->txt("rep_robj_xrs_booking_time_bigger_max_book_time"));
		}
	}


	/**
	 * Method to check whether the booking date is in the past
	 */
	private function isBookingInPast() {
		return (strtotime($this->date_from) <= time());
	}


	/**
	 * Method to check whether the date is valid
	 * date_to must be higher or equal than the date_from
	 */
	private function checkForInvalidDateConditions() {
		return ($this->date_from >= $this->date_to);
	}


	/**
	 * Method to check if the selected room is already booked in the given time range
	 *
	 */
	private function isAlreadyBooked($a_datetimes_from, $a_datetimes_to) {
		if ($this->permission->checkPrivilege(ilRoomSharingPrivilegesConstants::CANCEL_BOOKING_LOWER_PRIORITY)) {
			$temp = $this->ilRoomsharingDatabase->getRoomsBookedInDateTimeRange($a_datetimes_from, $a_datetimes_to, $this->room_id, $this->permission->getUserPriority());
		} else {
			$temp = $this->ilRoomsharingDatabase->getRoomsBookedInDateTimeRange($a_datetimes_from, $a_datetimes_to, $this->room_id);
		}

		return ($temp !== array());
	}


	/**
	 * Method to check if the selected room is already booked in the given time range
	 *
	 * Return false if the room is free.
	 */
	private function isAlreadyBookedForEdits() {
		$intDateFrom = $this->stringTimeToInt($this->date_from);
		$intDateFromOld = $this->stringTimeToInt($this->date_from_old);
		$intDateTo = $this->stringTimeToInt($this->date_to);
		$intDateToOld = $this->stringTimeToInt($this->date_to_old);
		$newTimeEqualsOldTime = $this->date_from == $this->date_from_old && $this->date_to == $this->date_to_old;
		$newTimeBetweenOldTime = $intDateFrom > $intDateFromOld && $intDateTo < $intDateToOld;
		$newTimeBeforeOldTime = $intDateFrom < $intDateFromOld;
		$newTimeAfterOldTime = $intDateTo > $intDateToOld;
		$newTimeBeforeAndAfterOldTime = $newTimeBeforeOldTime && $newTimeAfterOldTime;
		if ($newTimeEqualsOldTime || $newTimeBetweenOldTime) {
			return false;
		} else {
			if ($newTimeBeforeOldTime || $newTimeAfterOldTime || $newTimeBeforeAndAfterOldTime) {
				$temp = $this->ilRoomsharingDatabase->getBookingIdForRoomInDateTimeRange($this->date_from, $this->date_to, $this->room_id, $this->booking_id);

				return ($temp !== array());
			}
		}
	}


	/**
	 * Method that checks if the max allocation of a room is exceeded.
	 */
	private function isRoomOverbooked() {
		$room = new ilRoomSharingRoom($this->pool_id, $this->room_id);
		$max_alloc = $room->getMaxAlloc();
		$filtered_participants = array_filter($this->participants, array( $this, "filterValidParticipants" ));
		$overbooked = count($filtered_participants) >= $max_alloc;

		return $overbooked;
	}


	/**
	 * Method that checks if the min allocation of a room is not reached.
	 */
	private function isRoomUnderbooked() {
		$room = new ilRoomSharingRoom($this->pool_id, $this->room_id);
		$min_alloc = $room->getMinAlloc();
		$filtered_participants = array_filter($this->participants, array( $this, "filterValidParticipants" ));
		$underbooked = count($filtered_participants) + 1 < $min_alloc;

		return $underbooked;
	}


	/**
	 * Callback function which is used for existing and therefore valid participants.
	 * Also it filters out the booker itself, if he is in the list of participants.
	 *
	 * @param string $a_participant
	 * return boolean/integer id of the participant if participant exists; false otherwise
	 */
	private function filterValidParticipants($a_participant) {
		return (empty($a_participant) || $this->user->getLogin() === $a_participant) ? false : ilObjUser::_lookupId($a_participant);
	}


	/**
	 * Generates a booking acknowledgement via mail.
	 *
	 * @return array $recipient_ids List of recipients.
	 */
	private function sendBookingNotification() {
		$room_name = $this->ilRoomsharingDatabase->getRoomName($this->room_id);

		$mailer = new ilRoomSharingMailer($this->lng);
		$mailer->setRoomname($room_name);
		$mailer->setDateStart($this->date_from);
		$mailer->setDateEnd($this->date_to);
		$mailer->sendBookingMail($this->user->getId(), $this->participants);
	}


	/**
	 * Returns the room user agreement file id.
	 */
	public function getRoomAgreementFileId() {
		$agreement_file_id = $this->ilRoomsharingDatabase->getRoomAgreementId();

		return $agreement_file_id;
	}


	/**
	 * Get the booking Data of the given ID
	 *
	 * @param type $a_booking_id
	 *
	 * @return Array(
	 *                ['user_id']
	 *                ['seq_id']
	 *                ['booking_values']
	 *                ['attr_values']
	 *                ['participants']
	 *                'participants_org'])
	 */
	public function getBookingData($a_booking_id) {
		$row = $this->ilRoomsharingDatabase->getSequenceAndUserForBooking($a_booking_id);

		$booking = array();
		$booking['user_id'] = $row['user_id'];
		$booking['seq_id'] = $row['seq_id'];
		$booking['booking_values'] = $this->ilRoomsharingDatabase->getBooking($a_booking_id);
		$booking['attr_values'] = $this->ilRoomsharingDatabase->getBookingAttributeValues($a_booking_id);
		$booking['participants'] = $this->ilRoomsharingDatabase->getParticipantsForBookingShort($a_booking_id, 1);
		$booking['participants_org'] = $this->ilRoomsharingDatabase->getParticipantsForBookingShort($a_booking_id);

		return $booking;
	}


	/**
	 * Removing all empty user entry from the $a_booking_participants array.
	 *
	 * @param array $a_booking_participants with the participants
	 *
	 * @return array
	 *            the new array without empty users.
	 */
	private function deleteEmptyUser($a_booking_participants) {
		$booking_booking_participants = array();
		foreach ($a_booking_participants as $user) {
			if ($user != '') {
				$booking_booking_participants[] = $user;
			}
		}

		return $booking_booking_participants;
	}


	/**
	 * Return a array with the all deleted participants.
	 * If no user user is deleted, a empty array will be returned.
	 *
	 * @param array $a_booking_participants     = new participants
	 * @param array $a_old_booking_participants = old participants
	 *
	 * @return array
	 */
	private function getDeletedUser($a_booking_participants, $a_old_booking_participants) {
		$deletedUser = array();
		foreach ($a_old_booking_participants as $user) {
			if (!in_array($user, $a_booking_participants)) {

				$deletedUser[] = $user;
			}
		}

		return $deletedUser;
	}


	/**
	 * Get the max book time from the pool.
	 *
	 * @return integer =  time in s
	 */
	private function getMaxBookTime() {
		//Get the standard timezone of the system, to recover the normal standard
		//after the interpretation of the time to a interger.
		$string = $this->ilRoomsharingDatabase->getMaxBookTime();
		//format the given timestamp to the HH:MM Format
		$time1 = date("H:i", strtotime($string));
		//format the standard start timestamp to the HH:MM Format
		$time2 = date("H:i", strtotime('1970-01-01 00:00'));
		//calculate the difference from given and the standard timestamp
		//and return the resulting integer value.
		return strtotime($time1) - strtotime($time2);
	}


	/**
	 * Method to insert the booking
	 *
	 * @param array $a_booking_attr_values
	 *            Array with the values of the booking-attributes
	 *
	 * @return type -1 failed insert, 1 successful insert
	 */
	private function insertBooking($a_booking_attr_values, $a_booking_values, $a_booking_participants) {
		return $this->ilRoomsharingDatabase->insertBooking($a_booking_attr_values, $a_booking_values, $a_booking_participants);
	}


	/**
	 * Methode to check if the booking reach is smaller or equals the max book time of the pool.
	 *
	 * @return boolean
	 */
	private function isBookingReachHigherThenMaxBookTime() {
		return (strtotime($this->date_to) - strtotime($this->date_from)) > $this->getMaxBookTime();
	}


	/**
	 * Generates a booking acknowledgement via mail to given new Users.
	 *
	 * @param array $a_newUser
	 */
	private function sendBookingNotificationToNewUser($a_newUser) {
		$room_name = $this->ilRoomsharingDatabase->getRoomName($this->room_id);

		$mailer = new ilRoomSharingMailer($this->lng);
		$mailer->setRoomname($room_name);
		$mailer->setDateStart($this->date_from);
		$mailer->setDateEnd($this->date_to);
		$mailer->sendBookingMailToNewUser($a_newUser);
	}


	/**
	 * Generates a update booking acknowledgement via mail to the participants.
	 *
	 * @parm array $a_participants with the user-ids
	 */
	private function sendBookingUpdatedNotification($a_participants) {
		$room_name = $this->ilRoomsharingDatabase->getRoomName($this->room_id);

		$mailer = new ilRoomSharingMailer($this->lng);
		$mailer->setRoomname($room_name);
		$mailer->setDateStart($this->date_from);
		$mailer->setDateEnd($this->date_to);
		$mailer->sendUpdateBookingMailToParticipants($a_participants);
	}


	/**
	 * Generates a booking acknowledgement about a booking cancel for the users via mail.
	 *
	 * @param array $a_deletedUser
	 *            user-id who get the mail
	 */
	private function sendBookingUpdatedNotificationToCanceldUser($a_deletedUser) {
		$room_name = $this->ilRoomsharingDatabase->getRoomName($this->room_id);

		$mailer = new ilRoomSharingMailer($this->lng);
		$mailer->setRoomname($room_name);
		$mailer->setDateStart($this->date_from);
		$mailer->setDateEnd($this->date_to);
		$mailer->sendCancellationMailToParticipants($a_deletedUser);
	}


	/**
	 * Convert the given string time to a integer value.
	 *
	 * @param string $a_date
	 *
	 * @return integer
	 */
	private function stringTimeToInt($a_date) {
		return strtotime($a_date);
	}


	/**
	 * Methode to update a booking in the database.
	 *
	 * @param string $a_booking_id
	 * @param array  $a_booking_attr_values
	 * @param array  $a_old_booking_attr_values
	 * @param array  $a_booking_values
	 * @param array  $a_old_booking_values
	 * @param array  $a_booking_participants
	 * @param array  $a_old_booking_participants
	 *
	 * @return boolean true if the update was successful
	 */
	private function updateBooking($a_booking_id, $a_booking_attr_values, $a_old_booking_attr_values, $a_booking_values, $a_old_booking_values, $a_booking_participants, $a_old_booking_participants) {
		return $this->ilRoomsharingDatabase->updateBooking($a_booking_id, $a_booking_attr_values, $a_old_booking_attr_values, $a_booking_values, $a_old_booking_values, $a_booking_participants, $a_old_booking_participants);
	}


	/**
	 * Checks if the edit booking input is valid (e.g. valid dates, already booked rooms, ...)
	 *
	 * @throws ilRoomSharingBookException
	 */
	private function validateEditBookingInput() {
		if ($this->isBookingInPast()) {
			throw new ilRoomSharingBookException($this->lng->txt("rep_robj_xrs_datefrom_is_earlier_than_now"));
		}
		if ($this->checkForInvalidDateConditions()) {
			throw new ilRoomSharingBookException($this->lng->txt("rep_robj_xrs_datefrom_bigger_dateto"));
		}
		if ($this->isAlreadyBookedForEdits()) {
			throw new ilRoomSharingBookException($this->lng->txt("rep_robj_xrs_room_already_booked"));
		}
		if ($this->isRoomOverbooked()) {
			throw new ilRoomSharingBookException($this->lng->txt("rep_robj_xrs_room_max_allocation_exceeded"));
		}
		if ($this->isRoomUnderbooked()) {
			throw new ilRoomSharingBookException($this->lng->txt("rep_robj_xrs_room_min_allocation_not_reached"));
		}
		if ($this->isBookingReachHigherThenMaxBookTime()) {
			throw new ilRoomSharingBookException($this->lng->txt("rep_robj_xrs_booking_time_bigger_max_book_time"));
		}
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
