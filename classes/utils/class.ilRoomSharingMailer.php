<?php

require_once('Services/Mail/classes/class.ilMailNotification.php');
require_once('Services/Calendar/classes/class.ilDate.php');
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");

/**
 * This class is used for generating mails to inform users about the bookings.
 * The ilSystemNotification-Class does not work with our language files, so we
 * have to implement our own mailer.
 *
 * @author Fabian Müller <famueller@stud.hs-bremen.de>
 */
class ilRoomSharingMailer extends ilMailNotification
{
	private $s_roomname;
	private $datestart;
	private $dateend;
	private $reason;
	private $lng;
	private $bookings;
	private $ilRoomSharingDatabase;

	/**
	 * Constructor with language option.
	 *
	 * @param object language object from plugin
	 * @param type $a_is_personal_workspace
	 */
	public function __construct($lng = false, $pool_id = 0, $a_is_personal_workspace = false)
	{
		parent::__construct($a_is_personal_workspace);
		$this->lng = $lng;
		$this->ilRoomSharingDatabase = new ilRoomsharingDatabase($pool_id);
	}

	/**
	 * Set room name.
	 *
	 * @param string $s_roomname
	 */
	/**
	 * Set room name.
	 *
	 * @param string $s_roomname
	 */
	public function setRoomname($s_roomname)
	{
		$this->s_roomname = (string) $s_roomname;
	}

	/**
	 * Set starting date
	 *
	 * @param string $s_datestart
	 */
	public function setDateStart($s_datestart)
	{
		$this->datestart = new ilDateTime((string) $s_datestart, IL_CAL_DATETIME);
	}

	/**
	 * Set end date
	 *
	 * @param string $s_dateend
	 */
	public function setDateEnd($s_dateend)
	{
		$this->dateend = new ilDateTime((string) $s_dateend, IL_CAL_DATETIME);
	}

	/**
	 * Compose a mail that informs user about a change of room attributes.
	 * @param integer $a_user_id ID of user
	 */
	private function composeRoomChangeMail($a_user_id)
	{
		$lng = $this->lng;

		$this->initLanguage($a_user_id);
		$this->initMail();
		$this->setSubject($this->lng->txt('rep_robj_xrs_mail_room_changed_subject'));
		$this->setBody(ilMail::getSalutation($a_user_id, $this->getLanguage()));
		$this->appendBody("\n\n");
		$this->appendBody($lng->txt('rep_robj_xrs_mail_room_changed_message') . "\n");
		foreach ($this->bookings as $booking)
		{
			if ($booking['user_id'] != $a_user_id)
			{
				continue;
			}
			else
			{
				$roomname = $this->ilRoomSharingDatabase->getRoomName($booking['room_id']);
				$datestart = new ilDateTime($booking['date_from'], IL_CAL_DATETIME);
				$dateend = new ilDateTime($booking['date_to'], IL_CAL_DATETIME);

				$this->appendBody($lng->txt('rep_robj_xrs_room') . " ");
				$this->appendBody($roomname . " ");
				$this->appendBody($lng->txt('rep_robj_xrs_from') . " ");
				$this->appendBody($datestart->get(IL_CAL_FKT_DATE, 'd.m.Y H:s') . " ");
				$this->appendBody($lng->txt('rep_robj_xrs_to') . " ");
				$this->appendBody($dateend->get(IL_CAL_FKT_DATE, 'd.m.Y H:s') . "\n");
			}
		}
	}

	/**
	 * Compose a mail that confirms a user that he quit a participation.
	 */
	private function composeParticipationCancelMail($a_user_id)
	{
		$lng = $this->lng;

		$this->initLanguage($a_user_id);
		$this->initMail();
		$this->setSubject($lng->txt('rep_robj_xrs_mail_participation_cancel_subject'));
		$this->setBody(ilMail::getSalutation($a_user_id, $this->getLanguage()));
		$this->appendBody("\n\n");
		$this->appendBody($lng->txt('rep_robj_xrs_mail_participation_cancel_message') . "\n");

		foreach ($this->bookings as $booking)
		{
			$roomname = $this->ilRoomSharingDatabase->getRoomName($booking['room_id']);
			$datestart = new ilDateTime($booking['date_from'], IL_CAL_DATETIME);
			$dateend = new ilDateTime($booking['date_to'], IL_CAL_DATETIME);

			$this->appendBody($lng->txt('rep_robj_xrs_room') . " ");
			$this->appendBody($roomname . " ");
			$this->appendBody($lng->txt('rep_robj_xrs_from') . " ");
			$this->appendBody($datestart->get(IL_CAL_FKT_DATE, 'd.m.Y H:s') . " ");
			$this->appendBody($lng->txt('rep_robj_xrs_to') . " ");
			$this->appendBody($dateend->get(IL_CAL_FKT_DATE, 'd.m.Y H:s') . "\n");
		}
	}

