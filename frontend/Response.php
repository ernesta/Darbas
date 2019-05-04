<?php
/**
 * Singleton object, returns values for the charts in the frontend
 * Relies on WorkData class to get all the values from the DB
 */

class Response {

	private static $instance = null;

	protected $totalCount = null;
	protected $change = null;

	public static function getMe() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}	

	private function __construct() {
		$this->date = new DateTime();
		if (intval($this->date->format('H')) < 9) {
			$this->date->modify('-1 day');
		}
	}

	public function getTotalCount() {
		if ($this->totalCount === null) {
			$this->totalCount = WorkData::getCount($this->date);
		}
		return $this->totalCount;
	}

	public function getChange() {
		if ($this->change === null) {
			$date = clone($this->date);
			$date->modify('-1 day');
			$this->change = $this->getTotalCount() - WorkData::getCount($date);
		}
		return $this->change;
	}

	public function getEnding($one, $two_nine, $many) {
		$count = $this->getTotalCount() % 100;
		if ($count % 10 == 1) {
			return $one;
		}
		elseif (($count % 10 == 0) || (($count > 10) && ($count < 20))) {
			return $many;
		}
		else {
			return $two_nine;
		}
	}
	
	public function getChartData() {
		$date = clone($this->date);
		$date->modify('-1 year');
		$details = WorkData::getDetails($date, $this->date);
		if (!empty($details)) {
			$obj = new StdClass();
			$obj->color = ($this->getChange() > 0) ? '#82CAFA' : '#F665AB';
			reset($details);			
			$obj->startDate = key($details);
			$obj->data = $details;			
			return $obj;
		}	
	}	

}
