<?php

require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/exceptions/class.ilRoomSharingBookingsException.php");
require_once("Services/UIComponent/Toolbar/classes/class.ilToolbarGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/appointments/bookings/class.ilRoomSharingBookingsTableGUI.php");
require_once("Services/PermanentLink/classes/class.ilPermanentLinkGUI.php");
require_once("Services/Utilities/classes/class.ilConfirmationGUI.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/appointments/bookings/class.ilRoomSharingBookings.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/utils/class.ilRoomSharingPermissionUtils.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/privileges/class.ilRoomSharingPrivilegesConstants.php");
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/booking/class.ilRoomSharingShowAndEditBookGUI.php");

use ilRoomSharingPrivilegesConstants as PRIVC;

/**
 * Class ilRoomSharingBookingsGUI
 *
 * @author  Alexander Keller <a.k3ll3r@gmail.com>
 * @author  Robert Heimsoth <rheimsoth@stud.hs-bremen.de>
 * @author  Thomas Matern <tmatern@stud.hs-bremen.de>
 *
 * @version $Id$
 * @property ilCtrl                       $ctrl
 * @property ilLanguage                   $lng
 * @property ilTemplate                   $tpl
 * @property ilRoomSharingPermissionUtils $permission
 */
class ilRoomSharingBookingsGUI {

	protected $ref_id;
	private $pool_id;
	private $permission;
	private $ctrl;
	private $lng;
	private $tpl;
	private $tabs;


	/**
	 * Constructor of ilRoomSharingBookingsGUI
	 *
	 * @global type                        $ilCtrl
	 * @global type                        $lng
	 * @global type                        $tpl
	 *
	 * @param ilRoomSharingAppointmentsGUI $a_parent_obj
	 */
	function __construct(ilRoomSharingAppointmentsGUI $a_parent_obj) {
		global $ilCtrl, $lng, $tpl, $rssPermission, $ilTabs;

		$this->ref_id = $a_parent_obj->ref_id;
		$this->pool_id = $a_parent_obj->getPoolId();
		$this->permission = $rssPermission;
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		$this->tpl = $tpl;
		$this->tabs = $ilTabs;
	}


	/**
	 * Main switch for command execution.
	 *
	 * @return Returns always true.
	 */
	function executeCommand() {
		$cmd = $this->ctrl->getCmd("showBookings");

		if ($cmd == 'render') {
			$cmd = 'showBookings';
		}
		if ($cmd == 'exportbookingobject') {
			exportBookingObject();
		}
		switch ($next_class) {
			default :
				$cmd .= 'Object';
				$this->$cmd();
				break;
		}

		return true;
	}


	/**
	 * Shows all made bookings.
	 *
	 * @global type $tpl
	 */
	function showBookingsObject() {
		$toolbar = new ilToolbarGUI();

		if ($this->permission->checkPrivilege(PRIVC::ADD_OWN_BOOKINGS)) {
			$toolbar->addButton($this->lng->txt('rep_robj_xrs_booking_add'), $this->ctrl->getLinkTargetByClass("ilobjroomsharinggui", "showSearch"));
		}

		include_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/appointments/bookings/class.ilRoomSharingBookingsTableGUI.php");
		$bookingsTable = new ilRoomSharingBookingsTableGUI($this, 'showBookings', $this->ref_id);
		$bookingsTable->initFilter();
		$bookingsTable->getItems();

		$plink = new ilPermanentLinkGUI('xrs', $this->ref_id);

		$this->tpl->setContent($toolbar->getHTML() . $bookingsTable->getHTML() . $plink->getHTML());
	}


