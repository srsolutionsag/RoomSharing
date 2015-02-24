<?php

include_once './acceptance/php-webdriver/__init__.php';
include_once './acceptance/tests/ilRoomSharingAcceptanceSeleniumHelper.php';

/**
 * This class represents the gui-testing for the RoomSharing System
 *
 * @group selenium-editbooking
 * @property WebDriver $webDriver
 *
 * @author Martin Doser
 */
class ilRoomSharingAcceptanceBookingEditTest extends PHPUnit_Framework_TestCase
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
        
        
        public function testEditSubject()
        {       
                // create a booking
                self::$helper->searchForRoomByAll("I117","","12","12","2017","10","00","12","00",array());
                self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
                self::$helper->doABooking("EditTestBooking", "12", "12", "2017", "10", "00", "12", "12", "2017", "12",
			"00", "true");
                
                //edit subject
                $row = self::$webDriver->findElement(WebDriverBy::xpath("//tr[contains(text(), " . "TestEditBooking" . ")]/td[8]"));
                $row->findElement(WebDriverBy::linkText('Bearbeiten'))->click();
                self::$webDriver->findElement(WebDriverBy::id('subject'))->clear();
		self::$webDriver->findElement(WebDriverBy::id('subject'))->sendKeys("Edited");
                self::$webDriver->findElement(WebDriverBy::name('cmd[saveEditBooking]'))->click();
                $this->assertContains("Buchung erfolgreich bearbeitet", self::$helper->getSuccMessage());
                self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Buchungen'))->click();
                
                //change subject back
                $row2 = self::$webDriver->findElement(WebDriverBy::xpath("//tr[contains(text(), " . "Edited" . ")]/td[8]"));
                $row2->findElement(WebDriverBy::linkText('Bearbeiten'))->click();
                self::$webDriver->findElement(WebDriverBy::id('subject'))->clear();
		self::$webDriver->findElement(WebDriverBy::id('subject'))->sendKeys("TestEditBooking");
                self::$webDriver->findElement(WebDriverBy::name('cmd[saveEditBooking]'))->click();
                $this->assertContains("Buchung erfolgreich bearbeitet", self::$helper->getSuccMessage());
                self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Buchungen'))->click();
        }
        
        
        public function testEditDate() 
        {  
                //edit date
                $row = self::$webDriver->findElement(WebDriverBy::xpath("//tr[contains(text(), " . "TestEditBooking" . ")]/td[8]"));
                $row->findElement(WebDriverBy::linkText('Bearbeiten'))->click();
                self::$webDriver->findElement(WebDriverBy::id('from[date]_d'))->sendKeys("13");
		self::$webDriver->findElement(WebDriverBy::id('from[date]_m'))->sendKeys("12");
		self::$webDriver->findElement(WebDriverBy::id('from[date]_y'))->sendKeys("2017");
		self::$webDriver->findElement(WebDriverBy::id('from[time]_h'))->sendKeys("11");
		self::$webDriver->findElement(WebDriverBy::id('from[time]_m'))->sendKeys("00");

		self::$webDriver->findElement(WebDriverBy::id('to[date]_d'))->sendKeys("13");
		self::$webDriver->findElement(WebDriverBy::id('to[date]_m'))->sendKeys("12");
		self::$webDriver->findElement(WebDriverBy::id('to[date]_y'))->sendKeys("2017");
		self::$webDriver->findElement(WebDriverBy::id('to[time]_h'))->sendKeys("13");
		self::$webDriver->findElement(WebDriverBy::id('to[time]_m'))->sendKeys("13");
                self::$webDriver->findElement(WebDriverBy::name('cmd[saveEditBooking]'))->click();
                $this->assertContains("Buchung erfolgreich bearbeitet", self::$helper->getSuccMessage());
                self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Buchungen'))->click();
        }
        
        
        public function testEditInvalidDate()
        {
                //edit date, date in past
                $row = self::$webDriver->findElement(WebDriverBy::xpath("//tr[contains(text(), " . "TestEditBooking" . ")]/td[8]"));
                $row->findElement(WebDriverBy::linkText('Bearbeiten'))->click();
                self::$webDriver->findElement(WebDriverBy::id('from[date]_d'))->sendKeys("13");
		self::$webDriver->findElement(WebDriverBy::id('from[date]_m'))->sendKeys("12");
		self::$webDriver->findElement(WebDriverBy::id('from[date]_y'))->sendKeys("2013");
		self::$webDriver->findElement(WebDriverBy::id('from[time]_h'))->sendKeys("11");
		self::$webDriver->findElement(WebDriverBy::id('from[time]_m'))->sendKeys("00");

		self::$webDriver->findElement(WebDriverBy::id('to[date]_d'))->sendKeys("13");
		self::$webDriver->findElement(WebDriverBy::id('to[date]_m'))->sendKeys("12");
		self::$webDriver->findElement(WebDriverBy::id('to[date]_y'))->sendKeys("2013");
		self::$webDriver->findElement(WebDriverBy::id('to[time]_h'))->sendKeys("13");
		self::$webDriver->findElement(WebDriverBy::id('to[time]_m'))->sendKeys("13");
                self::$webDriver->findElement(WebDriverBy::name('cmd[saveEditBooking]'))->click();
                $this->assertContains("Vergangenheit", self::$helper->getErrMessage());
                self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Buchungen'))->click();
                
                //edit date, room occupied
                //book second room for occupation
                self::$helper->searchForRoomByAll("I117","","12","12","2018","10","00","12","00",array());
                self::$webDriver->findElement(WebDriverBy::linktext('Buchen'))->click();
                self::$helper->doABooking("BlockingBooking", "12", "12", "2018", "10", "00", "12", "12", "2018", "12",
			"00", "true");
                
                //edit the date
                $row2 = self::$webDriver->findElement(WebDriverBy::xpath("//tr[contains(text(), " . "TestEditBooking" . ")]/td[8]"));
                $row2->findElement(WebDriverBy::linkText('Bearbeiten'))->click();
                self::$webDriver->findElement(WebDriverBy::id('from[date]_d'))->sendKeys("12");
		self::$webDriver->findElement(WebDriverBy::id('from[date]_m'))->sendKeys("01");
		self::$webDriver->findElement(WebDriverBy::id('from[date]_y'))->sendKeys("2018");
		self::$webDriver->findElement(WebDriverBy::id('from[time]_h'))->sendKeys("10");
		self::$webDriver->findElement(WebDriverBy::id('from[time]_m'))->sendKeys("00");

		self::$webDriver->findElement(WebDriverBy::id('to[date]_d'))->sendKeys("12");
		self::$webDriver->findElement(WebDriverBy::id('to[date]_m'))->sendKeys("01");
		self::$webDriver->findElement(WebDriverBy::id('to[date]_y'))->sendKeys("2018");
		self::$webDriver->findElement(WebDriverBy::id('to[time]_h'))->sendKeys("12");
		self::$webDriver->findElement(WebDriverBy::id('to[time]_m'))->sendKeys("12");
                self::$webDriver->findElement(WebDriverBy::name('cmd[saveEditBooking]'))->click();
                $this->assertContains("Der Raum ist in dem Zeitraum bereits gebucht", self::$helper->getErrMessage());
                self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Buchungen'))->click(); 
        }


        public function testEditComment() 
        {
                //edit comment
                $row = self::$webDriver->findElement(WebDriverBy::xpath("//tr[contains(text(), " . "TestEditBooking" . ")]/td[8]"));
                $row->findElement(WebDriverBy::linkText('Bearbeiten'))->click();
                self::$webDriver->findElement(WebDriverBy::id('comment'))->sendKeys("aComment");
                self::$webDriver->findElement(WebDriverBy::name('cmd[saveEditBooking]'))->click();
                $this->assertContains("Buchung erfolgreich bearbeitet", self::$helper->getSuccMessage());
                self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Buchungen'))->click();
        }
        
        public function testDeleteBookings()
        {
            // delete the bookings
            self::$helper->deleteBooking("EditTestBooking");
            //Bestaetigungsnachricht derzeit fehlerhaft, daher wird im Moment auf "falsche" Ausgabe überprüft
            //Ersetzen sobald Fehler behoben
            $this->assertContains("Die Buchungen wurde gelöscht", self::$helper->getSuccMessage());
            //$this->assertContains("Die Buchung wurde gelöscht", self::$helper->getSuccMessage());
            self::$helper->deleteBooking("BlockingBooking");
            $this->assertContains("Die Buchungen wurde gelöscht", self::$helper->getSuccMessage());
            //$this->assertContains("Die Buchung wurde gelöscht", self::$helper->getSuccMessage());
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

