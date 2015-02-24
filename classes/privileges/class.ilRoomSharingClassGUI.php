<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingAssignedUsersTableGUI.php");
require_once("Services/Utilities/classes/class.ilConfirmationGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivilegesConstants.php");

use ilRoomSharingPrivilegesConstants as PRIVC;

/**
 * Class ilRoomSharingClassGUI
 *
 * @author       Alexander Keller <a.k3ll3r@gmail.com>
 *
 * @version      $Id$
 *
 * @ilCtrl_Calls ilRoomSharingClassGUI: ilRepositorySearchGUI
 */
class ilRoomSharingClassGUI {

	private $parent;
	private $class_id;
	private $pool_id;
	private $ctrl;
	private $lng;
	private $tpl;
	private $tabs;
	private $privileges;
	private $permission;


	/**
	 * Constructor of ilRoomSharingClassGUI
	 *
	 * @global type $ilCtrl
	 * @global type $lng
	 * @global type $tpl
	 * @global type $ilTabs        needed since this class represents its own distinct gui with unique tabs
	 * @global type $rssPermission for retrieving privilege information of a user
	 *
	 * @param type  $a_parent
	 * @param type  $a_class_id    id of the class for which this GUI is generated
	 */
	public function __construct($a_parent, $a_class_id) {
		global $ilCtrl, $lng, $tpl, $ilTabs, $rssPermission;

		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		$this->tpl = $tpl;
		$this->parent = $a_parent;
		$this->tabs = $ilTabs;
		$this->permission = $rssPermission;
		$this->pool_id = $this->parent->getPoolId();
		$this->class_id = $a_class_id ? $a_class_id : $_GET["class_id"];
		$this->ctrl->saveParameter($this, "class_id");
		$this->privileges = new ilRoomSharingPrivileges($this->pool_id);
	}


	/**
	 * Executes the command given by ilCtrl.
	 */
	public function executeCommand() {
		$this->renderPageWithTabs();
		$next_class = $this->ctrl->getNextClass($this);

		if ($next_class == "ilrepositorysearchgui") {
			$this->renderRepositorySearch();
		} else {
			$this->executeDefaultCommand();
		}
	}


	/**
	 * Renders the Class GUI with its icons, title, subtitle and the tabs.
	 */
	private function renderPageWithTabs() {
		$class_info = $this->privileges->getClassById($this->class_id);
		$this->tpl->setTitle($class_info["name"]);
		$description = $class_info["description"];
		$this->tpl->setDescription($description);
//		$this->tpl->setTitleIcon(ilUtil::getImagePath("icon_role_b.png"), $this->lng->txt("rep_robj_xrs_class"));
		$this->tpl->setTitleIcon(ilUtil::getImagePath("icon_role_b.svg"), $this->lng->txt("rep_robj_xrs_class"));
		$this->setTabs();
	}


	private function setTabs() {
		$this->tabs->clearTargets();
		$this->tabs->setBackTarget($this->lng->txt("rep_robj_xrs_privileges"), $this->ctrl->getLinkTargetByClass("ilroomsharingprivilegesgui", "showPrivileges"));

		$this->tabs->addTab("edit_properties", $this->lng->txt("edit_properties"), $this->ctrl->getLinkTarget($this, "renderEditClassForm"));

		$this->tabs->addTab("user_assignment", $this->lng->txt("user_assignment"), $this->ctrl->getLinkTarget($this, "renderUserAssignment"));
	}


	/**
	 * Renders the RepositorySearchGUI which is used for assigning users to a class.
	 */
	private function renderRepositorySearch() {
		$rep_search = &new ilRepositorySearchGUI();
		$rep_search->setTitle($this->lng->txt("role_add_user"));
		$rep_search->setCallback($this, "assignUsersToClass");
		$this->tabs->setTabActive("user_assignment");
		$this->ctrl->setReturn($this, "renderUserAssignment");
		$this->ctrl->forwardCommand($rep_search);
	}


	private function executeDefaultCommand() {
		$cmd = $this->ctrl->getCmd("renderEditClassForm");
		$this->$cmd();
	}


