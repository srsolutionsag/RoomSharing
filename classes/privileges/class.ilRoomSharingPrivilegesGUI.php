<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingClassPrivilegesTableGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingClassGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivilegesConstants.php");
require_once("Services/Form/classes/class.ilPropertyFormGUI.php");
require_once("Services/Search/classes/class.ilRepositorySearchGUI.php");

use ilRoomSharingPrivilegesConstants as PRIVC;

/**
 * Class ilRoomSharingPrivilegesGUI
 *
 * @author Alexander Keller <a.k3ll3r@gmail.com>
 * @author Robert Heimsoth <rheimsoth@stud.hs-bremen.de>
 *
 * @version $Id$
 * @property ilCtrl $ctrl
 * @property ilLanguage $lng
 * @property ilTemplate $tpl
 *
 * @ilCtrl_Calls ilRoomSharingPrivilegesGUI: ilRoomSharingClassGUI, ilRepositorySearchGUI
 */
class ilRoomSharingPrivilegesGUI
{
	protected $ref_id;
	private $pool_id;
	private $parent;
	private $ctrl;
	private $lng;
	private $tpl;
	private $tabs;
	private $privileges;
	private $user;
	private $permission;

	CONST SELECT_INPUT_NONE_OFFSET = 1;

	/**
	 * Constructor of ilRoomSharingPrivilegesGUI
	 *
	 * @global type $ilCtrl for navigating through GUI classes
	 * @global type $lng for translations
	 * @global type $tpl used for setting HTML content
	 * @global type $ilTabs for setting tabs
	 * @global type $ilUser used for determining user information
	 * @global type $rssPermission for retrieving user privilege information
	 * @param ilObjRoomSharingGUI $a_parent_obj needed for the pool id
	 */
	public function __construct(ilObjRoomSharingGUI $a_parent_obj)
	{
		global $ilCtrl, $lng, $tpl, $ilTabs, $ilUser, $rssPermission;

		$this->parent = $a_parent_obj;
		$this->ref_id = $this->parent->ref_id;
		$this->pool_id = $this->parent->getPoolId();
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		$this->tpl = $tpl;
		$this->tabs = $ilTabs;
		$this->user = $ilUser;
		$this->permission = $rssPermission;
		$this->privileges = new ilRoomSharingPrivileges($this->pool_id);
	}

	/**
	 * Executes the command given by ilCtrl.
	 */
	public function executeCommand()
	{
		$next_class = $this->ctrl->getNextClass($this);

		if ($next_class == "ilroomsharingclassgui")
		{
			$this->renderClassGui();
		}
		else
		{
			$this->executeDefaultCommand();
		}
	}

	/**
	 * Renders the GUI of the class the users wants to edit.
	 */
	private function renderClassGui()
	{
		$class_id = (int) $_GET["class_id"];
		$this->ctrl->setReturn($this, "showPrivileges");
		$this->class_gui = new ilRoomSharingClassGUI($this->parent, $class_id);
		$this->ctrl->forwardCommand($this->class_gui);
	}

	private function executeDefaultCommand()
	{
		$cmd = $this->ctrl->getCmd("showPrivileges");
		$this->$cmd();
	}

	/**
	 * Displays a toolbar for adding new classes and a table consisting of the exsisting classes and
	 * its corresponding privileges.
	 */
	public function showPrivileges()
	{
		$toolbar = $this->createToolbar();
		$class_privileges_table = new ilRoomSharingClassPrivilegesTableGUI($this, "showPrivileges",
			$this->ref_id);

		$this->tpl->setContent($toolbar->getHTML() . $class_privileges_table->getHTML());
	}

	/**
	 * The toolbar basically consists a button for adding new classes to the table. The button is
	 * only disabled if the creation of new classes is granted.
	 * @return \ilToolbarGUI
	 */
	private function createToolbar()
	{
		$toolbar = new ilToolbarGUI();

		if ($this->permission->checkPrivilege(PRIVC::ADD_CLASS))
		{
			$target = $this->ctrl->getLinkTarget($this, "renderAddClassForm");
			$toolbar->addButton($this->lng->txt("rep_robj_xrs_privileges_class_new"), $target);
		}

		return $toolbar;
	}

