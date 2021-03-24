<?php

/**
 * nanoPortal base class
 */

class Nano
{
    const VERSION = '0.01';

    // core directory
    private static $dir = '';

    // core libraries directory
    private static $libraryDir = '';

    private static $initialized = false;

    /**
     * Initialize framework (initialize classes autoloader, set directories)
     */
    public static function init()
    {
        if (self::$initialized) {
            return;
        }

        // set framework's directory
        $dir = __DIR__ . '/..';

        // setup paths
        self::$dir = realpath($dir);
        self::$libraryDir = self::$dir . '/lib';

        self::$initialized = true;

        date_default_timezone_set('Europe/Berlin');
    }

    /**
     * Creates new instance of Nano application based on given configuration
     */
    public static function app($dir, $configSet = 'default')
    {
        // initialize framework
        Nano::init();

        // create new application
        $app = new NanoApp($dir, $configSet);

        return $app;
    }

    /**
     * Creates a CLI script from given class
     *
     * @param string $dir
     * @param $scriptClass
     * @param string string $configSet
     * @return NanoScript
     */
    public static function script($dir, $scriptClass, $configSet = 'default')
    {
        // initialize framework
        Nano::init();

        // create new application
        $app = new NanoCliApp($dir, $configSet, $scriptClass::LOGFILE);

        /* @var NanoScript $script */
        $script = new $scriptClass($app);

        // call NanoScript::onTearDown method when the script is completed
        $app->getEvents()->bind('NanoAppTearDown', [$script, 'onTearDown']);

        return $script;
    }

    /**
     * Creates new instance of Nano application for command line
     *
     * @deprecated use Nano::script
     */
    public static function cli($dir, $logFile = 'script', $configSet = 'default')
    {
        // initialize framework
        Nano::init();

        // create new application
        $app = new NanoCliApp($dir, $configSet, $logFile);

        return $app;
    }

    /**
     * Return path to nanoPortal core
     */
    public static function getCoreDirectory()
    {
        return self::$dir;
    }

    /**
     * Return path to nanoPortal libraries
     */
    public static function getLibDirectory()
    {
        return self::$libraryDir;
    }

    /**
     * Add given library to include_path
     */
    public static function addLibrary($directory)
    {
        // normalize path
        $fullPath = self::getLibDirectory() . '/' . $directory;

        // update include_path
        set_include_path(get_include_path() . PATH_SEPARATOR . $fullPath);
    }
}

Nano::init();
