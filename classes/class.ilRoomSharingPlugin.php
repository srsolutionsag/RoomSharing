<?php

include_once("./Services/Repository/classes/class.ilRepositoryObjectPlugin.php");

/**
 * RoomSharing repository object plugin
 *
 * @author troehrig 
 * @version $Id$
 * 
 *
 */
class ilRoomSharingPlugin extends ilRepositoryObjectPlugin
{

	/**
	 * Get name of the Plugin
	 * 
	 * @return String
	 */
	function getPluginName()
	{
		return "RoomSharing";
	}

}
