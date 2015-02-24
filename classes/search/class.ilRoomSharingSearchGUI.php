<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/rooms/class.ilRoomSharingRooms.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/rooms/class.ilRoomSharingRoomsTableGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingTextInputGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingNumberInputGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingTimeInputGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/search/class.ilRoomSharingSearchFormGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingPermissionUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivilegesConstants.php");
require_once("Services/Calendar/classes/Form/class.ilRecurrenceInputGUI.php");
require_once("Services/Form/classes/class.ilCombinationInputGUI.php");
require_once("Services/Form/classes/class.ilPropertyFormGUI.php");
require_once("Services/YUI/classes/class.ilYuiUtil.php");

use ilRoomSharingPrivilegesConstants as PRIVC;

/**
 * Class ilRoomSharingSearchGUI
 *
 * @author Alexander Keller <a.k3ll3r@gmail.com>
 * @version $Id$
 *
 * @property ilRoomSharingPermissionUtils $permission
 */
class ilRoomSharingSearchGUI
{
	private $rooms;
	private $ref_id;
	private $pool_id;
	private $permission;
	private $tabs;
	private $search_form;

	/**
	 * Constructor for the class ilRoomSharingSearchGUI
	 *
	 * @param ilObjRoomSharingGUI $a_parent_obj the main GUI-object, which is needed for the pool id
	 */
	public function __construct(ilObjRoomSharingGUI $a_parent_obj)
	{
		global $ilCtrl, $lng, $tpl, $ilTabs, $rssPermission;

		$this->parent_obj = $a_parent_obj;
		$this->ref_id = $a_parent_obj->ref_id;
		$this->pool_id = $a_parent_obj->getPoolId();
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		$this->tpl = $tpl;
		$this->tabs = $ilTabs;
		$this->permission = $rssPermission;
		$this->rooms = new ilRoomSharingRooms($this->pool_id, new ilRoomsharingDatabase($this->pool_id));
	}

	/**
	 * Executes the command given by ilCtrl.
	 */
	public function executeCommand()
	{
		// the default command "showSearch" will be executed, if none is set
		$cmd = $this->ctrl->getCmd("showSearch");
		$this->$cmd();
	}

	/**
	 * Dispaly a search form if the required privileges are met.
	 */
	public function showSearch()
	{
		if ($this->permission->checkPrivilege(PRIVC::ACCESS_SEARCH))
		{
			$this->tabs->setTabActive("search");
			$search_form = $this->createForm();
			$this->tpl->setContent($search_form->getHTML());
		}
		else
		{
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_no_permission_for_action"));
			$this->ctrl->redirectByClass('ilinfoscreengui', 'showSummary', 'showSummary');
		}
	}

	/**
	 * Function which is called when the search results need to be applied.
	 */
	public function applySearch()
	{
		$search_form = $this->createForm();

		// continue only if the input data is correct
		if ($search_form->checkInput() && $this->checkTime($search_form))
		{
			$search_form->writeInputsToSession();
			$this->showSearchResults();
		}
		else // otherwise return to the form and display an error messages if needed
		{
			$search_form->setValuesByPost();
			$this->tpl->setContent($search_form->getHTML());
		}
	}

	/**
	 * Resets the inputs of search form.
	 */
	public function resetSearch()
	{
		$search_form = $this->createForm();
		$search_form->resetFormInputs();

		$this->showSearch();
	}

	/**
	 * Displays the results for the given input.
	 */
	public function showSearchResults()
	{
		$new_search_toolbar = $this->createNewSearchToolbar();
		$search_form = $this->createForm();

		$rooms_table = new ilRoomSharingRoomsTableGUI($this, "showSearchResults", $this->ref_id);
		$rooms_table->setTitle($this->lng->txt("search_results"));
		$rooms_table->getItems($this->getFormInput($search_form));

		$this->tpl->setContent($new_search_toolbar->getHTML() . $rooms_table->getHTML());
	}

