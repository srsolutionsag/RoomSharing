<?php

/**
 * This class holds different methods to help creating readable Selenium tests.
 *
 * @author Thomas Wolscht
 * @author Dan Sörgel
 */
class ilRoomSharingAcceptanceSeleniumHelper
{
	private $webDriver;
	private $rssObjectName;

	public function __construct($driver, $rss)
	{
		$this->webDriver = $driver;
		$this->rssObjectName = $rss;
	}

	/**
	 * Applys a new filter for rooms
	 * @param string $roomName Name of room
	 * @param integer $seats Minimum seats
	 * @param array $attributes minimum attributes as ATTR_NAME => ATTR_MINIMUM
	 */
	public function applyRoomFilter($roomName, $seats, array $attributes)
	{
		//Navigate
		$this->webDriver->findElement(WebDriverBy::linkText('Räume'))->click();

		//Input
		$this->webDriver->findElement(WebDriverBy::id('room_name'))->clear();
		$this->webDriver->findElement(WebDriverBy::id('room_name'))->sendKeys($roomName);
		$this->webDriver->findElement(WebDriverBy::id('room_seats'))->clear();
		$this->webDriver->findElement(WebDriverBy::id('room_seats'))->sendKeys($seats);
		foreach ($attributes as $attr => $min)
		{
			$this->webDriver->findElement(WebDriverBy::id('attribute_' . $attr . '_amount'))->clear();
			$this->webDriver->findElement(WebDriverBy::id('attribute_' . $attr . '_amount'))->sendKeys($min);
		}

		//Submit
		$this->webDriver->findElement(WebDriverBy::name('cmd[applyRoomFilter]'))->click();
	}

	/**
	 * Createa a new Floorplan. Needs to be in floorplans overview GUI.
	 * @param type $title Title of floorplan
	 * @param type $filePath Filepath. Absolut
	 * @param type $desc Description of Floorplan
	 */
	public function createFloorPlan($title, $filePath, $desc = "")
	{
		//Navigate
		$this->webDriver->findElement(WebDriverBy::linkText(' Gebäudeplan hinzufügen '))->click();
		//Input Data
		$this->webDriver->findElement(WebDriverBy::name('title'))->sendKeys($title);
		$this->webDriver->findElement(WebDriverBy::name('description'))->sendKeys($desc);
		$fileInput = $this->webDriver->findElement(WebDriverBy::name('upload_file'));
		$fileInput->sendKeys($filePath);

		//Submit
		$this->webDriver->findElement(WebDriverBy::name('cmd[save]'))->click();
	}

	/**
	 * Changes the first found floorplan.
	 * @param type $newTitle New title
	 * @param type $newDesc New description
	 * @param type $newFilePath New file path (absolut)
	 */
	public function changeFirstFloorPlan($newTitle, $newDesc, $newFilePath = false)
	{
		//Navigate
		$menu = $this->webDriver->findElement(WebDriverBy::xpath("//div[@id='il_center_col']/div[4]/table/tbody/tr[2]/td[4]"));
		$menu->findElement(WebDriverBy::linkText('Aktionen'))->click();
		$menu->findElement(WebDriverBy::linkText('Bearbeiten'))->click();

		//Input Data
		$this->webDriver->findElement(WebDriverBy::name('title'))->clear();
		$this->webDriver->findElement(WebDriverBy::name('title'))->sendKeys($newTitle);

		$this->webDriver->findElement(WebDriverBy::name('description'))->clear();
		$this->webDriver->findElement(WebDriverBy::name('description'))->sendKeys($newDesc);

		if ($newFilePath !== false)
		{
			$this->webDriver->findElement(WebDriverBy::id('file_mode_replace'))->click();
			$fileInput = $this->webDriver->findElement(WebDriverBy::name('upload_file'));
			$fileInput->sendKeys($newFilePath);
		}

		//Submit
		$this->webDriver->findElement(WebDriverBy::name('cmd[update]'))->click();
	}

	/**
	 * Deletes all Floorplans. Use with Caution!
	 */
	public function deleteAllFloorPlans()
	{
		try
		{
			while (true)
			{
				$menu = $this->webDriver->findElement(WebDriverBy::xpath("//div[@id='il_center_col']/div[4]/table/tbody/tr[2]/td[4]"));
				$menu->findElement(WebDriverBy::linkText('Aktionen'))->click();
				$menu->findElement(WebDriverBy::linkText('Löschen'))->click();
				$this->webDriver->findElement(WebDriverBy::name('cmd[removeFloorplan]'))->click();
			}
		}
		catch (Exception $finished)
		{

		}
	}

