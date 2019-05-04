<?php

/**
 * Before there was Monolog, there was a PLogger. Same principle, really, just a bit simplier.
 */

class PLogger {
	
	const E_INFO = 1;
	const E_NOTICE = 2;
	const E_WARNING = 3;
	const E_ERROR = 4;
	
	/** @var $store PLoggerStore */
	protected static $store = null;
	
	/** @var $reportingLevel int */
	protected static $reportingLevel = 2;
	
	/** @var $modules array */
	protected static $modules = array();
	
	/** @return PLogger */
	
	public static function initialize(PLoggerStore $store, $level = PLogger::E_NOTICE, $catchPHPErrors = false) {
		self::$store = $store;
		self::$reportingLevel = $level;
		if ($catchPHPErrors) { set_error_handler('PLogger::errorHandler'); }
	}
	
	public static function setReportingLevel($level) {
		self::$reportingLevel = $level;
	}
	
	public static function setModuleLevel($module, $level) {
		self::$modules[$module] = $level;
	}
	
	public static function errorHandler($type, $message, $errfile, $errline, $errcontext) {
		$data = array('message' => $message, 'file' => $errfile, 'line' => $errline, 'context' => $errcontext);
		if ($type === E_NOTICE || $type === E_USER_NOTICE) {
			self::logNotice ('NOTICE', $data, 'PHP');
		}
		elseif ($type === E_WARNING || $type === E_USER_WARNING || $type === E_DEPRECATED) {
			self::logNotice ('PHP WARNING', $data, 'PHP');
		}
		elseif ($type === E_USER_ERROR) {
			self::logError ('USER ERROR', $data, 'PHP');
			exit(1);
		}
	}
	
	public static function logInfo($title, $data, $module = null) {
		self::log(self::E_INFO, $title, $data, $module);		
	}
	
	public static function logNotice($title, $data, $module = null) {
		self::log(self::E_NOTICE, $title, $data, $module);
	}
	
	public static function logWarning($title, $data, $module = null) {
		self::log(self::E_WARNING, $title, $data, $module);
	}
	
	public static function logError($title, $data, $module = null) {
		self::log(self::E_ERROR, $title, $data, $module);
	}
	
	public static function pprint($data) {
		print '<pre>' . print_r($data, 1) . '</pre>';
	}
	
	protected static function log($type, $title, $data, $module) {
		if (($type >= self::$reportingLevel) ||
			(($module !== null) && (isset(self::$modules[$module])) && (self::$modules[$module] <= $type))) {
			if (self::$store !== null) { self::$store->save($title, $data, $module); }
		}
	}
	
}

interface PLoggerStore {
	public function save($title, $data, $module);
}

class PLoggerException extends Exception {}


class PLoggerScreenStore implements PLoggerStore {
	
	public function save($title, $data, $module) {
		echo '<br>********' . strtoupper($module) . ': ' . $title . '***********<br>';		
		if (!is_scalar($data)) { $data = '<pre>' . print_r($data, 1) . '</pre>'; }
		echo $data;
	}	
}

class PLoggerCLIStore implements PLoggerStore {
	
	public function save($title, $data, $module) {
		echo "\n********" . strtoupper($module) . ': ' . $title . "***********\n";		
		if (!is_scalar($data)) { $data = print_r($data, 1) . "\n"; }
		echo $data;
	}	
}

class PLoggerFileStore implements PLoggerStore {
	
	protected $file = null;
	
	public function __construct($file) {
		if (!is_writeable($file)) { throw new PLoggerException('log file provided is not writeable!'); }
		else { $this->file = $file; }
	}
	
	public function save($title, $data, $module) {
		$title =  "\n" .'********' . strtoupper($module) . ': ' . $title . '***********' . "\n";		
		if (!is_scalar($data)) { $data = print_r($data, 1); }
		file_put_contents($this->file, $title . $data, FILE_APPEND);
	}	
}
