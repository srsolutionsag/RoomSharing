<?php

include_once './acceptance/php-webdriver/__init__.php';
include_once './acceptance/tests/ilRoomSharingAcceptanceSeleniumHelper.php';

/**
 * Testcases for selenium for filtering a room
 *
 * @group selenium-roomfilter
 * @property WebDriver $webDriver
 *
 * @author Dan Sörgel
 */
class ilRoomSharingAcceptanceRoomFilterTest extends PHPUnit_Framework_TestCase
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
	 * Tests the filter panel itself in room filter
	 * @test
	 */
	public function testFilterPanel()
	{
		self::$webDriver->findElement(WebDriverBy::linkText('Räume'))->click();

		#1: Hide Room Filter
		self::$webDriver->findElement(WebDriverBy::linkText('Filter ausblenden'))->click();
		$this->assertEquals(false,
			self::$webDriver->findElement(WebDriverBy::name('cmd[applyRoomFilter]'))->isDisplayed(),
			'#1 Hiding the RoomFilter does not hide it');

		#2: Show Room Filtere
		self::$webDriver->findElement(WebDriverBy::linkText('Filter anzeigen'))->click();
		$this->assertEquals(true,
			self::$webDriver->findElement(WebDriverBy::name('cmd[applyRoomFilter]'))->isDisplayed(),
			'#2 Showing the RoomFilter does not show it');

		#3 Apply and hide filter
		self::$webDriver->findElement(WebDriverBy::id('room_name'))->sendKeys('123');
		self::$webDriver->findElement(WebDriverBy::name('cmd[applyRoomFilter]'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Filter ausblenden'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Filter anzeigen'))->click();
		$this->assertEquals('123',
			self::$webDriver->findElement(WebDriverBy::name('room_name'))->getAttribute('value'),
			'#3 Hiding and showing the RoomFilter resets it');

		#4 Reset filter
		self::$webDriver->findElement(WebDriverBy::name('cmd[resetRoomFilter]'))->click();
		$this->assertEquals(true,
			empty(self::$webDriver->findElement(WebDriverBy::name('room_name'))->getAttribute('value')),
			'#4 Filter reseting does not work');
	}

	/**
	 * Tests the field 'room name' in room filter
	 * @test
	 */
	public function testRoomName()
	{
		self::$helper->createRoomAttribute('TEST_A');
		self::$helper->createRoom('123', 1, 20, "", " - Keine Zuordnung - ", array('TEST_A' => 2));
		self::$helper->createRoom('123a', 1, 20, "", " - Keine Zuordnung - ", array('TEST_A' => 3));

		//#1 Filter for 123
		self::$helper->applyRoomFilter('123', '', array('TEST_A' => 1));
		$this->assertEquals(2, self::$helper->getNoOfResults(), '#1 Filtering for 123 does not work');

		//#2 Filter for 123a
		self::$helper->applyRoomFilter('123a', '', array('TEST_A' => 1));
		$this->assertEquals(1, self::$helper->getNoOfResults(), '#2 Filtering for 123a does not work');
		$this->assertEquals('123a', self::$helper->getFirstResult(), '#2 Filtering for 123a does not work');

		//#3 Filter for 123b, triggers intelligent search
		self::$helper->applyRoomFilter('123b', '', array('TEST_A' => 1));
		$this->assertEquals(2, self::$helper->getNoOfResults(), '#3 Filtering for 123b does not work');

		//#4 Filter for I123, triggers intelligent search
		self::$helper->applyRoomFilter('I123', '', array('TEST_A' => 1));
		$this->assertEquals(2, self::$helper->getNoOfResults(), '#4 Filtering for I123 does not work');

		//#5 Filter for IA, should not trigger intelligent search
		self::$helper->applyRoomFilter('IA', '', array('TEST_A' => 1));
		$this->assertEquals(0, self::$helper->getNoOfResults(), '#5 Filtering for IA does not work');

		//#6 SQL evilness
		self::$helper->applyRoomFilter("0';SELECT * FROM bla;--", '', array('TEST_A' => 1));
		$this->assertEquals(0, self::$helper->getNoOfResults(), '#6 SQL Injection works');

		self::$helper->deleteAllRooms();
		self::$helper->deleteRoomAttribute('TEST_A');
	}

	/**
	 * Tests the field 'seats' in room filter
	 * @test
	 */
	public function testRoomSeats()
	{
		self::$helper->createRoomAttribute('TEST_A');
		self::$helper->createRoom('123', 1, 20, "", " - Keine Zuordnung - ", array('TEST_A' => 2));
		self::$helper->createRoom('123a', 1, 40, "", " - Keine Zuordnung - ", array('TEST_A' => 3));

		//#1 -1
		self::$helper->applyRoomFilter('', '-1', array('TEST_A' => 1));
		$this->assertEquals(2, self::$helper->getNoOfResults(),
			'#1 -1 for seats in room filter does not work');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className('ilInfoMessage'));
		}
		catch (Exception $ex)
		{
			$this->fail('#1 -1 for seats in room filter does not work');
		}

		//#2 0
		self::$helper->applyRoomFilter('', '0', array('TEST_A' => 1));
		$this->assertEquals(2, self::$helper->getNoOfResults(),
			'#2 0 for seats in room filter does not work');

		//#3 1
		self::$helper->applyRoomFilter('', '1', array('TEST_A' => 1));
		$this->assertEquals(2, self::$helper->getNoOfResults(),
			'#3 1 for seats in room filter does not work');

		//#4 20
		self::$helper->applyRoomFilter('', '20', array('TEST_A' => 1));
		$this->assertEquals(2, self::$helper->getNoOfResults(),
			'#4 20 for seats in room filter does not work');

		//#5 21
		self::$helper->applyRoomFilter('', '21', array('TEST_A' => 1));
		$this->assertEquals(1, self::$helper->getNoOfResults(),
			'#5 21 for seats in room filter does not work');
		$this->assertEquals('123a', self::$helper->getFirstResult(),
			'#5 21 for seats in room filter does not work');

		//#6 40
		self::$helper->applyRoomFilter('', '40', array('TEST_A' => 1));
		$this->assertEquals(1, self::$helper->getNoOfResults(),
			'#6 40 for seats in room filter does not work');
		$this->assertEquals('123a', self::$helper->getFirstResult(),
			'#6 40 for seats in room filter does not work');

		//#7 41
		self::$helper->applyRoomFilter('', '41', array('TEST_A' => 1));
		$this->assertEquals(1, self::$helper->getNoOfResults(),
			'#7 41 for seats in room filter does not work');
		$this->assertEquals('123a', self::$helper->getFirstResult(),
			'#7 41 for seats in room filter does not work');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className('ilInfoMessage'));
		}
		catch (Exception $ex)
		{
			$this->fail('#7 41 for seats in room filter does not work');
		}

		self::$helper->deleteAllRooms();
		self::$helper->deleteRoomAttribute('TEST_A');
	}

	/**
	 * Tests one room attribute field in room filter
	 * @test
	 */
	public function testRoomAttribute()
	{
		self::$helper->createRoomAttribute('TEST_A');
		self::$helper->createRoom('123', 1, 20, "", " - Keine Zuordnung - ", array('TEST_A' => 2));
		self::$helper->createRoom('123a', 1, 40, "", " - Keine Zuordnung - ", array('TEST_A' => 4));

		//#1 -1
		self::$helper->applyRoomFilter('', '', array('TEST_A' => -1));
		$this->assertEquals(2, self::$helper->getNoOfResults(),
			'#1 -1 for room attributes in room filter does not work');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className('ilInfoMessage'));
		}
		catch (Exception $ex)
		{
			$this->fail('#1 -1 for room attributes in room filter does not work');
		}

		//#2 0
		self::$helper->applyRoomFilter('', '', array('TEST_A' => 0));
		$this->assertEquals(2, self::$helper->getNoOfResults(),
			'#2 0 for room attributes in room filter does not work');

		//#3 1
		self::$helper->applyRoomFilter('', '', array('TEST_A' => 1));
		$this->assertEquals(2, self::$helper->getNoOfResults(),
			'#3 1 for room attributes in room filter does not work');

		//#4 2
		self::$helper->applyRoomFilter('', '', array('TEST_A' => 2));
		$this->assertEquals(2, self::$helper->getNoOfResults(),
			'#4 2 for room attributes in room filter does not work');

		//#5 3
		self::$helper->applyRoomFilter('', '', array('TEST_A' => 3));
		$this->assertEquals(1, self::$helper->getNoOfResults(),
			'#5 3 for room attributes in room filter does not work');
		$this->assertEquals('123a', self::$helper->getFirstResult(),
			'#5 3 for room attributes in room filter does not work');

		//#6 4
		self::$helper->applyRoomFilter('', '', array('TEST_A' => 4));
		$this->assertEquals(1, self::$helper->getNoOfResults(),
			'#6 4 for room attributes in room filter does not work');
		$this->assertEquals('123a', self::$helper->getFirstResult(),
			'#6 4 for for room attributes in room filter does not work');

		//#7 5
		self::$helper->applyRoomFilter('', '', array('TEST_A' => 5));
		$this->assertEquals(1, self::$helper->getNoOfResults(),
			'#7 5 for for room attributes in room filter does not work');
		$this->assertEquals('123a', self::$helper->getFirstResult(),
			'#7 5 for for room attributes in room filter does not work');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className('ilInfoMessage'));
		}
		catch (Exception $ex)
		{
			$this->fail('#7 5 for room attributes in room filter does not work');
		}

		self::$helper->deleteAllRooms();
		self::$helper->deleteRoomAttribute('TEST_A');
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
		self::$webDriver->findElement(WebDriverBy::linkText('Abmelden'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Bei ILIAS anmelden'))->click();
	}

}
