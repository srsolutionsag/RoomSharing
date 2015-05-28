<?php

require_once("Services/Table/classes/class.ilTable2GUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivileges.php");
require_once("./Services/UIComponent/Tooltip/classes/class.ilTooltipGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivilegesConstants.php");

use ilRoomSharingPrivilegesConstants as PRIVC;

/**
 * Class ilRoomSharingClassPrivilegesTableGUI
 *
 * @author  Alexander Keller <a.k3ll3r@gmail.com>
 * @author  Robert Heimsoth <rheimsoth@stud.hs-bremen.de>
 *
 * @version $Id$
 */
class ilRoomSharingClassPrivilegesTableGUI extends ilTable2GUI {

	const MAX_POPUP_TEXT_WIDTH = 30;
	private $ctrl;
	private $privileges;
	private $ref_id;
	private $permission;
	private $class_array;


	/**
	 * Constructor of ilRoomSharingClassPrivilegesTableGUI
	 *
	 * @global type $ilCtrl
	 * @global type $lng
	 * @global type $rssPermission for retrieving user privilege information
	 *
	 * @param type  $a_parent_obj
	 * @param type  $a_parent_cmd
	 * @param type  $a_ref_id
	 */
	public function __construct($a_parent_obj, $a_parent_cmd, $a_ref_id) {
		global $ilCtrl, $lng, $rssPermission;

		parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->lng = $lng;
		$this->ctrl = $ilCtrl;
		$this->ref_id = $a_ref_id;
		$this->permission = $rssPermission;
		$this->privileges = new ilRoomSharingPrivileges($a_parent_obj->getPoolId());
		$this->class_array = $this->privileges->getClasses();

		$this->setFormAction($ilCtrl->getFormAction($a_parent_obj, $a_parent_cmd));
		$this->initTableProperties();
		$this->renderSaveButton();
		$this->addColumns();
		$this->fetchPrivilegeTableData();
	}


	private function initTableProperties() {
		global $tpl;

		$this->setId('class_priv_' . $this->ref_id);
		$this->setTitle($this->lng->txt("rep_robj_xrs_privileges_settings"));
		$this->setNoEntriesText($this->lng->txt("rep_robj_xrs_privileges_class_not_available"));
		$this->setEnableHeader(true);
		$this->setLimit(100);
		$this->setShowRowsSelector(false);
		$this->setRowTemplate("tpl.room_class_privileges_row.html", "Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/");
		$tpl->addJavaScript("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/js/ilPrivilegesSelect.js");
	}


	/**
	 * Renders the save button if classes exists and the saving of privileges is granted.
	 */
	private function renderSaveButton() {
		if ($this->isSavingAllowed()) {
			$this->addCommandButton("savePrivilegeSettings", $this->lng->txt('save'));
		}
	}


	private function isSavingAllowed() {
		return !empty($this->class_array) && $this->permission->checkPrivilege(PRIVC::EDIT_PRIVILEGES);
	}


	private function addColumns() {
		foreach ($this->class_array as $class_row) {
			$this->addColumn($this->createColumnHeader($class_row), "", "", "", false);
		}
	}


	/**
	 * Creates a single column header of the privileges table, which consists of the name of the
	 * class and the role assignment.
	 *
	 * @param array $a_class_row information about the class
	 *
	 * @return string the column header
	 */
	private function createColumnHeader($a_class_row) {
		$assigned_role = $this->createAssignedRoleText($a_class_row["role"]);
		$class_name = $a_class_row["name"];
		$column_header = $class_name . $assigned_role;
		$this->ctrl->setParameterByClass("ilroomsharingclassgui", "class_id", $a_class_row["id"]);

		return $this->createClassLink($column_header);
	}


	/**
	 * In case a role assignment exists, a double arrow followed by the role name is returned.
	 *
	 * @param string $a_role_name the name of the role
	 *
	 * @return string the concatenation of a double arrow and the role name if existing
	 */
	private function createAssignedRoleText($a_role_name) {
		$right_double_arrow = " &#8658; ";
		if (isset($a_role_name)) {
			return $right_double_arrow . $a_role_name;
		}
	}


	/**
	 * Since editing classes is a privilege that can be prohibited a fallback for the edit link is
	 * needed, which basically consists of a non linkable text.
	 *
	 * @param string $a_class_text the class text
	 *
	 * @return string either a link or a normal string if the apppropriate privileges aren't set
	 */
	private function createClassLink($a_class_text) {
		if ($this->permission->checkPrivilege(PRIVC::EDIT_CLASS)) {
			$link_target = $this->ctrl->getLinkTargetByClass("ilroomsharingclassgui", "");

			return '<a class="tblheader" href="' . $link_target . '" >' . $a_class_text . "</a>";
		} else {
			return $a_class_text;
		}
	}


