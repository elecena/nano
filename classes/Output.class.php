<?php

namespace Nano;

/**
 * Abstract class for output formatting
 */
abstract class Output
{
    // data to be processed
    protected $data;

    /**
     * Creates an instance of given cache driver
     *
     * @param string $driver output driver
     * @param null $data data for the output
     * @return Output output instance
     */
    public static function factory($driver, $data = null)
    {
        $className = sprintf('Nano\\Output\\Output%s', ucfirst($driver));

        /* @var Output $instance */
        $instance = new $className();

        if (!is_null($instance)) {
            if (!is_null($data)) {
                $instance->setData($data);
            }
        }

        return $instance;
    }

    /**
     * Set data to be formatted
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Return raw data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Render current data
     *
     * @return string
     */
    abstract public function render();

    /**
     * Get value of Content-type HTTP header suitable for given output formatter
     */
    abstract public function getContentType();
}
