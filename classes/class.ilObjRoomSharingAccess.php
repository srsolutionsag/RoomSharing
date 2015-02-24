<?php

require_once("./Services/Repository/classes/class.ilObjectPluginAccess.php");
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/class.ilObjRoomSharing.php");
require_once("./Services/Object/classes/class.ilObject2.php");

/**
 * Access/Condition checking for RoomSharingPool object.
 *
 * @author  Thomas Matern <tmatern@stud.hs-bremen.de>
 *
 * @version $Id$
 *
 */
class ilObjRoomSharingAccess extends ilObjectPluginAccess {

	/**
	 * Get commands.
	 *
	 * this method returns an array of all possible commands/permission combinations.
	 *
	 * @return string Command
	 *
	 *         example:
	 *         $commands = array
	 *         (
	 *         array("permission" => "read", "cmd" => "view", "lang_var" => "show"),
	 *         array("permission" => "write", "cmd" => "edit", "lang_var" => "edit"),
	 *         );
	 */
	public function _getCommands() {
		$commands = array();
		$commands [] = array(
			"permission" => "read",
			"cmd" => "render",
			"lang_var" => "show",
			"default" => true
		);
		$commands [] = array(
			"permission" => "write",
			"cmd" => "render",
			"lang_var" => "edit_content"
		);
		$commands [] = array(
			"permission" => "write",
			"cmd" => "edit",
			"lang_var" => "settings"
		);

		return $commands;
	}


	/**
	 * Check whether goto script will succeed.
	 *
	 * @return boolean true, if everything is ok
	 *
	 * @param string $a_target
	 */
	public function _checkGoto($a_target) {
		global $ilAccess;
		$rVal = false;

		$target_array = explode("_", $a_target);
		$ref_id = $target_array [1];
		$target_type = $target_array [0];

		if ($target_type != "xrs" || $ref_id <= 0) {
			$rVal = false;
		} else {
			if ($ilAccess->checkAccess("read", "", $ref_id)) {
				$rVal = true;
			}
		}

		return $rVal;
	}


	/**
	 * Checks wether a user may invoke a command or not
	 * (this method is called by ilAccessHandler::checkAccess)
	 *
	 * Please do not check any preconditions handled by
	 * ilConditionHandler here. Also don't do usual RBAC checks.
	 *
	 * @param string $a_cmd
	 * @param string $a_permission
	 * @param int    $a_ref_id
	 * @param int    $a_obj_id
	 * @param int    $a_user_id (if not provided, current user is taken)
	 *
	 * @return boolean true if the pool can be shown
	 */
	public function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = "") {
		/* @var $ilAccess ilAccessHandler */
		/* @var $ilUser ilObjUser */
		/* @var $lng ilLanguage */
		global $ilUser, $ilAccess, $lng;
		if ($a_user_id === "") {
			$a_user_id = $ilUser->getId();
		}

		$rVal = false;

		// Check whether the user has write permissions (owner has always write permissions).
		if ($ilAccess->checkAccessOfUser($a_user_id, 'write', '', $a_ref_id, "xrs", $a_obj_id)) {
			$rVal = true;
		} else {
			$rVal = ilObjRoomSharing::_lookupOnline($a_obj_id);
			if (!$rVal) {
				$lng->loadLanguageModule("rep_robj_xrs");
				$message = $lng->txt('rep_robj_xrs_pool') . ' "' . ilObject2::_lookupTitle($a_obj_id) . '" ' . $lng->txt('rep_robj_xrs_is_offline');
				ilUtil::sendInfo($message, true);
			}
		}

		return $rVal;
	}
}

?>
