<?php

require_once("./Services/Table/classes/class.ilTable2GUI.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/rooms/class.ilRoomSharingRooms.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingNumberInputGUI.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingTextInputGUI.php");
require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
require_once("./Services/Form/classes/class.ilCombinationInputGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingPermissionUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivilegesConstants.php");
require_once ('./Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingSequenceBookingUtils.php');

use ilRoomSharingSequenceBookingUtils as seqUtils;
use ilRoomSharingPrivilegesConstants as PRIVC;

/**
 * Class ilRoomSharingRoomsTableGUI. Table with rooms.
 *
 * @author Alexander Keller <a.k3ll3r@gmail.com>
 * @author Robert Heimsoth <rheimsoth@stud.hs-bremen.de>
 * @version $Id$
 *
 * @property ilRoomSharingPermissionUtils $permission
 */
class ilRoomSharingRoomsTableGUI extends ilTable2GUI
{
	private $rooms;
	private $db;
	private $message = '';
	private $messageNeeded = false;
	private $messagePlural = false;
	private $messageLowerZero = '';
	private $messageNeededLowerZero = false;
	private $messagePluralLowerZero = false;
	private $permission;

	/**
	 * Constructor for the class ilRoomSharingRoomsTableGUI
	 *
	 * @param unknown $a_parent_obj
	 * @param unknown $a_parent_cmd
	 * @param unknown $a_ref_id
	 */
	public function __construct($a_parent_obj, $a_parent_cmd, $a_ref_id)
	{
		global $ilCtrl, $lng, $rssPermission;

		$this->parent_obj = $a_parent_obj;
		$this->parent_cmd = $a_parent_cmd;
		$this->lng = $lng;
		$this->ctrl = $ilCtrl;
		$this->permission = $rssPermission;
		$this->ref_id = $a_ref_id;
		// in order to keep filter settings, table ordering etc. set an ID
		// this is better to be unset for debug sessions
		// $this->setId("roomtable");
		parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->db = new ilRoomsharingDatabase($a_parent_obj->getPoolID());
		$this->rooms = new ilRoomSharingRooms($a_parent_obj->getPoolID(), $this->db);
		$this->lng->loadLanguageModule("form");

		$this->setTitle($this->lng->txt("rep_robj_xrs_rooms"));
		$this->setLimit(10); // datasets that are displayed per page
		$this->setFormAction($ilCtrl->getFormAction($a_parent_obj, $a_parent_cmd));
		$this->setEnableHeader(true);
		$this->_addColumns(); // add columns and column headings
		$this->setEnableHeader(true);
		$this->setRowTemplate("tpl.room_rooms_row.html",
			"Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing");
	}

	/**
	 * Gets all the items that need to populated into the table.
	 *
	 * @param array $filter
	 */
	public function getItems(array $filter)
	{
		$data = $this->getFilteredData($filter);

		$old_name = $filter["room_name"];
		$new_name = preg_replace('/\D/', '', filter_var($filter["room_name"], FILTER_SANITIZE_NUMBER_INT));

		if (count($data) == 0 && ($new_name || $new_name === "0") && ($old_name || $old_name === "0") && $old_name
			!== $new_name)
		{
			$filter["room_name"] = $new_name;

			$data = $this->getFilteredData($filter);

			$message = $this->lng->txt('rep_robj_xrs_no_match_for') . " $old_name " .
				$this->lng->txt('rep_robj_xrs_found') . ". " .
				$this->lng->txt('rep_robj_xrs_results_for') . " $new_name.";

			ilUtil::sendInfo($message);
		}
		$this->setMaxCount(count($data));
		$this->setData($data);
	}

