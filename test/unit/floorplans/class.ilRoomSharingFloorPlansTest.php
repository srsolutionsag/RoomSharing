<?php

chdir("../../../../../../../../"); // necessary for the include paths that are used within the classes to be tested
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/floorplans/class.ilRoomSharingFloorPlans.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");
require_once("Services/UICore/classes/class.ilCtrl.php");
require_once("Services/Language/classes/class.ilLanguage.php");
require_once("Services/Init/classes/class.ilias.php");
require_once("Services/Utilities/classes/class.ilBenchmark.php");
require_once("Services/Database/classes/class.ilDB.php");
require_once("Services/User/classes/class.ilObjUser.php");
require_once("Services/Utilities/classes/class.ilUtil.php");
require_once("Services/Object/classes/class.ilObjectDataCache.php");
require_once("Services/Logging/classes/class.ilLog.php");
require_once("Services/MediaObjects/classes/class.ilObjMediaObject.php");
require_once("Services/MediaObjects/classes/class.ilMediaItem.php");

class ilObjectDefinition
{
	public function isRBACObject($a_val)
	{
		return false;
	}

	public function getTranslationType($a_val)
	{
		return $a_val;
	}

}

/**
 * Class ilRoomSharingFloorPlansTest
 *
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 * @group unit
 */
class ilRoomSharingFloorPlansTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var ilRoomSharingFloorPlans
	 */
	private static $floorPlans;
	private static $DBMock;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		$test = new self();
		self::$DBMock = $test->getMockBuilder('ilRoomSharingDatabase')->disableOriginalConstructor()->getMock();

		$allFloorPlans = array(
			array(
				'file_id' => 230,
				'title' => 'Green plan'
			),
			array(
				'file_id' => 234,
				'title' => 'Red plan'
			)
		);
		self::$DBMock->method("getAllFloorplans")->willReturn($allFloorPlans);

