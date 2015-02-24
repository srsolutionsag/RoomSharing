<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDBConstants.php");

use ilRoomSharingDBConstants as dbc;

/**
 * Class for database queries.
 *
 * @author Malte Ahlering
 *
 * @property ilDB $ilDB
 */
class ilRoomSharingDatabaseBooking
{
	private $pool_id;
	private $ilDB;
	private $ilRoomSharingDatabase;

	/**
	 * constructor ilRoomsharingDatabaseBooking
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
	 * Insert a bboking into the database.
	 *
	 * @global type $this->ilDB
	 * @global type $ilUser
	 * @param array $a_booking_values
	 *        	Array with the values of the booking
	 * @param array $a_booking_attr_values
	 *        	Array with the values of the booking-attributes
	 * @return integer 1 = successful, -1 not successful
	 */
	public function insertBooking($a_booking_attr_values, $a_booking_values, $a_booking_participants)
	{
		global $ilUser;
		$this->ilDB->insert(dbc::BOOKINGS_TABLE,
			array(
			'id' => array('integer', $this->ilDB->nextID(dbc::BOOKINGS_TABLE)),
			'date_from' => array('timestamp', $a_booking_values ['from'][0]),
			'date_to' => array('timestamp', $a_booking_values ['to'][0]),
			'room_id' => array('integer', $a_booking_values ['room']),
			'pool_id' => array('integer', $this->pool_id),
			'user_id' => array('integer', $ilUser->getId()),
			'subject' => array('text', $a_booking_values ['subject']),
			'public_booking' => array('boolean', $a_booking_values ['book_public'] == '1'),
			'bookingcomment' => array('text', $a_booking_values ['comment'])
			)
		);

		$insertedId = $this->ilDB->getLastInsertId();

		if ($insertedId == - 1)
		{
			return - 1;
		}

		$this->ilRoomSharingDatabase->insertBookingAttributes($insertedId, $a_booking_attr_values);

		$this->ilRoomSharingDatabase->insertBookingParticipants($insertedId, $a_booking_participants);

		$this->ilRoomSharingDatabase->insertBookingAppointment($insertedId, $a_booking_values,
			$a_booking_values ['from'][0], $a_booking_values ['to'][0]);

		return 1;
	}

	/**
	 * This method inserts a booking recurrence by inserting a set of data.
	 *
	 *
	 * @global type $ilDB
	 * @param array $a_booking_values
	 *        	Array with the values of the booking
	 * @param array $a_booking_attr_values
	 *        	Array with the values of the booking-attributes
	 * @return integer 1 = successful, -1 not successful
	 */
	public function insertBookingRecurrence($a_booking_attr_values, $a_booking_values,
		$a_booking_participants)
	{
		global $ilUser;
		$count_booking_values_from = count($a_booking_values['from']);
		//Are there more than one startdays? Then its a sequence booking, so generate a new id
		if ($count_booking_values_from > 1)
		{
			$next_seq_id = $this->ilDB->nextID(dbc::BOOKING_SEQUENCES_TABLE);
		}
		else
		{
			$next_seq_id = NULL;
		}
		$query = "INSERT INTO " . dbc::BOOKINGS_TABLE . " (id, date_from, date_to, seq_id, room_id, pool_id, user_id, subject, public_booking, bookingcomment) VALUES ";
		// create SQL query
		$newBookIds = array();

		for ($i = 0; $i < $count_booking_values_from; $i++)
		{
			$book_id = $this->ilDB->nextID(dbc::BOOKINGS_TABLE);
			$newBookIds[] = $book_id;
			$query .= "(" .
				$this->ilDB->quote($book_id, 'integer') . ", " .
				$this->ilDB->quote($a_booking_values['from'][$i], 'timestamp') . ", " .
				$this->ilDB->quote($a_booking_values['to'][$i], 'timestamp') . ", " .
				$this->ilDB->quote($next_seq_id, 'integer') . ", " .
				$this->ilDB->quote($a_booking_values['room'], 'integer') . ", " .
				$this->ilDB->quote($this->pool_id, 'integer') . ", " .
				$this->ilDB->quote($ilUser->getId(), 'integer') . ", " .
				$this->ilDB->quote($a_booking_values['subject'], 'text') . ", " .
				$this->ilDB->quote($a_booking_values['book_public'] == '1', 'boolean') . ", " .
				$this->ilDB->quote($a_booking_values['comment'], 'text') .
				"), ";
		}
		$q = substr($query, 0, -2); // delete last comma and blank
		$this->ilDB->manipulate($q); // SQL
		$insertedId = $this->ilDB->getLastInsertId();
		if ($insertedId == - 1)
		{
			return - 1;
		}
		/**
		 *  add bookingAttributes, bookingParticipants, bookingAppointments
		 *  for each booking entry
		 */
		for ($i = 0; $i < $count_booking_values_from; $i++)
		{
			$this->ilRoomSharingDatabase->insertBookingAttributes($newBookIds[$i], $a_booking_attr_values);
			$this->ilRoomSharingDatabase->insertBookingParticipants($newBookIds[$i], $a_booking_participants);
			$this->ilRoomSharingDatabase->insertBookingAppointment($newBookIds[$i], $a_booking_values,
				$a_booking_values ['from'][$i], $a_booking_values ['to'][$i]);
		}
		return 1;
	}

