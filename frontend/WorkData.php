<?php

/**
 * Returns data about job statistics from the database
 */

class WorkData {

	/* returns the number of workers in Lithuania at a given date */
	public static function getCount(DateTime $date) {
		$db = XInitializator::getDB('DB');
		$count = 0;
		$retries = 0;
		$date = clone($date);
		while (($count == 0) && ($retries++ < 5)) {
			$count = $db->getVar('SELECT `total` from sodra_summary WHERE `date` = ?', array($date->format('Y-m-d')));
			$date->modify('-1 day');
		}
		if ($count == 0) {
			PLogger::logError('Zero count for registered workers!');
		}
		return $count;	
	}
	
	/* returns an array of worker numbers over a given period of time */
	public static function getDetails(DateTime $start_date, DateTime $end_date) {
		$result = XInitializator::getDB('DB')->getArray(
			'SELECT UNIX_TIMESTAMP(`date`) as date, `total` as total FROM sodra_summary WHERE `date` BETWEEN ? AND ? ORDER BY `date` ASC',
			 array($start_date->format('Y-m-d'), $end_date->format('Y-m-d')));
		$yield = array();
		foreach($result as $row) {
			$yield[$row['date'] + 3600 * 5] = $row['total'];
		}
		return $yield;
	}

}