	/**
	 * Creates a new search toolbar
	 * The toolbar is used for displaying a button, which allows the user to start a new search.
	 *
	 * @return \ilToolbarGUI
	 */
	private function createNewSearchToolbar()
	{
		$toolbar = new ilToolbarGUI();
		$target = $this->ctrl->getLinkTarget($this, "showSearch");
		$toolbar->addButton($this->lng->txt("search_new"), $target);

		return $toolbar;
	}

	/**
	 * Puts together an array which contains the search criterias for the search results. The
	 * standard procedure is to get those values from POST, but here it is actually coming from the
	 * SESSION.
	 *
	 * @return array returns the filter array
	 * @param ilRoomSharingSearchFormGUI the search form
	 */
	private function getFormInput($search_form)
	{
		$filtered_inputs = array();
		$room = $search_form->getInputFromSession("room_name");

		// "Room"
		// makes sure that "0"-strings are not ignored
		if ($room || $room === "0")
		{
			$filtered_inputs["room_name"] = $room;
		}

		// "Seats"
		$seats = $search_form->getInputFromSession("room_seats");
		if ($seats)
		{
			$filtered_inputs["room_seats"] = $seats;
		}

		// "Date" and "Time"
		$date = $search_form->getInputFromSession("date");
		$filtered_inputs["date"] = $date["date"];
		$time_from = $search_form->getInputFromSession("time_from");
		$filtered_inputs["time_from"] = $time_from["time"];
		$time_to = $search_form->getInputFromSession("time_to");
		$filtered_inputs["time_to"] = $time_to["time"];

		// "Room Attributes"
		$room_attributes = $this->rooms->getAllAttributes();
		foreach ($room_attributes as $room_attribute)
		{
			$attr_value = $search_form->getInputFromSession("attribute_" . $room_attribute .
				"_amount", false);

			if ($attr_value)
			{
				$filtered_inputs["attributes"][$room_attribute] = $attr_value;
			}
		}

		$filtered_inputs["recurrence"] = $_SESSION['form_searchform'];

		return $filtered_inputs;
	}

	/**
	 * Checks if the given date and time combination is valid for search
	 * and shows an error-massage if it's not.
	 *
	 * @return bool if form valid
	 * @param ilRoomSharingSearchFormGUI the search form
	 */
	private function checkTime($search_form)
	{
		$datenow = new DateTime(date('Y-m-d H:i:s'));
		$date = $search_form->getInput("date", false);
		$time_from = $search_form->getInput("time_from", false);
		$time_to = $search_form->getInput("time_to", false);
		$dateFrom = new DateTime($date['date'] . ' ' . $time_from['time']);
		$dateTo = new DateTime($date['date'] . ' ' . $time_to['time']);

		if ($dateFrom >= $dateTo)
		{
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_search_range_to_small"));
		}
		else if ($dateFrom < $datenow)
		{
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_search_time_in_past"));
		}
		else
		{
			return true;
		}
	}

	/**
	 * Creates and returns the search form.
	 *
	 * @return \ilRoomSharingSearchFormGUI the customized search form
	 */
	private function createForm()
	{
		$search_form = new ilRoomSharingSearchFormGUI();
		ilYuiUtil::initDomEvent();
		$search_form->setId("searchform");
		$search_form->setTitle($this->lng->txt("search"));
		$search_form->addCommandButton("applySearch", $this->lng->txt("rep_robj_xrs_search"));
		$search_form->addCommandButton("resetSearch", $this->lng->txt("reset"));
		$search_form->setFormAction($this->ctrl->getFormAction($this));

		$this->search_form = $search_form;

		$form_items = $this->createFormItems();
		foreach ($form_items as $item)
		{
			$search_form->addItem($item);
		}

		return $search_form;
	}