	/**
	 * Compose a mail that tells the creator that somenone has left
	 */
	private function composeParticipationCancelMailForCreator($a_user_id, $a_creator_id)
	{
		$lng = $this->lng;
		$user = $this->ilRoomSharingDatabase->getUserById($a_user_id);

		$this->initLanguage($a_creator_id);
		$this->initMail();
		$this->setSubject($lng->txt('rep_robj_xrs_mail_participation_creator_subject'));
		$this->setBody(ilMail::getSalutation($a_creator_id, $this->getLanguage()));
		$this->appendBody("\n\n");
		$this->appendBody($lng->txt('rep_robj_xrs_mail_participation_creator_message') . "\n");
		$this->appendBody($lng->txt('rep_robj_xrs_user') . ": ");
		$this->appendBody($user['firstname'] . " " . $user['lastname'] . " (" . $user['login'] . ")\n");
		$this->appendBody($lng->txt('rep_robj_xrs_booking') . ":\n");
		$booking = $this->bookings[0];

		$roomname = $this->ilRoomSharingDatabase->getRoomName($booking['room_id']);
		$datestart = new ilDateTime($booking['date_from'], IL_CAL_DATETIME);
		$dateend = new ilDateTime($booking['date_to'], IL_CAL_DATETIME);

		$this->appendBody($lng->txt('rep_robj_xrs_room') . " ");
		$this->appendBody($roomname . " ");
		$this->appendBody($lng->txt('rep_robj_xrs_from') . " ");
		$this->appendBody($datestart->get(IL_CAL_FKT_DATE, 'd.m.Y H:s') . " ");
		$this->appendBody($lng->txt('rep_robj_xrs_to') . " ");
		$this->appendBody($dateend->get(IL_CAL_FKT_DATE, 'd.m.Y H:s') . "\n");
	}

	/**
	 * Send notification to creator of booking
	 * @param array $a_user_id user-id who will get the mail
	 * Returns nothing.
	 */
	private function composeAndSendBookingMailToCreator($a_user_id)
	{
		$this->composeBookingMailForCreator($a_user_id);
		parent::sendMail(array($a_user_id), array('system'), is_numeric($a_user_id));
	}

	/**
	 * Set reason why booking was cancelled.
	 *
	 * @param string $s_reason Reason for cancellation.
	 *                          Used by sendCancellationMailWithReason()
	 */
	public function setReason($s_reason)
	{
		$this->reason = $s_reason;
	}

	/**
	 * Send room change notification to user.
	 * @param integer $a_userid ID of user who gets the mail
	 */
	private function composeAndSendRoomChangeMail($a_userid)
	{
		$this->composeRoomChangeMail($a_userid);
		parent::sendMail(array($a_userid), array('system'), is_numeric($a_userid));
	}

	/**
	 * Send participation quit message to user.
	 * @param integer $a_userid ID of user who gets the mail
	 */
	private function composeAndSendParticipationCancelMail($a_userid)
	{
		$this->composeParticipationCancelMail($a_userid);
		parent::sendMail(array($a_userid), array('system'), is_numeric($a_userid));
	}

	/**
	 * Send creator a mail that a user has left.
	 * @param integer $a_user_id The user who left.
	 * @param integer $a_creator_id The creator of a booking.
	 */
	private function composeAndSendParticipationCancelMailForCreator($a_user_id, $a_creator_id)
	{
		$this->composeParticipationCancelMailForCreator($a_user_id, $a_creator_id);
		parent::sendMail(array($a_creator_id), array('system'), is_numeric($a_creator_id));
	}

	/**
	 * Send booking notification to creator and participants
	 * @param array $a_user_id userid of creator
	 * @param array $a_participants_ids userids of participants
	 *
	 * Returns nothing
	 */
	public function sendBookingMail($a_user_id, array $a_participants_ids)
	{
		$user = $this->ilRoomSharingDatabase->getUserById($a_user_id);
		$this->composeAndSendBookingMailToCreator($a_user_id);

		foreach (array_unique($a_participants_ids) as $participant_id)
		{
			if ($user['login'] != $participant_id)
			{
				$this->composeAndSendBookingMailToParticipant($participant_id);
			}
		}
	}

