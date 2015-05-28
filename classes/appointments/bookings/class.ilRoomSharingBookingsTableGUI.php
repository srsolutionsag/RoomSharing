<?php

require_once("Services/Table/classes/class.ilTable2GUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/appointments/bookings/class.ilRoomSharingBookings.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingPermissionUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivilegesConstants.php");
require_once("Services/Form/classes/class.ilPropertyFormGUI.php");
require_once("Services/Form/classes/class.ilCombinationInputGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingTextInputGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingNumberInputGUI.php");
require_once("Services/User/classes/class.ilUserAutoComplete.php");

use ilRoomSharingPrivilegesConstants as PRIVC;

/**
 * Class ilRoomSharingBookingsTableGUI
 *
 * @author  Alexander Keller <a.k3ll3r@gmail.com>
 * @author  Robert Heimsoth <rheimsoth@stud.hs-bremen.de>
 * @author  Thomas Matern <tmatern@stud.hs-bremen.de>
 *
 * @version $Id$
 *
 * @property ilLanguage                   $lng
 * @property ilCtrl                       $ctrl
 * @property ilRoomSharingBookings        $bookings
 * @property ilRoomSharingAppointmentsGUI $parent_obj
 * @property ilRoomSharingPermissionUtils $permission
 */
class ilRoomSharingBookingsTableGUI extends ilTable2GUI {

	protected $lng;
	protected $ctrl;
	protected $parent_obj;
	private $permission;
	private $bookings;
	private $ref_id;
	const EXPORT_PDF = 3;


	/**
	 * Constructor
	 *
	 * @param object  $a_parent_obj
	 * @param string  $a_parent_cmd
	 * @param integer $a_ref_id
	 */
	public function __construct($a_parent_obj, $a_parent_cmd, $a_ref_id) {
		global $ilCtrl, $lng, $rssPermission;
		$this->permission = $rssPermission;
		$this->lng = $lng;
		$this->ctrl = $ilCtrl;

		$this->parent_obj = $a_parent_obj;
		$this->ref_id = $a_ref_id;
		$this->setId("roomobj");

		$this->bookings = new ilRoomSharingBookings($a_parent_obj->getPoolId());
		$this->bookings->setPoolId($a_parent_obj->getPoolId());

		parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->setTitle($this->lng->txt("rep_robj_xrs_bookings"));
		$this->setLimit(10); // data sets per page
		$this->setFormAction($this->ctrl->getFormAction($a_parent_obj, $a_parent_cmd));

		// add columns and column headings
		$this->addColumns();

		// checkboxes labeled with "bookings" get affected by the "Select All"-Checkbox
		$this->setSelectAllCheckbox('bookings');
		$this->setRowTemplate("tpl.room_appointment_row.html", "Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/");
		// command for cancelling bookings
		if ($this->permission->checkPrivilege(PRIVC::ADD_OWN_BOOKINGS) || $this->permission->checkPrivilege(PRIVC::CANCEL_BOOKING_LOWER_PRIORITY)) {
			$this->addMultiCommand('confirmMultipleCancels', $this->lng->txt('rep_robj_xrs_booking_cancel'));
		}
	}


	/**
	 * Initialize a search filter for ilRoomSharingRoomsTableGUI.
	 */
	public function initFilter() {
		$this->createUserFormItem();
		$this->createRoomFormItem();
		$this->createSubjectFormItem();
		$this->createCommentFormItem();
		$this->createAttributeFormItem();
	}


	private function createUserFormItem() {
		if (!$this->permission->checkPrivilege(PRIVC::SEE_NON_PUBLIC_BOOKINGS_INFORMATION)) {
			global $ilUser;
			$this->filter ["user"] ['user_id'] = $ilUser->getId();

			return;
		}

		$user_comb = new ilCombinationInputGUI($this->lng->txt("rep_robj_xrs_user"), "user");
		$user_name_input = new ilRoomSharingTextInputGUI("", "login");
		$user_name_input->setMaxLength(14);
		$user_name_input->setSize(14);
		$ajax_datasource = $this->ctrl->getLinkTarget($this, 'doUserAutoComplete', '', true);
		$user_name_input->setDataSource($ajax_datasource);
		$user_comb->addCombinationItem("user_id", $user_name_input, $this->lng->txt("rep_robj_xrs_user_name"));
		$this->addFilterItem($user_comb);
		$user_comb->readFromSession(); // get the value that was submitted
		$this->filter ["user"] = $user_comb->getValue();

		$this->setExportFormats(array( self::EXPORT_CSV, self::EXPORT_EXCEL, self::EXPORT_PDF ));

		$this->getItems();
	}


