<?php

/**
 * Database access layer for MongoDB
 */

class DatabaseMongo extends Database {

	private $db;

	/**
	 * Connect to a database
	 */
	protected function __construct(NanoApp $app, Array $settings, $name) {
		parent::__construct($app, $settings, $name);

		// mongodb://[username:password@]host1[:port1][,host2[:port2:],...]/db
		$dsn = sprintf('mongodb://%s:%d/%s',
			$settings['host'],
			isset($settings['port']) ? $settings['port']: Mongo::DEFAULT_PORT,
			$settings['database']
		);
		$this->log(__METHOD__, 'connecting with "' . $dsn . '"...');

		// // @see http://php.net/manual/en/mongo.construct.php
		$this->link = new Mongo($dsn, array(
			'username' => $settings['user'],
			'password' => $settings['pass'],
		));

		$this->db = $this->link->selectDB($settings['database']);

		$this->log(__METHOD__, 'connected');

		// @see http://learnmongo.com/posts/mongodb-and-64-bit-php/
		ini_set('mongo.native_long', 1);
	}

	/**
	 * Start benchmarking current query
	 */
	protected function time() {
		$this->debug->time('query');
	}

	/**
	 * Finish benchmarking current query and update the stats
	 */
	protected function timeEnd() {
		$time = $this->debug->timeEnd('query');
		$this->queries++;
		$this->queriesTime += $time;
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
	public function ping() {}

	/**
	 * Send given query and return results handler
	 *
	 * @see http://www.php.net/manual/en/mysqli.real-query.php
	 */
	public function query($sql) {}

	/**
	 * Start a transaction
	 */
	public function begin($fname = 'Database::begin') {}

	/**
	 * Commit the current transaction
	 */
	public function commit($fname = 'Database::commit') {}

	/**
	 * Rollback the current transaction
	 */
	public function rollback() {}

	/**
	 * Properly encode given string
	 */
	public function escape($value) {}

	/**
	 * Select given fields from a collection using following WHERE statements
	 *
	 * @see http://php.net/manual/en/mongocollection.find.php
	 */
	public function select($table, $fields, $where = array(), Array $options = array(), $fname = 'Database::select') {
		$this->log(__METHOD__, "/* {$fname} */ {$table}: SELECT WHERE " . json_encode($where));

		if ($fields === '*') {
			$fields = array();
		}

		// The array is in the format array('fieldname' => true, 'fieldname2' => true)
		if (!empty($fields)) {
			$fields = array_combine(array_values($fields), array_fill(0, count($fields), true));
		}

		$this->time();
		$cursor = $this->db->selectCollection($table)->find($where, $fields);
		$this->timeEnd();

		// @see http://php.net/manual/en/class.mongocursor.php
		if (isset($options['order'])) {
			$cursor->sort($options['order']);
		}

		if (isset($options['limit'])) {
			$cursor->limit(intval($options['limit']));
		}

		//return $cursor;

		// wrap results into iterator
		return new DatabaseResult($this, $cursor);
	}

	/**
	 * Select given fields from a table using following WHERE statements (return single row)
	 */
	public function selectRow($table, $fields, $where = array(), Array $options = array(), $fname = 'Database::selectRow') {
		$options['limit'] = 1;
		$res = $this->select($table, $fields, $where, $options, $fname);

		if (!empty($res)) {
			$row = $res->fetchRow();
			$res->free();

			return $row;
		}
		else {
			return false;
		}
	}

	/**
	 * Select given fields from a table using following WHERE statements (return single field)
	 */
	public function selectField($table, $field, $where = array(), Array $options = array(), $fname = 'Database::selectField') {
		$row = $this->selectRow($table, array($field), $where, $options, $fname);

		return !empty($row) && isset($row[$field]) ? $row[$field] : false;
	}

	/**
	 * Remove rows from a table using following WHERE statements
	 */
	public function delete($table, $where = array(), Array $options = array(), $fname = 'Database::delete') {
		$this->log(__METHOD__, "/* {$fname} */ {$table}: REMOVE WHERE " . json_encode($where));

		// @see http://php.net/manual/en/mongocollection.remove.php
		$this->time();
		$this->db->selectCollection($table)->remove($where, $options);
		$this->timeEnd();
	}

	/**
	 * Remove single row from a table using following WHERE statements
	 */
	public function deleteRow($table, $where = array(), $fname = 'Database::deleteRow') {
		return $this->delete($table, $where, array(
			'justOne' => true // LIMIT 1
		), $fname);
	}

	/**
	 * Update a table using following values for rows matching WHERE statements
	 *
	 * @see http://dev.mysql.com/doc/refman/5.5/en/update.html
	 */
	public function update($table, Array $values, $where = array(), Array $options = array(), $fname = 'Database::update') {
		$this->log(__METHOD__, "/* {$fname} */ {$table}: UPDATE " . json_encode($values) . ' WHERE ' . json_encode($where));

		// LIMIT 1
		$options['multiple'] = !empty($options['multiple']);

		$this->time();
		$this->db->selectCollection($table)->update($where, $values, $options);
		$this->timeEnd();
	}

	/**
	 * Insert a single row into a table using following values
	 */
	public function insert($table, Array $row, Array $options = array(), $fname = 'Database::insert') {
		$this->log(__METHOD__, "/* {$fname} */ {$table}: INSERT " . json_encode($row));

		$this->time();
		$this->db->selectCollection($table)->insert($row);
		$this->timeEnd();

		return $row['_id'];
	}

	/**
	 * Insert multiple rows into a table using following values
	 */
	public function insertRows($table, Array $rows, Array $options = array(), $fname = 'Database::insertRows') {
		foreach($rows as $row) {
			$this->insert($table, $row, $options, $fname);
		}
	}

	/**
	 * Returns number of items in a given collection
	 */
	public function count($table, Array $query = array(), $fname = 'DatabaseMongo::count') {
		$this->log(__METHOD__, "/* {$fname} */ COUNT {$table} WHERE " . json_encode($query));

		return $this->db->selectCollection($table)->count($query);
	}

	/**
	 * Returns a list of distinct values for the given key across a collection
	 *
	 * @see http://www.php.net/manual/en/mongodb.command.php
	 * @see http://www.php.net/manual/en/mongocollection.distinct.php (PECL mongo >=1.2.11)
	 *
	 * @return mixed|bool result
	 */
	public function distinct($table, $key, Array $query = array(), $fname = 'DatabaseMongo::distinct') {
		$this->log(__METHOD__, "/* {$fname} */ DISTINCT {$key} IN {$table} WHERE " . json_encode($query));

		$res = $this->db->command(array('distinct' => $table, 'key' => $key, 'query' => $query));

		return !empty($res['ok']) ? $res['values'] : false;
	}

	/**
	 * Returns a result of Map/Reduce operation
	 *
	 * @see http://docs.mongodb.org/manual/applications/map-reduce/
	 * @see http://stackoverflow.com/questions/3002841/mongo-map-reduce-first-time
	 */
	public function mapReduce($table, $map, $reduce, Array $query = array(), $fname = 'DatabaseMongo::mapReduce') {
		$mapFunc = new MongoCode($map);
		$reduceFunc = new MongoCode($reduce);

		$this->log(__METHOD__, "/* {$fname} */ MAP REDUCE ON {$table} WHERE " . json_encode($query));

		$res = $this->db->command(array(
			'mapreduce' => $table,
			'map' => $mapFunc,
			'reduce' => $reduceFunc,
			'out' => array('inline' => 1),
			'query' => $query,
		));

		if (!empty($res['ok'])) {
			$this->log(__METHOD__, "took {$res['timeMillis']} ms");

			return $res['results'];
		}
		else {
			return false;
		}
	}

	/**
	 * Get primary key value for recently inserted row
	 */
	public function getInsertId() {}

	/**
	 * Get number of rows affected by the recent query
	 */
	public function getRowsAffected() {}

	/**
	 * Get number of rows in given results set
	 */
	public function numRows($results) {
		return $results->count();
	}

	/**
	 * Change the position of results cursor
	 */
	public function seekRow($results, $rowId) {
		if ($rowId === 0) {
			$results->reset();
		}
	}

	/**
	 * Get data for current row
	 */
	public function fetchRow($results) {
		try {
			$results->next();
			$row = $results->current();
		}
		catch (Exception $ex) {
			$row = null;

			$this->log(__METHOD__, $ex->getMessage());
		}

		return !is_null($row) ? $row : false;
	}

	/**
	 * Free the memory
	 */
	public function freeResults($results) {}

	/**
	 * Get information about current connection
	 */
	public function getInfo() {
		return $this->isConnected() ? 'MongoDB' : '';
	}

	/**
	 * Return true if currently connected to the database
	 */
	public function isConnected() {
		return $this->link->connected;
	}
}