	/**
	 * Send a mail to every user when a room has been edited.
	 *
	 * @param integer $a_roomid id of the room.
	 */
	public function sendRoomChangeMail($a_roomid)
	{
		$user_ids = array();
		$this->bookings = $this->ilRoomSharingDatabase->getBookingsForRoomThatAreValid($a_roomid);
		foreach ($this->bookings as $booking)
		{
			$user_ids[] = $booking['user_id'];
		}
		foreach (array_unique($user_ids) as $userid)
		{
			$this->composeAndSendRoomChangeMail($userid);
		}
	}

	/**
	 * Send a mail to every user when a room has been edited.
	 *
	 * @param array $a_user_id The users id
	 * @param array $a_booking_ids The id(s) of the booking
	 */
	public function sendParticipationCancelMail($a_user_id, $a_booking_ids)
	{
		foreach ($a_booking_ids as $booking_id)
		{
			$type = $this->ilRoomSharingDatabase->getBooking($booking_id);
			$this->bookings[] = $type[0];
		}
		$this->composeAndSendParticipationCancelMail($a_user_id);
	}

	/**
	 * Send a mail to the creator of a booking that a user left.
	 *
	 * @param array $a_user_id The id of the user who left
	 * @param array $a_creator_id The id of the creator
	 * @param array $a_booking_ids The booking ids
	 */
	public function sendParticipationCancelMailForCreator($a_user_id, $a_booking_ids)
	{
		foreach ($a_booking_ids as $booking_id)
		{
			$type = $this->ilRoomSharingDatabase->getBooking($booking_id);
			$this->bookings[0] = $type[0];
			$this->composeAndSendParticipationCancelMailForCreator($a_user_id, $this->bookings[0]['user_id']);
		}
	}

	/**
	 * Compose notification of booking for the creator of that booking.
	 *
	 * @param string $a_user_id The user who will get the mail.
	 * Returns nothing.
	 */
	private function composeBookingMailForCreator($a_user_id)
	{
		$lng = $this->lng;

		$this->initLanguage($a_user_id);
		$this->initMail();
		$this->setSubject($this->lng->txt('rep_robj_xrs_mail_booking_creator_subject'));
		$this->setBody(ilMail::getSalutation($a_user_id, $this->getLanguage()));
		$this->appendBody("\n\n");
		$this->appendBody($lng->txt('rep_robj_xrs_mail_booking_creator_message') . "\n");
		$this->appendBody($lng->txt('rep_robj_xrs_room') . " ");
		$this->appendBody($this->s_roomname . " ");
		$this->appendBody($lng->txt('rep_robj_xrs_from') . " ");
		$this->appendBody($this->datestart->get(IL_CAL_FKT_DATE, 'd.m.Y H:s') . " ");
		$this->appendBody($lng->txt('rep_robj_xrs_to') . " ");
		$this->appendBody($this->dateend->get(IL_CAL_FKT_DATE, 'd.m.Y H:s'));
		$this->appendBody("\n\n");
		$this->appendBody(ilMail::_getAutoGeneratedMessageString($this->language));
	}

	/**
	 * Compose notification of update booking for the creator of that booking.
	 *
	 * @param string $a_user_id The user who will get the mail.
	 * Returns nothing.
	 */
	private function composeUpdateBookingMailForCreator($a_user_id)
	{
		$lng = $this->lng;

		$this->initLanguage($a_user_id);
		$this->initMail();
		$this->setSubject($this->lng->txt('rep_robj_xrs_mail_update_booking_creator_subject'));
		$this->setBody(ilMail::getSalutation($a_user_id, $this->getLanguage()));
		$this->appendBody("\n\n");
		$this->appendBody($lng->txt('rep_robj_xrs_mail_update_booking_creator_message') . "\n");
		$this->appendBody($lng->txt('rep_robj_xrs_room') . " ");
		$this->appendBody($this->s_roomname . " ");
		$this->appendBody($lng->txt('rep_robj_xrs_from') . " ");
		$this->appendBody($this->datestart->get(IL_CAL_FKT_DATE, 'd.m.Y H:s') . " ");
		$this->appendBody($lng->txt('rep_robj_xrs_to') . " ");
		$this->appendBody($this->dateend->get(IL_CAL_FKT_DATE, 'd.m.Y H:s'));
		$this->appendBody("\n\n");
		$this->appendBody(ilMail::_getAutoGeneratedMessageString($this->language));
	}

