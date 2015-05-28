<?php
include_once("./Services/Table/interfaces/interface.ilTableFilterItem.php");

/**
 * Class xrsTimeInputGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xrsTimeInputGUI extends ilSubEnabledFormPropertyGUI implements ilTableFilterItem, ilToolbarItem {

	/**
	 * @var DateTime
	 */
	protected $time;
	/**
	 * @var int
	 */
	protected $hours = 0;
	/**
	 * @var int
	 */
	protected $minutes = 0;


	/**
	 * @return string
	 */
	protected function render() {
		$tpl = new ilTemplate('./Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/templates/default/utils/tpl.hours_input.html', false, false);
		//				echo '<pre>' . print_r($this->getHours(), 1) . '</pre>';
		//				echo '<pre>' . print_r($this->getMinutes(), 1) . '</pre>';
		$tpl->setVariable('POSTVAR', $this->getPostVar());
		for ($x = 0; $x < 24; $x ++) {
			$tpl->setCurrentBlock('hour');
			if ($x == $this->getHours()) {
				$tpl->setVariable('SELECTED', "selected=selected");
			}
			$tpl->setVariable('VAL', $x);
			$tpl->setVariable('DISPLAY', str_pad($x, 2, '0', STR_PAD_LEFT));
			$tpl->parseCurrentBlock();
		}
		for ($x = 0; $x < 60; $x = $x + 5) {
			$tpl->setCurrentBlock('minute');
			if ($this->getMinutes() >= $x AND $this->getMinutes() < $x + 5) {
				$tpl->setVariable('SELECTED', "selected=selected");
			}
			$tpl->setVariable('VAL', $x);
			$tpl->setVariable('DISPLAY', str_pad($x, 2, '0', STR_PAD_LEFT));
			$tpl->parseCurrentBlock();
		}

		return $tpl->get();
	}


	/**
	 * @param $data
	 */
	public function setValueByArray($data) {
		$time = $data[$this->getPostVar()];
		if (is_array($time)) {
			$time = $time['h'] . ':' . $time['m'] . ':00';
		}

		$date = new DateTime($time);
		$this->setHours($date->format('G'));
		$this->setMinutes($date->format('i'));
	}


	/**
	 * @param array $input
	 *
	 * @return DateTime
	 */
	public static function getInputAsDateTime(array $input) {
		$time = $input['h'] . ':' . $input['m'] . ':00';

		return new DateTime($time);
	}


	/**
	 * Insert property html
	 *
	 * @return    int    Size
	 */
	public function insert(&$a_tpl) {
		$html = $this->render();

		$a_tpl->setCurrentBlock("prop_generic");
		$a_tpl->setVariable("PROP_GENERIC", $html);
		$a_tpl->parseCurrentBlock();
	}


	function checkInput() {
		return true;
	}


	/**
	 * Get input item HTML to be inserted into table filters
	 *
	 * @return string
	 */
	public function getTableFilterHTML() {
		return $this->render();
	}


	/**
	 *
	 * Get input item HTML to be inserted into ilToolbarGUI
	 *
	 * @access    public
	 * @return    string
	 *
	 */
	public function getToolbarHTML() {
		return $this->render();
	}


	/**
	 * @return DateTime
	 */
	public function getTime() {
		return $this->time;
	}


	/**
	 * @param DateTime $time
	 */
	public function setTime($time) {
		$this->time = $time;
	}


	/**
	 * @return int
	 */
	public function getHours() {
		return $this->hours;
	}


	/**
	 * @param int $hours
	 */
	public function setHours($hours) {
		$this->hours = $hours;
	}


	/**
	 * @return int
	 */
	public function getMinutes() {
		return $this->minutes;
	}


	/**
	 * @param int $minutes
	 */
	public function setMinutes($minutes) {
		$this->minutes = $minutes;
	}
}

?>
