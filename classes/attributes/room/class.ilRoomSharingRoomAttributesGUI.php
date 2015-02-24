<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/attributes/room/class.ilRoomSharingRoomAttributes.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/exceptions/class.ilRoomSharingAttributesException.php");
require_once("Services/Form/classes/class.ilPropertyFormGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/attributes/class.ilRoomSharingAttributesConstants.php");

use ilRoomSharingAttributesConstants as ATTRC;

/**
 * Class ilRoomSharingRoomAttributesGUI
 *
 * @author  Thomas Matern <tmatern@stud.hs-bremen.de>
 *
 * @version $Id$
 * @property ilCtrl            $ctrl
 * @property ilLanguage        $lng
 * @property ilTemplate        $tpl
 * @property ilPropertyFormGUI $attributesForm
 */
class ilRoomSharingRoomAttributesGUI {

	protected $ref_id;
	private $pool_id;
	private $attributesForm;
	private $ctrl;
	private $lng;
	private $tpl;


	/**
	 * Constructor of ilRoomSharingRoomAttributesGUI
	 *
	 * @global ilCtrl                    $ilCtrl
	 * @global ilLanguage                $lng
	 * @global ilTemplate                $tpl
	 *
	 * @param ilRoomSharingAttributesGUI $a_parent_obj
	 */
	function __construct(ilRoomSharingAttributesGUI $a_parent_obj) {
		global $ilCtrl, $lng, $tpl;

		$this->ref_id = $a_parent_obj->ref_id;
		$this->pool_id = $a_parent_obj->getPoolId();
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		$this->tpl = $tpl;
	}


	/**
	 * Command execution.
	 *
	 * @return Returns always true.
	 */
	function executeCommand() {
		$cmd = $this->ctrl->getCmd(ATTRC::SHOW_ROOM_ATTR_ACTIONS);
		if ($cmd == 'render') {
			$cmd = ATTRC::SHOW_ROOM_ATTR_ACTIONS;
		}
		$this->$cmd();

		return true;
	}


	/**
	 * Shows all available attributes.
	 */
	public function showRoomAttributeActions() {
		$this->createAttributesForm();
		$this->tpl->setContent($this->attributesForm->getHTML());
	}


	/**
	 * Executes the action provided by the user.
	 */
	public function executeRoomAttributeAction() {
		$this->createAttributesForm();
		if ($this->attributesForm->checkInput()) {
			try {
				$updatedRoomsAmount = $this->proceedRoomAttributeAction();
			} catch (ilRoomSharingAttributesException $exc) {
				ilUtil::sendFailure($this->lng->txt($exc->getMessage()), true);
				$this->ctrl->redirect($this, ATTRC::SHOW_ROOM_ATTR_ACTIONS);
			}
			if (isset($updatedRoomsAmount) && $updatedRoomsAmount > 0) {
				ilUtil::sendSuccess($this->createDeletionMessage($updatedRoomsAmount), true);
			} else {
				ilUtil::sendSuccess($this->lng->txt('msg_obj_modified'), true);
			}
			$this->ctrl->redirect($this, ATTRC::SHOW_ROOM_ATTR_ACTIONS);
		}
		$this->attributesForm->setValuesByPost();
		$this->tpl->setContent($this->attributesForm->getHtml());
	}


	/**
	 * Creates an message with amount of affected rooms after an attribute was deleted.
	 *
	 * @param integer $a_updated_rooms_amount
	 *
	 * @return string Created message
	 */
	private function createDeletionMessage($a_updated_rooms_amount) {
		return $this->lng->txt('msg_obj_modified') . '. ' . $a_updated_rooms_amount . ' ' . $this->lng->txt('rep_robj_xrs_rooms_were_updated');
	}


