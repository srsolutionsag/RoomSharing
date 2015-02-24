<?php

include_once './acceptance/php-webdriver/__init__.php';
include_once './acceptance/tests/ilRoomSharingAcceptanceSeleniumHelper.php';

/**
 * This class represents the gui-testing for the RoomSharing System
 *
 * @group selenium-search
 * @property WebDriver $webDriver
 *
 * @author Thomas Wolscht
 * @author Dan Sörgel
 */
class ilRoomSharingAcceptanceSearchTest extends PHPUnit_Framework_TestCase
{
	private static $webDriver;
	private static $url = 'http://localhost/roomsharingsystem'; // URL to local RoomSharing
	private static $rssObjectName; // name of RoomSharing pool
	private static $login_user = 'root';
	private static $login_pass = 'homer';
	private static $helper;

	public static function setUpBeforeClass()
	{
		global $rssObjectName;
		self::$rssObjectName = $rssObjectName;
		$host = 'http://localhost:4444/wd/hub'; // this is the default
		$capabilities = DesiredCapabilities::firefox();
		self::$webDriver = RemoteWebDriver::create($host, $capabilities, 5000);
		self::$webDriver->manage()->timeouts()->implicitlyWait(3); // implicitly wait time => 3 sec.
		self::$webDriver->manage()->window()->maximize();  // maxize browser window
		self::$webDriver->get(self::$url); // go to RoomSharing System
		self::$helper = new ilRoomSharingAcceptanceSeleniumHelper(self::$webDriver, self::$rssObjectName);
	}

	public function setUp()
	{
		self::$helper->login(self::$login_user, self::$login_pass);  // login
		self::$helper->toRSS();
	}

