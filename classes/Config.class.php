<?php

namespace Nano;

/**
 * Configuration class
 */
class Config
{

    // directory to load config files from
    private $dir = 0;

    // ley => value list of settings
    private $settings;

    /**
     * Set directory for config files
     */
    public function __construct($dir)
    {
        $this->dir = $dir;
        $this->settings = array();
    }

    /**
     * Return path to config directory
     */
    public function getDirectory()
    {
        return $this->dir;
    }

    /**
     * Gets setting value
     *
     * Uses $default when key is not found, when both read value and $default are arrays they're merged
     */
    public function get($key, $default = null)
    {
        if (isset($this->settings[$key])) {
            $ret =$this->settings[$key];

            // merge with defaults
            if (is_array($ret) && is_array($default)) {
                $ret = array_merge($default, $ret);
            }
        } else {
            $ret = $default;
        }

        return $ret;
    }

    /**
     * Sets setting value
     */
    public function set($key, $value)
    {
        $this->settings[$key] = $value;
    }

    /**
     * Deletes given setting
     */
    public function delete($key)
    {
        unset($this->settings[$key]);
    }

    /**
     * Load settings from given set - <$dir>/<$configSet>.config.php
     */
    public function load($configSet)
    {
        $file = "{$this->dir}/{$configSet}.config.php";

        if (is_readable($file)) {
            $config = array();

            // load config file
            include $file;

            if (is_array($config)) {
                foreach ($config as $key => $value) {
                    // handle nested keys
                    // $config['foo']['bar'] = 'test'; // store as 'foo.bar' = 'test'
                    if (is_array($value)) {
                        foreach ($value as $subkey => $subvalue) {
                            $this->settings[$key. '.' . $subkey] = $subvalue;
                        }
                    }

                    // store as 'foo' = array('bar' => 'test') too
                    $this->settings[$key] = $value;
                }
            }

            return true;
        } else {
            return false;
        }
    }
}