	/**
	 * Imports a daVinci File
	 * @param type $file absolut path to davinci file
	 * @param type $bookingImport true if bookings shoud be imported
	 * @param type $roomImport true if rooms shoud be imported
	 * @param type $defaulSeats default size of a room
	 */
	public function importDaVinciFile($file, $bookingImport = true, $roomImport = true,
		$defaulSeats = 20)
	{
		//Navigate
		$this->webDriver->findElement(WebDriverBy::linkText('Import'))->click();

		//Import
		$this->webDriver->findElement(WebDriverBy::id('upload_file'))->sendKeys($file);
		$bookingChecked = $this->webDriver->findElement(WebDriverBy::id('import_bookings'))->getAttribute('checked');
		if ((empty($bookingChecked) && $bookingImport) || (!$bookingImport && !empty($bookingChecked)))
		{
			$this->webDriver->findElement(WebDriverBy::id('import_bookings'))->click();
		}
		$roomChecked = $this->webDriver->findElement(WebDriverBy::id('import_rooms'))->getAttribute('checked');
		if ((empty($roomChecked) && $roomImport) || (!$roomImport && !empty($roomChecked)))
		{
			$this->webDriver->findElement(WebDriverBy::id('import_rooms'))->click();
		}
		$this->webDriver->findElement(WebDriverBy::id('default_cap'))->clear();
		$this->webDriver->findElement(WebDriverBy::id('default_cap'))->sendKeys($defaulSeats);

		//Submit
		$this->webDriver->findElement(WebDriverBy::name('cmd[Importieren]'))->click();
	}

	/**
	 * Createa a new bookingattribute
	 * @param string $name
	 */
	public function createBookingAttribute($name)
	{
		//Navigate
		$this->webDriver->findElement(WebDriverBy::linkText('Attribute'))->click();
		$this->webDriver->findElement(WebDriverBy::linkText('Attribute für Buchungen'))->click();
		$this->webDriver->findElement(WebDriverBy::id('radio_action_mode_create_attribute'))->click();
		//Create
		$this->webDriver->findElement(WebDriverBy::id('new_attribute_name'))->sendKeys($name);
		//Submit
		$this->webDriver->findElement(WebDriverBy::name('cmd[executeBookingAttributeAction]'))->click();
	}

	/**
	 * Changes a given bookingattribute into a new name
	 * @param string $old_name
	 * @param string $new_name
	 */
	public function changeBookingAttribute($old_name, $new_name)
	{
		//Navigate
		$this->webDriver->findElement(WebDriverBy::linkText('Attribute'))->click();
		$this->webDriver->findElement(WebDriverBy::linkText('Attribute für Buchungen'))->click();
		$this->webDriver->findElement(WebDriverBy::id('radio_action_mode_rename_attribute'))->click();
		//Change
		$this->webDriver->findElement(WebDriverBy::id('rename_attribute_id'))->sendKeys($old_name);
		$this->webDriver->findElement(WebDriverBy::id('changed_attribute_name'))->sendKeys($new_name);
		//Submit
		$this->webDriver->findElement(WebDriverBy::name('cmd[executeBookingAttributeAction]'))->click();
	}

	/**
	 * Deletes a booking attribute
	 * @param string $name
	 */
	public function deleteBookingAttribute($name)
	{
		//Navigate
		$this->webDriver->findElement(WebDriverBy::linkText('Attribute'))->click();
		$this->webDriver->findElement(WebDriverBy::linkText('Attribute für Buchungen'))->click();
		$this->webDriver->findElement(WebDriverBy::id('radio_action_mode_delete_attribute'))->click();
		//Delete
		$this->webDriver->findElement(WebDriverBy::id('del_attribute_id'))->sendKeys($name);
		//Submit
		$this->webDriver->findElement(WebDriverBy::name('cmd[executeBookingAttributeAction]'))->click();
	}

	/**
	 * Createa a new roomattribute
	 * @param string $name
	 */
	public function createRoomAttribute($name)
	{
		//Navigate
		$this->webDriver->findElement(WebDriverBy::linkText('Attribute'))->click();
		$this->webDriver->findElement(WebDriverBy::id('radio_action_mode_create_attribute'))->click();
		//Create
		$this->webDriver->findElement(WebDriverBy::id('new_attribute_name'))->sendKeys($name);
		//Submit
		$this->webDriver->findElement(WebDriverBy::name('cmd[executeRoomAttributeAction]'))->click();
	}

	/**
	 * Changes a given roomattribute into a new name
	 * @param string $old_name
	 * @param string $new_name
	 */
	public function changeRoomAttribute($old_name, $new_name)
	{
		//Navigate
		$this->webDriver->findElement(WebDriverBy::linkText('Attribute'))->click();
		$this->webDriver->findElement(WebDriverBy::id('radio_action_mode_rename_attribute'))->click();
		//Change
		$this->webDriver->findElement(WebDriverBy::id('rename_attribute_id'))->sendKeys($old_name);
		$this->webDriver->findElement(WebDriverBy::id('changed_attribute_name'))->sendKeys($new_name);
		//Submit
		$this->webDriver->findElement(WebDriverBy::name('cmd[executeRoomAttributeAction]'))->click();
	}

	/**
	 * Deletes a room attribute
	 * @param string $name
	 */
	public function deleteRoomAttribute($name)
	{
		//Navigate
		$this->webDriver->findElement(WebDriverBy::linkText('Attribute'))->click();
		$this->webDriver->findElement(WebDriverBy::id('radio_action_mode_delete_attribute'))->click();
		//Delete
		$this->webDriver->findElement(WebDriverBy::id('del_attribute_id'))->sendKeys($name);
		//Submit
		$this->webDriver->findElement(WebDriverBy::name('cmd[executeRoomAttributeAction]'))->click();
	}

