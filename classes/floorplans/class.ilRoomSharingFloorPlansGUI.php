<?php

require_once("./Services/MediaObjects/classes/class.ilObjMediaObject.php");
require_once("Services/UIComponent/Toolbar/classes/class.ilToolbarGUI.php");
require_once("Services/Form/classes/class.ilPropertyFormGUI.php");
require_once("./Services/Utilities/classes/class.ilConfirmationGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/floorplans/class.ilRoomSharingFloorPlans.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/floorplans/class.ilRoomSharingFloorPlansTableGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingPermissionUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivilegesConstants.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/exceptions/class.ilRoomSharingFloorplanException.php");

use ilRoomSharingPrivilegesConstants as PRIVC;

/**
 * Class ilRoomSharingFloorPlansGUI
 *
 * This class represents the frontend for the RoomSharing floor plans. It is
 * used for adding, editing and removing floorplans.
 *
 * @author Alexander Keller <a.k3ll3r@gmail.com>
 * @author Thomas Wolscht <t.wolscht@googlemail.com>
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 *
 * @property ilCtrl $ctrl;
 * @property ilLanguage $lng
 * @property ilTemplate $tpl
 * @property ilTabsGUI $tabs
 * @property ilRoomSharingPermissionUtils $permission
 *
 * @version $Id$
 */
class ilRoomSharingFloorPlansGUI
{
	protected $ref_id;
	private $pool_id;
	private $permission;
	private $ctrl;
	private $lng;
	private $tpl;
	private $tabs;

	/**
	 * Constructor of ilRoomSharingFloorPlansGUI.
	 * @param object $a_parent_obj used for getting necessary IDs
	 */
	public function __construct(ilObjRoomSharingGUI $a_parent_obj)
	{
		global $ilCtrl, $lng, $tpl, $ilTabs, $rssPermission;

		$this->ref_id = $a_parent_obj->ref_id;
		$this->pool_id = $a_parent_obj->getPoolId();
		$this->permission = $rssPermission;
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		$this->tpl = $tpl;
		$this->tabs = $ilTabs;
	}

	/**
	 * Switch for the command execution.
	 *
	 * @return true, if nothing bad happens when a command is executed
	 */
	public function executeCommand()
	{
		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd("render");   // the default command, if none
		// is found
		switch ($next_class)
		{
			default:
				$cmd .= 'Object';
				$this->$cmd();
				break;
		}
		return true;
	}

	/**
	 * Main function of ilRoomSharingFloorPlansGUI. Creates an instance of
	 * RoomSharingFloorPlansTableGUI to display a table that contains all
	 * uploaded floor plans. If the user has 'write' permissions, a button for
	 * adding a new floor plan is displayed.
	 */
	public function renderObject()
	{
		if (!$this->permission->checkPrivilege(PRIVC::ACCESS_FLOORPLANS))
		{
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_no_permission_for_action"));
			$this->ctrl->redirectByClass('ilinfoscreengui', 'showSummary', 'showSummary');
			return FALSE;
		}

		// floor plans only addable by users with write permissions
		if ($this->permission->checkPrivilege(PRIVC::ADD_FLOORPLANS))
		{
			$bar = new ilToolbarGUI();
			$bar->addButton($this->lng->txt('rep_robj_xrs_floor_plans_add'), $this->ctrl->getLinkTarget($this, 'create'));
			$bar_content = $bar->getHTML();
		}
		$table = new ilRoomSharingFloorPlansTableGUI($this, 'render', $this->ref_id);
		$this->tpl->setContent($bar_content . $table->getHTML());
	}

	/**
	 * Form for adding or editing a floor plan.
	 *
	 * @param string $a_mode "create" or "edit" mode
	 * @param integer $a_file_id the file id for which the form is called
	 * @return ilPropertyFormGUI the form for adding or creating floorplans
	 */
	public function initForm($a_mode = "create", $a_file_id = NULL)
	{
		$form_gui = null;   // form initialization
		// create a form depending on the mode
		if ($a_mode === "create")
		{
			$form_gui = $this->initCreationForm();
		}
		else
		{
			$form_gui = $this->initEditForm($a_file_id);
		}
		$form_gui->setFormAction($this->ctrl->getFormAction($this));

		return $form_gui;
	}

