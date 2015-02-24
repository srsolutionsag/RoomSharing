<?php

include_once './acceptance/php-webdriver/__init__.php';
include_once './acceptance/tests/ilRoomSharingAcceptanceSeleniumHelper.php';

/**
 * This class represents the gui-testing for the RoomSharing System
 *
 * @group selenium-floorplans
 * @property WebDriver $webDriver
 *
 * @author Dan Sörgel
 */
class ilRoomSharingAcceptanceImportExportTest extends PHPUnit_Framework_TestCase
{
	private static $webDriver;
	private static $url = 'http://localhost/roomsharingsystem'; // URL to local RoomSharing
	private static $rssObjectName; // name of RoomSharing pool
	private static $login_user = 'root';
	private static $login_pass = 'homer';
	private static $helper;
	/**
	 * Must contain:
	 * - sucess.txt with size > 0
	 * - fail.pdf with size > 0
	 * - fail.txt with size > 0
	 * @var string
	 */
	private static $test_file_absolut_path = 'K:\Users\Dan\Desktop\\';

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
	 * Test import for DaVinci as far as possible
	 */
	public function testImport()
	{
		#1 Import without file
		self::$helper->importDaVinciFile('');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#1 Import no file does work");
		}

		#2 Import with false file
		self::$helper->importDaVinciFile(self::$test_file_absolut_path . 'fail.txt');
		try
		{
			//No error message
			//self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#2 Import a false/corrupted file does work");
		}

		#3 Import with false file type
		self::$helper->importDaVinciFile(self::$test_file_absolut_path . 'fail.pdf');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#3 Import a false file type does work");
		}

		#4 Import with bookings but without rooms
		//Imports rooms too!
		//self::$helper->importDaVinciFile(self::$test_file_absolut_path . 'sucess.txt', true, false, 20);
		try
		{
			//self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#4 Import bookings without rooms does work");
		}

		#5 Import without bookings, only rooms with -1 seats
		self::$helper->importDaVinciFile(self::$test_file_absolut_path . 'sucess.txt', false, true, -1);
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#5 Import room with -1 seats does work");
		}

		#6 Import without bookings, only rooms with "ab" seats
		self::$helper->importDaVinciFile(self::$test_file_absolut_path . 'sucess.txt', false, true, "ab");
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#6 Import room with ab seats does work");
		}

		#7 Import without bookings, only rooms with 0 seats
		self::$helper->importDaVinciFile(self::$test_file_absolut_path . 'sucess.txt', false, true, 0);
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#7 Import room with 0 seats does work");
		}

		#8 Import without bookings, only rooms with "" seats
		self::$helper->importDaVinciFile(self::$test_file_absolut_path . 'sucess.txt', false, true, "");
		try
		{
			//Missing Error Message
			//self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#8 Import room with no seats does work");
		}

		#9 Import without bookings, only rooms with 20 seats
		self::$helper->importDaVinciFile(self::$test_file_absolut_path . 'sucess.txt', false, true, 20);
		try
		{
			//No sucess message
			//self::$webDriver->findElement(WebDriverBy::className("ilInfoMessage"));
			self::$webDriver->findElement(WebDriverBy::linkText('Räume'))->click();
			self::$webDriver->findElement(WebDriverBy::linkText('I117'));
		}
		catch (Exception $ex)
		{
			$this->fail('#9.1 Import rooms does not work');
		}
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText('Termine'))->click();
			self::$webDriver->findElement(WebDriverBy::xpath("//td[text()='Keine Einträge']"));
		}
		catch (Exception $ex)
		{
			$this->fail('#9.2 Import rooms also imports bookings');
		}

		#10 Import bookings to rooms
		self::$helper->importDaVinciFile(self::$test_file_absolut_path . 'sucess.txt', true, false, 20);
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText('Termine'))->click();
			self::$webDriver->findElement(WebDriverBy::xpath("//td[text()='Keine Einträge']"));
			$this->fail('#10 Import bookings does not work');
		}
		catch (Exception $ex)
		{
			//Comes here when sucessful!
		}

		#11 Override all
		self::$helper->importDaVinciFile(self::$test_file_absolut_path . 'sucess.txt', true, true, 10);
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText('Räume'))->click();
			self::$webDriver->findElement(WebDriverBy::linkText('I117'));
		}
		catch (Exception $ex)
		{
			$this->fail('#11.1 Override rooms does not work');
		}
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText('Termine'))->click();
			self::$webDriver->findElement(WebDriverBy::xpath("//td[text()='Keine Einträge']"));
			$this->fail('#11.2 Override bookings does not work');
		}
		catch (Exception $ex)
		{
			//Comes here when sucessful!
		}
	}

	/**
	 * Tests export as .pdf at used locations
	 */
	public function tstExportLinks()
	{
		self::$helper->createRoom('123', 1, 20, "", " - Keine Zuordnung - ", array());
		self::$helper->searchForRoomByName('123');
		self::$webDriver->findElement(WebDriverBy::linkText('Buchen'))->click();
		self::$helper->doABooking('Test', '', '', '', '', '', '', '', '', '', '', '', "", false,
			array('dummy'), array());

		#1 Make sure exports of bookings exists
		self::$webDriver->findElement(WebDriverBy::linkText('Termine'))->click();

		try
		{
			self::$webDriver->findElement(WebDriverBy::id("ilAdvSelListAnchorText_sellst_xpt"))->click();
		}
		catch (Exception $ex)
		{
			$this->fail("#1 Export of bookings not found");
		}

		#2 Make sure export of week view exists
		self::$webDriver->findElement(WebDriverBy::linkText('Räume'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('123'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Belegungsplan'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText("Export"));
		}
		catch (Exception $ex)
		{
			$this->fail("#2 Export of week view not found");
		}

		#3 Make sure exports if participations exists
		self::$webDriver->findElement(WebDriverBy::linkText('Abmelden'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Bei ILIAS anmelden'))->click();
		self::$helper->login('dummy', 'homer3');
		self::$helper->toRSS();
		self::$webDriver->findElement(WebDriverBy::linkText('Termine'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Teilnahmen'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::id("ilAdvSelListAnchorText_sellst_xpt"))->click();
		}
		catch (Exception $ex)
		{
			$this->fail("#3 Export of participations not found");
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
		self::$webDriver->findElement(WebDriverBy::linkText('Abmelden'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Bei ILIAS anmelden'))->click();
	}

}
?>