	//Eigene Implementation für das Prüfen der Raumverfügbarkeit
	//für jedes Datum, welches generiert wird
	public function getFilteredData(array $filter)
	{
		$freq = unserialize($filter['recurrence']["frequence"]);
		switch ($freq)
		{
			case "DAILY":
				$repeat_amount = unserialize($filter['recurrence']["repeat_amount"]);
				$repeat_type = unserialize($filter['recurrence']["repeat_type"]);
				$repeat_until = unserialize($filter['recurrence']["repeat_until"]);
				$filter['datetimes'] = seqUtils::getDailyFilteredData($filter['date'], $repeat_type,
						$repeat_amount, $repeat_until, $filter['time_from'], $filter['time_to']);
				break;
			case "WEEKLY":
				$repeat_amount = unserialize($filter['recurrence']["repeat_amount"]);
				$repeat_type = unserialize($filter['recurrence']["repeat_type"]);
				$repeat_until = unserialize($filter['recurrence']["repeat_until"]);
				$weekdays = unserialize($filter ["recurrence"]["weekdays"]);
				$filter['datetimes'] = seqUtils::getWeeklyFilteredData($filter['date'], $repeat_type,
						$repeat_amount, $repeat_until, $weekdays, $filter['time_from'], $filter['time_to']);
				break;
			case "MONTHLY":
				$repeat_amount = unserialize($filter['recurrence']["repeat_amount"]);
				$repeat_type = unserialize($filter['recurrence']["repeat_type"]);
				$repeat_until = unserialize($filter['recurrence']["repeat_until"]);
				$start_type = unserialize($filter['recurrence']["start_type"]);
				if ($start_type == "weekday")
				{
					$w1 = unserialize($filter['recurrence']["weekday_1"]);
					$w2 = unserialize($filter['recurrence']["weekday_2"]);
					$filter['datetimes'] = seqUtils::getMonthlyFilteredData($filter['date'], $repeat_type,
							$repeat_amount, $repeat_until, $start_type, null, $w1, $w2, $filter['time_from'],
							$filter['time_to']);
				}
				elseif ($start_type == "monthday")
				{
					$md = unserialize($filter['recurrence']["monthday"]);
					$filter['datetimes'] = seqUtils::getMonthlyFilteredData($filter['date'], $repeat_type,
							$repeat_amount, $repeat_until, $start_type, $md, null, null, $filter['time_from'],
							$filter['time_to']);
				}
				break;
			default:
				$filter['datetimes']['from'] = array();
				$filter['datetimes']['from'][] = $filter['date'] . " " . $filter['time_from'];
				$filter['datetimes']['to'] = array();
				$filter['datetimes']['to'][] = $filter['date'] . " " . $filter['time_to'];
				break;
		}

		$data = $this->rooms->getList($filter);
		return $data;
	}

	/**
	 * Adds columns and column headings to the table.
	 */
	private function _addColumns()
	{
		$this->addColumn($this->lng->txt("rep_robj_xrs_room"), "room");
		$this->addColumn($this->lng->txt("rep_robj_xrs_seats"));
		$this->addColumn($this->lng->txt("rep_robj_xrs_room_attributes")); // not sortable
		$this->addColumn("", "action");
	}