	/**
	 * Determines which action the user performed and calls backend functions.
	 *
	 * @throws ilRoomSharingAttributesException itself and from backend
	 * @return integer deleted assignments if action 'Delete' was executed
	 */
	private function proceedRoomAttributeAction() {
		$roomSharingRoomAttributes = new ilRoomSharingRoomAttributes($this->pool_id, new ilRoomsharingDatabase($this->pool_id));

		switch ($this->attributesForm->getInput(ATTRC::ACTION_MODE)) {
			case ATTRC::CREATE_MODE:
				$newName = $this->attributesForm->getInput(ATTRC::NEW_NAME);
				$roomSharingRoomAttributes->createAttribute($newName);
				break;

			case ATTRC::RENAME_MODE:
				$attributeId = $this->attributesForm->getInput(ATTRC::RENAME_ATTR_ID);
				$changedName = $this->attributesForm->getInput(ATTRC::CHANGED_NAME);
				$roomSharingRoomAttributes->renameAttribute($attributeId, $changedName);
				break;

			case ATTRC::DELETE_MODE:
				$attributeId = $this->attributesForm->getInput(ATTRC::DEL_ATTR_ID);

				return $roomSharingRoomAttributes->deleteAttribute($attributeId);
			default:
				throw new ilRoomSharingAttributesException('rep_robj_xrs_illigal_action_performed');
		}
	}


	/**
	 * Creates the attributes form.
	 * It contains an radio group with radio names as actions.
	 * Every radio has own gui elements.
	 */
	private function createAttributesForm() {
		$this->attributesForm = new ilPropertyFormGUI();
		$this->attributesForm->setTitle($this->lng->txt('rep_robj_xrs_edit_attributes'));
		$this->attributesForm->setDescription($this->lng->txt('rep_robj_xrs_attributes_for_rooms_desc'));

		// Radio group
		$radioGroup = new ilRadioGroupInputGUI($this->lng->txt('rep_robj_xrs_choose_action'), ATTRC::ACTION_MODE);
		$radioGroup->setRequired(true);

		// Available attributes
		$roomSharingRoomAttributes = new ilRoomSharingRoomAttributes($this->pool_id, new ilRoomsharingDatabase($this->pool_id));
		$attributes = $roomSharingRoomAttributes->getAllAvailableAttributesWithIdAndName();

		// Create
		$create = new ilRadioOption($this->lng->txt('rep_robj_xrs_create_attribute'), ATTRC::CREATE_MODE, $this->lng->txt('rep_robj_xrs_create_attribute_info'));
		$new_name = new ilTextInputGUI($this->lng->txt('rep_robj_xrs_name_of_new_attribute'), ATTRC::NEW_NAME);
		$new_name->setRequired(true);
		$new_name->setMaxLength(ATTRC::MAX_NAME_LENGTH);
		$new_name->setInfo($this->lng->txt('rep_robj_xrs_must_be_unique'));
		$create->addSubItem($new_name);
		$radioGroup->addOption($create);

		//Rename
		$rename = new ilRadioOption($this->lng->txt('rep_robj_xrs_rename_attribute'), ATTRC::RENAME_MODE, $this->lng->txt('rep_robj_xrs_rename_attribute_info'));
		$toRename = new ilSelectInputGUI($this->lng->txt('rep_robj_xrs_choose_attribute'), ATTRC::RENAME_ATTR_ID);
		$toRename->setOptions($attributes);
		$toRename->setRequired(true);
		$rename->addSubItem($toRename);
		$changedName = new ilTextInputGUI($this->lng->txt('rep_robj_xrs_new_attribute_name'), ATTRC::CHANGED_NAME);
		$changedName->setRequired(true);
		$changedName->setMaxLength(ATTRC::MAX_NAME_LENGTH);
		$changedName->setInfo($this->lng->txt('rep_robj_xrs_must_be_unique'));
		$rename->addSubItem($changedName);
		$radioGroup->addOption($rename);

		//Delete
		$delete = new ilRadioOption($this->lng->txt('rep_robj_xrs_delete_attribute'), ATTRC::DELETE_MODE, $this->lng->txt('rep_robj_xrs_delete_room_attribute_info'));
		$toDelete = new ilSelectInputGUI($this->lng->txt('rep_robj_xrs_choose_attribute'), ATTRC::DEL_ATTR_ID);
		$toDelete->setOptions($attributes);
		$toDelete->setRequired(true);
		$delete->addSubItem($toDelete);
		$radioGroup->addOption($delete);

		$this->attributesForm->addItem($radioGroup);
		$this->attributesForm->addCommandButton(ATTRC::EXECUTE_ROOM_ATTR_ACTION, $this->lng->txt("save"));
		$this->attributesForm->setFormAction($this->ctrl->getFormAction($this));
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
	 *
	 */
	public function setPoolId($a_pool_id) {
		$this->pool_id = $a_pool_id;
	}
}

?>