	/**
	 * Creates a form which is used for adding new floor plans.
	 *
	 * @return ilPropertyFormGUI the creation form
	 */
	protected function initCreationForm()
	{
		$creation_form = new ilPropertyFormGUI();
		$creation_form->setTitle($this->lng->txt("rep_robj_xrs_floor_plans_add"));

		$title = $this->createTitleInputFormItem();
		$creation_form->addItem($title);

		$desc = $this->createDescInputFormItem();
		$creation_form->addItem($desc);

		$file = $this->createFileInputFormItem();
		$creation_form->addItem($file);

		$creation_form->addCommandButton("save", $this->lng->txt("save"));
		$creation_form->addCommandButton("render", $this->lng->txt("cancel"));

		return $creation_form;
	}

	/**
	 * Create a form for editing existing floor plans.
	 *
	 * @param integer $a_file_id the id for the file which should be edited
	 * @return ilPropertyFormGUI the edit form
	 */
	protected function initEditForm($a_file_id)
	{
		$edit_form = new ilPropertyFormGUI();
		$edit_form->setTitle($this->lng->txt("rep_robj_xrs_floor_plans_edit"));

		$title = $this->createTitleInputFormItem();
		$edit_form->addItem($title);

		$desc = $this->createDescInputFormItem();
		$edit_form->addItem($desc);

		$item = new ilHiddenInputGUI('file_id');
		$item->setValue($a_file_id);
		$edit_form->addItem($item);

		$radg = $this->createRadioGroupInputFormItem();
		$edit_form->addItem($radg);

		// look for floor plan infos and set the input entries accordingly
		$fplan = new ilRoomSharingFloorPlans($this->pool_id, new ilRoomsharingDatabase($this->pool_id));
		$fplaninfo = $fplan->getFloorPlanInfo($a_file_id);

		if ($fplaninfo)
		{
			$mobj = new ilObjMediaObject($fplaninfo[0]["file_id"]);
			$title->setValue($mobj->getTitle());
			$desc->setValue($mobj->getMediaItem("Standard")->getCaption());
		}

		$edit_form->addCommandButton("update", $this->lng->txt("save"));
		$edit_form->addCommandButton("render", $this->lng->txt("cancel"));

		return $edit_form;
	}

	/**
	 * Create an input field for the title.
	 * @return ilTextInputGUI the input form item
	 */
	protected function createTitleInputFormItem()
	{
		$title = new ilTextInputGUI($this->lng->txt("title"), "title");
		$title->setRequired(true);
		$title->setSize(40);
		$title->setMaxLength(120);

		return $title;
	}

	/**
	 * Create and return a text area input field for descriptions.
	 * @return ilTextAreaInputGUI description input item
	 */
	protected function createDescInputFormItem()
	{
		$desc = new ilTextAreaInputGUI($this->lng->txt("description"), "description");
		$desc->setCols(70);
		$desc->setRows(8);

		return $desc;
	}

	/**
	 * Creates an input field for floor plans that should be uploaded.
	 * @return ilFileInputGUI file input form item
	 */
	protected function createFileInputFormItem()
	{
		$file = new ilFileInputGUI($this->lng->txt("rep_robj_xrs_room_floor_plans"), "upload_file");
		$file->setSize(50);
		$file->setRequired(true);
		$file->setALlowDeletion(true);
		$file->setInfo($this->lng->txt("rep_robj_xrs_floor_plans_filetypes") . " .bmp, .jpg, .jpeg, .png, .gif");

		return $file;
	}

	/**
	 * This function creates and returns a radio button group which consists of
	 * an option for either keeping or replacing the current floor plan. If the
	 * current floor plan should be replaced, a file input field will be
	 * displayed.
	 * @return ilRadioGroupInputGUI the radio input group
	 */
	protected function createRadioGroupInputFormItem()
	{
		$radg = new ilRadioGroupInputGUI($this->lng->txt(""), "file_mode");
		$op1 = new ilRadioOption($this->lng->txt("rep_robj_xrs_floor_plans_keep"), "keep", $this->lng->txt("rep_robj_xrs_floor_plans_keep_info"));
		$radg->addOption($op1);
		$op2 = new ilRadioOption($this->lng->txt("rep_robj_xrs_floor_plans_replace"), "replace", $this->lng->txt("rep_robj_xrs_floor_plans_replace_info"));
		$op2->addSubItem($this->createFileInputFormItem());
		$radg->addOption($op2);
		$radg->setValue("keep"); // the default option is to 'keep' a floor plan

		return $radg;
	}

