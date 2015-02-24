<?php

include_once './acceptance/php-webdriver/__init__.php';
include_once './acceptance/tests/ilRoomSharingAcceptanceSeleniumHelper.php';

/**
 * This class represents the gui-testing for the RoomSharing System
 *
 * @group selenium-floorplans
 * @property WebDriver $webDriver
 *
 * @author Thomas Wolscht
 * @author Dan Sörgel
 */
class ilRoomSharingAcceptanceFloorPlansTest extends PHPUnit_Framework_TestCase
{
	private static $webDriver;
	private static $url = 'http://localhost/roomsharingsystem'; // URL to local RoomSharing
	private static $rssObjectName; // name of RoomSharing pool
	private static $login_user = 'root';
	private static $login_pass = 'homer';
	/**
	 * Must contain:
	 * - sucess.jpg with size > 0
	 * - sucess.bmp with size > 0
	 * - fail.pdf with size > 0
	 * - fail.txt with size > 0
	 * - empty.txt with size = 0
	 * - big.jpg with size bigger than upload limit
	 * @var string
	 */
	private static $test_file_absolut_path = 'K:\Users\Dan\Desktop\\';
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
	 * Test functions for adding new floorplans.
	 * @test
	 */
	public function testAddingFloorPlans()
	{
		self::$webDriver->findElement(WebDriverBy::linkText('Gebäudeplan'))->click();

		//#1 Test for Back Links from Adding a floor plan
		self::$webDriver->findElement(WebDriverBy::linkText(' Gebäudeplan hinzufügen '))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Gebäudeplänen'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText('Gebäudepläne anzeigen'));
		}
		catch (Exception $ex)
		{
			$this->fail("#1.1: Return from adding floorplans to floorplans overview via backtab does not work.");
		}
		self::$webDriver->findElement(WebDriverBy::linkText(' Gebäudeplan hinzufügen '))->click();
		self::$webDriver->findElement(WebDriverBy::name('cmd[render]'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText('Gebäudepläne anzeigen'));
		}
		catch (Exception $ex)
		{
			$this->fail("#1.2: Return from adding floorplans to floorplans overview via backtab does not work.");
		}

