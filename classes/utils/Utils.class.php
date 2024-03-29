<?php

/**
 * nanoPortal utilities class
 */

class Utils
{
    /**
     * Creates temporary file and returns its name
     *
     * @see http://www.php.net/manual/en/function.tempnam.php
     */
    public static function getTempFile()
    {
        return tempnam(false /* use system default */, 'nano');
    }
}
