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
class ilRoomSharingAcceptanceAttributeGUITest extends PHPUnit_Framework_TestCase
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
	 * @test
	 */
	public function testRoomattributeManagement()
	{
		//#1 Create a new attribute
		self::$helper->createRoomAttribute("Room_A");
		try
		{
			self::$webDriver->findElement(WebDriverBy::id('radio_action_mode_rename_attribute'))->click();
			self::$webDriver->findElement(WebDriverBy::xpath("//option[text()='Room_A']"));
		}
		catch (Exception $ex)
		{
			$this->fail("#1 Creation of a new room attribute has failed" . $ex);
		}

		//#2 Create an empty attribute
		self::$helper->createRoomAttribute("");
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#2 Creation of an empty room attribute has succeded" . $ex);
		}

		//#3 Create an existing attribute
		self::$helper->createRoomAttribute("Room_A");
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#3 Creation of an existing room attribute has succeded" . $ex);
		}

		//#4 Create a new attribute with html tags
		self::$helper->createRoomAttribute("<b><u>Room_B</u></b>");
		try
		{
			self::$webDriver->findElement(WebDriverBy::id('radio_action_mode_rename_attribute'))->click();
			self::$webDriver->findElement(WebDriverBy::xpath("//option[text()='Room_B']"));
		}
		catch (Exception $ex)
		{
			$this->fail("#4 Creation of a new room attribute with HTML has failed" . $ex);
		}
		self::$helper->changeRoomAttribute('Room_B', 'Room_B');

		//#5 Check if creation rooms GUI have the new attributes
		self::$webDriver->findElement(WebDriverBy::linkText("Räume"))->click();
		self::$webDriver->findElement(WebDriverBy::linkText(" Raum hinzufügen "))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::xpath("//label[text()='Room_A']"));
			self::$webDriver->findElement(WebDriverBy::xpath("//label[text()='Room_B']"));
		}
		catch (Exception $ex)
		{
			$this->fail("#5 Creation rooms GUI does not have created attributes" . $ex);
		}
		self::$helper->createRoom("123", 1, 20, "TEST", " - Keine Zuordnung - ",
			array('Room_A' => 2, 'Room_B' => 3));

		//#6 Change name of a room attribute into a new
		self::$helper->changeRoomAttribute('Room_A', 'Room_C');
		try
		{
			self::$webDriver->findElement(WebDriverBy::id('radio_action_mode_rename_attribute'))->click();
			self::$webDriver->findElement(WebDriverBy::xpath("//option[text()='Room_C']"));
		}
		catch (Exception $ex)
		{
			$this->fail("#6 Renaming a room attribute has failed" . $ex);
		}

		//#7 Change name of a room attribute into nothing
		self::$helper->changeRoomAttribute('Room_C', '');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#7 Changing a room attribute to no name has succeded" . $ex);
		}

		//#8 Change name of a room attribute into existing one
		self::$helper->changeRoomAttribute('Room_C', 'Room_B');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#8 Changing a room attribute to an existing name has succeded" . $ex);
		}

		//#9 Change name of a room attribute into the same
		self::$helper->changeRoomAttribute('Room_C', 'Room_C');
		try
		{
			self::$webDriver->findElement(WebDriverBy::id('radio_action_mode_rename_attribute'))->click();
			self::$webDriver->findElement(WebDriverBy::xpath("//option[text()='Room_C']"));
		}
		catch (Exception $ex)
		{
			$this->fail("#9 Renaming a room attribute to his old name has failed" . $ex);
		}

		//#10 Check if changes did not effect the amount of a room
		self::$webDriver->findElement(WebDriverBy::linkText("Räume"))->click();
		self::$webDriver->findElement(WebDriverBy::linkText("123"))->click();
		try
		{
			$roomC_field = self::$webDriver->findElement(WebDriverBy::xpath("//label[text()='Room_C']"))->getAttribute('for');
			$roomB_field = self::$webDriver->findElement(WebDriverBy::xpath("//label[text()='Room_B']"))->getAttribute('for');
			$this->assertEquals(2,
				self::$webDriver->findElement(WebDriverBy::id($roomC_field))->getAttribute('value'));
			$this->assertEquals(3,
				self::$webDriver->findElement(WebDriverBy::id($roomB_field))->getAttribute('value'));
		}
		catch (Exception $ex)
		{
			$this->fail("#10 Changing room attribute name effects amount of it on a room" . $ex);
		}

		//#11 Delete room attribute
		self::$helper->deleteRoomAttribute("Room_C");
		$el = false;
		try
		{
			self::$webDriver->findElement(WebDriverBy::id('radio_action_mode_rename_attribute'))->click();
			$el = self::$webDriver->findElement(WebDriverBy::xpath("//option[text()='Room_C']"));
		}
		catch (Exception $unused)
		{

		}
		if ($el)
		{
			$this->fail("#11 Deleting a room attribute has failed");
		}
		self::$helper->deleteRoomAttribute("Room_B");

		//#12 Change none room attribute to a new name
		self::$helper->changeRoomAttribute('', 'Room_B');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#12 Changing a none existing room attribute to an none existing name has succeded" . $ex);
		}

		//#13 Delete none room attribute
		self::$helper->deleteRoomAttribute('');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#13 Deleting a none existing room attribute has succeded" . $ex);
		}

		//#14 Check if deletion did effect the amount of a room
		self::$webDriver->findElement(WebDriverBy::linkText("Räume"))->click();
		self::$webDriver->findElement(WebDriverBy::linkText("123"))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::xpath("//label[text()='Room_C']"))->getAttribute('for');
			self::$webDriver->findElement(WebDriverBy::xpath("//label[text()='Room_B']"))->getAttribute('for');
			$this->fail("#14 Deleting room attributes does not effect amount of it on a room" . $ex);
		}
		catch (Exception $ex)
		{

		}

		self::$helper->deleteAllRooms();
	}

	/**
	 * @test
	 */
	public function testBookingattributeManagement()
	{
		//#1 Create a new booking attribute
		self::$helper->createBookingAttribute("Book_A");
		try
		{
			self::$webDriver->findElement(WebDriverBy::id('radio_action_mode_rename_attribute'))->click();
			self::$webDriver->findElement(WebDriverBy::xpath("//option[text()='Book_A']"));
		}
		catch (Exception $ex)
		{
			$this->fail("#1 Creation of a new booking attribute has failed" . $ex);
		}

		//#2 Create an empty booking attribute
		self::$helper->createBookingAttribute("");
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#3 Creation of an empty booking attribute has succeded" . $ex);
		}

		//#3 Create an existing booking attribute
		self::$helper->createBookingAttribute("Book_A");
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#3 Creation of an existing booking attribute has succeded" . $ex);
		}

		//#4 Create a new booking attribute with html tags
		self::$helper->createBookingAttribute("<b><u>Book_B</u></b>");
		try
		{
			self::$webDriver->findElement(WebDriverBy::id('radio_action_mode_rename_attribute'))->click();
			self::$webDriver->findElement(WebDriverBy::xpath("//option[text()='Book_B']"));
		}
		catch (Exception $ex)
		{
			$this->fail("#5 Creation of a new booking attribute with HTML has failed" . $ex);
		}
		self::$helper->changeBookingAttribute('Book_B', 'Book_B');

		//#5 Check if creation bookings GUI has the new attributes
		self::$helper->createRoom('123', 1, 20, "TEST", " - Keine Zuordnung - ", array());
		self::$helper->searchForRoomByAll("123", "", "", "", date('Y') + 1, '23', '50', '23', '55',
			array());
		self::$webDriver->findElement(WebDriverBy::linkText("Buchen"))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::xpath("//label[text()='Book_A']"));
			self::$webDriver->findElement(WebDriverBy::xpath("//label[text()='Book_B']"));
		}
		catch (Exception $ex)
		{
			$this->fail("#5 Creation booking GUI does not have created attributes" . $ex);
		}
		self::$helper->doABooking('TEST', "", "", "", "", "", "", "", "", "", "", false, "", false,
			array(), array('Book_A' => 'TEST A', 'Book_B' => 'TEST B'));


		//#6 Change name of a booking attribute into a new
		self::$helper->changeBookingAttribute('Book_A', 'Book_C');
		try
		{
			self::$webDriver->findElement(WebDriverBy::id('radio_action_mode_rename_attribute'))->click();
			self::$webDriver->findElement(WebDriverBy::xpath("//option[text()='Book_C']"));
		}
		catch (Exception $ex)
		{
			$this->fail("#6 Renaming a booking attribute has failed" . $ex);
		}

		//#7 Change name of a booking attribute into nothing
		self::$helper->changeBookingAttribute('Book_C', '');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#7 Changing a booking attribute to no name has succeded" . $ex);
		}

		//#8 Change name of a booking attribute into existing one
		self::$helper->changeBookingAttribute('Book_C', 'Book_B');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#8 Changing a booking attribute to an existing name has succeded " . $ex);
		}

		//#9 Change name of a booking attribute into the same
		self::$helper->changeBookingAttribute('Book_C', 'Book_C');
		try
		{
			self::$webDriver->findElement(WebDriverBy::id('radio_action_mode_rename_attribute'))->click();
			self::$webDriver->findElement(WebDriverBy::xpath("//option[text()='Book_C']"));
		}
		catch (Exception $ex)
		{
			$this->fail("#9 Renaming a booking attribute to his old name has failed " . $ex);
		}

		//#10 Check if changes did not effect the bookings
		self::$webDriver->findElement(WebDriverBy::linkText('Termine'))->click();
		self::$webDriver->findElement(WebDriverBy::id('ilChkboxListAnchorText_tbl_roomobj'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::xpath("//td[text()='Book_C']"));
			self::$webDriver->findElement(WebDriverBy::xpath("//td[text()='Book_B']"));
			$attr_inputs = self::$webDriver->findElements(WebDriverBy::name("tblfsroomobj[]"));
			foreach ($attr_inputs as $input)
			{
				if (empty($input->getAttribute('checked')))
				{
					$input->click();
				}
			}
			self::$webDriver->findElement(WebDriverBy::name("cmd[showBookings]"))->click();
			self::$webDriver->findElement(WebDriverBy::xpath("//td[contains(text(),'TEST A')]"));
			self::$webDriver->findElement(WebDriverBy::xpath("//td[contains(text(),'TEST B')]"));
		}
		catch (Exception $ex)
		{
			$this->fail("#10 Changes of a booking attribute does effect bookings");
		}

		//#11 Delete booking attribute
		self::$helper->deleteBookingAttribute("Book_C");
		$el = false;
		try
		{
			self::$webDriver->findElement(WebDriverBy::id('radio_action_mode_rename_attribute'))->click();
			$el = self::$webDriver->findElement(WebDriverBy::xpath("//option[text()='Book_C']"));
		}
		catch (Exception $unused)
		{

		}
		if ($el)
		{
			$this->fail("#11 Deleting a booking attribute has failed");
		}
		self::$helper->deleteBookingAttribute("Book_B");

		//#12 Change none booking attribute to a new name
		self::$helper->changeBookingAttribute('', 'Book_B');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#12 Changing a none existing booking attribute to an none existing name has succeded" . $ex);
		}

		//#13 Delete none booking attribute
		self::$helper->deleteBookingAttribute('');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#13 Deleting a none existing booking attribute has succeded" . $ex);
		}

		//#14 Check if deletion did effect the bookings
		self::$webDriver->findElement(WebDriverBy::linkText('Termine'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::id('ilChkboxListAnchorText_tbl_roomobj'))->click();
			$this->fail("#14 Deletion of a booking attribute does not effect bookings");
		}
		catch (Exception $unused)
		{

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