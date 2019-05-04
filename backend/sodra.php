<?php

/*
 * This file should not be run directly. It's used by sodra.py to import data from zip files provided by SoDra. 
 * Reads csv input from stdin and saves it MySQL DB. Such a setup was done as python on production server
 * did not have mysql connectors. And because we like php.
 */

$settings = @parse_ini_file(dirname(__FILE__) . '/../settings/settings.ini', true);
$db = $db = new PDO('mysql:dbname=' . $settings['mysql']['db_name'] . ';host=' . $settings['mysql']['host'], $settings['mysql']['username'], $settings['mysql']['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));

//check if tables exist, create if they don't;
$db->exec(
	'CREATE TABLE IF NOT EXISTS `sodra_summary` (
		`date` date NOT NULL,
		`total` bigint(20) NOT NULL,
		PRIMARY KEY (`date`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1;'
);

$db->exec(
	"CREATE TABLE IF NOT EXISTS `sodra` (
		`mok_kodas` int(12) NOT NULL DEFAULT '0',
		`dr_kodas` int(10) NOT NULL DEFAULT '0',
		`skaicius` int(10) NOT NULL,
		`data` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`mok_kodas`,`dr_kodas`,`data`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1;"
);

//read from stdin and import data
$db->beginTransaction();
$stmt = $db->prepare("INSERT INTO sodra (mok_kodas, dr_kodas, skaicius, data) VALUES (:mok_kodas, :dr_kodas, :skaicius, :data)");

while(($data = fgetcsv(STDIN)) != false) {
	$stmt->bindValue(':mok_kodas', $data[0]);
	$stmt->bindValue(':dr_kodas', $data[1]);
	$stmt->bindValue(':skaicius', $data[2]);
	$stmt->bindValue(':data', $data[3]);
	$date = $data[3];
	$stmt->execute();
}
$db->commit();
//create a summary point in sodra_summary table to avoid costly queries later on
$s = $db->prepare('INSERT INTO sodra_summary (date, total) SELECT data, sum(skaicius) FROM sodra WHERE data = ? GROUP BY data');
$s->bindValue(1, $date);
$s->execute();



