<?php

session_start();
require_once("Services/Calendar/classes/class.ilCalendarWeekGUI.php");
require_once("Services/Calendar/classes/class.ilCalendarWeekGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingPermissionUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivilegesConstants.php");
require_once("Services/YUI/classes/class.ilYuiUtil.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/rooms/detail/class.ilRoomSharingRoom.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/rooms/detail/calendar/class.ilRoomSharingCalendarSchedule.php");
require_once("Services/Calendar/classes/class.ilCalendarSettings.php");
require_once("Services/Calendar/classes/class.ilCalendarAppointmentPanelGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/rooms/detail/calendar/class.ilRoomSharingCalendarScheduleExportTableGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/export/class.ilRoomSharingPDFCreator.php");

use ilRoomSharingPrivilegesConstants as PRIVC;

/**
 * Class ilRoomSharingCalendarWeekGUI
 *
 * Displays room-based appointments in weekview-format.
 *
 * @author       Tim Röhrig
 *
 * @ilCtrl_Calls ilRoomSharingCalendarWeekGUI: ilRoomSharingCalendarWeekGUI
 * @ilCtrl_Calls ilRoomSharingCalendarWeekGUI: ilRoomSharingCalendarScheduleExportTableGUI
 * @property ilRoomSharingPermissionUtils $permission
 */
class ilRoomSharingCalendarWeekGUI extends ilCalendarWeekGUI {

	protected $room_id;
	private $pool_id;
	// Color of appointments in week-view
	private $color = 'lightblue';
	protected $timeIntervals = array();
	protected $days = array();
	protected $bookedTimes = NULL;
	protected $format = 'Y-m-d H:i:s';
	protected $timeRows;
	private $permission;


	/**
	 * Constructor.
	 *
	 * @param ilDate  $a_seed_date
	 * @param integer $a_pool_id
	 * @param integer $a_room_id
	 */
	public function __construct(ilDate $a_seed_date, $a_pool_id, $a_room_id) {
		global $rssPermission;
		$this->permission = $rssPermission;

		$this->room_id = $a_room_id;
		$this->pool_id = $a_pool_id;
		parent::__construct($a_seed_date);
	}


	/**
	 * Adds SubTabs for the MainTab "Rooms".
	 *
	 * @param string $a_active
	 *            SubTab which should be activated after method call.
	 */
	protected function setSubTabs($a_active) {
		$this->tabs_gui->setTabActive('rooms');

		$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'room_id', $this->room_id);
		$_SESSION['last_cmd'] = 'showroom';

		// Roominfo
		$this->tabs_gui->addSubTab('room', $this->lng->txt('rep_robj_xrs_room'), $this->ctrl->getLinkTargetByClass('ilroomsharingroomgui', 'showRoom'));

