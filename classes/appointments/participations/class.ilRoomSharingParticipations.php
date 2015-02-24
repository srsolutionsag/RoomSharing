<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingNumericUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingDateUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingBookingUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingMailer.php");

/**
 * Class ilRoomSharingParticipations
 *
 * @author Alexander Keller <a.k3ll3r@gmail.com>
 * @author Robert Heimsoth <rheimsoth@stud.hs-bremen.de>
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 *
 * @property ilUser $ilUser
 */
class ilRoomSharingParticipations
{
	private $pool_id;
	private $ilRoomsharingDatabase;
	private $ilUser;
	private $lng;

	/**
	 * Construct of ilRoomSharingParticipations.
	 *
	 * @param integer $a_pool_id
	 */
	function __construct($a_pool_id)
	{
		global $ilUser, $lng;
		$this->lng = $lng;
		$this->ilUser = $ilUser;
		$this->pool_id = $a_pool_id;
		$this->ilRoomsharingDatabase = new ilRoomsharingDatabase($this->pool_id);
	}

	/**
	 * Remove a participations.
	 *
	 * @param integer $booking_id The booking id of the participation.
	 */
	public function removeParticipations(array $a_booking_ids)
	{
		foreach ($a_booking_ids as $a_booking_id)
		{
			if (!ilRoomSharingNumericUtils::isPositiveNumber($a_booking_id))
			{
				ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_no_id_submitted"), true);
			}
		}
		//In order to prevent unnessary prepareManip statements, use different function if only one booking shoud be left.
		if (count($a_booking_ids) == 1)
		{
			$this->ilRoomsharingDatabase->deleteParticipation($this->ilUser->getId(), $a_booking_ids[0]);
		}
		else
		{
			$this->ilRoomsharingDatabase->deleteParticipations($this->ilUser->getId(), $a_booking_ids);
		}
		$this->sendQuitMail($a_booking_ids);
		ilUtil::sendSuccess($this->lng->txt('rep_robj_xrs_participations_left'), true);
	}

	/**
	 * Get the participations from the database.
	 *
	 * @global type $ilUser
	 * @return array with the participation details.
	 */
	public function getList()
	{
		$participations = $this->ilRoomsharingDatabase->getParticipationsForUser($this->ilUser->getId());
		$result = array();
		foreach ($participations as $participation)
		{
			$bookingDatas = $this->ilRoomsharingDatabase->getBooking($participation['booking_id']);
			if ($bookingDatas != array())
			{
				$result[] = $this->readBookingData($bookingDatas);
			}
		}
		return $result;
	}

	/**
	 * Reads a booking
	 *
	 * @param array $a_bookingData
	 * @param integer $a_participation_id
	 * @return array Booking-Information
	 */
	private function readBookingData($a_bookingData)
	{
		$one_booking = array();
		$one_booking['recurrence'] = ilRoomSharingNumericUtils::isPositiveNumber($a_bookingData['seq_id']);

		$one_booking['date'] = ilRoomSharingBookingUtils::readBookingDate($a_bookingData);
		$one_booking ['sortdate'] = $a_bookingData['date_from'];
		// Get the name of the booked room
		$one_booking['room'] = $this->ilRoomsharingDatabase->getRoomName($a_bookingData['room_id']);
		$one_booking['room_id'] = $a_bookingData['room_id'];

		$one_booking['subject'] = $a_bookingData['subject'];

		$one_booking['person_responsible'] = $this->readBookingResponsiblePerson($a_bookingData['user_id']);
		$one_booking['person_responsible_id'] = $a_bookingData['user_id'];

		// The booking id
		$one_booking['booking_id'] = $a_bookingData['id'];
		return $one_booking;
	}

	/**
	 * Reads the data of the responsible person
	 *
	 * @param integer $a_user_id
	 * @return string Login-Name or Fullname (if exists)
	 */
	private function readBookingResponsiblePerson($a_user_id)
	{
		$userData = $this->ilRoomsharingDatabase->getUserById($a_user_id);

		// Check whether the user has a firstname and a lastname
		if (!empty($userData['firstname']) && !empty($userData['lastname']))
		{
			$result = $userData['firstname'] .
				' ' . $userData['lastname'];
		} // ...if not, use the username
		else
		{
			$result = $userData['login'];
		}
		return $result;
	}

	/**
	 * Returns all the additional information that can be displayed in the
	 * bookings table.
	 *
	 * @return array (associative) with additional information.
	 */
	public function getAdditionalBookingInfos()
	{
		$attributes = array();
		$attributesRows = $this->ilRoomsharingDatabase->getAllBookingAttributes();

		foreach ($attributesRows as $attributesRow)
		{
			$attributes [$attributesRow ['name']] = array(
				"txt" => $attributesRow ['name'],
				"id" => $attributesRow ['id']
			);
		}
		return $attributes;
	}

	/**
	 * Returns roomsharing pool id.
	 *
	 * @return int pool id
	 */
	function getPoolId()
	{
		return $this->pool_id;
	}

	/**
	 * Sets roomsharing pool id.
	 *
	 * @param integer $a_pool_id current pool id.
	 */
	function setPoolId($a_pool_id)
	{
		$this->pool_id = $a_pool_id;
	}

	/**
	 * Send quit mail
	 *
	 * @param integer $a_booking_ids booking ids
	 */
	private function sendQuitMail($a_booking_ids)
	{
		$mailer = new ilRoomSharingMailer($this->lng, $this->pool_id);
		$mailer->sendParticipationCancelMail($this->ilUser->getId(), $a_booking_ids);
		$mailer->sendParticipationCancelMailForCreator($this->ilUser->getId(), $a_booking_ids);
	}

}
