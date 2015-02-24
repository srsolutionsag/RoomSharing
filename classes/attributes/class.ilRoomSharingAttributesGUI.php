<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/attributes/room/class.ilRoomSharingRoomAttributesGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/attributes/booking/class.ilRoomSharingBookingAttributesGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/attributes/class.ilRoomSharingAttributesConstants.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingPermissionUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivilegesConstants.php");

use ilRoomSharingAttributesConstants as ATTRC;
use ilRoomSharingPrivilegesConstants as PRIVC;

/**
 * Class ilRoomSharingAttributesGUI
 *
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 *
 * @version $Id$
 *
 * @ilCtrl_Calls ilRoomSharingAttributesGUI: ilRoomSharingRoomAttributesGUI, ilRoomSharingBookingAttributesGUI, ilCommonActionDispatcherGUI
 *
 * @property ilCtrl $ctrl
 * @property ilLanguage $lng
 * @property ilRoomSharingPermissionUtils $permission
 */
class ilRoomSharingAttributesGUI
{
	public $ref_id;
	protected $ctrl;
	protected $lng;
	private $permission;
	private $pool_id;

	/**
	 * Constructor of ilRoomSharingAttributesGUI
	 *
	 * @param object $a_parent_obj
	 */
	function __construct($a_parent_obj)
	{
		global $ilCtrl, $lng, $rssPermission;
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		$this->permission = $rssPermission;

		$this->ref_id = $a_parent_obj->ref_id;
		$this->pool_id = $a_parent_obj->getPoolId();
	}

	/**
	 * Main switch for command execution.
	 *
	 * @return Returns always true.
	 */
	function executeCommand()
	{
		// default cmd if none provided
		$cmd = $this->ctrl->getCmd(ATTRC::SHOW_ROOM_ATTR_ACTIONS);

		switch ($cmd)
		{
			case 'render':
			case 'showContent':
			case ATTRC::SHOW_ROOM_ATTR_ACTIONS:
			case ATTRC::EXECUTE_ROOM_ATTR_ACTION:
				$this->setSubTabs(ATTRC::ROOM_ATTRS);
				$rooms_attributes_gui = & new ilRoomSharingRoomAttributesGUI($this);
				$this->ctrl->forwardCommand($rooms_attributes_gui);
				break;
			case ATTRC::SHOW_BOOKING_ATTR_ACTIONS:
			case ATTRC::EXECUTE_BOOKING_ATTR_ACTION:
				$this->setSubTabs(ATTRC::BOOKING_ATTRS);
				$bookings_attributes_gui = & new ilRoomSharingBookingAttributesGUI($this);
				$this->ctrl->forwardCommand($bookings_attributes_gui);
				break;
			default:
				$this->$cmd();
				break;
		}
		return true;
	}

	/**
	 * Adds SubTabs for the MainTab "attributes".
	 *
	 * @param type $a_active
	 *        	SubTab which should be activated after method call.
	 */
	protected function setSubTabs($a_active)
	{
		global $ilTabs;
		$ilTabs->setTabActive(ATTRC::ATTRS);
		if ($this->permission->checkPrivilege(PRIVC::ADMIN_ROOM_ATTRIBUTES))
		{
			// Room attributes
			$ilTabs->addSubTab(ATTRC::ROOM_ATTRS, $this->lng->txt('rep_robj_xrs_attributes_for_rooms'),
				$this->ctrl->getLinkTargetByClass(ATTRC::ROOM_ATTRS_GUI, ATTRC::SHOW_ROOM_ATTR_ACTIONS));
		}
		if ($this->permission->checkPrivilege(PRIVC::ADMIN_BOOKING_ATTRIBUTES))
		{
			// Booking attributes
			$ilTabs->addSubTab(ATTRC::BOOKING_ATTRS, $this->lng->txt('rep_robj_xrs_attributes_for_bookings'),
				$this->ctrl->getLinkTargetByClass(ATTRC::BOOKING_ATTRS_GUI, ATTRC::SHOW_BOOKING_ATTR_ACTIONS));
		}
		$ilTabs->activateSubTab($a_active);
	}

	/**
	 * Returns roomsharing pool id.
	 *
	 * @return Room-ID
	 */
	function getPoolId()
	{
		return $this->pool_id;
	}

	/**
	 * Sets roomsharing pool id.
	 *
	 * @param integer Pool-ID
	 */
	function setPoolId($a_pool_id)
	{
		$this->pool_id = $a_pool_id;
	}

}

?>
