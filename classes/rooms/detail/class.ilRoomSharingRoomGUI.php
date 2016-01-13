<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingNumericUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingStringUtils.php");
require_once("Services/Form/classes/class.ilPropertyFormGUI.php");
require_once("Services/Form/classes/class.ilCombinationInputGUI.php");
require_once("Services/Form/classes/class.ilSubEnabledFormPropertyGUI.php");
require_once("Services/Form/classes/class.ilMultiSelectInputGUI.php");
require_once("Services/Utilities/classes/class.ilConfirmationGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingTextInputGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingNumberInputGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/rooms/detail/class.ilRoomSharingRoom.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/rooms/class.ilRoomSharingRoomsGUI.php");
require_once("Services/UIComponent/Toolbar/classes/class.ilToolbarGUI.php");
require_once("Services/MediaObjects/classes/class.ilObjMediaObject.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/exceptions/class.ilRoomSharingRoomException.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingPermissionUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivilegesConstants.php");

use ilRoomSharingPrivilegesConstants as PRIVC;

/**
 * Class ilRoomSharingRoomGUI.
 * This class is mainly responsible for displaying a form for adding and editing rooms.
 *
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 * @author Thomas Wolscht <twolscht@stud.hs-bremen.de>
 *
 * @property ilCtrl                       $ctrl
 * @property ilLanguage                   $lng
 * @property ilTemplate                   $tpl
 * @property ilPropertyFormGUI            $form_gui
 * @property ilRoomSharingPermissionUtils $permission
 */
class ilRoomSharingRoomGUI {

	protected $ref_id;
	private $parent_obj;
	private $ctrl;
	private $lng;
	private $tpl;
	private $tabs;
	private $room_id;
	private $pool_id;
	private $form_gui;
	private $room_obj;
	private $permission;
	const SESSION_ROOM_ID = 'xrs_room_id';
	const ATTRIBUTE_ID_PREFIX = 'room_attr_id_';


	/**
	 * Constructor for ilRoomSharingRoomGUI.
	 *
	 * @param object  $a_parent_obj the caller of this method
	 * @param integer $a_room_id    the id for which the GUI should be generated
	 */
	public function __construct($a_parent_obj, $a_room_id) {
		global $ilCtrl, $lng, $tpl, $ilTabs, $rssPermission;

		if (!empty($a_room_id)) {
			$this->room_id = $a_room_id;
			$this->setRoomIdAsSessionVariable($a_room_id);
		} else {
			$this->room_id = $this->getRoomIdFromSession();
		}
		$this->ref_id = $a_parent_obj->ref_id;
		$this->parent_obj = $a_parent_obj;
		$this->pool_id = $a_parent_obj->getPoolId();

		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		$this->tpl = $tpl;
		$this->tabs = $ilTabs;
		$this->permission = $rssPermission;

		$this->room_obj = &new ilRoomSharingRoom($this->pool_id, $this->room_id);
	}


	/**
	 * Executes the default command which displays the room with its properties.
	 */
	public function executeCommand() {
		$cmd = $this->ctrl->getCmd('showRoom');
		$this->$cmd();

		return true;
	}


	/**
	 * Adds subtabs to the main tab "Rooms".
	 *
	 * @param string $a_subtab_to_be_activated SubTab which should be activated after method call.
	 */
	protected function setSubTabs($a_subtab_to_be_activated) {
		$this->tabs->setTabActive('rooms');

		$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'room_id', $this->room_id);