	/**
	 * Creates the form items
	 */
	private function createFormItems()
	{
		$form_items = array();

		$form_items[] = $this->createRoomFormItem();
		$form_items[] = $this->createSeatsFormItem();
		$form_items[] = $this->createDateFormItem();
		$form_items[] = $this->createTimeRangeFormItem();

		if ($this->permission->checkPrivilege(PRIVC::ADD_SEQUENCE_BOOKINGS))
		{
			$form_items[] = $this->createRecurrenceFormItem();
		}
		$room_attribute_items = $this->createRoomAttributeFormItems();
		$form_items = array_merge($form_items, $room_attribute_items);

		return array_filter($form_items);
	}

	/**
	 * Creates the input item for the room name input.
	 */
	private function createRoomFormItem()
	{
		$room_name_input = new ilRoomSharingTextInputGUI($this->lng->txt("rep_robj_xrs_room"), "room_name");
		$room_name_input->setParent($this->search_form);
		$room_name_input->setMaxLength(14);
		$room_name_input->setSize(14);

		$room_get_value = $_GET["room"];

		//if the user was redirected from the room list, set the value for the room accordingly
		if ($room_get_value)
		{
			$room_name_input->setValue($room_get_value);
		}
		else // otherwise use the input that has been set before
		{
			$room_name_input->readFromSession();
		}

		return $room_name_input;
	}

	/**
	 * Creates the combination input item containing a number input field for the desired seat amount.
	 */
	private function createSeatsFormItem()
	{
		$rooms_seats_text = $this->lng->txt("rep_robj_xrs_needed_seats") . " (" . $this->lng->txt("rep_robj_xrs_amount") . ")";
		$room_seats_input = new ilRoomSharingNumberInputGUI($rooms_seats_text, "room_seats");
		$room_seats_input->setParent($this->search_form);
		$room_seats_input->setMaxLength(8);
		$room_seats_input->setSize(8);
		$room_seats_input->setMinValue(0);
		$room_seats_input->setMaxValue($this->rooms->getMaxSeatCount());
		$room_seats_input->readFromSession();

		return $room_seats_input;
	}

	/**
	 * Creates the form item for the date input.
	 */
	private function createDateFormItem()
	{
		$date_comb = new ilCombinationInputGUI($this->lng->txt("date"), "date");
		$date = new ilDateTimeInputGUI("", "date");

		$date_given = unserialize($_SESSION ["form_searchform"] ["date"]);
		$hr_from = (date('H') + 1 < 10 ? "0" . (date('H') + 1) : (date('H') + 1));
		if (!empty($date_given['date']))
		{
			$date->setDate(new ilDate($date_given['date'], IL_CAL_DATE));
		}
		else if ($hr_from >= 24)
		{   //increase the day if 23:00 or later
			$date->setDate(new ilDate(date('Y-m-d', strtotime($date_given['date'] . ' + 1 days')),
				IL_CAL_DATE));
		}
		$date_comb->setRequired(true);
		$date_comb->addCombinationItem("date", $date, $this->lng->txt("rep_robj_xrs_on"));

		return $date_comb;
	}

