<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingNumberInputGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/import/class.ilRoomSharingDaVinciImport.php");

/**
 * Description of class
 *
 * @author MartinDoser
 *
 *
 * @property ilCtrl     $ctrl
 * @property ilLanguage $lng
 */
class ilRoomSharingDaVinciImportGUI {

	private $parent_obj;
	public $ref_id;
	protected $ctrl;
	protected $lng;
	private $pool_id;


	/**
	 * Constructor of ilRoomSharingDaVinciImportGUI
	 *
	 * @param object $a_parent_obj
	 */
	function __construct($a_parent_obj) {
		global $ilCtrl, $lng, $tpl;
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;

		$this->parent_obj = $a_parent_obj;
		$this->ref_id = $a_parent_obj->ref_id;
		$this->pool_id = $a_parent_obj->getPoolId();
		$this->tpl = $tpl;
	}


	/**
	 * Main switch for command execution.
	 *
	 * @return Returns always true.
	 */
	public function executeCommand() {
		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd("render");   // the default command, if none
		// is found
		switch ($cmd) {
			case "Importieren":
				$cmd = 'import';

			default:
				$cmd .= 'Object';
				break;
		}

		$this->$cmd();

		return true;
	}


	public function renderObject() {
		global $ilCtrl, $tpl, $lng, $ilTabs;
		$form = $this->initForm();
		$tpl->setContent($form->getHTML());
	}


	/**
	 * called when the button import in the form is clicked
	 * Imports the data from the uploaded daVinci file
	 */
	public function importObject() {
		$form = $this->initForm();
		if ($form->checkInput()) {
			$import = new ilRoomSharingDaVinciImport($this->parent_obj, $this->lng, $this->pool_id, new ilRoomsharingDatabase($this->pool_id));
			$file = $form->getInput("upload_file");
			$import_rooms = $form->getInput("import_rooms");
			$import_bookings = $form->getInput("import_bookings");
			$default_cap = $form->getInput("default_cap");
			$import->importBookingsFromDaVinciFile($file, $import_rooms, $import_bookings, $default_cap);
			$this->renderObject();
		} else {
			$form->setValuesByPost();
			$this->tpl->setContent($form->getHTML());
		}
	}


	/**
	 * Form for importing data from a davinci file.
	 *
	 * @global type $lng    the language instance
	 * @global type $ilCtrl the ilias control structure
	 * @return \ilPropertyFormGUI
	 */
	public function initForm() {
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form_gui = $this->initImportForm();
		$form_gui->setFormAction($this->ctrl->getFormAction($this));

		return $form_gui;
	}


	/**
	 * Creates a form for the davinci import
	 *
	 * @return \ilPropertyFormGUI the creation form
	 */
	protected function initImportForm() {
		$import_form = new ilPropertyFormGUI();
		$import_form->setTitle($this->lng->txt("rep_robj_xrs_daVinci_import_title"));

		$file = $this->createFileInputFormItem();
		$import_form->addItem($file);

		$importOption1field = new ilCheckboxInputGUI($this->lng->txt("rep_robj_xrs_daVinci_import_bookings"), 'import_bookings');
		$import_form->addItem($importOption1field);

		$importOption2field = new ilCheckboxInputGUI($this->lng->txt("rep_robj_xrs_daVinci_import_rooms"), 'import_rooms');
		$import_form->addItem($importOption2field);

		$default_cap = new ilRoomSharingNumberInputGUI($this->lng->txt("rep_robj_xrs_daVinci_import_default_cap"), "default_cap");
		$default_cap->setDisabled(false);
		$default_cap->setMinValue(1);
		$default_cap->setValue("20");
		$import_form->addItem($default_cap);

		$import_form->addCommandButton($this->lng->txt("rep_robj_xrs_daVinci_import_button"), $this->lng->txt("import"));

		return $import_form;
	}


	/**
	 * Creates an input field for the davinci text file upload
	 *
	 * @return \ilFileInputGUI file input form item
	 */
	protected function createFileInputFormItem() {
		$file = new ilFileInputGUI($this->lng->txt("rep_robj_xrs_daVinci_import_file"), "upload_file");
		$file->setSize(50);
		$file->setRequired(true);
		$file->setALlowDeletion(true);
		$file->setSuffixes(array( 'txt' ));

		return $file;
	}


	/**
	 * Returns roomsharing pool id.
	 *
	 * @return Room-ID
	 */
	function getPoolId() {
		return $this->pool_id;
	}


	/**
	 * Sets roomsharing pool id.
	 *
	 * @param integer Pool-ID
	 */
	function setPoolId($a_pool_id) {
		$this->pool_id = $a_pool_id;
	}
}
