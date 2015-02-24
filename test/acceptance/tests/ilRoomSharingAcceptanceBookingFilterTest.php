<?php

include_once './acceptance/php-webdriver/__init__.php';
include_once './acceptance/tests/ilRoomSharingAcceptanceSeleniumHelper.php';

/**
 * Description of ilRoomSharingAcceptanceBookingFilterTest
 * @group selenium-privileges
 * @property WebDriver $webDriver
 * @author Albert Koch
 */
class ilRoomSharingAcceptanceBookingFilterTest extends PHPUnit_Framework_TestCase
{
	private static $webDriver;
	private static $url = 'http://localhost/roomsharingsystem'; // URL to local RoomSharing
	private static $rssObjectName; // name of RoomSharing pool
	private static $login_user = 'root';
	private static $login_pass = 'homer';
	private static $helper;
	private static $new_user_gender = 'm';
	private static $new_user_first_name = 'karl';
	private static $new_user_last_name = 'auer'; //In German that's kind of funny
	private static $new_user_login = 'kauer';
	private static $new_user_pw = 'karl123';
	private static $new_user_initial_pw = 'karl321';
	private static $new_user_email = 'karl@auer.de';
	private static $lazy_user_login = 'afaenger';
	private static $lazy_user_first_name = 'Ann';
	private static $lazy_user_last_name = 'Faenger';
	private static $lazy_user_pw = 'doesnothing123';
	private static $lazy_user_email = 'ann@faenger.de';
	private static $lazy_user_gender = 'f';
	private static $classname = 'Users';
	private static $standard_roomname = 'Standard_Room';
	private static $differing_roomname = 'Differing_Room';
	private static $unbooked_roomname = 'Unbooked';
	private static $standard_subject = 'Standard Subject';
	private static $differing_subject = 'Differing Subject';
	private static $never_used_subject = 'Not Used';
	private static $standard_comment = 'Standard Comment';
	private static $differing_comment = 'Differing Comment';
	private static $never_used_comment = 'Not Used';
	private static $attribute_name = 'Semester';
	private static $standard_attribute = '1';
	private static $differing_attribute = '2';
	private static $attribute_not_used = '3';

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
		self::$helper->login(self::$login_user, self::$login_pass);  // login
		self::$helper->toRSS();
		self::createTestData();
		self::$helper->logout();
	}

	public function setUp()
	{
		self::$helper->login(self::$login_user, self::$login_pass);  // login
		self::$helper->toRSS();
	}

	public static function createTestData()
	{

		self::$webDriver->findElement(WebDriverBy::partialLinkText('Rechte'))->click();
		self::$webDriver->findElement(WebDriverBy::id('select_4_nocreation'))->click();
		self::$webDriver->findElement(WebDriverBy::name('cmd[savePermissions]'))->click();
		self::$helper->createNewUser(self::$new_user_login, self::$new_user_initial_pw,
			self::$new_user_gender, self::$new_user_first_name, self::$new_user_last_name,
			self::$new_user_email);
		self::$helper->createNewUser(self::$lazy_user_login, self::$lazy_user_pw, self::$lazy_user_gender,
			self::$lazy_user_first_name, self::$lazy_user_last_name, self::$lazy_user_email);
		self::$helper->toRSS();
		self::$helper->addAttributeForBooking(self::$attribute_name);
		self::setUpPrivilegeClass(); //Grant the privileges necessary to book
		self::$helper->createRoom(self::$standard_roomname, '1', '10');
		self::$helper->createRoom(self::$differing_roomname, '1', '10');
		self::$helper->createRoom(self::$unbooked_roomname, '1', '10');
		self::$helper->searchForRoomByName(self::$standard_roomname);
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->doABooking(self::$standard_subject, "1", "1", date("Y") + 1, "12", "00", "1", "1",
			date("Y") + 1, "13", "00", "", self::$standard_comment, false, array(),
			array(self::$attribute_name => self::$standard_attribute));
		self::$helper->searchForRoomByName(self::$differing_roomname);
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->doABooking(self::$standard_subject, "1", "1", date("Y") + 1, "14", "00", "1", "1",
			date("Y") + 1, "15", "00", "", self::$standard_comment, false, array(),
			array(self::$attribute_name => self::$standard_attribute));
		self::$helper->searchForRoomByName(self::$standard_roomname);
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->doABooking(self::$differing_subject, "1", "1", date("Y") + 1, "16", "00", "1", "1",
			date("Y") + 1, "17", "00", "", self::$standard_comment, false, array(),
			array(self::$attribute_name => self::$standard_attribute));
		self::$helper->searchForRoomByName(self::$standard_roomname);
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->doABooking(self::$standard_subject, "1", "1", date("Y") + 1, "18", "00", "1", "1",
			date("Y") + 1, "19", "00", "", self::$differing_comment, false, array(),
			array(self::$attribute_name => self::$standard_attribute));
		self::$helper->searchForRoomByName(self::$standard_roomname);
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->doABooking(self::$standard_subject, "1", "1", date("Y") + 1, "20", "00", "1", "1",
			date("Y") + 1, "21", "00", "", self::$standard_comment, false, array(),
			array(self::$attribute_name => self::$differing_attribute));
		self::$helper->logout();
		//new user will be asked to change his password at first login
		self::$helper->loginNewUserForFirstTime(self::$new_user_login, self::$new_user_initial_pw,
			self::$new_user_pw);
		self::$helper->toRSS();
		self::$helper->searchForRoomByName(self::$standard_roomname);
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->doABooking(self::$standard_subject, "1", "1", date("Y") + 1, "8", "00", "1", "1",
			date("Y") + 1, "9", "00", "", self::$standard_comment, false, array(),
			array(self::$attribute_name => self::$standard_attribute));
	}

	public static function setUpPrivilegeClass()
	{
		self::$webDriver->findElement(WebDriverBy::partialLinkText('Magazin'))->click();
		self::$helper->createPrivilegClass(self::$classname, '', 'User');
		self::$webDriver->findElement(WebDriverBy::partialLinkText(self::$classname))->click();
		self::$webDriver->findElement(WebDriverBy::partialLinkText('Benutzerzuweisung'))->click();
		self::$webDriver->findElement(WebDriverBy::id('user_login'))->sendKeys(self::$new_user_login);
		self::$webDriver->findElement(WebDriverBy::name('cmd[addUserFromAutoComplete]'))->click();
		self::$webDriver->findElement(WebDriverBy::partialLinkText('Privilegien'))->click();
		$class_id = self::$helper->getPrivilegeClassIDByNamePartial(self::$classname);
		self::$helper->grantPrivilege('accessAppointments', $class_id);
		self::$helper->grantPrivilege('accessSearch', $class_id);
		self::$helper->grantPrivilege('addOwnBookings', $class_id);
		self::$helper->grantPrivilege('seeNonPublicBookingInformation', $class_id);
		self::$helper->grantPrivilege('accessRooms', $class_id);
	}

	/*
	 * Tests the filter panel itself in booking filter
	 * @test
	 */
	public function testFilterPanel()
	{
		self::$webDriver->findElement(WebDriverBy::linkText('Termine'))->click();

		#2: Hide Booking Filter
		self::$webDriver->findElement(WebDriverBy::linkText('Filter ausblenden'))->click();
		$this->assertEquals(false,
			self::$webDriver->findElement(WebDriverBy::name('cmd[applyFilter]'))->isDisplayed(),
			'#1 Hiding the Filter does not hide it');
		#1: Show Booking Filtere
		self::$webDriver->findElement(WebDriverBy::partialLinkText('Filter anzeigen'))->click();
		$this->assertEquals(true,
			self::$webDriver->findElement(WebDriverBy::name('cmd[applyFilter]'))->isDisplayed(),
			'#2 Showing the Filter does not show it');

		#3 Apply and hide filter
		self::$webDriver->findElement(WebDriverBy::id('login'))->sendKeys(self::$login_user);
		self::$webDriver->findElement(WebDriverBy::partialLinkText('Filter ausblenden'))->click();
		self::$webDriver->findElement(WebDriverBy::partialLinkText('Filter anzeigen'))->click();
		$this->assertEquals(self::$login_user,
			self::$webDriver->findElement(WebDriverBy::name('login'))->getAttribute('value'),
			'#3 Hiding and showing the RoomFilter resets it');

		#4 Reset filter
		self::$webDriver->findElement(WebDriverBy::name('cmd[resetFilter]'))->click();
		$this->assertEquals(true,
			empty(self::$webDriver->findElement(WebDriverBy::name('login'))->getAttribute('value')),
			'#4 Filter reseting does not work');
	}

	/*
	 * Tests the filter by username of booking person
	 * @test
	 */
	public function testUserName()
	{
		//Navigate
		self::$webDriver->findElement(WebDriverBy::linkText('Termine'))->click();

		#1: See the Bookings created by root user
		self::$helper->applyBookingFilter(self::$login_user);
		$this->assertEquals(5, self::$helper->getNoOfResults(),
			'#1 for Username in booking filter does not work');

		#2: See the bookings created by the new user
		self::$helper->applyBookingFilter(self::$new_user_login);
		$this->assertEquals(1, self::$helper->getNoOfResults(),
			'#2 for Username in booking filter does not work');

		#3: See the bookings (0) of a user that didn't create any
		self::$helper->applyBookingFilter(self::$lazy_user_login);
		$this->assertEquals(0, self::$helper->getNoOfResults(),
			'#3 for Username in booking filter does not work - not 0 results for user
				that did not book');

		#4 Reset the filter
		self::$helper->applyBookingFilter('', '', '', '');
		$this->assertEquals(6, self::$helper->getNoOfResults(),
			'#4 for username - reset filter - does not work');
	}

	/*
	 * Tests the filter by the booked room
	 * @test
	 */
	public function testRoom()
	{
		self::$webDriver->findElement(WebDriverBy::linkText('Termine'))->click();
		#1: See only the Booking of a differing room
		self::$helper->applyBookingFilter('', self::$differing_roomname);
		$this->assertEquals(1, self::$helper->getNoOfResults(),
			'#1 for room in booking filter does not work -> Wrong Number of Results');
		#2: See the Booking of a room which wasn't booked at all
		self::$helper->applyBookingFilter('', self::$unbooked_roomname);
		$this->assertEquals(0, self::$helper->getNoOfResults(),
			'#2 for room in booking filter does not work -> Number of Results not 0');
		#2: Reset the filter
		self::$helper->applyBookingFilter('', '', '', '');
		$this->assertEquals(6, self::$helper->getNoOfResults(),
			'#3 for room - reset filter - does not work');
	}

	/*
	 * Tests the filter by the subject of the booking
	 * @test
	 */
	public function testSubject()
	{
		self::$webDriver->findElement(WebDriverBy::linkText('Termine'))->click();
		#1: See only the booking with a differing subject
		self::$helper->applyBookingFilter('', '', self::$differing_subject);
		$this->assertEquals(1, self::$helper->getNoOfResults(), '#1 for subject does not work');
		#2: See the - not existing - bookings with a never used subject
		self::$helper->applyBookingFilter('', '', self::$never_used_subject);
		$this->assertEquals(0, self::$helper->getNoOfResults(),
			'#2 for subject does not work - Number of results not 0');
		#3: Reset the filter
		self::$helper->applyBookingFilter('', '', '', '');
		$this->assertEquals(6, self::$helper->getNoOfResults(),
			'#3 for subject - reset filter - does not work');
	}

	/*
	 * Tests the filter by the comment given to the booking
	 * @test
	 */
	public function testComment()
	{
		self::$webDriver->findElement(WebDriverBy::linkText('Termine'))->click();
		#1: See only the booking with a differing comment
		self::$helper->applyBookingFilter('', '', '', self::$differing_comment);
		$this->assertEquals(1, self::$helper->getNoOfResults(), '#1 for comment does not work');
		#2: See the - not existing - booking with a never used comment
		self::$helper->applyBookingFilter('', '', '', self::$never_used_comment);
		$this->assertEquals(0, self::$helper->getNoOfResults(),
			'#2 for comment does not work - Number of results not 0');
		#3: Reset the filter
		self::$helper->applyBookingFilter('', '', '', '');
		$this->assertEquals(6, self::$helper->getNoOfResults(),
			'#3 for comment - reset filter - does not work');
	}

	/*
	 * Tests the filter by one attribute
	 * @test
	 */
	public function testAttribute()
	{
		self::$webDriver->findElement(WebDriverBy::linkText('Termine'))->click();
		#1: See only the booking with a differing Attribute
		self::$helper->applyBookingFilter('', '', '', '',
			array(self::$attribute_name => self::$differing_attribute));
		$this->assertEquals(1, self::$helper->getNoOfResults(), '#1 for Attribute does not work');
		#2: See the - not existing - booking with a never used Attribute
		self::$helper->applyBookingFilter('', '', '', '',
			array(self::$attribute_name => self::$attribute_not_used));
		$this->assertEquals(0, self::$helper->getNoOfResults(), '#1 for Attribute does not work');
		#3: Reset the filter
		self::$helper->applyBookingFilter('', '', '', '', array(self::$attribute_name => ''));
		$this->assertEquals(6, self::$helper->getNoOfResults(),
			'#3 for attribute - reset filter - does not work');
	}

	/**
	 * Closes web browser.
	 */
	public static function tearDownAfterClass()
	{

		self::$helper->login(self::$login_user, self::$login_pass);
		self::$helper->toRSS();
		self::$helper->deleteAllRooms();
		self::$webDriver->findElement(WebDriverBy::linkText('Privilegien'))->click();
		self::$webDriver->findElement(WebDriverBy::partialLinkText(self::$classname))->click();
		self::$webDriver->findElement(WebDriverBy::partialLinkText('Klasse löschen'))->click();
		self::$webDriver->findElement(WebDriverBy::name('cmd[deleteClass]'))->click();
		self::$helper->deleteOneAttributeForBooking();
		self::$helper->deleteUser();
		self::$helper->toRSS();
		self::$helper->deleteUser();
		self::$helper->logout();
		self::$webDriver->quit();
	}

	public function tearDown()
	{
		self::$helper->logout();
	}

}