	/**
	 * Show booking object
	 */
	function showBookingObject() {
		$this->tabs->clearTargets();
		$this->tabs->setBackTarget($this->lng->txt('rep_robj_xrs_booking_back'), $this->ctrl->getLinkTargetByClass('ilroomsharingappointmentsgui', "showBookings"));
		$booking_id = (int)$_GET['booking_id'];
		$room_id = (int)$_GET['room_id'];
		$booking = new ilRoomSharingShowAndEditBookGUI($this, $booking_id, $room_id, 'show');
		$form = $booking->renderBookingForm();
		$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'booking_id', $booking_id);
		$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'room_id', $room_id);
		$this->tpl->setContent($form->getHTML());
	}


	/**
	 * Edit booking object
	 */
	function editBookingObject() {
		$this->tabs->clearTargets();
		$this->tabs->setBackTarget($this->lng->txt('rep_robj_xrs_booking_back'), $this->ctrl->getLinkTargetByClass('ilroomsharingappointmentsgui', "showBookings"));
		$booking_id = (int)$_GET['booking_id'];
		$room_id = (int)$_GET['room_id'];
		$booking = new ilRoomSharingShowAndEditBookGUI($this, $booking_id, $room_id, 'edit');
		$form = $booking->renderBookingForm();
		$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'booking_id', $booking_id);
		$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'room_id', $room_id);
		$this->tpl->setContent($form->getHTML());
	}


	/**
	 * Save edited booking object
	 */
	function saveEditBookingObject() {
		$this->tabs->clearTargets();
		$this->tabs->setBackTarget($this->lng->txt('rep_robj_xrs_booking_back'), $this->ctrl->getLinkTargetByClass('ilroomsharingappointmentsgui', "showBookings"));
		$booking_id = (int)$_GET['booking_id'];
		$room_id = (int)$_GET['room_id'];
		$booking = new ilRoomSharingShowAndEditBookGUI($this, $booking_id, $room_id, 'edit');
		$form = $booking->renderBookingForm();
		$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'booking_id', $booking_id);
		$this->ctrl->setParameterByClass('ilobjroomsharinggui', 'room_id', $room_id);
		$this->tpl->setContent($form->getHTML());
	}


	/**
	 * Used for deleting bookings.
	 */
	public function cancelBookingObject() {
		$bookings = new ilRoomSharingBookings($this->pool_id);
		// the canceling has to be confirmed via a form, which is why we get the id from POST
		try {
			$bookings->removeBooking($_POST ["booking_id"]);
		} catch (ilRoomSharingBookingsException $exc) {
			ilUtil::sendFailure($this->lng->txt($exc->getMessage()), true);
			$this->showBookingsObject();
		}
		$this->showBookingsObject();
	}


	/**
	 * Asks Confirmation from the user while canceling multiple Bookings.
	 */
	public function confirmMultipleCancelsObject() {
		if (!empty($_POST['bookings'])) {
			$this->tabs->clearTargets();
			$this->tabs->setBackTarget($this->lng->txt('rep_robj_xrs_booking_back'), $this->ctrl->getLinkTarget($this, 'showBookings'));

			// create the confirmation GUI
			$confirmation = new ilConfirmationGUI();
			$confirmation->setFormAction($this->ctrl->getFormAction($this));
			$confirmation->setHeaderText($this->lng->txt('rep_robj_xrs_bookings_confirm'));

			foreach ($_POST['bookings'] as $num => $booking_data) {
				$parts = explode('_', $booking_data, 2); //$booking_data comes as <id>_<subject>
				$confirmation->addItem('booking_ids[' . $num . ']', $parts[0], $parts[1]);
			}

			$confirmation->setConfirm($this->lng->txt('rep_robj_xrs_booking_confirm_cancel'), 'cancelMultipleBookings'); // cancel the bookings
			$confirmation->setCancel($this->lng->txt('cancel'), 'showBookings'); // cancel the confirmation dialog

			$this->tpl->setContent($confirmation->getHTML()); // display
		} else {
			ilUtil::sendFailure($this->lng->txt('rep_robj_xrs_booking_no_cancel_ids'));
			$this->showBookingsObject();
		}
	}


	/**
	 * Cancels Multiple Bookings.
	 */
	public function cancelMultipleBookingsObject() {
		$bookings = new ilRoomSharingBookings($this->pool_id);
		try {
			$bookings->removeMultipleBookings($_POST ["booking_ids"]);
		} catch (ilRoomSharingBookingsException $exc) {
			ilUtil::sendFailure($this->lng->txt($exc->getMessage()), true);
			$this->showBookingsObject();
		}
		$this->showBookingsObject();
	}


	/**
	 * Displays a confirmation dialog, in which the user is given the chance
	 * to decline or confirm his decision.
	 */
	public function confirmCancelObject() {
		$this->tabs->clearTargets();
		$this->tabs->setBackTarget($this->lng->txt('rep_robj_xrs_booking_back'), $this->ctrl->getLinkTarget($this, 'showBookings'));

		// create the confirmation GUI
		$confirmation = new ilConfirmationGUI();
		$confirmation->setFormAction($this->ctrl->getFormAction($this));
		$confirmation->setHeaderText($this->lng->txt('rep_robj_xrs_booking_confirm'));

		$booking_id = $_GET ["booking_id"];
		$booking_subject = $_GET ["booking_subject"];

		$confirmation->addItem('booking_id', $booking_id, $booking_subject);
		$confirmation->setConfirm($this->lng->txt('rep_robj_xrs_booking_confirm_cancel'), 'cancelBooking'); // cancel the booking
		$confirmation->setCancel($this->lng->txt('cancel'), 'showBookings'); // cancel the confirmation dialog

		$this->tpl->setContent($confirmation->getHTML()); // display
	}


	/**
	 * Returns roomsharing pool id.
	 *
	 * @return integer Pool-ID
	 */
	function getPoolId() {
		return $this->pool_id;
	}


	/**
	 * Sets roomsharing pool id.
	 *
	 * @param integer Pool-ID
	 *
	 */
	function setPoolId($a_pool_id) {
		$this->pool_id = $a_pool_id;
	}


	/**
	 * Applys filter
	 */
	public function applyFilterObject() {
		$bookingsTable = new ilRoomSharingBookingsTableGUI($this, 'showBookings', $this->ref_id);
		$bookingsTable->initFilter();
		$bookingsTable->writeFilterToSession(); // writes filter to session
		$bookingsTable->resetOffset(); // set the record offset to 0 (first page)
		$this->showBookingsObject();
	}


	/**
	 * Resets all the input fields.
	 */
	public function resetFilterObject() {
		$bookingsTable = new ilRoomSharingBookingsTableGUI($this, 'showBookings', $this->ref_id);
		$bookingsTable->initFilter();
		$bookingsTable->resetFilter();
		$bookingsTable->resetOffset(); // set the record offset to 0 (first page)
		$this->showBookingsObject();
	}
}

?>
