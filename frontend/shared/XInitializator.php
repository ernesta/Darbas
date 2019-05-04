<?php

/**
 * Manages autoloading of other classes and configuration files.
 */

class ConfigurationException extends Exception {}

class XInitializator {
	
	/** @var boolean */
	protected static $hasRun = FALSE;
	
	/** @var array */
	protected static $defaults = array(
		'time_limit' => 500,
		'encoding' => 'UTF-8',
		'autoloader' => array('path' => '', 'max_depth' => 2),
		'error-level' => 32767
	);
	
	/** @var array */
	protected static $classes = array();
	
	/** @var PDO */
	protected static $dbObject = null;
	
	/** @var array | boolean */
	protected static $settings = false;
	
	/**
	 * initialize - do all the random things at startup
	 * @param string $settings_file
	 * @param array $defaults
	 * @throws ConfigurationException 
	 */			
	public static function initialise($settings_file, array $defaults = array()) {
		
		/* make sure it runs only once */
		if (self::$hasRun) { throw new ConfigurationException('Initializator already initialized'); }
		else self::$hasRun = TRUE;
		
		/* set the generic options based on defaults & provided values */
		self::$defaults = array_merge(self::$defaults, $defaults);
		set_time_limit(self::$defaults['time_limit']);
		mb_internal_encoding(self::$defaults['encoding']);
		error_reporting(self::$defaults['error-level']);		
		
		/* parse settings file */
		if (!file_exists($settings_file)) { 
			throw new ConfigurationException('The provided settings file does not exist'); 
		}
		else {
			self::$settings = @parse_ini_file($settings_file, TRUE);
			if (self::$settings === false) throw new ConfigurationException('The provided settings file is not valid');
		}
		/* initialize the AutoLoader */		
		spl_autoload_register('self::loader');				
	}
	
	/**
	 * Callback for spl_autoload_register.
	 * Loads a class if known or throws an exception
	 * @param string $className
	 */
	
	public static function loader($className) {
		if (isset(self::$classes[$className])) {			
			include self::$classes[$className];
		}
	}
	
	/**
	 * Loads all init.php files from the structure 
	 * 
	 * @param string $path
	 * @param int $max_depth
	 * @param int $current_depth 
	 * @throws ConfigurationException 
	 */
	public static function loadInitFiles($path, $max_depth, $current_depth = 0) {
		$files = @scandir($path);
		/* make sure we got something from scandir */
		if (!is_array($files)) {
			throw new ConfigurationException('invalid directory provided for autoloader: ' . $path);
		}
		else {
			/* loop through all items in directory */
			foreach($files as $file) {
				/* load init.php files, if any. Use require_once to ensure no dublicates */
				if ($file == 'init.php') { require_once($path . '/init.php'); }
				/* recursively scan child directories, if not max depth yet */
				elseif	(($file <> '.') && ($file <> '..') &&
						(is_dir($fullpath = $path . '/' . $file)) &&
						($current_depth <= $max_depth)) {
					self::loadInitFiles($fullpath, $max_depth, $current_depth + 1);
				}
			}
		}	
	}
	
	/**
	 * Add classes to autoloader knowledge.
	 * Usually should be called from init.php files
	 * @param string $className
	 * @param string $classPath 
	 */	
	public static function registerClass($className, $classPath) {
		if (isset(self::$classes[$className])) { 
			throw new ConfigurationException('Class already registered to autoload array: '. $className); 
		}
		elseif (!file_exists($classPath)) {
			throw new ConfigurationException('Invalid class path given for registration: '. $classPath); 
		}
		self::$classes[$className] = $classPath;
	}
	
	/**
	 * Remove classes from autoloader knowledge
	 * @param string $className 
	 */
	
	public static function deregisterClass($className) {
		if (isset(self::$classes[$className])) { 
			unset(self::$classes[$className]);
		}				
	}
	
	public static function getKey() {		
		$i = 1;
		$data = self::$settings;		
		while($i <= func_num_args()) {
			$arg = func_get_arg($i - 1);			
			if ( (is_array($data)) && (isset($data[$arg])) ) {
				$data = $data[$arg];
				$i++;
			}
			else {
				throw new ConfigurationException('invalid setting key requested');
			}
		}
		return $data;
	}
	
	/**
	 * Returns Database instance (singleton fashion)
	 * @param string $class
	 * @return PDO
	 */
	public static function getDB($class = 'PDO') {		
		if (!(self::$dbObject instanceof PDO)) {
			$reflection = new ReflectionClass($class);
			if (($class <> 'PDO') && (!($reflection->isSubclassOf('PDO')))) {
				throw new ConfigurationException('DB class should extend PDO!');
			}
			else {
				$conn = 'mysql:dbname=' . self::getKey('mysql','db_name') . ';host=' . self::getKey('mysql','host');
				$username = self::getKey('mysql','username');
				$password = self::getKey('mysql','password');
				$options = array(
					PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '" . self::getKey('mysql','charset') . "'",
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
				try {
					self::$dbObject = new $class($conn, $username, $password, $options);
				}
				catch(PDOException $e) {				
					throw new ConfigurationException('PDO instantiation failed');				
				}
			}
		}
		return self::$dbObject;
	}
	
	public static function closeDB() {
		self::$dbObject = null;
	}
}
