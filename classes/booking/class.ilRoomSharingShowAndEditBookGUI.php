<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/rooms/class.ilRoomSharingRooms.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/appointments/bookings/class.ilRoomSharingBookings.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/rooms/detail/class.ilRoomSharingRoom.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/booking/class.ilRoomSharingBook.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/exceptions/class.ilRoomSharingBookException.php");
require_once("Services/Form/classes/class.ilCombinationInputGUI.php");
require_once("Services/Form/classes/class.ilPropertyFormGUI.php");
require_once("Services/User/classes/class.ilUserAutoComplete.php");
include_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");

use ilRoomSharingPrivilegesConstants as PRIVC;

/**
 * Class ilRoomSharingShowAndEditBookGUI
 *
 * @author Michael Dazjuk <mdazjuk@stud.hs-bremen.de>
 * @author Robert Heimsoth <rheimsoth@stud.hs-bremen.de>
 * @author Alexander Keller <a.k3ll3r@gmail.com>
 *
 * @version $Id$
 */
class ilRoomSharingShowAndEditBookGUI
{
	private $parent_obj;
	private $pool_id;
	private $room_id;
	private $date_from;
	private $date_to;
	private $book;
	private $mode;
	private $booking_id;
	private $old_booking_values;
	private $old_attr_values;
	private $old_participants;

	const NUM_PERSON_RESPONSIBLE = 1;
	const EDIT_BOOK_CMD = "editBooking";
	const SAVE_BOOK_CMD = "saveEditBooking";
	const CANCEL_EDIT_CMD = "cancelEdit";
	const SESSION_BOOKING_ID = "bookingID";
	const SESSION_ROOM_ID = "roomID";
	const SESSION_MODE = 'mode';

	/**
	 * Constructur for ilRoomSharingBookGUI
	 *
	 * @param ilObjRoomSharingGUI $a_parent_obj
	 * @param integer $a_booking_id
	 * @param integer $a_room_id
	 * @param string $a_mode
	 */
	public function __construct($a_parent_obj, $a_booking_id, $a_room_id, $a_mode = null)
	{
		global $ilCtrl, $lng, $tpl, $rssPermission;

		$this->permission = $rssPermission;
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		$this->tpl = $tpl;
		$this->parent_obj = $a_parent_obj;
		$this->pool_id = $a_parent_obj->getPoolId();

		$this->setOrGetSessionVariables($a_booking_id, $a_room_id, $a_mode);

		$this->book = new ilRoomSharingBook($this->pool_id);
	}

	/**
	 * Methode to organzie the get and set of the session variables.
	 *
	 * @param integer $a_booking_id
	 * @param integer $a_room_id
	 * @param string $a_mode
	 */
	private function setOrGetSessionVariables($a_booking_id, $a_room_id, $a_mode)
	{
		if (!empty($a_booking_id))
		{
			$this->booking_id = $a_booking_id;
			$this->setBookingId($a_booking_id);
		}
		else
		{
			$this->booking_id = $this->getBookingId();
		}

		if (!empty($a_room_id))
		{
			$this->room_id = $a_room_id;
			$this->setRoomId($a_room_id);
		}
		else
		{
			$this->room_id = $this->getRoomId();
		}

		if (!empty($a_mode))
		{
			$this->mode = $a_mode;
			$this->setMode($a_mode);
		}
		else
		{
			$this->mode = $this->getMode();
		}
	}

	/**
	 * Executes the command given by ilCtrl.
	 */
	public function executeCommand()
	{
		$cmd = $this->ctrl->getCmd("renderBookingForm");
		$this->$cmd();
	}

	/**
	 * Renders the booking form as HTML.
	 */
	public function renderBookingForm()
	{
		$booking_form = $this->createForm();
		$this->tpl->setContent($booking_form->getHTML());
	}

