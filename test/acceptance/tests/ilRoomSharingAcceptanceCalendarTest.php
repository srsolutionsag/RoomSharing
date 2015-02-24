<?php

include_once './acceptance/php-webdriver/__init__.php';
include_once './acceptance/tests/ilRoomSharingAcceptanceSeleniumHelper.php';

/**
 * This class represents the gui-testing for the RoomSharing System
 *
 * @group selenium-calendar
 * @property WebDriver $webDriver
 *
 * @author Martin Doser
 */
class ilRoomSharingAcceptanceCalendarTest extends PHPUnit_Framework_TestCase
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
        
        
        public function testCalendarEntryCreateTest()
        {
                // create Booking
                self::$helper->searchForRoomByAll("I117","",date('d')+1,date('m'),date('Y'),"10","00","12","00",array());
                self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
                self::$helper->doABooking("CalendarTestBooking", date('d')+1, date('m'), date('Y'), "10", "00", date('d')+1, date('m'), date('Y'), "12",
			"00", "true");
                
                // check calendar entry
                self::$webDriver->findElement(WebDriverBy::linkText(self::$helper->getCurrentMonth() . ' ' . date('Y')))->click();
                $this->assertContains("10:00 CalendarTestBooking",self::$webDriver->findElement(webDriverBy::cssSelector("div.ilTabContentOuter.ilTabsTableCell"))->getText());
                
                // go back to bookings
                self::$webDriver->findElement(WebDriverBy::linkText('Termine'))->click();
        }
        
        
        public function testCalendarEntryEditTest()
        {
                //edit date
                $row = self::$webDriver->findElement(WebDriverBy::xpath("//tr[contains(text(), " . "CalendarTestBooking" . ")]/td[8]"));
                $row->findElement(WebDriverBy::linkText('Bearbeiten'))->click();
                self::$webDriver->findElement(WebDriverBy::id('from[date]_d'))->sendKeys(date('d')+1);
		self::$webDriver->findElement(WebDriverBy::id('from[date]_m'))->sendKeys(date('m'));
		self::$webDriver->findElement(WebDriverBy::id('from[date]_y'))->sendKeys(date('Y'));
		self::$webDriver->findElement(WebDriverBy::id('from[time]_h'))->sendKeys("14");
		self::$webDriver->findElement(WebDriverBy::id('from[time]_m'))->sendKeys("00");

		self::$webDriver->findElement(WebDriverBy::id('to[date]_d'))->sendKeys(date('d')+1);
		self::$webDriver->findElement(WebDriverBy::id('to[date]_m'))->sendKeys(date('m'));
		self::$webDriver->findElement(WebDriverBy::id('to[date]_y'))->sendKeys(date('Y'));
		self::$webDriver->findElement(WebDriverBy::id('to[time]_h'))->sendKeys("15");
		self::$webDriver->findElement(WebDriverBy::id('to[time]_m'))->sendKeys("10");
                self::$webDriver->findElement(WebDriverBy::name('cmd[saveEditBooking]'))->click();
                $this->assertContains("Buchung erfolgreich bearbeitet", self::$helper->getSuccMessage());
                self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Buchungen'))->click();
                
                
                // check calendar for booking
                self::$webDriver->findElement(WebDriverBy::linkText(self::$helper->getCurrentMonth() . ' ' . date('Y')))->click();
                $this->assertContains("14:00 CalendarTestBooking",self::$webDriver->findElement(webDriverBy::cssSelector("div.ilTabContentOuter.ilTabsTableCell"))->getText());
                
                // go back to bookings
                self::$webDriver->findElement(WebDriverBy::linkText('Termine'))->click();
        }
        
        
        
        
        
        public function testCalendarDeleteTest() {
                // delete the booking
                self::$helper->deleteBooking("CalendarTestBooking");
                
                // check calendar, no entry should be present
                self::$webDriver->findElement(WebDriverBy::linkText(self::$helper->getCurrentMonth() . ' ' . date('Y')))->click();
                $isNotPresent = self::$webDriver->findElements(webDriverBy::xpath("//a[contains(text(),'CalendarTestBooking')]")) === array();
                
                if($isNotPresent == false)
                {
                    $this->fail('Calendar entry still present');
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
		self::$webDriver->findElement(WebDriverBy::linkText('Abmelden'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Bei ILIAS anmelden'))->click();
	}

}
?>