	private function fetchPrivilegeTableData() {
		$data = $this->privileges->getPrivilegesMatrix();

		$this->setMaxCount(count($data));
		$this->setData($data);
	}


	/**
	 * Overriden method of ilTable2GUI. Is required to force upon tooltips for the column headers.
	 */
	public function fillHeader() {
		parent::fillHeader();
		$this->addTooltips();
	}


	private function addTooltips() {
		$tbl_header_id = 1;
		foreach ($this->class_array as $class_row) {
			$tooltip_text = $this->createTooltipText($class_row);
			ilTooltipGUI::addTooltip("thc_" . $this->getId() . "_" . $tbl_header_id, $tooltip_text, "", "bottom center", "top center", false);
			$tbl_header_id ++;
		}
	}


	/**
	 * Creates the tooltip text for the column headers. Because ILIAS is very flimsy when it comes
	 * to displaying carriage returns two requirements are needed:
	 * 1. the carriage return has to be coded as "&#13;&#10;"
	 * 2. the tooltip text needs to be included in a <pre>-Tag so that the carriage retuns can be
	 * interpreted correctly.
	 *
	 * @param array $a_class_row class information
	 *
	 * @return string the tooltip text for the class
	 */
	private function createTooltipText($a_class_row) {
		$carriage_return = "&#13;&#10;";
		$class_text = $this->createTooltipClassText($a_class_row["name"]);
		$role_text = $this->createTooltipRoleText($a_class_row["role"]);
		$class_priority_text = $this->createTooltipClassPriorityText($a_class_row["priority"]);

		$tooltip_text = "<pre>" . $class_text . $carriage_return . $role_text . $carriage_return . $class_priority_text . "</pre>";

		return $tooltip_text;
	}


	private function createTooltipClassText($a_class_name) {
		return $this->lng->txt("rep_robj_xrs_class") . ": " . $a_class_name;
	}


	private function createTooltipRoleText($a_assigned_role) {
		$role_assignment_text = $this->lng->txt("rep_robj_xrs_privileges_role_assignment");
		if (empty($a_assigned_role)) {
			return $role_assignment_text . ": " . $this->lng->txt("none");
		} else {
			$role_assignment_text = $role_assignment_text . ": " . $a_assigned_role;

			return $this->createShortenedTooltipRoleTextIfMaxTextWidthReached($role_assignment_text);
		}
	}


	/**
	 * This function shortens the text for the role assignment whenever a certain threshold is
	 * reached. This is needed due to the fact that the tooltip popup has a maximum pixel width
	 * which would otherwise cut the role text in an inappropriate way.
	 *
	 * @param string $a_role_text the role text that should be shortened
	 *
	 * @return string the shortened role if the threshold has been reached; the unshortened
	 * role text otherwise
	 */
	private function createShortenedTooltipRoleTextIfMaxTextWidthReached($a_role_text) {
		if (strlen($a_role_text) > self::MAX_POPUP_TEXT_WIDTH) {
			return substr($a_role_text, 0, self::MAX_POPUP_TEXT_WIDTH) . "...";
		} else {
			return $a_role_text;
		}
	}


	private function createTooltipClassPriorityText($a_class_priority) {
		return $this->lng->txt("rep_robj_xrs_class_priority") . ": " . $a_class_priority;
	}


	/**
	 * Fills an entire table row with a given data row. The row comes in four different flavours:
	 * 1. a row that displays checkboxes for locking privileges
	 * 2. a section info row, which groups its underlying privilieges
	 * 3. a row for displaying a "select all" checkbox for selecting grouped privileges
	 * 4. a checkbox row for a privilege
	 *
	 * @see ilTable2GUI::fillRow()
	 *
	 * @param $a_table_row data set for that row
	 */
	public function fillRow($a_table_row) {
		if (isset($a_table_row["show_lock_row"])) {
			$this->fillLockRow();

			return true;
		}

		if (isset($a_table_row["show_section_info"])) {
			$this->fillSectionInfoRow($a_table_row["section"]);

			return true;
		}

		if (isset($a_table_row["show_select_all"])) {
			$this->fillSelectAllRow($a_table_row["type"], $a_table_row["privileges"]);

			return true;
		}

		$this->fillPrivilegeRow($a_table_row["privilege"], $a_table_row["classes"]);
	}


	private function fillLockRow() {
		foreach ($this->class_array as $class_row) {
			$this->fillPrivilegeLockData($class_row["id"]);
		}
	}


