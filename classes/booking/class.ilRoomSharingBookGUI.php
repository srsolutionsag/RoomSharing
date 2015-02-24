<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/rooms/class.ilRoomSharingRooms.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/appointments/bookings/class.ilRoomSharingBookings.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/rooms/detail/class.ilRoomSharingRoom.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/booking/class.ilRoomSharingBook.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/exceptions/class.ilRoomSharingBookException.php");
require_once("Services/Form/classes/class.ilCombinationInputGUI.php");
require_once("Services/Form/classes/class.ilPropertyFormGUI.php");
require_once("Services/User/classes/class.ilUserAutoComplete.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");
require_once('Modules/Session/classes/class.ilEventRecurrence.php');
require_once('Services/Calendar/classes/Form/class.ilRecurrenceInputGUI.php');
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingPermissionUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivilegesConstants.php");

use ilRoomSharingPrivilegesConstants as PRIVC;

/**
 * Class ilRoomSharingBookGUI
 *
 * @author  Michael Dazjuk <mdazjuk@stud.hs-bremen.de>
 * @author  Robert Heimsoth <rheimsoth@stud.hs-bremen.de>
 * @author  Alexander Keller <a.k3ll3r@gmail.com>
 *
 * @version $Id$
 *
 * @property ilRoomSharingPermissionUtils $permission
 */
class ilRoomSharingBookGUI {

	private $parent_obj;
	private $pool_id;
	private $room_id;
	private $date_from;
	private $date_to;
	private $book;
	private $rec;
	private $permission;
	const NUM_PERSON_RESPONSIBLE = 1;
	const BOOK_CMD = "book";


	/**
	 * Constructur for ilRoomSharingBookGUI
	 *
	 * @param ilObjRoomSharingGUI $a_parent_obj
	 * @param string              $a_room_id
	 * @param string              $a_date_from
	 * @param string              $a_date_to
	 */
	public function __construct(ilObjRoomSharingGUI $a_parent_obj, $a_room_id = NULL, $a_date_from = "", $a_date_to = "") {
		global $ilCtrl, $lng, $tpl, $rssPermission;

		$this->permission = $rssPermission;
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		$this->tpl = $tpl;
		$this->parent_obj = $a_parent_obj;
		$this->pool_id = $a_parent_obj->getPoolId();
		$this->room_id = $a_room_id;
		$this->date_from = $a_date_from;
		$this->date_to = $a_date_to;

		$this->book = new ilRoomSharingBook($this->pool_id);
	}


	/**
	 * Executes the command given by ilCtrl.
	 */
	public function executeCommand() {
		$cmd = $this->ctrl->getCmd("renderBookingForm");
		$this->$cmd();
	}


	/**
	 *
	 * Renders the booking form as HTML.
	 */
	public function renderBookingForm() {
		$booking_form = $this->createForm();
		$this->tpl->setContent($booking_form->getHTML());
	}


	/**
	 * Creates a booking form.
	 *
	 * @return ilform
	 */
	private function createForm() {
		$form = new ilPropertyFormGUI();
		include_once('./Services/YUI/classes/class.ilYuiUtil.php');
		ilYuiUtil::initDomEvent();
		$form->setFormAction($this->ctrl->getFormAction($this));

		$form->setTitle($this->getFormTitle());
		$form->addCommandButton(self::BOOK_CMD, $this->lng->txt("rep_robj_xrs_room_book"));

		$form_items = $this->createFormItems();
		foreach ($form_items as $item) {
			$form->addItem($item);
		}

		return $form;
	}


	private function getFormTitle() {
		$title = $this->lng->txt('rep_robj_xrs_room_book') . ': ' . $this->lng->txt('rep_robj_xrs_room');
		$title = $title . " " . $this->getRoomFromId();

		return $title;
	}


	private function getRoomFromId() {
		$room_id = empty($this->room_id) ? $_POST['room_id'] : $this->room_id;
		$this->room_id = $room_id;
		$rooms = new ilRoomSharingRooms($this->pool_id, new ilRoomsharingDatabase($this->pool_id));

		return $rooms->getRoomName($room_id);
	}


