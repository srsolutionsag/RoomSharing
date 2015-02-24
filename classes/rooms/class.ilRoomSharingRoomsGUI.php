<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/rooms/class.ilRoomSharingRoomsTableGUI.php");
require_once("Services/UIComponent/Toolbar/classes/class.ilToolbarGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingPermissionUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivilegesConstants.php");

use ilRoomSharingPrivilegesConstants as PRIVC;

/**
 * Class ilRoomSharingRoomsGUI. Shows an table with rooms of the room sharing pool.
 *
 * @author Alexander Keller <a.k3ll3r@gmail.com>
 *
 * @property ilCtrl $ctrl
 * @property ilLanguage $lng
 * @property ilTemplate $tpl
 * @property ilRoomSharingPermissionUtils $permission
 */
class ilRoomSharingRoomsGUI
{
	protected $ref_id;
	private $pool_id;
	private $ctrl;
	private $lng;
	private $tpl;
	private $permission;

	/**
	 * Constructor for the class ilRoomSharingRoomsGUI.
	 * @param ilObjRoomSharingGUI $a_parent_obj
	 */
	public function __construct(ilObjRoomSharingGUI $a_parent_obj)
	{
		global $ilCtrl, $lng, $tpl, $rssPermission;

		$this->parent_obj = $a_parent_obj;
		$this->ref_id = $a_parent_obj->ref_id;
		$this->pool_id = $a_parent_obj->getPoolId();
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		$this->tpl = $tpl;
		$this->permission = $rssPermission;
	}

	/**
	 * Execute the command given.
	 *
	 * @return Returns always true.
	 */
	public function executeCommand()
	{
		// the default command, if none is set
		$cmd = $this->ctrl->getCmd("showRooms");
		$cmd .= 'Object';
		$this->$cmd();
		return true;
	}

	/**
	 * Show a list of all rooms.
	 */
	public function showRoomsObject()
	{
		if (!$this->permission->checkPrivilege(PRIVC::ACCESS_ROOMS))
		{
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_no_permission_for_action"));
			$this->ctrl->redirectByClass('ilinfoscreengui', 'showSummary', 'showSummary');
			return false;
		}

		$roomsTable = new ilRoomSharingRoomsTableGUI($this, 'showRooms', $this->ref_id);
		$roomsTable->initFilter();
		$roomsTable->getItems($roomsTable->getCurrentFilter());

		$toolbar = new ilToolbarGUI;
		if ($this->permission->checkPrivilege(PRIVC::ADD_ROOMS))
		{
			$toolbar->addButton($this->lng->txt('rep_robj_xrs_add_room'),
				$this->ctrl->getLinkTargetByClass('ilroomsharingroomgui', 'addRoom'));
		}

		// the commands (functions) to be called when the correspondent buttons are clicked
		$roomsTable->setResetCommand("resetRoomFilter");
		$roomsTable->setFilterCommand("applyRoomFilter");
		$this->tpl->setContent($toolbar->getHTML() . $roomsTable->getHTML());
	}

	/**
	 * Creates a new table for the  rooms and writes all the input
	 * values to the session, so that a filter can be applied.
	 */
	public function applyRoomFilterObject()
	{

		$roomsTable = new ilRoomSharingRoomsTableGUI($this, 'showRooms', $this->ref_id);
		$roomsTable->initFilter();
		$roomsTable->writeFilterToSession(); // writes filter to session
		$roomsTable->resetOffset(); // set the record offset to 0 (first page)
		$this->showRoomsObject();
	}

	/**
	 * Resets all the input fields.
	 */
	public function resetRoomFilterObject()
	{

		$roomsTable = new ilRoomSharingRoomsTableGUI($this, 'showRooms', $this->ref_id);
		$roomsTable->initFilter();
		$roomsTable->resetFilter();
		$roomsTable->resetOffset(); // set the record offset to 0 (first page)
		$this->showRoomsObject();
	}

	/**
	 * Returns the Roomsharing Pool ID.
	 *
	 * @return the current PoolID
	 */
	public function getPoolId()
	{
		return $this->pool_id;
	}

	/**
	 * Sets the Roomsharing Pool ID.
	 *
	 * @param the new PoolID
	 */
	public function setPoolId($a_pool_id)
	{
		$this->pool_id = $a_pool_id;
	}

}

?>
