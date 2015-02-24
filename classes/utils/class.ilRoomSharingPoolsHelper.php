<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/class.ilObjRoomSharing.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDatabase.php");
require_once("./Services/MediaObjects/classes/class.ilObjMediaObject.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingNumericUtils.php");

/**
 * Helper class for room sharing pools.
 *
 * @author Thomas Matern <tmatern@stud.hs-bremen.de>
 */
class ilRoomSharingPoolsHelper
{
	/**
	 * Deletes all data of a room sharing pool.
	 *
	 * @param ilObjRoomSharing $a_pool
	 * @return bool true if deletion was successful
	 */
	public static function deletePool(ilObjRoomSharing $a_pool)
	{
		$db = new ilRoomSharingDatabase($a_pool->getPoolId());

		// Calendar
		$calendar_id = $db->getCalendarId();
		$db->deleteCalendar($calendar_id);

		// Bookings
		$all_bookings_ids = $db->getAllBookingsIds();
		// Calendar-Entries
		$db->deleteCalendarEntriesOfBookings($all_bookings_ids);
		foreach ($all_bookings_ids as $booking_id)
		{
			$db->deleteBooking($booking_id);
		}
		// Booking attributes
		$all_booking_attributes = $db->getAllBookingAttributes();
		foreach ($all_booking_attributes as $booking_attribute)
		{
			$db->deleteBookingAttribute($booking_attribute['id']);
			$db->deleteAttributeBookingAssign($booking_attribute['id']);
		}
		// Rooms
		$all_room_ids = $db->getAllRoomIds();
		foreach ($all_room_ids as $room_id)
		{
			$db->deleteRoom($room_id);
		}
		// Room attributes
		$all_room_attributes = $db->getAllRoomAttributes();
		foreach ($all_room_attributes as $room_attribute)
		{
			$db->deleteRoomAttribute($room_attribute['id']);
			$db->deleteAttributeRoomAssign($room_attribute['id']);
		}
		// Privileges
		$classes = $db->getClasses();
		foreach ($classes as $class)
		{
			$db->deleteClass($class['id']); // Takes also care of assignments
		}
		// Floorplans
		$all_floorplans_ids = $db->getAllFloorplanIds();
		foreach ($all_floorplans_ids as $floor_plan_id)
		{
			$db->deleteFloorplan($floor_plan_id);
		}
		// Files of floorplans and rooms user agreement
		foreach ($all_floorplans_ids as $floor_plan_file_id)
		{
			if (!empty($floor_plan_file_id) && $floor_plan_file_id != "0")
			{
				$floor_plan_file = new ilObjMediaObject($floor_plan_file_id);
				$floor_plan_file->delete();
			}
		}
		$rooms_agreement_file_id = $a_pool->getRoomsAgreementFileId();
		if (ilRoomSharingNumericUtils::isPositiveNumber($rooms_agreement_file_id))
		{
			$rooms_agreement_file = new ilObjMediaObject($rooms_agreement_file_id);
			$rooms_agreement_file->delete();
		}
		// Pool itself
		$db->deletePoolEntry("SURE");
	}

	/**
	 * Clones all data of a given room sharing pool to a new one.
	 *
	 * @param ilObjRoomSharing $a_pool
	 * @param ilObjRoomSharing $a_new_pool
	 * @return bool true if cloning was successful
	 */
	public static function clonePool(ilObjRoomSharing $a_pool, ilObjRoomSharing $a_new_pool)
	{
		// Pool main properties
		$rooms_agreement_file_id = $a_pool->getRoomsAgreementFileId();
		$cloned_rooms_agreement_file_id = "0";

		if (ilRoomSharingNumericUtils::isPositiveNumber($rooms_agreement_file_id))
		{
			$rooms_agreement_file = new ilObjMediaObject($rooms_agreement_file_id);
			$cloned_agreement_file = $rooms_agreement_file->duplicate();
			$cloned_rooms_agreement_file_id = $cloned_agreement_file->getId();
		}

		$a_new_pool->setOnline($a_pool->isOnline());
		$a_new_pool->setMaxBookTime($a_pool->getMaxBookTime());
		$a_new_pool->setRoomsAgreementFileId($cloned_rooms_agreement_file_id);
		$a_new_pool->update();

		// Other database related information
		$db = new ilRoomSharingDatabase($a_pool->getPoolId());
		$clone_db = new ilRoomSharingDatabase($a_new_pool->getPoolId());

		// Booking attributes
		$all_booking_attributes = $db->getAllBookingAttributes();
		foreach ($all_booking_attributes as $booking_attribute)
		{
			$clone_db->insertBookingAttribute($booking_attribute['name']);
		}

		// Room attributes
		$all_room_attributes = $db->getAllRoomAttributes();
		$room_attrs_id_mapping = array();
		foreach ($all_room_attributes as $room_attribute)
		{
			$id_of_cloned_attribute = $clone_db->insertRoomAttribute($room_attribute['name']);
			$room_attrs_id_mapping[$room_attribute['id']] = $id_of_cloned_attribute;
		}

		// Floorplans
		$all_floorplan_ids = $db->getAllFloorplanIds();
		// Key is original id and value the new one
		$floorplans_ids_mapping = array();
		foreach ($all_floorplan_ids as $floorplan_id)
		{
			if (ilRoomSharingNumericUtils::isPositiveNumber($floorplan_id))
			{
				$floorplan = new ilObjMediaObject($floorplan_id);
				$cloned_floorplan = $floorplan->duplicate();
				$clone_db->insertFloorplan($cloned_floorplan->getId());
				$floorplans_ids_mapping[$floorplan_id] = $cloned_floorplan->getId();
			}
		}

		// Rooms
		$all_rooms = $db->getAllRooms();
		foreach ($all_rooms as $room)
		{
			// Critical: should be the floorplan_id the building_id or the file_id?
			// building_id is actually used..
			$mapped_floorplan_id = $floorplans_ids_mapping[$room['file_id']];
			$flooplan_id_to_set = "0";
			if (ilRoomSharingNumericUtils::isPositiveNumber($mapped_floorplan_id))
			{
				$flooplan_id_to_set = $mapped_floorplan_id;
			}
			$cloned_room_id = $clone_db->insertRoom($room['name'], $room['type'], $room['min_alloc'],
				$room['max_alloc'], $flooplan_id_to_set, $room['building_id']);
			// Room attribute assignments
			$room_attributes = $db->getAttributesForRoom($room['id']);
			foreach ($room_attributes as $room_attribute)
			{
				$mapped_attr_id = $room_attrs_id_mapping[$room_attribute['id']];
				if (ilRoomSharingNumericUtils::isPositiveNumber($mapped_attr_id))
				{
					$clone_db->insertAttributeForRoom($cloned_room_id, $mapped_attr_id, $room_attribute['count']);
				}
			}
		}

		// Privileges
		$classes = $db->getClasses();
		// Key is original id and value the new one
		$mapped_classes_ids = array();
		foreach ($classes as $class)
		{
			$cloned_class_id = $clone_db->insertClass($class['name'], $class['description'],
				$class['role_id'], $class['priority'], $class['id']);
			$mapped_classes_ids[$class['id']] = $cloned_class_id;
		}
		foreach ($mapped_classes_ids as $class_id => $cloned_class_id)
		{
			$users_ids_for_class = $db->getUsersForClass($class_id);
			foreach ($users_ids_for_class as $user_id)
			{
				$clone_db->assignUserToClass($cloned_class_id, $user_id);
			}
		}
	}

}

?>