	private function createFormItems() {
		$form_items = array();
		$form_items[] = $this->createSubjectTextInput();
		$form_items[] = $this->createCommentTextInput();
		$booking_attributes = $this->createBookingAttributeTextInputs();
		$form_items = array_merge($form_items, $booking_attributes);
		$form_items[] = $this->createTimeRangeInput();

		if ($this->permission->checkPrivilege(PRIVC::ADD_SEQUENCE_BOOKINGS)) {
			$form_items[] = $this->createRecurrenceGUI();
		}
		$form_items[] = $this->createPublicBookingCheckBox();
		$form_items[] = $this->createUserAgreementCheckBoxIfPossible();
		$form_items[] = $this->createRoomIdHiddenInputField();
		$form_items[] = $this->createParticipantsSection();
		$form_items[] = $this->createParticipantsMultiTextInput();

		return array_filter($form_items);
	}


	private function createCommentTextInput() {
		$comment = new ilTextInputGUI($this->lng->txt("comment"), "comment");
		$comment->setRequired(false);
		$comment->setSize(40);
		$comment->setMaxLength(4000);

		return $comment;
	}


	private function createSubjectTextInput() {
		$subject = new ilTextInputGUI($this->lng->txt("subject"), "subject");
		$subject->setRequired(true);
		$subject->setSize(40);
		$subject->setMaxLength(120);

		return $subject;
	}


	private function createBookingAttributeTextInputs() {
		$text_input_items = array();
		$booking_attributes = $this->getBookingAttributes();
		foreach ($booking_attributes as $attr) {
			$text_input_items[] = $this->createSingleBookingAttributeTextInput($attr);
		}

		return $text_input_items;
	}


	private function getBookingAttributes() {
		$ilBookings = new ilRoomSharingBookings($this->pool_id);

		return $ilBookings->getAdditionalBookingInfos();
	}


	private function createSingleBookingAttributeTextInput($a_attribute) {
		$attr = new ilTextInputGUI($a_attribute['txt'], $a_attribute['id']);
		$attr->setSize(40);
		$attr->setMaxLength(120);

		return $attr;
	}


	private function createTimeRangeInput() {
		$time_range = new ilCombinationInputGUI($this->lng->txt("assessment_log_datetime"), "time_range");

		$from_id = "from";
		$to_id = "to";
		$from_transl = $this->lng->txt($from_id);
		$to_transl = $this->lng->txt($to_id);
		$time_input_from = $this->createDateTimeInput($from_transl, $from_id, $this->date_from);
		$time_input_to = $this->createDateTimeInput($to_transl, $to_id, $this->date_to);

		$time_range->addCombinationItem($from_id, $time_input_from, $from_transl);
		$time_range->addCombinationItem($to_id, $time_input_to, $to_transl);

		return $time_range;
	}


	private function createRecurrenceGUI() {
		$this->getRecurrenceFromSession();
		$r = new ilRecurrenceInputGUI($this->lng->txt('cal_recurrences'), 'frequence');
		$subforms = array( IL_CAL_FREQ_DAILY, IL_CAL_FREQ_WEEKLY, IL_CAL_FREQ_MONTHLY ); //ohne jährlich
		$r->setEnabledSubForms($subforms);
		$r->allowUnlimitedRecurrences(false);
		$r->setRecurrence($this->rec);

		return $r;
	}


