<?php

/**
 * Database access layer
 */

use Nano\Debug;
use Nano\Logger\NanoLogger;

/**
 * Generic exception
 */
class DatabaseException extends Exception {}
class DatabaseConnectionException extends DatabaseException {}

/**
 * Class Database
 */
abstract class Database {

	// debug
	protected $debug;

	protected $logger;

	// connection resource
	protected $link;

	// indicates that connection was successfully established
	protected $connected = false;

	// number of queries
	protected $queries = 0;

	// total time of queries (in seconds)
	protected $queriesTime = 0;

	// connection's name (either config entry name or DB driver's name)
	protected $name;

	// stats collector
	protected $stats;

	// already created connections
	static private $connectionsPoll = array();

	/**
	 * Force constructors to be protected - use Database::connect
	 */
	protected function __construct(NanoApp $app, Array $settings, $name) {
		// use debugger from the application
		$this->debug = $app->getDebug();
		$this->setName($name);

		$this->logger = NanoLogger::getLogger('nano.database.' . $name);

		// add performance report
		$events = $app->getEvents();
		$events->bind('NanoAppTearDown', array($this, 'onNanoAppTearDown'));

		$this->stats = \Nano\Stats::getCollector($app, "database.{$name}");
	}

	/**
	 * Connect to a given database
	 *
	 * @param NanoApp $app application instance
	 * @param mixed $config config array or database config entry name to use to connect
	 * @return Database instance of database model
	 * @throws DatabaseException
	 */
	public static function connect(NanoApp $app, $config = 'default') {
		// try to reuse already created connection (when getting database by name)
		if (is_string($config) && isset(self::$connectionsPoll[$config])) {
			return self::$connectionsPoll[$config];
		}

		// get settings from app config
		$settings = is_string($config) ? $app->getConfig()->get("db.{$config}") : $config;

		$driver = (is_array($settings) && isset($settings['driver'])) ? $settings['driver'] : null;
		$instance = null;

		$debug = $app->getDebug();
		$logger = NanoLogger::getLogger('nano.database');

		if (!is_null($driver)) {
			$className = 'Database' . ucfirst(strtolower($driver));

			$src = dirname(__FILE__) . '/database/' . $className . '.class.php';

			if (file_exists($src)) {
				require_once $src;

				//$debug->log(__METHOD__ . ' - connecting using "' . $driver . '" driver');

				try {
					$name = is_string($config) ? $config : $driver;
					$instance = new $className($app, $settings, $name);
				}
				catch(DatabaseException $e) {
					$logger->error($e->getMessage(), [
						'exception' => $e,
						'driver' => $className
					]);
					throw $e;
				}
			}
		}
		else {
			$debug->log(__METHOD__ . ' - no driver specified', Debug::ERROR);
		}

		// cache it
		if (is_string($config) && !is_null($instance)) {
			self::$connectionsPoll[$config] = $instance;
		}

		return $instance;
	}

	/**
	 * Debug logging helper
	 *
	 * @param $method
	 * @param $msg
	 * @param string|bool $time
	 */
	protected function log($method, $msg, $time = false) {
		$msg =  "{$method} [{$this->name}] - {$msg}";

		if (is_numeric($time)) {
			$time = round($time, 3);
			$msg .= " [{$time} s]";
		}

		$this->debug->log($msg);
	}

	/**
	 * Set's connection name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Close the current connection
	 */
	abstract public function close();

	/**
	 * Ping the current connection
	 */
	abstract public function ping();

	/**
	 * Send given query and return results handler
	 * @return DatabaseResult
	 */
	abstract public function query($sql, $fname = false);

	/**
	 * Start a transaction
	 */
	abstract public function begin($fname = 'Database::begin');

	/**
	 * Commit the current transaction
	 */
	abstract public function commit($fname = 'Database::commit');

	/**
	 * Rollback the current transaction
	 */
	abstract public function rollback();

	/**
	 * Properly encode given string
	 */
	abstract public function escape($value);

