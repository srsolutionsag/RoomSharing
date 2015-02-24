<?php

chdir("../../../../../../../../"); // necessary for the include paths that are used within the classes to be tested

/**
 * Class ilRoomSharingBookingsTest
 *
 * @group unit
 */
class ilRoomSharingBookingsTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var ilRoomSharingBookings
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		$this->object = new ilRoomSharingBookings;
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown()
	{

	}

	/**
	 * @covers ilRoomSharingBookings::removeBooking
	 * @todo   Implement testRemoveBooking().
	 */
	public function testRemoveBooking()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers ilRoomSharingBookings::removeMultipleBookings
	 * @todo   Implement testRemoveMultipleBookings().
	 */
	public function testRemoveMultipleBookings()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers ilRoomSharingBookings::getList
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
	 * @covers ilRoomSharingBookings::getAdditionalBookingInfos
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
	 * @covers ilRoomSharingBookings::setPoolId
	 * @todo   Implement testSetPoolId().
	 */
	public function testSetPoolId()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers ilRoomSharingBookings::getPoolId
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
	 * @covers ilRoomSharingBookings::sendCancellationNotification
	 * @todo   Implement testSendCancellationNotification().
	 */
	public function testSendCancellationNotification()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

}
