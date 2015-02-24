<?php

include_once './acceptance/php-webdriver/__init__.php';
include_once './acceptance/tests/ilRoomSharingAcceptanceSeleniumHelper.php';

/**
 * Selenium test class for RoomSharing Pool GUI-Testing
 *
 * @group selenium-R
 * @property WebDriver $webDriver
 * @author Thomas W.
 */
class ilRoomSharingAcceptanceRSSPoolTest extends PHPUnit_Framework_TestCase
{
	private static $webDriver;
	private static $url = 'http://localhost/roomsharingsystem'; // URL to local RoomSharing
	private static $rssObjectName; // name of RoomSharing pool
	private static $login_user = 'root';
	private static $login_pass = 'homer';
	private static $helper;
	/**
	 * Must contain:
	 * - file.jpg
	 *
	 * @var type
	 */
	private static $test_file_absolut_path = 'C:\Users\Tom\Desktop\\';

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
		self::$helper->logout();
	}

	/**
	 * Test RSS pool objects.
	 *
	 *  - create new RoomSharing Pool
	 *  - edit pool
	 *  - copy pool
	 *  - delete pool (new pool and copy)
	 *
	 * @test
	 */
	public function testRSSpool()
	{
		// create new pool
		self::$webDriver->findElement(WebDriverBy::linkText('Magazin - Einstiegsseite'))->click();
		self::$webDriver->findElement(WebDriverBy::id('il_add_new_item_ov_tr'))->click();
		self::$webDriver->findElement(WebDriverBy::id('xrs'))->click();
		self::$webDriver->findElement(WebDriverBy::id('title'))->sendKeys("testPool123");
		self::$webDriver->findElement(WebDriverBy::name('cmd[save]'))->click();
		$this->assertContains("Objekt hinzugefügt", self::$helper->getSuccMessage());
		self::$webDriver->findElement(WebDriverBy::id('desc'))->sendKeys("meine Beschreibung");
		self::$webDriver->findElement(WebDriverBy::name('cmd[updateSettings]'))->click();
		self::$webDriver->findElement(WebDriverBy::id('mm_rep_tr'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Magazin - Einstiegsseite'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText("testPool123"))->click();
		$desc1 = self::$webDriver->findElement(WebDriverBy::cssSelector('div.ilHeaderDesc'))->getText();
		$this->assertContains("meine Beschreibung", $desc1);
		self::$webDriver->findElement(WebDriverBy::linkText("Einstellungen"))->click();
		//set online
		self::$webDriver->findElement(WebDriverBy::id('online'))->click();
		$online_value = self::$webDriver->findElement(WebDriverBy::id('online'))->getAttribute("checked");
		$this->assertContains("checked", $online_value);
		self::$webDriver->findElement(WebDriverBy::name('cmd[updateSettings]'))->click();
		$this->assertContains("Änderungen gespeichert", self::$helper->getSuccMessage());
		//max booked time
		self::$webDriver->findElement(WebDriverBy::id('max_book_time[time]_h'))->sendKeys("05");
		self::$webDriver->findElement(WebDriverBy::id('max_book_time[time]_m'))->sendKeys("30");
		$file = self::$test_file_absolut_path . 'big.jpg';
		self::$webDriver->findElement(WebDriverBy::id('rooms_agreement'))->sendKeys($file);
		self::$webDriver->findElement(WebDriverBy::name('cmd[updateSettings]'))->click();
		$this->assertContains("Änderungen gespeichert", self::$helper->getSuccMessage());
		self::$webDriver->findElement(WebDriverBy::id('mm_rep_tr'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Magazin - Einstiegsseite'))->click();
		// copy pool
		$s = "ilAdvSelListAnchorText_act_" . $this->getIdByName("testPool123") . "_pref_1";
		self::$webDriver->findElement(WebDriverBy::id($s))->click();
		self::$webDriver->findElement(WebDriverBy::linkText("Kopieren"))->click();
		self::$webDriver->findElement(WebDriverBy::name("target"))->click();
		self::$webDriver->findElement(WebDriverBy::name("cmd[saveTarget]"))->click();
		$this->assertContains("Objekt kopiert", self::$helper->getSuccMessage());
		self::$webDriver->findElement(WebDriverBy::linkText("Einstellungen"))->click();
		$new_desc = self::$webDriver->findElement(WebDriverBy::id('il_mhead_t_focus'))->getText();
		$this->assertContains("testPool123 - Kopie", $new_desc);
		self::$webDriver->findElement(WebDriverBy::id('mm_rep_tr'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Magazin - Einstiegsseite'))->click();
		//move RSS pool
		self::$webDriver->findElement(WebDriverBy::id('il_add_new_item_ov_tr'))->click();
		self::$webDriver->findElement(WebDriverBy::id('grp'))->click();
		self::$webDriver->findElement(WebDriverBy::id('title'))->sendKeys("testgruppe1");
		self::$webDriver->findElement(WebDriverBy::name('cmd[save]'))->click();
		$newGrpID = $this->getGrpIdByName("testgruppe1");
		self::$webDriver->findElement(WebDriverBy::id('mm_rep_tr'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Magazin - Einstiegsseite'))->click();
		$s = "ilAdvSelListAnchorText_act_" . $this->getIdByName("testPool123") . "_pref_1";
		self::$webDriver->findElement(WebDriverBy::id($s))->click();
		self::$webDriver->findElement(WebDriverBy::linkText("Verschieben"))->click();
		self::$webDriver->findElement(WebDriverBy::cssSelector("input[value='" . $newGrpID . "']"))->click();
		self::$webDriver->findElement(WebDriverBy::linkText("Einfügen"))->click();
		self::$webDriver->findElement(WebDriverBy::id('mm_rep_tr'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Magazin - Einstiegsseite'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText("testgruppe1"))->click();
		self::$webDriver->findElement(WebDriverBy::linkText("testPool123"))->click();
		$desc = self::$webDriver->findElement(WebDriverBy::cssSelector('div.ilHeaderDesc'))->getText();
		$this->assertContains("meine Beschreibung", $desc);
		//delete new pools
		$s = "ilAdvSelListAnchorText_act_" . $this->getIdByName("testPool123") . "_pref_1";
		self::$webDriver->findElement(WebDriverBy::id($s))->click();
		self::$webDriver->findElement(WebDriverBy::linkText("Löschen"))->click();
		self::$webDriver->findElement(WebDriverBy::name("cmd[confirmedDelete]"))->click();
		$this->assertContains("Objekt(e) gelöscht", self::$helper->getSuccMessage());
		$s = "ilAdvSelListAnchorText_act_" . $this->getIdByName("testPool123 - Kopie") . "_pref_1";
		self::$webDriver->findElement(WebDriverBy::id($s))->click();
		self::$webDriver->findElement(WebDriverBy::linkText("Löschen"))->click();
		self::$webDriver->findElement(WebDriverBy::name("cmd[confirmedDelete]"))->click();
		$this->assertContains("Objekt(e) gelöscht", self::$helper->getSuccMessage());
	}

	/**
	 * get ILIAS id by name of RoomSharing pool
	 * @param type $name
	 */
	private function getIdByName($name)
	{
		$pool = self::$webDriver->findElement(WebDriverBy::linkText($name));
		$pool2 = $pool->getAttribute("href");
		$pos = strpos($pool2, "id=");
		return substr($pool2, $pos + 3, -18);
	}

	/**
	 * get ILIAS id by name of ILIAS group
	 * @param type $name
	 */
	private function getGrpIdByName($name)
	{
		$pool = self::$webDriver->findElement(WebDriverBy::linkText($name));
		$pool2 = $pool->getAttribute("href");
		$pos = strpos($pool2, "id=");
		return substr($pool2, $pos + 3, -75);
	}

}