// We assume that we have all privileges.
		global $rssPermission;
		$rssPermission = $test->getMockBuilder('ilRoomSharingPermissionUtils')->disableOriginalConstructor()->getMock();
		$rssPermission->method("checkPrivilege")->willReturn(true);

		global $ilCtrl;
		$ilCtrl = $test->getMockBuilder('ilCtrl')->disableOriginalConstructor()->getMock();

		global $lng;
		$lng = $test->getMockBuilder('ilLanguage')->disableOriginalConstructor()->getMock();

		self::$floorPlans = new ilRoomSharingFloorPlans(1, self::$DBMock);
	}

	/**
	 * @covers ilRoomSharingFloorPlans::getAllFloorPlans
	 */
	public function testGetAllFloorPlans()
	{
		$allFloorPlans = self::$floorPlans->getAllFloorPlans();

		self::assertEquals(2, count($allFloorPlans));

		self::assertContains(array(
			'file_id' => 230,
			'title' => 'Green plan'
			), $allFloorPlans);

		self::assertContains(array(
			'file_id' => 234,
			'title' => 'Red plan'
			), $allFloorPlans);
	}

	/**
	 * @covers ilRoomSharingFloorPlans::getFloorPlanInfo
	 */
	public function testGetFloorPlanInfo()
	{
		$floorPlanInfo = array(
			'bla' => 'blu'
		);
		self::$DBMock->method("getFloorplan")->willReturn($floorPlanInfo);

		$info = self::$floorPlans->getFloorPlanInfo(234);

		self::$DBMock->expects($this->once())->method('getFloorplan')->with($this->equalTo(234));

		self::assertEquals(1, count($info));
		self::assertArrayHasKey('bla', $info);
		self::assertContains('blu', $info);
	}

	/**
	 * @covers ilRoomSharingFloorPlans::fileToDatabase
	 */
	public function testFileToDatabase()
	{
		self::$DBMock->method("insertFloorplan")->willReturn(3);

		$rtn = self::$floorPlans->fileToDatabase(235);

		self::$DBMock->expects($this->once())->method('insertFloorplan')->with($this->equalTo(235));
		self::assertEquals(3, $rtn);
	}

	/**
	 * @covers ilRoomSharingFloorPlans::deleteFloorPlan
	 */
	public function testDeleteFloorPlan()
	{
		define('DEBUG', 0);
		define('MAXLENGTH_OBJ_TITLE', 100);
		define('MAXLENGTH_OBJ_DESC', 100);
		define('MDB2_AUTOQUERY_INSERT', 'ins');

		global $ilias, $ilBench, $ilDB, $objDefinition, $ilObjDataCache;
		$ilias = self::getMockBuilder('ilias')->disableOriginalConstructor()->getMock();
		$ilBench = self::getMockBuilder('ilBenchmark')->disableOriginalConstructor()->getMock();
		$ilDB = self::getMockBuilder('ilDB')->disableOriginalConstructor()->getMock();
		$objDefinition = new ilObjectDefinition();

		$ilObjDataCache = self::getMockBuilder('ilObjectDataCache')->disableOriginalConstructor()->getMock();
		$ilObjDataCache->method("lookupType")->willReturn('xxx');

		$objData = array(
			'obj_id' => 235,
			'type' => 'xxx',
			'title' => 'Red floorplan',
			'description' => 'Newest plan',
			'owner' => 1,
			'create_date' => '2015',
			'last_update' => '2015',
			'import_id' => 0
		);
		$objUsages = array("mep_id" => "a");
		$ilDB->method("numRows")->willReturn(1);
		$ilDB->method("fetchAssoc")->will(
			self::onConsecutiveCalls($objData, false, false, $objUsages));

		self::$DBMock->method("deleteFloorPlan")->willReturn(1);

		self::$floorPlans->deleteFloorPlan(235);

		self::$DBMock->expects($this->once())->method('deleteFloorPlan')->with($this->equalTo(235));
		self::$DBMock->expects($this->once())->method('deleteFloorplanRoomAssociation')->with($this->equalTo(235));
	}

	/**
	 * @covers ilRoomSharingFloorPlans::getRoomsWithFloorplan
	 */
	public function testGetRoomsWithFloorplan()
	{
		$roomIDs = array(1, 2, 3);
		self::$DBMock->method("getRoomsWithFloorplan")->willReturn($roomIDs);

		self::assertEquals($roomIDs, self::$floorPlans->getRoomsWithFloorplan(342));
	}

	/**
	 * @covers ilRoomSharingFloorPlans::addFloorPlan
	 */
	public function testAddFloorPlan()
	{
		self::$floorPlans->mobjMock = self::getMockBuilder('ilObjMediaObject')->disableOriginalConstructor()
			->setMethods(array('create', 'setTitle', 'getTitle', 'update'))
			->getMock();

		self::$floorPlans->mobjMock->method("getTitle")->will(
			self::onConsecutiveCalls("FirstPlan", "SecondPlan", "ThirdPlan", "FourthPlan"));
		self::$floorPlans->mobjMock->method("getId")->willReturn(4000);

		$allFloorPlanIds = array(1, 2, 3, 4);
		self::$DBMock->method("getAllFloorplanIds")->willReturn($allFloorPlanIds);

		$newfile = array("format" => "image/bmp", "filename" => "NewFloorplan.bmp", "size" => 2345);

		self::$floorPlans->addFloorPlan("Booba", "Newest plan", $newfile);

		$mediaItem = self::$floorPlans->mobjMock->getMediaItem("Standard");

		self::assertEquals("Newest plan", $mediaItem->getCaption());
		self::assertEquals("image/bmp", $mediaItem->getFormat());
		self::assertEquals("NewFloorplan.bmp", $mediaItem->getLocation());
		self::assertEquals("LocalFile", $mediaItem->getLocationType());
	}

	private function prepareAddFloorPlan()
	{
		self::$floorPlans->mobjMock = &self::getMockBuilder('ilObjMediaObject')->disableOriginalConstructor()->getMock();
		self::$floorPlans->mobjMock->method("getTitle")->will(
			self::onConsecutiveCalls("FirstPlan", "SecondPlan", "ThirdPlan", "FourthPlan"));

		$allFloorPlanIds = array(1, 2, 3, 4);
		self::$DBMock->method("getAllFloorplanIds")->willReturn($allFloorPlanIds);
	}

	/**
	 * @covers ilRoomSharingFloorPlans::addFloorPlan
	 * @expectedException ilRoomSharingFloorplanException
	 * @expectedExceptionMessage rep_robj_xrs_floor_plan_title_is_already_taken
	 */
	public function testAddFloorPlanTitleTaken()
	{
		$this->prepareAddFloorPlan();

		$newfile = array("format" => "image/bmp", "filename" => "NewFloorplan", "size" => 2345);

		self::$floorPlans->addFloorPlan("ThirdPlan", "Newest plan", $newfile);
	}

	/**
	 * @covers ilRoomSharingFloorPlans::addFloorPlan
	 * @expectedException ilRoomSharingFloorplanException
	 * @expectedExceptionMessage rep_robj_xrs_floor_plans_upload_error
	 */
	public function testAddFloorPlanWrongFormat()
	{
		$this->prepareAddFloorPlan();

		$newfile = array("format" => "video/avi", "filename" => "NewFloorplan", "size" => 2345);

		self::$floorPlans->addFloorPlan("Videossas", "Newest plan", $newfile);
	}

	/**
	 * @covers ilRoomSharingFloorPlans::addFloorPlan
	 * @expectedException ilRoomSharingFloorplanException
	 * @expectedExceptionMessage rep_robj_xrs_floor_plans_upload_error
	 */
	public function testAddFloorPlanEmptyFile()
	{
		$this->prepareAddFloorPlan();

		$newfile = array("format" => "image/bmp", "filename" => "NewFloorplan", "size" => 0);

		self::$floorPlans->addFloorPlan("Videossas", "Newest plan", $newfile);
	}

}