	/**
	 * Createa a new room
	 * @param type $roomName
	 * @param int $min
	 * @param int $max
	 * @param string $roomType
	 * @param string $floorplan
	 * @param array $attributes Array of Attributes like [Name] => [Amount]
	 */
	public function createRoom($roomName, $min, $max, $roomType = "",
		$floorplan = " - Keine Zuordnung - ", array $attributes = array())
	{
		//Navigate
		$this->webDriver->findElement(WebDriverBy::linkText('Räume'))->click();
		$this->webDriver->findElement(WebDriverBy::linkText(' Raum hinzufügen '))->click();
		//Create
		$this->webDriver->findElement(WebDriverBy::name('name'))->sendKeys($roomName);
		$this->webDriver->findElement(WebDriverBy::name('type'))->sendKeys($roomType);
		$this->webDriver->findElement(WebDriverBy::name('min_alloc'))->sendKeys($min);
		$this->webDriver->findElement(WebDriverBy::name('max_alloc'))->sendKeys($max);
		$this->webDriver->findElement(WebDriverBy::name('file_id'))->sendKeys($floorplan);
		foreach ($attributes as $attribute => $amount)
		{
			$id = $this->webDriver->findElement(WebDriverBy::xpath("//label[text()='" . $attribute . "']"))->getAttribute('for');
			$this->webDriver->findElement(WebDriverBy::id($id))->sendKeys($amount);
		}
		//Submit
		$this->webDriver->findElement(WebDriverBy::name('cmd[createRoom]'))->click();
	}

	/**
	 * Createa a new room
	 * @param type $roomName
	 * @param int $min
	 * @param int $max
	 * @param string $roomType
	 * @param string $floorplan
	 * @param array $attributes Array of Attributes like [Name] => [Amount]
	 */
	public function editRoom($oldRoomName, $newRoomName, $min, $max, $roomType = "",
		$floorplan = " - Keine Zuordnung - ", array $attributes = array())
	{
		//Navigate
		$this->webDriver->findElement(WebDriverBy::linkText('Räume'))->click();
		$this->webDriver->findElement(WebDriverBy::linkText($oldRoomName))->click();
		$this->webDriver->findElement(WebDriverBy::linkText(' Editieren '))->click();
		//Edit
		$this->webDriver->findElement(WebDriverBy::name('name'))->clear();
		$this->webDriver->findElement(WebDriverBy::name('name'))->sendKeys($newRoomName);
		$this->webDriver->findElement(WebDriverBy::name('type'))->clear();
		$this->webDriver->findElement(WebDriverBy::name('type'))->sendKeys($roomType);
		$this->webDriver->findElement(WebDriverBy::name('min_alloc'))->clear();
		$this->webDriver->findElement(WebDriverBy::name('min_alloc'))->sendKeys($min);
		$this->webDriver->findElement(WebDriverBy::name('max_alloc'))->clear();
		$this->webDriver->findElement(WebDriverBy::name('max_alloc'))->sendKeys($max);
		$this->webDriver->findElement(WebDriverBy::name('file_id'))->sendKeys($floorplan);
		foreach ($attributes as $attribute => $amount)
		{
			$id = $this->webDriver->findElement(WebDriverBy::xpath("//label[text()='" . $attribute . "']"))->getAttribute('for');
			$this->webDriver->findElement(WebDriverBy::id($id))->clear();
			$this->webDriver->findElement(WebDriverBy::id($id))->sendKeys($amount);
		}
		//Submit
		$this->webDriver->findElement(WebDriverBy::name('cmd[saveRoom]'))->click();
	}

	/**
	 * Deletes ALL available rooms. Use with caution!
	 */
	public function deleteAllRooms()
	{
		$this->webDriver->findElement(WebDriverBy::linkText('Räume'))->click();
		$this->webDriver->findElement(WebDriverBy::name('cmd[resetRoomFilter]'))->click();
		while (true)
		{
			try
			{
				$this->webDriver->findElement(WebDriverBy::linkText('Löschen'))->click();
				$this->webDriver->findElement(WebDriverBy::name('cmd[deleteRoom]'))->click();
			}
			catch (Exception $unused)
			{
				break;
			}
		}
	}

	/**
	 * Search for room by room name.
	 * @param string $roomName Room name
	 */
	public function searchForRoomByName($roomName)
	{
		$this->webDriver->findElement(WebDriverBy::linkText('Suche'))->click();
		$this->webDriver->findElement(WebDriverBy::id('room_name'))->clear();
		$this->webDriver->findElement(WebDriverBy::id('room_name'))->sendKeys($roomName);
		$this->webDriver->findElement(WebDriverBy::name('cmd[applySearch]'))->click();
	}

	/**
	 * Creates a new privilege class
	 * @param string $className
	 * @param string $classComment
	 * @param string $roleAssign
	 * @param int $priority
	 * @param sting $copyFrom
	 */
	public function createPrivilegClass($className, $classComment = "", $roleAssign = "",
		$priority = "", $copyFrom = "Kein")
	{
		//Navigation
		$this->toRSS();
		$this->webDriver->findElement(WebDriverBy::linkText('Privilegien'))->click();
		$this->webDriver->findElement(WebDriverBy::partialLinkText('Neue Klasse anlegen'))->click();
		//Data
		$this->webDriver->findElement(WebDriverBy::id('name'))->sendKeys($className);
		$this->webDriver->findElement(WebDriverBy::id('description'))->sendKeys($classComment);
		if (!empty($roleAssign))
		{
			$this->webDriver->findElement(WebDriverBy::id('role_assignment'))->sendKeys($roleAssign);
		}
		if (!empty($priority))
		{
			$this->webDriver->findElement(WebDriverBy::id('priority'))->sendKeys($priority);
		}
		try
		{
			$this->webDriver->findElement(WebDriverBy::xpath("//label[text()='" . $copyFrom . "']"))->click();
		}
		catch (Exception $unused)
		{
			//The CopyFrom does not appear if there is no class yet
		}

		//Submit
		$this->webDriver->findElement(WebDriverBy::name('cmd[addClass]'))->click();
	}