	/**
	 * Get recurrence
	 */
	private function getRecurrenceFromSession() {
		$this->rec = new ilCalendarRecurrence();
		$fre = unserialize($_SESSION ["form_searchform"] ["frequence"]);
		$this->rec->setFrequenceType($fre);
		switch ($fre) {
			case "NONE":
				break;
			case "DAILY":
				break;
			case "WEEKLY":
				$days = unserialize($_SESSION ["form_searchform"] ["weekdays"]);
				$d = array();
				if (is_array($days)) {
					foreach ($days as $day) {
						$d[] = $day;
					}
				}
				$this->rec->setBYDAY(implode(",", $d));
				break;
			case "MONTHLY":
				$start_type = unserialize($_SESSION ["form_searchform"] ["start_type"]);
				if ($start_type == "weekday") {
					$w1 = unserialize($_SESSION ["form_searchform"] ["weekday_1"]);
					$w2 = unserialize($_SESSION ["form_searchform"] ["weekday_2"]);
					if ($w2 == 8) {
						$this->rec->setBYSETPOS($w1);
						$this->rec->setBYDAY('MO,TU,WE,TH,FR');
					} elseif ($w2 == 9) {
						$this->rec->setBYMONTHDAY($w1);
					} else {
						$this->rec->setBYDAY($w1 . $w2);
					}
				} elseif ($start_type == "monthday") {
					$this->rec->setBYMONTHDAY(unserialize($_SESSION ["form_searchform"] ["monthday"]));
				}
				break;
			default:
				break;
		}
		$repeat_type = unserialize($_SESSION ["form_searchform"] ["repeat_type"]);
		$this->rec->setInterval(unserialize($_SESSION ["form_searchform"] ["repeat_amount"]));
		if ($repeat_type == "max_amount") {
			$this->rec->setFrequenceUntilCount(unserialize($_SESSION ["form_searchform"] ["repeat_until"]));
		} elseif ($repeat_type == "max_date") {
			$date = unserialize($_SESSION ["form_searchform"] ["repeat_until"]);
			//print_r($date);
			//var_dump($_SESSION);
			//echo $date['date']['m'];
			$date2 = date('Y-m-d H:i:s', mktime(0, 0, 0, $date['date']['m'], $date['date']['d'], $date['date']['y']));
			$this->rec->setFrequenceUntilDate(new ilDateTime($date2, IL_CAL_DATETIME));
		}
	}


	private function createDateTimeInput($a_title, $a_postvar, $a_date) {
		$date_time_input = new ilDateTimeInputGUI($a_title, $a_postvar);
		if (isset($a_date)) {
			$date_time_input->setDate(new ilDateTime($a_date, IL_CAL_DATETIME));
		}
		$date_time_input->setMinuteStepSize(5);
		$date_time_input->setShowTime(true);

		return $date_time_input;
	}


	private function createPublicBookingCheckBox() {
		$checkbox_public = new ilCheckboxInputGUI($this->lng->txt("rep_robj_xrs_room_public_booking"), "book_public");

		return $checkbox_public;
	}


	private function createUserAgreementCheckBoxIfPossible() {
		if ($this->isRoomAgreementIdAvailable()) {
			return $this->createUserAgreementCheckBox();
		}
	}


	private function isRoomAgreementIdAvailable() {
		$agreement_id = $this->book->getRoomAgreementFileId();

		return !empty($agreement_id);
	}


	private function createUserAgreementCheckBox() {
		$agreement_id = $this->book->getRoomAgreementFileId();
		$link = $this->getFileLinkForUserAgreementId($agreement_id);
		$title = $this->lng->txt("rep_robj_xrs_rooms_user_agreement_accept");
		$checkbox_agreement = new ilCheckboxInputGUI($title, "accept_room_rules");
		$checkbox_agreement->setRequired(true);
		$checkbox_agreement->setOptionTitle($link);

		return $checkbox_agreement;
	}


	private function getFileLinkForUserAgreementId($a_file_id) {
		$agreement_file = new ilObjMediaObject($a_file_id);
		$media = $agreement_file->getMediaItem("Standard");
		$source = $agreement_file->getDataDirectory() . "/" . $media->getLocation();

		$link = "<p> <a target = \"_blank\" href=\"" . $source . "\">" . $this->lng->txt('rep_robj_xrs_current_rooms_user_agreement') . "</a></p>";

		return $link;
	}


	private function createRoomIdHiddenInputField() {
		$hidden_room_id = new ilHiddenInputGUI("room_id");
		$hidden_room_id->setValue($this->room_id);
		$hidden_room_id->setRequired(true);

		return $hidden_room_id;
	}


	private function createParticipantsMultiTextInput() {
		$participants_input = new ilTextInputGUI($this->lng->txt("rep_robj_xrs_participants_list"), "participants");
		$participants_input->setMulti(true);
		$ajax_datasource = $this->ctrl->getLinkTarget($this, 'doUserAutoComplete', '', true);
		$participants_input->setDataSource($ajax_datasource);
		$participants_input->setInfo($this->getMaxRoomAllocationInfo());

		return $participants_input;
	}


	/**
	 * Method that realizes the auto-completion for the participants list.
	 */
	private function doUserAutoComplete() {
		$search_fields = array( "login", "firstname", "lastname", "email" );
		$result_field = "login";

		$auto = new ilUserAutoComplete();
		$auto->setSearchFields($search_fields);
		$auto->setResultField($result_field);
		$auto->enableFieldSearchableCheck(true);

		echo $auto->getList($_REQUEST['term']);
		exit();
	}