	/**
	 * Compose notification of booking for participants.
	 *
	 * @param string $a_user_id The participant who will get the mail.
	 * Returns nothing.
	 */
	private function composeBookingMailForParticipant($a_user_id)
	{
		$lng = $this->lng;

		$this->initLanguage($a_user_id);
		$this->initMail();
		$this->setSubject($this->lng->txt('rep_robj_xrs_mail_booking_participant_subject'));
		$this->setBody(ilMail::getSalutation($a_user_id, $this->getLanguage()));
		$this->appendBody("\n\n");
		$this->appendBody($lng->txt('rep_robj_xrs_mail_booking_participant_message') . "\n");
		$this->appendBody($lng->txt('rep_robj_xrs_room') . " ");
		$this->appendBody($this->s_roomname . " ");
		$this->appendBody($lng->txt('rep_robj_xrs_from') . " ");
		$this->appendBody($this->datestart->get(IL_CAL_FKT_DATE, 'd.m.Y H:s') . " ");
		$this->appendBody($lng->txt('rep_robj_xrs_to') . " ");
		$this->appendBody($this->dateend->get(IL_CAL_FKT_DATE, 'd.m.Y H:s'));
		$this->appendBody("\n\n");
		$this->appendBody(ilMail::_getAutoGeneratedMessageString($this->language));
	}

	/**
	 * Compose update booking notification for participants.
	 *
	 * @param string $a_user_id The participant who will get the mail.
	 */
	private function composeUpdatingBookingMailForParticipant($a_user_id)
	{
		$lng = $this->lng;

		$this->initLanguage($a_user_id);
		$this->initMail();
		$this->setSubject($this->lng->txt('rep_robj_xrs_mail_update_booking_participant_subject'));
		$this->setBody(ilMail::getSalutation($a_user_id, $this->getLanguage()));
		$this->appendBody("\n\n");
		$this->appendBody($lng->txt('rep_robj_xrs_mail_update_booking_participant_message') . "\n");
		$this->appendBody($lng->txt('rep_robj_xrs_room') . " ");
		$this->appendBody($this->s_roomname . " ");
		$this->appendBody($lng->txt('rep_robj_xrs_from') . " ");
		$this->appendBody($this->datestart->get(IL_CAL_FKT_DATE, 'd.m.Y H:s') . " ");
		$this->appendBody($lng->txt('rep_robj_xrs_to') . " ");
		$this->appendBody($this->dateend->get(IL_CAL_FKT_DATE, 'd.m.Y H:s'));
		$this->appendBody("\n\n");
		$this->appendBody(ilMail::_getAutoGeneratedMessageString($this->language));
	}

	/**
	 * Compose a update mail for the participant about the removing the creator from a booking.
	 *
	 * @param string $a_user_id
	 */
	private function composeRemovingParticipantFromBookingAsMailForParticipant($a_user_id)
	{
		$lng = $this->lng;

		$this->initLanguage($a_user_id);
		$this->initMail();
		$this->setSubject($this->lng->txt('rep_robj_xrs_mail_removing participant_subject'));
		$this->setBody(ilMail::getSalutation($a_user_id, $this->getLanguage()));
		$this->appendBody("\n\n");
		$this->appendBody($lng->txt('rep_robj_xrs_mail_removing participant_message') . "\n");
		$this->appendBody($lng->txt('rep_robj_xrs_room') . " ");
		$this->appendBody($this->s_roomname . " ");
		$this->appendBody($lng->txt('rep_robj_xrs_from') . " ");
		$this->appendBody($this->datestart->get(IL_CAL_FKT_DATE, 'd.m.Y H:s') . " ");
		$this->appendBody($lng->txt('rep_robj_xrs_to') . " ");
		$this->appendBody($this->dateend->get(IL_CAL_FKT_DATE, 'd.m.Y H:s') . " \n");
		$this->appendBody("\n\n");
		$this->appendBody(ilMail::_getAutoGeneratedMessageString($this->language));
	}

	/**
	 * Send notification to Creator the of booking.
	 *
	 * @param string $a_user_id user-id who will get the mail
	 */
	protected function composeAndSendUpdateBookingMailToCreator($a_user_id)
	{
		$this->composeUpdateBookingMailForCreator($a_user_id);
		parent::sendMail(array($a_user_id), array('system'), is_numeric($a_user_id));
	}

