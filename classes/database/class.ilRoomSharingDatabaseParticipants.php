<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");
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
class ilRoomSharingDatabaseParticipants
{
	private $pool_id;
	private $ilDB;
	private $ilRoomSharingDatabase;

	/**
	 * constructor ilRoomsharingDatabaseParticipants
	 *
	 * @param integer $a_pool_id
	 */
	public function __construct($a_pool_id, $ilRoomSharingDatabase)
	{
		global $ilDB; // Database-Access-Class
		$this->ilDB = $ilDB;
		$this->pool_id = $a_pool_id;
		$this->ilRoomSharingDatabase = $ilRoomSharingDatabase;
	}

	/**
	 * Method to insert booking participants into the database.
	 *
	 * @global ilUser $ilUser
	 * @param integer $a_insertedId
	 * @param array $a_booking_participants
	 *        	Array with the values of the booking-participants
	 */
	public function insertBookingParticipants($a_insertedId, $a_booking_participants)
	{
		global $ilUser;

		$booked_participants = array();

		// Insert the attributes for the booking in the conjunction table
		foreach ($a_booking_participants as $booking_participant_value)
		{
			// Only insert the attribute value, if a value was submitted by the user
			if ($booking_participant_value !== "" && $booking_participant_value != $ilUser->getLogin())
			{
				//Check if the user is already a participant of this booking
				//(avoids duplicate participations for one user in one booking)
				if (in_array($booking_participant_value, $booked_participants))
				{
					continue;
				}

				$booked_participants[] = $booking_participant_value;

				//Get the id of the participant (user) by the given username
				$booking_participant_id = $this->ilRoomSharingDatabase->getUserIdByUsername($booking_participant_value);

				//Check if the id has a correct format
				if (ilRoomSharingNumericUtils::isPositiveNumber($booking_participant_id))
				{
					$this->insertBookingParticipant($a_insertedId, $booking_participant_id);
				}
			}
		}
	}

	/**
	 * Inserts a booking participant into the database.
	 *
	 * @param integer $a_insertedId
	 * @param integer $a_participantId
	 */
	public function insertBookingParticipant($a_insertedId, $a_participantId)
	{
		$this->ilDB->insert(dbc::BOOKING_TO_USER_TABLE,
			array(
			'booking_id' => array('integer', $a_insertedId),
			'user_id' => array('integer', $a_participantId)
			)
		);
	}

	/**
	 * Update a booking participant in the database.
	 *
	 * @param integer $a_insertedId
	 * @param integer $a_participantId
	 */
	public function updateBookingParticipant($a_insertedId, $a_participantId)
	{
		$this->ilDB->update(dbc::BOOKING_TO_USER_TABLE,
			array(
			'user_id' => array('integer', $a_participantId)
			), array(
			'booking_id' => array('integer', $a_insertedId),
			)
		);
	}

