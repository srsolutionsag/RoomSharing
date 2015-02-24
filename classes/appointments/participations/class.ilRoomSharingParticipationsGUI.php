<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/appointments/participations/class.ilRoomSharingParticipationsTableGUI.php");

/**
 * Class ilRoomSharingParticipationsGUI
 *
 * @author  Alexander Keller <a.k3ll3r@gmail.com>
 * @author  Robert Heimsoth <rheimsoth@stud.hs-bremen.de>
 * @author  Thomas Matern <tmatern@stud.hs-bremen.de>
 *
 * @version $Id$
 * @property ilLanguage $lng
 * @property ilCtrl     $ctrl
 * @property ilTemplate $tpl
 */
class ilRoomSharingParticipationsGUI {

	protected $ref_id;
	private $pool_id;
	private $ctrl;
	private $lng;
	private $tpl;


	/**
	 * Constructor of ilRoomSharingParticipationsGUI.
	 *
	 * @param ilRoomSharingAppointmentsGUI $a_parent_obj
	 */
	function __construct(ilRoomSharingAppointmentsGUI $a_parent_obj) {
		global $ilCtrl, $lng, $tpl;
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		$this->tpl = $tpl;
		$this->ref_id = $a_parent_obj->ref_id;
		$this->pool_id = $a_parent_obj->getPoolId();
	}


	/**
	 * Main switch for command execution.
	 *
	 * @return bool whether the command execution was successful.
	 */
	function executeCommand() {
		$cmd = $this->ctrl->getCmd("showParticipations");

		if ($cmd == 'render') {
			$cmd = 'showParticipations';
		}

		$cmd .= 'Object';
		$this->$cmd();

		return true;
	}


	/**
	 * Leaves multiple Participations which are given via $_POST['booking_ids'] and the
	 * current user.
	 */
	public function leaveMultipleParticipationsObject() {
		$bookings = new ilRoomSharingParticipations($this->pool_id);
		try {
			$bookings->removeParticipations($_POST["booking_ids"]);
		} catch (ilRoomSharingBookingsException $exc) {
			ilUtil::sendFailure($this->lng->txt($exc->getMessage()), true);
			$this->showParticipationsObject();
		}
		$this->showParticipationsObject();
	}


	/**
	 * Shows Confirmation Dialog for leaving multiple bookings. Usually called by clicking the leave-Button.
	 */
	function confirmLeaveMultipleParticipationsObject() {
		$this->showConfirmLeaveDialog($_POST['participations']);
	}


	/**
	 * Shows Confirmation Dialog for leaving a signle booking. Usually called by clicking the leave-Link.
	 */
	function confirmLeaveParticipationObject() {
		$this->showConfirmLeaveDialog(array( $_GET['booking_id'] . '_' . $_GET['booking_subject'] ));
	}


	/**
	 * Shows a confirmation dialog for leaving one or more participations
	 *
	 * @global type $ilTabs
	 *
	 * @param array $a_ids Array of IDs. Each ID has to contain a subject in this form: %id%_%subject%
	 */
	private function showConfirmLeaveDialog($a_ids) {
		global $ilTabs;
		if (!empty($a_ids)) {
			$ilTabs->clearTargets();
			$ilTabs->setBackTarget($this->lng->txt('rep_robj_xrs_participations_back'), $this->ctrl->getLinkTarget($this, 'showParticipations'));

			// create the confirmation GUI
			$confirmation = new ilConfirmationGUI();
			$confirmation->setFormAction($this->ctrl->getFormAction($this));
			$confirmation->setHeaderText($this->lng->txt('rep_robj_xrs_participations_confirm'));

			foreach ($a_ids as $num => $a_booking_info) {
				$parts = explode('_', $a_booking_info, 2);
				$confirmation->addItem('booking_ids[' . $num . ']', $parts[0], $parts[1]);
			}

			$confirmation->setConfirm($this->lng->txt('rep_robj_xrs_participations_confirm_leave'), 'leaveMultipleParticipations'); // cancel the bookings
			$confirmation->setCancel($this->lng->txt('cancel'), 'showParticipations'); // cancel the confirmation dialog

			$this->tpl->setContent($confirmation->getHTML()); // display
		} else {
			ilUtil::sendFailure($this->lng->txt('rep_robj_xrs_participations_no_leave_ids'));
			$this->showParticipationsObject();
		}
	}


	/**
	 * Show all participations.
	 */
	function showParticipationsObject() {
		$participationsTable = new ilRoomSharingParticipationsTableGUI($this, 'showParticipations', $this->ref_id);
		$this->tpl->setContent($participationsTable->getHTML());
	}


	/**
	 * Returns roomsharing pool id.
	 *
	 * @return int pool id
	 */
	function getPoolId() {
		return $this->pool_id;
	}


	/**
	 * Sets roomsharing pool id.
	 *
	 * @param integer $a_pool_id current pool id.
	 */
	function setPoolId($a_pool_id) {
		$this->pool_id = $a_pool_id;
	}
}

?>