	/**
	 * Deletes a privilege class
	 * @param string $classNameWithRole LinkText to click to delete class
	 */
	public function deletePrivilegClass($classNameWithRole)
	{
		//Navigation
		$this->webDriver->findElement(WebDriverBy::linkText('Privilegien'))->click();
		$this->webDriver->findElement(WebDriverBy::linkText($classNameWithRole))->click();
		$this->webDriver->findElement(WebDriverBy::linkText(' Klasse löschen '))->click();
		$this->webDriver->findElement(WebDriverBy::name('cmd[deleteClass]'))->click();
	}

	/**
	 * Search for room by all possible informations.
	 * @param string $roomName			Room name
	 * @param int $seats				Amount of seats
	 * @param int $day					Day of booking
	 * @param int $month				Month of booking
	 * @param int $year		    		Year of booking
	 * @param int $h_from				Hour (from)
	 * @param int $m_from				Minutes (from)
	 * @param int $h_to					Hour (to)
	 * @param int $m_to					Minutes (to)
	 * @param array $room_attributes	roomattributes as [name of attribute] => [amount]
	 */
	public function searchForRoomByAll($roomName, $seats, $day, $month, $year, $h_from, $m_from, $h_to,
		$m_to, array $room_attributes)
	{
		$this->webDriver->findElement(WebDriverBy::linkText('Suche'))->click();

		$this->webDriver->findElement(WebDriverBy::id('room_name'))->clear();
		$this->webDriver->findElement(WebDriverBy::id('room_name'))->sendKeys($roomName);

		$this->webDriver->findElement(WebDriverBy::id('room_seats'))->clear();
		$this->webDriver->findElement(WebDriverBy::id('room_seats'))->sendKeys($seats);

		$this->webDriver->findElement(WebDriverBy::id('date[date]_d'))->sendKeys($day);
		$this->webDriver->findElement(WebDriverBy::id('date[date]_m'))->sendKeys($month);
		$this->webDriver->findElement(WebDriverBy::id('date[date]_y'))->sendKeys($year);
		$this->webDriver->findElement(WebDriverBy::id('time_from[time]_h'))->sendKeys($h_from);
		$this->webDriver->findElement(WebDriverBy::id('time_from[time]_m'))->sendKeys($m_from);
		$this->webDriver->findElement(WebDriverBy::id('time_to[time]_h'))->sendKeys($h_to);
		$this->webDriver->findElement(WebDriverBy::id('time_to[time]_m'))->sendKeys($m_to);

		foreach ($room_attributes as $name => $amount)
		{
			$this->webDriver->findElement(WebDriverBy::id('attribute_' . $name . '_amount'))->clear();
			$this->webDriver->findElement(WebDriverBy::id('attribute_' . $name . '_amount'))->sendKeys($amount);
		}
		$this->webDriver->findElement(WebDriverBy::name('cmd[applySearch]'))->click();
	}

	/**
	 * Returns ID of a class by its name
	 * @param string $name Class name
	 * @return int class ID
	 */
	public function getPrivilegeClassIDByName($name)
	{
		$link_taget = $this->webDriver->findElement(WebDriverBy::linkText($name))->getAttribute('href');
		$link_taget_A_vars = explode("&", $link_taget);
		foreach ($link_taget_A_vars as $var)
		{
			if (substr($var, 0, 9) === "class_id=")
			{
				$keyAndValue = explode("=", $var);
				return $keyAndValue[1];
			}
		}
	}

	/**
	 * Changes the named privilege of the given Class ID
	 * @param string $priv_name Name of privilege to change
	 * @param string $class_id ID of the class whose privilege will be changed
	 * @return type
	 */
	public function changeAndCheckPrivilegeChange($priv_name, $class_id)
	{
		$el = $this->webDriver->findElement(WebDriverBy::name('priv[' . $class_id . '][' . $priv_name . ']'));
		$checked = $el->getAttribute('checked');
		$el->click();
		$this->webDriver->findElement(WebDriverBy::name('cmd[savePrivilegeSettings]'))->click();
		$el_saved = $this->webDriver->findElement(WebDriverBy::name('priv[' . $class_id . '][' . $priv_name . ']'));
		$checked_saved = $el_saved->getAttribute('checked');
		return (empty($checked) && !empty($checked_saved)) || (!empty($checked) && empty($checked_saved));
	}

	/**
	 * Gets current month in german language
	 * @param int $month Gets the given month instead of current
	 * @return string current month in german
	 */
	public function getCurrentMonth($month = "")
	{
		$monate = array(1 => "Januar",
			2 => "Februar",
			3 => "M&auml;rz",
			4 => "April",
			5 => "Mai",
			6 => "Juni",
			7 => "Juli",
			8 => "August",
			9 => "September",
			10 => "Oktober",
			11 => "November",
			12 => "Dezember");
		$monat = empty($month) ? date("n") : $month;
		return $monate[$monat];
	}

