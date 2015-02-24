<?php

require_once("./Services/Table/classes/class.ilTable2GUI.php");
require_once("./Services/MediaObjects/classes/class.ilObjMediaObject.php");
require_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/floorplans/class.ilRoomSharingFloorPlans.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingPermissionUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivilegesConstants.php");

use ilRoomSharingPrivilegesConstants as PRIVC;

/**
 * Class ilRoomSharingFloorPlansTableGUI
 *
 * This class is used for displaying a table containing all uploaded floor
 * plans. A thumbnail shows a small picture of the floor plan next to the
 * title and the description. Other than that the table also provides options
 * for editing and removing floor plans, if the necessary write criterias of
 * a user are met.
 *
 * @author Thomas Wolscht <t.wolscht@googlemail.com>
 * @author Christopher Marks <Deamp_dev@yahoo.de>
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 *
 * @property ilCtrl $ctrl;
 * @property ilLanguage $lng
 * @property ilRoomSharingPermissionUtils $permission
 */
class ilRoomSharingFloorPlansTableGUI extends ilTable2GUI
{
	private $pool_id;
	private $permission;
	protected $lng;
	private $ctrl;

	/**
	 * Constructor of ilRoomSharingFloorPlansTableGUI
	 *
	 * @global type $ilCtrl the ilias control structure
	 * @global type $lng the translation instance of ilias
	 * @param object $a_parent_obj the parent object for retrieving information
	 * @param string $a_parent_cmd the cmd that led to the creation of this table
	 * @param integer $a_ref_id the reference id for write permission checks
	 */
	public function __construct($a_parent_obj, $a_parent_cmd, $a_ref_id)
	{
		global $ilCtrl, $lng, $rssPermission;

		$this->parent_obj = $a_parent_obj;
		$this->lng = $lng;
		$this->ctrl = $ilCtrl;
		$this->permission = $rssPermission;
		$this->ref_id = $a_ref_id;
		$this->pool_id = $a_parent_obj->getPoolId();

		parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->setTitle($this->lng->txt("rep_robj_xrs_floor_plans_show"));
		$this->setLimit(10);   // max no. of rows
		$this->addColumns();
		$this->setEnableHeader(true);
		$this->setRowTemplate("tpl.room_floorplans.html",
			"Customizing/global/plugins/Services/" . "Repository/RepositoryObject/RoomSharing");
		$this->getItems();
	}

	/**
	 * Retrieves the data that should be populated into the table.
	 */
	private function getItems()
	{
		$floorplans = new ilRoomSharingFloorPlans($this->pool_id,
			new ilRoomsharingDatabase($this->pool_id));
		$data = $floorplans->getAllFloorPlans($this->pool_id);
		$this->setMaxCount(count($data));
		$this->setData($data);
	}

	/**
	 * Adds columns and column headings to the table.
	 */
	private function addColumns()
	{
		$this->addColumn($this->lng->txt("rep_robj_xrs_plan"));
		$this->addColumn($this->lng->txt("title"), "title");
		$this->addColumn($this->lng->txt("desc"));
		$this->addColumn($this->lng->txt("actions"));
	}

	/**
	 * Populates one row of the table. The table has the following shape:
	 *
	 * -- Thumbnail -- Title -- Description -- Actions --
	 *
	 * @param assoc array $a_set the row that needs to be populated
	 */
	public function fillRow($a_set)
	{
		$mediaObject = new ilObjMediaObject($a_set['file_id']);

		$this->fillRowImage($mediaObject, $a_set["type"]);
		$this->fillRowTitleAndDescription($mediaObject, $a_set['title']);
		$this->fillRowActions($a_set['file_id']);
	}

	/**
	 * Populates the Image Thumbnail in the row
	 *
	 * @param ilObjMediaObject $a_mediaObject the media object
	 * @param string $a_type the image type
	 */
	private function fillRowImage($a_mediaObject, $a_type)
	{
		$med = $a_mediaObject->getMediaItem("Standard");

		$this->tpl->setVariable("LINK_VIEW",
			$a_mediaObject->getDataDirectory() . "/" . $med->getLocation());

		$target = $med->getThumbnailTarget();
		if ($target !== "")
		{
			$this->tpl->setVariable("IMG", ilUtil::img($target));
		}
		else
		{
			$this->tpl->setVariable("IMG", ilUtil::img(ilUtil::getImagePath("icon_" . $a_type . ".png")));
		}
	}

	/**
	 * Populates the title and description in the row
	 *
	 * @param ilObjMediaObject $a_mediaObject the media object
	 * @param string $a_title the title
	 */
	private function fillRowTitleAndDescription($a_mediaObject, $a_title)
	{
		$this->tpl->setVariable('TXT_TITLE', $a_title);
		$this->tpl->setVariable('TXT_DESCRIPTION', $a_mediaObject->getMediaItem("Standard")->getCaption());
	}

	/**
	 * Populates the actions in the row
	 *
	 * @param type $a_file_id the file id
	 */
	private function fillRowActions($a_file_id)
	{
		$alist = new ilAdvancedSelectionListGUI();
		$alist->setId($a_file_id);
		$alist->setListTitle($this->lng->txt("actions"));

		$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'file_id', $a_file_id);
		if ($this->permission->checkPrivilege(PRIVC::EDIT_FLOORPLANS))
		{
			$alist->addItem($this->lng->txt('edit'), 'edit',
				$this->ctrl->getLinkTarget($this->parent_obj, 'editFloorplan'));
		}
		if ($this->permission->checkPrivilege(PRIVC::DELETE_FLOORPLANS))
		{
			$alist->addItem($this->lng->txt('delete'), 'delete',
				$this->ctrl->getLinkTarget($this->parent_obj, 'confirmDelete'));
		}
		$this->tpl->setVariable("LAYER", $alist->getHTML());
	}

	/**
	 * Set the poolID of bookings
	 *
	 * @param integer $pool_id
	 *        	poolID
	 */
	public function setPoolId($pool_id)
	{
		$this->pool_id = $pool_id;
	}

	/**
	 * Get the PoolID of bookings
	 *
	 * @return integer PoolID
	 */
	public function getPoolId()
	{
		return (int) $this->pool_id;
	}

}

?>