	/**
	 * Select given fields from a table using following WHERE statements
	 *
	 * @see http://dev.mysql.com/doc/refman/5.0/en/select.html
	 * @return DatabaseResult
	 */
	public function select($table, $fields, $where = array(), Array $options = array(), $fname = 'Database::select') {
		$sql = "SELECT /* {$fname} */ " . $this->resolveList($fields) . ' FROM ' . $this->resolveList($table);

		// JOINS
		$joinsSql = $this->resolveJoins($options);
		if (!empty($joinsSql)) {
			$sql .= ' ' . $joinsSql;
		}

		// WHERE part
		$whereSql = $this->resolveWhere($where);
		if (!empty($whereSql)) {
			$sql .= ' WHERE ' . $whereSql;
		}

		// variuos options part
		$optionsSql = $this->resolveOptions($options);
		if (!empty($optionsSql)) {
			$sql .= ' ' . $optionsSql;
		}

		return $this->query($sql, $fname);
	}

	/**
	 * Select given fields from a table using following WHERE statements (return single row)
	 */
	public function selectRow($table, $fields, $where = array(), Array $options = array(), $fname = 'Database::selectRow') {
		$options['limit'] = 1;
		$res = $this->select($table, $fields, $where, $options, $fname);

		if (!empty($res)) {
			$ret = $res->fetchRow();

			$res->free();
			return $ret;
		}
		else {
			return false;
		}
	}

	/**
	 * Select given fields from a table using following WHERE statements (return single field)
	 */
	public function selectField($table, $field, $where = array(), Array $options = array(), $fname = 'Database::selectField') {
		$row = $this->selectRow($table, $field, $where, $options, $fname);

		return !empty($row) ? reset($row) : false;
	}

	/**
	 * Select given field from a table using following WHERE statements (return a set of rows)
	 */
	public function selectFields($table, $field, $where = array(), Array $options = array(), $fname = 'Database::selectFields') {
		$res = $this->select($table, $field, $where, $options, $fname);

		$fields = array();
		foreach($res as $row) {
			$fields[] = reset($row);
		}

		return !empty($fields) ? $fields : false;
	}

	/**
	 * Remove rows from a table using following WHERE statements
	 */
	abstract public function delete($table, $where = array(), Array $options = array(), $fname = 'Database::delete');

	/**
	 * Remove single row from a table using following WHERE statements
	 */
	abstract public function deleteRow($table, $where = array(), $fname = 'Database::deleteRow');

	/**
	 * Update a table using following values for rows matching WHERE statements
	 */
	abstract public function update($table, Array $values, $where = array(), Array $options = array(), $fname = 'Database::update');

	/**
	 * Insert a single row into a table using following values
	 */
	abstract public function insert($table, Array $row, Array $options = array(), $fname = 'Database::insert');

	/**
	 * Insert multiple rows into a table using following values
	 */
	abstract public function insertRows($table, Array $rows, Array $options = array(), $fname = 'Database::insertRows');

	/**
	 * Get primary key value for recently inserted row
	 */
	abstract public function getInsertId();

	/**
	 * Get number of rows affected by the recent query
	 */
	abstract public function getRowsAffected();

	/**
	 * Get number of rows in given results set
	 */
	abstract public function numRows($results);

	/**
	 * Change the position of results cursor
	 */
	abstract public function seekRow($results, $rowId);

	/**
	 * Get data for current row
	 */
	abstract public function fetchRow($results);

	/**
	 * Free the memory
	 */
	abstract public function freeResults($results);

	/**
	 * Get information about current connection
	 */
	abstract public function getInfo();

	/**
	 * Return true if currently connected to the database
	 */
	abstract public function isConnected();

	/**
	 * Prepare SQL by replacing placeholders with given set of values
	 */
	public function prepareSQL($sql, Array $values) {
		$replacements = array();

		foreach($values as $key => $value) {
			$replacements['%' . $key . '%'] = $this->escape($value);
		}

		$sql = strtr($sql, $replacements);

		return $sql;
	}

	/**
	 * Return part of SQL for given list of values
	 */
	public function resolveList($values) {
		if (is_array($values)) {
			$sql = implode(',', $values);
		}
		else {
			$sql = $values;
		}

		return $sql;
	}