	/**
	 * Login to RoomSharing
	 * @param string $user User
	 * @param string $pass Password
	 */
	public function login($user, $pass)
	{
		$this->webDriver->findElement(WebDriverBy::id('username'))->sendKeys($user);
		$this->webDriver->findElement(WebDriverBy::id('password'))->sendKeys($pass)->submit();
		$this->webDriver->findElement(WebDriverBy::id('mm_rep_tr'))->click();
	}

	/**
	 * Navigate to RoomSharing Pool
	 */
	public function toRSS()
	{
		$this->webDriver->findElement(WebDriverBy::cssSelector('div.il_HeaderInner'))->click();
		$this->webDriver->findElement(WebDriverBy::id('mm_rep_tr'))->click();
		$this->webDriver->findElement(WebDriverBy::linkText('Magazin - Einstiegsseite'))->click();
		$this->webDriver->findElement(WebDriverBy::partialLinkText('MeinRoomsharingPool'))->click();
		//$this->webDriver->findElement(WebDriverBy::xpath("(//a[contains(text(),'" . $this->rssObjectName . "')])[2]"))->click();
		//$this->assertContains(self::$rssObjectName, $this->webDriver->getTitle());
	}

	/**
	 * Get current day
	 * @return string day
	 */
	public function getCurrentDay()
	{
		return date("d");
	}

	/**
	 * Get current year
	 * @return string year
	 */
	public function getCurrentYear()
	{
		return date("y");
	}

	/**
	 * Get amount of search results.
	 * @return string search results
	 */
	public function getNoOfResults()
	{
		try
		{
			$result = $this->webDriver->findElement(WebDriverBy::cssSelector('span.ilTableFootLight'))->getText();
			return substr($result, strripos($result, " ") + 1, -1);
		}
		catch (WebDriverException $exception)
		{
			return 0;
		}
	}

	/**
	 * Get first result of search.
	 * @return first result
	 */
	public function getFirstResult()
	{
		return $this->webDriver->findElement(WebDriverBy::cssSelector('td.std'))->getText();
	}

	/**
	 * Get error message.
	 * @return error message
	 */
	public function getErrMessage()
	{
		return $this->webDriver->findElement(WebDriverBy::cssSelector('div.ilFailureMessage'))->getText();
	}

	/**
	 * This method creates a booking.
	 * @param string $subject	Subject
	 * @param type $f_day		From day
	 * @param type $f_month		From Month
	 * @param type $f_year		From Year
	 * @param type $f_hour		From Hour
	 * @param type $f_minute	From Minute
	 * @param type $t_day		To Day
	 * @param type $t_month		To Month
	 * @param type $t_year		To Year
	 * @param type $t_hour		To Hour
	 * @param type $t_minute	To Minute
	 * @param bool $acc			Tick "Accept room using agreement" (Agreement must be there)
	 * @param string $comment	Comment
	 * @param bool $public		Tick "Booking is public"
	 * @param array $participants List of Participants (Must be User Names)
	 * @param array $booking_attributes List of booking attributes that shoud be used as NAME => TEXT
	 */
	public function doABooking($subject, $f_day, $f_month, $f_year, $f_hour, $f_minute, $t_day,
		$t_month, $t_year, $t_hour, $t_minute, $acc, $comment = "", $public = false,
		array $participants = array(), array $booking_attributes = array())
	{
		$this->webDriver->findElement(WebDriverBy::id('subject'))->clear();
		$this->webDriver->findElement(WebDriverBy::id('subject'))->sendKeys($subject);

		$this->webDriver->findElement(WebDriverBy::id('comment'))->clear();
		$this->webDriver->findElement(WebDriverBy::id('comment'))->sendKeys($comment);

		$this->webDriver->findElement(WebDriverBy::id('from[date]_d'))->sendKeys($f_day);
		$this->webDriver->findElement(WebDriverBy::id('from[date]_m'))->sendKeys($f_month);
		$this->webDriver->findElement(WebDriverBy::id('from[date]_y'))->sendKeys($f_year);
		$this->webDriver->findElement(WebDriverBy::id('from[time]_h'))->sendKeys($f_hour);
		$this->webDriver->findElement(WebDriverBy::id('from[time]_m'))->sendKeys($f_minute);

		$this->webDriver->findElement(WebDriverBy::id('to[date]_d'))->sendKeys($t_day);
		$this->webDriver->findElement(WebDriverBy::id('to[date]_m'))->sendKeys($t_month);
		$this->webDriver->findElement(WebDriverBy::id('to[date]_y'))->sendKeys($t_year);
		$this->webDriver->findElement(WebDriverBy::id('to[time]_h'))->sendKeys($t_hour);
		$this->webDriver->findElement(WebDriverBy::id('to[time]_m'))->sendKeys($t_minute);
		if ($acc == true)
		{
			$this->webDriver->findElement(WebDriverBy::id('accept_room_rules'))->click();
		}
		if ($public == true)
		{
			$this->webDriver->findElement(WebDriverBy::id('book_public'))->click();
		}
		foreach ($participants as $num => $participant)
		{
			$this->webDriver->findElement(WebDriverBy::id('ilMultiAdd~participants~0'))->click();
			$this->webDriver->findElement(WebDriverBy::id('participants~' . $num))->sendKeys($participant);
		}

		foreach ($booking_attributes as $name => $attribute)
		{
			$field = $this->webDriver->findElement(WebDriverBy::xpath("//label[text()='" . $name . "']"))->getAttribute('for');
			$this->webDriver->findElement(WebDriverBy::id($field))->sendKeys($attribute);
		}

		$this->webDriver->findElement(WebDriverBy::name('cmd[book]'))->click();
	}