	/**
	 * Test searchs based only on rooms.
	 * @test
	 */
	public function testRoomSearch()
	{
		//#1 Search for room only with numbers
		self::$helper->createRoomAttribute("Beamer");
		self::$helper->createRoom('123', 1, 20, "TEST", " - Keine Zuordnung - ", array('Beamer' => 3));
		self::$helper->searchForRoomByName("123");
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("123", self::$helper->getFirstResult());

		//#2 Search for room with letter
		self::$helper->createRoom('032a', 1, 20, "TEST", " - Keine Zuordnung - ", array('Beamer' => 1));
		self::$helper->searchForRoomByName("032a");
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("032a", self::$helper->getFirstResult());

		//#3 Search for 123a to trigger intelligent search
		self::$helper->searchForRoomByName("123a");
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("123", self::$helper->getFirstResult());
		try
		{
			self::$webDriver->findElement(WebDriverBy::className('ilInfoMessage'));
		}
		catch (Exception $ex)
		{
			$this->fail("#3 Intelligent Search did not give a message" . $ex);
		}

		//#4 Search for empty room
		self::$helper->searchForRoomByName("");
		$this->assertEquals("2", self::$helper->getNoOfResults());
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText('123'));
			self::$webDriver->findElement(WebDriverBy::linkText('032a'));
		}
		catch (Exception $ex)
		{
			$this->fail("#4 Searching for all rooms did not find all rooms");
		}

		//#5 Search for only letter room
		self::$helper->searchForRoomByName("IA");
		$this->assertEquals("0", self::$helper->getNoOfResults());
		self::$helper->createRoom('IA', 1, 20, "TEST", " - Keine Zuordnung - ", array('Beamer' => 1));
		self::$helper->searchForRoomByName("IA");
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("IA", self::$helper->getFirstResult());

		//#6 (intelligent) search for room with - signed numbers
		self::$helper->searchForRoomByName("-123");
		$this->assertEquals("1", self::$helper->getNoOfResults());

		//#7 (intelligent) search for room with + signed numbers
		self::$helper->searchForRoomByName("+123");
		$this->assertEquals("1", self::$helper->getNoOfResults());

		//#8 Test SQL Injection
		self::$helper->searchForRoomByName("\';SELECT * FROM usr;--");
		$this->assertEquals("0", self::$helper->getNoOfResults());

		//#9 Search for multiple rooms
		self::$helper->searchForRoomByName("2");
		$this->assertEquals("2", self::$helper->getNoOfResults());
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText('123'));
			self::$webDriver->findElement(WebDriverBy::linkText('032a'));
		}
		catch (Exception $ex)
		{
			$this->fail("#9 Searching for rooms containing 2 did not find all rooms");
		}

		self::$helper->deleteAllRooms();
	}

	/**
	 * Test searches based only on seats
	 * @test
	 */
	public function testSeatSearch()
	{
		self::$helper->createRoomAttribute("Beamer");
		self::$helper->createRoom('123', 1, 20, "TEST", " - Keine Zuordnung - ", array('Beamer' => 3));

		//#1 Search for rooms with minimum 5 seats
		self::$helper->searchForRoomByAll("", 5, "", "", "", "", "", "", "", array());
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("123", self::$helper->getFirstResult());

		//#2 Search for rooms with minimum 0 seats
		self::$helper->searchForRoomByAll("", 0, "", "", "", "", "", "", "", array());
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("123", self::$helper->getFirstResult());

		//#3 Search for rooms with more than 20 seats
		self::$helper->searchForRoomByAll("", 21, "", "", "", "", "", "", "", array());
		$this->assertEquals("0", self::$helper->getNoOfResults());

		//#4 Search for rooms with 20 seats
		self::$helper->searchForRoomByAll("", 20, "", "", "", "", "", "", "", array());
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("123", self::$helper->getFirstResult());

		//#5 Search for rooms with negative seats
		self::$helper->searchForRoomByAll("", -1, "", "", "", "", "", "", "", array());
		$this->assertEquals("0", self::$helper->getNoOfResults());

		self::$helper->createRoom('123a', 1, 10, "TEST", " - Keine Zuordnung - ", array('Beamer' => 1));

		//#6 Search for two rooms with enough seats
		self::$helper->searchForRoomByAll("", 5, "", "", "", "", "", "", "", array());
		$this->assertEquals("2", self::$helper->getNoOfResults());

		//#7 Search for one room while one is filtered out
		self::$helper->searchForRoomByAll("", 15, "", "", "", "", "", "", "", array());
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("123", self::$helper->getFirstResult());

		self::$helper->deleteAllRooms();
	}

	/**
	 * Test searches bases only on dates
	 * @test
	 */
	public function testDateSearchSyntax()
	{
		self::$helper->createRoomAttribute("Beamer");
		self::$helper->createRoom('123', 1, 20, "TEST", " - Keine Zuordnung - ", array('Beamer' => 3));

		//#1 Search for date today
		self::$helper->searchForRoomByAll("", "", date("d"), self::$helper->getCurrentMonth(), date("Y"),
			"", "", "", "", array());
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("123", self::$helper->getFirstResult());

		//#1 Search for date in future (day)
		self::$helper->searchForRoomByAll("", "", (date("d") + 1), self::$helper->getCurrentMonth(),
			date("Y"), "", "", "", "", array());
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("123", self::$helper->getFirstResult());

		//#2 Search for date in future (month)
		self::$helper->searchForRoomByAll("", "", date("d"),
			self::$helper->getCurrentMonth((date('n') % 12) + 1), date("Y"), "", "", "", "", array());
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("123", self::$helper->getFirstResult());

		//#3 Search for date in future (year)
		self::$helper->searchForRoomByAll("", "", date("d"), self::$helper->getCurrentMonth(),
			(date("Y") + 1), "", "", "", "", array());
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("123", self::$helper->getFirstResult());

		//#4 Search for date in past (day)
		self::$helper->searchForRoomByAll("", "", "01", self::$helper->getCurrentMonth(), date("Y"), "",
			"", "", "", array());
		$this->assertEquals("0", self::$helper->getNoOfResults());

		//#5 Search for date in past (month)
		self::$helper->searchForRoomByAll("", "", date("d"),
			self::$helper->getCurrentMonth((date('n') % 12) + 11), date("Y"), "", "", "", "", array());
		$this->assertEquals("0", self::$helper->getNoOfResults());

		//#6 Search for date in past (year)
		self::$helper->searchForRoomByAll("", "", date("d"), self::$helper->getCurrentMonth(),
			(date("Y") - 1), "", "", "", "", array());
		$this->assertEquals("0", self::$helper->getNoOfResults());

		self::$helper->deleteAllRooms();
	}

	/**
	 * Test searches bases only on attributes
	 * @test
	 */
	public function testAttributeSearch()
	{
		self::$helper->createRoomAttribute("Beamer");
		self::$helper->createRoom('123', 1, 20, "TEST", " - Keine Zuordnung - ", array('Beamer' => 3));

		//#1 Search for rooms with minimum 1 beamer
		self::$helper->searchForRoomByAll("", "", "", "", "", "", "", "", "", array('Beamer' => 1));
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("123", self::$helper->getFirstResult());

		//#2 Search for rooms with minimum 0 beamer
		self::$helper->searchForRoomByAll("", "", "", "", "", "", "", "", "", array('Beamer' => 0));
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("123", self::$helper->getFirstResult());

		//#3 Search for rooms with more than 3 beamer
		self::$helper->searchForRoomByAll("", "", "", "", "", "", "", "", "", array('Beamer' => 4));
		$this->assertEquals("0", self::$helper->getNoOfResults());

		//#4 Search for rooms with 3 beamer
		self::$helper->searchForRoomByAll("", "", "", "", "", "", "", "", "", array('Beamer' => 3));
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("123", self::$helper->getFirstResult());

		//#5 Search for rooms with negative beamer
		self::$helper->searchForRoomByAll("", "", "", "", "", "", "", "", "", array('Beamer' => -1));
		$this->assertEquals("0", self::$helper->getNoOfResults());

		self::$helper->createRoom('123a', 1, 20, "TEST", " - Keine Zuordnung - ", array('Beamer' => 1));

		//#6 Search for two rooms with enough beamers
		self::$helper->searchForRoomByAll("", "", "", "", "", "", "", "", "", array('Beamer' => 1));
		$this->assertEquals("2", self::$helper->getNoOfResults());

		//#7 Search for one room while one is filtered out
		self::$helper->searchForRoomByAll("", "", "", "", "", "", "", "", "", array('Beamer' => 2));
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("123", self::$helper->getFirstResult());

		self::$helper->deleteAllRooms();
	}

	/**
	 * Test searches bases only on times
	 * @test
	 */
	public function testTimeSearchSyntax()
	{
		self::$helper->createRoomAttribute("Beamer");
		self::$helper->createRoom('123', 1, 20, "TEST", " - Keine Zuordnung - ", array('Beamer' => 3));

		//#1 Search for time in future (min)
		self::$helper->searchForRoomByAll("", "", "", "", "", "", "50", "", "55", array());
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("123", self::$helper->getFirstResult());

		//#2 Search for time in future (hour)
		self::$helper->searchForRoomByAll("", "", "", "", "", "23", "", "23", "", array());
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("123", self::$helper->getFirstResult());

		//#3 Search for time in past
		self::$helper->searchForRoomByAll("", "", "", "", "", "0", "0", "0", "05", array());
		$this->assertEquals("0", self::$helper->getNoOfResults());

		//#4 Search for time in past and future
		self::$helper->searchForRoomByAll("", "", "", "", "", "0", "0", "23", "55", array());
		$this->assertEquals("0", self::$helper->getNoOfResults());

		//#5 Search for time equal in future
		self::$helper->searchForRoomByAll("", "", "", "", "", "23", "55", "23", "55", array());
		$this->assertEquals("0", self::$helper->getNoOfResults());

		//#6 Search for time to before time from
		self::$helper->searchForRoomByAll("", "", "", "", "", "23", "55", "222", "55", array());
		$this->assertEquals("0", self::$helper->getNoOfResults());

		self::$helper->deleteAllRooms();
	}

	/**
	 * Test searches bases only on dates and time, but with existing bookings
	 * @test
	 */
	public function testDateTimeSearchWithBookings()
	{
		self::$helper->createRoomAttribute("Beamer");
		self::$helper->createRoom('123', 1, 20, "TEST", " - Keine Zuordnung - ", array('Beamer' => 3));
		self::$helper->createRoom('123a', 1, 15, "TEST", " - Keine Zuordnung - ", array('Beamer' => 1));

		self::$helper->searchForRoomByAll("123", "", "", "", "", "23", "35", "23", "40", array());
		self::$webDriver->findElement(WebDriverBy::linkText('Buchen'))->click();
		self::$helper->doABooking("TEST", "", "", "", "", "", "", "", "", "", "", false);

		self::$helper->searchForRoomByAll("123", "", "", "", "", "23", "45", "23", "50", array());
		self::$webDriver->findElement(WebDriverBy::linkText('Buchen'))->click();
		self::$helper->doABooking("TEST", "", "", "", "", "", "", "", "", "", "", false);

		//#1 Search rooms before the two bookings
		self::$helper->searchForRoomByAll("", "", "", "", "", "23", "30", "23", "35", array());
		$this->assertEquals("2", self::$helper->getNoOfResults());

		//#2 Search rooms before and while one of the two bookings
		self::$helper->searchForRoomByAll("", "", "", "", "", "", "", "23", "40", array());
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("123a", self::$helper->getFirstResult());

		//#3 Search rooms before, while and after one of the two bookings
		self::$helper->searchForRoomByAll("", "", "", "", "", "", "", "23", "45", array());
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("123a", self::$helper->getFirstResult());

		//#4 Search rooms between the two bookings
		self::$helper->searchForRoomByAll("", "", "", "", "", "23", "40", "", "", array());
		$this->assertEquals("2", self::$helper->getNoOfResults());

		//#5 Search rooms while both bookings with free space inside
		self::$helper->searchForRoomByAll("", "", "", "", "", "23", "35", "23", "50", array());
		$this->assertEquals("1", self::$helper->getNoOfResults());
		$this->assertEquals("123a", self::$helper->getFirstResult());

		//#6 Search rooms after both bookings
		self::$helper->searchForRoomByAll("", "", "", "", "", "23", "50", "23", "55", array());
		$this->assertEquals("2", self::$helper->getNoOfResults());

		self::$helper->deleteAllRooms();
	}

	/**
	 * Test general search controls
	 * @test
	 */
	public function testSearchUsage()
	{
		self::$helper->createRoomAttribute("Beamer");
		self::$helper->createRoom('123', 1, 20, "TEST", " - Keine Zuordnung - ", array('Beamer' => 2));
		#1 Use "New search" Button
		self::$helper->searchForRoomByAll("123", 5, date("d"), self::$helper->getCurrentMonth(),
			(date("Y") + 1), date("H"), date("i"), "23", "55", array("Beamer" => 1));
		self::$webDriver->findElement(WebDriverBy::linkText(' Neue Suche '))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::name('cmd[applySearch]'));
		}
		catch (Exception $ex)
		{
			$this->fail("#1 Back link does not link back" . $ex);
		}

		#2 Test if search remains stable after using link
		$this->assertEquals("123",
			self::$webDriver->findElement(WebDriverBy::id('room_name'))->getAttribute("value"));
		$this->assertEquals("5",
			self::$webDriver->findElement(WebDriverBy::id('room_seats'))->getAttribute("value"));
		$this->assertEquals("1",
			self::$webDriver->findElement(WebDriverBy::id('attribute_Beamer_amount'))->getAttribute("value"));
		$options = self::$webDriver->findElements(WebDriverBy::tagName('option'));
		$counter = 0;
		foreach ($options as $option)
		{
			$selected = $option->getAttribute('selected');
			if (!empty($selected))
			{
				switch ($counter)
				{
					case 0: $this->assertEquals(date("d"), $option->getText());
						break;
					case 1: $this->assertEquals(self::$helper->getCurrentMonth(), $option->getText());
						break;
					case 2: $this->assertEquals(date("Y") + 1, $option->getText());
						break;
					case 3: $this->assertEquals(date("H"), $option->getText());
						break;
					case 4: $this->assertEquals(date("i"), $option->getText());
						break;
					case 5: $this->assertEquals("23", $option->getText());
						break;
					case 6: $this->assertEquals("55", $option->getText());
						break;
				}
				$counter++;
			}
		}

		#3 Reset the search
		self::$webDriver->findElement(WebDriverBy::name('cmd[resetSearch]'))->click();
		$this->assertEquals("",
			self::$webDriver->findElement(WebDriverBy::id('room_name'))->getAttribute("value"));
		$this->assertEquals("",
			self::$webDriver->findElement(WebDriverBy::id('room_seats'))->getAttribute("value"));
		$this->assertEquals("",
			self::$webDriver->findElement(WebDriverBy::id('attribute_Beamer_amount'))->getAttribute("value"));
		$options = self::$webDriver->findElements(WebDriverBy::tagName('option'));
		$counter = 0;
		foreach ($options as $option)
		{
			$selected = $option->getAttribute('selected');
			if (!empty($selected))
			{
				switch ($counter)
				{
					case 0: $this->assertEquals(date("d"), $option->getText());
						break;
					case 1: $this->assertEquals(self::$helper->getCurrentMonth(), $option->getText());
						break;
					case 2: $this->assertEquals(date("Y"), $option->getText());
						break;
					case 3: $this->assertEquals(date("H") + 1, $option->getText());
						break;
					case 4: $this->assertEquals(0, $option->getText());
						break;
					case 5: $this->assertEquals(date("H") + 2, $option->getText());
						break;
					case 6: $this->assertEquals(0, $option->getText());
						break;
				}
				$counter++;
			}
		}


		self::$helper->deleteAllRooms();
	}

	/**
	 * Closes web browser.
	 */
	public static function tearDownAfterClass()
	{
		self::$webDriver->quit();
	}

	public function tearDown()
	{
		self::$helper->logout();
	}

}

?>