	/**
	 * Creates the time range form item which consists of an ilCombinationGUI containing two
	 * customized ilDateTimeInputGUIs in the shape of an ilRoomSharingTimeInputGUI.
	 */
	private function createTimeRangeFormItem()
	{
		global $ilUser;

		$time_comb = new ilCombinationInputGUI($this->lng->txt("rep_robj_xrs_time_range"), "time");
		$time_from = new ilRoomSharingTimeInputGUI("", "time_from");
		$time_from->setShowTime(true);
		$time_from->setShowDate(false);
		$time_from->setMinuteStepSize(5);

		$time_from_given = unserialize($_SESSION ["form_searchform"] ["time_from"]);
		$time_to_given = unserialize($_SESSION ["form_searchform"] ["time_to"]);

		if ($this->isNoTimeSet($time_from_given['time']))
		{
			$current_date_time_array = $this->getCurrentTime();
			$time_from_given['time'] = $current_date_time_array['time']['from'];
			$time_to_given['time'] = $current_date_time_array['time']['to'];
			$time_from_given['date'] = $current_date_time_array['date']['from'];
			$time_to_given['date'] = $current_date_time_array['date']['to'];
		}

		if (!empty($time_from_given['date']) && !empty($time_from_given['time']))
		{
			$time_from->setDate(new ilDate($time_from_given['date'] . ' ' . $time_from_given['time'],
				IL_CAL_DATETIME, $ilUser->getTimeZone()));
		}

		$time_comb->addCombinationItem("time_from", $time_from, $this->lng->txt("rep_robj_xrs_between"));
		$time_to = new ilRoomSharingTimeInputGUI("", "time_to");
		$time_to->setShowTime(true);
		$time_to->setShowDate(false);
		$time_to->setMinuteStepSize(5);

		if (!empty($time_to_given['date']) && !empty($time_to_given['time']))
		{
			$time_to->setDate(new ilDate($time_to_given['date'] . ' ' . $time_to_given['time'],
				IL_CAL_DATETIME, $ilUser->getTimeZone()));
		}

		$time_comb->addCombinationItem("time_to", $time_to, $this->lng->txt("and"));
		$time_comb->setComparisonMode(ilCombinationInputGUI::COMPARISON_ASCENDING);
		$time_comb->setRequired(true);

		return $time_comb;
	}

	/**
	 * Checks whether or not a time is set.
	 * @param string $a_time_given the time to be checked
	 * @return boolean true, if the time is NOT set; false otherwise
	 */
	private function isNoTimeSet($a_time_given)
	{
		return empty($a_time_given) || $a_time_given == '00:00:00';
	}

	/**
	 * Returns the current time, if no time could be found in the session variable.
	 * @return array an asociative array containing the "from" and "to" time; and the "from"
	 * and "to" date
	 */
	private function getCurrentTime()
	{
		$date_time_array = array();

		// get current time and add leading 0
		$hr_from = (date('H') + 1 < 10 ? "0" . (date('H') + 1) : (date('H') + 1));

		// add leading 0
		$hr_to = ($hr_from + 1 < 10 ? "0" . ($hr_from + 1) : ($hr_from + 1));

		if ($hr_from >= 24)
		{   // increase the day if 23:00 or later and set time from 00:00 to 01:00
			$date_time_array['time']['from'] = "00:00:00";
			$date_time_array['time']['to'] = "01:00:00";
			$date = date('Y-m-d');
			$date_time_array['date']['from'] = date('Y-m-d', strtotime($date . ' + 1 days'));
			$date_time_array['date']['to'] = date('Y-m-d', strtotime($date . ' + 1 days'));
		}
		else
		{
			$date_time_array['time']['from'] = $hr_from . ':00:00';
			$date_time_array['time']['to'] = $hr_to . ':00:00';
			$date_time_array['date']['from'] = date('Y-m-d');
			$date_time_array['date']['to'] = date('Y-m-d');
		}

		return $date_time_array;
	}

	/**
	 * If room attributes are present, create the input items for the those attributes.
	 */
	private function createRoomAttributeFormItems()
	{
		$room_attribute_items = array();
		$room_attributes = $this->rooms->getAllAttributes();
		foreach ($room_attributes as $room_attribute)
		{
			// setup an ilRoomSharingNumberInputGUI for the room attributes
			$room_attribute_title = $room_attribute . " (" . $this->lng->txt("rep_robj_xrs_amount") . ")";
			$room_attribute_postvar = "attribute_" . $room_attribute . "_amount";
			$room_attribute_input = new ilRoomSharingNumberInputGUI($room_attribute_title,
				$room_attribute_postvar);
			$room_attribute_input->setParent($this->search_form);
			$room_attribute_input->setMaxLength(8);
			$room_attribute_input->setSize(8);
			$room_attribute_input->setMinValue(0);
			$max = $this->rooms->getMaxCountForAttribute($room_attribute);
			$max_num = isset($max) ? $max : 0;
			$room_attribute_input->setMaxValue($max_num);
			$room_attribute_input->readFromSession();

			$room_attribute_items[] = $room_attribute_input;
		}

		return $room_attribute_items;
	}