	/**
	 * Delete a booking by subject.
	 * @param booking $subject
	 */
	public function deleteBooking($subject)
	{
		$row = $this->webDriver->findElement(WebDriverBy::xpath("//tr[contains(text(), " . $subject . ")]/td[8]"));
		$row->findElement(WebDriverBy::linkText('Stornieren'))->click();
		$this->webDriver->findElement(WebDriverBy::name('cmd[cancelBooking]'))->click();
	}

	/**
	 * Get success message.
	 * @return success message
	 */
	public function getSuccMessage()
	{
		return $this->webDriver->findElement(WebDriverBy::cssSelector('div.ilSuccessMessage'))->getText();
	}

	/*
	 * Creates a new user
	 * @param type $login		Login Name
	 * @param type $pw			Initial Password
	 * @param type $gender		Gender
	 * @param type $firstname	First Name of user
	 * @param type $lastname		Last Name of user
	 * @param type $email		Email of user - doesn't have to be valid
	 */
	public function createNewUser($login, $pw, $gender, $firstname, $lastname, $email)
	{
		//Navigate
		$this->webDriver->findElement(WebDriverBy::linkText('Administration'))->click();
		$this->webDriver->findElement(WebDriverBy::id('mm_adm_usrf'))->click();
		$this->webDriver->findElement(WebDriverBy::partialLinkText('Neuer Benutzer'))->click();

		//Input
		$this->webDriver->findElement(WebDriverBy::id('login'))->clear();
		$this->webDriver->findElement(WebDriverBy::id('login'))->sendKeys($login);
		$this->webDriver->findElement(WebDriverBy::id('passwd'))->clear();
		$this->webDriver->findElement(WebDriverBy::id('passwd'))->sendKeys($pw);
		$this->webDriver->findElement(WebDriverBy::id('passwd_retype'))->clear();
		$this->webDriver->findElement(WebDriverBy::id('passwd_retype'))->sendKeys($pw);
		$gender_id = 'gender_' . $gender;
		$this->webDriver->findElement(WebDriverBy::id($gender_id))->click();
		$this->webDriver->findElement(WebDriverBy::id('firstname'))->clear();
		$this->webDriver->findElement(WebDriverBy::id('firstname'))->sendKeys($firstname);
		$this->webDriver->findElement(WebDriverBy::id('lastname'))->clear();
		$this->webDriver->findElement(WebDriverBy::id('lastname'))->sendKeys($lastname);
		$this->webDriver->findElement(WebDriverBy::id('email'))->clear();
		$this->webDriver->findElement(WebDriverBy::id('email'))->sendKeys($email);
		$this->webDriver->findElement(WebDriverBy::cssSelector('select[id="language"] option[value="de"]'))->click();
		//Submit
		$this->webDriver->findElement(WebDriverBy::name('cmd[save]'))->click();
	}

	/*
	 * Logs out the current user and returns to the Login-Screen
	 */
	public function logout()
	{
		$this->webDriver->findElement(WebDriverBy::linkText('Abmelden'))->click();
		$this->webDriver->findElement(WebDriverBy::linkText('Bei ILIAS anmelden'))->click();
	}

	/*
	 * Login a User for the first time - he will be asked to change his Password
	 * @param type $login	Login name of uer
	 * @param type $pw		Initial Password of user
	 * @param type $newpw	New Password - can be the same as pw
	 */
	public function loginNewUserForFirstTime($login, $pw, $newpw)
	{
		$this->login($login, $pw);
		$this->webDriver->findElement(WebDriverBy::id('current_password'))->sendKeys($pw);
		$this->webDriver->findElement(WebDriverBy::id('new_password'))->sendKeys($newpw);
		$this->webDriver->findElement(WebDriverBy::id('new_password_retype'))->sendKeys($newpw);
		$this->webDriver->findElement(WebDriverBy::name('cmd[savePassword]'))->click();
	}

	/*
	 * Give a class a specific privilege
	 * @param string $priv_name	Privilege to be granted
	 * @param type $class_id		ID of the class that should have the privilege
	 */
	public function grantPrivilege($priv_name, $class_id)
	{
		$el = $this->webDriver->findElement(WebDriverBy::name('priv[' . $class_id . '][' . $priv_name . ']'));
		$checked = $el->getAttribute('checked');
		if (!$checked)
		{
			$el->click();
			$this->webDriver->findElement(WebDriverBy::name('cmd[savePrivilegeSettings]'))->click();
		}
	}