	/**
	 * Send notification about adding the participants to a booking
	 *
	 * @param string $a_participant_id user-id who will get the mail
	 */
	protected function composeAndSendBookingMailToParticipant($a_participant_id)
	{
		$this->composeBookingMailForParticipant($a_participant_id);
		parent:: sendMail(array($a_participant_id), array('system'), is_numeric($a_participant_id));
	}

	/**
	 * Send notification about booking update to the participants
	 *
	 * @param string $a_participant_id user-id who will get the mail
	 */
	protected function composeAndSendUpdateBookingMailToParticipant($a_participant_id)
	{
		$this->composeUpdatingBookingMailForParticipant($a_participant_id);
		parent::sendMail(array($a_participant_id), array('system'), is_numeric($a_participant_id));
	}

	/**
	 * Send cancelling notification to creator of booking
	 * @param string $a_user_id user-id who will get the mail
	 * Returns nothing.
	 */
	protected function composeAndSendCancellationMailToCreator($a_user_id, $s_reason)
	{
		$this->composeCancellationMailForCreator(
			$a_user_id, $s_reason);
		parent::sendMail(array($a_user_id), array('system'), is_numeric($a_user_id));
	}

	/**
	 * Send cancellation notification to participant of booking
	 * @param string $a_participant_id user-id who will get the mail
	 */
	protected function composeAndSendCancellationMailToParticipant($a_participant_id, $s_reason)
	{
		$this->composeCancellationMailForParticipant($a_participant_id, $s_reason);
		parent::sendMail(array
			($a_participant_id), array('system'), is_numeric($a_participant_id));
	}

	/**
	 * Send booking notification to new participants
	 *
	 * @param array $a_participants_ids userids of participants
	 */
	public function sendBookingMailToNewUser(array
	$a_participants_ids)
	{
		foreach (array_unique($a_participants_ids) as $participant_id)
		{
			$this->composeAndSendBookingMailToParticipant($participant_id);
		}
	}

	/**
	 * Send cancel booking notification to participants
	 *
	 * @param array $a_participants_ids userids of participants
	 */
	public function sendCancellationMailToParticipants($a_participants_ids)
	{
		foreach (array_unique($a_participants_ids) as $participant_id)
		{
			$this->composeRemovingParticipantFromBookingAsMailForParticipant($participant_id);
			parent::sendMail(array
				($participant_id), array('system'), is_numeric($participant_id));
		}
	}

	/**
	 * Send cancellation notification to creator and participants.
	 * @param string $a_user_id userid of creator
	 * @param array $a_participants_ids userids of participants
	 *
	 * Returns nothing.
	 */
	public function sendCancellationMail($a_user_id, array
	$a_participants_ids)
	{
		$this->composeAndSendCancellationMailToCreator($a_user_id, NULL);
		foreach (array_unique($a_participants_ids) as $participant_id)
		{
			$this->composeAndSendCancellationMailToParticipant($participant_id, NULL);
		}
	}