	private function fillPrivilegeLockData($a_class_id) {
		$this->tpl->setCurrentBlock("class_lock");
		$this->tpl->setVariable("LOCK_CLASS_ID", $a_class_id);
		$this->tpl->setVariable("TXT_LOCK", $this->lng->txt("rep_robj_xrs_privileges_lock"));
		$this->tpl->setVariable("TXT_LOCK_LONG", $this->lng->txt("rep_robj_xrs_privileges_lock_desc"));

		if ($this->isClassLocked($a_class_id)) {
			$this->tpl->setVariable("LOCK_CHECKED", "checked='checked'");
		}

		if ($this->isLockingDisallowed()) {
			$this->tpl->setVariable("LOCK_DISABLED", "disabled='disabled'");
		}

		$this->privileges->getLockedClasses();
		$this->tpl->parseCurrentBlock();
	}


	private function isClassLocked($a_class_id) {
		return in_array($a_class_id, $this->privileges->getLockedClasses());
	}


	private function isLockingDisallowed() {
		return !$this->permission->checkPrivilege(PRIVC::LOCK_PRIVILEGES) || !$this->permission->checkPrivilege(PRIVC::EDIT_PRIVILEGES);
	}


	private function fillSectionInfoRow($a_section_info_row) {
		$this->tpl->setCurrentBlock("section_info");
		$this->tpl->setVariable("SECTION_TITLE", $a_section_info_row["title"]);
		$this->tpl->setVariable("SECTION_DESC", $a_section_info_row["description"]);
		$this->tpl->parseCurrentBlock();
	}


	private function fillSelectAllRow($a_type, $a_privileges_for_type) {
		foreach ($this->class_array as $class_row) {
			$this->fillSelectAllData($class_row["id"], $a_type, $a_privileges_for_type);
		}
	}


	/**
	 * The table data for the individual select all checkboxes is populated here. The sub id is
	 * needed for differentiating the select all checkboxes, while the privilege ids are needed for
	 * assigning them to that very select all checkbox. The corresponding JavaScript-function needs
	 * these information.
	 *
	 * @param string $a_class_id            the id of the class to which the select all checkbox is assigned to
	 * @param string $a_type                the type name which acts as a sub id
	 * @param array  $a_privileges_for_type the ids of the privileges that should be selected by the
	 *                                      select all checkbox
	 */
	private function fillSelectAllData($a_class_id, $a_type, $a_privileges_for_type) {
		$this->tpl->setCurrentBlock("class_select_all");
		$this->tpl->setVariable("JS_CLASS_ID", $a_class_id);
		$this->tpl->setVariable("JS_FORM_NAME", $this->getFormName());
		$this->tpl->setVariable("JS_SUBID", $a_type);
		$this->tpl->setVariable("JS_ALL_PRIVS", "['" . implode("','", $a_privileges_for_type) . "']");
		$this->tpl->setVariable("TXT_SEL_ALL", $this->lng->txt("select_all"));

		if (!$this->permission->checkPrivilege(PRIVC::EDIT_PRIVILEGES)) {
			$this->tpl->setVariable("PRIV_DISABLED", 'disabled="disabled"');
		}

		$this->tpl->parseCurrentBlock();
	}


	private function fillPrivilegeRow($a_privilege_row, $a_class_array) {
		foreach ($a_class_array as $class_row) {
			$this->fillPrivilegeData($a_privilege_row, $class_row);
		}
	}


	/**
	 * Fills the table data of a privilege. If no editing of the privileges is allowed, the checkbox
	 * will be disabled.
	 *
	 * @param array $a_privilege_row information for the privilege (id, name, description)
	 * @param array $a_class_row     information about the class (id, isset) to where this privilege
	 *                               belongs
	 */
	private function fillPrivilegeData($a_privilege_row, $a_class_row) {
		$this->tpl->setCurrentBlock("class_td");
		$this->tpl->setVariable("PRIV_CLASS_ID", $a_class_row["id"]);
		$this->tpl->setVariable("PRIV_ID", $a_privilege_row["id"]);

		$this->tpl->setVariable("TXT_PRIV", $a_privilege_row["name"]);

		$this->tpl->setVariable("TXT_PRIV_LONG", $a_privilege_row["description"]);

		if ($a_class_row["privilege_set"]) {
			$this->tpl->setVariable("PRIV_CHECKED", 'checked="checked"');
		}

		if (!$this->permission->checkPrivilege(PRIVC::EDIT_PRIVILEGES)) {
			$this->tpl->setVariable("PRIV_DISABLED", 'disabled="disabled"');
		}

		$this->tpl->parseCurrentBlock();
	}


	/**
	 * Set the poolID of bookings
	 *
	 * @param integer $pool_id
	 *            poolID
	 */
	public function setPoolId($pool_id) {
		$this->pool_id = $pool_id;
	}


	/**
	 * Get the PoolID of bookings
	 *
	 * @return integer PoolID
	 */
	public function getPoolId() {
		return (int)$this->pool_id;
	}
}

?>