	/**
	 * Fills an entire table row with the given set.
	 * The corresponding array has the following shape:
	 *
	 * @see ilTable2GUI::fillRow()
	 * @param $a_set data set for that row
	 */
	public function fillRow($a_set)
	{
		global $ilAccess;

		// ### Room ###
		$this->tpl->setVariable('TXT_ROOM', $a_set ['room']);
		$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'room_id', $a_set ['room_id']);
		if ($this->permission->checkPrivilege(PRIVC::ACCESS_ROOMS))
		{
			$this->tpl->setVariable('HREF_ROOM',
				$this->ctrl->getLinkTargetByClass('ilobjroomsharinggui', 'showRoom'));
		}
		$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'room_id', '');

		// ### Seats ###
		$this->tpl->setVariable('TXT_SEATS', $a_set ['seats']);

		// ### Room Attributes ###
		$attribute_keys = array_keys($a_set ['attributes']);
		$attribute_count = count($attribute_keys);
		for ($i = 0; $i < $attribute_count; ++$i)
		{
			$this->tpl->setCurrentBlock('attributes');
			$attribute = $attribute_keys [$i];

			// make sure that the last room attribute has no break at the end
			if ($i < $attribute_count - 1)
			{
				$this->tpl->setVariable('TXT_SEPARATOR', '<br>');
			}
			$this->tpl->setVariable('TXT_AMOUNT', $a_set ['attributes'] [$attribute]);
			$this->tpl->setVariable('TXT_ATTRIBUTE', $attribute);
			$this->tpl->parseCurrentBlock();
		}

		// actions
		$this->tpl->setCurrentBlock("actions");
		if ($this->permission->checkPrivilege(PRIVC::ADD_OWN_BOOKINGS))
		{
			$this->tpl->setVariable('LINK_ACTION_TXT', $this->lng->txt('rep_robj_xrs_room_book'));
			$this->tpl->setVariable('LINK_ACTION_SEPARATOR', '<br>');
		}
		$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'room', $a_set ['room']);
		$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'room_id', $a_set ['room_id']);
		$_SESSION['last_cmd'] = $this->parent_cmd;

		// only display a booking form if a search was initialized beforehand
		if ($this->
			parent_cmd === "showSearchResults")
		{
			// if this class is used to display search results, the input made
			// must be transported to the book form
			$date = unserialize($_SESSION ["form_searchform"] ["date"]);
			$time_from = unserialize($_SESSION ["form_searchform"] ["time_from"]);
			$time_to = unserialize($_SESSION ["form_searchform"] ["time_to"]);

			$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'date', $date ['date']);
			$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'time_from', $time_from ['time']);
			$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'time_to', $time_to ['time']);
			if ($this->permission->checkPrivilege(PRIVC::ADD_OWN_BOOKINGS))
			{
				$this->tpl->setVariable('LINK_ACTION',
					$this->ctrl->getLinkTargetByClass('ilobjroomsharinggui', 'book'));
			}
			// free those parameters, since we don't need them anymore
			$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'date', "");
			$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'time_from', "");
			$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'time_to', "");
		}
		else
		{
			// the user is linked to the search form if he is trying to book
			// a room when the normal room list is displayed
			if ($this->permission->checkPrivilege(PRIVC::ACCESS_SEARCH))
			{
				$this->tpl->setVariable('LINK_ACTION',
					$this->ctrl->getLinkTargetByClass('ilobjroomsharinggui', 'showSearch'));
			}
		}

		// unset the parameters; just in case
		$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'room_id', "");

		// allow  to edit and delete rooms, but only if the room list and not the
		// search results are displayed
		if ($this->permission->checkPrivilege(PRIVC::ACCESS_ROOMS) && $this->parent_cmd === "showRooms")
		{
			$this->tpl->parseCurrentBlock();
			$this->tpl->setVariable('LINK_ACTION',
				$this->ctrl->getLinkTarget($this->parent_obj, $this->parent_cmd));
			$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'room_id', $a_set ['room_id']);
			if ($this->permission->checkPrivilege(PRIVC::EDIT_ROOMS))
			{
				$this->tpl->setVariable('LINK_ACTION',
					$this->ctrl->getLinkTargetByClass('ilobjroomsharinggui', 'editRoom'));
				$this->tpl->setVariable('LINK_ACTION_TXT', $this->lng->txt('edit'));
				$this->tpl->setVariable('LINK_ACTION_SEPARATOR', '<br>');
				$this->tpl->parseCurrentBlock();
			}
			if ($this->permission->checkPrivilege(PRIVC::DELETE_ROOMS))
			{
				$this->tpl->setVariable('LINK_ACTION',
					$this->ctrl->getLinkTargetByClass('ilobjroomsharinggui', 'confirmDeleteRoom'));
				$this->tpl->setVariable('LINK_ACTION_TXT', $this->lng->txt('delete'));
				$this->tpl->parseCurrentBlock();
			}
		}
		$this->tpl->parseCurrentBlock();
	}

	/**
	 * Build a filter that can used for database-queries.
	 *
	 * @return array the filter
	 */
	public function getCurrentFilter()
	{
		$filter = array();
		// make sure that "0"-strings are not ignored
		if ($this->filter ["room"] ["room_name"] || $this->filter ["room"] ["room_name"] === "0")
		{
			$filter ["room_name"] = $this->filter ["room"] ["room_name"];
		}
		if ($this->filter ["seats"] ["room_seats"] || $this->filter ["seats"] ["room_seats"] === 0.0)
		{
			$filter ["room_seats"] = $this->filter ["seats"] ["room_seats"];
		}

		if ($this->filter ["attributes"])
		{
			foreach ($this->filter ["attributes"] as $key => $value)
			{
				if ($value ["amount"])
				{
					$filter ["attributes"] [$key] = $value ["amount"];
				}
			}
		}

		return $filter;
	}

	/**
	 * Initialize a search filter for ilRoomSharingRoomsTableGUI.
	 */
	public function initFilter()
	{
		$this->message = '';
		$this->messageNeeded = false;
		$this->messagePlural = false;
		// Room
		$this->createRoomFormItem();
		// Seats
		$this->createSeatsFormItem();
		// Room Attributes
		$this->createRoomAttributeFormItem();
		// generate info Message if needed
		$this->generateMessageIfNeeded();
	}

	/**
	 * Creates a combination input item which allows you to type in a room name.
	 */
	protected function createRoomFormItem()
	{
		// Room Name
		$room_comb = new ilCombinationInputGUI($this->lng->txt("rep_robj_xrs_room"), "room");
		$room_name_input = new ilRoomSharingTextInputGUI("", "room_name");
		$room_name_input->setMaxLength(14);
		$room_name_input->setSize(14);
		$room_comb->addCombinationItem("room_name", $room_name_input,
			$this->lng->txt("rep_robj_xrs_room_name"));
		$this->addFilterItem($room_comb);
		$room_comb->readFromSession(); // get the value that was submitted
		$this->filter ["room"] = $room_comb->getValue();
	}

	/**
	 * Creates a combination input item consisting of a number input field for
	 * the desired seat amount.
	 */
	protected function createSeatsFormItem()
	{
		// Seats
		$seats_comb = new ilCombinationInputGUI($this->lng->txt("rep_robj_xrs_needed_seats"), "seats");
		$room_seats_input = new ilRoomSharingNumberInputGUI("", "room_seats");
		$room_seats_input->setMaxLength(8);
		$room_seats_input->setSize(8);
		$room_seats_input->setMinValue(0);
		$room_seats_input->setMaxValue($this->rooms->getMaxSeatCount());
		$seats_comb->addCombinationItem("room_seats", $room_seats_input,
			$this->lng->txt("rep_robj_xrs_amount"));
		$this->addFilterItem($seats_comb);
		$seats_comb->readFromSession(); // get the value that was submitted
		$this->filter ["seats"] = $seats_comb->getValue();

		$value = $_POST[$room_seats_input->getPostVar(
		)];
		if ($value !== "" && $value > $room_seats_input->getMaxValue())
		{
			$this->message = $this->message . $this->lng->txt("rep_robj_xrs_needed_seats");

			if (!$this->messagePlural && $this->messageNeeded)
			{
				$this->messagePlural = true;
			}
			$this->messageNeeded = true;
		}
		elseif ($value !== "" && $value < 0)
		{
			$this->messageLowerZero = $this->messageLowerZero . $this->lng->txt("rep_robj_xrs_needed_seats");
			if (!$this->messagePluralLowerZero && $this->messageNeededLowerZero)
			{
				$this->messagePluralLowerZero = true;
			}
			$this->messageNeededLowerZero = true;
		}
	}

	/**
	 * If room attributes are present, display some input fields for the desired
	 * amount of those attributes.
	 */
	protected function createRoomAttributeFormItem()
	{
		$room_attributes = $this->rooms->getAllAttributes();
		foreach ($room_attributes as $room_attribute)
		{
			// setup an ilCombinationInputGUI for the room attributes
			$room_attribute_comb = new ilCombinationInputGUI($room_attribute
				, "attribute_" . $room_attribute);
			$room_attribute_input = new ilRoomSharingNumberInputGUI("
			", "attribute_" . $room_attribute . "_amount");
			$room_attribute_input->setMaxLength(8);
			$room_attribute_input->setSize(8);
			$room_attribute_input->setMinValue(0);
			$max_count = $this->rooms->getMaxCountForAttribute($room_attribute);
			$max_count_num = isset($max_count) ? $max_count : 0;
			$room_attribute_input->setMaxValue($max_count_num);
			$room_attribute_comb->addCombinationItem("amount", $room_attribute_input,
				$this->lng->txt("rep_robj_xrs_amount"));

			$this->addFilterItem($room_attribute_comb);
			$room_attribute_comb->readFromSession();

			$this->filter ["attributes"] [$room_attribute] = $room_attribute_comb->getValue();

			$value = $_POST[$room_attribute_input->getPostVar()];
			if ($value !== "" && $value > $room_attribute_input->getMaxValue())
			{
				if ($this->message != '')
				{
					$this->message = $this->message . ', ' . $room_attribute;
				}
				else
				{
					$this->message = $room_attribute;
				}

				if (!$this->messagePlural && $this->messageNeeded)
				{
					$this->messagePlural = true;
				}
				$this->messageNeeded = true;
			}
			elseif ($value !== "" && $value < 0)
			{
				if ($this->messageLowerZero != '')
				{
					$this->messageLowerZero = $this->messageLowerZero . ', ' . $room_attribute;
				}
				else
				{
					$this->messageLowerZero = $room_attribute;
				}

				if (!$this->messagePluralLowerZero && $this->messageNeededLowerZero)
				{
					$this->messagePluralLowerZero = true;
				}
				$this->messageNeededLowerZero = true;
			}
		}
	}

	/**
	 * Generate and show a infomessage if the private variables $message and $messageNeeded are set.
	 * They are set if one input value is bigger then the maxvalue or smaller than zero
	 */
	private function generateMessageIfNeeded()
	{
		if ($this->messageNeeded)
		{
			if (!$this->messagePlural)
			{
				$msg1 = $this->lng->txt('rep_robj_xrs_singular_field_input_value_too_high_begin');
				$msg1 = $msg1 . ' "' . $this->message;
				$msg1 = $msg1 . '" ' . $this->lng->txt('rep_robj_xrs_singular_field_input_value_too_high_end');
			}
			else
			{
				$msg1 = $this->lng->txt('rep_robj_xrs_plural_field_input_value_too_high_begin');
				$msg1 = $msg1 . ' "' . $this->message;
				$msg1 = $msg1 . '" ' . $this->lng->txt('rep_robj_xrs_plural_field_input_value_too_high_end');
			}
		}
		if ($this->messageNeededLowerZero)
		{
			if (!$this->messagePluralLowerZero)
			{
				$msg2 = $this->lng->txt('rep_robj_xrs_singular_field_input_value_too_low_begin');
				$msg2 = $msg2 . ' "' . $this->messageLowerZero;
				$msg2 = $msg2 . '" ' . $this->lng->txt('rep_robj_xrs_singular_field_input_value_too_low_end');
			}
			else
			{
				$msg2 = $this->lng->txt('rep_robj_xrs_plural_field_input_value_too_low_begin');
				$msg2 = $msg2 . ' "' . $this->messageLowerZero;
				$msg2 = $msg2 . '" ' . $this->lng->txt('rep_robj_xrs_plural_field_input_value_too_low_end');
			}
		}
		if (!empty($msg1) || !empty($msg2))
		{
			if (empty($msg1))
			{
				ilUtil::sendInfo($msg2);
			}
			elseif (empty($msg2))
			{
				ilUtil::sendInfo($msg1);
			}
			else
			{
				ilUtil::sendInfo($msg1 . "<br>" . $msg2);
			}
		}
	}

}
