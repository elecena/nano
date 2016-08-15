<?php

/**
 * Database access layer for MongoDB
 *
 * @see http://php.net/manual/en/mongodb.installation.pecl.php
 *
 * sudo apt-get install php-pear php5-dev pkg-config
 * sudo pecl install mongodb
 *
 * @see http://php.net/manual/en/mongodb.tutorial.library.php
 */
class DatabaseMongo extends Database {

	/* @var MongoDB\Database */
	private $db;

	/* @var MongoDB\Client */
	protected $link;

	const DEFAULT_PORT = 27017;

	/**
	 * Connect to a database
	 *
	 * @param NanoApp $app
	 * @param array $settings
	 * @param string $name
	 */
	protected function __construct(NanoApp $app, Array $settings, $name) {
		parent::__construct($app, $settings, $name);

		// mongodb://[username:password@]host1[:port1][,host2[:port2:],...]/db
		$dsn = sprintf('mongodb://%s:%d/%s',
			$settings['host'],
			isset($settings['port']) ? $settings['port']: self::DEFAULT_PORT,
			$settings['database']
		);

		$this->log(__METHOD__, 'connecting with "' . $dsn . '"...');
		$this->debug->time('connect');

		// @see http://php.net/manual/en/mongodb.tutorial.library.php
		$this->link = new MongoDB\Client($dsn, [
			'username' => $settings['user'],
			'password' => $settings['pass'],
		]);

		$this->db = $this->link->selectDatabase($settings['database']);
		$time = $this->debug->timeEnd('connect');

		$this->log(__METHOD__, 'connected with ' . $settings['host'], $time);
		$this->stats->timing('time.connection', round($time * 1000) /* ms */);
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
		# NOP
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
	public function select($table, $fields, $where = [], Array $options = [], $fname = 'Database::select') {
		$this->log(__METHOD__, "/* {$fname} */ {$table}: SELECT WHERE " . json_encode($where));

		$this->time();

		$opts = [];

		if (isset($options['order'])) {
			$opts['sort'] = $options['order'];
		}

		if (isset($options['limit'])) {
			$opts['limit'] = intval($options['limit']);
		}

		$cursor = $this->db->selectCollection($table)->find($where, $options);

		# cast results to array
		$cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

		$this->timeEnd();

		// wrap results into iterator
		$resultsIterator = new \ArrayIterator($cursor->toArray());
		return new DatabaseResult($this, $resultsIterator);
	}

	/**
	 * Select given fields from a table using following WHERE statements (return single row)
	 */
	public function selectRow($table, $fields, $where = [], Array $options = [], $fname = 'Database::selectRow') {
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
	public function selectField($table, $field, $where = [], Array $options = [], $fname = 'Database::selectField') {
		$row = $this->selectRow($table, [$field], $where, $options, $fname);

		return !empty($row) && isset($row[$field]) ? $row[$field] : false;
	}

	/**
	 * Remove rows from a table using following WHERE statements
	 */
	public function delete($table, $where = [], Array $options = [], $fname = 'Database::delete') {
		$this->log(__METHOD__, "/* {$fname} */ {$table}: REMOVE WHERE " . json_encode($where));

		// @see http://php.net/manual/en/mongocollection.remove.php
		$this->time();
		$this->db->selectCollection($table)->deleteOne($where, $options);
		$this->timeEnd();
	}

	/**
	 * Remove single row from a table using following WHERE statements
	 */
	public function deleteRow($table, $where = [], $fname = 'Database::deleteRow') {
		$this->delete($table, $where, [
			'justOne' => true // LIMIT 1
		], $fname);
	}

	/**
	 * Update a table using following values for rows matching WHERE statements
	 *
	 * @see http://dev.mysql.com/doc/refman/5.5/en/update.html
	 */
	public function update($table, Array $values, $where = [], Array $options = [], $fname = 'Database::update') {
		$this->log(__METHOD__, "/* {$fname} */ {$table}: UPDATE " . json_encode($values) . ' WHERE ' . json_encode($where));

		$options = [
			'upsert' => true,
		];

		$this->time();
		try {
			$this->db->selectCollection($table)->updateOne($where, $values, $options);
		}
		catch (\MongoDB\Exception\InvalidArgumentException $ex) {
			# handle First key in $update argument is not an update operator
			$this->db->selectCollection($table)->replaceOne($where, $values, $options);
		}
		$this->timeEnd();
	}

	/**
	 * Insert a single row into a table using following values
	 */
	public function insert($table, Array $row, Array $options = [], $fname = 'Database::insert') {
		$this->log(__METHOD__, "/* {$fname} */ {$table}: INSERT " . json_encode($row));

		$this->time();
		$this->db->selectCollection($table)->insertOne($row);
		$this->timeEnd();

		return $row['_id'];
	}

	/**
	 * Insert multiple rows into a table using following values
	 */
	public function insertRows($table, Array $rows, Array $options = [], $fname = 'Database::insertRows') {
		foreach($rows as $row) {
			$this->insert($table, $row, $options, $fname);
		}
	}

	/**
	 * Returns number of items in a given collection
	 */
	public function count($table, Array $query = [], $fname = 'DatabaseMongo::count') {
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
	public function distinct($table, $key, Array $query = [], $fname = 'DatabaseMongo::distinct') {
		$this->log(__METHOD__, "/* {$fname} */ DISTINCT {$key} IN {$table} WHERE " . json_encode($query));

		$res = $this->db->command(['distinct' => $table, 'key' => $key, 'query' => $query]);

		return !empty($res['ok']) ? $res['values'] : false;
	}

	/**
	 * Returns a result of Map/Reduce operation
	 *
	 * @see http://docs.mongodb.org/manual/applications/map-reduce/
	 * @see http://stackoverflow.com/questions/3002841/mongo-map-reduce-first-time
	 */
	public function mapReduce($table, $map, $reduce, Array $query = [], $fname = 'DatabaseMongo::mapReduce') {
		$mapFunc = new MongoCode($map);
		$reduceFunc = new MongoCode($reduce);

		$this->log(__METHOD__, "/* {$fname} */ MAP REDUCE ON {$table} WHERE " . json_encode($query));

		$res = $this->db->command([
			'mapreduce' => $table,
			'map' => $mapFunc,
			'reduce' => $reduceFunc,
			'out' => ['inline' => 1],
			'query' => $query,
		]);

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
	 *
	 * @param \ArrayIterator $results
	 * @return int
	 */
	public function numRows($results) {
		return $results->count();
	}

	/**
	 * Change the position of results cursor
	 *
	 * @param \ArrayIterator $results
	 * @param int $rowId
	 */
	public function seekRow($results, $rowId) {
		if ($rowId === 0) {
			$results->rewind();
		}
	}

	/**
	 * Get data for current row
	 *
	 * @param \ArrayIterator $results
	 * @return array|false
	 */
	public function fetchRow($results) {
		try {
			$row = $results->current();
			$results->next();
		}
		catch (Exception $ex) {
			$row = null;

			$this->log(__METHOD__, $ex->getMessage());
		}

		return !is_null($row) ? $row : false;
	}

	/**
	 * Free the memory
	 *
	 * @param \ArrayIterator $results
	 */
	public function freeResults($results) {}

	/**
	 * Get information about current connection
	 */
	public function getInfo() {
		return 'MongoDB';
	}

	/**
	 * Return true if currently connected to the database
	 */
	public function isConnected() {
		return isset($this->link);
	}

	/**
	 * Returns an instance on MongoDB timestamp (in msec)
	 *
	 * @param int|bool $date false for now
	 * @return \MongoDB\BSON\UTCDateTime
	 */
	static public function getDate($date = false) {
		return new \MongoDB\BSON\UTCDateTime( $date ?: time() * 1000 );
	}
}
