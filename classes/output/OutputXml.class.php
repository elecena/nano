<?php

namespace Nano\Output;

use Nano\Output;

/**
 * XML renderer
 *
 * @see http://stackoverflow.com/questions/1397036/how-to-convert-array-to-simplexml-in-php
 * @see http://stackoverflow.com/questions/99350/php-associative-arrays-to-and-from-xml
 */
class OutputXml extends Output
{

    /**
     * Converts given array into XML and adds nodes into given element
     */
    private function arrayToXml(array &$data, \SimpleXMLElement $node)
    {
        foreach ($data as $key => $val) {
            $key = strtolower($key);

            if (is_array($val)) {
                $child = is_numeric($key) ? $node->addChild('item') : $node->addChild($key);
                $this->arrayToXml($val, $child);
            } else {
                if (is_numeric($key)) {
                    $node->addChild('value', $val);
                } else {
                    $node->$key = $val;
                }
            }
        }
    }

    /**
     * Render current data
     */
    public function render()
    {
        $xml = new \SimpleXMLElement('<root />');

        // recursively add data to XML
        $this->arrayToXml($this->data, $xml);

        // render XML
        return trim($xml->asXML());
    }

    /**
     * @see http://www.ietf.org/rfc/rfc3023.txt
     */
    public function getContentType()
    {
        return 'text/xml; charset=UTF-8';
    }
}