	/**
	 * Delete a booking from the database.
	 *
	 * @param integer $a_booking_id
	 */
	public function deleteBooking($a_booking_id)
	{
		$this->ilDB->manipulate('DELETE FROM ' . dbc::BOOKINGS_TABLE .
			' WHERE id = ' . $this->ilDB->quote($a_booking_id, 'integer') .
			' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer'));
		$this->ilDB->manipulate('DELETE FROM ' . dbc::BOOK_USER_TABLE .
			' WHERE booking_id = ' . $this->ilDB->quote($a_booking_id, 'integer'));
	}

	/**
	 * Delete bookings from the database.
	 *
	 * @param array $booking_ids
	 */
	public function deleteBookings($booking_ids)
	{
		$st = $this->ilDB->prepareManip('DELETE FROM ' . dbc::BOOKINGS_TABLE .
			' WHERE ' . $this->ilDB->in("id", $booking_ids) .
			' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer'));
		$this->ilDB->execute($st, $booking_ids);
		$st2 = $this->ilDB->prepareManip('DELETE FROM ' . dbc::BOOK_USER_TABLE .
			' WHERE ' . $this->ilDB->in("booking_id", $booking_ids));
		$this->ilDB->execute($st2, $booking_ids);
	}

	/**
	 * Get all bookings related to a given sequence.
	 *
	 * @param integer $a_seq_id
	 * @return type return of $this->ilDB->query
	 */
	public function getAllBookingIdsForSequence($a_seq_id)
	{
		$set = $this->ilDB->query('SELECT id FROM ' . dbc::BOOKINGS_TABLE .
			' WHERE seq = ' . $this->ilDB->quote($a_seq_id, 'integer') .
			' AND pool_id = ' . $this->ilDB->quote($this->pool_id, 'integer'));

		$booking_ids = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$booking_ids[] = $row;
		}
		return $booking_ids;
	}

	/**
	 * Gets User and Sequence for a booking.
	 *
	 * @param integer $a_booking_id
	 * @return type return of $this->ilDB->query
	 */
	public function getSequenceAndUserForBooking($a_booking_id)
	{
		$set = $this->ilDB->query('SELECT seq_id, user_id  FROM ' . dbc::BOOKINGS_TABLE .
			' WHERE id = ' . $this->ilDB->quote($a_booking_id, 'integer') .
			' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer'));
		if ($this->ilDB->numRows($set) > 0)
		{
			$result = $this->ilDB->fetchAssoc($set);
		}
		else
		{
			$result = NULL;
		}
		return $result;
	}

	/**
	 * Gets all bookings for a user.
	 *
	 * @param integer $a_user_id
	 * @return type return of $this->ilDB->query
	 */
	public function getBookingsForUser($a_user_id)
	{
		$set = $this->ilDB->query('SELECT * FROM ' . dbc::BOOKINGS_TABLE .
			' WHERE pool_id = ' . $this->ilDB->quote($this->pool_id, 'integer') .
			' AND user_id = ' . $this->ilDB->quote($a_user_id, 'integer') .
			' AND (date_from >= "' . date('Y-m-d H:i:s') . '"' .
			' OR date_to >= "' . date('Y-m-d H:i:s') . '")' . ' ORDER BY date_from ASC');
		$bookings = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$bookings[] = $row;
		}
		return $bookings;
	}

	/**
	 * Gets all bookings filtered by given criteria.
	 *
	 * @param array $filter filter criteria
	 * @return array
	 */
	public function getFilteredBookings(array $filter)
	{
		$query = 'SELECT b.id, b.user_id, b.subject, b.bookingcomment,' .
			' r.id AS room_id, b.date_from, b.date_to, b.seq_id FROM ' . dbc::BOOKINGS_TABLE . ' b ' .
			' JOIN ' . dbc::ROOMS_TABLE . ' r ON b.room_id = r.id ' .
			' WHERE (date_from >= ' . $this->ilDB->quote(date('Y-m-d H:i:s'), 'timestamp') .
			' OR date_to >= ' . $this->ilDB->quote(date('Y-m-d H:i:s'), 'timestamp') . ')'
			. ' AND b.pool_id = ' . $this->ilDB->quote($this->pool_id, 'integer');

		if ($filter['user_id'] || $filter['user_id'])
		{
			$user_id = $this->ilRoomSharingDatabase->getUserIdByUsername($filter['user_id']);
			if ($user_id == NULL)
			{
				$user_id = $filter['user_id'];
			}

			$query .= ' AND b.user_id = ' . $this->ilDB->quote($user_id, 'integer') . ' ';
		}

		if ($filter['room_name'] || $filter['room_name'])
		{
			$query .= ' AND r.name LIKE ' .
				$this->ilDB->quote('%' . $filter['room_name'] . '%', 'text') . ' ';
		}

		if ($filter['subject'] || $filter['subject'])
		{
			$query .= ' AND b.subject LIKE ' .
				$this->ilDB->quote('%' . $filter['subject'] . '%', 'text') . ' ';
		}

		if ($filter['comment'] || $filter['comment'])
		{
			$query .= ' AND b.bookingcomment LIKE ' .
				$this->ilDB->quote('%' . $filter['comment'] . '%', 'text') . ' ';
		}

		if ($filter['attributes'])
		{
			foreach ($filter['attributes'] as $attribute => $value)
			{
				$query .= ' AND EXISTS (SELECT * FROM ' . dbc::BOOKING_TO_ATTRIBUTE_TABLE . ' ba ' .
					' LEFT JOIN ' . dbc::BOOKING_ATTRIBUTES_TABLE . ' a ON a.id = ba.attr_id ' .
					' WHERE booking_id = b.id AND name = ' .
					$this->ilDB->quote($attribute, 'text') . ' AND value LIKE ' .
					$this->ilDB->quote('%' . $value . '%', 'text') . ' ) ';
			}
		}

		$set = $this->ilDB->query($query);
		$bookings = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$bookings[] = $row;
		}
		return $bookings;
	}

	/**
	 * Gets a booking.
	 *
	 * @param integer $a_booking_id
	 * @return type return of $this->ilDB->query
	 */
	public function getBooking($a_booking_id)
	{
		$set = $this->ilDB->query('SELECT *' . ' FROM ' . dbc::BOOKINGS_TABLE . ' WHERE id = ' .
			$this->ilDB->quote($a_booking_id, 'integer') .
			' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer') .
			' AND (date_from >= "' . date('Y-m-d H:i:s') . '"' .
			' OR date_to >= "' . date('Y-m-d H:i:s') . '")' .
			' ORDER BY date_from ASC');

		$booking = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$booking = $row;
		}
		return $booking;
	}

	/**
	 * Gets and returns all bookings that have been made; even the ones in the past.
	 *
	 * @param integer $a_room_id the id of the room for which the bookings should be returnd
	 * @return array an array containing information about bookings for the specified room
	 */
	public function getAllBookingsForRoom($a_room_id)
	{
		$set = $this->ilDB->query('SELECT * FROM ' . dbc::BOOKINGS_TABLE .
			' WHERE room_id = ' . $this->ilDB->quote($a_room_id, 'integer') .
			' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer'));
		$bookings = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$bookings[] = $row;
		}
		return $bookings;
	}

	/**
	 * Gets all bookings for a room which are in present or future.
	 *
	 * @param integer $a_room_id the room id
	 * @return array list of bookings
	 */
	public function getBookingsForRoomThatAreValid($a_room_id)
	{
		$set = $this->ilDB->query('SELECT * FROM ' . dbc:: BOOKINGS_TABLE .
			' WHERE room_id = ' . $this->ilDB->quote($a_room_id, 'integer') .
			' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer') .
			' AND (date_from >= "' . date('Y-m-d H:i:s') . '"' .
			' OR date_to >= "' . date('Y-m-d H:i:s') . '")');
		$bookings = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$bookings[] = $row;
		}
		return $bookings;
	}

	/**
	 * Gets all actual bookings for a room.
	 *
	 * @return type return of $this->ilDB->query
	 */
	public function getAllBookingsIds()
	{
		$set = $this->ilDB->query('SELECT id FROM ' . dbc::BOOKINGS_TABLE .
			' WHERE pool_id = ' . $this->ilDB->quote($this->pool_id, 'integer'));

		$bookingsIds = array();
		while ($bookingRow = $this->ilDB->fetchAssoc($set))
		{
			$bookingsIds [] = $bookingRow['id'];
		}

		return $bookingsIds;
	}

	/*
	 * Gets all booking ids for one room in given datetime ranges.
	 *
	 * @param integer $a_room_id
	 * @param array $a_datetimes_from
	 * @param array $a_datetimes_to
	 * @return array booking ids
	 */
	public function getBookingIdsForRoomInDateimeRanges($a_room_id, $a_datetimes_from, $a_datetimes_to)
	{
		$query = 'SELECT id FROM ' . dbc::BOOKINGS_TABLE . ' WHERE room_id = ' .
			$this->ilDB->quote($a_room_id, 'integer');

		$count = count($a_datetimes_from);
		if ($count == count($a_datetimes_to))
		{
			// throw exception if arrays not same size?
			for ($i = 0; $i < $count; $i++)
			{
				if ($i == 0) $query .= ' AND (';
				$a_datetime_from = $a_datetimes_from[$i];
				$a_datetime_to = $a_datetimes_to[$i];

				$query .= ' (' . $this->ilDB->quote($a_datetime_to, 'timestamp') . ' > date_from' .
					' AND ' . $this->ilDB->quote($a_datetime_from, 'timestamp') . ' < date_to) OR';
				if ($i == $count - 1)
				{
					$query = substr($query, 0, -2);
					$query .= ')';
				}
			}
		}

		$set = $this->ilDB->query($query);
		$res_book_id = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$res_book_id [] = $row ['id'];
		}
		return $res_book_id;
	}

	/**
	 * Updating a booking in the database.
	 *
	 * @global type $ilUser
	 * @param int $a_booking_id the booking ID
	 * @param array $a_booking_attr_values new Values of the attribute from the booking
	 * @param array $a_old_booking_attr_values old Values of the attribute from the booking
	 * @param array $a_booking_values new Values of the the booking
	 * @param array $a_old_booking_values old Values of the the booking (temporary unused, for expansion)
	 * @param array $a_booking_participants new Values of the participants
	 * @param array $a_old_booking_participants old Values of the participants
	 *
	 * @return int 1 if succes, 0 if fail
	 */
	public function updateBooking($a_booking_id, $a_booking_attr_values, $a_old_booking_attr_values,
		$a_booking_values, $a_old_booking_values, $a_booking_participants, $a_old_booking_participants)
	{
		global $ilUser;

		$fields = array(
			'id' => array('integer', $a_booking_id),
			'date_from' => array('timestamp', $a_booking_values ['from'] ['date'] . " " .
				$a_booking_values ['from'] ['time']),
			'date_to' => array('timestamp', $a_booking_values ['to'] ['date'] . " " .
				$a_booking_values ['to'] ['time']),
			'room_id' => array('integer', $a_booking_values ['room']),
			'user_id' => array('integer', $ilUser->getId()),
			'subject' => array('text', $a_booking_values ['subject']),
			'public_booking' => array('boolean', $a_booking_values ['book_public'] == '1'),
			'bookingcomment' => array('text', $a_booking_values ['comment'])
		);
		$where = array(
			'id' => array('integer', $a_booking_id),
			'pool_id' => array('integer', $this->pool_id)
		);

		$this->ilDB->update(dbc::BOOKINGS_TABLE, $fields, $where);

		$this->ilRoomSharingDatabase->updateBookingAttributes($a_booking_id, $a_booking_attr_values,
			$a_old_booking_attr_values);

		$this->ilRoomSharingDatabase->updateBookingParticipants($a_booking_id, $a_booking_participants,
			$a_old_booking_participants);

		$this->ilRoomSharingDatabase->updateBookingAppointment($a_booking_id, $a_booking_values);

		return 1;
	}

	/**
	 * Get all other the booking-ids for a room-id that in the given timerange.
	 *
	 * @param string $a_date_from
	 * @param string $a_date_to
	 * @param string $a_room_id
	 * @parm integer $a_booking_id
	 *
	 * @return array values = all other book ids for the given room ids in the time range
	 */
	public function getBookingIdForRoomInDateTimeRange($a_date_from, $a_date_to, $a_room_id,
		$a_booking_id)
	{
		$query = 'SELECT DISTINCT id FROM ' . dbc::BOOKINGS_TABLE .
			' WHERE pool_id =' . $this->ilDB->quote($this->pool_id, 'integer') . ' AND ' .
			' room_id = ' . $this->ilDB->quote($a_room_id, 'text') . ' AND ' .
			' id != ' . $this->ilDB->quote($a_booking_id, 'integer') . ' AND ' .
			' (' . $this->ilDB->quote($a_date_from, 'timestamp') .
			' BETWEEN date_from AND date_to OR ' . $this->ilDB->quote($a_date_to, 'timestamp') .
			' BETWEEN date_from AND date_to OR date_from BETWEEN ' .
			$this->ilDB->quote($a_date_from, 'timestamp') . ' AND ' . $this->ilDB->quote($a_date_to,
				'timestamp') .
			' OR date_to BETWEEN ' . $this->ilDB->quote($a_date_from, 'timestamp') .
			' AND ' . $this->ilDB->quote($a_date_to, 'timestamp') . ')';

		$set = $this->ilDB->query($query);
		$booking_ids = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$booking_ids[] = $row ['id'];
		}
		return $booking_ids;
	}

	/**
	 * Returns the info for a booking
	 * (title, user, description, room, start, end)
	 *
	 * @param type $booking_id
	 * @return array the booking info
	 */
	public function getInfoForBooking($booking_id)
	{
		$set = $this->ilDB->query('SELECT * FROM ' . dbc::BOOKINGS_TABLE . ' b LEFT JOIN ' .
			dbc::ROOMS_TABLE . ' r ON r.id = b.room_id LEFT JOIN usr_data u ON u.usr_id = b.user_id WHERE b.id = ' .
			$this->ilDB->quote($booking_id, 'integer'));
		$info = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$info['title'] = $row['subject'];
			$info['user'] = $row['public_booking'] == 1 ? 'Gebucht von ' . $row['firstname'] . ' ' . $row['lastname'] . '<BR>'
					: '';
			$info['description'] = $row['bookingcomment'];
			$info['room'] = $row['name'];
			$info['start'] = new ilDateTime($row['date_from'], IL_CAL_DATETIME);
			$info['end'] = new ilDateTime($row['date_to'], IL_CAL_DATETIME);
		}
		return $info;
	}

	/**
	 * Gathers all current bookings that have been made for this room.
	 *
	 * @param integer $a_room_id the id of the room for which the current bookings should be returned
	 * @return array an array containing information regarding the bookings
	 */
	public function getCurrentBookingsForRoom($a_room_id)
	{
		$set = $this->ilDB->query('SELECT * FROM ' . dbc:: BOOKINGS_TABLE .
			' WHERE room_id = ' . $this->ilDB->quote($a_room_id, 'integer') .
			' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer') .
			' AND (date_from >= "' . date('Y-m-d H:i:s') . '"' .
			' OR date_to >= "' . date('Y-m-d H:i:s') . '")' . ' ORDER BY date_from ASC');
		$bookings = array();
		while ($row = $this->ilDB->fetchAssoc($set))
		{
			$bookings[] = $row;
		}
		return $bookings;
	}

	/**
	 * Deletes all bookings that are assigned to a room.
	 *
	 * @param integer $a_room_id the id of the room for which the bookings should be deleted
	 * @return integer the amount of bookings that are affected by the deletion
	 */
	public function deleteAllBookingsAssignedToRoom($a_room_id)
	{
		return $this->ilDB->manipulate('DELETE FROM ' . dbc::BOOKINGS_TABLE .
				' WHERE room_id = ' . $this->ilDB->quote($a_room_id, 'integer') .
				' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer'));
	}

	/**
	 * Gets all bookings for a room in a time span
	 *
	 * @param $room_id
	 * @param $start
	 * @param $end
	 * @param $type
	 * @return array bookings
	 */
	public function getBookingsForRoomInTimeSpan($room_id, $start, $end, $type)
	{
		$query = 'SELECT b.id id FROM ' . dbc::BOOKINGS_TABLE . ' b';

		if ($type != 4)
		{
			$query .= ' WHERE ((date_from <= ' .
				$this->ilDB->quote($end->get(IL_CAL_DATETIME, '', 'UTC'), 'timestamp') .
				' AND date_to >= ' .
				$this->ilDB->quote($start->get(IL_CAL_DATETIME, '', 'UTC'), 'timestamp') .
				') OR (date_from <= ' .
				$this->ilDB->quote($end->get(IL_CAL_DATETIME, '', 'UTC'), 'timestamp') .
				' ))';
		}
		else
		{
			$date = new ilDateTime(mktime(0, 0, 0), IL_CAL_UNIX);
			$query .= ' WHERE date_from >= ' .
				$this->ilDB->quote($date->get(IL_CAL_DATETIME, '', 'UTC'), 'timestamp');
		}

		$query .= ' AND room_id = ' . $this->ilDB->quote($room_id, 'integer') .
			' AND pool_id = ' . $this->ilDB->quote($this->pool_id, 'integer') .
			' ORDER BY date_from';

		$res = $this->ilDB->query($query);

		$events = array();
		while ($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$events[] = $row;
		}
		return $events;
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
