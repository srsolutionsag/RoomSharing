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
class ilRoomSharingAcceptanceRoomTest extends PHPUnit_Framework_TestCase
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

////////////////////////////////////////////////////////////////////////////////////////////////////
// tests // tests // tests // tests // tests // tests // tests // tests // tests // tests // tests /
////////////////////////////////////////////////////////////////////////////////////////////////////
// create room with empty fields -> fail                                                           /
// create room with empty name but valid seats -> fail                                             /
// create room with valid name but empty seats -> fail                                             /
// create room with valid name but negative seats -> fail                                          /
// create room with valid name but min seats > max seats -> fail                                   /
// create room1 with valid name1 and valid seats and type and negative min seats -> success        /
// create room with valid name1 and valid seats and type and positive min seats -> fail            /
// create room2 with valid name2 and valid seats and type and positive min seats -> success        /
////////////////////////////////////////////////////////////////////////////////////////////////////
// edit room1: change name to name2 -> fail                                                        /
// edit room1: change name to empty string -> fail                                                 /
// edit room1: change seats to empty string -> fail                                                /
// edit room1: change seats to less than min seats -> fail                                                   /
// edit room1: change seats to negative seats -> fail                                              /
// edit room1: change name to name3 -> success                                                     /
// edit room2: change name to name1 -> success                                                     /
// edit room1: change name to name2 -> success                                                     /
////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	 * Tests the creation of rooms.
	 * @ test
	 *
	 */
	public function testRoomCreation()
	{
		// create room with empty fields -> fail
		self::$helper->createRoom('', '', '', "", " - Keine Zuordnung - ");
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#1 Creation of a room with empty fields has succeeded" . $ex);
		}

		// create room with empty name but valid seats -> fail
		self::$helper->createRoom('', 1, 20, "", " - Keine Zuordnung - ");
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#2 Creation of a room with empty name has succeeded" . $ex);
		}

		// create room with valid name but empty seats -> fail
		self::$helper->createRoom('name1', '', '', "", " - Keine Zuordnung - ");
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#3 Creation of a room with empty seats has succeeded" . $ex);
		}

		// create room with valid name but negative seats -> fail
		self::$helper->createRoom('name1', 1, -1, "", " - Keine Zuordnung - ");
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#4 Creation of a room with negative seats has succeeded" . $ex);
		}

		// create room with valid name but min seats > max seats -> fail
		self::$helper->createRoom('name1', 2, 1, "", " - Keine Zuordnung - ");
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#5 Creation of a room with min seats > max seats has succeeded" . $ex);
		}

		// create room1 with valid name1 and valid seats and type and negative min seats -> success
		self::$helper->createRoom('name1', 1, 2, "type1", " - Keine Zuordnung - ");
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText("name1"));
		}
		catch (Exception $ex)
		{
			$this->fail("#6 Creation of a room with valid fields has failed" . $ex);
		}

		// create room with valid name1 and valid seats and type and positive min seats -> fail
		self::$helper->createRoom('name1', 1, 2, "type2", " - Keine Zuordnung - ");
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#7 Creation of a room with existing fields has succeeded" . $ex);
		}

		// create room2 with valid name2 and valid seats and type and positive min seats -> success
		self::$helper->createRoom('name2', 1, 2, "type2", " - Keine Zuordnung - ");
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText("name1"));
			self::$webDriver->findElement(WebDriverBy::linkText("name2"));
		}
		catch (Exception $ex)
		{
			$this->fail("#8 Creation of a room with valid fields has failed" . $ex);
		}

		self::$helper->deleteAllRooms();
	}

	/**
	 * Tests the editing of rooms.
	 * @test
	 */
	public function testRoomEditing()
	{
		self::$helper->createRoom('name1', 1, 2, "type1", " - Keine Zuordnung - ");
		self::$helper->createRoom('name2', 1, 2, "type2", " - Keine Zuordnung - ");

		// edit room1: change name to name2 -> fail
		self::$helper->editRoom('name1', 'name2', 1, 2, "type1", " - Keine Zuordnung - ");
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#1 Editing of a room name to existing name has succeeded" . $ex);
		}

		// edit room1: change name to empty string -> fail
		self::$helper->editRoom('name1', '', 1, 2, "type1", " - Keine Zuordnung - ");
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#2 Editing of a room name to empty string has succeeded" . $ex);
		}

		// edit room1: change seats to empty string -> fail
		self::$helper->editRoom('name1', 'name1', 1, '', "type1", " - Keine Zuordnung - ");
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#3 Editing of room seats to empty string has succeeded" . $ex);
		}
		// edit room1: change seats to less than min seats -> fail
		self::$helper->editRoom('name1', 'name1', 2, 1, "type1", " - Keine Zuordnung - ");
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#4 Editing of room seats to less than min seats has succeeded" . $ex);
		}

		// edit room1: change seats to negative seats -> fail
		self::$helper->editRoom('name1', 'name1', 1, -1, "type1", " - Keine Zuordnung - ");
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#5 Editing of room seats negative seats has succeeded" . $ex);
		}

		// edit room1: change name to name3 -> success
		self::$helper->editRoom('name1', 'name3', 1, 2, "type1", " - Keine Zuordnung - ");
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText("name3"));
			self::$webDriver->findElement(WebDriverBy::linkText("name2"));
		}
		catch (Exception $ex)
		{
			$this->fail("#6 Editing of room name has failed" . $ex);
		}

		// edit room2: change name to name1 -> success
		self::$helper->editRoom('name2', 'name1', 1, 2, "type2", " - Keine Zuordnung - ");
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText("name3"));
			self::$webDriver->findElement(WebDriverBy::linkText("name1"));
		}
		catch (Exception $ex)
		{
			$this->fail("#7 Editing of room name has failed" . $ex);
		}

		// edit room1: change name to name2 -> success
		self::$helper->editRoom('name3', 'name2', 1, 2, "type1", " - Keine Zuordnung - ");
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText("name2"));
			self::$webDriver->findElement(WebDriverBy::linkText("name1"));
		}
		catch (Exception $ex)
		{
			$this->fail("#8 Editing of room name has failed" . $ex);
		}
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
		self::$helper->deleteAllRooms();
		self::$webDriver->findElement(WebDriverBy::linkText('Abmelden'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Bei ILIAS anmelden'))->click();
	}

}