	/**
	 * Renders a form for adding a new class.
	 */
	private function renderAddClassForm()
	{
		$this->tabs->clearTargets();
		$class_form = $this->createAddClassForm();
		$this->tpl->setContent($class_form->getHTML());
	}

	/**
	 * Creates the form for adding new classes, appends items to it and returns it for
	 * displaying.
	 *
	 * @return \ilPropertyFormGUI
	 */
	private function createAddClassForm()
	{
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->lng->txt("rep_robj_xrs_privileges_class_new"));
		$form->addCommandButton("addClass", $this->lng->txt("rep_robj_xrs_privileges_class_new"));
		$form->addCommandButton("showPrivileges", $this->lng->txt("cancel"));
		$form_items = $this->createAddClassFormItems();

		foreach ($form_items as $item)
		{
			$form->addItem($item);
		}

		return $form;
	}

	/**
	 * Creates and collects all form items and returns them so that they can be added to the form.
	 */
	private function createAddClassFormItems()
	{
		$form_items = array();
		$form_items[] = $this->createClassNameTextInput();
		$form_items[] = $this->createClassDescriptionTextArea();
		$form_items[] = $this->createClassRoleAssignmentSelection();
		$form_items[] = $this->createClassPrioritySelection();
		$form_items[] = $this->createClassCopyPrivilegesRadioGroupIfClassesExist();

		return array_filter($form_items);
	}

	private function createClassNameTextInput()
	{
		$name_input = new ilTextInputGUI($this->lng->txt("name"), "name");
		$name_input->setSize(45);
		$name_input->setMaxLength(70);
		$name_input->setRequired(true);

		return $name_input;
	}

	private function createClassDescriptionTextArea()
	{
		$description_area = new ilTextAreaInputGUI($this->lng->txt("description"), "description");
		$description_area->setCols(40);
		$description_area->setRows(3);

		return $description_area;
	}

	private function createClassRoleAssignmentSelection()
	{
		$role_assignment_selection = new ilSelectInputGUI($this->lng->txt("rep_robj_xrs_privileges_role_assignment"),
			"role_assignment");
		$role_options = $this->createRoleAssignmentOptions();
		$role_assignment_selection->setOptions($role_options);

		return $role_assignment_selection;
	}

	private function createRoleAssignmentOptions()
	{
		$role_names = array($this->lng->txt("none"));
		$parent_roles = $this->privileges->getParentRoles();

		foreach ($parent_roles as $role_info)
		{
			$role_names[] = $role_info["title"];
		}

		return $role_names;
	}

	private function createClassPrioritySelection()
	{
		$priority_selection = new ilSelectInputGUI($this->lng->txt("rep_robj_xrs_class_priority"),
			"priority");
		$priority_levels = range(0, 9);
		$priority_selection->setOptions($priority_levels);

		return $priority_selection;
	}

	/**
	 * In case classes exist, a form input will be created, which allows the user to copy the
	 * privileges of the class of choice.
	 *
	 * @return ilRadioGroupInputGUI the input GUI for copying class privileges
	 */
	private function createClassCopyPrivilegesRadioGroupIfClassesExist()
	{
		$classes = $this->privileges->getClasses();

		if (!empty($classes))
		{
			return $this->createClassCopyPrivilegesRadioGroupForClasses($classes);
		}
	}

	private function createClassCopyPrivilegesRadioGroupForClasses($a_class_array)
	{
		$class_to_copy = new ilRadioGroupInputGUI($this->lng->txt("rep_robj_xrs_privileges_copy_privileges"),
			"copied_class_privileges");
		$empty_option = new ilRadioOption($this->lng->txt("none"), 0);
		$class_to_copy->addOption($empty_option);

		foreach ($a_class_array as $class_row)
		{
			$copy_option = new ilRadioOption($class_row["name"], $class_row["id"], $class_row["description"]);
			$class_to_copy->addOption($copy_option);
		}

		return $class_to_copy;
	}

	/**
	 * Tries to save the inputs of the class form and acts accordingly.
	 */
	private function addClass()
	{
		$class_form = $this->createAddClassForm();
		if ($class_form->checkInput())
		{
			$this->evaluateAddClassFormEntries($class_form);
		}
		else
		{
			$this->handleInvalidAddClassForm($class_form);
		}
	}

	/**
	 * Saves the new properties of the class if the form inputs were valid and displays the
	 * privileges with the newly created class.
	 *
	 * @param $a_class_form the class form for which the values should be saved.
	 */
	private function evaluateAddClassFormEntries($a_class_form)
	{
		$class_form_entries = $this->getClassFormEntries($a_class_form);
		$class_name = $class_form_entries["name"];
		if ($this->isClassNameAlreadyPresent($class_name))
		{
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_privileges_class_already_exists"), true);
			$this->handleInvalidAddClassForm($a_class_form);
		}
		else
		{
			$this->saveFormEntries($class_form_entries);
			$this->showPrivileges();
		}
	}

	/**
	 * Checks whether or not a given class name already exists for this pool.
	 *
	 * @param string $a_class_name the name of the class that needs to be checked
	 * @return boolean true, if the name already exists; false otherwise
	 */
	private function isClassNameAlreadyPresent($a_class_name)
	{
		$all_class_names = $this->privileges->getClassNames();
		return in_array($a_class_name, $all_class_names);
	}

	/**
	 * In case the form input for the name of the class was empty an error message and the class
	 * form with its non erroneous entries will be displayed.
	 *
	 * @param ilPropertyFormGUI $a_class_form the class for which the invalid input should be handled
	 */
	private function handleInvalidAddClassForm($a_class_form)
	{
		$this->tabs->clearTargets();
		$a_class_form->setValuesByPost();
		$this->tpl->setContent($a_class_form->getHTML());
	}

	/**
	 * Gathers and returns the inputs of the form.
	 *
	 * @param $a_class_form the class form for which the inputs should be gathered
	 * @return array the entries as an associative array
	 */
	private function getClassFormEntries($a_class_form)
	{
		$entries = array();
		$entries["name"] = $a_class_form->getInput("name");
		$entries["description"] = $a_class_form->getInput("description");
		$entries["priority"] = $a_class_form->getInput("priority");
		$entries["role_id"] = $this->getRoleIdFromSelectionInput($a_class_form->getInput("role_assignment"));
		$entries["copied_class_privileges"] = $a_class_form->getInput("copied_class_privileges");

		return $entries;
	}

	/**
	 * Since the selection index of the selection input is off by 1, the real index of the selection
	 * and thus the id of the role must be determined.
	 *
	 * @param string $a_role_assignment_selection index of the selection
	 * @return string the id of the role that was selected
	 */
	private function getRoleIdFromSelectionInput($a_role_assignment_selection)
	{
		$global_roles = $this->privileges->getParentRoles();
		$role_array_index = $a_role_assignment_selection - self::SELECT_INPUT_NONE_OFFSET;

		return $global_roles[$role_array_index]["id"];
	}

	/**
	 * Tries to save the form entries and displays an error message if this was not possible.
	 *
	 * @param array $a_entries the entries that need to be saved
	 */
	private function saveFormEntries($a_entries)
	{
		try
		{
			$this->privileges->addClass($a_entries);
			ilUtil::sendSuccess($this->lng->txt("rep_robj_xrs_class_added_successfully"), true);
		}
		catch (ilRoomSharingPrivilegesException $ex)
		{
			ilUtil::sendFailure($this->lng->txt($ex->getMessage()), true);
		}
	}

	/**
	 * This function is called when the save button has been pushed. If critical choices have been
	 * made throughout the settings confirmation dialogs will be displayed. One of those critical
	 * choices is a possible lock of the classes the user is assigned to. If this is not the case
	 * another check for a possible revoking of the privilege of accessing and editing privileges
	 * will be made, which also results in the display of a confirmation dialog.
	 */
	private function savePrivilegeSettings()
	{
		$classes_with_ticked_locks = $_POST["lock"];
		$classes_with_ticked_privileges = $_POST["priv"];
		$class_ids_of_ticked_locks = $this->getClassIdsOfTickedLocks($classes_with_ticked_locks);
		if ($this->isLockConfirmationRequired($class_ids_of_ticked_locks))
		{
			$this->privileges->setPrivileges($classes_with_ticked_privileges);
			$this->renderConfirmPrivilegeLock($class_ids_of_ticked_locks);
		}
		else
		{
			$this->privileges->setLockedClasses($classes_with_ticked_locks);
			$this->savePrivilegesWithPossibleConfirmation($classes_with_ticked_privileges);
		}
	}

	/**
	 * Since the post variable for the class lock returns an associative array like this:
	 * [class_id] => set? (e.g. [[2] => 1, [3] => 1)
	 * a conversion is made which simply returns the keys of that array and thus the ids of the
	 * classes that have been ticked.
	 * @param array $a_classes_with_ticked_locks an array with the class id as key and a value of 1
	 * if the lock checkbox of this class has been set
	 * @return array an array which contains all class that should be locked
	 */
	private function getClassIdsOfTickedLocks($a_classes_with_ticked_locks)
	{
		if (empty($a_classes_with_ticked_locks))
		{
			return array();
		}
		else
		{
			return array_keys($a_classes_with_ticked_locks);
		}
	}

	/**
	 * Determines if the displayment of the lock confirmation dialog is required. This is the case
	 * if at least one checkbox for locking has been ticked and if new locks have been set.
	 *
	 * @param array $a_class_ids_of_ticked_locks
	 * @return boolean true if a confirmation is required; false otherwise
	 */
	private function isLockConfirmationRequired($a_class_ids_of_ticked_locks)
	{
		return !empty($a_class_ids_of_ticked_locks) && $this->areNewLocksSet($a_class_ids_of_ticked_locks);
	}

	/**
	 * Determines if new locks have been set. New means that the checkbox for locking class
	 * privileges has been set from unticked to ticked.
	 *
	 * @param array $a_class_ids_of_ticked_locks
	 * @return boolean true if new locks have been set; false otherwise
	 */
	private function areNewLocksSet($a_class_ids_of_ticked_locks)
	{
		$new_locked_class_ids = $this->getNewLockedClassIds($a_class_ids_of_ticked_locks);

		return !empty($new_locked_class_ids);
	}

	/**
	 * Renders the confirmation dialog for a class lock by creating it and displaying appropriate
	 * warning and info messages.
	 *
	 * @param array $a_class_ids_of_ticked_locks the ids of the classes that should be locked
	 */
	private function renderConfirmPrivilegeLock($a_class_ids_of_ticked_locks)
	{
		$this->tabs->clearTargets();
		$this->tabs->setBackTarget($this->lng->txt("rep_robj_xrs_privileges_lock_confirm_back"),
			$this->ctrl->getLinkTarget($this, "showPrivileges"));

		$confirmation_dialog = $this->createPrivilegeLockConfirmationDialog($a_class_ids_of_ticked_locks);
		$this->displayPrivilegeLockConfirmationMessages($a_class_ids_of_ticked_locks);
		$this->tpl->setContent($confirmation_dialog->getHTML());
	}

	/**
	 * Creates the confirmation dialog for locking class privileges by adding the names of the
	 * classes that should be locked and by setting a json encoded post variable which includes
	 * all of the ids of the classes were locked before and should be locked now.
	 *
	 * @param array $a_class_ids_of_ticked_locks the ids of the classes that should be locked
	 * @return \ilConfirmationGUI
	 */
	private function createPrivilegeLockConfirmationDialog($a_class_ids_of_ticked_locks)
	{
		$confirmation_dialog = new ilConfirmationGUI();
		$confirmation_dialog->setFormAction($this->ctrl->getFormAction($this));
		$confirmation_dialog->setHeaderText($this->lng->txt("rep_robj_xrs_privileges_confirm_class_lock_question"));
		$new_locked_class_ids = $this->getNewLockedClassIds($a_class_ids_of_ticked_locks);

		foreach ($new_locked_class_ids as $class_id)
		{
			$confirmation_dialog->addItem("new_locked_class_ids", "",
				$this->privileges->getClassById($class_id)["name"]);
		}
		$confirmation_dialog->addHiddenItem("locked_classes", json_encode($a_class_ids_of_ticked_locks));

		$confirmation_dialog->setConfirm($this->lng->txt("rep_robj_xrs_privileges_confirm_class_lock"),
			"lockClassesAfterConfirmation");
		$confirmation_dialog->setCancel($this->lng->txt("cancel"), "showPrivileges");

		return $confirmation_dialog;
	}

	/**
	 * Only the newly set names of the classes that should be locked should be displayed in the
	 * confirmation dialog. This method returns the ids of those classes.
	 *
	 * @param array $a_class_ids_of_ticked_locks the ids of the classes that should be locked
	 * @return array the ids of the newly locked classes
	 */
	private function getNewLockedClassIds($a_class_ids_of_ticked_locks)
	{
		$unlocked_class_ids = $this->privileges->getUnlockedClasses();
		$new_locked_class_ids = array_intersect($a_class_ids_of_ticked_locks, $unlocked_class_ids);

		return $new_locked_class_ids;
	}

	/**
	 * This method displays messages in the lock confirmation dialog. These messages consist of
	 * an info message which explains the risks of the locking mechanism and warning message which
	 * will only be displayed if a class is about to be locked to where the user is assigned to.
	 *
	 * @param array $a_class_ids_of_ticked_locks the ids of the classes that should be locked
	 */
	private function displayPrivilegeLockConfirmationMessages($a_class_ids_of_ticked_locks)
	{
		ilUtil::sendInfo($this->lng->txt("rep_robj_xrs_privileges_confirm_class_lock_info"));

		if ($this->areOwnClassesToBeLocked($a_class_ids_of_ticked_locks))
		{
			$own_class_names_to_be_locked = $this->getOwnClassNamesToBeLocked($a_class_ids_of_ticked_locks);
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_privileges_confirm_class_self_lock_of")
				. " " . implode(", ", $own_class_names_to_be_locked));
		}
	}

	/**
	 * Checks whether the user is about to lock a class he is assigned to.
	 *
	 * @param array $a_class_ids_of_ticked_locks the ids of the classes that should be locked
	 * @return boolean true if the user is assigned to at least one of the classes; false otherwise
	 */
	private function areOwnClassesToBeLocked($a_class_ids_of_ticked_locks)
	{
		$own_class_ids_to_be_locked = $this->getOwnClassIdsToBeLocked($a_class_ids_of_ticked_locks);
		return !empty($own_class_ids_to_be_locked);
	}

	/**
	 * Determines and returns the class ids of the classes that are about to be locked and to where
	 * a user is assgined to.
	 *
	 * @param array $a_class_ids_of_ticked_locks the ids of the classes that should be locked
	 * @return array an array containing the class id of the classes the user is assigned to
	 */
	private function getOwnClassIdsToBeLocked($a_class_ids_of_ticked_locks)
	{
		$new_locked_class_ids = $this->getNewLockedClassIds($a_class_ids_of_ticked_locks);
		$own_class_ids = $this->privileges->getAssignedClassesForUser($this->user->getId());
		$own_class_ids_to_be_locked = array_intersect($new_locked_class_ids, $own_class_ids);

		return $own_class_ids_to_be_locked;
	}

	/**
	 * Returns the names of the classes the user is assigned to.
	 *
	 * @param $a_class_ids_of_ticked_locks the ids of the classes that should be locked
	 * @return array the class names
	 */
	private function getOwnClassNamesToBeLocked($a_class_ids_of_ticked_locks)
	{
		$own_class_ids_to_be_locked = $this->getOwnClassIdsToBeLocked($a_class_ids_of_ticked_locks);
		$own_class_names_to_be_locked = array();

		foreach ($own_class_ids_to_be_locked as $class_id)
		{
			$own_class_names_to_be_locked[] = $this->privileges->getClassById($class_id)["name"];
		}

		return $own_class_names_to_be_locked;
	}

	/**
	 * Used for locking classes after the corresponding confirmation dialog has been confirmed.
	 */
	public function lockClassesAfterConfirmation()
	{
		$locked_class_ids = json_decode($_POST["locked_classes"]);
		$class_ids_with_ticked_locks = array_flip($locked_class_ids);
		$this->privileges->setLockedClasses($class_ids_with_ticked_locks);
		ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);

		$this->ctrl->redirect($this);
	}

	/**
	 * If a user is about to revoke the privileges of either accessing or editing privileges of a
	 * class he is assigned to a special confirmation dialog will be displayed; otherwise the
	 * privileges will be saved as is.
	 *
	 * @param array $a_classes_with_ticked_privileges an associative array containing the class ids
	 * and its corresponding set privileges
	 */
	private function savePrivilegesWithPossibleConfirmation($a_classes_with_ticked_privileges)
	{
		if ($this->isOwnAccessingAndEditingOfPrivilegesEndangered($a_classes_with_ticked_privileges))
		{
			$this->renderConfirmRevokingOfAccessingAndEditingPrivileges($a_classes_with_ticked_privileges);
		}
		else
		{
			$this->savePrivilegesWithoutConfirmation($a_classes_with_ticked_privileges);
		}
	}

	/**
	 * Determines whether or not the user is about to revoke his privileges of accessing and editing
	 * privileges.
	 *
	 * @param array $a_classes_with_ticked_privileges an associative array containing the class ids
	 * and its corresponding set privileges
	 * @return boolean true, if danger is ahead; false otherwise
	 */
	private function isOwnAccessingAndEditingOfPrivilegesEndangered($a_classes_with_ticked_privileges)
	{
		$endangered_class_ids = $this->getClassIdsForEndangeredPrivilegeOfAccessingAndEditingPrivileges($a_classes_with_ticked_privileges);

		return !empty($endangered_class_ids);
	}

	/**
	 * Renders the confirmation dialog for the privilege revoking of accessing and/or editing
	 * privileges. The dialog will be created and displayed.
	 *
	 * @param array $a_classes_with_ticked_privileges an associative array containing the class ids
	 * and its corresponding set privileges
	 */
	private function renderConfirmRevokingOfAccessingAndEditingPrivileges($a_classes_with_ticked_privileges)
	{
		$this->tabs->clearTargets();
		$this->tabs->setBackTarget($this->lng->txt("rep_robj_xrs_privileges_lock_confirm_back"),
			$this->ctrl->getLinkTarget($this, "showPrivileges"));

		$confirmation_dialog = $this->createRevokePrivilegeOfAccessingAndEditingPriviligesConfirmationDialog($a_classes_with_ticked_privileges);
		ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_privileges_confirm_privilege_revoking_info"));
		$this->tpl->setContent($confirmation_dialog->getHTML());
	}

	/**
	 * Creates the confirmation dialog for revoking privileges by adding the names of the involved
	 * classes and by setting a json encoded post variable which includes all classes and the
	 * privileges of those classes that should be set. After the json_encoding all double quotes
	 * will be escaped ("" -> &quot;) or otherwise the encoded string would be cut off after
	 * retrieving it.
	 *
	 * @param array $a_classes_with_ticked_privileges an associative array containing the class ids
	 * and its corresponding set privileges
	 * @return \ilConfirmationGUI
	 */
	private function createRevokePrivilegeOfAccessingAndEditingPriviligesConfirmationDialog($a_classes_with_ticked_privileges)
	{
		$confirmation_dialog = new ilConfirmationGUI();
		$confirmation_dialog->setFormAction($this->ctrl->getFormAction($this));
		$confirmation_dialog->setHeaderText($this->lng->txt("rep_robj_xrs_privileges_confirm_privilege_revoking_question"));
		$endangered_class_ids = $this->getClassIdsForEndangeredPrivilegeOfAccessingAndEditingPrivileges($a_classes_with_ticked_privileges);

		foreach ($endangered_class_ids as $class_id)
		{
			$confirmation_dialog->addItem("", "", $this->privileges->getClassById($class_id)["name"]);
		}
		$post_privileges = htmlspecialchars(json_encode($a_classes_with_ticked_privileges));
		$confirmation_dialog->addHiddenItem("confirmed_privileges", $post_privileges);

		$confirmation_dialog->setConfirm($this->lng->txt("rep_robj_xrs_privileges_confirm_privilege_revoking"),
			"revokeAccessingAndEditingPrivilegesAfterConfirmation");
		$confirmation_dialog->setCancel($this->lng->txt("cancel"), "showPrivileges");

		return $confirmation_dialog;
	}

	/**
	 * Determines and returns all class ids where the user is assigned to and where the user is
	 * about to revoke the privileges of accessing and/or editing the privileges. Here only classes
	 * that are unlocked will be considered.
	 *
	 * @param array $a_classes_with_ticked_privileges an associative array containing the class ids
	 * and its corresponding set privileges
	 * @return array the endangered class ids
	 */
	private function getClassIdsForEndangeredPrivilegeOfAccessingAndEditingPrivileges($a_classes_with_ticked_privileges)
	{
		$possibly_endangered_class_ids = $this->getOwnAndUnlockedClassIds();
		$all_class_ids_with_privileges = $this->privileges->getAllClassPrivileges();

		$endangered_class_ids = array();
		foreach ($possibly_endangered_class_ids as $class_id)
		{
			$ticked_privileges_of_endangered_class_to_be_set = !empty($a_classes_with_ticked_privileges[$class_id])
					? $a_classes_with_ticked_privileges[$class_id] : array();
			$current_privileges_of_possibly_endangered_class = $all_class_ids_with_privileges[$class_id];
			$privileges_of_endangered_class_to_be_set = array_keys($ticked_privileges_of_endangered_class_to_be_set);
			$privileges_to_be_unset = $this->determinePrivilegesToBeUnset($current_privileges_of_possibly_endangered_class,
				$privileges_of_endangered_class_to_be_set);

			if ($this->isEndangeredOfRevokingAccessingAndEditingPrivileges($privileges_to_be_unset))
			{
				$endangered_class_ids[] = $class_id;
			}
		}

		return $endangered_class_ids;
	}

	/**
	 * Returns the class the class ids of the classes that are unlocked and the user is assigned to.
	 *
	 * @return array said array
	 */
	private function getOwnAndUnlockedClassIds()
	{
		$unlocked_class_ids = $this->privileges->getUnlockedClasses();
		$assigned_user_classes = $this->privileges->getAssignedClassesForUser($this->user->getId());
		$possibly_endangered_class_ids = array_intersect($unlocked_class_ids, $assigned_user_classes);

		return $possibly_endangered_class_ids;
	}

	/**
	 * Determines all privileges that are not checked. This is done by using array_udiff and the
	 * parameter "strcasecmp" which allows for a case insensitive diff. This is needed since the
	 * current privileges are returned in lower case by the database.
	 *
	 * @param array $a_current_privileges the current privileges in lower case
	 * @param type $a_privileges_to_be_set the privileges that are about to be set in camel case
	 * @return type
	 */
	private function determinePrivilegesToBeUnset($a_current_privileges, $a_privileges_to_be_set)
	{
		$privileges_to_be_unset = array_udiff($a_current_privileges, $a_privileges_to_be_set, "strcasecmp");

		return $privileges_to_be_unset;
	}

	/**
	 * Checks whether or not the privilege of accessing and/or editing the privileges is about to
	 * be unset.
	 * @param array $a_privileges_to_be_unset the privileges that are about to be unset
	 * @return boolean true if those privileges are about to be revoked; false otherwise
	 */
	private function isEndangeredOfRevokingAccessingAndEditingPrivileges($a_privileges_to_be_unset)
	{
		return in_array(strtolower(PRIVC::ACCESS_PRIVILEGES), $a_privileges_to_be_unset) || in_array(strtolower(PRIVC::EDIT_PRIVILEGES),
				$a_privileges_to_be_unset);
	}

	/**
	 * Used for revoking the privileges of accessing and/or editing the privileges after the
	 * corresponding confirmation dialog has been confirmed.
	 */
	public function revokeAccessingAndEditingPrivilegesAfterConfirmation()
	{
		$classes_with_ticked_privileges = json_decode($_POST["confirmed_privileges"]);
		$this->privileges->setPrivileges($classes_with_ticked_privileges);
		ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);

		$this->ctrl->redirect($this);
	}

	/**
	 * If not confirmation for saving the privileges is required they will simply be saved.
	 *
	 * @param array $a_classes_with_ticked_privileges an associative array containing the class ids
	 * and its corresponding set privileges
	 */
	private function savePrivilegesWithoutConfirmation($a_classes_with_ticked_privileges)
	{
		$this->privileges->setPrivileges($a_classes_with_ticked_privileges);
		ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
		$this->ctrl->redirect($this);
	}

	/**
	 * Displays a success message and the privileges table after the deletion of a class.
	 */
	public function showConfirmedClassDeletion()
	{
		ilUtil::sendSuccess($this->lng->txt("rep_robj_xrs_class_deletion_successful"));
		$this->showPrivileges();
	}

	/**
	 * Returns the RoomSharing Pool Id.
	 *
	 * @return integer Pool-ID
	 */
	public function getPoolId()
	{
		return $this->pool_id;
	}

	/**
	 * Sets the RoomSharing Pool Id.
	 *
	 * @param integer Pool-ID
	 */
	public function setPoolId($a_pool_id)
	{
		$this->pool_id = $a_pool_id;
	}

}

?>
