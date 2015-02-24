<?php

require_once("./Services/Component/classes/class.ilPluginConfigGUI.php");
require_once("Services/Form/classes/class.ilPropertyFormGUI.php");

/**
 * Configuration GUI for the RoomSharing-Plugin.
 *
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 * @version $Id$
 *
 */
class ilRoomSharingConfigGUI extends ilPluginConfigGUI
{
	/**
	 * Handles all commmands, default is "configure"
	 * @param type $cmd
	 */
	function performCommand($cmd)
	{
		// Switch case delete, due it's not required.
		$this->configure();
	}

	/**
	 * Configuration gui.
	 */
	function configure()
	{
		global $tpl;
		$pl = $this->getPluginObject();

		$form = new ilPropertyFormGUI();
		$form->setTitle($pl->txt('roomsharing_plugin_configuration'));
		$form->setDescription($pl->txt('roomsharing_plugin_config_not_required'));

		$tpl->setContent($form->getHTML());
	}

}
