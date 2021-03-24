<?php

use JShrink\Minifier;

/**
 * JS processor
 *
 * @see https://github.com/tedious/JShrink#usage
 */

class StaticAssetsJs extends StaticAssetsProcessor
{

    /**
     * Process given JS files
     *
     * @param array $files
     * @return string
     */
    protected function process(array $files)
    {
        $content = '';

        foreach ($files as $file) {
            $content .= file_get_contents($file);
        }

        // compress JS code
        if (!$this->inDebugMode()) {
            $content = Minifier::minify($content, ['flaggedComments' => true]);
        }

        return trim($content);
    }
}
