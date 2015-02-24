<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/database/class.ilRoomSharingDBConstants.php");
require_once("./Services/MediaObjects/classes/class.ilObjMediaObject.php");

use ilRoomSharingDBConstants as dbc;

/**
 * Class for database queries.
 *
 * @author Malte Ahlering
 *
 * @property ilDB $ilDB
 */
class ilRoomSharingDatabaseFloorplan {

	private $pool_id;
	private $ilDB;


	/**
	 * constructor ilRoomsharingDatabaseFloorplan
	 *
	 * @param integer $a_pool_id
	 */
	public function __construct($a_pool_id) {
		global $ilDB; // Database-Access-Class
		$this->ilDB = $ilDB;
		$this->pool_id = $a_pool_id;
	}


	/**
	 * Gets all floorplans.
	 *
	 * @return type return of $this->ilDB->query
	 */
	public function getAllFloorplans() {
		$set = $this->ilDB->query('SELECT * FROM ' . dbc::FLOORPLANS_TABLE . ' WHERE pool_id = ' . $this->ilDB->quote($this->pool_id, 'integer')
			. ' order by file_id DESC');

		$floorplans = array();
		$row = $this->ilDB->fetchAssoc($set);
		while ($row) {
			$mobj = new ilObjMediaObject($row['file_id']);
			$row["title"] = $mobj->getTitle();
			$floorplans [] = $row;
			$row = $this->ilDB->fetchAssoc($set);
		}

		return $floorplans;
	}


	/**
	 * Gets a floorplan.
	 *
	 * @param integer $a_file_id
	 *
	 * @return type return of $this->ilDB->query
	 */
	public function getFloorplan($a_file_id) {
		$set = $this->ilDB->query('SELECT * FROM ' . dbc::FLOORPLANS_TABLE . ' WHERE file_id = ' . $this->ilDB->quote($a_file_id, 'integer')
			. ' AND pool_id = ' . $this->ilDB->quote($this->pool_id, 'integer'));

		$floorplan = array();
		$row = $this->ilDB->fetchAssoc($set);
		while ($row) {
			$floorplan [] = $row;
			$row = $this->ilDB->fetchAssoc($set);
		}

		return $floorplan;
	}


	/**
	 * Gets a floorplans ids.
	 *
	 * @return type return of $this->ilDB->query
	 */
	public function getAllFloorplanIds() {
		$set = $this->ilDB->query('SELECT file_id FROM ' . dbc::FLOORPLANS_TABLE . ' WHERE pool_id = '
			. $this->ilDB->quote($this->pool_id, 'integer'));

		$floorplans_ids = array();
		$row = $this->ilDB->fetchAssoc($set);
		while ($row) {
			$floorplans_ids [] = $row ['file_id'];
			$row = $this->ilDB->fetchAssoc($set);
		}

		return $floorplans_ids;
	}


	/**
	 * Inserts a floorplan into the database.
	 *
	 * @param integer $a_file_id
	 *
	 * @return type return of $this->ilDB->manipulate
	 */
	public function insertFloorplan($a_file_id) {
		$this->ilDB->insert(dbc::FLOORPLANS_TABLE, array(
				'file_id' => array( 'integer', $a_file_id ),
				'pool_id' => array( 'integer', $this->pool_id )
			));

		return $this->ilDB->getLastInsertId();
	}


	/**
	 * Deletes a floorplan from the database.
	 *
	 * @param integer $a_file_id
	 *
	 * @return type return of $this->ilDB->manipulate
	 */
	public function deleteFloorplan($a_file_id) {
		return $this->ilDB->manipulate('DELETE FROM ' . dbc::FLOORPLANS_TABLE . ' WHERE file_id = ' . $this->ilDB->quote($a_file_id, 'integer')
			. ' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer'));
	}


	/**
	 * Delete floorplan - room association if floorplan will be deleted.
	 *
	 * @param integer floorplan_id
	 *
	 * @return integer amount of affected rows
	 */
	public function deleteFloorplanRoomAssociation($a_file_id) {
		return $this->ilDB->manipulate('UPDATE ' . dbc::ROOMS_TABLE . ' SET file_id = 0 WHERE file_id = ' . $this->ilDB->quote($a_file_id, 'integer')
			. ' AND pool_id =' . $this->ilDB->quote($this->pool_id, 'integer'));
	}


	/**
	 * Set the poolID of bookings
	 *
	 * @param integer $pool_id
	 *            poolID
	 */
	public function setPoolId($pool_id) {
		$this->pool_id = $pool_id;
	}


	/**
	 * Get the PoolID of bookings
	 *
	 * @return integer PoolID
	 */
	public function getPoolId() {
		return (int)$this->pool_id;
	}
}

?>
