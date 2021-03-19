<?php

use Nano\Debug;

/**
 * Database access layer for mySQL
 */
class DatabaseMysql extends Database {

	// @see http://php.net/manual/en/mysqlinfo.concepts.buffering.php
	const RESULT_MODE = MYSQLI_STORE_RESULT;

	// "MySQL server has gone away" error ID
	const ERR_SERVER_HAS_GONE_AWAY = 2006;

	// current connection settings
	private $settings;

	/**
	 * Connect to a database
	 *
	 * @param NanoApp $app
	 * @param array $settings
	 * @param $name
	 * @throws DatabaseException
	 */
	protected function __construct(NanoApp $app, Array $settings, $name) {
		parent::__construct($app, $settings, $name);

		$this->link = mysqli_init();

		// store connection settings
		$this->settings = $settings;

		// TOOD: implement lazy connection on the first query
		$this->doConnect();
	}

	/**
	 * (Re)connect using settings passed to the constructor
	 *
	 * @param bool $reconnect
	 * @throws DatabaseException
	 */
	protected function doConnect($reconnect = true) {
		// reuse connection settings
		$settings = $this->settings;

		// (try to) activate persistent connections
		// @see http://www.php.net/manual/en/mysqli.persistconns.php
		if (!empty($settings['persistent']) && isset($settings['host'])) {
			$settings['host'] = 'p:' . $settings['host'];
		}

		// @see http://www.php.net/manual/en/mysqli.real-connect.php
		$params = array();
		$keys = array('host', 'user', 'pass', 'database', 'port', 'socket', 'flags');

		foreach($keys as $key) {
			if (isset($settings[$key])) {
				$params[] = $settings[$key];
			}
			else {
				break;
			}
		}

		// try to connect
		if (!empty($params)) {
			// set connect timeout
			if (isset($settings['connect_timeout'])) {
				$res = $this->link->options(MYSQLI_OPT_CONNECT_TIMEOUT, $settings['connect_timeout']);

				if ($res === false) {
					throw new DatabaseConnectionException("[{$this->name}] Setting timeout to {$settings['connect_timeout']} sec failed");
				}
			}

			$this->debug->time('connect');
			$res = @call_user_func_array(array($this->link, 'real_connect'), $params);
			$time = $this->debug->timeEnd('connect');

			$hostInfo = $settings['host'] . (isset($settings['port']) ? ":{$settings['port']}" : '');

			if ($res) {
				// add more information to Monolog logs
				$this->logger->pushProcessor(function($record) use ($hostInfo) {
					$record['extra']['database'] = [
						'host' => $hostInfo,
						'name' => $this->name,
					];
					return $record;
				});

				$this->logger->info('Connected', [
					'time' => $time * 1000 // [ms]
				]);

				$this->log(__METHOD__, 'connected with ' . $hostInfo, $time);
				$this->stats->timing('time.connection', round($time * 1000) /* ms */);

				$this->connected = true;

				// set UTF8 as connection encoding
				if (!empty($settings['utf'])) {
					// @see http://php.net/manual/en/mysqli.set-charset.php
					$this->link->set_charset("utf8");
				}
			}
			else {
				$errorNo = $this->link->connect_errno;
				$errorMsg = trim($this->link->connect_error);

				$this->debug->log(__METHOD__ . " - connecting with '{$hostInfo}' failed (#{$errorNo}: {$errorMsg})", Debug::ERROR);

				throw new DatabaseConnectionException( "[{$this->name}] {$errorMsg} ({$hostInfo})", $errorNo);
			}
		}
	}