	private function createRoomFormItem() {
		$room_comb = new ilCombinationInputGUI($this->lng->txt("rep_robj_xrs_room"), "room");
		$room_name_input = new ilRoomSharingTextInputGUI("", "room_name");
		$room_name_input->setMaxLength(14);
		$room_name_input->setSize(14);
		$room_comb->addCombinationItem("room_name", $room_name_input, $this->lng->txt("rep_robj_xrs_room_name"));
		$this->addFilterItem($room_comb);
		$room_comb->readFromSession(); // get the value that was submitted
		$this->filter ["room"] = $room_comb->getValue();
	}


	private function createSubjectFormItem() {
		$subject_comb = new ilCombinationInputGUI($this->lng->txt("rep_robj_xrs_subject"), "subject");
		$subject_input = new ilRoomSharingTextInputGUI("", "booking_subject");
		$subject_input->setMaxLength(14);
		$subject_input->setSize(14);
		$subject_comb->addCombinationItem("booking_subject", $subject_input, $this->lng->txt("rep_robj_xrs_subject"));
		$this->addFilterItem($subject_comb);
		$subject_comb->readFromSession(); // get the value that was submitted
		$this->filter ["subject"] = $subject_comb->getValue();
	}


	private function createCommentFormItem() {
		$comment_comb = new ilCombinationInputGUI($this->lng->txt("rep_robj_xrs_comment"), "comment");
		$comment_input = new ilRoomSharingTextInputGUI("", "booking_comment");
		$comment_input->setMaxLength(14);
		$comment_input->setSize(14);
		$comment_comb->addCombinationItem("booking_comment", $comment_input, $this->lng->txt("rep_robj_xrs_comment"));
		$this->addFilterItem($comment_comb);
		$comment_comb->readFromSession(); // get the value that was submitted
		$this->filter ["comment"] = $comment_comb->getValue();
	}


	private function createAttributeFormItem() {
		$attributes = $this->bookings->getAllAttributes();
		foreach ($attributes as $room_attribute) {
			// setup an ilCombinationInputGUI for the room attributes
			$room_attribute_comb = new ilCombinationInputGUI($room_attribute, "attribute_" . $room_attribute);
			$room_attribute_input = new ilRoomSharingTextInputGUI("", "attribute_" . $room_attribute . "_value");
			$room_attribute_input->setMaxLength(14);
			$room_attribute_input->setSize(14);
			$room_attribute_comb->addCombinationItem("amount", $room_attribute_input, $this->lng->txt("rep_robj_xrs_value"));

			$this->addFilterItem($room_attribute_comb);
			$room_attribute_comb->readFromSession();

			$this->filter ["attributes"] [$room_attribute] = $room_attribute_comb->getValue();
		}
	}


	/**
	 * Gets all the items that need to be populated into the table.
	 */
	public function getItems() {
		$data = $this->bookings->getList($this->getCurrentFilter());

		$this->setMaxCount(count($data));
		$this->setData($data);
	}


	/**
	 * Adds columns and column headings to the table.
	 */
	private function addColumns() {
		$this->addColumn('', 'f', '1'); // checkboxes
		$this->addColumn('', 'f', 'l'); // icons
		$this->addColumn($this->lng->txt("rep_robj_xrs_date"), "sortdate");
		$this->addColumn($this->lng->txt("rep_robj_xrs_room"), "room");
		$this->addColumn($this->lng->txt("rep_robj_xrs_subject"), "subject");
		$this->addColumn($this->lng->txt("rep_robj_xrs_participants"), "participants");
		$this->addColumn($this->lng->txt("comment"), "comment");

		// Add the selected optional columns to the table
		foreach ($this->getSelectedColumns() as $c) {
			$this->addColumn($c, $c);
		}
		$this->addColumn($this->lng->txt(''), 'optional');
	}


