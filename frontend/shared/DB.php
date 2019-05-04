<?php

/**
 * Custom PDO wrapper (nothing fancy, just shorthand methods to write SQL. No ORM-like features of anything.
 */

class DB extends PDO {
	
/* GENERIC WRAPPER */
	
	/** @return PDOStatement */
	public function getPDOStatement($sql_to_prepare, $exec_params = array()) {
		$q = $this->prepare($sql_to_prepare);
		$q->execute($exec_params) or $this->throwError($q);
		return $q;
	}
	
/* SELECT WRAPPERS */
	
	/** @return array[] */
	public function getArray($sql_to_prepare, array $exec_params = array()) {		
		$q = $this->getPDOStatement($sql_to_prepare, $exec_params);
		return $q->fetchAll(PDO::FETCH_ASSOC);
	}
	
	/** @return array[] */
	public function getObjectArray($className, $sql_to_prepare, array $exec_params = array(), $constr_args = array()) {		
		$q = $this->getPDOStatement($sql_to_prepare, $exec_params);
		return $q->fetchAll(PDO::FETCH_CLASS, $className, $constr_args);
	}
	
	/** @return StdClass */
	public function getObject($className, $sql_to_prepare, array $exec_params = array(), $constr_args = array()) {		
		$q = $this->getPDOStatement($sql_to_prepare, $exec_params);
		return $q->fetch(PDO::FETCH_CLASS, $className, $constr_args);
	}
	
	/** @return StdClass */
	public function updateObject(stdClass $obj, $sql_to_prepare, array $exec_params = array()) {		
		$q = $this->getPDOStatement($sql_to_prepare, $exec_params);
		return $q->fetch(PDO::FETCH_INTO, $obj);
	}
	
	/** @return array */
	public function getRow($sql_to_prepare, $exec_params = array()) {
		$q = $this->getPDOStatement($sql_to_prepare, $exec_params);
		return $q->fetch();
	}
	
	/** @return array */
	public function getCol($sql_to_prepare, $exec_params) {
		$q = $this->getPDOStatement($sql_to_prepare, $exec_params);		
		return $q->fetchAll(PDO::FETCH_COLUMN);
	}
	
	/** @return string */
	public function getVar($sql_to_prepare, $exec_params = array()) {
		$row = $this->getRow($sql_to_prepare, $exec_params);
		return $row[0];
	}
	
/* INSERT WRAPPERS */
	
	/** @return PDOStatement */
	public function insert($table, array $data) {		
		return $this->genericInsert($table, $data, 'insert', array());
	}
	
	/** @return PDOStatement */
	public function insertUpdate($table, array $data, array $excluded_keys = array()) {		
		return $this->genericInsert($table, $data, 'insertUpdate', $excluded_keys);
	}
	
/* UPDATE WRAPPERS */
	
	/** @return PDOStatement */
	public function update($table, array $data, array $conditions = array()) {
		/* get prepared statement */
		$q = $this->prepareUpdate($table, array_keys($data), array_keys($conditions));
		/* bind values */
		foreach($data as $key => $value) { $q->bindValue(':k_' .$key, $value); }
		foreach($conditions as $key => $value) { $q->bindValue(':c_' .$key, $value); }
		/* execute */
		$q->execute() or $this->throwError($q);
		return $q;
	}
	
/* OTHER WRAPPERS */
	
	public function createTrigger($name, $trigger) {		
		$exists = $this->getVar('SHOW TRIGGERS WHERE `trigger` = ?', array($name));
		if (!$exists) {
			$statement = "CREATE TRIGGER `$name` " . $trigger;
			$this->getPDOStatement($statement);
		}
	}
	
/* HELPERS */
	
	/** Executes INSERT or INSERT .. ON DUPLICATE KEY UPDATE statements
	 * @return PDOStatement */
	protected function genericInsert($table, $data, $type, $excluded_keys, PDOStatement $q = null) {				
		/* if multi-row data given, initiate recursive insertion */
		if ((is_array($data)) && (isset($data[0])) && (is_array($data[0]))) {
			foreach ($data as $row) {
				$q = $this->genericInsert($table, $row, $type, $excluded_keys, $q);
			}
			return $q;
		}
		/* proceed with one-line row insert */
		else {
			/* get prepared statement, if needed */
			if ($q === null) { 
				if ($type == 'insertUpdate') { $q = $this->prepareInsertUpdate($table, array_keys($data), $excluded_keys); }
				elseif ($type == 'insert') { $q = $this->prepareInsert($table, array_keys($data)); }
				else throw new PDOException('Unknown insert type provided');
			}								
			/* bind the values of this row */
			foreach ($data as $key => $value) {			
				$q->bindValue(':' . $key, $value);
			}		
			/* execute */
			$q->execute() or $this->throwError($q);
			return $q;
		}
	}
	
	/** Returns a prepared statement for INSERT statement
	 * @return PDOStatement */
	protected function prepareInsert($table, array $keys) {
		list($keys, $placeholders) = $this->getPlaceholders($keys);
		return $this->prepare("INSERT INTO `$table` $keys VALUES $placeholders");
	}
	
	/** Returns a prepared statement for INSERT or INSERT ON DUPLICATE KEY UPDATE
	 * @return PDOStatement */
	protected function prepareInsertUpdate($table, array $keys, array $excluded_keys) {
		$update_fields = '';		
		list($keys, $placeholders, $update_fields) = $this->getPlaceholders($keys, $excluded_keys);			
		if (empty($update_fields)) { return $this->prepare("INSERT INTO `$table` $keys VALUES $placeholders"); }
		else return $this->prepare("INSERT INTO `$table` $keys VALUES $placeholders ' ON DUPLICATE KEY UPDATE ' . $update_fields");
	}
	
	/** Returns a prepared statement for UPDATE
	 * @return PDOStatement */
	protected function prepareUpdate($table, array $keys, array $condition_keys) {
		$set_statements = array();	
		$cond_statements = array();
		/* generate key placeholders */
		foreach($keys as $key) {
			$set_statements[] = "`$key` = :k_$key";
		}
		/* generate condition placeholders */
		foreach($condition_keys as $key) {
			$cond_statements[] = "`$key` = :c_$key";
		}
		$sql = "UPDATE `$table` SET " . implode(", ", $set_statements);
		if (!empty($cond_statements)) $sql .= ' WHERE ' . implode(" AND ", $cond_statements);                
		return $this->prepare($sql);
	}
	
	/**
	 * returns portions of INSERT statement: key list, value list, and if needed - duplicate update list
	 * @param array $keys (All table columns to be inserted)
	 * @param array $excluded_keys (columns that should not be updated in case of duplicate key)
	 * @return array 
	 */	 
	
	protected function getPlaceholders(array $keys, array $excluded_keys = array()) {
		$key_brackets = '(`' . implode('`, `', $keys) . '`)';
		$pl_brackets = '(:' . implode(', :', $keys) . ')';
		if (func_num_args() == 1) { return array($key_brackets, $pl_brackets); }
		else {
			$update_fields = array();
			foreach (array_diff($keys, $excluded_keys) as $key) {				
				$update_fields[] = "`$key` = VALUES(`$key`)";
			}
			$update_fields = implode(', ', $update_fields);
			return array($key_brackets, $pl_brackets, $update_fields);
		}		
	}
	
	/** throws an error */
	protected function throwError($handler) {
		$error = $handler->errorInfo();
		throw new PDOException($error[2]);
	}
}


?>
