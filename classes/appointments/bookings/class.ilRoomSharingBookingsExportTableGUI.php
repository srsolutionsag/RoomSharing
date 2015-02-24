<?php

require_once('./Services/Table/classes/class.ilTable2GUI.php');
require_once('Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/appointments/bookings/class.ilRoomSharingBookings.php');

/**
 * ilRoomSharingBookingsExportTableGUI for export of bookings in pdf.
 *
 * @author albert
 */
class ilRoomSharingBookingsExportTableGUI extends ilTable2GUI
{
	protected $bookings;
	protected $pool_id;
	protected $lng;
	protected $ctrl;

	/**
	 * Constructor
	 *
	 * @param ilRoomSharingBookingsTableGUI $a_parent_obj
	 * @param string $a_parent_cmd
	 * @param integer $a_ref_id
	 */
	public function __construct($a_parent_obj, $a_parent_cmd, $a_ref_id)
	{
		global $ilCtrl, $lng;
		$this->parent_obj = $a_parent_obj;
		$this->lng = $lng;
		$this->ctrl = $ilCtrl;
		$this->ref_id = $a_ref_id;
		$this->setId("roomobj");

		$this->bookings = new ilRoomSharingBookings($a_parent_obj->getPoolId());
		$this->bookings->setPoolId($a_parent_obj->getPoolId());

		parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->disable('action');
		$this->setTitle($lng->txt("rep_robj_xrs_bookings"));
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
	public function getItems()
	{
		$data = $this->bookings->getList(array());

		$this->setMaxCount(count($data));
		$this->setData($data);
	}

	/**
	 * Adds columns and column headings to the table.
	 */
	private function _addColumns()
	{
		//calculation of Columnwidth - can still be optimized
		$firstColInPro = 3;
		$tmpNum = (100 - $firstColInPro) / (count($this->getSelectedColumns()) + 4);
		$tmpString = (string) $tmpNum . '%';

		$this->addColumn('', '', $firstColInPro . '%'); // icons
		$this->addColumn($this->lng->txt("rep_robj_xrs_date"), '', $tmpString);
		$this->addColumn($this->lng->txt("rep_robj_xrs_room"), '', $tmpString);
		$this->addColumn($this->lng->txt("rep_robj_xrs_subject"), '', $tmpString);
		$this->addColumn($this->lng->txt("rep_robj_xrs_participants"), '', $tmpString);

		// Add the selected optional columns to the table
		foreach ($this->getSelectedColumns() as $c)
		{
			$this->addColumn($c, '', $tmpString);
		}
	}

	/**
	 * Fills an entire table row with the given set.
	 *
	 * (non-PHPdoc)
	 * @see ilTable2GUI::fillRow()
	 * @param $a_set data set for that row
	 */
	public function fillRow($a_set)
	{
		if ($a_set ['recurrence'])
		{
			// icon for the recurrence date
			$this->tpl->setVariable('IMG_RECURRENCE_PATH', ilUtil::getImagePath("cmd_move_s.png"));
			$this->tpl->setVariable('IMG_RECURRENCE_TITLE', $this->lng->txt("rep_robj_xrs_room_date_recurrence"));
		}
		else
		{
			//fills the column
			$this->tpl->setVariable('TXT_BLANK', '');
		}
		// ### Appointment ###
		$this->tpl->setVariable('TXT_DATE', $a_set ['date']);

		// ### Room ###
		$this->tpl->setVariable('TXT_ROOM', $a_set ['room']);
		// ### Subject ###
		$this->tpl->setVariable('TXT_SUBJECT', ($a_set ['subject'] === null ? '' : $a_set ['subject']));
		// ### Participants ###
		$participant_count = count($a_set ['participants']);
		for ($i = 0; $i < $participant_count; ++$i)
		{
			$this->tpl->setCurrentBlock("participants");
			$this->tpl->setVariable("TXT_USER", $a_set ['participants'] [$i]);

			if ($i < $participant_count - 1)
			{
				$this->tpl->setVariable('TXT_SEPARATOR', ',');
			}
			$this->tpl->parseCurrentBlock();
		}

		// Populate the selected additional table cells
		foreach ($this->getSelectedColumns() as $c)
		{
			$this->tpl->setCurrentBlock("additional");
			$this->tpl->setVariable("TXT_ADDITIONAL", $a_set [$c] === null ? "" : $a_set [$c]);
			$this->tpl->parseCurrentBlock();
		}
	}

	public function getTableHTML()
	{
		global $ilCtrl, $ilUser;

		$this->prepareOutput();

		if (is_object($ilCtrl) && $this->getId() == "")
		{
			$ilCtrl->saveParameter($this->getParentObject(), $this->getNavParameter());
		}

		if (!$this->getPrintMode())
		{
			// set form action
			if ($this->form_action != "" && $this->getOpenFormTag())
			{
				$hash = "";
				if (is_object($ilUser) && $ilUser->getPref("screen_reader_optimization"))
				{
					$hash = "#" . $this->getTopAnchor();
				}

				$this->tpl->setCurrentBlock("tbl_form_header");
				//$this->tpl->setVariable("FORMACTION", $this->getFormAction() . $hash);
				//$this->tpl->setVariable("FORMNAME", $this->getFormName());
				$this->tpl->parseCurrentBlock();
			}

			if ($this->form_action != "" && $this->getCloseFormTag())
			{
				$this->tpl->touchBlock("tbl_form_footer");
			}
		}

		if (!$this->enabled['content'])
		{
			return $this->render();
		}

		$this->determineOffsetAndOrder();

		$this->setFooter("tblfooter", $this->lng->txt("previous"), $this->lng->txt("next"));

		$data = $this->getData();
		if ($this->dataExists())
		{
			// sort
			if (!$this->getExternalSorting() && $this->enabled["sort"])
			{
				$data = ilUtil::sortArray($data, $this->getOrderField(), $this->getOrderDirection(), $this->numericOrdering($this->getOrderField()));
			}
		}

		// fill rows
		if ($this->dataExists())
		{
			if ($this->getPrintMode())
			{
				ilDatePresentation::setUseRelativeDates(false);
			}

			$this->tpl->addBlockFile("TBL_CONTENT", "tbl_content", $this->row_template, $this->row_template_dir);

			foreach ($data as $set)
			{
				$this->tpl->setCurrentBlock("tbl_content");
				$this->css_row = ($this->css_row != "tblrow1") ? "tblrow1" : "tblrow2";
				$this->tpl->setVariable("CSS_ROW", $this->css_row);

				$this->fillRow($set);
				$this->tpl->setCurrentBlock("tbl_content");
				$this->tpl->parseCurrentBlock();
			}
		}
		else
		{
			// add standard no items text (please tell me, if it messes something up, alex, 29.8.2008)
			$no_items_text = (trim($this->getNoEntriesText()) != '') ? $this->getNoEntriesText() : $this->lng->txt("no_items");

			$this->css_row = ($this->css_row != "tblrow1") ? "tblrow1" : "tblrow2";

			$this->tpl->setCurrentBlock("tbl_no_entries");
			$this->tpl->setVariable('TBL_NO_ENTRY_CSS_ROW', $this->css_row);
			$this->tpl->setVariable('TBL_NO_ENTRY_COLUMN_COUNT', $this->column_count);
			$this->tpl->setVariable('TBL_NO_ENTRY_TEXT', trim($no_items_text));
			$this->tpl->parseCurrentBlock();
		}

		return $this->render();
	}

	/**
	 * Can be used to add additional columns to the bookings table.
	 *
	 * (non-PHPdoc)
	 * @see ilTable2GUI::getSelectableColumns()
	 * @return additional information for bookings
	 */
	public function getSelectableColumns()
	{
		return $this->bookings->getAdditionalBookingInfos();
	}

}