	/**
	 * Fills an entire table row with the given set.
	 *
	 * (non-PHPdoc)
	 *
	 * @see ilTable2GUI::fillRow()
	 *
	 * @param $a_rowData data set for that row
	 */
	public function fillRow($a_rowData) {
		// the "CHECKBOX_NAME" has to match with the label set in the
		// setSelectAllCheckbox()-function in order to be affected when the
		// "Select All" Checkbox is checked
		$this->tpl->setVariable('CHECKBOX_NAME', 'bookings');
		$this->tpl->setVariable('CHECKBOX_ID', $a_rowData['id'] . '_' . $a_rowData['subject']);

		$this->setRecurrence($a_rowData);

		$this->setAppointment($a_rowData);

		$this->setRoom($a_rowData);

		$this->setSubject($a_rowData);

		$this->setParticipants($a_rowData);

		$this->setComment($a_rowData);

		$this->setAdditionalItems($a_rowData);

		$this->setActions($a_rowData);

		$this->tpl->parseCurrentBlock();
	}


	/**
	 * Can be used to add additional columns to the bookings table.
	 *
	 * (non-PHPdoc)
	 *
	 * @see ilTable2GUI::getSelectableColumns()
	 * @return additional information for bookings
	 */
	public function getSelectableColumns() {
		return $this->bookings->getAdditionalBookingInfos();
	}


	/**
	 * Sets recurrence value in the table row.
	 *
	 * @param array $a_rowData
	 */
	private function setRecurrence($a_rowData) {
		if ($a_rowData ['recurrence']) {
			// icon for the recurrence date
			$this->tpl->setVariable('IMG_RECURRENCE_PATH', ilUtil::getImagePath("cmd_move_s.png"));
		}
		$this->tpl->setVariable('IMG_RECURRENCE_TITLE', $this->lng->txt("rep_robj_xrs_room_date_recurrence"));
	}


	/**
	 * Sets appointment value in the table row.
	 *
	 * @param array $a_rowData
	 */
	private function setAppointment($a_rowData) {
		$this->tpl->setVariable('TXT_DATE', $a_rowData ['date']);
		// link for the date overview
		if ($this->permission->checkPrivilege(PRIVC::ACCESS_APPOINTMENTS)) {
			$this->ctrl->setParameterByClass('ilRoomSharingShowAndEditBookGUI', 'booking_id', $a_rowData['id']);
			$this->ctrl->setParameterByClass('ilRoomSharingShowAndEditBookGUI', 'room_id', $a_rowData['room_id']);
			$this->tpl->setVariable('HREF_DATE', $this->ctrl->getLinkTargetByClass('ilRoomSharingShowAndEditBookGUI', 'showBooking'));
			$this->ctrl->setParameterByClass('ilRoomSharingShowAndEditBookGUI', 'booking_id', '');
			$this->ctrl->setParameterByClass('ilRoomSharingShowAndEditBookGUI', 'room_id', '');
		}
	}