	private function getMaxRoomAllocationInfo() {
		$room = new ilRoomSharingRoom($this->pool_id, $this->room_id);
		$max_alloc = $this->lng->txt("rep_robj_xrs_at_most") . ": " . ($room->getMaxAlloc() - self::NUM_PERSON_RESPONSIBLE);

		return $max_alloc;
	}


	private function createParticipantsSection() {
		$participant_section = new ilFormSectionHeaderGUI();
		$participant_section->setTitle($this->lng->txt("rep_robj_xrs_participants"));

		return $participant_section;
	}


	/**
	 * Function to the validate and save the form data
	 *
	 * @global type $ilTabs
	 */
	private function book() {
		$form = $this->createForm();
		if ($this->isFormValid($form)) {
			$this->evaluateFormEntries($form);
		} else {
			$this->handleInvalidForm($form);
		}
	}


	private function isFormValid($a_form) {
		return $a_form->checkInput()
		&& (!$this->isRoomAgreementIdAvailable()
			|| $a_form->getInput('accept_room_rules') == 1);
	}


	private function evaluateFormEntries($a_form) {
		$common_entries = $this->fetchCommonFormEntries($a_form);
		$attribute_entries = $this->fetchAttributeFormEntries($a_form);
		$participant_entries = $a_form->getInput('participants');
		$recurrence_entries = $this->fetchRecurrenceFormEntries($a_form);

		$this->saveFormEntries($a_form, $common_entries, $attribute_entries, $participant_entries, $recurrence_entries);
	}


	private function fetchRecurrenceFormEntries($a_form) {
		$recurrence_entries = array();
		$recurrence_entries['frequence'] = $a_form->getInput('frequence');
		$this->writeSingleInputToSession("frequence", $recurrence_entries['frequence']);

		switch ($recurrence_entries['frequence']) {
			case 'DAILY':
				$recurrence_entries['repeat_amount'] = $a_form->getInput("count_DAILY", false);
				$this->writeSingleInputToSession("repeat_amount", $recurrence_entries['repeat_amount']);
				$recurrence_entries = $this->getUntilValue($a_form, $recurrence_entries);
				break;

			case 'WEEKLY':
				$recurrence_entries['repeat_amount'] = $a_form->getInput("count_WEEKLY", false);
				$this->writeSingleInputToSession("repeat_amount", $recurrence_entries['repeat_amount']);

				$recurrence_entries['weekdays'] = $a_form->getInput("byday_WEEKLY", false);
				$this->writeSingleInputToSession("weekdays", $recurrence_entries['weekdays']);
				$recurrence_entries = $this->getUntilValue($a_form, $recurrence_entries);
				break;
			case 'MONTHLY':
				$recurrence_entries['repeat_amount'] = $a_form->getInput("count_MONTHLY", false);
				$this->writeSingleInputToSession("repeat_amount", $recurrence_entries['repeat_amount']);
				$subtype = $a_form->getInput("subtype_MONTHLY", false);
				if ($subtype == 1) {
					$recurrence_entries['start_type'] = "weekday";
					$this->writeSingleInputToSession("start_type", "weekday");
					$recurrence_entries['weekday_1'] = $a_form->getInput("monthly_byday_num", false);
					$this->writeSingleInputToSession("weekday_1", $recurrence_entries['weekday_1']);
					$recurrence_entries['weekday_2'] = $a_form->getInput("monthly_byday_day", false);
					$this->writeSingleInputToSession("weekday_2", $recurrence_entries['weekday_2']);
				} elseif ($subtype == 2) {
					$recurrence_entries['start_type'] = "monthday";
					$this->writeSingleInputToSession("start_type", "monthday");
					$recurrence_entries['monthday'] = $a_form->getInput("monthly_bymonthday", false);
					$this->writeSingleInputToSession("monthday", $recurrence_entries['monthday']);
				}
				$recurrence_entries = $this->getUntilValue($a_form, $recurrence_entries);
				break;
			default:
				break;
		}

		return $recurrence_entries;
	}