	/**
	 * Renders a form for editing the properties of a class.
	 */
	private function renderEditClassForm() {
		$this->tabs->setTabActive("edit_properties");

		$toolbar = $this->createEditClassFormToolbar();
		$class_form = $this->createEditClassFormWithPrivilegeCheck();
		$this->tpl->setContent($toolbar->getHTML() . $class_form->getHTML());
	}


	/**
	 * Creates a toolbar for the form which is used for editing class properties. A button for
	 * deleting the corresponding class is added if a user has the privilege of doing so.
	 *
	 * @return \ilToolbarGUI
	 */
	private function createEditClassFormToolbar() {
		$toolbar = new ilToolbarGUI();

		if ($this->permission->checkPrivilege(PRIVC::DELETE_CLASS)) {
			$toolbar->addButton($this->lng->txt("rep_robj_xrs_class_confirm_deletion"), $this->ctrl->getLinkTarget($this, "renderConfirmClassDeletion"));
		}

		return $toolbar;
	}


	private function createEditClassFormWithPrivilegeCheck() {
		if ($this->permission->checkPrivilege(PRIVC::EDIT_CLASS)) {
			$form = $this->createEditClassForm();

			return $form;
		} else {
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_no_permission"));
		}
	}


	/**
	 * Creates the form for editing class properties, appends items to it and returns it for
	 * displaying.
	 *
	 * @return \ilPropertyFormGUI
	 */
	private function createEditClassForm() {
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->lng->txt("rep_robj_xrs_privileges_class_edit"));
		$form->addCommandButton("saveEditClassForm", $this->lng->txt("save"));
		$form_items = $this->createEditClassFormItems();

		foreach ($form_items as $item) {
			$form->addItem($item);
		}