	/**
	 * Renders the creation form when the corresponding button is clicked.
	 */
	public function createObject()
	{
		if (!$this->permission->checkPrivilege(PRIVC::ADD_FLOORPLANS))
		{
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_no_permission_for_action"));
			$this->ctrl->redirectByClass('ilinfoscreengui', 'showSummary', 'showSummary');
			return FALSE;
		}
		$this->tabs->clearTargets();
		$this->tabs->setBackTarget($this->lng->txt('rep_robj_xrs_back_to_floor_plans'), $this->ctrl->getLinkTarget($this, 'render'));
		$form = $this->initForm();
		$this->tpl->setContent($form->getHTML());
	}

	/**
	 * This function is called whenever a "save" for a new floor plan is
	 * initiated.
	 */
	public function saveObject()
	{
		if (!$this->permission->checkPrivilege(PRIVC::ADD_FLOORPLANS))
		{
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_no_permission_for_action"));
			$this->ctrl->redirectByClass('ilinfoscreengui', 'showSummary', 'showSummary');
			return FALSE;
		}
		$form = $this->initForm();
		if ($form->checkInput()) // is everything fine with the inputs?
		{
			$fplan = new ilRoomSharingFloorPlans($this->pool_id, new ilRoomsharingDatabase($this->pool_id));
			$title_new = $form->getInput("title");
			$desc_new = $form->getInput("description");
			$file_new = $form->getInput("upload_file");

			try
			{
				$fplan->addFloorPlan($title_new, $desc_new, $file_new);
				ilUtil::sendSuccess($this->lng->txt("rep_robj_xrs_floor_plans_added"), true);
				$this->renderObject();
			}
			catch (ilRoomSharingFloorplanException $exc)
			{
				ilUtil::sendFailure($this->lng->txt($exc->getMessage()), true);
				$this->resetView($form);
			}
		}
		else // if that's not the case, reset the old inputs
		{
			$this->resetView($form);
		}
	}

	/**
	 * Resets the current view, by reloading the template with a given form and setting special back target.
	 *
	 * @param ilPropertyFormGUI $a_form
	 */
	private function resetView(ilPropertyFormGUI $a_form)
	{
		$this->tabs->clearTargets();
		$this->tabs->setBackTarget($this->lng->txt('rep_robj_xrs_back_to_floor_plans'), $this->ctrl->getLinkTarget($this, 'render'));
		$a_form->setValuesByPost();
		$this->tpl->setContent($a_form->getHTML());
	}

	/**
	 * Before deleting a floor plan, the user has to confirm it via a
	 * confirmation GUI which is created in this function.
	 */
	public function confirmDeleteObject()
	{
		if (!$this->permission->checkPrivilege(PRIVC::DELETE_FLOORPLANS))
		{
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_no_permission_for_action"));
			$this->ctrl->redirectByClass('ilinfoscreengui', 'showSummary', 'showSummary');
			return FALSE;
		}
		$this->tabs->clearTargets();
		$this->tabs->setBackTarget($this->lng->txt('rep_robj_xrs_back_to_floor_plans'), $this->ctrl->getLinkTarget($this, 'render'));

		$cgui = new ilConfirmationGUI();
		$cgui->setFormAction($this->ctrl->getFormAction($this));

		$fplan = $fplan = new ilRoomSharingFloorPlans($this->pool_id, new ilRoomsharingDatabase($this->pool_id));

		// check if floorplan is associated to a room
		$allRooms = $fplan->getRoomsWithFloorplan((int) $_GET['file_id']);
		foreach ($allRooms as $room)
		{
			$rooms[] = $room["name"];
		}
		if (empty($rooms))
		{
			$cgui->setHeaderText($this->lng->txt("info_delete_sure"));
		}
		else
		{
			$roomsWithPlan = implode(", ", $rooms);
			$cgui->setHeaderText($this->lng->txt("info_delete_sure") .
				'<br>' . $this->lng->txt("rep_robj_xrs_floor_plans_room_assoc") .
				' ' . $roomsWithPlan);
		}
		// the buttons for confirming and cancelling the deletion
		$cgui->setCancel($this->lng->txt("cancel"), "render");
		$cgui->setConfirm($this->lng->txt("delete"), "removeFloorplan");

		// the table which includes the thumbnail picture and the title
		$mobj = new ilObjMediaObject((int) $_GET['file_id']);
		$med = $mobj->getMediaItem("Standard");
		$target = $med->getThumbnailTarget();
		$cgui->addItem('file_id', (int) $_GET['file_id'], $mobj->getTitle(), $target);

		$this->tpl->setContent($cgui->getHTML());
	}

