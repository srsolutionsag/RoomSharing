<?php

include_once("./Services/Calendar/classes/class.ilMiniCalendarGUI.php");

/**
 * This class is used to display a small calender inside of the main tab.
 *
 * @author Tim Röhrig
 */
class ilRoomSharingCalendar extends ilMiniCalendarGUI {

	protected $color = '#ff8000';
	protected $cal_cat_id;


	public function __construct($seed, $cal_id, $a_par_obj) {
		parent::__construct($seed, $a_par_obj);
		$this->cal_cat_id = $cal_id;
		$this->initCalendar();
	}


	public function getCalendarId() {
		return $this->cal_cat_id;
	}


	/**
	 * Create a new bookings calendar category.
	 *
	 * It is named after the parenting RoomSharing-oject
	 *
	 * @access protected
	 * @return
	 */
	protected function createBookingsCalendarCategory() {
		global $ilUser;

		$cat = new ilCalendarCategory();
		$cat->setColor($this->color);
		$cat->setType(ilCalendarCategory::TYPE_USR);
		$title = $this->getParentObject()->getTitle();
		$cat->setTitle($title);
		$cat->setObjId($ilUser->getId());

		return $cat->add();
	}


	/**
	 * init mini-calendar
	 *
	 * Used to display personal appointments and bookings in the minicalendar
	 * copied from ilPDBlockCalendar
	 *
	 * @access protected
	 */
	private function initCalendar() {
		global $ilUser;

		include_once('./Services/Calendar/classes/class.ilCalendarCategories.php');
		$cats = ilCalendarCategories::_getInstance($ilUser->getId());

		include_once('./Services/Calendar/classes/class.ilCalendarUserSettings.php');
		if (ilCalendarUserSettings::_getInstance()->getCalendarSelectionType() == ilCalendarUserSettings::CAL_SELECTION_MEMBERSHIP) {
			$cats->initialize(ilCalendarCategories::MODE_PERSONAL_DESKTOP_MEMBERSHIP);
		} else {
			$cats->initialize(ilCalendarCategories::MODE_PERSONAL_DESKTOP_ITEMS);
		}

		//if there was no calendar-category before or the calendar was deleted
		$categoriesInfo = $cats->getCategoriesInfo();
		if ($this->cal_cat_id == 0 || !isset($categoriesInfo[$this->cal_cat_id])) {
			//create a new calendar-category
			$this->cal_cat_id = $this->createBookingsCalendarCategory();
		}
	}
}