	/**
	 * Compose a cancellation mail for the creator of the booking.
	 * @param type $a_user_id The user who will get the cancellation mail.
	 */
	private function composeCancellationMailForCreator($a_user_id, $reason = "")
	{
		$lng = $this->lng;

		$this->initLanguage($a_user_id);
		$this->initMail();
		$this->setSubject($this->lng->txt('rep_robj_xrs_mail_cancellation_creator_subject'));
		$this->setBody(ilMail::getSalutation($a_user_id, $this->getLanguage()));
		$this->appendBody("\n\n");
		$this->appendBody($lng->txt('rep_robj_xrs_mail_cancellation_creator_message
			') . "\n");
		$this->appendBody($lng->txt('rep_robj_xrs_room') . " ");
		$this->appendBody($this->s_roomname . " ");
		$this->appendBody($lng->txt('rep_robj_xrs_from') . " ");
		$this->appendBody($this->datestart->get(IL_CAL_FKT_DATE, 'd.m.Y H:s') . " ");
		$this->appendBody($lng->txt('rep_robj_xrs_to') . " ");
		$this->appendBody($this->dateend->get(IL_CAL_FKT_DATE, 'd.m.Y H:s') . " \n");
		if ($reason !== '')
		{
			$this->appendBody($lng->txt('rep_robj_xrs_mail_cancellation_reason_prefix') . "\n");
			$this->appendBody($reason . "\n");
			$this->appendBody("\n");
		}
	}

	/**
	 * Compose a cancellat ion mail for the participants of the booking.
	 * @param type $a_user_id The user who will get the cancellation mail.
	 */
	private function composeCancellationMailForParticipant($a_user_id, $reason = "")
	{
		$lng = $this->lng;

		$this->initLanguage($a_user_id);
		$this->initMail();
		$this->setSubject($this->lng->txt('rep_robj_xrs_mail_cancellation_participant_subject'));
		$this->setBody(ilMail::getSalutation($a_user_id, $this->getLanguage()));
		$this->appendBody("\n\n");
		$this->appendBody($lng->txt('rep_robj_xrs_mail_cancellation_participant_message') . "\n");
		$this->appendBody($lng->txt('rep_robj_xrs_room') . " ");
		$this->appendBody($this->s_roomname . " ");
		$this->appendBody($lng->txt('rep_robj_xrs_from') . " ");
		$this->appendBody($this->datestart->get(IL_CAL_FKT_DATE, 'd.m.Y H:s') . " ");
		$this->appendBody($lng->txt('rep_robj_xrs_to') . " ");
		$this->appendBody($this->dateend->get(IL_CAL_FKT_DATE, 'd.m.Y H:s') . " \n");
		if ($reason !== '')
		{
			$this->appendBody($lng->txt('rep_robj_xrs_mail_cancellation_reason_prefix') . "\n");
			$this->appendBody($reason . "\n");
			$this->appendBody("\n");
		}
	}

	/**
	 * Compose notification of sequence booking for the creator of that booking.
	 *
	 * @param array $a_user_id The user who will get the mail.
	 * Returns nothing.
	 */
	private function composeSequenceBookingMailForCreator($a_user_id)
	{
		$lng = $this->lng;

		$this->initLanguage($a_user_id);
		$this->initMail();
		$this->setSubject($this->lng->txt('rep_robj_xrs_mail_sequencebook_creator_subject'));
		$this->setBody(ilMail::getSalutation($a_user_id, $this->getLanguage()));
		$this->appendBody("\n\n");
		$this->appendBody($lng->txt('rep_robj_xrs_mail_sequencebook_cre ator_message') . "\n");
		$this->appendBody($lng->txt('rep_robj_xrs_room') . " ");
		$this->appendBody($this->s_roomname . " ");
		$this->appendBody($lng->txt('rep_robj_xrs_from') . " ");
		$this->appendBody($this->datestart->get(IL_CAL_FKT_DATE, 'd.m.Y H:s') . " ");
		$this->appendBody($lng->txt('rep_robj_xrs_to') . " ");
		$this->appendBody($this->dateend->get(IL_CAL_FKT_DATE, 'd.m.Y H:s'));
		$this->appendBody("\n\n");
		$this->appendBody(ilMail::_getAutoGeneratedMessageString($this->language));
	}

	/**
	 * Send notification to creator of booking series
	 * @param array $a_user_id user-id who will get the mail
	 * Returns nothing.
	 */
	protected function composeAndSendSequenceBookingMailToCreator($a_user_id)
	{
		$this->composeSequenceBookingMailForCreator($a_user_id);
		parent::sendMail(array($a_user_id), array('system'), is_numeric($a_user_id)
		);
	}

	/**
	 * Send a update booking notification to the participants.
	 *
	 * @param string $a_user_id = userid of creator
	 * @param array $a_participants_ids = userids of participants
	 *
	 */
	public function sendUpdateBookingMailToParticipants(array $a_participants_ids)
	{
		foreach (array_unique($a_participants_ids) as $participant_id)
		{
			$this->composeUpdatingBookingMailForParticipant($participant_id);
			parent::sendMail(array($participant_id), array('system'), is_numeric($participant_id));
		}
	}

	/**
	 * Send cancellation notification to creator and participants.
	 * This also includes a reason, why the booking was cancelled.
	 * The reason has to be set via the setReason() function.
	 * @param array $a_user_id userid of creator
	 * @param array $a_participants_ids userids of participants
	 *
	 * Returns nothing.
	 */
	public function sendCancellationMailWithReason($a_user_id, array $a_participants_ids)
	{
		$this->composeAndSendCancellationMailToCreator($a_user_id, $this->reason);
		foreach (array_unique($a_participants_ids) as $participant_id)
		{
			$this->composeAndSendCancellationMailToParticipant($participant_id, $this->reason);
		}
	}

}
