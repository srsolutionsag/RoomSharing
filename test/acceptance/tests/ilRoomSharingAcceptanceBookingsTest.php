<?php

include_once './acceptance/php-webdriver/__init__.php';
include_once './acceptance/tests/ilRoomSharingAcceptanceSeleniumHelper.php';

/**
 * This class represents the gui-testing for the RoomSharing System
 *
 * @group selenium-bookings
 * @property WebDriver $webDriver
 *
 * created by: Thomas Wolscht
 */
class ilRoomSharingAcceptanceBookingsTest extends PHPUnit_Framework_TestCase
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
		self::$helper->searchForRoomByName("117");
	}

	/**
	 * Test invalid booking: booking in the past
	 * @test
	 */
	public function testInvalidBookingInPast()
	{
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->fillBookingForm("Test Buchung", "12", "2", "2013", "10", "00", "12", "2", "2013", "11", "00");
		self::$webDriver->findElement(WebDriverBy::name('cmd[book]'))->click();
		$this->assertContains("Vergangenheit", self::$helper->getErrMessage());
		self::$webDriver->findElement(
				WebDriverBy::linkText('Zurück zu den Suchergebnissen'))->click();
	}

	/**
	 * Test invalid booking: "to" and "from" time is the same
	 * @test
	 */
	public function testInvalidBookingSameTime()
	{
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->fillBookingForm("Test Buchung", "12", "2", "2016", "10", "00", "12", "2", "2016", "10", "00");
		self::$webDriver->findElement(WebDriverBy::name('cmd[book]'))->click();
		$this->assertContains("ist später oder gleich", self::$helper->getErrMessage());
		self::$webDriver->findElement(
				WebDriverBy::linkText('Zurück zu den Suchergebnissen'))->click();
	}

	/**
	 * Test invalid booking: no subject
	 * @test
	 */
	public function testInvalidBookingNoSubject()
	{
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->fillBookingForm("", "12", "2", "2016", "10", "00", "12", "2", "2016", "11", "00");
		self::$webDriver->findElement(WebDriverBy::name('cmd[book]'))->click();
		$this->assertContains("Einige Angaben sind unvollständig oder ungültig", self::$helper->getErrMessage());
		self::$webDriver->findElement(
				WebDriverBy::linkText('Zurück zu den Suchergebnissen'))->click();
	}

	/**
	 * Test invalid booking: time is in wront order
	 * @test
	 */
	public function testInvalidBookingTimeFlipped()
	{

		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->fillBookingForm("Testbuchung", "12", "2", "2016", "11", "00", "12", "2", "2016", "10", "00");
		self::$webDriver->findElement(WebDriverBy::name('cmd[book]'))->click();
		$this->assertContains("ist später oder gleich", self::$helper->getErrMessage());
		self::$webDriver->findElement(
				WebDriverBy::linkText('Zurück zu den Suchergebnissen'))->click();
	}

	/**
	 * Test invalid booking: agreement not accepted
	 * @test
	 */
	public function testInvalidBookingNoAgreementAccepted()
	{
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->fillBookingForm("Testbuchung", "12", "2", "2016", "10", "00", "12", "2", "2016", "11", "00", false);
		self::$webDriver->findElement(WebDriverBy::name('cmd[book]'))->click();
		$this->assertContains("Einige Angaben sind unvollständig", self::$helper->getErrMessage());
		self::$webDriver->findElement(
				WebDriverBy::linkText('Zurück zu den Suchergebnissen'))->click();
	}

	/**
	 * Test invalid booking: booking time is longer than the max for this pool
	 * @test
	 */
	public function testInvalidBookingTooLong()
	{
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->fillBookingForm("Testbuchung", "12", "2", "2016", "10", "00", "12", "2", "2016", "14", "00");
		self::$webDriver->findElement(WebDriverBy::name('cmd[book]'))->click();
		$this->assertContains("als die Maximal zulässige Buchungsdauer", self::$helper->getErrMessage());
		self::$webDriver->findElement(
				WebDriverBy::linkText('Zurück zu den Suchergebnissen'))->click();
	}

	/**
	 * Test invalid booking: too many participants
	 * @test
	 */
	public function testInvalidBookingWithTooManyParticipants()
	{
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->fillBookingForm("Testbuchung", "12", "2", "2016", "10", "00", "12", "2", "2016", "11", "00", true, "",
				true, array("aaa", "bbb", "ccc")
		);
		self::$webDriver->findElement(WebDriverBy::name('cmd[book]'))->click();
		$this->assertContains("Maximale Anzahl der Sitzplätze überschritten", self::$helper->getErrMessage());
		self::$webDriver->findElement(
				WebDriverBy::linkText('Zurück zu den Suchergebnissen'))->click();
	}

	/**
	 * Test valid booking: only required fields
	 * @test
	 */
	public function testValidBookingOnlyRequiredFields()
	{
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->fillBookingForm("Testbuchung", "12", "2", "2016", "10", "00", "12", "2", "2016", "11", "00"
		);
		self::$webDriver->findElement(WebDriverBy::name('cmd[book]'))->click();
		$this->assertEquals("Buchung hinzugefügt", self::$helper->getSuccMessage());
		self::$helper->deleteFirstBooking();
	}

	/**
	 * Test valid booking: with comment
	 * @test
	 */
	public function testValidBookingWithComment()
	{
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->fillBookingForm("Testbuchung", "12", "2", "2016", "10", "00", "12", "2", "2016", "11", "00", true,
				"Testkommentar"
		);
		self::$webDriver->findElement(WebDriverBy::name('cmd[book]'))->click();
		$this->assertEquals("Buchung hinzugefügt", self::$helper->getSuccMessage());
		$page = self::$webDriver->findElement(WebDriverBy::tagName('body'))->getText();
		$this->assertContains("Testkommentar", $page);
		self::$helper->deleteFirstBooking();
	}

	/**
	 * Test valid booking: with attribute
	 * @test
	 */
	public function testValidBookingWithAttribute()
	{
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->fillBookingForm("Testbuchung", "12", "2", "2016", "10", "00", "12", "2", "2016", "11", "00",
				true, //Accept agreement
				"Testkommentar", //Comment
				true, //Public booking
				array(), //Participants
				array("Modul" => "Testmodul")
		);
		self::$webDriver->findElement(WebDriverBy::name('cmd[book]'))->click();
		$this->assertEquals("Buchung hinzugefügt", self::$helper->getSuccMessage());
		$page = self::$webDriver->findElement(WebDriverBy::tagName('body'))->getText();
		$this->assertContains("Testmodul", $page);
		self::$helper->deleteFirstBooking();
	}

	/**
	 * Test valid booking: participants
	 * @test
	 */
	public function testValidBookingWithParticipants()
	{
		self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
		self::$helper->fillBookingForm("Testbuchung", "12", "2", "2016", "10", "00", "12", "2", "2016", "11", "00", true, "",
				true, array("aaa", "bbb")
		);
		self::$webDriver->findElement(WebDriverBy::name('cmd[book]'))->click();
		$this->assertEquals("Buchung hinzugefügt", self::$helper->getSuccMessage());
		$page = self::$webDriver->findElement(WebDriverBy::tagName('body'))->getText();
		$this->assertContains("Alfred", $page);
		$this->assertContains("Bernd", $page);
		self::$helper->deleteFirstBooking();
	}

	/**
	 * Closes web browser.
	 */
	public static function tearDownAfterClass()
	{
		self::$webDriver->quit();
	}

	/**
	 * Log out after each test case.
	 */
	public function tearDown()
	{
		self::$webDriver->findElement(WebDriverBy::linkText('Abmelden'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Bei ILIAS anmelden'))->click();
	}

}
