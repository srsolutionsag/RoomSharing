<?php

include_once("./Services/Repository/classes/class.ilRepositoryObjectPlugin.php");

/**
 * RoomSharing repository object plugin
 *
 * @author  troehrig
 * @version $Id$
 *
 *
 */
class ilRoomSharingPlugin extends ilRepositoryObjectPlugin {

	private static $instance;

	/**
	 * Get name of the Plugin
	 *
	 * @return String
	 */
	function getPluginName() {
		return "RoomSharing";
	}


    /**
     * @return ilRoomSharingPlugin
     */
	public static function getInstance()
	{
		if (static::$instance === null) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	protected function uninstallCustom()
	{
	}
}