		// room information
		$this->tabs->addSubTab('room', $this->lng->txt('rep_robj_xrs_room'), $this->ctrl->getLinkTargetByClass('ilroomsharingroomgui', 'showRoom'));
		if ($this->permission->checkPrivilege(PRIVC::SEE_BOOKINGS_OF_ROOMS)) {
			// room occupation per week
			$this->tabs->addSubTab('weekview', $this->lng->txt('rep_robj_xrs_room_occupation'), $this->ctrl->getLinkTargetByClass('ilRoomSharingCalendarWeekGUI', 'show'));
		}
		$this->tabs->activateSubTab($a_subtab_to_be_activated);
	}


	/**
	 * Renders the GUI for a room, which consists of buttons for editing and adding new rooms and a
	 * form for editing room properties.
	 */
	public function showRoom() {
		if (!$this->permission->checkPrivilege(PRIVC::ACCESS_ROOMS)) {
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_no_permission_for_action"));
			$this->ctrl->redirectByClass('ilinfoscreengui', 'showSummary', 'showSummary');

			return false;
		}
		$this->setSubTabs('room');
		$this->room_obj = new ilRoomSharingRoom($this->pool_id, $this->room_id);

		$toolbar = new ilToolbarGUI();
		if ($this->permission->checkPrivilege(PRIVC::ACCESS_ROOMS)) {
			$toolbar->addButton($this->lng->txt('rep_robj_xrs_back_to_rooms'), $this->ctrl->getLinkTargetByClass('ilroomsharingroomsgui', "showRooms"));
		}
		if ($this->permission->checkPrivilege(PRIVC::EDIT_ROOMS)) {
			$toolbar->addButton($this->lng->txt('rep_robj_xrs_room_edit'), $this->ctrl->getLinkTarget($this, "editRoom"));
		}
		if ($this->permission->checkPrivilege(PRIVC::ADD_ROOMS)) {
			$toolbar->addButton($this->lng->txt('rep_robj_xrs_add_room'), $this->ctrl->getLinkTarget($this, "addRoom"));
		}

		$this->form_gui = $this->initForm("show");
		$this->tpl->setContent($toolbar->getHTML() . $this->form_gui->getHTML());
	}


	/**
	 * Creates a form adding a new room.
	 */
	public function addRoom() {
		if (!$this->permission->checkPrivilege(PRIVC::ADD_ROOMS)) {
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_no_permission_for_action"));
			$this->ctrl->redirectByClass('ilinfoscreengui', 'showSummary', 'showSummary');

			return false;
		}
		$this->room_obj = &new ilRoomSharingRoom($this->pool_id, "", true);

		$toolbar = new ilToolbarGUI();
		if ($this->permission->checkPrivilege(PRIVC::ACCESS_ROOMS)) {
			$toolbar->addButton($this->lng->txt('rep_robj_xrs_back_to_rooms'), $this->ctrl->getLinkTargetByClass('ilroomsharingroomsgui', "showRooms"));
		}
		$this->form_gui = $this->initForm("create");
		$this->form_gui->clearCommandButtons();
		$this->form_gui->addCommandButton("createRoom", $this->lng->txt("save"));

		$this->tpl->setContent($toolbar->getHTML() . $this->form_gui->getHTML());
	}


	/**
	 * Creates a form editing room properties of an existing room.
	 */
	public function editRoom() {
		if (!$this->permission->checkPrivilege(PRIVC::EDIT_ROOMS)) {
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_no_permission_for_action"), true);
			$this->ctrl->redirectByClass('ilinfoscreengui', 'showSummary', 'showSummary');

			return false;
		}
		$this->room_obj = new ilRoomSharingRoom($this->pool_id, (int)$_GET['room_id']);
		$toolbar = new ilToolbarGUI();
		if ($this->permission->checkPrivilege(PRIVC::ACCESS_ROOMS)) {
			$toolbar->addButton($this->lng->txt('rep_robj_xrs_back_to_rooms'), $this->ctrl->getLinkTargetByClass('ilroomsharingroomsgui', "showRooms"));
		}
		$this->form_gui = $this->initForm("edit");
		$this->form_gui->clearCommandButtons();
		$this->form_gui->addCommandButton("saveRoom", $this->lng->txt("save"));

		$this->tpl->setContent($toolbar->getHTML() . $this->form_gui->getHTML());
	}


	/**
	 * Initializes a form for either displaying a room, adding a new room or editing an existing
	 * room. The difference between those three forms is subtle but important: the form for
	 * displaying rooms displays the information of the room without the ability of editing them.
	 * The form for creating a room allows the input of values but contains no values initially. The
	 * form for editing a room contains information that have been set before.
	 * The creation of either those forms is determined by the mode parameter.
	 *
	 * @param string $a_mode the mode this form is centered around
	 *
	 * @return ilPropertyFormGUI the form with the given mode
	 */
	private function initForm($a_mode = "show") {
		$form_gui = &new ilPropertyFormGUI();
		$form_gui->setMultipart(true);
		$form_gui->setTitle($this->lng->txt("rep_robj_xrs_room_properties"));
		$form_gui->setDescription($this->lng->txt("rep_robj_xrs_room_prop_description"));

		$name = new ilRoomSharingTextInputGUI($this->lng->txt("rep_robj_xrs_room_name"), "name");
		$name->setDisabled(true);
		$form_gui->addItem($name);

		$type = new ilRoomSharingTextInputGUI($this->lng->txt("rep_robj_xrs_room_type"), "type");
		$type->setDisabled(true);
		$form_gui->addItem($type);

		$min_alloc = new ilRoomSharingNumberInputGUI($this->lng->txt("rep_robj_xrs_room_min_alloc"), "min_alloc");
		$min_alloc->setDisabled(true);
		$form_gui->addItem($min_alloc);

		$max_alloc = new ilRoomSharingNumberInputGUI($this->lng->txt("rep_robj_xrs_room_max_alloc"), "max_alloc");
		$max_alloc->setDisabled(true);
		$form_gui->addItem($max_alloc);

		$floor_plan = new ilSelectInputGUI($this->lng->txt("rep_robj_xrs_room_floor_plans"), "file_id");
		$floor_plan->setOptions($this->room_obj->getAllFloorplans());
		$floor_plan->setDisabled(true);
		$form_gui->addItem($floor_plan);

		if (count($this->room_obj->getAllAvailableAttributes())) {
			$defined_attributes = $this->room_obj->getAttributes();
			$show_mode_with_exist_attrs = (($a_mode == "show") && count($defined_attributes) > 0);

			if (($a_mode == "edit") || ($a_mode == "create") || $show_mode_with_exist_attrs) {
				$attributes_header = new ilFormSectionHeaderGUI();
				$attribute_header_text = $this->createAttributeHeaderText();
				$attributes_header->setTitle($this->lng->txt("rep_robj_xrs_room_attributes") . $attribute_header_text);
				$form_gui->addItem($attributes_header);
			}

			foreach ($this->room_obj->getAllAvailableAttributes() as $attr) {
				$attribute_amount_by_id = $this->room_obj->getAttributeAmountById($attr['id']);
				$amount_not_given = !ilRoomSharingNumericUtils::isPositiveNumber($attribute_amount_by_id, true);
				if ($a_mode == "show" && $amount_not_given) {
					continue;
				} else {
					$attr_field = new ilRoomSharingNumberInputGUI($attr['name'], self::ATTRIBUTE_ID_PREFIX . $attr['id']);
					$attr_field->setValue($attribute_amount_by_id);
					$attr_field->setMinValue(0);
					$attr_field->setDisabled(($a_mode == "show"));
					$form_gui->addItem($attr_field);
				}
			}
		}

		if ($a_mode == "edit" || $a_mode == "create") {
			$name->setDisabled(false);
			$name->setRequired(true);
			$type->setDisabled(false);
			$min_alloc->setDisabled(false);
			$min_alloc->setMinValue(0);
			$max_alloc->setDisabled(false);
			$max_alloc->setRequired(true);
			$max_alloc->setMinValue(0);
			$floor_plan->setDisabled(false);

			if ($a_mode == "create") {
				$min_alloc->setValue("0");
				$form_gui->addCommandButton($this->ctrl->getLinkTarget($this, "addRoom"), $this->lng->txt("rep_robj_xrs_add_room"));
			} else {
				$form_gui->addCommandButton("saveRoom", $this->lng->txt("save"));
			}
		}
		if ($a_mode == "edit" || $a_mode == "show") {
			$name->setValue($this->room_obj->getName());
			$type->setValue($this->room_obj->getType());
			$min_alloc->setValue($this->room_obj->getMinAlloc());
			$max_alloc->setValue($this->room_obj->getMaxAlloc());
			$floor_plan->setValue($this->room_obj->getFileId());
			if ($a_mode == "show") {
				$floor_plan->setDisabled(true);
				$mobj = new ilObjMediaObject($this->room_obj->getFileId());
				$mitems = $mobj->getMediaItems();
				if (!empty($mitems)) {
					$med = $mobj->getMediaItem("Standard");
					$target = $med->getThumbnailTarget();
					$image_with_link =
						"<br><a target='_blank' href='" . $mobj->getDataDirectory() . "/" . $med->getLocation() . "'>" . ilUtil::img($target)
						. "</a>";
					$floor_plan->setInfo($image_with_link);
				}
			}
		}

		$form_gui->setFormAction($this->ctrl->getFormAction($this));

		return $form_gui;
	}


	/**
	 * This function creates the header text for the attribute section of the form. This is needed
	 * since the normal way of setting an info text via "setInfo" on an "ilFormSectionHeaderGUI" is
	 * for whatever reason not possible.
	 *
	 * @return string the attribute header text
	 */
	private function createAttributeHeaderText() {
		$header_text =
			'<div class=' . "'ilFormInfo'" . "style='font-weight: normal !important;'>" . $this->lng->txt("rep_robj_xrs_room_attributes_info")
			. '</div>';

		return $header_text;
	}


	/**
	 * Handles the save command of the form for creating new room.
	 */
	public function createRoom() {
		if (!$this->permission->checkPrivilege(PRIVC::ADD_ROOMS)) {
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_no_permission_for_action"), true);
			$this->ctrl->redirectByClass('ilinfoscreengui', 'showSummary', 'showSummary');

			return false;
		}
		$toolbar = new ilToolbarGUI();
		if ($this->permission->checkPrivilege(PRIVC::ACCESS_ROOMS)) {
			$toolbar->addButton($this->lng->txt('rep_robj_xrs_back_to_rooms'), $this->ctrl->getLinkTargetByClass('ilroomsharingroomsgui', "showRooms"));
		}

		$this->form_gui = $this->initForm("create");
		$this->form_gui->setValuesByPost();
		$this->form_gui->clearCommandButtons();
		$this->form_gui->addCommandButton("createRoom", $this->lng->txt("save"));
		if ($this->form_gui->checkInput()) {
			try {
				$this->room_obj = new ilRoomSharingRoom($this->pool_id, "", true);
				$this->room_obj->setPoolId($this->pool_id);
				$this->room_obj->setName($this->form_gui->getInput("name"));
				$this->room_obj->setType($this->form_gui->getInput("type"));
				$this->room_obj->setMinAlloc($this->form_gui->getInput("min_alloc"));
				$this->room_obj->setMaxAlloc($this->form_gui->getInput("max_alloc"));
				$this->room_obj->setFileId($this->form_gui->getInput("file_id"));

				foreach ($this->getSetAttributeValuesFromForm() as $set_attribute_values) {
					$this->room_obj->addAttribute($set_attribute_values['id'], $set_attribute_values['count']);
				}

				$new_room_id = $this->room_obj->create();
			} catch (ilRoomSharingRoomException $exc) {
				ilUtil::sendFailure($this->lng->txt($exc->getMessage()), true);
				$this->form_gui->setValuesByPost();
				$this->tpl->setContent($toolbar->getHTML() . $this->form_gui->getHTML());
			}

			if (ilRoomSharingNumericUtils::isPositiveNumber($new_room_id)) {
				ilUtil::sendSuccess($this->lng->txt("rep_robj_xrs_room_added"), true);
				$this->room_obj->setId($new_room_id);
				$this->setRoomIdAsSessionVariable($new_room_id);
				$this->room_obj = new ilRoomSharingRoom($this->pool_id, $new_room_id);
				$this->ctrl->redirect($this, "showRooms");
			} else {
				ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_wrong_input"), true);
				$this->form_gui->setValuesByPost();
				$this->tpl->setContent($toolbar->getHTML() . $this->form_gui->getHTML());
			}
		} else {
			$this->form_gui->setValuesByPost();
			$this->tpl->setContent($toolbar->getHTML() . $this->form_gui->getHTML());
		}
	}


	/**
	 * Handles the save command of the form for editing an existing room.
	 */
	public function saveRoom() {
		if (!$this->permission->checkPrivilege(PRIVC::EDIT_ROOMS)) {
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_no_permission_for_action"), true);
			$this->ctrl->redirectByClass('ilinfoscreengui', 'showSummary', 'showSummary');

			return false;
		}
		$toolbar = new ilToolbarGUI();
		if ($this->permission->checkPrivilege(PRIVC::ACCESS_ROOMS)) {
			$toolbar->addButton($this->lng->txt('rep_robj_xrs_back_to_rooms'), $this->ctrl->getLinkTargetByClass('ilroomsharingroomsgui', "showRooms"));
		}

		$this->form_gui = $this->initForm("edit");
		$this->form_gui->setValuesByPost();
		if ($this->form_gui->checkInput()) {
			try {
				$this->room_obj->setName($this->form_gui->getInput("name"));
				$this->room_obj->setType($this->form_gui->getInput("type"));
				$this->room_obj->setMinAlloc($this->form_gui->getInput("min_alloc"));
				$this->room_obj->setMaxAlloc($this->form_gui->getInput("max_alloc"));
				$this->room_obj->setFileId($this->form_gui->getInput("file_id"));

				$this->room_obj->resetAttributes();

				foreach ($this->getSetAttributeValuesFromForm() as $set_attribute_values) {
					$this->room_obj->addAttribute($set_attribute_values['id'], $set_attribute_values['count']);
				}

				$this->room_obj->save();
				$this->showRoom();
			} catch (ilRoomSharingRoomException $exc) {
				ilUtil::sendFailure($this->lng->txt($exc->getMessage()), true);
				$this->form_gui->setValuesByPost();
				$this->tpl->setContent($toolbar->getHTML() . $this->form_gui->getHTML());
			}
		} else {
			$this->form_gui->setValuesByPost();
			$this->tpl->setContent($toolbar->getHTML() . $this->form_gui->getHTML());
		}
	}


	/**
	 * Displays a confirmation dialog for the deletion of a room.
	 */
	public function confirmDeleteRoom() {
		if (!$this->permission->checkPrivilege(PRIVC::DELETE_ROOMS)) {
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_no_permission_for_action"), true);
			$this->ctrl->redirectByClass('ilinfoscreengui', 'showSummary', 'showSummary');

			return false;
		}
		$this->tabs->clearTargets();
		$this->tabs->setBackTarget($this->lng->txt('rep_robj_xrs_back_to_rooms'), $this->ctrl->getLinkTargetByClass('ilroomsharingroomsgui', "showRooms"));
		$cgui = new ilConfirmationGUI();
		$cgui->setFormAction($this->ctrl->getFormAction($this));
		$cgui->setCancel($this->lng->txt("cancel"), "showRooms");
		$cgui->setConfirm($this->lng->txt("confirm"), "deleteRoom");
		$amount_of_bookings = $this->room_obj->getAmountOfBookings();
		if ($amount_of_bookings > 0) {
			$cgui->setHeaderText($this->lng->txt('rep_robj_xrs_room_delete'));
			ilUtil::sendFailure($this->lng->txt('rep_robj_xrs_room_delete_booking') . " <b>" . $amount_of_bookings . "</b>", true);
		} else {
			$cgui->setHeaderText($this->lng->txt('rep_robj_xrs_room_delete'));
		}
		$cgui->addItem('booking_id', $this->room_id, $this->room_obj->getName());
		$this->tpl->setContent($cgui->getHTML());
	}


	/**
	 * Handles the deletion of a room after the corresponding confirm dialog has been confirmed.
	 */
	public function deleteRoom() {
		if (!$this->permission->checkPrivilege(PRIVC::DELETE_ROOMS)) {
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_no_permission_for_action"), true);
			$this->ctrl->redirectByClass('ilinfoscreengui', 'showSummary', 'showSummary');

			return false;
		}
		try {
			$this->room_obj->delete();
			ilUtil::sendSuccess($this->lng->txt('rep_robj_xrs_room_delete_success'), true);
		} catch (ilRoomSharingRoomException $exc) {
			ilUtil::sendFailure($this->lng->txt($exc->getMessage()), true);
			$this->ctrl->redirectByClass('ilroomsharingroomsgui', 'showRooms');
		}
		$this->ctrl->redirectByClass('ilroomsharingroomsgui', 'showRooms');
	}


	/**
	 * Returns an associative array which contains the attribute ids and its corresponding amounts
	 * set via the form
	 *
	 * @return array associative array with attribute ids and its amounts
	 */
	private function getSetAttributeValuesFromForm() {
		$all_input_items = $this->form_gui->getInputItemsRecursive();
		$set_attributes = array();

		foreach ($all_input_items as $input_item) {
			if ($this->isSetAttributeValid($input_item)) {
				$set_attributes[] = array( 'id' => $this->getAttributeIdFromInput($input_item), 'count' => $input_item->getValue() );
			}
		}

		return $set_attributes;
	}


	/**
	 * Retrieves the attribute id from the attribute input field.
	 *
	 * @param $a_input_item the form input field from where the id is retrieved
	 *
	 * @return integer the id of the attribute that has been set
	 */
	private function getAttributeIdFromInput($a_input_item) {
		return substr($a_input_item->getPostVar(), strlen(self::ATTRIBUTE_ID_PREFIX));
	}


	/**
	 * Determines whether or not the given input field is an attribute field which contains a
	 * valid amount.
	 *
	 * @param $a_input_item the input item that should be checked
	 *
	 * @return boolean true if the set attribute is valid; false otherwise
	 */
	private function isSetAttributeValid($a_input_item) {
		$valid = false;
		if (!empty($a_input_item)) {
			$post_var = $a_input_item->getPostVar();
			if (!empty($post_var)
				&& ilRoomSharingStringUtils::startsWith($post_var, self::ATTRIBUTE_ID_PREFIX)
			) {
				$valid = ilRoomSharingNumericUtils::isPositiveNumber($a_input_item->getValue(), true);
			}
		}

		return $valid;
	}


	/**
	 * Redirects to the overview of all rooms.
	 */
	private function showRooms() {
		$this->ctrl->redirectByClass('ilroomsharingroomsgui', 'showRooms');
	}


	/**
	 * Returns the room id which was saved in the session.
	 *
	 * @return integer the room id saved as a session variable
	 */
	private function getRoomIdFromSession() {
		return unserialize($_SESSION[self::SESSION_ROOM_ID]);
	}


	/**
	 * Saves the room id as a sessin variable.
	 *
	 * @param integer $a_room_id the room id that should be saved to the session
	 */
	private function setRoomIdAsSessionVariable($a_room_id) {
		$_SESSION[self::SESSION_ROOM_ID] = serialize($a_room_id);
	}


	/**
	 * Returns the RoomSharing pool id.
	 *
	 * @returns integer the pool id.
	 */
	public function getPoolId() {
		return $this->pool_id;
	}


	/**
	 * Sets the RoomSharing pool id.
	 *
	 * @param integer the pool id to be set.
	 */
	public function setPoolId($a_pool_id) {
		$this->pool_id = $a_pool_id;
	}
}

?>