	/**
	 * If the deletion is confirmed, this function is called.
	 */
	public function removeFloorplanObject()
	{
		if (!$this->permission->checkPrivilege(PRIVC::DELETE_FLOORPLANS))
		{
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_no_permission_for_action"));
			$this->ctrl->redirectByClass('ilinfoscreengui', 'showSummary', 'showSummary');
			return FALSE;
		}
		$fplan = $fplan = new ilRoomSharingFloorPlans($this->pool_id, new ilRoomsharingDatabase($this->pool_id));
		$result = $fplan->deleteFloorPlan((int) $_POST['file_id']);
		if ($result)
		{
			ilUtil::sendSuccess($this->lng->txt("rep_robj_xrs_floor_plans_deleted"), true);
		}
		else
		{
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_floor_plans_deleted_error"), true);
		}
		$this->renderObject();
	}

	/**
	 * The editing of floor plan information (title, description, file) is
	 * handled by this function. The user has the option of keeping the
	 * existing floor plan or creating a new one.
	 *
	 * @global type $ilTabs the tab gui of ilias
	 */
	public function editFloorplanObject()
	{
		if (!$this->permission->checkPrivilege(PRIVC::EDIT_FLOORPLANS))
		{
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_no_permission_for_action"));
			$this->ctrl->redirectByClass('ilinfoscreengui', 'showSummary', 'showSummary');
			return FALSE;
		}

		$this->tabs->clearTargets();
		$this->tabs->setBackTarget($this->lng->txt('rep_robj_xrs_back_to_floor_plans'), $this->ctrl->getLinkTarget($this, 'render'));
		$fid = (int) $_GET['file_id'];
		$form = $this->initForm($a_mode = "edit", $fid);
		$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'file_id', $fid);
		$this->tpl->setContent($form->getHTML());
	}

	/**
	 * This function saves the updated information of a floorplan. In order for
	 * this function to be called a user has to click the 'save' button in the
	 * edit form.
	 */
	public function updateObject()
	{
		if (!$this->permission->checkPrivilege(PRIVC::EDIT_FLOORPLANS))
		{
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_no_permission_for_action"));
			$this->ctrl->redirectByClass('ilinfoscreengui', 'showSummary', 'showSummary');
			return FALSE;
		}
		$file_id = (int) $_POST['file_id'];
		$form = $this->initForm($a_mode = "edit", $file_id);
		if ($form->checkInput()) // make you sure the input is correctF
		{
			$fplan = new ilRoomSharingFloorPlans($this->pool_id, new ilRoomsharingDatabase($this->pool_id));
			$title_new = $form->getInput("title");
			$desc_new = $form->getInput("description");

			try
			{
				if ($form->getInput("file_mode") === "keep")
				{
					$fplan->updateFloorPlanInfos($file_id, $title_new, $desc_new);
				}
				else // create a new file, if the current floor plan shouldn't be kept
				{
					$file_new = $form->getInput("upload_file");
					$fplan->updateFloorPlanInfosAndFile($file_id, $title_new, $desc_new, $file_new);
				}
				ilUtil::sendSuccess($this->lng->txt("rep_robj_xrs_floor_plans_edited"), true);
				$this->renderObject();
			}
			catch (ilRoomSharingFloorplanException $exc)
			{
				ilUtil::sendFailure($this->lng->txt($exc->getMessage()), true);
				$this->resetView($form);
			}
		}
		else // if that's not the case, restore the old input values
		{
			$this->resetView($form);
		}
	}

	/**
	 * Returns roomsharing pool id.
	 * @return type pool id as integer
	 */
	public function getPoolId()
	{
		return $this->pool_id;
	}

	/**
	 * Sets roomsharing pool id.
	 * @param type $a_pool_id the given pool id
	 */
	public function setPoolId($a_pool_id)
	{
		$this->pool_id = $a_pool_id;
	}

}
?>
