<?php

chdir("../../../../../../../../"); // necessary for the include paths that are used within the classes to be tested

/**
 * Class ilRoomSharingParticipationsTest
 *
 * @group unit
 */
class ilRoomSharingParticipationsTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var ilRoomSharingParticipations
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		$this->object = new ilRoomSharingParticipations;
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown()
	{

	}

	/**
	 * @covers ilRoomSharingParticipations::removeParticipations
	 * @todo   Implement testRemoveParticipations().
	 */
	public function testRemoveParticipations()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers ilRoomSharingParticipations::getList
	 * @todo   Implement testGetList().
	 */
	public function testGetList()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers ilRoomSharingParticipations::getAdditionalBookingInfos
	 * @todo   Implement testGetAdditionalBookingInfos().
	 */
	public function testGetAdditionalBookingInfos()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers ilRoomSharingParticipations::getPoolId
	 * @todo   Implement testGetPoolId().
	 */
	public function testGetPoolId()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers ilRoomSharingParticipations::setPoolId
	 * @todo   Implement testSetPoolId().
	 */
	public function testSetPoolId()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

}