	/*
	 * Apply a Booking Filter
	 * @param string $username			User that created the booking
	 * @param type $room				Booked room
	 * @param string $subject			Subject of the Booking
	 * @param string $comment			Comment of the Booking
	 */
	public function applyBookingFilter($username = '', $room = '', $subject = '', $comment = '',
		$booking_attributes = array())
	{
		//Navigate
		$this->webDriver->findElement(WebDriverBy::linkText('Termine'))->click();

		//Input
		$this->webDriver->findElement(WebDriverBy::id('login'))->clear();
		$this->webDriver->findElement(WebDriverBy::id('login'))->sendKeys($username);
		$this->webDriver->findElement(WebDriverBy::id('room_name'))->clear();
		$this->webDriver->findElement(WebDriverBy::id('room_name'))->sendKeys($room);
		$this->webDriver->findElement(WebDriverBy::id('booking_subject'))->clear();
		$this->webDriver->findElement(WebDriverBy::id('booking_subject'))->sendKeys($subject);
		$this->webDriver->findElement(WebDriverBy::id('booking_comment'))->clear();
		$this->webDriver->findElement(WebDriverBy::id('booking_comment'))->sendKeys($comment);

		foreach ($booking_attributes as $name => $attribute)
		{
			$this->webDriver->findElement(WebDriverBy::id("attribute_" . $name . "_value"))->clear();
			$this->webDriver->findElement(WebDriverBy::id("attribute_" . $name . "_value"))
				->sendKeys($attribute);
		}


//Submit
		$this->webDriver->findElement(WebDriverBy::name('cmd[applyFilter]'))->click();
	}

	/* Deletes one user from the user-list. It cannot be specified which user to delete - but it's
	 *  impossible to delete the root user
	 */
	public function deleteUser()
	{
//Navigate
		$this->webDriver->findElement(WebDriverBy::linkText('Administration'))->click();
		$this->webDriver->findElement(WebDriverBy::id('mm_adm_usrf'))->click();
//delete
		$this->webDriver->findElement(WebDriverBy::name('id[]'))->click();
		$this->webDriver->findElement(WebDriverBy::name('selected_cmd2'))->sendKeys('Löschen');
		$this->webDriver->findElement(WebDriverBy::cssSelector('option[value="deleteUsers"]'))->click();
		$this->webDriver->findElement(WebDriverBy::cssSelector('div.ilLocator.xsmall'))->click();
		$this->webDriver->findElement(WebDriverBy::name('select_cmd2'))->click();
		$this->webDriver->findElement(WebDriverBy::name('cmd[confirmdelete]'))->click();
	}

	public function getPrivilegeClassIDByNamePartial($name)
	{
		$link_taget = $this->webDriver->findElement(WebDriverBy::partialLinkText($name . ' ⇒ User'))->getAttribute('href');
		$link_taget_A_vars = explode("&", $link_taget);
		foreach ($link_taget_A_vars as $var)
		{
			if (substr($var, 0, 9) === "class_id=")
			{
				$keyAndValue = explode("=", $var);
				return $keyAndValue[1];
			}
		}
	}

	/*
	 * Adds an Attribute for Bookings
	 * @param string $attribute_name			Name for the attribute
	 */
	public function addAttributeForBooking($attribute_name)
	{
		$this->webDriver->findElement(webDriverBy::partialLinkText('Attribute'))->click();
		$this->webDriver->findElement(webDriverBy::partialLinkText('Attribute für Buchungen'))->click();
		$this->webDriver->findElement(webDriverBy::id('radio_action_mode_create_attribute'))->click();
		$this->webDriver->findElement(webDriverBy::id('new_attribute_name'))->click();
		$this->webDriver->findElement(webDriverBy::id('new_attribute_name'))->sendKeys($attribute_name);
		$this->webDriver->findElement(webDriverBy::cssSelector('div.ilFormFooter.ilFormCommands > input[name="cmd[executeBookingAttributeAction]"]'))->click();
	}

	/*
	 * Deletes the first Attribute for Bookings found
	 */
	public function deleteOneAttributeForBooking()
	{
		$this->webDriver->findElement(webDriverBy::partialLinkText('Attribute'))->click();
		$this->webDriver->findElement(webDriverBy::partialLinkText('Attribute für Buchungen'))->click();
		$this->webDriver->findElement(webDriverBy::id('radio_action_mode_delete_attribute'))->click();
		$this->webDriver->findElement(webDriverBy::cssSelector('div.ilFormFooter.ilFormCommands > input[name="cmd[executeBookingAttributeAction]"]'))->click();
	}