	/**
	 * Sets room values in the table row.
	 *
	 * @param array $a_rowData
	 */
	private function setRoom($a_rowData) {
		$this->tpl->setVariable('TXT_ROOM', $a_rowData ['room']);
		if ($this->permission->checkPrivilege(PRIVC::ACCESS_ROOMS)) {
			$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'room_id', $a_rowData ['room_id']);
			$this->tpl->setVariable('HREF_ROOM', $this->ctrl->getLinkTargetByClass('ilobjroomsharinggui', 'showRoom'));
			$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'room_id', '');
		}
	}


	/**
	 * Sets comment value in the table row.
	 *
	 * @param array $a_rowData
	 */
	private function setComment($a_rowData) {
		$this->tpl->setVariable('TXT_COMMENT', ($a_rowData ['comment'] === NULL ? '' : $a_rowData ['comment']));
	}


	/**
	 * Sets subject value in the table row.
	 *
	 * @param array $a_rowData
	 */
	private function setSubject($a_rowData) {
		$this->tpl->setVariable('TXT_SUBJECT', ($a_rowData ['subject'] === NULL ? '' : $a_rowData ['subject']));
	}


	/**
	 * Sets participants value in the table row.
	 *
	 * @param array $a_rowData
	 */
	private function setParticipants($a_rowData) {
		$participant_count = count($a_rowData ['participants']);
		for ($i = 0; $i < $participant_count; ++ $i) {
			$this->tpl->setCurrentBlock("participants");
			$this->tpl->setVariable("TXT_USER", $a_rowData ['participants'] [$i]);

			// put together a link for the user profile view
			$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'user_id', $a_rowData ['participants_ids'] [$i]);
			$this->tpl->setVariable('HREF_PROFILE', $this->ctrl->getLinkTargetByClass('ilobjroomsharinggui', 'showProfile'));
			// unset the parameter for safety purposes
			$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'user_id', '');

			if ($i < $participant_count - 1) {
				$this->tpl->setVariable('TXT_SEPARATOR', ',');
			}
			$this->tpl->parseCurrentBlock();
		}
	}


	/**
	 * Sets additional values in the table row.
	 *
	 * @param array $a_rowData
	 */
	private function setAdditionalItems($a_rowData) {
		// Populate the selected additional table cells
		foreach ($this->getSelectedColumns() as $c) {
			$this->tpl->setCurrentBlock("additional");
			$this->tpl->setVariable("TXT_ADDITIONAL", $a_rowData [$c] === NULL ? "" : $a_rowData [$c]);
			$this->tpl->parseCurrentBlock();
		}
	}


	/**
	 * Sets action parameters in the table row.
	 *
	 * @param array $a_rowData
	 */
	private function setActions($a_rowData) {
		$this->tpl->setCurrentBlock("actions");
		if ($this->permission->checkPrivilege(PRIVC::ADD_OWN_BOOKINGS)) {
			$this->ctrl->setParameterByClass('ilRoomSharingShowAndEditBookGUI', 'booking_id', $a_rowData ['id']);
			$this->ctrl->setParameterByClass('ilRoomSharingShowAndEditBookGUI', 'room_id', $a_rowData ['room_id']);
			$this->tpl->setVariable('LINK_ACTION', $this->ctrl->getLinkTargetByClass('ilRoomSharingShowAndEditBookGUI', 'editBooking'));
			$this->tpl->setVariable('LINK_ACTION_TXT', $this->lng->txt('rep_robj_xrs_booking_edit'));
			$this->ctrl->setParameterByClass('ilRoomSharingShowAndEditBookGUI', 'booking_id', '');
			$this->ctrl->setParameterByClass('ilRoomSharingShowAndEditBookGUI', 'room_id', '');
		}
		$this->tpl->setVariable('LINK_ACTION_SEPARATOR', '<br>');
		$this->tpl->parseCurrentBlock();

		if ($this->permission->checkPrivilege(PRIVC::ADD_OWN_BOOKINGS) || $this->permission->checkPrivilege(PRIVC::CANCEL_BOOKING_LOWER_PRIORITY)) {
			$this->ctrl->setParameterByClass('ilroomsharingbookingsgui', 'booking_subject', $a_rowData ['subject']);
			$this->ctrl->setParameterByClass('ilroomsharingbookingsgui', 'booking_id', $a_rowData ['id']);
			$this->tpl->setVariable('LINK_ACTION', $this->ctrl->getLinkTargetByClass('ilroomsharingbookingsgui', 'confirmCancel'));
			$this->tpl->setVariable('LINK_ACTION_TXT', $this->lng->txt('rep_robj_xrs_booking_cancel'));
			$this->ctrl->setParameterByClass('ilroomsharingbookingsgui', 'booking_subject', '');
			$this->ctrl->setParameterByClass('ilroomsharingbookingsgui', 'booking_id', '');
		}
	}


	/**
	 * Build a filter that can used for database-queries.
	 *
	 * @return array the filter
	 */
	private function getCurrentFilter() {
		$filter = array();
		// make sure that "0"-strings are not ignored
		if ($this->filter ["room"] ["room_name"] || $this->filter ["room"] ["room_name"] === "0") {
			$filter ["room_name"] = $this->filter ["room"] ["room_name"];
		}
		if ($this->filter ["subject"] ["booking_subject"]
			|| $this->filter ["subject"] ["booking_subject"] === "0"
		) {
			$filter ["subject"] = $this->filter ["subject"] ["booking_subject"];
		}
		if ($this->filter ["comment"] ["booking_comment"]
			|| $this->filter ["comment"] ["booking_comment"] === "0"
		) {
			$filter ["comment"] = $this->filter["comment"] ["booking_comment"];
		}

		if ($this->filter ["user"] ["user_id"] || $this->filter ["user"] ["user_id"] === 0.0) {
			$filter ["user_id"] = $this->filter ["user"] ["user_id"];
		}

		if ($this->filter ["attributes"]) {
			foreach ($this->filter ["attributes"] as $key => $value) {
				if ($value ["amount"]) {
					$filter ["attributes"] [$key] = $value ["amount"];
				}
			}
		}

		return $filter;
	}


	/**
	 * Set the export formats. Copied form ilTable2GUI. Changed only the footer.
	 * See bottom of the method.
	 *
	 * @param array $formats
	 */
	public function setExportFormats(array $formats) {
		$this->export_formats = array();

		// #11339
		$valid = array(
			self::EXPORT_EXCEL => "tbl_export_excel",
			self::EXPORT_CSV => "tbl_export_csv",
			self::EXPORT_PDF => $this->lng->txt("rep_robj_xrs_export_pdf")
		);

		foreach ($formats as $format) {
			if (array_key_exists($format, $valid)) {
				$this->export_formats[$format] = $valid[$format];
			}
		}
	}


	/**
	 * Fill footer row
	 */
	function fillFooter() {
		global $lng, $ilCtrl, $ilUser;

		$footer = false;

		// select all checkbox
		if ((strlen($this->getFormName())) && (strlen($this->getSelectAllCheckbox())) && $this->dataExists()) {
			$this->tpl->setCurrentBlock("select_all_checkbox");
			$this->tpl->setVariable("SELECT_ALL_TXT_SELECT_ALL", $lng->txt("select_all"));
			$this->tpl->setVariable("SELECT_ALL_CHECKBOX_NAME", $this->getSelectAllCheckbox());
			$this->tpl->setVariable("SELECT_ALL_FORM_NAME", $this->getFormName());
			$this->tpl->setVariable("CHECKBOXNAME", "chb_select_all_" . $this->unique_id);
			$this->tpl->parseCurrentBlock();
		}

		// table footer numinfo
		if ($this->enabled["numinfo"] && $this->enabled["footer"]) {
			$start = $this->offset + 1; // compute num info
			if (!$this->dataExists()) {
				$start = 0;
			}
			$end = $this->offset + $this->limit;

			if ($end > $this->max_count or $this->limit == 0) {
				$end = $this->max_count;
			}

			if ($this->max_count > 0) {
				if ($this->lang_support) {
					$numinfo = "(" . $start . " - " . $end . " " . strtolower($this->lng->txt("of")) . " " . $this->max_count . ")";
				} else {
					$numinfo = "(" . $start . " - " . $end . " of " . $this->max_count . ")";
				}
			}
			if ($this->max_count > 0) {
				if ($this->getEnableNumInfo()) {
					$this->tpl->setCurrentBlock("tbl_footer_numinfo");
					$this->tpl->setVariable("NUMINFO", $numinfo);
					$this->tpl->parseCurrentBlock();
				}
			}
			$footer = true;
		}

		// table footer linkbar
		if ($this->enabled["linkbar"] && $this->enabled["footer"] && $this->limit != 0
			&& $this->max_count > 0
		) {
			$layout = array(
				"link" => $this->footer_style,
				"prev" => $this->footer_previous,
				"next" => $this->footer_next,
			);
			//if (!$this->getDisplayAsBlock())
			//{
			$linkbar = $this->getLinkbar("1");
			$this->tpl->setCurrentBlock("tbl_footer_linkbar");
			$this->tpl->setVariable("LINKBAR", $linkbar);
			$this->tpl->parseCurrentBlock();
			$linkbar = true;
			//}
			$footer = true;
		}

		// column selector
		if (count($this->getSelectableColumns()) > 0) {
			$items = array();
			foreach ($this->getSelectableColumns() as $k => $c) {
				$items[$k] = array(
					"txt" => $c["txt"],
					"selected" => $this->isColumnSelected($k)
				);
			}
			include_once("./Services/UIComponent/CheckboxListOverlay/classes/class.ilCheckboxListOverlayGUI.php");
			$cb_over = new ilCheckboxListOverlayGUI("tbl_" . $this->getId());
			$cb_over->setLinkTitle($lng->txt("columns"));
			$cb_over->setItems($items);
			//$cb_over->setUrl("./ilias.php?baseClass=ilTablePropertiesStorage&table_id=".
			//		$this->getId()."&cmd=saveSelectedFields&user_id=".$ilUser->getId());
			$cb_over->setFormCmd($this->getParentCmd());
			$cb_over->setFieldVar("tblfs" . $this->getId());
			$cb_over->setHiddenVar("tblfsh" . $this->getId());
			$cb_over->setSelectionHeaderClass("ilTableMenuItem");
			$column_selector = $cb_over->getHTML();
			$footer = true;
		}

		if ($this->getShowTemplates() && is_object($ilUser)) {
			// template handling
			if (isset($_REQUEST["tbltplcrt"]) && $_REQUEST["tbltplcrt"]) {
				if ($this->saveTemplate($_REQUEST["tbltplcrt"])) {
					ilUtil::sendSuccess($lng->txt("tbl_template_created"));
				}
			} else {
				if (isset($_REQUEST["tbltpldel"]) && $_REQUEST["tbltpldel"]) {
					if ($this->deleteTemplate($_REQUEST["tbltpldel"])) {
						ilUtil::sendSuccess($lng->txt("tbl_template_deleted"));
					}
				}
			}

			$create_id = "template_create_overlay_" . $this->getId();
			$delete_id = "template_delete_overlay_" . $this->getId();
			$list_id = "template_stg_" . $this->getId();

			include_once("./Services/Table/classes/class.ilTableTemplatesStorage.php");
			$storage = new ilTableTemplatesStorage();
			$templates = $storage->getNames($this->getContext(), $ilUser->getId());

			include_once("./Services/UIComponent/Overlay/classes/class.ilOverlayGUI.php");

			// form to delete template
			if (count($templates)) {
				$overlay = new ilOverlayGUI($delete_id);
				$overlay->setTrigger($list_id . "_delete");
				$overlay->setAnchor("ilAdvSelListAnchorElement_" . $list_id);
				$overlay->setAutoHide(false);
				$overlay->add();

				$lng->loadLanguageModule("form");
				$this->tpl->setCurrentBlock("template_editor_delete_item");
				$this->tpl->setVariable("TEMPLATE_DELETE_OPTION_VALUE", "");
				$this->tpl->setVariable("TEMPLATE_DELETE_OPTION", "- " . $lng->txt("form_please_select") . " -");
				$this->tpl->parseCurrentBlock();
				foreach ($templates as $name) {
					$this->tpl->setVariable("TEMPLATE_DELETE_OPTION_VALUE", $name);
					$this->tpl->setVariable("TEMPLATE_DELETE_OPTION", $name);
					$this->tpl->parseCurrentBlock();
				}

				$this->tpl->setCurrentBlock("template_editor_delete");
				$this->tpl->setVariable("TEMPLATE_DELETE_ID", $delete_id);
				$this->tpl->setVariable("TXT_TEMPLATE_DELETE", $lng->txt("tbl_template_delete"));
				$this->tpl->setVariable("TXT_TEMPLATE_DELETE_SUBMIT", $lng->txt("delete"));
				$this->tpl->setVariable("TEMPLATE_DELETE_CMD", $this->parent_cmd);
				$this->tpl->parseCurrentBlock();
			}

			// form to save new template
			$overlay = new ilOverlayGUI($create_id);
			$overlay->setTrigger($list_id . "_create");
			$overlay->setAnchor("ilAdvSelListAnchorElement_" . $list_id);
			$overlay->setAutoHide(false);
			$overlay->add();

			$this->tpl->setCurrentBlock("template_editor");
			$this->tpl->setVariable("TEMPLATE_CREATE_ID", $create_id);
			$this->tpl->setVariable("TXT_TEMPLATE_CREATE", $lng->txt("tbl_template_create"));
			$this->tpl->setVariable("TXT_TEMPLATE_CREATE_SUBMIT", $lng->txt("save"));
			$this->tpl->setVariable("TEMPLATE_CREATE_CMD", $this->parent_cmd);
			$this->tpl->parseCurrentBlock();

			// load saved template
			include_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");
			$alist = new ilAdvancedSelectionListGUI();
			$alist->setId($list_id);
			$alist->addItem($lng->txt("tbl_template_create"), "create", "#");
			if (count($templates)) {
				$alist->addItem($lng->txt("tbl_template_delete"), "delete", "#");
				foreach ($templates as $name) {
					$ilCtrl->setParameter($this->parent_obj, $this->prefix . "_tpl", urlencode($name));
					$alist->addItem($name, $name, $ilCtrl->getLinkTarget($this->parent_obj, $this->parent_cmd));
					$ilCtrl->setParameter($this->parent_obj, $this->prefix . "_tpl", "");
				}
			}
			$alist->setListTitle($lng->txt("tbl_templates"));
			$this->tpl->setVariable("TEMPLATE_SELECTOR", "&nbsp;" . $alist->getHTML());
		}

		if ($footer) {
			$this->tpl->setCurrentBlock("tbl_footer");
			$this->tpl->setVariable("COLUMN_COUNT", $this->getColumnCount());
			if ($this->getDisplayAsBlock()) {
				$this->tpl->setVariable("BLK_CLASS", "Block");
			}
			$this->tpl->parseCurrentBlock();

			// top navigation, if number info or linkbar given
			if ($numinfo != "" || $linkbar != "" || $column_selector != ""
				|| count($this->filters) > 0
				|| count($this->optional_filters) > 0
			) {
				if (is_object($ilUser) && (count($this->filters) || count($this->optional_filters))) {
					$this->tpl->setCurrentBlock("filter_activation");
					$this->tpl->setVariable("TXT_ACTIVATE_FILTER", $lng->txt("show_filter"));
					$this->tpl->setVariable("FILA_ID", $this->getId());
					if ($this->getId() != "") {
						$this->tpl->setVariable("SAVE_URLA",
							"./ilias.php?baseClass=ilTablePropertiesStorage&table_id=" . $this->getId() . "&cmd=showFilter&user_id="
							. $ilUser->getId());
					}
					$this->tpl->parseCurrentBlock();

					if (!$this->getDisableFilterHiding()) {
						$this->tpl->setCurrentBlock("filter_deactivation");
						$this->tpl->setVariable("TXT_HIDE", $lng->txt("hide_filter"));
						if ($this->getId() != "") {
							$this->tpl->setVariable("SAVE_URL",
								"./ilias.php?baseClass=ilTablePropertiesStorage&table_id=" . $this->getId() . "&cmd=hideFilter&user_id="
								. $ilUser->getId());
							$this->tpl->setVariable("FILD_ID", $this->getId());
						}
						$this->tpl->parseCurrentBlock();
					}
				}

				if ($numinfo != "" && $this->getEnableNumInfo()) {
					$this->tpl->setCurrentBlock("top_numinfo");
					$this->tpl->setVariable("NUMINFO", $numinfo);
					$this->tpl->parseCurrentBlock();
				}
				if ($linkbar != "" && !$this->getDisplayAsBlock()) {
					$linkbar = $this->getLinkbar("2");
					$this->tpl->setCurrentBlock("top_linkbar");
					$this->tpl->setVariable("LINKBAR", $linkbar);
					$this->tpl->parseCurrentBlock();
				}

				// column selector
				$this->tpl->setVariable("COLUMN_SELECTOR", $column_selector);

				// row selector
				if ($this->getShowRowsSelector() && is_object($ilUser)) {
					include_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");
					$alist = new ilAdvancedSelectionListGUI();
					$alist->setId("sellst_rows_" . $this->getId());
					$hpp = ($ilUser->getPref("hits_per_page") != 9999) ? $ilUser->getPref("hits_per_page") : $lng->txt("unlimited");

					$options = array(
						0 => $lng->txt("default") . " (" . $hpp . ")",
						5 => 5,
						10 => 10,
						15 => 15,
						20 => 20,
						30 => 30,
						40 => 40,
						50 => 50,
						100 => 100,
						200 => 200,
						400 => 400,
						800 => 800
					);
					foreach ($options as $k => $v) {
						$ilCtrl->setParameter($this->parent_obj, $this->prefix . "_trows", $k);
						$alist->addItem($v, $k, $ilCtrl->getLinkTarget($this->parent_obj, $this->parent_cmd));
						$ilCtrl->setParameter($this->parent_obj, $this->prefix . "_trows", "");
					}
					$alist->setListTitle($this->getRowSelectorLabel() ? $this->getRowSelectorLabel() : $lng->txt("rows"));
					$this->tpl->setVariable("ROW_SELECTOR", $alist->getHTML());
				}

				// export
				if (count($this->export_formats) && $this->dataExists()) {
					include_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");
					$alist = new ilAdvancedSelectionListGUI();
					$alist->setId("sellst_xpt");
					foreach ($this->export_formats as $format => $caption_lng_id) {
						$ilCtrl->setParameter($this->parent_obj, $this->prefix . "_xpt", $format);
						$url = $ilCtrl->getLinkTarget($this->parent_obj, $this->parent_cmd);
						$ilCtrl->setParameter($this->parent_obj, $this->prefix . "_xpt", "");
						$caption = $lng->txt($caption_lng_id);
						//this part is necessary, because the labels for xls- and csv-Export are fetched from the ilias-lang-files, while the label for pdf-export is fetched from the lang-file of the plugin. If the ilias-lang-file does not contain a translation for the caption_lng_id, it will set it into '-'es. In that case the caption comes from the plugin and we just use the string that is there.
						if (strpos($caption, '-') === 0
							&& strpos($caption, '-', strlen($caption) - 1) === strlen($caption) - 1
						) //caption starts and ends with '-'
						{
							$alist->addItem($caption_lng_id, $format, $url);
						} else {
							$alist->addItem($lng->txt($caption_lng_id), $format, $url);
						}
					}
					$alist->setListTitle($lng->txt("export"));
					$this->tpl->setVariable("EXPORT_SELECTOR", "&nbsp;" . $alist->getHTML());
				}

				$this->tpl->setCurrentBlock("top_navigation");
				$this->tpl->setVariable("COLUMN_COUNT", $this->getColumnCount());
				if ($this->getDisplayAsBlock()) {
					$this->tpl->setVariable("BLK_CLASS", "Block");
				}
				$this->tpl->parseCurrentBlock();
			}
		}
	}


	/**
	 * Export the Data
	 *
	 * @param type $format
	 * @param type $send
	 */
	public function exportData($format, $send = false) {
		if ($this->dataExists()) {
			// #9640: sort
			if (!$this->getExternalSorting() && $this->enabled["sort"]) {
				$this->determineOffsetAndOrder(true);

				$this->row_data = ilUtil::sortArray($this->row_data, $this->getOrderField(), $this->getOrderDirection(), $this->numericOrdering($this->getOrderField()));
			}

			$filename = "export";

			switch ($format) {
				default:
				case self::EXPORT_EXCEL:
					include_once "./Services/Excel/classes/class.ilExcelUtils.php";
					include_once "./Services/Excel/classes/class.ilExcelWriterAdapter.php";
					$adapter = new ilExcelWriterAdapter($filename . ".xls", $send);
					$workbook = $adapter->getWorkbook();
					$worksheet = $workbook->addWorksheet();
					$row = 0;

					ob_start();
					$this->fillMetaExcel($worksheet, $row);
					$this->fillHeaderExcel($worksheet, $row);
					foreach ($this->row_data as $set) {
						$row ++;
						$this->fillRowExcel($worksheet, $row, $set);
					}
					ob_end_clean();

					$workbook->close();
					break;

				case self::EXPORT_CSV:
					include_once "./Services/Utilities/classes/class.ilCSVWriter.php";
					$csv = new ilCSVWriter();
					$csv->setSeparator(";");

					ob_start();
					$this->fillMetaCSV($csv);
					$this->fillHeaderCSV($csv);
					foreach ($this->row_data as $set) {
						$this->fillRowCSV($csv, $set);
					}
					ob_end_clean();

					if ($send) {
						$filename .= ".csv";
						header("Content-type: text/comma-separated-values");
						header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
						header("Expires: 0");
						header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
						header("Pragma: public");
						echo $csv->getCSVString();
					} else {
						file_put_contents($filename, $csv->getCSVString());
					}
					break;
				case self::EXPORT_PDF:
					include_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/appointments/bookings/class.ilRoomSharingBookingsExportTableGUI.php");
					$exportTable = new ilRoomSharingBookingsExportTableGUI($this->parent_obj, 'showBookings', $this->ref_id);
					include_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/export/class.ilRoomSharingPDFCreator.php");

					$staff = $exportTable->getTableHTML();
					ilRoomSharingPDFCreator::generatePDF($exportTable->getTableHTML(), 'D', 'file.pdf');

					break;
			}

			if ($send) {
				exit();
			}
		}
	}
}