		return $form;
	}


	/**
	 * Creates and collects all form items and returns them so that they can be added to the form.
	 */
	private function createEditClassFormItems() {
		$class_info = $this->privileges->getClassById($this->class_id);

		$form_items = array();
		$form_items[] = $this->createClassNameTextInput($class_info["name"]);
		$form_items[] = $this->createClassDescriptionTextArea($class_info["description"]);
		$form_items[] = $this->createClassRoleAssignmentSelection($class_info["role_id"]);
		$form_items[] = $this->createClassPrioritySelection($class_info["priority"]);

		return $form_items;
	}


	private function createClassNameTextInput($a_class_name_value) {
		$name_input = new ilTextInputGUI($this->lng->txt("name"), "name");
		$name_input->setSize(45);
		$name_input->setMaxLength(70);
		$name_input->setRequired(true);
		$name_input->setValue($a_class_name_value);

		return $name_input;
	}


	private function createClassDescriptionTextArea($a_description_value) {
		$description_area = new ilTextAreaInputGUI($this->lng->txt("description"), "description");
		$description_area->setCols(40);
		$description_area->setRows(3);
		$description_area->setValue($a_description_value);

		return $description_area;
	}


	private function createClassRoleAssignmentSelection($a_assigned_role_id) {
		$role_assignment_selection = new ilSelectInputGUI($this->lng->txt("rep_robj_xrs_privileges_role_assignment"), "role_assignment");

		$role_options = $this->createRoleAssignmentOptions();
		$role_assignment_selection->setOptions($role_options);
		$selection_index = $this->determineSelectionIndex($a_assigned_role_id);
		$role_assignment_selection->setValue($selection_index);

		return $role_assignment_selection;
	}


	/**
	 * Creates and returns the option entries for the role assignment.
	 */
	private function createRoleAssignmentOptions() {
		$role_names = array( $this->lng->txt("none") );
		$global_roles = $this->privileges->getParentRoles();

		foreach ($global_roles as $role_info) {
			$role_names[] = $role_info["title"];
		}

		return $role_names;
	}


	/**
	 * Determines the selection index for role assignment selection by getting the index through
	 * the role id and adding a value of 1 to it. This is required because a "none"-value is
	 * added to the options, which makes the index off by one value.
	 *
	 * @param $a_assigned_role_id the role id for which the selection index should be determined
	 *
	 * @return the selection index
	 */
	private function determineSelectionIndex($a_assigned_role_id) {
		$selection_index = $this->getSelectionIndexByRoleId($a_assigned_role_id);
		$selection_index += ilRoomSharingPrivilegesGUI::SELECT_INPUT_NONE_OFFSET;

		return $selection_index;
	}


	/**
	 * Gets the selection index for the role assignment by iterating through all parent roles.
	 *
	 * @param $a_assigned_role_id the role id for which the selection index should be determined
	 *
	 * @return the index of the array where the id was found
	 */
	private function getSelectionIndexByRoleId($a_assigned_role_id) {
		$parent_roles = $this->privileges->getParentRoles();
		$selection_index = - 1;

		foreach ($parent_roles as $role_index => $role_info) {
			if ($role_info["id"] == $a_assigned_role_id) {
				$selection_index = $role_index;
				break;
			}
		}

		return $selection_index;
	}


	private function createClassPrioritySelection($a_class_priority_selection_value) {
		$priority_selection = new ilSelectInputGUI($this->lng->txt("rep_robj_xrs_class_priority"), "priority");
		$priority_levels = range(0, 9);
		$priority_selection->setOptions($priority_levels);
		$priority_selection->setValue($a_class_priority_selection_value);

		return $priority_selection;
	}


	/**
	 * Tries to save the inputs of the class form and acts accordingly.
	 */
	private function saveEditClassForm() {
		$class_form = $this->createEditClassFormWithPrivilegeCheck();
		if ($class_form->checkInput()) {
			$this->evaluateEditClassFormEntries($class_form);
		} else {
			$this->handleInvalidEditClassForm($class_form);
		}
	}


	/**
	 * Saves the new properties of the class if the form inputs were valid and displays the class
	 * form with the newly set properties.
	 *
	 * @param $a_class_form the class form for which the values should be saved.
	 */
	private function evaluateEditClassFormEntries($a_class_form) {
		$class_form_entries = $this->getClassFormEntries($a_class_form);
		$class_name = $class_form_entries["name"];
		if ($this->isClassNameAlreadyPresent($class_name)) {
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_privileges_class_already_exists"), true);
			$this->handleInvalidEditClassForm($a_class_form);
		} else {
			$this->handleValidEditClassFormEntries($class_form_entries);
		}
	}


	/**
	 * Gathers and returns the inputs of the form.
	 *
	 * @param $a_class_form the class form for which the inputs should be gathered
	 *
	 * @return array the entries as in the shape of an associative array
	 */
	private function getClassFormEntries($a_class_form) {
		$entries = array();
		$entries["id"] = $this->class_id;
		$entries["name"] = $a_class_form->getInput("name");
		$entries["priority"] = $a_class_form->getInput("priority");
		$entries["description"] = $a_class_form->getInput("description");
		$entries["role_id"] = $this->getRoleIdFromSelectionInput($a_class_form->getInput("role_assignment"));

		return $entries;
	}


	/**
	 * Since the selection index of the selection input is off by 1, the real index of the selection
	 * and thus the id of the role must be determined.
	 *
	 * @param string $a_role_assignment_selection index of the selection
	 *
	 * @return string the id of the role that was selected
	 */
	private function getRoleIdFromSelectionInput($a_role_assignment_selection) {
		$global_roles = $this->privileges->getParentRoles();
		$role_array_index = $a_role_assignment_selection - ilRoomSharingPrivilegesGUI::SELECT_INPUT_NONE_OFFSET;

		return $global_roles[$role_array_index]["id"];
	}


	/**
	 * Checks whether or not a given class name already exists for this pool.
	 *
	 * @param string $a_class_name the name of the class that needs to be checked
	 *
	 * @return boolean true, if the name already exists; false otherwise
	 */
	private function isClassNameAlreadyPresent($a_class_name) {
		$all_class_names = $this->privileges->getClassNames();
		$filtered_class_names = $this->removeCurrentClassNameFromClassNamesArray($all_class_names);

		return in_array($a_class_name, $filtered_class_names);
	}


	/**
	 * Removes the current and valid class name from the class name array. This is an important
	 * thing to do, since otherwise the user would be presented with an error stating that the
	 * current name is already in use.
	 *
	 * @param array $a_all_class_names the array of whom the class is removed
	 */
	private function removeCurrentClassNameFromClassNamesArray($a_all_class_names) {
		$classById = $this->privileges->getClassById($this->class_id);
		$current_class_name = $classById["name"];
		$key = array_search($current_class_name, $a_all_class_names);
		if (isset($key)) {
			unset($a_all_class_names[$key]);
		}

		return $a_all_class_names;
	}


	/**
	 * This function saves the form entries after their successful validation
	 * and displays the orignal form again.
	 *
	 * @param array $a_class_form_entries an array which contains all the form entries
	 */
	private function handleValidEditClassFormEntries($a_class_form_entries) {
		$this->saveFormEntries($a_class_form_entries);
		$this->renderPageWithTabs();
		$this->renderEditClassForm();
	}


	/**
	 * Tries to save the form entries and displays an error message if this was not possible.
	 *
	 * @param array $a_entries the entries that need to be saved
	 */
	private function saveFormEntries($a_entries) {
		try {
			$this->privileges->editClass($a_entries);
			ilUtil::sendSuccess($this->lng->txt("saved_successfully"), true);
		} catch (ilRoomSharingPrivilegesException $ex) {
			ilUtil::sendFailure($this->lng->txt($ex->getMessage()), true);
		}
	}


	/**
	 * In case the form was empty an error message and the class form with its non erroneous
	 * entries will be displayed.
	 *
	 * @param ilPropertyFormGUI $a_class_form the class for which the invalid input should be handled
	 */
	private function handleInvalidEditClassForm($a_class_form) {
		$a_class_form->setValuesByPost();
		$toolbar = $this->createEditClassFormToolbar();
		$this->tpl->setContent($toolbar->getHTML() . $a_class_form->getHTML());
		$this->tabs->setTabActive("edit_properties");

		ilUtil::sendFailure($this->lng->txt("err_check_input"));
	}


	/**
	 * Renders the confirmation dialog for a class deletion.
	 */
	public function renderConfirmClassDeletion() {
		$this->tabs->clearTargets();
		$this->tabs->setBackTarget($this->lng->txt("rep_robj_xrs_class_back"), $this->ctrl->getLinkTarget($this, "renderEditClassForm"));
		$confirm_dialog = $this->createClassDeletionConfirmationDialog();

		$this->tpl->setContent($confirm_dialog->getHTML());
	}


	private function createClassDeletionConfirmationDialog() {
		$confirm_dialog = new ilConfirmationGUI();
		$confirm_dialog->setFormAction($this->ctrl->getFormAction($this));
		$confirm_dialog->setHeaderText($this->lng->txt("rep_robj_xrs_class_confirm_deletion_header"));

		$class_name = $this->privileges->getClassById($this->class_id);
		$confirm_dialog->addItem("class_id", $this->class_id, $class_name["name"]);
		$confirm_dialog->setConfirm($this->lng->txt("rep_robj_xrs_class_confirm_deletion"), "deleteClass");
		$confirm_dialog->setCancel($this->lng->txt("cancel"), "renderEditClassForm");

		return $confirm_dialog;
	}


	/**
	 * Deletes a class after the confirmation dialog has been confirmed.
	 */
	public function deleteClass() {
		$this->privileges->deleteClass($this->class_id);
		$this->ctrl->redirectByClass("ilroomsharingprivilegesgui", "showConfirmedClassDeletion");
	}


	/**
	 * Renders the GUI for the assignment of class users. The GUI consists of a toolbar and a table
	 * which holgs the assigned users.
	 */
	private function renderUserAssignment() {
		$this->tabs->setTabActive("user_assignment");
		$user_assignment_toolbar = $this->createUserAssignmentToolbar();
		$table = new ilRoomSharingAssignedUsersTableGUI($this, "renderUserAssignment", $this->class_id);

		$this->tpl->setContent($user_assignment_toolbar->getHTML() . $table->getHTML());
	}


	/**
	 * Creates the toolbar for assigning users. The Toolbar consists of an autocompletion text input
	 * for ILIAS-Users and a button for assigning them to the class. Furthermore a button for
	 * an extended search (groups, courses, ...) is added.
	 *
	 * @return \ilToolbarGUI
	 */
	private function createUserAssignmentToolbar() {
		$toolbar = new ilToolbarGUI();
		ilRepositorySearchGUI::fillAutoCompleteToolbar($this, $toolbar, array(
				"auto_complete_name" => $this->lng->txt('user'),
				"submit_name" => $this->lng->txt("add")
			));

		$toolbar->addSpacer();

		$toolbar->addButton($this->lng->txt("search_user"), $this->ctrl->getLinkTargetByClass("ilRepositorySearchGUI", "start"));

		return $toolbar;
	}


	/**
	 * Assigns the given users to a class if said users aren't already assigned to that class.
	 * This is a callback function from ilRepositorySearchGUI.
	 *
	 * @param type $a_user_ids the ids of the users that should be assigned to the class
	 */
	public function assignUsersToClass($a_user_ids) {
		if (empty($a_user_ids)) {
			ilUtil::sendFailure($this->lng->txt('search_err_user_not_exist'), true);
		} else {
			if ($this->areUsersAlreadyAssigned($a_user_ids)) {
				ilUtil::sendInfo($this->lng->txt("rep_robj_xrs_class_user_already_assigned"), true);
			} else {
				$this->privileges->assignUsersToClass($this->class_id, $a_user_ids);
				ilUtil::sendSuccess($this->lng->txt("rep_robj_xrs_class_assignment_successful"), true);
			}
		}

		$this->ctrl->redirect($this, "renderuserassignment");
	}


	/**
	 * In case users are already assigned to class this class returns true and false otherwise.
	 *
	 * @param array $a_user_ids the user ids for which the assignment should be checked
	 *
	 * @return boolean true if already assigned; false otherwise.
	 */
	private function areUsersAlreadyAssigned($a_user_ids) {
		$assigned_user_ids = $this->getAssignedUserIdsForClass($this->class_id);
		$unassigned_user_ids = array_intersect($a_user_ids, $assigned_user_ids);
		$new_assigned_user_ids = array_diff($a_user_ids, $unassigned_user_ids);

		return empty($new_assigned_user_ids);
	}


	/**
	 * Returns all users that are assigned to a class in order to check if they are already
	 * assigned.
	 *
	 * @param string $a_class_id the id of the class for which the assigned users should be returned
	 *
	 * @return array the user ids of the users that are assigned to that very class.
	 */
	private function getAssignedUserIdsForClass($a_class_id) {
		$assigned_user_ids = array();
		$assigned_users = $this->privileges->getAssignedUsersForClass($a_class_id);

		foreach ($assigned_users as $user) {
			$assigned_user_ids[] = $user["id"];
		}

		return $assigned_user_ids;
	}


	/**
	 * Deassigns users from a class. The ids of the users that should be deassigned are either
	 * delivered through POST (checkboxes: multiple users) or via GET (action link: single user).
	 */
	public function deassignUsersFromClass() {
		$user_ids_to_be_deassigned = $this->getUsersToBeUnassigned();
		$this->privileges->deassignUsersFromClass($this->class_id, $user_ids_to_be_deassigned);
		ilUtil::sendSuccess($this->lng->txt("rep_robj_xrs_class_deassignment_successful"), true);
		$this->renderUserAssignment();
	}


	/**
	 * Since both, a multi deassign and a single deassign are possible, a choice of those two
	 * options has to be made. That choice is made here.
	 *
	 * @return array the user ids that should be deassigned from the class
	 */
	private function getUsersToBeUnassigned() {
		$many_user_ids = $_POST["user_id"];

		if (isset($many_user_ids)) {
			return $many_user_ids;
		} else {
			$single_user_id = array( $_GET["user_id"] );

			return $single_user_id;
		}
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
