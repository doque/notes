<?php

class MySQL {
	/**
	* holds the connection to the mysql server
	* @var ressource
	*/
	private $connection;

	/**
	* stores the last perform mysql query result
	* @var ressource
	*/
	private $result;

	/**
	* defines whether or whether not exceptions should be catched internally. if true, the script execution will be exited and the error printed, if not, you have to catch exceptions manually and handle them
	* @var boolean
	*/
	public $catchExceptions = true;

	/**
	* holds the time used for queries
	* @see MySQL::getTime()
	* @var float
	*/
	protected $time = 0;

	/**
	* holds the count of how many queries were perform
	* @see MySQL::getQueries()
	* @var integer
	*/
	protected $queries = 0;


	/**
	* takes the mysql connection details to instantiate the object and connect to the database server
	*
	* @param string $host the host of the mysql server
	* @param string $username the mysql username to use
	* @param string $password the mysql password
	* @param string $database the name of the mysql database to use
	* @throws MySQLException
	*/
	public function __construct($host, $username, $password, $database) {
		$this->connect($host, $username, $password);
		$this->selectDB($database);
	}

	/**
	* Get number of affected rows in previous MySQL operation
	*
	* @return integer Returns the number of affected rows on success, and -1 if the last query failed. 
	*/
	public function affectedRows() {
		return mysql_affected_rows($this->connection);
	}

	public function buildFieldBits($data) {
		$fields = array();
		foreach($data AS $field => $value) {
			$pieces = explode(':', $field);
			$field  = array_pop($pieces);
			$type   = array_pop($pieces);

			try {
				switch(strtolower($type)) {
					case 'str':
						$fields[$field] = "'" . $this->escapeString($value) . "'";
						break;
					case 'int':
						$fields[$field] = intval($value);
						break;
					case 'null':
						$fields[$field] = 'NULL';
						break;
					case 'dec':
						$fields[$field] = round((float) $value, 2);
						break;
					default:
						throw new MySQLException($this, 'MySQL::buildFieldBits() - invalid type given ('.$type.')');
				}
			} catch(MySQLException $e) {
				if($this->catchExceptions) die($e->__toString());
				throw $e;
			}

			// apply additional mysql functions
			if(!empty($pieces)) {
				foreach($pieces AS $func) {
					$fields[$field] = strtoupper($func) . '(' . $fields[$field] . ')';
				}
			}
		}

		$queryStrings = array();
		foreach($fields AS $field => $value) {
			array_push($queryStrings, '`'.$field.'` = '.$value);
		}
		return implode(', ', $queryString);
	}

	/**
	* Fetch a result row as an associative array
	*
	* @param ressource $sql optional. If omitted the last performed query result is used
	* @return array|boolean Returns an associative array of strings that corresponds to the fetched row, or FALSE if there are no more rows.
	*/
	public function fetchAssoc($sql = false) {
		if($sql !== false) {
			$this->result = $sql;
		}
		return mysql_fetch_assoc($this->result);
	}

	/**
	* tells the amount of sent queries
	*
	* @return integer the number of total mysql queries that were sent
	*/
	public function getQueries() {
		return $this->queries;
	}

	/**
	* tells the time used to perform queries
	*
	* @return float the time in seconds which took all mysql operations.
	*/
	public function getTime() {
		return $this->time;
	}

	/**
	* Get the ID generated from the previous INSERT operation
	*
	* @return integer The ID generated for an AUTO_INCREMENT column by the previous INSERT query on success or 0 if the previous query does not generate an AUTO_INCREMENT value
	*/
	public function insertID() {
		return mysql_insert_id();
	}

	/**
	* Get number of rows in result
	*
	* @param ressource $sql Optional. If omitted the last performed query result is used.
	* @return boolean|integer  The number of rows in a result set on success, or FALSE on failure. 
	*/
	public function numRows($sql = false) {
		if($sql !== false) {
			$this->result = $sql;
		}
		return mysql_num_rows($this->result);
	}

	/**
	* Secure and send a MySQL query using the sprintf {@link sprintf} syntax while all parameters will automatically be secured with the fitting functions (intval, float, mysql_real_escape_string)
	*
	* @param string $query The mysql query to send
	* @return boolean|ressource For SELECT, SHOW, DESCRIBE, EXPLAIN and other statements returning resultset, this method returns a resource on success, or FALSE on error.  For other type of SQL statements, UPDATE, DELETE, DROP, etc, this method returns TRUE on success or FALSE on error.
	* @throws MySQLException
	*/
	public function query($query) {
		$args = func_get_args();
		array_shift($args);
		$query = $this->buildQuery($query, $args);

		try {
			$start = microtime(true);
			$this->result = @mysql_query($query, $this->connection);
			$this->time += microtime(true) - $start;
			$this->queries++;

			if($this->result === false) {
				throw new MySQLException($this, 'Invalid SQL', $query);
			}
		} catch(MySQLException $e) {
			if($this->catchExceptions) die($e->__toString());
			throw $e;
		}

		return $this->result;
	}

