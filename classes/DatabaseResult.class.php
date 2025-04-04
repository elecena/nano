<?php

/**
 * Wrapper for database query results
 *
 * @see http://pl2.php.net/manual/en/class.iterator.php
 */

class DatabaseResult implements Iterator, Countable
{
    // current position in results
    protected int $pos;

    // current row
    protected array|bool|null $currentRow;

    public function __construct(protected Database $database, protected $results)
    {
        // reset position
        $this->pos = 0;
    }

    /**
     * Return number of rows in result
     */
    public function numRows()
    {
        return $this->database->numRows($this->results);
    }

    /**
     * Change the position of results cursor
     */
    public function seek($rowId)
    {
        $this->database->seekRow($this->results, $rowId);
    }

    /**
     * Return data from current row
     *
     * @return array|bool data
     */
    public function fetchRow()
    {
        return $this->database->fetchRow($this->results);
    }

    /**
     * Return first field from current row
     */
    public function fetchField()
    {
        $row = $this->fetchRow();

        return !empty($row) ? reset($row) : false;
    }

    /**
     * Free results
     */
    public function free()
    {
        $this->database->freeResults($this->results);
    }

    /**
     * Implement Countable interface
     */
    public function count(): int
    {
        return $this->numRows();
    }

    /**
     * Implement Iterator interface
     */
    public function rewind(): void
    {
        if ($this->numRows()) {
            $this->seek(0);
        }
        $this->pos = 0;
        $this->currentRow = null;
    }

    #[ReturnTypeWillChange]
    public function current()
    {
        if (is_null($this->currentRow)) {
            $this->next();
        }

        return $this->currentRow;
    }

    public function key(): int
    {
        return $this->pos;
    }

    #[ReturnTypeWillChange]
    public function next()
    {
        $this->pos++;
        $this->currentRow = $this->fetchRow();
        return $this->currentRow;
    }

    public function valid(): bool
    {
        return $this->current() !== false;
    }
}
