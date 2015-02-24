<?php

include_once './acceptance/php-webdriver/__init__.php';
include_once './acceptance/tests/ilRoomSharingAcceptanceSeleniumHelper.php';

/**
 * Description of ilRoomSharingAcceptanceBookingFilterTest
 * @group selenium-privileges
 * @property WebDriver $webDriver
 * @author Albert Koch
 */
class ilRoomSharingAcceptancePrivilegeTest extends PHPUnit_Framework_TestCase
{
	private static $webDriver;
	private static $helper;
	private static $url = 'http://localhost/roomsharingsystem'; // URL to local RoomSharing
	private static $rssObjectName; // name of RoomSharing pool
	private static $login_root = 'root';
	private static $pass_root = 'homer';
	private static $new_user_1_gender = 'f';
	private static $new_user_1_first_name = 'klara';
	private static $new_user_1_last_name = 'fall'; //In German that's kind of funny
	private static $new_user_1_login = 'kfall';
	private static $new_user_1_pw = 'karla123';
	private static $new_user_1_initial_pw = 'karla321';
	private static $new_user_1_email = 'klara@fall.de';
	private static $new_user_2_gender = 'm';
	private static $new_user_2_first_name = 'karl';
	private static $new_user_2_last_name = 'auer'; //In German that's kind of funny
	private static $new_user_2_login = 'kauer';
	private static $new_user_2_pw = 'karl123';
	private static $new_user_2_initial_pw = 'karl321';
	private static $new_user_2_email = 'karl@auer.de';
	private static $classname_1 = 'Class1';
	private static $classname_2 = 'Class2';
	private static $standard_roomname = 'Standard';
	private static $standard_subject = 'Standard';

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
		self::$helper->login(self::$login_root, self::$pass_root);  // login
		self::$helper->toRSS();
		self::createTestData();
		self::$helper->logout();
	}

	public static function createTestData()
	{

		self::$webDriver->findElement(WebDriverBy::partialLinkText('Rechte'))->click();
		self::$webDriver->findElement(WebDriverBy::id('select_4_nocreation'))->click();
		self::$webDriver->findElement(WebDriverBy::name('cmd[savePermissions]'))->click();
		self::$helper->createNewUser(self::$new_user_1_login, self::$new_user_1_initial_pw,
			self::$new_user_1_gender, self::$new_user_1_first_name, self::$new_user_1_last_name,
			self::$new_user_1_email);
		self::$helper->toRSS();
		self::$helper->createNewUser(self::$new_user_2_login, self::$new_user_2_initial_pw,
			self::$new_user_2_gender, self::$new_user_2_first_name, self::$new_user_2_last_name,
			self::$new_user_2_email);
		self::$helper->logout();
		//new user will be asked to change his password at first login
		self::$helper->loginNewUserForFirstTime(self::$new_user_1_login, self::$new_user_1_initial_pw,
			self::$new_user_1_pw);
		self::$helper->logout();
		self::$helper->loginNewUserForFirstTime(self::$new_user_2_login, self::$new_user_2_initial_pw,
			self::$new_user_2_pw);
		self::$helper->logout();
		self::$helper->login(self::$login_root, self::$pass_root);
		self::$helper->toRSS();
		self::$helper->createPrivilegClass(self::$classname_1, '', 'User', '5');
		self::$helper->createPrivilegClass(self::$classname_2, '', 'User', '6');
		self::$webDriver->findElement(WebDriverBy::partialLinkText(self::$classname_1))->click();
		self::$webDriver->findElement(WebDriverBy::partialLinkText('Benutzerzuweisung'))->click();
		self::$webDriver->findElement(WebDriverBy::id('user_login'))->sendKeys(self::$new_user_1_login);
		self::$webDriver->findElement(WebDriverBy::name('cmd[addUserFromAutoComplete]'))->click();
		self::$webDriver->findElement(WebDriverBy::partialLinkText('Privilegien'))->click();
		self::$webDriver->findElement(WebDriverBy::partialLinkText(self::$classname_2))->click();
		self::$webDriver->findElement(WebDriverBy::partialLinkText('Benutzerzuweisung'))->click();
		self::$webDriver->findElement(WebDriverBy::id('user_login'))->sendKeys(self::$new_user_2_login);
		self::$webDriver->findElement(WebDriverBy::name('cmd[addUserFromAutoComplete]'))->click();
	}

	public function setUp()
	{
		self::$helper->login(self::$login_root, self::$pass_root);  // login
		self::$helper->toRSS();
	}

	/*
	 * Tests the application of the Privileges considering the Appointments
	 * @test
	 */
	public function testAppointmentPrivileges()
	{
		#1 check without any privilege
		self::$helper->logout();
		self::$helper->login(self::$new_user_1_login, self::$new_user_1_pw);
		self::$helper->toRSS();
		try
		{
			$webDriver->findElement(webDriverBy::linkText('Termine'));
			$this->fail("#1 Appointments are displayed even if there's no privilege");
		}
		catch (Exception $ex)
		{
			//Nothing to do here - the Element musn't be found!
		}

		#2 check with the privilege to see appointments and administrate booking attributes
		self::$helper->logout();
		self::$helper->login(self::$login_root, self::$pass_root);
		self::$helper->toRSS();
		self::$webDriver->findElement(webDriverBy::linkText('Privilegien'))->click();
		$class_1_id = self::$helper->getPrivilegeClassIDByNamePartial(self::$classname_1);
		self::$helper->grantPrivilege('accessAppointments', $class_1_id);
		self::$helper->grantPrivilege('adminBookingAttributes', $class_1_id);
		self::$helper->logout();
		self::$helper->login(self::$new_user_1_login, self::$new_user_1_pw);
		self::$helper->toRSS();
		$this->assertEquals(true,
			self::$webDriver->findElement(webDriverBy::linkText('Termine'))->isDisplayed(),
			'#2 - 1 Appointments are not displayed even the privilege is granted');
		$this->assertEquals(true,
			self::$webDriver->findElement(webDriverBy::linkText('Termine'))->isDisplayed(),
			'#2 - 2 Attributes are not displayed even the privilege is granted');

		# Check if non_public bookings are not displayed
		self::$helper->logout();
		self::$helper->login(self::$login_root, self::$pass_root);
		self::$helper->toRSS();
		//Create a non-public booking
		self::$helper->createRoom(self::$standard_roomname, '1', '10');
		self::$helper->searchForRoomByName(self::$standard_roomname);
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->doABooking(self::$standard_subject, "1", "1", date("Y") + 1, "12", "00", "1", "1",
			date("Y") + 1, "13", "00", '');
		self::$helper->logout();
		self::$helper->login(self::$new_user_1_login, self::$new_user_1_pw);
		self::$helper->toRSS();
		$this->assertEquals(0, self::$helper->getNoOfResults(),
			"#3 A non-public booking is visible to an user without the privilege");

		#4 Check the privilege to see non-public bookings
		self::$helper->logout();
		self::$helper->login(self::$login_root, self::$pass_root);
		self::$helper->toRSS();
		self::$webDriver->findElement(webDriverBy::linkText('Privilegien'))->click();
		self::$helper->grantPrivilege('seeNonPublicBookingInformation', $class_1_id);
		self::$helper->logout();
		self::$helper->login(self::$new_user_1_login, self::$new_user_1_pw);
		self::$helper->toRSS();
		$this->assertEquals(1, self::$helper->getNoOfResults(),
			"#4 A non-public booking is not visible to an user with the privilege");

		#5 Check if the search is accessible
		self::$helper->logout();
		self::$helper->login(self::$login_root, self::$pass_root);
		self::$helper->toRSS();
		self::$webDriver->findElement(webDriverBy::linkText('Privilegien'))->click();
		self::$helper->grantPrivilege('accessSearch', $class_1_id);
		self::$helper->logout();
		self::$helper->login(self::$new_user_1_login, self::$new_user_1_pw);
		self::$helper->toRSS();
		$this->assertEquals(true,
			self::$webDriver->findElement(webDriverBy::linkText('Suche'))->isDisplayed(),
			'#5 Search is not displayed even the privilege is granted');

		#6 Check if the "add booking"-link is not showed, if the privilege isn't given
		try
		{
			$webDriver->findElement(webDriverBy::partialLinkText('Buchung hinzufügen'))->click();
			$this->fail('#6 Add Booking is acceptible without the privilege');
		}
		catch (Exception $ex)
		{
			//Nothing to do - the exception should come
		}

		#7 Check if the "add booking"-link is showed if the privilege is given
		self::$helper->logout();
		self::$helper->login(self::$login_root, self::$pass_root);
		self::$helper->toRSS();
		self::$webDriver->findElement(webDriverBy::linkText('Privilegien'))->click();
		self::$helper->grantPrivilege('addOwnBookings', $class_1_id);
		self::$helper->logout();
		self::$helper->login(self::$new_user_1_login, self::$new_user_1_pw);
		self::$helper->toRSS();
		$this->assertEquals(true,
			self::$webDriver->findElement(webDriverBy::partialLinkText('Buchung hinzufügen'))->isDisplayed(),
			'#7 Add Booking is not displayed even the privilege is granted');

		#8 Check if a user with a higher Priority can oust another booking
		self::$helper->searchForRoomByName(self::$standard_roomname);
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->doABooking(self::$standard_subject, "1", "1", date("Y") + 1, "15", "00", "1", "1",
			date("Y") + 1, "16", "00", '');
		self::$helper->searchForRoomByName(self::$standard_roomname);
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->doABooking(self::$standard_subject, "1", "1", date("Y") + 1, "17", "00", "1", "1",
			date("Y") + 1, "18", "00", '');
		self::$helper->logout();
		self::$helper->login(self::$login_root, self::$pass_root);
		self::$helper->toRSS();
		self::$webDriver->findElement(webDriverBy::linkText('Privilegien'))->click();
		$class_2_id = self::$helper->getPrivilegeClassIDByNamePartial(self::$classname_2);

		self::$helper->grantPrivilege('cancelBookingLowerPriority', $class_2_id);
		self::$helper->grantPrivilege('accessSearch', $class_2_id);
		self::$helper->grantPrivilege('seeNonPublicBookingInformation', $class_2_id);
		self::$helper->grantPrivilege('accessAppointments', $class_2_id);
		self::$helper->grantPrivilege('addOwnBookings', $class_2_id);
		self::$helper->logout();
		self::$helper->login(self::$new_user_2_login, self::$new_user_2_pw);
		self::$helper->toRSS();
		self::$helper->searchForRoomByName(self::$standard_roomname);
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->doABooking(self::$standard_subject, "1", "1", date("Y") + 1, "15", "00", "1", "1",
			date("Y") + 1, "16", "00", '');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
			$this->fail("#8 Booking with ousting a lower-priority booking does not succeed");
		}
		catch (Exception $ex)
		{

		}

		#9 Check if user with higher priority can cancel bookings with lower priority
		self::$helper->toRSS();
		self::$webDriver->findElement(webDriverBy::id('chb_select_all_'))->click();
		self::$webDriver->findElement(webDriverBy::cssSelector('div.ilTableCommandRow > div > input[name="cmd[confirmMultipleCancels]"]'))->click();
		self::$webDriver->findElement(webDriverBy::name('cmd[cancelMultipleBookings]'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
			$this->fail('#9 Cancelling a booking with a lower priority is impossible');
		}
		catch (Exception $ex)
		{

		}
		#10 Check if serial bookings can be made and if unlimited bookings are possible
		self::$helper->logout();
		self::$helper->login(self::$login_root, self::$pass_root);
		self::$helper->toRSS();
		self::$webDriver->findElement(webDriverBy::linkText('Privilegien'))->click();
		self::$helper->grantPrivilege('addSequenceBookings', $class_1_id);
		self::$helper->grantPrivilege('addUnlimitedBookings', $class_1_id);
		self::$helper->logout();
		self::$helper->login(self::$new_user_1_login, self::$new_user_1_pw);
		self::$helper->toRSS();
		self::$helper->searchForRoomByName(self::$standard_roomname);
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		try
		{
			$this->assertEquals(true,
				self::$webDriver->findElement(webDriverBy::id('il_recurrence_1'))->isDisplayed(),
				"#10 Sequence Bookings are impossible");
		}
		catch (Exception $ex)
		{
			$this->fail("#10 Sequence Bookings are impossible");
		}
		self::$helper->toRSS();
		self::$helper->searchForRoomByName(self::$standard_roomname);
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->doABooking(self::$standard_subject, "1", "1", date("Y") + 1, "20", "00", "1", "1",
			date("Y") + 2, "11", "00", '');
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
			$this->fail('#10 Unlimited Bookings are impossible');
		}
		catch (Exception $ex)
		{

		}
		#11 Import Bookings
		self::$helper->logout();
		self::$helper->login(self::$login_root, self::$pass_root);
		self::$helper->toRSS();
		self::$webDriver->findElement(webDriverBy::linkText('Privilegien'))->click();
		self::$helper->grantPrivilege('accessImport', $class_1_id);
		self::$helper->logout();
		self::$helper->login(self::$new_user_1_login, self::$new_user_1_pw);
		self::$helper->toRSS();
		try
		{
			$this->assertEquals(true,
				self::$webDriver->findElement(webDriverBy::partialLinkText('Import'))->isDisplayed(),
				"#11 Import is not accessible");
		}
		catch (Exception $ex)
		{
			$this->fail("#11 Import is not accessible");
		}
		//Not implemented: Preferences for notifications. The ID for this privilege
		// (to be used in grantPrivilege() is 'notificationSettings'
	}

	/*
	 * Tests the application of the privileges considering the rooms
	 * @test
	 */
	public function roomsTest()
	{
		/*
		 * Not implemented yet. The ID's for the privileges (to be used in grantPrivilege() are:
		 * accessRooms
		 * seeBookingsOfRooms
		 * addRooms
		 * editRooms
		 * deleteRooms
		 * adminRoomAttributes
		 */
	}

	/*
	 * Tests the application of the privileges considering the floorplans
	 * @test
	 */
	public function floorplanTest()
	{
		/*
		 * Not implemented yet. The IDs for the privileges (to be used in grantPrivilege()) are:
		 * accessFloorplans
		 * addFloorplans
		 * editFloorplans
		 * deleteFloorplans
		 */
	}

	/*
	 * Tests the application of the other privileges
	 * @test
	 */
	public function otherPrivilegesTest()
	{
		/*
		 * Not implemented yet. The IDs for the privileges (to be used in grantPrivilege()) are:
		 * accessSettings
		 * accessPrivileges
		 * addClass
		 * editClass
		 * deleteClass
		 * editPrivileges
		 * lockPrivileges
		 */
	}

	/**
	 * Closes web browser.
	 */
	public static function tearDownAfterClass()
	{
		self::$helper->login(self::$login_root, self::$pass_root);
		self::$helper->toRSS();
		self::$helper->deleteAllRooms();
		self::$webDriver->findElement(WebDriverBy::linkText('Privilegien'))->click();
		self::$webDriver->findElement(WebDriverBy::partialLinkText(self::$classname_1))->click();
		self::$webDriver->findElement(WebDriverBy::partialLinkText('Klasse löschen'))->click();
		self::$webDriver->findElement(WebDriverBy::name('cmd[deleteClass]'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Privilegien'))->click();
		self::$webDriver->findElement(WebDriverBy::partialLinkText(self::$classname_2))->click();
		self::$webDriver->findElement(WebDriverBy::partialLinkText('Klasse löschen'))->click();
		self::$webDriver->findElement(WebDriverBy::name('cmd[deleteClass]'))->click();
		self::$helper->deleteUser();
		self::$helper->toRSS();
		self::$helper->deleteUser();
		self::$helper->logout();
		self::$webDriver->quit();
	}

	public function tearDown()
	{
		//delete all rooms, that will cancel all bookings, too
		self::$helper->logout();
		self::$helper->login(self::$login_root, self::$pass_root);
		self::$helper->toRSS();
		self::$helper->deleteAllRooms();
		self::$helper->logout();
	}

}
