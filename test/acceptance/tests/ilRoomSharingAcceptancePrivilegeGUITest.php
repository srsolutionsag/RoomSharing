<?php

include_once './acceptance/php-webdriver/__init__.php';
include_once './acceptance/tests/ilRoomSharingAcceptanceSeleniumHelper.php';

/**
 * This class represents the gui-testing for the RoomSharing System privileges
 *
 * @group selenium-privileges
 * @property WebDriver $webDriver
 * @author Dan Sörgel
 */
class ilRoomSharingAcceptanceSearchTest extends PHPUnit_Framework_TestCase
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
	 * Precondition: No priviledge classes
	 * Postcondition: No priviledge classes
	 * @test
	 */
	public function editClassesTest()
	{
		//Setup by defining two classes
		self::$helper->createPrivilegClass("Test_A", "Test_A", "Keine", 5);
		self::$helper->createPrivilegClass("Test_B", "Test_B", "Keine", 7);

		//#1 Rename class to a non-existing name
		self::$webDriver->findElement(WebDriverBy::linkText('Test_A'))->click();
		self::$webDriver->findElement(WebDriverBy::name('name'))->clear()->sendKeys('Test_C');
		self::$webDriver->findElement(WebDriverBy::name('cmd[saveEditClassForm]'))->click();
		if (!(self::$webDriver->findElement(WebDriverBy::name('name'))->getAttribute('value') == 'Test_C'))
		{
			$this->fail("#1: Renaming a class seems not to work ");
		}

		//#2 Rename class to no name
		self::$webDriver->findElement(WebDriverBy::name('name'))->clear();
		self::$webDriver->findElement(WebDriverBy::name('cmd[saveEditClassForm]'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#2: Renaming a class to no name seems to work " . $ex);
		}

		//#3 Rename class to an existing name
		self::$webDriver->findElement(WebDriverBy::name('name'))->clear()->sendKeys('Test_B');
		self::$webDriver->findElement(WebDriverBy::name('cmd[saveEditClassForm]'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#3: Renaming a class to an existing name seems to work");
		}
		self::$webDriver->findElement(WebDriverBy::name('name'))->clear()->sendKeys('Test_A');

		//#4 Change Description
		self::$webDriver->findElement(WebDriverBy::name('description'))->clear()->sendKeys('New Desc');
		self::$webDriver->findElement(WebDriverBy::name('cmd[saveEditClassForm]'))->click();
		if (!(self::$webDriver->findElement(WebDriverBy::name('description'))->getAttribute('value') == 'New Desc'))
		{
			$this->fail("#4: Changing description seems not to work ");
		}

		//#5 Change role assign
		self::$webDriver->findElement(WebDriverBy::name('role_assignment'))->sendKeys('Administrator');
		self::$webDriver->findElement(WebDriverBy::name('cmd[saveEditClassForm]'))->click();
		$selected = self::$webDriver->findElement(WebDriverBy::xpath("//option[text()='Administrator']"))->getAttribute('selected');
		if (empty($selected))
		{
			$this->fail("#5: Changing role assign seems not to work ");
		}

		//#6 Change priority
		self::$webDriver->findElement(WebDriverBy::name('priority'))->sendKeys('9');
		self::$webDriver->findElement(WebDriverBy::name('cmd[saveEditClassForm]'))->click();
		$selected = self::$webDriver->findElement(WebDriverBy::xpath("//option[text()='9']"))->getAttribute('selected');
		if (empty($selected))
		{
			$this->fail("#6: Changing priority seems not to work ");
		}
		self::$webDriver->findElement(WebDriverBy::linkText('Benutzerzuweisung'))->click();

		//#7 Click add user to class and go back to attributes
		self::$webDriver->findElement(WebDriverBy::linkText('Benutzerzuweisung'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText('Eigenschaften'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText(' Klasse löschen '));
		}
		catch (Exception $ex)
		{
			$this->fail("#7 Click on Attributes seems not to work");
		}

		//#8 Click add user to class
		self::$webDriver->findElement(WebDriverBy::linkText('Benutzerzuweisung'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText(' Benutzer suchen '));
		}
		catch (Exception $ex)
		{
			$this->fail("#8 Click on add user seems not to work");
		}

		//#9 Add a new user to class
		self::$webDriver->findElement(WebDriverBy::id('user_login'))->clear()->sendKeys('root');
		self::$webDriver->findElement(WebDriverBy::name('cmd[addUserFromAutoComplete]'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::xpath("//span[text()='root']"));
		}
		catch (Exception $ex)
		{
			$this->fail("#9 Add a user seems not to work");
		}

		//#10 Add an assigned user to class
		self::$webDriver->findElement(WebDriverBy::id('user_login'))->clear()->sendKeys('root');
		self::$webDriver->findElement(WebDriverBy::name('cmd[addUserFromAutoComplete]'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilInfoMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#10 Add an assigned user seems to work");
		}

		//#11 Add an empty user to class
		self::$webDriver->findElement(WebDriverBy::id('user_login'))->clear();
		self::$webDriver->findElement(WebDriverBy::name('cmd[addUserFromAutoComplete]'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#11 Add an empty user seems to work");
		}

		//#12 Add an assigned user to class
		self::$webDriver->findElement(WebDriverBy::id('user_login'))->clear()->sendKeys('iamnouser');
		self::$webDriver->findElement(WebDriverBy::name('cmd[addUserFromAutoComplete]'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#12 Add an non-existing user seems to work");
		}

		//#13 Remove an assigned user
		self::$webDriver->findElement(WebDriverBy::linkText('Entfernen'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::xpath("//td[text()='Keine Einträge']"));
		}
		catch (Exception $ex)
		{
			$this->fail("#13 Delte an assigned user seems not to work");
		}

		//#14 Remove multiple assigned users
		self::$webDriver->findElement(WebDriverBy::id('user_login'))->clear()->sendKeys('root');
		self::$webDriver->findElement(WebDriverBy::name('cmd[addUserFromAutoComplete]'))->click();
		self::$webDriver->findElement(WebDriverBy::id('user_login'))->clear()->sendKeys('dummy');
		self::$webDriver->findElement(WebDriverBy::name('cmd[addUserFromAutoComplete]'))->click();
		self::$webDriver->findElement(WebDriverBy::xpath("//label[text()='Alle auswählen']"))->click();
		self::$webDriver->findElement(WebDriverBy::name('cmd[deassignUsersFromClass]'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::xpath("//td[text()='Keine Einträge']"));
		}
		catch (Exception $ex)
		{
			$this->fail("#14 Delte multiple assigned users seems not to work");
		}

		//#15 Cancel search users
		self::$webDriver->findElement(WebDriverBy::linkText(' Benutzer suchen '))->click();
		self::$webDriver->findElement(WebDriverBy::name('cmd[cancel]'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::xpath("//td[text()='Keine Einträge']"));
		}
		catch (Exception $ex)
		{
			$this->fail("#15 Go back from search seems not to work");
		}

		//#16 Add user by search for users
		self::$webDriver->findElement(WebDriverBy::linkText(' Benutzer suchen '))->click();
		self::$webDriver->findElement(WebDriverBy::name('rep_query[usr][login]'))->clear()->sendKeys('root');
		self::$webDriver->findElement(WebDriverBy::name('cmd[performSearch]'))->click();
		self::$webDriver->findElement(WebDriverBy::xpath("//label[text()='Alle auswählen']"))->click();
		self::$webDriver->findElement(WebDriverBy::name('cmd[addUser]'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::xpath("//span[text()='root']"));
		}
		catch (Exception $ex)
		{
			$this->fail("#16 Add user by search seems not to work");
		}

		//Delete classes after tesing is done
		self::$helper->deletePrivilegClass("Test_A ⇒ Administrator");
		self::$helper->deletePrivilegClass("Test_B");
	}

	/**
	 * Precondition: No priviledge classes
	 * Postcondition: No priviledge classes
	 * @test
	 */
	public function changePriviledgesTest()
	{
		//Setup by defining two classes
		self::$helper->createPrivilegClass("Test_A", "Test_A", "Keine", 2);
		self::$helper->createPrivilegClass("Test_B", "Test_B", "Keine", 1);

		//Get variable Class-ID for both classes
		$a_id = self::$helper->getPrivilegeClassIDByName("Test_A");
		$b_id = self::$helper->getPrivilegeClassIDByName("Test_B");

		//Get all privileges
		$priv = array();
		$priv[] = 'accessAppointments';
		$priv[] = 'accessSearch';
		$priv[] = 'addOwnBookings';
		$priv[] = 'addParticipants';
		$priv[] = 'addSequenceBookings';
		$priv[] = 'addUnlimitedBookings';
		$priv[] = 'seeNonPublicBookingInformation';
		$priv[] = 'notificationSettings';
		$priv[] = 'adminBookingAttributes';
		$priv[] = 'cancelBookingLowerPriority';
		$priv[] = 'accessRooms';
		$priv[] = 'seeBookingsOfRooms';
		$priv[] = 'addRooms';
		$priv[] = 'editRooms';
		$priv[] = 'deleteRooms';
		$priv[] = 'adminRoomAttributes';
		$priv[] = 'accessFloorplans';
		$priv[] = 'addFloorplans';
		$priv[] = 'editFloorplans';
		$priv[] = 'deleteFloorplans';
		$priv[] = 'accessSettings';
		$priv[] = 'accessPrivileges';
		$priv[] = 'addClass';
		$priv[] = 'editClass';
		$priv[] = 'deleteClass';
		$priv[] = 'editPrivileges';
		$priv[] = 'lockPrivileges';

		//#1 Check them all by activate each
		foreach ($priv as $privilege)
		{
			$ret = self::$helper->changeAndCheckPrivilegeChange($privilege, $a_id);
			if (!$ret)
			{
				$this->fail("#1 Change of priviledge " . $privilege . " has not been saved.");
			}
		}

		//#2 Check group ticks
		self::$webDriver->findElement(WebDriverBy::id('select_' . $b_id . '_bookings'))->click();
		self::$webDriver->findElement(WebDriverBy::id('select_' . $b_id . '_rooms'))->click();
		self::$webDriver->findElement(WebDriverBy::id('select_' . $b_id . '_floorplans'))->click();
		self::$webDriver->findElement(WebDriverBy::id('select_' . $b_id . '_privileges'))->click();
		self::$webDriver->findElement(WebDriverBy::name('cmd[savePrivilegeSettings]'))->click();
		foreach ($priv as $privilege)
		{
			$el_saved = self::$webDriver->findElement(WebDriverBy::name('priv[' . $b_id . '][' . $privilege . ']'));
			$checked_saved = $el_saved->getAttribute('checked');
			if (empty($checked_saved))
			{
				$this->fail("#2 Change of a group of priviledges with priviledge " . $privilege . " has not been saved.");
			}
		}

		//#3 Check lock privilege
		self::$webDriver->findElement(WebDriverBy::name('lock[' . $a_id . ']'))->click();
		self::$webDriver->findElement(WebDriverBy::name('cmd[savePrivilegeSettings]'))->click();
		self::$webDriver->findElement(WebDriverBy::name('cmd[showPrivileges]'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText(' Neue Klasse anlegen '));
		}
		catch (Exception $ex)
		{
			$this->fail("#3.1: Cancel of locking a privilege class dow not redirect so overview " . $ex);
		}
		self::$webDriver->findElement(WebDriverBy::name('lock[' . $a_id . ']'))->click();
		self::$webDriver->findElement(WebDriverBy::name('cmd[savePrivilegeSettings]'))->click();
		self::$webDriver->findElement(WebDriverBy::name('cmd[lockClassesAfterConfirmation]'))->click();
		$checkedLock_a = self::$webDriver->findElement(WebDriverBy::name('lock[' . $a_id . ']'))->getAttribute('checked');
		if (empty($checkedLock_a))
		{
			$this->fail('#3.2: Locking a privilege class does not work.');
		}

		//#4 Copy Privileges from another class
		self::$helper->createPrivilegClass("Test_C", "Test_C", "Keine", 3, "Test_A");
		$c_id = self::$helper->getPrivilegeClassIDByName("Test_C");
		$checkedLock_c = self::$webDriver->findElement(WebDriverBy::name('lock[' . $c_id . ']'))->getAttribute('checked');
		if (!empty($checkedLock_c))
		{
			$this->fail('#4.1: Lock from class is copied into new class.');
		}
		foreach ($priv as $privilege)
		{
			$el_saved = self::$webDriver->findElement(WebDriverBy::name('priv[' . $c_id . '][' . $privilege . ']'));
			$checked_saved = $el_saved->getAttribute('checked');
			if (empty($checked_saved))
			{
				$this->fail("#4.2: Copy privilege " . $privilege . " has failed.");
			}
		}

		//#5 change privileges of different classes
		$class_ids = array($a_id, $b_id, $c_id);
		$choosen = array();
		foreach ($class_ids as $class_id)
		{
			$privlege = $priv[array_rand($priv)];
			$choosen[$class_id] = $privlege;
			self::$webDriver->findElement(WebDriverBy::name('priv[' . $class_id . '][' . $privlege . ']'))->click();
		}
		self::$webDriver->findElement(WebDriverBy::name('cmd[savePrivilegeSettings]'))->click();
		foreach ($class_ids as $class_id)
		{
			$checked = self::$webDriver->findElement(WebDriverBy::name('priv[' . $class_id . '][' . $choosen[$class_id] . ']'))->getAttribute('checked');
			if (!empty($checked))
			{
				$this->fail("#5: Changing random privilege " . $choosen[$class_id] . " of class " . $class_id . " did not work.");
			}
		}

		//Delete classes after tesing is done
		self::$helper->deletePrivilegClass("Test_A");
		self::$helper->deletePrivilegClass("Test_B");
		self::$helper->deletePrivilegClass("Test_C");
	}

	/**
	 * Precondition: No priviledge classes
	 * Postcondition: No priviledge classes
	 * @test
	 */
	public function createAndDeleteClassesTest()
	{
		//#1 Test Cancel of a creation
		self::$webDriver->findElement(WebDriverBy::linkText('Privilegien'))->click();
		self::$webDriver->findElement(WebDriverBy::linkText(' Neue Klasse anlegen '))->click();
		self::$webDriver->findElement(WebDriverBy::name('cmd[showPrivileges]'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText(' Neue Klasse anlegen '));
		}
		catch (Exception $ex)
		{
			$this->fail("#1: Cancel of a new privilege class does not link to pivilege class overview: " . $ex);
		}

		//#2 Create a Privilege Class
		self::$helper->createPrivilegClass("Test_A", "Test_A", "User", 1);
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText("Test_A ⇒ User"));
		}
		catch (Exception $ex)
		{
			$this->fail("#2.1: Creation of a new privilege class with ILIAS-role does not work: " . $ex);
		}
		self::$helper->createPrivilegClass("Test_C", "Test_C", "Keine", 5);
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText("Test_C"));
		}
		catch (Exception $ex)
		{
			$this->fail("#2.2: Creation of a new privilege class without ILIAS-role does not work: " . $ex);
		}
		self::$helper->deletePrivilegClass("Test_C");

		//#3 Create an existing privilege class
		self::$helper->createPrivilegClass("Test_A", "Test_A", "User", 1);
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#3: Creation of an existing privilege class with ILIAS-role  works: " . $ex);
		}

		//#4 Create a privilege class that gets user-rolls from the same role as another already existing class.
		self::$webDriver->findElement(WebDriverBy::name('cmd[showPrivileges]'))->click();
		self::$helper->createPrivilegClass("Test_B", "Test_B", "User", 0);
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText("Test_B ⇒ User"));
		}
		catch (Exception $ex)
		{
			$this->fail("#4: Creation of a new privilege class with same ILIAS-role as another does not work: " . $ex);
		}
		self::$helper->deletePrivilegClass("Test_B ⇒ User");

		//#5 Create an empty privileges class
		self::$helper->createPrivilegClass("", "Test_C", "Guest", 2);
		try
		{
			self::$webDriver->findElement(WebDriverBy::className("ilFailureMessage"));
		}
		catch (Exception $ex)
		{
			$this->fail("#5: Creation of a no-name privilege class seems to be allowed: " . $ex);
		}
		self::$webDriver->findElement(WebDriverBy::name("cmd[showPrivileges]"))->click();

		//#6 Delete Test_A class, none should be left
		self::$webDriver->findElement(WebDriverBy::linkText("Test_A ⇒ User"))->click();
		self::$webDriver->findElement(WebDriverBy::linkText(' Klasse löschen '))->click();
		self::$webDriver->findElement(WebDriverBy::name('cmd[renderEditClassForm]'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText(' Klasse löschen '))->click();
		}
		catch (Exception $ex)
		{
			$this->fail("#6.1: Click cancel in the delete confirmation does not redirect to edit of the class " . $ex);
		}
		self::$webDriver->findElement(WebDriverBy::linkText('Zurück zu den Klasseneigenschaften'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::linkText(' Klasse löschen '))->click();
		}
		catch (Exception $ex)
		{
			$this->fail("#6.2: Click backtab in the delete confirmation does not redirect to edit of the class " . $ex);
		}
		self::$webDriver->findElement(WebDriverBy::name('cmd[deleteClass]'))->click();
		try
		{
			self::$webDriver->findElement(WebDriverBy::xpath("//td[text()='Keine Klassen vorhanden']"))->click();
		}
		catch (Exception $ex)
		{
			$this->fail("#6.3: Deleting a privileges class does not delete it " . $ex);
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