	/**
	 * Fill booking form and click booking link
	 * @param string $subject	Subject
	 * @param type $f_day		From day
	 * @param type $f_month		From Month
	 * @param type $f_year		From Year
	 * @param type $f_hour		From Hour
	 * @param type $f_minute	From Minute
	 * @param type $t_day		To Day
	 * @param type $t_month		To Month
	 * @param type $t_year		To Year
	 * @param type $t_hour		To Hour
	 * @param type $t_minute	To Minute
	 * @param bool $acc		Tick "Accept room using agreement"
	 *                              (Agreement must be there)
	 * @param string $comment	Comment
	 * @param bool $public		Tick "Booking is public"
	 * @param array $participants List of Participants (Must be User Names)
	 * @param array $booking_attributes List of booking attributes
	 *                                  that shoud be used as NAME => TEXT
	 */
	public function fillBookingForm(
	$subject, $f_day, $f_month, $f_year, $f_hour, $f_minute, $t_day, $t_month, $t_year, $t_hour,
		$t_minute, $acc = true, $comment = "", $public = true, $participants = array(),
		$booking_attributes = array()
	)
	{
		$this->webDriver->findElement(WebDriverBy::id('subject'))->clear();
		$this->webDriver->findElement(WebDriverBy::id('subject'))->sendKeys($subject);

		$this->webDriver->findElement(WebDriverBy::id('comment'))->clear();
		$this->webDriver->findElement(WebDriverBy::id('comment'))->sendKeys($comment);

		//Attributes
		foreach ($booking_attributes as $name => $attribute)
		{
			$field = $this->webDriver->findElement(WebDriverBy::xpath("//label[text()='" . $name . "']"))->getAttribute('for');
			$this->webDriver->findElement(WebDriverBy::id($field))->sendKeys($attribute);
		}

		//From Date
		$f_day_keys = $this->generateSendKeysFromString($f_day, "day");
		$this->webDriver->findElement(WebDriverBy::id('from[date]_d'))->sendKeys($f_day_keys);

		$f_month_keys = $this->generateSendKeysFromString($f_month, "month");
		$this->webDriver->findElement(WebDriverBy::id('from[date]_m'))->sendKeys($f_month_keys);

		$this->webDriver->findElement(WebDriverBy::id('from[date]_y'))->sendKeys($f_year);

		$f_hour_keys = $this->generateSendKeysFromString($f_hour, "hour");
		$this->webDriver->findElement(WebDriverBy::id('from[time]_h'))->sendKeys($f_hour_keys);
		$f_minute_keys = $this->generateSendKeysFromString($f_minute, "minute");
		$this->webDriver->findElement(WebDriverBy::id('from[time]_m'))->sendKeys($f_minute_keys);

		//To Date
		$t_day_keys = $this->generateSendKeysFromString($t_day, "day");
		$this->webDriver->findElement(WebDriverBy::id('to[date]_d'))->sendKeys($t_day_keys);

		$t_month_keys = $this->generateSendKeysFromString($t_month, "month");
		$this->webDriver->findElement(WebDriverBy::id('to[date]_m'))->sendKeys($t_month_keys);

		$this->webDriver->findElement(WebDriverBy::id('to[date]_y'))->sendKeys($t_year);

		$t_hour_keys = $this->generateSendKeysFromString($t_hour, "hour");
		$this->webDriver->findElement(WebDriverBy::id('to[time]_h'))->sendKeys($t_hour_keys);

		$t_minute_keys = $this->generateSendKeysFromString($t_minute, "minute");
		$this->webDriver->findElement(WebDriverBy::id('to[time]_m'))->sendKeys($t_minute_keys);

		//Click Agreement
		if ($acc == true)
		{
			$this->webDriver->findElement(WebDriverBy::id('accept_room_rules'))->click();
		}

		//Add participants
		if (count($participants) > 0)
		{
			$this->addParticipantsToBooking($participants);
		}
	}

	/**
	 * Generates a series of inputs from a string with two numbers in it
	 * @return string Series of inputs that represent the number
	 */
	private function generateSendKeysFromString($string = "", $type = "")
	{
		$keyseries = "";

		switch ($type)
		{
			default:
			case "day":
				$number = intval($string);
				$keyseries = "01"; //Make sure to start at first day
				for ($i = 0; $i < $number - 1; $i++)
				{
					$keyseries .= WebDriverKeys::ARROW_DOWN;
				}
				break;
			case "month":
				$number = intval($string);
				$keyseries = "Ja"; //Make sure to start at January
				for ($i = 0; $i < $number - 1; $i++)
				{
					$keyseries .= WebDriverKeys::ARROW_DOWN;
				}
				break;
			case "hour":
				$number = intval($string);
				$keyseries = "00"; //Make sure to start at 00
				for ($i = 0; $i < $number - 1; $i++)
				{
					$keyseries .= WebDriverKeys::ARROW_DOWN;
				}
				break;
			case "minute":
				//Minutes only have steps of five
				$number = floor(intval($string) / 5);
				$keyseries = "00"; //Make sure to start at 00
				for ($i = 0; $i < $number; $i++)
				{
					$keyseries .= WebDriverKeys::ARROW_DOWN;
				}
				break;
		}

		return $keyseries;
	}

	/**
	 * Adds a participant to booking form
	 */
	private function addParticipantsToBooking($logins = array())
	{
		$number = count($logins);
		if ($number <= 0)
		{
			return;
		}

		for ($i = 0; $i < $number; $i++)
		{
			if ($i == 0)
			{
				$this->webDriver->findElement(WebDriverBy::id('participants'))->sendKeys($logins[0]);
			}
			else
			{
				$this->webDriver->findElement(WebDriverBy::id('participants~' . strval($i)))->sendKeys($logins[$i]);
			}
			$this->webDriver->findElement(webDriverBy::id('ilMultiAdd~participants~' . strval($i)))->click();
		}
	}

	/**
	 * Deletes the first booking
	 */
	public function deleteFirstBooking()
	{
		$this->webDriver->findElement(WebDriverBy::linkText('Stornieren'))->click();
		$this->webDriver->findElement(WebDriverBy::name('cmd[cancelBooking]'))->click();
	}

}
