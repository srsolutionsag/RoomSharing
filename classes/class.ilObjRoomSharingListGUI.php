<?php

require_once("./Services/Repository/classes/class.ilObjectPluginListGUI.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/class.ilObjRoomSharingAccess.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/class.ilObjRoomSharing.php");

/**
 * ilObjRoomSharingListGUI implementation for room sharing plugin.
 * Handles the presentation in container items (categories, courses, ...)
 * together with the corresponding ilObjRoomSharingAccess class.
 *
 * PLEASE do not create instances of larger classes here. Use the
 * ...Access class to get DB data and keep it small.
 *
 * @author  Thomas Matern <tmatern@stud.hs-bremen.de>
 *
 * @version $Id$
 */
class ilObjRoomSharingListGUI extends ilObjectPluginListGUI {

	/**
	 * Init type
	 */
	public function initType() {
		$this->setType("xrs");
	}


	/**
	 * Get name of gui class handling the commands
	 *
	 * @return String
	 */
	public function getGuiClass() {
		return "ilObjRoomSharingGUI";
	}


	/**
	 * Get commands for the room sharing pool.
	 *
	 * @return array with commands
	 */
	public function initCommands() {
		$this->static_link_enabled = true;
		$this->delete_enabled = true;
		$this->cut_enabled = true;
		$this->copy_enabled = true;
		$this->subscribe_enabled = true;
		$this->link_enabled = true;
		$this->payment_enabled = false;
		$this->info_screen_enabled = true;
		$this->timings_enabled = false;

		$this->gui_class_name = "ilobjroomsharinggui";

		// general commands array
		$this->commands = ilObjRoomSharingAccess::_getCommands();

		return $this->commands;
	}


	public function getProperties() {
		global $lng;

		$props = array();

		if (!ilObjRoomSharing::_lookupOnline($this->obj_id)) {
			$props[] = array(
				"alert" => true,
				"property" => $lng->txt("status"),
				"value" => $lng->txt("offline")
			);
		}

		return $props;
	}
}