	/**
	* Secure, send and get the first result set of a mysql query as an associative array. Same usage as MySQL::query()
	* 
	* @see Mysql::query()
	* @return array|boolean Returns an associative array of strings that corresponds to the first fetched row, or FALSE if there are no more rows.
	* @throws MySQLException
	*/
	public function queryFirst($query) {
		$args = func_get_args();
		$result = call_user_func_array(array(get_class($this), 'query'), $args);

		return $this->fetchAssoc();
	}

	/**
	* Secure and send an SQL query to MySQL, without fetching and buffering the result rows. Same usage as Mysql::query()
	*
	* @see Mysql::query()
	* @param string $query The mysql query to send
	* @return boolean|ressource For SELECT, SHOW, DESCRIBE, EXPLAIN and other statements returning resultset, this method returns a resource on success, or FALSE on error.  For other type of SQL statements, UPDATE, DELETE, DROP, etc, this method returns TRUE on success or FALSE on error.
	* @throws MySQLException
	*/
	public function unbufferedQuery($query) {
		$args = func_get_args();
		array_shift($args);
		$query = $this->buildQuery($query, $args);

		try {
			$start = microtime(true);
			$this->result = @mysql_unbuffered_query($query, $this->connection);
			$this->time += microtime(true) - $start;
			$this->queries++;

			if($this->result === false) {
				throw new MySQLException($this, 'Invalid SQL', $query);
			}
		} catch(MySQLException $e) {
			if($this->catchExceptions) die($e->__toString());
			throw $e;
		}

		return $this->result;
	}


/*
	* internal functions following
	*/

	private function buildQuery($query, $values) {
		foreach($values AS $key => $value) {
			if(ctype_digit($value)) {
				$values[$key] = intval($values[$key]);
			}
			elseif(is_numeric($value)) {
				$values[$key] = (float) $values[$key];
			}
			else {
				$values[$key] = $this->escapeString($values[$key]);
			}
		}
		try {
			$query = @vsprintf($query, $values);
			if($query === false) {
				throw new MySQLException($this, 'MySQL::buildQuery() - too few arguments for this query', $query);
			}
		} catch(MySQLException $e) {
			if($this->catchExceptions) die($e->__toString());
			throw $e;
		}

		return $query;
	}

	private function connect($host, $username, $password) {
		try {
			$this->connection = @mysql_connect($host, $username, $password);
			if($this->connection === false) {
				throw new MySQLException($this, 'error connecting to the database server');
			}
		} catch(MySQLException $e) {
			if($this->catchExceptions) die($e->__toString());
			throw $e;
		}
	}

	private function escapeString($str) {
		return mysql_real_escape_string($str, $this->connection);
	}

	public function getError() {
		if(!($errstr = @mysql_error($this->connection))) {
			$errstr = @mysql_error();
		}
		return $errstr;
	}

	public function getErrno() {
		if(!($errno = @mysql_errno($this->connection))) {
			$errno = @mysql_errno();
		}
		return $errno;
	}

	private function ping() {
		while(false === @mysql_ping($this->connection));
	}

	private function selectDB($database) {
		try {
			$result = @mysql_select_db($database, $this->connection);
			if($result === false) throw new MySQLException($this, 'error selecting database');
		} catch(MySQLException $e) {
			if($this->catchExceptions) die($e->__toString());
			throw $e;
		}
	}
}

class MySQLException extends Exception {
	protected $object;
	protected $prosa;
	protected $query;

	public function __construct(&$obj, $prosa = null, $query = null) {
		$this->object  = $obj;
		$this->message = $obj->getError();
		$this->code    = $obj->getErrno();
		$this->prosa   = $prosa;
		$this->query   = $query;
	}

	public function __toString() {
		$message  = '<pre>';
		$message .= 'MySQL Database error: ' . $this->getProsa() . "\n";
		$message .= 'MySQL Error: ' . $this->object->getError() . "\n";
		$message .= 'Error Number: ' . $this->object->getErrno() . "\n";
		if(null !== ($query = $this->getQuery())) $message .= 'Query: ' . $query . "\n";
		$message .= "\n".'Stacktrace: ' . "\n" . $this->getDefusedTraceAsString() . '</pre>';

		return $message;
	}

	public function getObject() {
		return $this->object;
	}

	public function getProsa() {
		return $this->prosa;
	}

	public function getQuery() {
		return $this->query;
	}

	public function getDefusedTraceAsString() {
		$str = parent::getTraceAsString();
		if(false !== strpos($str, '__construct(')) {
			$str = preg_replace('/MySQL->__construct\(([^\)]*)\)/', 'MySQL->__construct(******, ******, ******, ******)', $str);
		}
		return $str;
	}

	public function setObject(&$obj) {
		$this->object = $obj;
	}
}

?>