	/**
	 * Gets all Participants of a booking.
	 *
	 * @param integer $a_booking_id
	 * @return type return of $this->ilDB->query
	 */
	public function getParticipantsForBooking($a_booking_id)
	{
		$set = $this->ilDB->query('SELECT users.firstname AS firstname,' .
			' users.lastname AS lastname, users.login AS login,' .
			' users.usr_id AS id FROM ' . dbc::BOOK_USER_TABLE . ' user ' .
			' LEFT JOIN usr_data AS users ON users.usr_id = user.user_id' .
			' WHERE booking_id = ' . $this->ilDB->quote($a_booking_id, 'integer') .
			' ORDER BY users.lastname, users.firstname ASC');
		$participants = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$participants[] = $row;
		}
		return $participants;
	}

	/*
	 * Gets only the usernames of the participants of a booking.
	 *
	 * @param integer $a_booking_id
	 * @return type return of $this->ilDB->query
	 */
	public function getParticipantsForBookingShort($a_booking_id)
	{
		$set = $this->ilDB->query('SELECT  users.login AS login' .
			' FROM ' . dbc::BOOK_USER_TABLE . ' participants ' .
			' INNER JOIN usr_data AS users ON users.usr_id = participants.user_id' .
			' WHERE booking_id = ' . $this->ilDB->quote($a_booking_id, 'integer') .
			' ORDER BY users.lastname, users.firstname ASC');
		$participants = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$participants[] = $row['login'];
		}
		return $participants;
	}

	/**
	 * Deletes a participation from the database.
	 *
	 * @param integer $a_user_id
	 * @param integer $a_booking_id
	 * @return type return of $this->ilDB->manipulate
	 */
	public function deleteParticipation($a_user_id, $a_booking_id)
	{
		return $this->ilDB->manipulate(
				'DELETE FROM ' . dbc::BOOK_USER_TABLE . ' WHERE user_id = ' .
				$this->ilDB->quote($a_user_id, 'integer') .
				' AND booking_id = ' . $this->ilDB->quote($a_booking_id, 'integer'));
	}

	/**
	 * Deletes a participation from the database.
	 *
	 * @param integer $a_user_id
	 * @param array $a_booking_ids
	 */
	public function deleteParticipations($a_user_id, $a_booking_ids)
	{
		$st = $this->ilDB->prepareManip('DELETE FROM ' . dbc::BOOK_USER_TABLE .
			' WHERE user_id = ' . $this->ilDB->quote($a_user_id, 'integer') .
			' AND ' . $this->ilDB->in("booking_id", $a_booking_ids));
		$this->ilDB->execute($st, $a_booking_ids);
	}

	/**
	 * Gets participation for a user.
	 *
	 * @param integer $a_user_id
	 * @return type return of $this->ilDB->query
	 */
	public function getParticipationsForUser($a_user_id)
	{
		$set = $this->ilDB->query(
			'SELECT booking_id FROM ' . dbc::BOOK_USER_TABLE .
			' WHERE user_id = ' . $this->ilDB->quote($a_user_id, 'integer'));

		$participations = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$participations[] = $row;
		}
		return $participations;
	}

	/**
	 * Method to update the booking participants in the database.
	 *
	 * @global type $ilUser
	 * @param integer $a_booking_id
	 * @param array $a_booking_participants
	 * 				Array with the values of the booking-participants
	 * @param array $a_old_booking_participants
	 * 				Array with the old values of the booking-participants
	 */
	public function updateBookingParticipants($a_booking_id, $a_booking_participants,
		$a_old_booking_participants)
	{
		global $ilUser;

		$booked_participants = array();
		foreach ($a_booking_participants as $booking_participant_value)
		{
			// Only insert the attribute value, if a value was submitted by the user
			if ($booking_participant_value != $ilUser->getLogin())
			{
				//(avoids duplicate participations for one user in one booking)
				if (in_array($booking_participant_value, $booked_participants))
				{
					continue;
				}
				$booked_participants[] = $booking_participant_value;
				//Check if the user is still in the booking
				if (in_array($booking_participant_value, $a_old_booking_participants))
				{
					continue;
				}
				//Get the id of the participant (user) by the given username
				$booking_participant_id = $this->ilRoomSharingDatabase->getUserIdByUsername($booking_participant_value);

				//Check if the id has a correct format
				if (ilRoomSharingNumericUtils::isPositiveNumber($booking_participant_id))
				{
					$this->insertBookingParticipant($a_booking_id, $booking_participant_id);
				}
			}
		}

		//Check if the user are no longer in the booking and
		//delete the participations from the booking.
		foreach ($a_old_booking_participants as $booking_participant_value)
		{
			if (in_array($booking_participant_value, $a_old_booking_participants) &&
				!in_array($booking_participant_value, $booked_participants))
			{
				//Get the id of the participant (user) by the given username
				$booking_participant_id = $this->ilRoomSharingDatabase->getUserIdByUsername($booking_participant_value);

				if (ilRoomSharingNumericUtils::isPositiveNumber($booking_participant_id))
				{
					$this->deleteParticipation($booking_participant_id, $a_booking_id);
				}
			}
		}
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
