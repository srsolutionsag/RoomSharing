<?php

include_once('./Services/Table/classes/class.ilTable2GUI.php');

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class
 *
 * @author Jonas Sell
 */
class ilRoomSharingCalendarScheduleExportTableGUI extends ilTable2GUI
{
	protected $calendarWeekGUI;
	protected $pool_id;
	private $insertedSubjects = array();

	/**
	 * Constructor
	 *
	 * @param unknown $a_parent_obj
	 * @param unknown $a_parent_cmd
	 * @param unknown $a_ref_id
	 */
	public function __construct($a_parent_obj, $a_parent_cmd, $a_ref_id, $calendarWeekGUI)
	{
		global $ilCtrl, $lng;
		$this->calendarWeekGUI = $calendarWeekGUI;
		$this->parent_obj = $a_parent_obj;
		$this->lng = $lng;
		$this->ctrl = $ilCtrl;
		$this->ref_id = $a_ref_id;
		$this->setId("calendarscheduleexport");

		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->disable('action');

		$elements = $this->calendarWeekGUI->buildAppointmentsArray()[0];
		$element = $elements[1];
		$this->setTitle(
			$lng->txt("rep_robj_xrs_room_occupation_title") . " "
			. $calendarWeekGUI->getRoomName()
			. " (" . $lng->txt("rep_robj_xrs_week_capitalised") . " " . date('W', $element->getTimestamp()) . ")");
		$this->setDescription($lng->txt("rep_robj_xrs_status") . ": " . date('d') . ". " . date('m') . ". " . date('Y'));
		$this->setLimit(20); // data sets per page
		// $this->setFormAction($ilCtrl->getFormAction($a_parent_obj, $a_parent_cmd));
		$this->_addColumns();

		$this->setRowTemplate("tpl.room_weekly_export_row.html",
			"Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/");
		$this->getItems();

		$tableHtml = str_replace("<nobreaks /><br /><br />", "", $this->getTableHTML());
		ilRoomSharingPDFCreator::generatePDF($tableHtml, 'D',
			$lng->txt("rep_robj_xrs_room_occupation_title") . " " . $calendarWeekGUI->getRoomName()
			. '.pdf');
	}

	/**
	 * Gets all the items that need to be populated into the table.
	 */
	public function getItems()
	{
		$data = $this->calendarWeekGUI->buildAppointmentsArray();

		$this->setMaxCount(count($data));
		$this->setData($data);
	}

	/**
	 * Adds columns and column headings to the table.
	 */
	private function _addColumns()
	{
		$width1 = "9%";
		$this->addColumn($this->lng->txt("time"), "", $width1, FALSE, "", "");
		$elements = $this->calendarWeekGUI->buildAppointmentsArray()[0];
		$width2 = "13%";
		for ($i = 1; $i < 8; $i++)
		{
			$element = $elements[$i];
			$this->addColumn(date('l', $element->getTimestamp())
				. "<br />" . date('d', $element->getTimestamp())
				. " " . substr(date('F', $element->getTimestamp()), 0, 3), "", $width2, FALSE, "", "");
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
		// time cell
		$this->tpl->setCurrentBlock("col_time");
		// set the time entry. <nobreaks />: the export automatically ads lline breaks.
		// we don't want them, as we add them when WE want!!!
		// all line breaks after the <nobreaks /> tag will be removed later.
		$this->tpl->setVariable("COL_TIME", $this->getTimeEntry($a_set[0]) . "<nobreaks />");
		$this->tpl->parseCurrentBlock();

		for ($i = 1; $i < 8; $i++)
		{
			/*
			  $cellContent = "";
			  if ($a_set[$i] != NULL)
			  {
			  $cellContent = $a_set[$i];
			  }
			  $str = "col_day" . $i;
			  $this->tpl->setCurrentBlock($str);
			  $str = "COL_DAY" . $i;
			  if ($a_set[$i] != NULL)
			  {
			  $this->tpl->setVariable($str,
			  $cellContent['subject']
			  . " ("
			  . date_format($cellContent['begin'], 'H:i')
			  . " - "
			  . date_format($cellContent['end'], 'H:i')
			  . ")");
			  }
			  $this->tpl->parseCurrentBlock();
			 */
			$str = "col_day" . $i;
			$this->tpl->setCurrentBlock($str);
			$str = "COL_DAY" . $i;
			$content = "";
			$bgColor = "";
			if ($a_set[$i] != NULL)
			{
				if (!array_key_exists($a_set[$i]['subject'], $this->insertedSubjects))
				{
					$content .= $this->cropIfNecessary($a_set[$i]['subject'])//substr($a_set[$i]['subject'], 0, 8)
						. " ("
						. date_format($a_set[$i]['begin'], 'H:i')
						. "-"
						. date_format($a_set[$i]['end'], 'H:i')
						. ")";
					$this->insertedSubjects[$a_set[$i]['subject']] = 1;
				}
				$bgColor = '  bgcolor="#add8e6"';
			}
			$this->tpl->setVariable($str, "" . $content);
			$this->tpl->setVariable('BG_COLOR', $bgColor);
			$this->tpl->parseCurrentBlock();
		}
	}

	public function cropIfNecessary($subjectName)
	{
		$result = NULL;
		if (strlen($subjectName) <= 20)
		{
			$result = $subjectName;
		}
		else
		{
			$result = substr($subjectName, 0, 17) . "...";
		}
		return $result;
	}

	public function getTimeEntry($timeString)
	{
		$result = NULL;
		if (strpos($timeString, "-") === FALSE)
		{
			// a normal hour interval. append three linebreaks
			$result = $timeString . "<br /><br /><br />";
		}
		else
		{
			// a literal interval. insert linebreaks around hyphen, append one linebreak
			$result = str_replace("-", "<br />-<br />", $timeString) . "<br />";
		}
		return $result;
	}

	public function getTableHTML()
	{
		global $lng, $ilCtrl, $ilUser;

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

				//$this->tpl->setCurrentBlock("tbl_form_header");
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
		/*
		  if (!$this->getExternalSegmentation())
		  {
		  $this->setMaxCount(count($this->row_data));
		  }
		 */
		$this->determineOffsetAndOrder();

		$this->setFooter("tblfooter", $this->lng->txt("previous"), $this->lng->txt("next"));

		$data = $this->getData();
		if ($this->dataExists())
		{
// sort
			if (!$this->getExternalSorting() && $this->enabled["sort"])
			{
				$data = ilUtil::sortArray($data, $this->getOrderField(), $this->getOrderDirection(),
						$this->numericOrdering($this->getOrderField()));
			}
		}

// fill rows
		if ($this->dataExists())
		{
			if ($this->getPrintMode())
			{
				ilDatePresentation::setUseRelativeDates(false);
			}

			$this->tpl->addBlockFile("TBL_CONTENT", "tbl_content", $this->row_template,
				$this->row_template_dir);

			$first = true;
			foreach ($data as $set)
			{
				if ($first)
				{
					// skip first row, as it contains only header dateTimes
					$first = false;
				}
				else
				{
					$this->tpl->setCurrentBlock("tbl_content");
					$this->css_row = ($this->css_row != "tblrow1") ? "tblrow1" : "tblrow2";
					$this->tpl->setVariable("CSS_ROW", $this->css_row);

					$this->fillRow($set);
					$this->tpl->setCurrentBlock("tbl_content");
					$this->tpl->parseCurrentBlock();
				}
			}
		}
		else
		{
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
