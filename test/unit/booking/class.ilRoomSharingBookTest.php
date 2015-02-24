<?php

chdir("../../../../../../../../"); // necessary for the include paths that are used within the classes to be tested

/**
 * Class ilRoomSharingBookTest
 *
 * @group unit
 */
class ilRoomSharingBookTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var ilRoomSharingBook
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		$this->object = new ilRoomSharingBook;
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown()
	{

	}

	/**
	 * @covers ilRoomSharingBook::addBooking
	 * @todo   Implement testAddBooking().
	 */
	public function testAddBooking()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers ilRoomSharingBook::setPoolId
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
	 * @covers ilRoomSharingBook::getRoomAgreementFileId
	 * @todo   Implement testGetRoomAgreementFileId().
	 */
	public function testGetRoomAgreementFileId()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

}