	/**
	 * Creates recurrence gui.
	 * Includes some settings to modify initial recurrence gui.
	 * @param type $a_qsearch_form
	 */
	private function createRecurrenceFormItem()
	{
		$this->getRecurrence();
		$rec = new ilRecurrenceInputGUI($this->lng->txt('cal_recurrences'), 'frequence');
		// set possible frequence types (IL_CAL_FREQ_YEARLY not needed)
		$subforms = array(IL_CAL_FREQ_DAILY, IL_CAL_FREQ_WEEKLY, IL_CAL_FREQ_MONTHLY);
		$rec->setRecurrence($this->rec);
		$rec->setEnabledSubForms($subforms);
		// no unlimited recurrences
		$rec->allowUnlimitedRecurrences(false);
		return $rec;
	}

	/**
	 * Read recurrence from Session
	 */
	protected function getRecurrence()
	{
		$this->rec = new ilCalendarRecurrence();
		$fre = unserialize($_SESSION ["form_searchform"] ["frequence"]);
		$this->rec->setFrequenceType($fre);
		switch ($fre)
		{
			case "NONE":
				break;
			case "DAILY":
				break;
			case "WEEKLY":
				$days = unserialize($_SESSION ["form_searchform"] ["weekdays"]);
				$d = array();
				if (is_array($days))
				{
					foreach ($days as $day)
					{
						$d[] = $day;
					}
				}
				$this->rec->setBYDAY(implode(",", $d));
				break;
			case "MONTHLY":
				$start_type = unserialize($_SESSION ["form_searchform"] ["start_type"]);
				if ($start_type == "weekday")
				{
					$w1 = unserialize($_SESSION ["form_searchform"] ["weekday_1"]);
					$w2 = unserialize($_SESSION ["form_searchform"] ["weekday_2"]);
					if ($w2 == 8)
					{
						$this->rec->setBYSETPOS($w1);
						$this->rec->setBYDAY('MO,TU,WE,TH,FR');
					}
					elseif ($w2 == 9)
					{
						$this->rec->setBYMONTHDAY($w1);
					}
					else
					{
						$this->rec->setBYDAY($w1 . $w2);
					}
				}
				elseif ($start_type == "monthday")
				{
					$this->rec->setBYMONTHDAY(unserialize($_SESSION ["form_searchform"] ["monthday"]));
				}
				break;
			default:
				break;
		}
		$repeat_type = unserialize($_SESSION ["form_searchform"] ["repeat_type"]);
		$this->rec->setInterval(unserialize($_SESSION ["form_searchform"] ["repeat_amount"]));
		if ($repeat_type == "max_amount")
		{
			$this->rec->setFrequenceUntilCount(unserialize($_SESSION ["form_searchform"] ["repeat_until"]));
		}
		elseif ($repeat_type == "max_date")
		{
			$date = unserialize($_SESSION ["form_searchform"] ["repeat_until"]);
			$date2 = date('Y-m-d H:i:s',
				mktime(0, 0, 0, $date['date']['m'], $date['date']['d'], $date['date']['y']));
			$this->rec->setFrequenceUntilDate(new ilDateTime($date2, IL_CAL_DATETIME));
		}
	}

	/**
	 * Set the pool id.
	 *
	 * @param integer $pool_id the pool id to be set
	 */
	public function setPoolId($pool_id)
	{
		$this->pool_id = $pool_id;
	}

	/**
	 * Get the pool id.
	 *
	 * @return integer the pool id of this class
	 */
	public function getPoolId()
	{
		return (int) $this->pool_id;
	}

}

?>