	/**
	 * Close the current connection
	 */
	public function close() {
		if ($this->isConnected()) {
			$this->link->close();
			$this->connected = false;

			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Ping the current connection
	 */
	public function ping() {
		return $this->isConnected() && $this->link->ping();
	}

	/**
	 * Send given query and return results handler
	 *
	 * @see http://www.php.net/manual/en/mysqli.real-query.php
	 *
	 * @param string $sql
	 * @param string|bool $fname
	 * @throws  DatabaseException
	 * @return DatabaseResult
	 */
	public function query($sql, $fname = false) {
		$this->debug->time('query');

		$res = $this->link->query($sql, self::RESULT_MODE);

		// reconnect and retry the query
		if ($this->link->errno == self::ERR_SERVER_HAS_GONE_AWAY) {
			$this->doConnect(true /* $reconnect*/ );

			$res = $this->link->query($sql, self::RESULT_MODE);
		}

		$time = $this->debug->timeEnd('query');

		// update stats
		$this->queries++;
		$this->queriesTime += $time;

		// log query
		$this->log(__METHOD__, $sql, $time);

		$method = $fname ?: __METHOD__;

		// report shorter SQL to fit syslog JSON-formatted messages length limit
		$shortSql = strlen($sql) > 4096 ? substr($sql, 0, 4096) . ' [...]' : $sql;

		// check for errors
		if (empty($res)) {
			$e = new DatabaseException($this->link->error, $this->link->errno);

			$this->logger->error($shortSql, [
				'exception' => $e,
				'method' => $method,
				'time' => $time * 1000 // [ms]
			]);

			$this->log(__METHOD__, "error #{$this->link->errno} - {$this->link->error}");

			throw $e;
		}
		else {
			$this->logger->info("SQL {$shortSql}", [
				'method' => $method,
				'rows' => $res instanceof mysqli_result ? $res->num_rows : ( $this->link->affected_rows ?: 0 ),
				'time' => $time * 1000 // [ms]
			]);
		}

		$this->setLastQuery($sql);

		// wrap results into iterator
		return new DatabaseResult($this, $res);
	}

	/**
	 * Start a transaction
	 */
	public function begin($fname = 'Database::begin') {
		return $this->query("BEGIN /* {$fname} */", $fname);
	}

	/**
	 * Commit the current transaction
	 */
	public function commit($fname = 'Database::commit') {
		return $this->query("COMMIT /* {$fname} */", $fname);
	}

	/**
	 * Rollback the current transaction
	 */
	public function rollback() {
		return $this->link->rollback();
	}

	/**
	 * Properly encode given string
	 *
	 * @param string $value
	 * @return string
	 */
	public function escape($value) {
		return $this->link->real_escape_string($value);
	}

	/**
	 * Remove rows from a table using following WHERE statements
	 *
	 * @see http://dev.mysql.com/doc/refman/5.0/en/delete.html
	 */
	public function delete($table, $where = array(), Array $options = array(), $fname = 'Database::delete') {
		$sql = "DELETE /* {$fname} */ FROM " . $this->resolveList($table);

		$whereSql = $this->resolveWhere($where);
		if (!empty($whereSql)) {
			$sql .= ' WHERE ' . $whereSql;
		}

		$optionsSql = $this->resolveOptions($options);
		if (!empty($optionsSql)) {
			$sql .= ' ' . $optionsSql;
		}

		return $this->query($sql, $fname);
	}

	/**
	 * Remove single row from a table using following WHERE statements
	 */
	public function deleteRow($table, $where = array(), $fname = 'Database::deleteRow') {
		return $this->delete($table, $where, array('limit' => 1));
	}

	/**
	 * Update a table using following values for rows matching WHERE statements
	 *
	 * @see http://dev.mysql.com/doc/refman/5.5/en/update.html
	 */
	public function update($table, Array $values, $where = array(), Array $options = array(), $fname = 'Database::update') {
		$sql = "UPDATE /* {$fname} */ " . $this->resolveList($table) . ' SET ' . $this->resolveSet($values);

		$whereSql = $this->resolveWhere($where);
		if (!empty($whereSql)) {
			$sql .= ' WHERE ' . $whereSql;
		}

		$optionsSql = $this->resolveOptions($options);
		if (!empty($optionsSql)) {
			$sql .= ' ' . $optionsSql;
		}

		return $this->query($sql, $fname);
	}

	/**
	 * Insert a single row into a table using following values
	 *
	 * @see http://dev.mysql.com/doc/refman/5.5/en/insert.html
	 */
	public function insert($table, Array $row, Array $options = array(), $fname = 'Database::insert') {
		return $this->insertRows($table, array($row), $options, $fname);
	}

	/**
	 * Insert multiple rows into a table using following values
	 */
	public function insertRows($table, Array $rows, Array $options = array(), $fname = 'Database::insertRows') {
		$fields = implode('`,`', array_keys(reset($rows)));

		$values = array();

		foreach($rows as $row) {
			$data = array_values($row);
			$data = array_map(array($this, 'escape'), $data);
			$values[] = '("' . implode('","', $data) . '")';
		}

		$values = implode(',', $values);

		// parse options
		$suffix = '';

		if (!empty($options['ON DUPLICATE KEY UPDATE'])) {
			list($column, $value) = $options['ON DUPLICATE KEY UPDATE'];
			$suffix .= sprintf(' ON DUPLICATE KEY UPDATE %s = "%s"', $column, $this->escape($value));
		}

		$sql = "INSERT INTO /* {$fname} */ {$table} (`{$fields}`) VALUES {$values}{$suffix}";

		return $this->query($sql, $fname);
	}

	/**
	 * REPLACE works exactly like INSERT, except that if an old row in the table has the same value as a new row
	 * for a PRIMARY KEY or a UNIQUE index, the old row is deleted before the new row is inserted.
	 *
	 * REPLACE is a MySQL extension to the SQL standard.
	 *
	 * @see https://dev.mysql.com/doc/refman/5.7/en/replace.html
	 *
	 * @param $table
	 * @param $row
	 * @param array $options
	 * @param string $fname
	 * @return DatabaseResult
	 */
	public function replace($table, array $row, array $options = [], $fname = 'Database::replace') {
		$fields = '`'. implode('`,`', array_keys($row)) . '`';

		$data = array_values($row);
		$data = array_map([$this, 'escape'], $data);
		$values = '("' . implode('","', $data) . '")';

		// REPLACE INTO test (id, value, timestamp) VALUES (1, 'Old', '2014-08-20 18:47:00');
		$sql = "REPLACE INTO /* {$fname} */ {$table} ({$fields}) VALUES {$values}";

		return $this->query($sql, $fname);
	}

	/**
	 * Get primary key value for recently inserted row
	 */
	public function getInsertId() {
		return !empty($this->link) ? $this->link->insert_id : 0;
	}

	/**
	 * Get number of rows affected by the recent query
	 */
	public function getRowsAffected() {
		return $this->link->affected_rows;
	}

	/**
	 * Get number of rows in given results set
	 * @param mysqli_result $results
	 * @return int
	 */
	public function numRows($results) {
		return $results->num_rows;
	}

	/**
	 * Change the position of results cursor
	 * @param mysqli_result $results
	 * @param int $rowId
	 */
	public function seekRow($results, $rowId) {
		$results->data_seek($rowId);
	}

	/**
	 * Get data for current row
	 *
	 * @param mysqli_result $results
	 * @return mixed|false
	 */
	public function fetchRow($results) {
		$row = $results->fetch_assoc();

		return !is_null($row) ? $row : false;
	}

	/**
	 * Free the memory
	 *
	 * @param mysqli_result $results
	 */
	public function freeResults($results) {
		$results->free_result();
	}

	/**
	 * Get information about current connection
	 */
	public function getInfo() {
		return $this->isConnected() ? $this->link->server_info : '';
	}

	/**
	 * Return true if currently connected to the database
	 */
	public function isConnected() {
		return $this->connected == true;
	}
}
