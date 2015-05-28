<?php

require_once("Services/Table/classes/class.ilTable2GUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivileges.php");

/**
 * Class ilRoomSharingAssignedUsersTableGUI
 *
 * @author  Alexander Keller <a.k3ll3r@gmail.com>
 *
 * @version $Id$
 */
class ilRoomSharingAssignedUsersTableGUI extends ilTable2GUI {

	private $ctrl;
	private $privileges;
	private $parent;


	/**
	 * Constructor of ilRoomSharingAssignedUsersTableGUI
	 *
	 * @global type $ilCtrl
	 * @global type $lng
	 *
	 * @param type  $a_parent_obj
	 * @param type  $a_parent_cmd
	 * @param type  $a_class_id the id of the class for which this user assignment table is generated
	 */
	public function __construct($a_parent_obj, $a_parent_cmd, $a_class_id) {
		global $ilCtrl, $lng;

		parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->lng = $lng;
		$this->ctrl = $ilCtrl;
		$this->class_id = $a_class_id;
		$this->parent = $a_parent_obj;
		$this->privileges = new ilRoomSharingPrivileges($a_parent_obj->getPoolId());

		$this->setFormAction($ilCtrl->getFormAction($a_parent_obj, $a_parent_cmd));
		$this->initTableProperties();
		$this->addColumns();
		$this->fetchUserTableData();
	}


	private function initTableProperties() {
		$this->setRowTemplate("tpl.room_user_assignment_row.html", "Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/");

		$this->setEnableTitle(true);
		$this->setDefaultOrderField("login");
		$this->setDefaultOrderDirection("asc");

		$this->setShowRowsSelector(true);
		$this->setSelectAllCheckbox("user_id[]");
		$this->addMultiCommand("deassignUsersFromClass", $this->lng->txt("remove"));
	}


	private function addColumns() {
		$this->addColumn("", "", "1", true);
		$this->addColumn($this->lng->txt("login"), "login", "29%");
		$this->addColumn($this->lng->txt("firstname"), "firstname", "29%");
		$this->addColumn($this->lng->txt("lastname"), "lastname", "29%");
		$this->addColumn($this->lng->txt(""), "", "13%");
	}


	private function fetchUserTableData() {
		$user_data = $this->privileges->getAssignedUsersForClass($this->class_id);
		$this->setMaxCount(count($user_data));
		$this->setData($user_data);
	}


	/**
	 * Fills an entire table row with the given set of user information.
	 *
	 * @see ilTable2GUI::fillRow()
	 *
	 * @param $a_user_row_set data set for that row
	 */
	protected function fillRow($a_user_row_set) {
		$this->tpl->setVariable("ID", $a_user_row_set["id"]);
		$this->tpl->setVariable("TXT_LOGIN", $a_user_row_set["login"]);
		$this->tpl->setVariable("TXT_FIRSTNAME", $a_user_row_set["firstname"]);
		$this->tpl->setVariable("TXT_LASTNAME", $a_user_row_set["lastname"]);
		$this->ctrl->setParameter($this->parent, "user_id", $a_user_row_set["id"]);
		$this->tpl->setVariable("LINK_ACTION", $this->ctrl->getLinkTarget($this->parent, "deassignUsersFromClass"));
		$this->tpl->setVariable("LINK_ACTION_TXT", $this->lng->txt("remove"));
	}
}

?>
