<?php

include_once('./Services/Table/classes/class.ilTable2GUI.php');

/**
 * Class ilRoomSharingParticipationsExportTableGUI
 *
 * @author albert
 */
class ilRoomSharingParticipationsExportTableGUI extends ilTable2GUI {

	protected $participations;
	protected $pool_id;


	/**
	 * Constructor
	 *
	 * @param unknown $a_parent_obj
	 * @param unknown $a_parent_cmd
	 * @param unknown $a_ref_id
	 */
	public function __construct($a_parent_obj, $a_parent_cmd, $a_ref_id) {
		global $ilCtrl, $lng;
		$this->parent_obj = $a_parent_obj;
		$this->lng = $lng;
		$this->ctrl = $ilCtrl;
		$this->ref_id = $a_ref_id;
		$this->setId("roomobj");

		include_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/appointments/participations/class.ilRoomSharingParticipations.php';
		$this->participations = new ilRoomSharingParticipations($a_parent_obj->getPoolId());
		$this->participations->setPoolId($a_parent_obj->getPoolId());
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->disable('action');
		$this->setTitle($lng->txt("rep_robj_xrs_participations"));
		//$this->setLimit(10); // data sets per page
		$this->setFormAction($ilCtrl->getFormAction($a_parent_obj, $a_parent_cmd));
		// add columns and column headings
		$this->_addColumns();
		$this->setRowTemplate("tpl.room_appointment_export_row.html", "Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/");
		$this->getItems();
	}


	/**
	 * Gets all the items that need to be populated into the table.
	 */
	public function getItems() {
		$data = $this->participations->getList(array());

		$this->setMaxCount(count($data));
		$this->setData($data);
	}


	/**
	 * Adds columns and column headings to the table.
	 */
	private function _addColumns() {
		//calculation of Columnwidth - can still be optimized
		$firstColInPro = 3;
		$tmpNum = (100 - $firstColInPro) / (count($this->getSelectedColumns()) + 4);
		$tmpString = (string)$tmpNum . '%';

		$this->addColumn('', '', $firstColInPro . '%'); // icons
		$this->addColumn($this->lng->txt("rep_robj_xrs_date"), '', $tmpString);
		$this->addColumn($this->lng->txt("rep_robj_xrs_room"), '', $tmpString);
		$this->addColumn($this->lng->txt("rep_robj_xrs_subject"), '', $tmpString);
		$this->addColumn($this->lng->txt("rep_robj_xrs_person_responsible"), '', $tmpString);
	}


	/**
	 * Fills an entire table row with the given set.
	 *
	 * (non-PHPdoc)
	 *
	 * @see ilTable2GUI::fillRow()
	 *
	 * @param $a_set data set for that row
	 */
	public function fillRow($a_set) {
		if ($a_set ['recurrence']) {
			// icon for the recurrence date
			$this->tpl->setCurrentBlock("date_recurrence");
			$this->tpl->setVariable('IMG_RECURRENCE_PATH', '');
			$this->tpl->setVariable('IMG_RECURRENCE_TITLE', '');
		} else {
			//fills the column
			$this->tpl->setCurrentBlock("date_recurrence_replacement");
			$this->tpl->setVariable('TXT_BLANK', '');
		}
		$this->tpl->parseCurrentBlock();

		// ### Appointment ###
		$this->tpl->setCurrentBlock("date");
		$this->tpl->setVariable('TXT_DATE', $a_set ['date']);
		$this->tpl->parseCurrentBlock();

		// ### Room ###
		$this->tpl->setCurrentBlock("room");
		$this->tpl->setVariable('TXT_ROOM', $a_set ['room']);
		$this->tpl->parseCurrentBlock();

		// ### Subject ###
		$this->tpl->setCurrentBlock("subject");
		$this->tpl->setVariable('TXT_SUBJECT', ($a_set ['subject'] === NULL ? '' : $a_set ['subject']));
		$this->tpl->parseCurrentBlock();

		// ### Person responsible ###
		$this->tpl->setCurrentBlock("participants");
		$this->tpl->setVariable('TXT_USER', $a_set ['person_responsible']);
		$this->tpl->setVariable('TXT_SEPARATOR', '');
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock("additional");
		$this->tpl->setVariable("TXT_ADDITIONAL", "");
		$this->tpl->parseCurrentBlock();
	}


	public function getTableHTML() {
		global $lng, $ilCtrl, $ilUser;

		$this->prepareOutput();

		if (is_object($ilCtrl) && $this->getId() == "") {
			$ilCtrl->saveParameter($this->getParentObject(), $this->getNavParameter());
		}

		if (!$this->getPrintMode()) {
			// set form action
			if ($this->form_action != "" && $this->getOpenFormTag()) {
				$hash = "";
				if (is_object($ilUser) && $ilUser->getPref("screen_reader_optimization")) {
					$hash = "#" . $this->getTopAnchor();
				}

				$this->tpl->setCurrentBlock("tbl_form_header");
				//$this->tpl->setVariable("FORMACTION", $this->getFormAction() . $hash);
				//$this->tpl->setVariable("FORMNAME", $this->getFormName());
				$this->tpl->parseCurrentBlock();
			}

			if ($this->form_action != "" && $this->getCloseFormTag()) {
				$this->tpl->touchBlock("tbl_form_footer");
			}
		}

		if (!$this->enabled['content']) {
			return $this->render();
		}

		$this->determineOffsetAndOrder();

		$this->setFooter("tblfooter", $this->lng->txt("previous"), $this->lng->txt("next"));

		$data = $this->getData();
		if ($this->dataExists()) {
			// sort
			if (!$this->getExternalSorting() && $this->enabled["sort"]) {
				$data = ilUtil::sortArray($data, $this->getOrderField(), $this->getOrderDirection(), $this->numericOrdering($this->getOrderField()));
			}
		}

		// fill rows
		if ($this->dataExists()) {
			if ($this->getPrintMode()) {
				ilDatePresentation::setUseRelativeDates(false);
			}

			$this->tpl->addBlockFile("TBL_CONTENT", "tbl_content", $this->row_template, $this->row_template_dir);

			foreach ($data as $set) {
				$this->tpl->setCurrentBlock("tbl_content");
				$this->css_row = ($this->css_row != "tblrow1") ? "tblrow1" : "tblrow2";
				$this->tpl->setVariable("CSS_ROW", $this->css_row);

				$this->fillRow($set);
				$this->tpl->setCurrentBlock("tbl_content");
				$this->tpl->parseCurrentBlock();
			}
		} else {
			// add standard no items text (please tell me, if it messes something up, alex, 29.8.2008)
			$no_items_text = (trim($this->getNoEntriesText()) != '') ? $this->getNoEntriesText() : $lng->txt("no_items");

			$this->css_row = ($this->css_row != "tblrow1") ? "tblrow1" : "tblrow2";

			$this->tpl->setCurrentBlock("tbl_no_entries");
			$this->tpl->setVariable('TBL_NO_ENTRY_CSS_ROW', $this->css_row);
			$this->tpl->setVariable('TBL_NO_ENTRY_COLUMN_COUNT', $this->column_count);
			$this->tpl->setVariable('TBL_NO_ENTRY_TEXT', trim($no_items_text));
			$this->tpl->parseCurrentBlock();
		}

		return $this->render();
	}
}