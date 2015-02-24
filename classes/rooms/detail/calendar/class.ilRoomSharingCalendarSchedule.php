<?php

require_once("Services/Calendar/classes/class.ilCalendarSchedule.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/rooms/detail/calendar/class.ilRoomSharingCalendarEntry.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");

/**
 * Class ilRoomSharingCalendarSchedule
 *
 * Calculates ilRoomSharingEvents for the room-weekviek
 *
 * @author  Tim Röhrig
 * @version $Id$
 *
 */
class ilRoomSharingCalendarSchedule extends ilCalendarSchedule {

	protected $room_obj;
	private $ilRoomSharingDatabase;


	/**
	 * Constructor.
	 *
	 * @param                   ilDate seed date
	 * @param                   int    type of schedule (TYPE_DAY,TYPE_WEEK or TYPE_MONTH)
	 * @param                   int    user_id
	 * @param ilRoomSharingRoom $room
	 */
	public function __construct(ilDate $seed, $a_type, $a_user_id = 0, ilRoomSharingRoom $room) {
		global $ilUser, $ilDB;

		$this->room_obj = $room;

		$this->db = $ilDB;

		$this->type = $a_type;
		$this->initPeriod($seed);

		if (!$a_user_id || $a_user_id == $ilUser->getId()) {
			$this->user = $ilUser;
		} else {
			$this->user = new ilObjUser($a_user_id);
		}
		$this->user_settings = ilCalendarUserSettings::_getInstanceByUserId($this->user->getId());
		$this->weekstart = $this->user_settings->getWeekStart();
		$this->timezone = $this->user->getTimeZone();

		$this->ilRoomSharingDatabase = new ilRoomsharingDatabase($room->getPoolId());
	}


	/**
	 * Calculates schedules.
	 */
	public function calculate() {
		$events = $this->getEvents();

		// we need category type for booking handling
		$ids = array();
		foreach ($events as $event) {
			$ids[] = $event->getEntryId();
		}

		$counter = 0;
		foreach ($events as $event) {
			$this->schedule[$counter]['event'] = $event;
			$this->schedule[$counter]['dstart'] = $event->getStart()->get(IL_CAL_UNIX);
			$this->schedule[$counter]['dend'] = $event->getEnd()->get(IL_CAL_UNIX);
			$this->schedule[$counter]['fullday'] = $event->isFullday();

			if (!$event->isFullday()) {
				switch ($this->type) {
					case self::TYPE_DAY:
					case self::TYPE_WEEK:
						// store date info (used for calculation of overlapping events)
						$start_date = new ilDateTime($this->schedule[$counter]['dstart'], IL_CAL_UNIX, $this->timezone);
						$this->schedule[$counter]['start_info'] = $start_date->get(IL_CAL_FKT_GETDATE, '', $this->timezone);

						$end_date = new ilDateTime($this->schedule[$counter]['dend'], IL_CAL_UNIX, $this->timezone);
						$this->schedule[$counter]['end_info'] = $end_date->get(IL_CAL_FKT_GETDATE, '', $this->timezone);
						break;

					default:
						break;
				}
			}
			$counter ++;
			if ($this->areEventsLimited() && $counter >= $this->getEventsLimit()) {
				break;
			}
		}
	}


	/**
	 * Get new/changed events.
	 *
	 * @param bool $a_include_subitem_calendars E.g include session calendars of courses.
	 *
	 * @return object $events[] Array of changed events
	 */
	public function getChangedEvents($a_include_subitem_calendars = false) {
		//geht noch nicht
	}


	/**
	 * Read events (will be moved to another class, since only active and/or visible calendars are shown).
	 *
	 * @return array with calendar entries.
	 */
	public function getEvents() {
		$events = $this->ilRoomSharingDatabase->getBookingsForRoomInTimeSpan($this->room_obj->getId(), $this->start, $this->end, $this->type);

		$res = array();
		foreach ($events as $event) {
			$newEvent = new ilRoomSharingCalendarEntry($event->id);
			if ($this->isValidEventByFilters($newEvent)) {
				$res[] = $newEvent;
			}
		}

		return $res;
	}
}

?>