	/**
	 * Return part of SQL for given set of join options
	 *
	 * http://svn.wikimedia.org/doc/classDatabaseBase.html#a6ca54fb2c2c1713604b8b77a4fa7b318
	 */
	public function resolveJoins(Array &$options) {
		$sql = false;

		if (isset($options['joins']) && is_array($options['joins'])) {
			$sqlParts = array();

			foreach($options['joins'] as $table => $params) {
				// LEFT JOIN table ON foo = bar
				$sqlParts[] = "{$params[0]} {$table} ON {$params[1]}";
			}

			$sql = implode(' ', $sqlParts);

			unset($options['joins']);
		}

		return $sql;
	}

	/**
	 * Return part of SQL for given WHERE statements
	 */
	public function resolveWhere($where) {
		if (is_string($where)) {
			$sql = $where;
		}
		else if (is_array($where)) {
			$sqlParts = array();

			foreach($where as $field => $cond) {
				if (is_numeric($field)) {
					$sqlParts[] = $cond;
				}
				else if (is_array($cond)) {
					$cond = array_map(array($this, 'escape'), $cond);
					$sqlParts[] = $field . ' IN ("' . implode('","', $cond) . '")';
				}
				else {
					$sqlParts[] = $field . '="' . $this->escape($cond) . '"';
				}
			}

			$sql = implode(' AND ', $sqlParts);
		}
		else {
			$sql = false;
		}

		return $sql;
	}

	/**
	 * Return part of SQL for given SET statements
	 *
	 * Used to form UPDATE queries
	 */
	public function resolveSet(Array $values) {
		$set = array();

		foreach($values as $col => $value) {
			if (is_numeric($col)) {
				$set[] = $value;
			}
			else {
				$value = $this->escape($value);
				$set[] = "{$col}=\"{$value}\"";
			}
		}

		$sql = implode(',', $set);

		return $sql;
	}

	/**
	 * Return part of SQL for given ORDER BY conditions
	 */
	public function resolveOrderBy($orderBy) {
		if (is_string($orderBy)) {
			$sql = $orderBy;
		}
		else if (is_array($orderBy)) {
			$sqlParts = array();

			foreach($orderBy as $field => $cond) {
				if (is_numeric($field)) {
					$sqlParts[] = $cond;
				}
				else {
					$sqlParts[] = $field . ' ' . $cond;
				}
			}

			$sql = implode(',', $sqlParts);
		}
		else {
			$sql = false;
		}

		return $sql;
	}

	/**
	 * Return part of SQL for given set of options
	 */
	public function resolveOptions(Array $options) {
		$sqlParts = array();

		if (isset($options['group'])) {
			$sqlParts[] = 'GROUP BY ' . $this->escape($options['group']);
		}

		if (isset($options['order'])) {
			$sqlParts[] = 'ORDER BY ' . $this->resolveOrderBy($options['order']);
		}

		if (isset($options['limit'])) {
			$sqlParts[] = 'LIMIT ' . intval($options['limit']);
		}

		if (isset($options['offset'])) {
			$sqlParts[] = 'OFFSET ' . intval($options['offset']);
		}

		// parse options it they were provided as a simple list
		foreach($options as $key => $opt) {
			if (is_numeric($key)) {
				$sqlParts[] = $opt;
			}
		}

		return implode(' ', $sqlParts);
	}

	/**
	 * Return performance data
	 */
	public function getPerformanceData() {
		return array(
			'queries' => $this->queries,
			'time' => round($this->queriesTime * 1000), // [ms]
		);
	}

	/**
	 * Add performance report to the log
	 */
	public function onNanoAppTearDown(NanoApp $app) {
		$debug = $app->getDebug();
		$request = $app->getRequest();

		// don't report stats for command line script
		if ($request->isCLI()) {
			return;
		}

		$perf = $this->getPerformanceData();

		$debug->log("Database [{$this->name}]: {$perf['queries']} queries in {$perf['time']} ms");
		$this->logger->info('Performance data', $perf);

		// send stats
		$this->stats->count('queries.count', $perf['queries']);
		$this->stats->timing('time.total', $perf['time'] /* ms */);
	}
}