	/**
	 * Creates a booking form.
	 *
	 * @return ilform
	 */
	private function createForm()
	{
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->getFormTitle());
		if ($this->permission->checkPrivilege(PRIVC::ADD_OWN_BOOKINGS))
		{
			if ($this->mode == 'show')
			{
				$form->addCommandButton(self::EDIT_BOOK_CMD, $this->lng->txt("rep_robj_xrs_booking_edit"));
			}
			elseif ($this->mode == 'edit')
			{
				$form->addCommandButton(self::SAVE_BOOK_CMD, $this->lng->txt("rep_robj_xrs_booking_save"));
				$form->addCommandButton(self::CANCEL_EDIT_CMD, $this->lng->txt("rep_robj_xrs_booking_edit_cancel"));
			}
		}
		$form_items = $this->createAndSetFormItems();
		foreach ($form_items as $item)
		{
			$form->addItem($item);
		}
		return $form;
	}

	/**
	 * Put together the titel, dependent on the mode of the instance.
	 *
	 * @return string
	 */
	private function getFormTitle()
	{
		$title = $title . $this->lng->txt('rep_robj_xrs_booking_in_show');
		$title = $title . ': ' . $this->lng->txt('rep_robj_xrs_room');
		$title = $title . " " . $this->getRoomFromId();

		if ($this->mode == 'edit')
		{
			$title = $title . " " . $this->lng->txt('rep_robj_xrs_booking_in_edit');
		}

		return $title;
	}

	/**
	 * Generate the room name.
	 *
	 * @return string
	 */
	private function getRoomFromId()
	{
		$rooms = new ilRoomSharingRooms($this->pool_id, new ilRoomsharingDatabase($this->pool_id));
		return $rooms->getRoomName($this->room_id);
	}

	/**
	 * Main function for the gui creation and also set the values.
	 *
	 * @return type
	 */
	private function createAndSetFormItems()
	{
		$booking = new ilRoomSharingBook($this->pool_id);
		$bookingData = $booking->getBookingData($this->booking_id);
		$this->date_from = $bookingData['booking_values']['date_from'];
		$this->date_to = $bookingData['booking_values']['date_to'];
		$this->old_booking_values = $bookingData['booking_values'];
		$this->old_attr_values = $bookingData['attr_values'];
		$this->old_participants = $bookingData['participants'];
		$form_items = array();
		$form_items[] = $this->createAndSetSubjectTextInput($bookingData['booking_values']);
		$form_items[] = $this->createAndSetCommentTextInput($bookingData['booking_values']);
		$booking_attributes = $this->createAndSetBookingAttributeTextInputs($bookingData['attr_values']);
		$form_items = array_merge($form_items, $booking_attributes);
		$form_items[] = $this->createAndSetTimeRangeInput();
		$form_items[] = $this->createAndSetPublicBookingCheckBox($bookingData['booking_values']);
		$form_items[] = $this->createAndSetUserAgreementCheckBoxIfPossible();
		$form_items[] = $this->createRoomIdHiddenInputField();
		$form_items[] = $this->createParticipantsSection();
		$form_items[] = $this->createAndSetParticipantsMultiTextInput($bookingData['participants']);

		return array_filter($form_items);
	}

	/**
	 * Generate and set the comment input field.
	 *
	 * @param array $a_bookingData
	 * @return \ilTextInputGUI
	 */
	private function createAndSetCommentTextInput($a_bookingData)
	{
		$comment = new ilTextInputGUI($this->lng->txt("comment"), "comment");
		$comment->setRequired(false);
		$comment->setSize(40);
		$comment->setMaxLength(4000);
		$comment->setValue($a_bookingData['bookingcomment']);
		if ($this->mode == 'show')
		{
			$comment->setDisabled(true);
		}

		return $comment;
	}

	/**
	 * Generate and set the subject input field.
	 *
	 * @param array $a_bookingData
	 * @return \ilTextInputGUI
	 */
	private function createAndSetSubjectTextInput($a_bookingData)
	{
		$subject = new ilTextInputGUI($this->lng->txt("subject"), "subject");
		$subject->setRequired(true);
		$subject->setSize(40);
		$subject->setMaxLength(120);
		$subject->setValue($a_bookingData['subject']);
		if ($this->mode == 'show')
		{
			$subject->setDisabled(true);
		}

		return $subject;
	}

	/**
	 * Generate and set the attribute input fields.
	 *
	 * @param array $a_bookingData
	 * @return array
	 */
	private function createAndSetBookingAttributeTextInputs($a_bookingData)
	{
		$text_input_items = array();
		$booking_attributes = $this->getBookingAttributes();
		foreach ($booking_attributes as $attr)
		{
			$text_input_items[] = $this->createSingleBookingAttributeTextInput($attr, $a_bookingData);
		}
		return $text_input_items;
	}

	/**
	 * Get all booking attributes of the pool.
	 *
	 * @return array
	 */
	private function getBookingAttributes()
	{
		$ilBookings = new ilRoomSharingBookings($this->pool_id);
		return $ilBookings->getAdditionalBookingInfos();
	}

	/**
	 * Generate and set each booking attribute input fields.
	 *
	 * @param array $a_attribute
	 * @param array $a_bookingdata
	 * @return \ilTextInputGUI
	 */
	private function createSingleBookingAttributeTextInput($a_attribute, $a_bookingdata)
	{
		$attr_id = $a_attribute['id'];
		$attr_txt = $a_attribute['txt'];
		$attr = new ilTextInputGUI($attr_txt, $attr_id);
		$attr->setSize(40);
		$attr->setMaxLength(120);
		$attr->setValue($this->getAttributValue($a_bookingdata, $attr_id));
		if ($this->mode == 'show')
		{
			$attr->setDisabled(true);
		}

		return $attr;
	}

	/**
	 * Perused the given array and search for the given attribute id and return the value.
	 * If no value for the attribute exist, this return a empty string.
	 *
	 * @param Array $a_bookingdata
	 * @param Strin $attr_id
	 * @return String Value
	 */
	private function getAttributValue($a_bookingdata, $attr_id)
	{
		$value = '';
		for ($i = 0; $i < sizeof($a_bookingdata, 0); $i++)
		{
			$data = $a_bookingdata[$i];
			if ($data['attr_id'] == $attr_id)
			{
				$value = $data[value];
			}
		}
		return $value;
	}

	/**
	 * Generate and organize the time range input fields.
	 *
	 * @return \ilCombinationInputGUI
	 */
	private function createAndSetTimeRangeInput()
	{
		$time_range = new ilCombinationInputGUI($this->lng->txt("assessment_log_datetime"), "time_range");

		$from_id = "from";
		$to_id = "to";
		$from_transl = $this->lng->txt($from_id);
		$to_transl = $this->lng->txt($to_id);

		$time_input_from = $this->createAndSetDateTimeInput($from_transl, $from_id, $this->date_from);
		$time_input_to = $this->createAndSetDateTimeInput($to_transl, $to_id, $this->date_to);

		$time_range->addCombinationItem($from_id, $time_input_from, $from_transl);
		$time_range->addCombinationItem($to_id, $time_input_to, $to_transl);

		return $time_range;
	}

	/**
	 * Generate and set date time input field
	 *
	 * @param string $a_title
	 * @param string $a_postvar
	 * @param string $a_date in the format YYYY-MM-DD HH:MM:SS
	 * @return \ilDateTimeInputGUI
	 */
	private function createAndSetDateTimeInput($a_title, $a_postvar, $a_date)
	{
		$date_time_input = new ilDateTimeInputGUI($a_title, $a_postvar);
		if (isset($a_date))
		{
			$date_time_input->setDate(new ilDateTime($a_date, IL_CAL_DATETIME));
		}
		$date_time_input->setMinuteStepSize(5);
		$date_time_input->setShowTime(true);
		if ($this->mode == 'show')
		{
			$date_time_input->setDisabled(true);
		}

		return $date_time_input;
	}

	/**
	 * Generate and set the public booking checkbox
	 *
	 * @param array $a_bookingData
	 * @return \ilCheckboxInputGUI
	 */
	private function createAndSetPublicBookingCheckBox($a_bookingData)
	{
		$checkbox_public = new ilCheckboxInputGUI($this->lng->txt("rep_robj_xrs_room_public_booking"), "book_public");
		$checkbox_public->setChecked($a_bookingData['public_booking'] == 1 ? true : false);
		if ($this->mode == 'show')
		{
			$checkbox_public->setDisabled(true);
		}

		return $checkbox_public;
	}

	/**
	 * Generate and always set the user agreement checkbox if available.
	 *
	 * @return \ilCheckboxInputGUI
	 */
	private function createAndSetUserAgreementCheckBoxIfPossible()
	{
		if ($this->isRoomAgreementIdAvailable())
		{
			return $this->createUserAgreementCheckBox();
		}
	}

	/**
	 * Check and get the room agreement id if available.
	 *
	 * @return type
	 */
	private function isRoomAgreementIdAvailable()
	{
		$agreement_id = $this->book->getRoomAgreementFileId();

		return !empty($agreement_id);
	}

	/**
	 * Generate and always set the user agreement checkbox.
	 *
	 * @return \ilCheckboxInputGUI
	 */
	private function createUserAgreementCheckBox()
	{
		$agreement_id = $this->book->getRoomAgreementFileId();
		$link = $this->getFileLinkForUserAgreementId($agreement_id);
		$title = $this->lng->txt("rep_robj_xrs_rooms_user_agreement_accept");
		$checkbox_agreement = new ilCheckboxInputGUI($title, "accept_room_rules");
		$checkbox_agreement->setRequired(true);
		$checkbox_agreement->setOptionTitle($link);
		$checkbox_agreement->setChecked(true);
		$checkbox_agreement->setValue(1);
		$checkbox_agreement->setDisabled(true);

		return $checkbox_agreement;
	}

	/**
	 * Generate the link to the room agreement.
	 *
	 * @param integer $a_file_id
	 * @return string
	 */
	private function getFileLinkForUserAgreementId($a_file_id)
	{
		$agreement_file = new ilObjMediaObject($a_file_id);
		$media = $agreement_file->getMediaItem("Standard");
		$source = $agreement_file->getDataDirectory() . "/" . $media->getLocation();

		$link = "<p> <a target=\"_blank\" href=\"" . $source . "\">" .
			$this->lng->txt('rep_robj_xrs_current_rooms_user_agreement') . "</a></p>";
		return $link;
	}

	private function createRoomIdHiddenInputField()
	{
		$hidden_room_id = new ilHiddenInputGUI("room_id");
		$hidden_room_id->setValue($this->room_id);
		$hidden_room_id->setRequired(true);

		return $hidden_room_id;
	}

	private function createAndSetParticipantsMultiTextInput($a_bookingData)
	{
		global $rssPermission;
		$participants_input = new ilTextInputGUI($this->lng->txt("rep_robj_xrs_participants_list"), "participants");
		$participants_input->setMulti(true);
		$ajax_datasource = $this->ctrl->getLinkTarget($this, 'doUserAutoComplete', '', true);
		$participants_input->setDataSource($ajax_datasource);
		$participants_input->setInfo($this->getMaxRoomAllocationInfo());
		if (!empty($a_bookingData[0]))
		{
			$participants_input->setValue($a_bookingData[0]);
		}
		$participants_input->setMultiValues($a_bookingData);

		if ($this->mode == 'show' || !$rssPermission->
				checkPrivilege(ilRoomSharingPrivilegesConstants::ADD_PARTICIPANTS))
		{
			$participants_input->setDisabled(true);
		}

		return $participants_input;
	}

	/**
	 * Method that realizes the auto-completion for the participants list.
	 */
	public function doUserAutoComplete()
	{
		$search_fields = array("login", "firstname", "lastname", "email");
		$result_field = "login";

		$auto = new ilUserAutoComplete();
		$auto->setSearchFields($search_fields);
		$auto->setResultField($result_field);
		$auto->enableFieldSearchableCheck(true);

		echo $auto->getList($_REQUEST['term']);
		exit();
	}

	private function getMaxRoomAllocationInfo()
	{
		$room = new ilRoomSharingRoom($this->pool_id, $this->room_id);
		$max_alloc = $this->lng->txt("rep_robj_xrs_at_most") . ": " . ($room->getMaxAlloc() - self::NUM_PERSON_RESPONSIBLE);

		return $max_alloc;
	}

	private function createParticipantsSection()
	{
		$participant_section = new ilFormSectionHeaderGUI();
		$participant_section->setTitle($this->lng->txt("rep_robj_xrs_participants"));

		return $participant_section;
	}

	private function isFormValid($a_form)
	{
		return $a_form->checkInput();
	}

	private function evaluateFormEntries($a_form)
	{
		$common_entries = $this->fetchCommonFormEntries($a_form);
		$attribute_entries = $this->fetchAttributeFormEntries($a_form);
		$participant_entries = $a_form->getInput('participants');

		$this->saveFormEntries($a_form, $common_entries, $attribute_entries, $participant_entries);
	}

	private function fetchCommonFormEntries($a_form)
	{
		$common_entries = array();
		$common_entries['subject'] = $a_form->getInput('subject');
		$common_entries['from'] = $a_form->getInput('from');
		$common_entries['to'] = $a_form->getInput('to');
		$common_entries['book_public'] = $a_form->getInput('book_public');
		$common_entries['accept_room_rules'] = $a_form->getInput('accept_room_rules');
		$common_entries['room'] = $a_form->getInput('room_id');
		$common_entries['comment'] = $a_form->getInput('comment');

		return $common_entries;
	}

	private function fetchAttributeFormEntries($a_form)
	{
		$attribute_entries = array();
		$booking_attributes = $this->getBookingAttributes();
		foreach ($booking_attributes as $attr)
		{
			$attribute_entries[$attr['id']] = $a_form->getInput($attr['id']);
		}

		return $attribute_entries;
	}

	private function saveFormEntries($a_form, $a_common_entries, $a_attribute_entries, $a_participant_entries)
	{
		try
		{
			$this->updateBooking($a_common_entries, $a_attribute_entries, $a_participant_entries);
		}
		catch (ilRoomSharingBookException $ex)
		{
			$this->handleException($a_form, $ex);
		}
	}

	private function updateBooking($a_common_entries, $a_attribute_entries, $a_participant_entries)
	{
		//adds current calendar-id to booking information
		$a_common_entries['cal_id'] = $this->parent_obj->getCalendarId();
		$this->book->updateEditBooking($this->booking_id, $this->old_booking_values, $this->old_attr_values, $this->old_participants, $a_common_entries, $a_attribute_entries, $a_participant_entries);
		$this->cleanUpAfterSuccessfulSave();
	}

	private function handleException($a_form, $a_exception)
	{
		ilUtil::sendFailure($a_exception->getMessage(), true);
		$this->resetInvalidForm($a_form);
	}

	private function cleanUpAfterSuccessfulSave()
	{
		global $ilTabs;

		$ilTabs->clearTargets();
		$this->parent_obj->setTabs();
		$this->ctrl->setCmd("showBooking");
		$this->parent_obj->performCommand("");
		ilUtil::sendSuccess($this->lng->txt('rep_robj_xrs_booking_success_edit'), true);
	}

	private function handleInvalidForm($a_form)
	{
		ilUtil::sendFailure($this->lng->txt('rep_robj_xrs_missing_required_entries'), true);
		$this->resetInvalidForm($a_form);
	}

	private function resetInvalidForm($a_form)
	{
		try
		{
			$a_form->setValuesByPost();
		}
		catch (Exception $ex)
		{
			//Catch other exceptions from the reset of the form
		}
		$this->tpl->setContent($a_form->getHTML());
	}

	/**
	 * Returns roomsharing pool id.
	 *
	 * @return integer Pool-ID
	 */
	public function getPoolId()
	{
		return $this->pool_id;
	}

	/**
	 * Sets roomsharing pool id.
	 *
	 * @param integer Pool-ID
	 */
	public function setPoolId($a_pool_id)
	{
		$this->pool_id = $a_pool_id;
	}

	public function showBooking()
	{
		$this->renderBookingForm('show');
	}

	private function editBooking()
	{
		$this->renderBookingForm('edit');
	}

	private function saveEditBooking()
	{
		$form = $this->createForm();
		if ($this->isFormValid($form))
		{
			$this->evaluateFormEntries($form);
		}
		else
		{
			$this->handleInvalidForm($form);
		}
	}

	private function cancelEdit()
	{
		$this->renderBookingForm('show');
	}

	/**
	 * Returns the booking id which was saved in the session.
	 *
	 * @return integer
	 */
	private function getBookingId()
	{
		return unserialize($_SESSION[self::SESSION_BOOKING_ID]);
	}

	/**
	 * Saves the booking id in the session.
	 *
	 * @param integer  $a_bookingId
	 */
	private function setBookingId($a_bookingId)
	{
		$_SESSION[self::SESSION_BOOKING_ID] = serialize($a_bookingId);
	}

	/**
	 * Returns the room id which was saved in the session.
	 *
	 * @return integer
	 */
	private function getRoomId()
	{
		return unserialize($_SESSION[self::SESSION_ROOM_ID]);
	}

	/**
	 * Saves the room id in the session.
	 *
	 * @param integer $a_roomId
	 */
	private function setRoomId($a_roomId)
	{
		$_SESSION[self::SESSION_ROOM_ID] = serialize($a_roomId);
	}

	/**
	 * Returns the mode which was saved in the session.
	 *
	 * @return string
	 */
	private function getMode()
	{
		return unserialize($_SESSION[self::SESSION_MODE]);
	}

	/**
	 * Saves the mode in the session.
	 *
	 * @param string  $a_mode
	 */
	private function setMode($a_mode)
	{
		$_SESSION[self::SESSION_MODE] = serialize($a_mode);
	}

}
?>