		// week-view
		$this->tabs_gui->addSubTab('weekview', $this->lng->txt('rep_robj_xrs_room_occupation'), $this->ctrl->getLinkTargetByClass('ilRoomSharingCalendarWeekGUI', 'show'));
		$this->tabs_gui->activateSubTab($a_active);
	}


	/**
	 * Main switch for command execution.
	 *
	 * @return boolean always true.
	 */
	function executeCommand() {
		if ($this->ctrl->getCmd() == 'export') {
			$this->show(true);
		} else {
			parent::executeCommand();
		}

		return true;
	}


	/**
	 * Fills the table with data for whole week.
	 */
	public function show($export = false) {

		if ($export && isset($_SESSION['weeklySeed'])) {
			$this->seed = $_SESSION['weeklySeed'];
		} else {
			$_SESSION['weeklySeed'] = $this->seed;
		}

		if (!$this->permission->checkPrivilege(PRIVC::SEE_BOOKINGS_OF_ROOMS)) {
			ilUtil::sendFailure($this->lng->txt("rep_robj_xrs_no_permission_for_action"));
			$this->ctrl->redirectByClass('ilinfoscreengui', 'showSummary', 'showSummary');

			return false;
		}

		global $ilUser;
		$this->setSubTabs('weekview');

		//intervalsize
		$raster = 15;
		if ($this->user_settings->getDayStart()) {
			// push starting point to last "slot" of hour BEFORE morning aggregation
			$morning_aggr = ($this->user_settings->getDayStart() - 1) * 60 + (60 - $raster);
		} else {
			$morning_aggr = 0;
		}
		$evening_aggr = $this->user_settings->getDayEnd() * 60;

		$this->tpl = new ilTemplate('tpl.room_week_view.html', true, true, 'Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing');

		ilYuiUtil::initDragDrop();
		ilYuiUtil::initPanel();

		$navigation = new ilCalendarHeaderNavigationGUI($this, $this->seed, ilDateTime::WEEK);
		$this->tpl->setVariable('NAVIGATION', $navigation->getHTML());

		if (isset($_GET["bkid"])) {
			$user_id = $_GET["bkid"];
			$disable_empty = true;
			$no_add = true;
		} elseif ($ilUser->getId() == ANONYMOUS_USER_ID) {
			$user_id = $ilUser->getId();
			$disable_empty = false;
			$no_add = true;
		} else {
			$user_id = $ilUser->getId();
			$disable_empty = false;
			$no_add = false;
		}

		$room = new ilRoomSharingRoom($this->pool_id, $this->room_id);

		$this->tpl->setVariable('ROOM', $this->lng->txt('rep_robj_xrs_room_occupation_title') . " " . $room->getName());

		$this->scheduler = new ilRoomSharingCalendarSchedule($this->seed, ilCalendarSchedule::TYPE_WEEK, $user_id, $room);
		$this->scheduler->addSubitemCalendars(true);
		$this->scheduler->calculate();

		$counter = 0;
		$hours = NULL;
		foreach (ilCalendarUtil::_buildWeekDayList($this->seed, $this->user_settings->getWeekStart())->get() as $date) {
			$daily_apps = $this->scheduler->getByDay($date, $this->timezone);
			$hours = $this->parseHourInfo($daily_apps, $date, $counter, $hours, $morning_aggr, $evening_aggr, $raster);
			$this->weekdays[] = $date;

			$num_apps[$date->get(IL_CAL_DATE)] = count($daily_apps);

			$counter ++;
		}

		$colspans = $this->calculateColspans($hours);

		$settings = ilCalendarSettings::_getInstance();

		$exportLink = $this->ctrl->getLinkTargetByClass("ilroomsharingcalendarweekgui", "export");
		$this->tpl->setCurrentBlock("export_block");
		$this->tpl->setVariable('EXPORT_LINK', $exportLink);
		$this->tpl->parseCurrentBlock();

		// Table header
		$counter = 0;
		foreach (ilCalendarUtil::_buildWeekDayList($this->seed, $this->user_settings->getWeekStart())->get() as $date) {
			$date_info = $date->get(IL_CAL_FKT_GETDATE, '', 'UTC');
			$this->ctrl->setParameterByClass('ilcalendarappointmentgui', 'seed', $date->get(IL_CAL_DATE));
			$this->ctrl->setParameterByClass('ilcalendarappointmentgui', 'idate', $date->get(IL_CAL_DATE));
			$this->ctrl->setParameterByClass('ilcalendardaygui', 'seed', $date->get(IL_CAL_DATE));

			$dayname = ilCalendarUtil::_numericDayToString($date->get(IL_CAL_FKT_DATE, 'w'), true);
			$this->days[$counter] = $date;
			$daydate = $date_info['mday'] . ' ' . ilCalendarUtil::_numericMonthToString($date_info['mon'], false);

			if (!$disable_empty || $num_apps[$date->get(IL_CAL_DATE)] > 0) {
				$link = $this->ctrl->getLinkTargetByClass('ilcalendardaygui', '');
				$this->ctrl->clearParametersByClass('ilcalendardaygui');

				$this->tpl->setCurrentBlock("day_view1_link");
				$this->tpl->setVariable('HEADER_DATE', $daydate);
				$this->tpl->setVariable('DAY_VIEW_LINK', $link);
				$this->tpl->parseCurrentBlock();

				$this->tpl->setCurrentBlock("day_view2_link");
				$this->tpl->setVariable('DAYNAME', $dayname);
				$this->tpl->setVariable('DAY_VIEW_LINK', $link);
				$this->tpl->parseCurrentBlock();
			} else {
				$this->tpl->setCurrentBlock("day_view1_no_link");
				$this->tpl->setVariable('HEADER_DATE', $daydate);
				$this->tpl->parseCurrentBlock();

				$this->tpl->setCurrentBlock("day_view2_no_link");
				$this->tpl->setVariable('DAYNAME', $dayname);
				$this->tpl->parseCurrentBlock();
			}

			$this->tpl->setCurrentBlock('day_header_row');
			$this->tpl->setVariable('DAY_COLSPAN', max($colspans[$counter], 1));
			$this->tpl->parseCurrentBlock();

			$counter ++;
		}

		$new_link_counter = 0;
		foreach ($hours as $num_hour => $hours_per_day) {
			$first = true;
			foreach ($hours_per_day as $num_day => $hour) {
				if ($first) {
					if (!($num_hour % 60) || ($num_hour == $morning_aggr && $morning_aggr)
						|| ($num_hour == $evening_aggr && $evening_aggr)
					) {
						$first = false;
						$this->timeIntervals[] = $hours_per_day[2];

						// aggregation rows
						if (($num_hour == $morning_aggr && $morning_aggr)
							|| ($num_hour == $evening_aggr && $evening_aggr)
						) {
							$this->tpl->setVariable('TIME_ROWSPAN', 1);
						} // rastered hour
						else {
							$this->tpl->setVariable('TIME_ROWSPAN', 60 / $raster);
						}

						$this->tpl->setCurrentBlock('time_txt');

						$this->tpl->setVariable('TIME', $hour['txt']);
						$this->tpl->parseCurrentBlock();
					}
				}

				foreach ($hour['apps_start'] as $app) {
					$this->showAppointment($app);
				}

				// screen reader: appointments are divs, now output cell
				if ($ilUser->prefs["screen_reader_optimization"]) {
					$this->tpl->setCurrentBlock('scrd_day_cell');
					$this->tpl->setVariable('TD_CLASS', 'calstd');
					$this->tpl->parseCurrentBlock();
				}

				#echo "NUMDAY: ".$num_day;
				#echo "COLAPANS: ".max($colspans[$num_day],1).'<br />';
				$num_apps = $hour['apps_num'];
				$colspan = max($colspans[$num_day], 1);

				// Show new apointment link
				$userCanAddBooking = $this->permission->checkPrivilege(PRIVC::ADD_OWN_BOOKINGS);
				$calStuffAllowed = !$hour['apps_num'] && !$ilUser->prefs["screen_reader_optimization"] && !$no_add;
				if ($userCanAddBooking && $calStuffAllowed) {
					$this->tpl->setCurrentBlock('new_app_link');

					$this->tpl->setVariable('DAY_NEW_APP_LINK', $this->lng->txt('rep_robj_xrs_room_book'));
					$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'room', $room->getName());
					$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'room_id', $room->getId());
					$_SESSION['last_cmd'] = 'showroom';

					$date = $this->weekdays[$num_day]->get(IL_CAL_DATE);

					//convert time into suitable format (HH:ii:ss)
					$hr = floor($num_hour / 60);
					$hr = $hr < 10 ? "0" . $hr : $hr;

					$hr_end = floor(($num_hour + 60) / 60);
					$hr_end = $hr_end < 10 ? "0" . $hr_end : $hr_end;

					$min = floor($num_hour % 60);
					$min = $min < 10 ? "0" . $min : $min;

					$min_end = floor(($num_hour + 60) % 60);
					$min_end = $min_end < 10 ? "0" . $min_end : $min_end;

					$time_from = $hr . ":" . $min . ":00";
					$time_to = $hr_end . ":" . $min_end . ":00";

					$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'date', $date);
					$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'time_from', $time_from);
					$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'time_to', $time_to);
					$this->tpl->setVariable('DAY_NEW_APP_LINK', $this->ctrl->getLinkTargetByClass('ilobjroomsharinggui', 'book'));

					// free the parameters
					$this->ctrl->clearParametersByClass('ilobjroomsharinggui');

					$this->tpl->setVariable('DAY_NEW_APP_SRC', ilUtil::getImagePath('date_add.png'));
					$this->tpl->setVariable('DAY_NEW_APP_ALT', $this->lng->txt('cal_new_app'));
					$this->tpl->setVariable('DAY_NEW_ID', ++ $new_link_counter);
					$this->tpl->parseCurrentBlock();
				}

				for ($i = $colspan; $i > $hour['apps_num']; $i --) {
					if ($ilUser->prefs["screen_reader_optimization"]) {
						continue;
					}
					$this->tpl->setCurrentBlock('day_cell');

					// last "slot" of hour needs border
					$empty_border = '';
					if ($num_hour % 60 == 60 - $raster
						|| ($num_hour == $morning_aggr && $morning_aggr)
						|| ($num_hour == $evening_aggr && $evening_aggr)
					) {
						$empty_border = ' calempty_border';
					}

					if ($i == ($hour['apps_num'] + 1)) {
						$this->tpl->setVariable('TD_CLASS', 'calempty calrightborder' . $empty_border);
						#$this->tpl->setVariable('TD_STYLE',$add_style);
					} else {
						$this->tpl->setVariable('TD_CLASS', 'calempty' . $empty_border);
						#$this->tpl->setVariable('TD_STYLE',$add_style);
					}

					if (!$hour['apps_num']) {
						$this->tpl->setVariable('DAY_ID', $new_link_counter);
					}
					$this->tpl->setVariable('TD_ROWSPAN', 1);
					$this->tpl->parseCurrentBlock();
				}
			}
			$this->tpl->touchBlock('time_row');
		}
		$this->tpl->setVariable("TXT_TIME", $this->lng->txt("time"));

		if ($export) {
			$this->export();
		}
	}


	/**
	 * Shows informations for given appointment.
	 *
	 * @param appointment $a_app
	 */
	protected function showAppointment($a_app) {
		global $ilUser;

		$this->tpl->setCurrentBlock('panel_code');
		$this->tpl->setVariable('NUM', $this->num_appointments);
		$this->tpl->parseCurrentBlock();

		if (!$ilUser->prefs["screen_reader_optimization"]) {
			$this->tpl->setCurrentBLock('not_empty');
		} else {
			$this->tpl->setCurrentBLock('scrd_not_empty');
		}

		$this->tpl->setVariable('PANEL_DATA', ilCalendarAppointmentPanelGUI::_getInstance($this->seed)->getHTML($a_app));

		$this->ctrl->clearParametersByClass('ilcalendarappointmentgui');
		$this->ctrl->setParameterByClass('ilcalendarappointmentgui', 'app_id', $a_app['event']->getEntryId());
		$this->tpl->setVariable('APP_EDIT_LINK', $this->ctrl->getLinkTargetByClass('ilcalendarappointmentgui', 'edit'));

		$style = 'background-color: ' . $this->color . ';';
		$style .= ('color:' . ilCalendarUtil::calculateFontColor($this->color));
		$td_style = $style;

		switch ($this->user_settings->getTimeFormat()) {
			case ilCalendarSettings::TIME_FORMAT_24:
				$title = $a_app['event']->getStart()->get(IL_CAL_FKT_DATE, 'H:i', $this->timezone);
				break;

			case ilCalendarSettings::TIME_FORMAT_12:
				$title = $a_app['event']->getStart()->get(IL_CAL_FKT_DATE, 'h:ia', $this->timezone);
				break;
		}
		// add end time for screen readers
		if ($ilUser->prefs["screen_reader_optimization"]) {
			switch ($this->user_settings->getTimeFormat()) {
				case ilCalendarSettings::TIME_FORMAT_24:
					$title .= "-" . $a_app['event']->getEnd()->get(IL_CAL_FKT_DATE, 'H:i', $this->timezone);
					break;

				case ilCalendarSettings::TIME_FORMAT_12:
					$title .= "-" . $a_app['event']->getEnd()->get(IL_CAL_FKT_DATE, 'h:ia', $this->timezone);
					break;
			}
		}

		$title .= (' ' . $a_app['event']->getPresentationTitle());
		$td_style .= $a_app['event']->getPresentationStyle();

		$this->tpl->setVariable('APP_TITLE', $title);
		$this->tpl->setVariable('LINK_NUM', $this->num_appointments);

		$this->tpl->setVariable('LINK_STYLE', $style);

		if (!$ilUser->prefs["screen_reader_optimization"]) {
			// provide table cell attributes
			$this->tpl->parseCurrentBlock();

			$this->tpl->setCurrentBlock('day_cell');

			$this->tpl->setVariable('DAY_CELL_NUM', $this->num_appointments);
			$this->tpl->setVariable('TD_ROWSPAN', $a_app['rowspan']);
			$this->tpl->setVariable('TD_STYLE', $td_style);
			$this->tpl->setVariable('TD_CLASS', 'calevent');

			$this->tpl->parseCurrentBlock();
		} else {
			// screen reader: work on div attributes
			$this->tpl->setVariable('DIV_STYLE', $style);
			$this->tpl->parseCurrentBlock();
		}
		$this->num_appointments ++;
	}


	/**
	 * Set the poolID of bookings
	 *
	 * @param integer $pool_id
	 *            poolID
	 */
	public function setPoolId($pool_id) {
		$this->pool_id = $pool_id;
	}


	/**
	 * Get the PoolID of bookings
	 *
	 * @return integer PoolID
	 */
	public function getPoolId() {
		return (int)$this->pool_id;
	}


	public function export() {

		/* auslagern nach ilRoomSharingCalendarScheduleExportTableGUI */
		$this->writeRoomDataFromRoomObject();
		new ilRoomSharingCalendarScheduleExportTableGUI($this->parent_obj, 'calendARSCHeduleexport', $this->ref_id, $this);
	}


	public function writeRoomDataFromRoomObject() {

		include_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/rooms/detail/class.ilRoomSharingRoom.php");
		$room = new ilRoomSharingRoom($this->pool_id, $this->room_id);

		$bookedTimes = $room->getBookedTimes();
		$resultString = "";
		foreach ($bookedTimes as $appointment) {
			foreach ($appointment as $element) {
				$resultString .= $element;
				$resultString .= "     ";
			}
		}
		file_put_contents('bookedTimes.txt', $resultString);
		$this->buildAppointmentsArray();
		/*

		 * $timeRows = array()
		 * $i = 0
		 * foreach colspans as time
		 *     $timeRow = array()
		 *     $k = 0
		 *     for each days as day
		 *         $timeRow[k] = getTableEntry(time, day)
		 *         $k++
		 *     end for
		 *     $timeRows[i] = $timeRow
		 *     $i++
		 * end for

		 */
	}


	public function buildAppointmentsArray() {
		if ($this->timeRows == NULL) {
			$timeRows = array();
			$i = 1;
			foreach ($this->getTimeIntervals() as $num_day => $hour) {

				$timeRow = array();
				$timeRow[0] = $hour['txt'];
				$k = 1;
				foreach ($this->getDays() as $day) {
					$convertedDay = DateTime::createFromFormat('YmdHis', $day->get(6));

					$timeRow[$k] = $this->getTableEntry($hour, $convertedDay);
					$timeRows[0][$k] = $convertedDay;
					$k ++;
				}
				$timeRows[$i] = $timeRow;
				$i ++;
			}
			$this->timeRows = $timeRows;
		}

		return $this->timeRows;
	}


	public function getTimeSlots() {
		$result = array();
		foreach ($this->getTimeIntervals() as $num_day => $hour) {
			$result[] = $hour;
		}

		return result;
	}


	public function getTimeIntervals() {
		return $this->timeIntervals;
	}


	public function getDays() {
		return $this->days;
	}


	public function getTableEntry($hour, $day) {
		if ($this->bookedTimes == NULL) {
			$room = new ilRoomSharingRoom($this->pool_id, $this->room_id);
			$this->bookedTimes = $room->getBookedTimes();
		}

		$result = NULL;

		$hourString = $hour['txt'];
		$hourStart = $this->getHourStart($hourString, $day);
		$hourEnd = $this->getHourEnd($hourString, $day);

		foreach ($this->bookedTimes as $appointment) {
			$beginOfAppointment = DateTime::createFromFormat($this->format, $appointment['date_from']);
			$endOfAppointment = DateTime::createFromFormat($this->format, $appointment['date_to']);

			if (($endOfAppointment <= $hourStart) || ($beginOfAppointment >= $hourEnd)) {
			} else {
				$result = array(
					'subject' => $appointment['subject'],
					'begin' => $beginOfAppointment,
					'end' => $endOfAppointment
				);
			}
		}

		return $result;
	}


	public function getHourStart($hourString, $day) {
		// this may be simply one hour or more or less - doesn't matter for the start time!
		$hour = substr($hourString, 0, 2);
		$minute = substr($hourString, 3, 2);
		$result = clone $day;
		date_time_set($result, intval($hour), intval($minute), 00);

		return $result;
	}


	public function getHourEnd($hourString, $day) {
		if (strpos($hourString, '-') === false) {
			// this is simply one hour - the end is implicitly one hour after the start.
			$hour = substr($hourString, 0, 2);
			$minute = substr($hourString, 3, 2);
			$result = clone $day;
			date_time_set($result, intval($hour) + 1, intval($minute), 00);

			return $result;
		} else {
			// this is a longer time range divided by a '-', so the end time is given.
			return $this->getHourStart(substr($hourString, strpos($hourString, '-') + 1), $day);
		}
	}


	public function getRoomName() {
		include_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/rooms/detail/class.ilRoomSharingRoom.php");
		$room = new ilRoomSharingRoom($this->pool_id, $this->room_id);

		return $room->getName();
	}
}

?>