<?php

namespace Nano;

/**
 * Wrapper for results
 */
class ResultsWrapper
{

    // results
    protected $results;

    /**
     * @param array $results
     */
    public function __construct(array $results = [])
    {
        $this->results = $results;
    }

    /**
     * Set given entry in results array
     *
     * @param string $key should be lowercase
     * @param mixed $value the value to set
     */
    public function set($key, $value)
    {
        $this->results[$key] = $value;
    }

    /**
     * Get given results entry
     *
     * To get 'data' entry use $res->getData()
     *
     * @param $name
     * @param $parameters
     * @return null
     */
    public function __call($name, $parameters)
    {
        $res = null;

        // getXXX
        if (substr($name, 0, 3) == 'get') {
            $entry = strtolower(substr($name, 3));

            if (isset($this->results[$entry])) {
                $res = $this->results[$entry];
            }
        }

        return $res;
    }
}
