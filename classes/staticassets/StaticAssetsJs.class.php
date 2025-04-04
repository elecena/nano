<?php

use MatthiasMullie\Minify;

/**
 * JS processor
 *
 * @see https://github.com/matthiasmullie/minify?tab=readme-ov-file#usage
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
            $minifier = new Minify\JS($content);
            $content = $minifier->minify();
        }

        return trim($content);
    }
}