	private function getUntilValue($a_form, $a_array) {
		$type = $a_form->getInput("until_type", false);
		if ($type == 2) {
			$a_array['repeat_type'] = "max_amount";
			$a_array['repeat_until'] = $a_form->getInput("count", false);
			$this->writeSingleInputToSession("repeat_type", "max_amount");
			$this->writeSingleInputToSession("repeat_until", $a_array['repeat_until']);
		} elseif ($type == 3) {
			$a_array['repeat_type'] = "max_date";
			$a_array['repeat_until'] = $a_form->getInput("until_end", false);
			$this->writeSingleInputToSession("repeat_type", "max_date");
			$this->writeSingleInputToSession("repeat_until", $a_array['repeat_until']);
		}

		return $a_array;
	}


	/**
	 * Writes a single input into SESSION.
	 *
	 * @param type $a_id    the id of the input
	 * @param type $a_value and the corresponding value
	 */
	public function writeSingleInputToSession($a_id, $a_value) {
		$_SESSION["form_searchform"][$a_id] = serialize($a_value);
	}


	private function fetchCommonFormEntries($a_form) {
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


	private function fetchAttributeFormEntries($a_form) {
		$attribute_entries = array();
		$booking_attributes = $this->getBookingAttributes();
		foreach ($booking_attributes as $attr) {
			$attribute_entries[$attr['id']] = $a_form->getInput($attr['id']);
		}

		return $attribute_entries;
	}


	private function saveFormEntries($a_form, $a_common_entries, $a_attribute_entries, $a_participant_entries, $a_recurrence_entries) {
		try {
			$this->addBooking($a_common_entries, $a_attribute_entries, $a_participant_entries, $a_recurrence_entries);
		} catch (ilRoomSharingBookException $ex) {
			$this->handleException($a_form, $ex);
		}
	}


	private function addBooking($a_common_entries, $a_attribute_entries, $a_participant_entries, $a_recurrence_entries) {
		//adds current calendar-id to booking information
		$a_common_entries['cal_id'] = $this->parent_obj->getCalendarId();
		$count_canceled_bookings = $this->book->addBooking($a_common_entries, $a_attribute_entries, $a_participant_entries, $a_recurrence_entries);
		if ($a_recurrence_entries['frequence'] != "DAILY" && $a_recurrence_entries['frequence'] != "WEEKLY"
			&& $a_recurrence_entries['frequence'] != "MONTHLY"
		) {
			$this->cleanUpAfterSuccessfulSave(false, $count_canceled_bookings);
		} else {
			$this->cleanUpAfterSuccessfulSave(true, $count_canceled_bookings);
		}
	}


	private function handleException($a_form, $a_exception) {
		ilUtil::sendFailure($a_exception->getMessage(), true);
		$this->resetInvalidForm($a_form);
	}


	private function cleanUpAfterSuccessfulSave($sequence = false, $a_count_canceled_bookings = 0) {
		global $ilTabs;

		$ilTabs->clearTargets();
		$this->parent_obj->setTabs();
		$this->ctrl->setCmd("render");
		$this->parent_obj->performCommand("");
		if ($sequence) {
			ilUtil::sendSuccess($this->lng->txt('rep_robj_xrs_seq_booking_added'), true);
		} else {
			ilUtil::sendSuccess($this->lng->txt('rep_robj_xrs_booking_added'), true);
		}
		if (ilRoomSharingNumericUtils::isPositiveNumber($a_count_canceled_bookings)) {
			ilUtil::sendInfo($a_count_canceled_bookings . " " . $this->lng->txt('rep_robj_xrs_booking_lower_priority_canceled'), true);
		}
	}


	private function handleInvalidForm($a_form) {
		ilUtil::sendFailure($this->lng->txt('rep_robj_xrs_missing_required_entries'), true);
		$this->resetInvalidForm($a_form);
	}


	private function resetInvalidForm($a_form) {
		try {
			$a_form->setValuesByPost();
		} catch (Exception $ex) {
			//Catch other exceptions from the reset of the form
		}
		$this->tpl->setContent($a_form->getHTML());
	}


	/**
	 * Returns roomsharing pool id.
	 *
	 * @return integer Pool-ID
	 */
	public function getPoolId() {
		return $this->pool_id;
	}


	/**
	 * Sets roomsharing pool id.
	 *
	 * @param integer Pool-ID
	 */
	public function setPoolId($a_pool_id) {
		$this->pool_id = $a_pool_id;
	}
}

?>