		//#2 Test empty Title
		self::$helper->createFloorPlan('', self::$test_file_absolut_path . 'sucess.jpg', 'Test');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#2 Add an no-title floorplan seems to work");
		}
		self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Gebäudeplänen'))->click();

		//#3 Test empty Plan
		self::$helper->createFloorPlan('Test_A', '', 'Test');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#3 Add an no-plan floorplan seems to work");
		}
		self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Gebäudeplänen'))->click();

		//#4 Test empty.txt
		self::$helper->createFloorPlan('Test_A', self::$test_file_absolut_path . 'empty.jpg', 'Test');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#4 Add an empty picture as floorplan seems to work");
		}
		self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Gebäudeplänen'))->click();
		//#5 Test big.jpg
		self::$helper->createFloorPlan('Test_A', self::$test_file_absolut_path . 'big.jpg', 'Test');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#5 Add an too big picture as floorplan seems to work");
		}
		self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Gebäudeplänen'))->click();

		//#6 Test fail.txt
		self::$helper->createFloorPlan('Test_A', self::$test_file_absolut_path . 'fail.txt', 'Test');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#6 Add an txt as floorplan seems to work");
		}
		self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Gebäudeplänen'))->click();

		//#7 Test fail.pdf
		self::$helper->createFloorPlan('Test_A', self::$test_file_absolut_path . 'fail.pdf', 'Test');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#7 Add an pdf as floorplan seems to work");
		}
		self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Gebäudeplänen'))->click();

		//#8 Test sucess.jpg
		self::$helper->createFloorPlan('Test_A', self::$test_file_absolut_path . 'sucess.jpg', 'Test');
		try
		{
			self::$webDriver->findElement(WebDriverBy::xpath("//a[contains(text(),'Test_A')]"));
		}
		catch (Exception $ex)
		{
			$this->fail("#8 Adding a jpg as floorplan seems not to work");
		}

		//#9 Test sucess.bmp
		self::$helper->createFloorPlan('Test_B', self::$test_file_absolut_path . 'sucess.bmp', 'Test2');
		try
		{
			self::$webDriver->findElement(WebDriverBy::xpath("//a[contains(text(),'Test_B')]"));
		}
		catch (Exception $ex)
		{
			$this->fail("#9 Adding a bmp as floorplan seems not to work");
		}

		//#10 Add existing Title
		self::$helper->createFloorPlan('Test_B', self::$test_file_absolut_path . 'sucess.bmp', 'Test2');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#10 Adding an existing title seems to work");
		}
		self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Gebäudeplänen'))->click();

		self::$helper->deleteAllFloorPlans();
	}

	/**
	 * Tests Changing and Deletion of a floorplan
	 * @test
	 */
	public function testEditAndDeleteFloorPlans()
	{
		self::$webDriver->findElement(WebDriverBy::linkText('Gebäudeplan'))->click();
		self::$helper->createFloorPlan('TEST_A', self::$test_file_absolut_path . 'sucess.jpg', 'Test');
		self::$helper->createFloorPlan('TEST_B', self::$test_file_absolut_path . 'sucess.jpg', 'Test');
		//#1 Check links from update back to overview
		$menu = self::$webDriver->findElement(WebDriverBy::xpath("//div[@id='il_center_col']/div[4]/table/tbody/tr[2]/td[4]"));
		$menu->findElement(WebDriverBy::linkText('Aktionen'))->click();
		$menu->findElement(WebDriverBy::linkText('Bearbeiten'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Gebäudeplänen'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText('Gebäudepläne anzeigen'));
		}
		catch (Exception $ex)
		{
			$this->fail('#1.1 Backtab from update a floorplan does not link back to overview' . $ex);
		}
		$menu = self::$webDriver->findElement(WebDriverBy::xpath("//div[@id='il_center_col']/div[4]/table/tbody/tr[2]/td[4]"));
		$menu->findElement(WebDriverBy::linkText('Aktionen'))->click();
		$menu->findElement(WebDriverBy::linkText('Bearbeiten'))->click();
		self::$webDriver->findElement(WebDriverBy::name('cmd[render]'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText('Gebäudepläne anzeigen'));
		}
		catch (Exception $ex)
		{
			$this->fail('#1.2 Cancel from update a floorplan does not link back to overview' . $ex);
		}

		//#1b Check Links back from deletion
		$menu = self::$webDriver->findElement(WebDriverBy::xpath("//div[@id='il_center_col']/div[4]/table/tbody/tr[2]/td[4]"));
		$menu->findElement(WebDriverBy::linkText('Aktionen'))->click();
		$menu->findElement(WebDriverBy::linkText('Löschen'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Gebäudeplänen'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText('Gebäudepläne anzeigen'));
		}
		catch (Exception $ex)
		{
			$this->fail('#1.1b Backtab from deletion a floorplan does not link back to overview' . $ex);
		}
		$menu = self::$webDriver->findElement(WebDriverBy::xpath("//div[@id='il_center_col']/div[4]/table/tbody/tr[2]/td[4]"));
		$menu->findElement(WebDriverBy::linkText('Aktionen'))->click();
		$menu->findElement(WebDriverBy::linkText('Löschen'))->click();
		self::$webDriver->findElement(WebDriverBy::name('cmd[render]'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText('Gebäudepläne anzeigen'));
		}
		catch (Exception $ex)
		{
			$this->fail('#1.2b Cancel from update a floorplan does not link back to overview' . $ex);
		}

		//#2 Changing Title to a new one
		self::$helper->changeFirstFloorPlan('TEST_AA', "");
		$menu = self::$webDriver->findElement(WebDriverBy::xpath("//div[@id='il_center_col']/div[4]/table/tbody/tr[2]/td[4]"));
		$menu->findElement(WebDriverBy::linkText('Aktionen'))->click();
		$menu->findElement(WebDriverBy::linkText('Bearbeiten'))->click();
		$newName = self::$webDriver->findElement(WebDriverBy::id("title"))->getAttribute('value');
		$this->assertEquals($newName, 'TEST_AA', '#2 Update title of floorplan does not work. ');
		self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Gebäudeplänen'))->click();

		//#3 Changing Title to an existing one (fails atm)
		self::$helper->changeFirstFloorPlan('TEST_A', "");
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
			self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Gebäudeplänen'))->click();
		}
		catch (Exception $ex)
		{
			$this->fail('#3 Update title to existing floorplan works. ' . $ex);
		}

		//#4 Changing Title to empty
		self::$helper->changeFirstFloorPlan('', "");
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail('#4 Update floorplan title to nothing works. ' . $ex);
		}
		self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Gebäudeplänen'))->click();

		//#5 Test empty Plan
		self::$helper->changeFirstFloorPlan('Test_B', '', ' ');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#5 Update to no-plan floorplan seems to work");
		}
		self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Gebäudeplänen'))->click();

		//#6 Test empty.jpg
		self::$helper->changeFirstFloorPlan('Test_B', '', self::$test_file_absolut_path . 'empty.jpg');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#6 Add an empty picture as floorplan seems to work");
		}
		self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Gebäudeplänen'))->click();

		//#7 Test big.jpg
		self::$helper->changeFirstFloorPlan('Test_B', '', self::$test_file_absolut_path . 'big.jpg');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#7 Add an too big picture as floorplan seems to work");
		}
		self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Gebäudeplänen'))->click();

		//#8 Test fail.txt
		self::$helper->changeFirstFloorPlan('Test_B', '', self::$test_file_absolut_path . 'fail.txt');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#8 Update a txt as floorplan seems to work");
		}
		self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Gebäudeplänen'))->click();

		//#9 Test fail.pdf
		self::$helper->changeFirstFloorPlan('Test_B', '', self::$test_file_absolut_path . 'fail.pdf');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#9 Update a pdf as floorplan seems to work");
		}
		self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Gebäudeplänen'))->click();

		//#10 Test sucess.jpg
		self::$helper->changeFirstFloorPlan('Test_B', '', self::$test_file_absolut_path . 'sucess.jpg');
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText(" Test_B"));
		}
		catch (Exception $ex)
		{
			$this->fail("#10 Update a jpg as floorplan seems not to work");
		}

		//#11 Test sucess.bmp
		self::$helper->changeFirstFloorPlan('Test_B', '', self::$test_file_absolut_path . 'sucess.bmp');
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText(" Test_B"));
		}
		catch (Exception $ex)
		{
			$this->fail("#11 Update a bmp as floorplan seems not to work");
		}

		self::$helper->deleteAllFloorPlans();
	}

	/**
	 * Tests the effects of changing a floorplan which is assigend to a room
	 * @test
	 */
	public function testAssignmentEffectsOfFloorplans()
	{
		self::$webDriver->findElement(WebDriverBy::linkText('Gebäudeplan'))->click();
		self::$helper->createFloorPlan('Test_A', self::$test_file_absolut_path . 'sucess.jpg', 'Test');
		//#1 Use a new floorplan by creating a room with it
		self::$helper->createRoom('123', '1', '20', "TEST", "Test_A", array());
		self::$webDriver->findElement(WebDriverBy::linkText(' Zurück zu allen Räumen '))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('123'))->click();
		$text = self::$webDriver->findElement(WebDriverBy::xpath("//option[text()='Test_A']"))->getAttribute('selected');
		$this->assertEquals(false, empty($text), '#1 Using a new floorplan on a room has failed!');

		//#2 Change a floorplan which is assigend to a room
		self::$webDriver->findElement(WebDriverBy::linkText('Gebäudeplan'))->click();
		$menu = self::$webDriver->findElement(WebDriverBy::xpath("//div[@id='il_center_col']/div[4]/table/tbody/tr[2]/td[4]"));
		$menu->findElement(WebDriverBy::linkText('Aktionen'))->click();
		$menu->findElement(WebDriverBy::linkText('Bearbeiten'))->click();
		self::$webDriver->findElement(WebDriverBy::id('title'))->clear();
		self::$webDriver->findElement(WebDriverBy::id('title'))->sendKeys('Test_B');
		self::$webDriver->findElement(WebDriverBy::id('file_mode_replace'))->click();
		self::$webDriver->findElement(WebDriverBy::id('upload_file'))->sendKeys(self::$test_file_absolut_path . 'sucess.jpg');
		self::$webDriver->findElement(WebDriverBy::name('cmd[update]'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Räume'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('123'))->click();
		$text = self::$webDriver->findElement(WebDriverBy::xpath("//option[text()='Test_B']"))->getAttribute('selected');
		$this->assertEquals(false, empty($text), '#2 Changing an assigend floorplan has failed!');

		//#3 Delete an assigned floorplan
		self::$webDriver->findElement(WebDriverBy::linkText('Gebäudeplan'))->click();
		self::$helper->deleteAllFloorPlans();
		self::$webDriver->findElement(WebDriverBy::linkText('Räume'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('123'))->click();
		$text = self::$webDriver->findElement(WebDriverBy::xpath("//option[text()=' - Keine Zuordnung - ']"))->getAttribute('selected');
		$this->assertEquals(false, empty($text), '#3 Deleting an assigend floorplan has failed!');

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
